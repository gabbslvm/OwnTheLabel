<?php
require "connection.php";
require "validate.php";
header("Content-Type: application/json");

$SHIPPING_FEE = 100;
$FREE_SHIPPING_MIN = 2500;

function reply($status, $data)
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function readText($d, $key)
{
    if (isset($d[$key]) && is_string($d[$key])) {
        return trim($d[$key]);
    }
    return '';
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    reply(405, ["error" => "Method not allowed."]);
}
if (!isset($_SESSION['user_id'])) {
    reply(401, ["error" => "Please log in to place an order."]);
}

$d = json_decode(file_get_contents("php://input"), true);
if (!is_array($d)) {
    reply(400, ["error" => "Invalid request."]);
}

$fullName = readText($d, 'full_name');
$email    = cleanEmail(readText($d, 'email'));
$phone    = readText($d, 'phone');
$address  = readText($d, 'address');
$city     = readText($d, 'city');
$postal   = readText($d, 'postal');
$payment  = readText($d, 'payment');

if (strlen($fullName) < 2 || strlen($fullName) > 100) {
    reply(400, ["error" => "Please enter your full name."]);
}
if (!isValidEmail($email)) {
    reply(400, ["error" => "Please enter a valid email."]);
}
if (strlen($phone) != 11 || substr($phone, 0, 2) != "09" || !ctype_digit($phone)) {
    reply(400, ["error" => "Mobile number must be 09XXXXXXXXX."]);
}
if (strlen($address) < 5 || strlen($address) > 255) {
    reply(400, ["error" => "Please enter your address."]);
}
if (strlen($city) < 2 || strlen($city) > 80) {
    reply(400, ["error" => "Please enter your city."]);
}
if (strlen($postal) != 4 || !ctype_digit($postal)) {
    reply(400, ["error" => "Postal code must be 4 digits."]);
}
if ($payment != 'cod' && $payment != 'gcash' && $payment != 'card') {
    reply(400, ["error" => "Choose a payment method."]);
}

if (!isset($d['items']) || !is_array($d['items']) || count($d['items']) == 0) {
    reply(400, ["error" => "Your cart is empty."]);
}

$lines = [];
foreach ($d['items'] as $it) {
    $vid = 0;
    $qty = 0;
    if (isset($it['variantId'])) {
        $vid = intval($it['variantId']);
    }
    if (isset($it['qty'])) {
        $qty = intval($it['qty']);
    }

    if ($vid < 1 || $qty < 1) {
        reply(400, ["error" => "Your cart has an invalid item."]);
    }

    if (isset($lines[$vid])) {
        $lines[$vid] = $lines[$vid] + $qty;
    } else {
        $lines[$vid] = $qty;
    }
}
foreach ($lines as $q) {
    if ($q > 20) {
        reply(400, ["error" => "You can order up to 20 of the same item."]);
    }
}
ksort($lines);

$userId = (int)$_SESSION['user_id'];

$conn->begin_transaction();

$items = [];
$receiptItems = [];
$subtotal = 0;

foreach ($lines as $vid => $qty) {
    $stmt = $conn->prepare("SELECT v.size, v.color, v.stock, p.name, p.price, p.is_active
        FROM product_variants v
        JOIN products p ON p.id = v.product_id
        WHERE v.id = ? FOR UPDATE");
    $stmt->bind_param("i", $vid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row || $row['is_active'] == 0) {
        $conn->rollback();
        reply(409, ["error" => "An item in your cart is no longer available. Please remove it and try again."]);
    }

    $label = $row['name'] . " (" . $row['size'] . ", " . $row['color'] . ")";
    $stock = (int)$row['stock'];

    if ($stock < 1) {
        $conn->rollback();
        reply(409, ["error" => $label . " is sold out."]);
    }
    if ($stock < $qty) {
        $conn->rollback();
        reply(409, ["error" => "Only " . $stock . " left of " . $label . "."]);
    }

    $upd = $conn->prepare("UPDATE product_variants SET stock = stock - ? WHERE id = ?");
    $upd->bind_param("ii", $qty, $vid);
    $upd->execute();

    $price = (float)$row['price'];
    $subtotal = $subtotal + ($price * $qty);

    $items[] = [
        "variant_id" => $vid,
        "name"  => $row['name'],
        "size"  => $row['size'],
        "color" => $row['color'],
        "price" => $price,
        "qty"   => $qty
    ];
    $receiptItems[] = [
        "name"  => $row['name'],
        "size"  => $row['size'],
        "color" => $row['color'],
        "price" => $price,
        "qty"   => $qty
    ];
}

$subtotal = round($subtotal, 2);
if ($subtotal >= $FREE_SHIPPING_MIN) {
    $shipping = 0;
} else {
    $shipping = $SHIPPING_FEE;
}
$total = round($subtotal + $shipping, 2);

$orderNo = "OTL-" . date("Ymd") . "-" . strtoupper(substr(md5(uniqid()), 0, 6));

$ins = $conn->prepare("INSERT INTO orders (order_no, user_id, full_name, email, phone, address, city, postal, payment_method, subtotal, shipping, total)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$ins->bind_param("sisssssssddd", $orderNo, $userId, $fullName, $email, $phone, $address, $city, $postal, $payment, $subtotal, $shipping, $total);
if (!$ins->execute()) {
    $conn->rollback();
    reply(500, ["error" => "Could not place your order. Please try again."]);
}
$orderId = $conn->insert_id;

foreach ($items as $it) {
    $lineIns = $conn->prepare("INSERT INTO order_items (order_id, variant_id, product_name, size, color, price, qty)
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $lineIns->bind_param("iisssdi", $orderId, $it['variant_id'], $it['name'], $it['size'], $it['color'], $it['price'], $it['qty']);
    if (!$lineIns->execute()) {
        $conn->rollback();
        reply(500, ["error" => "Could not place your order. Please try again."]);
    }
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
        "customer" => [
            "full_name" => $fullName,
            "email"     => $email,
            "phone"     => $phone,
            "address"   => $address,
            "city"      => $city,
            "postal"    => $postal
        ],
        "items"    => $receiptItems,
        "subtotal" => $subtotal,
        "shipping" => $shipping,
        "total"    => $total
    ]
]);