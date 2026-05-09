<?php
session_start();
require __DIR__ . '/db_connect.php';
require_once __DIR__ . '/auth.php';

alphatech_try_remember_login($conn);

header('Content-Type: application/json; charset=UTF-8');
echo json_encode([
    'logged_in' => !empty($_SESSION['user_id']),
    'user_id' => $_SESSION['user_id'] ?? null,
    'user_name' => $_SESSION['user_name'] ?? null,
]);

