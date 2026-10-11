<?php
require "connection.php";
header("Content-Type: application/json");

$d = json_decode(file_get_contents("php://input"), true);
$email = trim($d['email'] ?? '');
$password = $d['password'] ?? '';

if ($email === '' || $password === '') {
    http_response_code(400);
    echo json_encode(["error" => "Email and password are required."]);
    exit;
}

$stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();

if ($u && password_verify($password, $u['password'])) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $u['id'];
    $_SESSION['name'] = $u['name'];
    $_SESSION['role'] = $u['role'];
    echo json_encode(["ok" => true, "name" => $u['name'], "role" => $u['role']]);
} else {
    http_response_code(401);
    echo json_encode(["error" => "Invalid email or password."]);
}