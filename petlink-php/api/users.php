<?php
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../core/logger.php'; 

session_start();

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);

if ($method !== 'GET') {
    check_csrf();
}

$userModel = new User($pdo);

try {
    switch ($method) {
        case 'POST':
            check_admin();
            if (empty($data['username']) || empty($data['password']) || empty($data['fullName']) || empty($data['role'])) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
                exit();
            }
            $userModel->create($data);
            log_action($pdo, 'USER_CREATE', "Admin created a new user: '{$data['username']}'."); // <-- ADD THIS
            echo json_encode(['status' => 'success']);
            break;

        case 'PUT':
            check_auth();
            if ($data['type'] === 'updateInfo') {
                check_admin();
                $userModel->updateInfo($data);
                log_action($pdo, 'USER_UPDATE', "Admin updated info for user: '{$data['username']}'."); // <-- ADD THIS
            } elseif ($data['type'] === 'changePassword') {
                if ($_SESSION['user']['username'] !== $data['username']) {
                    http_response_code(403);
                    echo json_encode(['status' => 'error', 'message' => 'Forbidden.']);
                    exit();
                }
                $success = $userModel->changePassword($data['username'], $data['oldPassword'], $data['newPassword']);
                log_action($pdo, 'PASSWORD_CHANGE', "User '{$data['username']}' changed their own password."); // <-- ADD THIS
                if (!$success) {
                    http_response_code(401);
                    echo json_encode(['status' => 'error', 'message' => 'Incorrect current password.']);
                    exit();
                }
            } else {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Invalid update type.']);
                exit();
            }
            echo json_encode(['status' => 'success']);
            break;

        case 'DELETE':
            check_admin();
            $username = $_GET['id'] ?? null;
            if ($username === $_SESSION['user']['username']) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'You cannot delete your own account.']);
                exit();
            }
            $userModel->delete($username);
            log_action($pdo, 'USER_DELETE', "Admin soft-deleted user: '{$username}'."); // <-- ADD THI
            echo json_encode(['status' => 'success']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
            break;
    }
} catch (PDOException $e) {
    if ($e->errorInfo[1] == 1062) {
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'Username already exists.']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}