<?php
require "../api/connection.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login/login.html");
    exit;
}