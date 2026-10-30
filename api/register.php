<?php
require "connection.php";
require "validate.php";
header("Content-Type: application/json");

$d = json_decode(file_get_contents("php://input"), true);

$name = '';
if (isset($d['name'])) {
    $name = trim($d['name']);
}

$email = '';
if (isset($d['email'])) {
    $email = $d['email'];
}
$email = cleanEmail($email);

$password = '';
if (isset($d['password'])) {
    $password = $d['password'];
}

if ($name == '' || $email == '' || $password == '') {
    http_response_code(400);
    echo json_encode(["error" => "All fields are required."]);
    exit;
}
if (!isValidEmail($email)) {
    http_response_code(400);
    echo json_encode(["error" => "Enter a valid email."]);
    exit;
}
if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(["error" => "Password must be at least 6 characters."]);
    exit;
}

$check = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$checkResult = $check->get_result();
if ($checkResult->num_rows > 0) {
    http_response_code(409);
    echo json_encode(["error" => "Email is already registered."]);
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'customer')");
$stmt->bind_param("sss", $name, $email, $hash);
$stmt->execute();

session_regenerate_id(true);
$_SESSION['user_id'] = $conn->insert_id;
$_SESSION['name'] = $name;
$_SESSION['role'] = 'customer';

echo json_encode(["ok" => true, "name" => $name, "role" => "customer"]);