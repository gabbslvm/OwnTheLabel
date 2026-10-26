<?php
require "connection.php";
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Please log in to view your orders."]);
    exit;
}

$uid = (int)$_SESSION['user_id'];

$stmt = $conn->prepare(
    "SELECT id, order_no, full_name, email, phone, address, city, postal,
            payment_method, subtotal, shipping, total, status, created_at
    FROM orders WHERE user_id = ? ORDER BY id DESC"
);
$stmt->bind_param("i", $uid);
$stmt->execute();
$res = $stmt->get_result();

$orders = [];
while ($row = $res->fetch_assoc()) {
    $row['id'] = (int)$row['id'];
    $row['subtotal'] = (float)$row['subtotal'];
    $row['shipping'] = (float)$row['shipping'];
    $row['total'] = (float)$row['total'];
    $row['items'] = [];
    $orders[$row['id']] = $row;
}

if ($orders) {
    $ids = implode(",", array_keys($orders));
    $items = $conn->query(
        "SELECT order_id, product_name, size, color, price, qty
        FROM order_items WHERE order_id IN ($ids) ORDER BY id"
    );
    while ($it = $items->fetch_assoc()) {
        $orders[(int)$it['order_id']]['items'][] = [
            "name"  => $it['product_name'],
            "size"  => $it['size'],
            "color" => $it['color'],
            "price" => (float)$it['price'],
            "qty"   => (int)$it['qty']
        ];
    }
}

echo json_encode(array_values($orders));