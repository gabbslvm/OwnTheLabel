<?php
require "connection.php";

$_SESSION = [];
session_destroy();

header_remove("Content-Type");
header("Location: ../login/login.html", true, 303);
exit;