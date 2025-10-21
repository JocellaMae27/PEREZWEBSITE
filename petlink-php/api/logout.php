<?php
require_once '../config/database.php'; // <-- ADD THIS
require_once '../core/logger.php';

session_start();

if (isset($_SESSION['user'])) {
    log_action($pdo, 'LOGOUT', "User '{$_SESSION['user']['username']}' logged out.");
}


session_destroy();
echo json_encode(['status' => 'success', 'message' => 'Logged out successfully.']);