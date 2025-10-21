<?php
require_once '../config/database.php';
require_once '../models/Appointment.php';

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);

check_auth();

if ($method !== 'GET') {
    check_csrf();
}

$appointmentModel = new Appointment($pdo);

try {
    switch ($method) {
        case 'POST':
            $appointmentModel->create($data);
            echo json_encode(['status' => 'success', 'id' => $pdo->lastInsertId()]);
            break;

        case 'PUT':
            $appointmentModel->update($data);
            echo json_encode(['status' => 'success']);
            break;

        case 'DELETE':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Appointment ID is required.']);
                exit();
            }
            $appointmentModel->delete($id);
            echo json_encode(['status' => 'success']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
            break;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}