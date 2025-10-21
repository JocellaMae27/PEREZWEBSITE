<?php
require_once '../config/database.php';
require_once '../core/logger.php';
session_start();

// Load application configuration
$appConfig = require '../config/app.php';
$max_attempts = $appConfig['login_max_attempts'];
$lockout_minutes = $appConfig['login_lockout_minutes'];

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['username']) || empty($data['password'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Username and password are required.']);
    exit();
}

$username = $data['username'];
$password = $data['password'];
$ip_address = $_SERVER['REMOTE_ADDR'];

try {
    // 1. Check for existing lockouts
    $lockout_time = date('Y-m-d H:i:s', strtotime("-{$lockout_minutes} minutes"));
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM login_attempts WHERE (ip_address = ? OR username = ?) AND attempt_time > ?"
    );
    $stmt->execute([$ip_address, $username, $lockout_time]);
    $attempts_count = $stmt->fetchColumn();

    if ($attempts_count >= $max_attempts) {
        http_response_code(429); // 429 Too Many Requests
        echo json_encode(['status' => 'error', 'message' => "Too many failed login attempts. Please try again in {$lockout_minutes} minutes."]);
        exit();
    }

    // 2. Attempt to verify user credentials
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND deleted_at IS NULL");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // 3. If login succeeds, clear previous attempts and set session
        $stmt_clear = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ? OR username = ?");
        $stmt_clear->execute([$ip_address, $username]);

        $_SESSION['user'] = [
            'username' => $user['username'],
            'fullName' => $user['full_name'],
            'role' => $user['role']
        ];
        $_SESSION['last_activity'] = time(); // Initialize activity timer on login
        log_action($pdo, 'LOGIN_SUCCESS', "User '{$username}' logged in successfully.");
        echo json_encode(['status' => 'success', 'user' => $_SESSION['user']]);
    } else {
        // 4. If login fails, record the attempt
        $stmt_log = $pdo->prepare("INSERT INTO login_attempts (ip_address, username, attempt_time) VALUES (?, ?, NOW())");
        $stmt_log->execute([$ip_address, $username]);
        log_action($pdo, 'LOGIN_FAILURE', "Failed login attempt for username '{$username}'.");
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Invalid username or password.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}