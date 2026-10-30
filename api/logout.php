<?php
require "connection.php";

$_SESSION = [];
session_destroy();

header("Location: ../login/login.html");
exit;