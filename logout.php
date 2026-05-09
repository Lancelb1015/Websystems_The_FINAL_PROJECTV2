<?php
session_start();
require __DIR__ . '/db_connect.php';
require_once __DIR__ . '/auth.php';

alphatech_clear_remember_cookie($conn);

session_unset();
session_destroy();
header("Location: index.php");
exit();
?>
