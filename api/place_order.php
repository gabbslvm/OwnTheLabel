<?php
require "connection.php";
require "validate.php";
header("Content-Type: application/json");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);


const SHIPPING_FEE = 100;
const FREE_SHIPPING_MIN = 2500;
const MAX_LINES = 30;
const MAX_QTY = 20;

class OrderError extends Exception {
    public $status;
    public function __construct($message, $status = 400) {
        parent::__construct($message);
        $this->status = $status;
    }
}

function reply($status, $data) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function text($d, $key) {
    return isset($d[$key]) && is_string($d[$key]) ? trim($d[$key]) : '';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    reply(405, ["error" => "Method not allowed."]);
}
if (!isset($_SESSION['user_id'])) {
    reply(401, ["error" => "Please log in to place an order."]);
}

$d = json_decode(file_get_contents("php://input"), true);
if (!is_array($d)) {
    reply(400, ["error" => "Invalid request."]);
}


$fullName = text($d, 'full_name');
$email    = cleanEmail(text($d, 'email'));
$phone    = text($d, 'phone');
$address  = text($d, 'address');
$city     = text($d, 'city');
$postal   = text($d, 'postal');
$payment  = text($d, 'payment');

if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 100) reply(400, ["error" => "Please enter your full name."]);
if (!isValidEmail($email))                                  reply(400, ["error" => "Please enter a valid email."]);
if (!preg_match('/^09[0-9]{9}$/', $phone))                  reply(400, ["error" => "Mobile number must be 09XXXXXXXXX."]);
if (mb_strlen($address) < 5 || mb_strlen($address) > 255)   reply(400, ["error" => "Please enter your address."]);
if (mb_strlen($city) < 2 || mb_strlen($city) > 80)          reply(400, ["error" => "Please enter your city."]);
if (!preg_match('/^[0-9]{4}$/', $postal))                   reply(400, ["error" => "Postal code must be 4 digits."]);
if (!in_array($payment, ['cod', 'gcash', 'card'], true))    reply(400, ["error" => "Choose a payment method."]);


$lines = [];
$rawItems = $d['items'] ?? null;
if (!is_array($rawItems) || !$rawItems) {
    reply(400, ["error" => "Your cart is empty."]);
}
foreach ($rawItems as $it) {
    $vid = (int)($it['variantId'] ?? 0);
    $qty = (int)($it['qty'] ?? 0);
    if ($vid < 1 || $qty < 1) {
        reply(400, ["error" => "Your cart has an invalid item."]);
    }
    $lines[$vid] = ($lines[$vid] ?? 0) + $qty;
}
if (count($lines) > MAX_LINES) {
    reply(400, ["error" => "Too many different items in one order."]);
}
foreach ($lines as $q) {
    if ($q > MAX_QTY) {
        reply(400, ["error" => "You can order up to " . MAX_QTY . " of the same item."]);
    }
}
ksort($lines); 

$userId = (int)$_SESSION['user_id'];

try {
    $conn->begin_transaction();

    $pick = $conn->prepare(
        "SELECT v.id, v.size, v.color, v.stock, p.name, p.price, p.is_active
         FROM product_variants v
         JOIN products p ON p.id = v.product_id
         WHERE v.id = ? FOR UPDATE"
    );
    $take = $conn->prepare("UPDATE product_variants SET stock = stock - ? WHERE id = ? AND stock >= ?");

    $items = [];
    $subtotal = 0.0;

    foreach ($lines as $vid => $qty) {
        $pick->bind_param("i", $vid);
        $pick->execute();
        $row = $pick->get_result()->fetch_assoc();

        if (!$row || !(int)$row['is_active']) {
            throw new OrderError("An item in your cart is no longer available. Please remove it and try again.", 409);
        }

        $label = $row['name'] . " (" . $row['size'] . ", " . $row['color'] . ")";
        $stock = (int)$row['stock'];
        if ($stock < 1) {
            throw new OrderError($label . " is sold out.", 409);
        }
        if ($stock < $qty) {
            throw new OrderError("Only " . $stock . " left of " . $label . ".", 409);
        }

        $take->bind_param("iii", $qty, $vid, $qty);
        $take->execute();
        if ($take->affected_rows !== 1) {
            throw new OrderError($label . " just sold out. Please review your cart.", 409);
        }

        $price = (float)$row['price'];
        $subtotal += $price * $qty;
        $items[] = [
            "variant_id" => $vid,
            "name"       => $row['name'],
            "size"       => $row['size'],
            "color"      => $row['color'],
            "price"      => $price,
            "qty"        => $qty
        ];
    }

    $subtotal = round($subtotal, 2);
    $shipping = $subtotal >= FREE_SHIPPING_MIN ? 0.0 : (float)SHIPPING_FEE;
    $total    = round($subtotal + $shipping, 2);
    $orderNo  = "OTL-" . date("Ymd") . "-" . strtoupper(bin2hex(random_bytes(3)));

    $ins = $conn->prepare(
        "INSERT INTO orders (order_no, user_id, full_name, email, phone, address, city, postal, payment_method, subtotal, shipping, total)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $ins->bind_param("sisssssssddd", $orderNo, $userId, $fullName, $email, $phone, $address, $city, $postal, $payment, $subtotal, $shipping, $total);
    $ins->execute();
    $orderId = $conn->insert_id;

    $lineIns = $conn->prepare(
        "INSERT INTO order_items (order_id, variant_id, product_name, size, color, price, qty)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    foreach ($items as $it) {
        $lineIns->bind_param("iisssdi", $orderId, $it['variant_id'], $it['name'], $it['size'], $it['color'], $it['price'], $it['qty']);
        $lineIns->execute();
    }

    $info = $conn->prepare("SELECT status, created_at FROM orders WHERE id = ?");
    $info->bind_param("i", $orderId);
    $info->execute();
    $meta = $info->get_result()->fetch_assoc();

    $conn->commit();

    reply(200, [
        "ok" => true,
        "order" => [
            "order_no"       => $orderNo,
            "status"         => $meta['status'],
            "created_at"     => $meta['created_at'],
            "payment_method" => $payment,
            "customer"       => [
                "full_name" => $fullName,
                "email"     => $email,
                "phone"     => $phone,
                "address"   => $address,
                "city"      => $city,
                "postal"    => $postal
            ],
            "items"    => array_map(function ($i) {
                unset($i['variant_id']);
                return $i;
            }, $items),
            "subtotal" => $subtotal,
            "shipping" => $shipping,
            "total"    => $total
        ]
    ]);
} catch (OrderError $e) {
    $conn->rollback();
    reply($e->status, ["error" => $e->getMessage()]);
} catch (Throwable $e) {
    $conn->rollback();
    error_log("place_order failed: " . $e->getMessage());
    reply(500, ["error" => "Could not place your order. Please try again."]);
}