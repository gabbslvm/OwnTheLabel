<?php
require "../api/connection.php";

if (($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login/login.html");
    exit;
}