<?php
require_once '../config/database.php';
require_once '../models/MedicalRecord.php';

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);

check_auth();

if ($method === 'POST') {
    check_csrf();
}

$medicalRecordModel = new MedicalRecord($pdo);

try {
    switch ($method) {
        case 'POST':
            $medicalRecordModel->saveRecordAndInvoice($data);
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