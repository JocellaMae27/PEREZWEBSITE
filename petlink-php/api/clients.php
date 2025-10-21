<?php
require_once '../config/database.php';
require_once '../models/Client.php';
require_once '../core/logger.php';

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);

check_auth();

if ($method !== 'GET') {
    check_csrf();
}

$clientModel = new Client($pdo);

try {
    switch ($method) {
        case 'POST':
            $clientModel->create($data);
            $lastId = $pdo->lastInsertId();
            log_action($pdo, 'CLIENT_CREATE', "Created client '{$data['fullName']}' (ID: {$lastId})."); // <-- ADD THIS
            echo json_encode(['status' => 'success']);
            break;
        case 'PUT':
            $clientModel->update($data);
            log_action($pdo, 'CLIENT_UPDATE', "Updated client '{$data['fullName']}' (ID: {$data['id']})."); // <-- ADD THIS
            echo json_encode(['status' => 'success']);
            break;
        case 'DELETE':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Client ID is required.']);
                exit();
            }
            $clientModel->delete($id);
            log_action($pdo, 'CLIENT_DELETE', "Soft-deleted client (ID: {$id})."); // <-- ADD THIS
            echo json_encode(['status' => 'success']);
            break;
        default:
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
            break;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}