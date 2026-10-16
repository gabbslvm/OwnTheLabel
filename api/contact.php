<?php
require "connection.php";
require "validate.php";
header("Content-Type: application/json");

function text($d, $key) {
    return isset($d[$key]) && is_string($d[$key]) ? trim($d[$key]) : '';
}

function fail($status, $msg) {
    http_response_code($status);
    echo json_encode(["error" => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail(405, "Method not allowed.");
}

$d = json_decode(file_get_contents("php://input"), true);
$first   = text($d, 'first_name');
$last    = text($d, 'last_name');
$email   = cleanEmail(text($d, 'email'));
$message = text($d, 'message');

if ($first === '' || $last === '' || $email === '' || $message === '') {
    fail(400, "All fields are required.");
}
if (mb_strlen($first) > 60 || mb_strlen($last) > 60) {
    fail(400, "Names must be 60 characters or fewer.");
}
if (!isValidEmail($email)) {
    fail(400, "Please enter a valid email address.");
}
if (mb_strlen($message) > 2000) {
    fail(400, "Message must be 2000 characters or fewer.");
}

try {
    $stmt = $conn->prepare("INSERT INTO messages (first_name, last_name, email, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $first, $last, $email, $message);
    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }
    echo json_encode(["ok" => true]);
} catch (Throwable $e) {
    error_log("contact failed: " . $e->getMessage());
    fail(500, "Could not send your message. Please try again.");
}