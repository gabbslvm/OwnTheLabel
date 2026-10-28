<?php
require "connection.php";
header("Content-Type: application/json");

function reply($status, $data)
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    reply(405, ["error" => "Method not allowed."]);
}
if (!isset($_SESSION['user_id'])) {
    reply(401, ["error" => "Please log in to rate products."]);
}
if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
    reply(403, ["error" => "Admin accounts cannot rate products."]);
}

$d = json_decode(file_get_contents("php://input"), true);
if (!is_array($d)) {
    reply(400, ["error" => "Invalid request."]);
}

$productId = isset($d['product_id']) ? intval($d['product_id']) : 0;
$rating    = isset($d['rating']) ? intval($d['rating']) : 0;

if ($productId < 1) {
    reply(400, ["error" => "Invalid product."]);
}
if ($rating < 1 || $rating > 5) {
    reply(400, ["error" => "Rating must be from 1 to 5."]);
}

$check = $conn->prepare("SELECT id FROM products WHERE id = ? AND is_active = 1");
$check->bind_param("i", $productId);
$check->execute();
if (!$check->get_result()->fetch_assoc()) {
    reply(404, ["error" => "This product is no longer available."]);
}

$userId = (int)$_SESSION['user_id'];

$save = $conn->prepare("INSERT INTO product_ratings (product_id, user_id, rating)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE rating = VALUES(rating)");
$save->bind_param("iii", $productId, $userId, $rating);
if (!$save->execute()) {
    reply(500, ["error" => "Could not save your rating. Please try again."]);
}

$sum = $conn->prepare("SELECT ROUND(AVG(rating), 1) AS avg_rating, COUNT(*) AS rating_count
    FROM product_ratings WHERE product_id = ?");
$sum->bind_param("i", $productId);
$sum->execute();
$row = $sum->get_result()->fetch_assoc();

reply(200, [
    "ok" => true,
    "product_id"   => $productId,
    "my_rating"    => $rating,
    "avg_rating"   => (float)$row['avg_rating'],
    "rating_count" => (int)$row['rating_count']
]);