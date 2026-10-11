<?php
require "connection.php";
header("Content-Type: application/json");
$d = json_decode(file_get_contents("php://input"), true);

$stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
$stmt->bind_param("s", $d['email']);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();

if ($u && password_verify($d['password'], $u['password'])) {
    $_SESSION['user_id'] = $u['id'];
    $_SESSION['name'] = $u['name'];
    $_SESSION['role'] = $u['role'];
    echo json_encode(["ok" => true, "role" => $u['role']]);
} else {
    http_response_code(401);
    echo json_encode(["error" => "Invalid email or password."]);
}