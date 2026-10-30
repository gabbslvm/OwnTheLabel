<?php
require "connection.php";
require "validate.php";
header("Content-Type: application/json");

$d = json_decode(file_get_contents("php://input"), true);

$email = '';
if (isset($d['email'])) {
    $email = $d['email'];
}
$email = cleanEmail($email);

$password = '';
if (isset($d['password'])) {
    $password = $d['password'];
}

if ($email == '' || $password == '') {
    http_response_code(400);
    echo json_encode(["error" => "Email and password are required."]);
    exit;
}

if (!isValidEmail($email)) {
    http_response_code(400);
    echo json_encode(["error" => "Please enter a valid email address."]);
    exit;
}

$stmt = $conn->prepare("SELECT id, name, password, role, status FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$u = $result->fetch_assoc();

if ($u && password_verify($password, $u['password'])) {

    if ($u['status'] == 'disabled') {
        http_response_code(403);
        echo json_encode(["error" => "This account is disabled."]);
        exit;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $u['id'];
    $_SESSION['name'] = $u['name'];
    $_SESSION['role'] = $u['role'];
    echo json_encode(["ok" => true, "name" => $u['name'], "role" => $u['role']]);

} else {
    http_response_code(401);
    echo json_encode(["error" => "Invalid email or password."]);
}