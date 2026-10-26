<?php
require "connection.php";
require "validate.php";
header("Content-Type: application/json");

function getText($d, $key)
{
    if (isset($d[$key]) && is_string($d[$key])) {
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

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    fail(405, "Method not allowed.");
}

$d = json_decode(file_get_contents("php://input"), true);
$first   = readText($d, 'first_name');
$last    = readText($d, 'last_name');
$email   = cleanEmail(readText($d, 'email'));
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
if (strlen($message) > 2000) {
    fail(400, "Message must be 2000 characters or fewer.");
}

$stmt = $conn->prepare("INSERT INTO messages (first_name, last_name, email, message) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $first, $last, $email, $message);

if ($stmt->execute()) {
    echo json_encode(["ok" => true]);
} else {
    fail(500, "Could not send your message. Please try again.");
}