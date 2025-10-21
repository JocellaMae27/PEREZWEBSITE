<?php
header("Content-Type: application/json");

$host = 'localhost';
$dbname = 'petlink_db';
$username = 'root'; // Your DB username
$password = '';     // Your DB password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

// A simple function to check if the user is authenticated
function check_auth() {
    session_start();
    
    // Load application configuration
    $appConfig = require __DIR__ . '/app.php';
    $sessionTimeoutSeconds = $appConfig['session_timeout_seconds'];

    // Check if the user has been inactive for too long.
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $sessionTimeoutSeconds)) {
        session_unset();     
        session_destroy();   
        
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Session expired due to inactivity.']);
        exit();
    }
    
    $_SESSION['last_activity'] = time();

    if (!isset($_SESSION['user'])) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit();
    }
}

// Function to check for admin role
function check_admin() {
    check_auth();
    if ($_SESSION['user']['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Forbidden: Admin access required']);
        exit();
    }
}

function check_csrf() {
    // For a JSON API, we only need to check the HTTP header.
    // The session must be started before this function is called.
    if (empty($_SESSION['csrf_token']) || !isset($_SERVER['HTTP_X_CSRF_TOKEN']) || !hash_equals($_SESSION['csrf_token'], $_SERVER['HTTP_X_CSRF_TOKEN'])) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token. Request blocked.']);
        exit();
    }
}