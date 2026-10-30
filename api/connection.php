<?php
header("Content-Type: application/json");
session_start();
$conn = new mysqli("localhost", "root", "", "otl_db");
if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(["error" => "Database connection failed."]));
}
$conn->set_charset("utf8mb4");