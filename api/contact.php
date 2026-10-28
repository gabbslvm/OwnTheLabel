<?php
require "connection.php";
require "validate.php";
require "order_rules.php";
header("Content-Type: application/json");

$TOPICS = ["General Inquiry", "Order Cancellation", "Feedback"];

function readText($d, $key)
{
    if (is_array($d) && isset($d[$key]) && is_string($d[$key])) {
        return trim($d[$key]);
    }
    return '';
}

function fail($status, $msg)
{
    http_response_code($status);
    echo json_encode(["error" => $msg]);
    exit;
}

$uid = null;
if (isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if ($uid === null) {
        fail(401, "Please log in to view your messages.");
    }
    $stmt = $conn->prepare("SELECT id, topic, order_no, message, status, reply, replied_at, created_at
        FROM messages WHERE user_id = ? ORDER BY id DESC");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
    echo json_encode($rows);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    fail(405, "Method not allowed.");
}

$d = json_decode(file_get_contents("php://input"), true);
$first   = readText($d, 'first_name');
$last    = readText($d, 'last_name');
$email   = cleanEmail(readText($d, 'email'));
$topic   = readText($d, 'topic');
$orderNo = strtoupper(readText($d, 'order_no'));
$message = readText($d, 'message');

if ($first == '' || $last == '' || $email == '' || $message == '') {
    fail(400, "All fields are required.");
}
if (strlen($first) > 60 || strlen($last) > 60) {
    fail(400, "Names must be 60 characters or fewer.");
}
if (!isValidEmail($email)) {
    fail(400, "Please enter a valid email address.");
}
if (!in_array($topic, $TOPICS)) {
    fail(400, "Please choose a topic.");
}
if (strlen($message) > 2000) {
    fail(400, "Message must be 2000 characters or fewer.");
}

if ($topic == 'Order Cancellation') {
    if ($uid === null) {
        fail(401, "Please log in to request an order cancellation.");
    }
    if ($orderNo == '') {
        fail(400, "Please enter your order number.");
    }
    $chk = $conn->prepare("SELECT id, status FROM orders WHERE order_no = ? AND user_id = ?");
    $chk->bind_param("si", $orderNo, $uid);
    $chk->execute();
    $order = $chk->get_result()->fetch_assoc();
    if (!$order) {
        fail(400, "We couldn't find that order number on your account.");
    }

    if (!order_can_cancel($order['status'])) {
        fail(409, order_cancel_block_reason($order['status']));
    }

    $dup = $conn->prepare("SELECT id FROM messages
        WHERE topic = 'Order Cancellation' AND order_no = ? AND user_id = ? AND status = 'Pending'");
    $dup->bind_param("si", $orderNo, $uid);
    $dup->execute();
    if ($dup->get_result()->num_rows > 0) {
        fail(409, "You already sent a cancellation request for this order. Please wait for our reply.");
    }
} else {
    $orderNo = '';
}

$stmt = $conn->prepare("INSERT INTO messages (user_id, first_name, last_name, email, topic, order_no, message)
    VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("issssss", $uid, $first, $last, $email, $topic, $orderNo, $message);

if ($stmt->execute()) {
    echo json_encode(["ok" => true]);
} else {
    fail(500, "Could not send your message. Please try again.");
}