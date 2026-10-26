<?php
require "connection.php";
header("Content-Type: application/json");

$_SESSION = [];
session_destroy();

echo json_encode(["ok" => true]);