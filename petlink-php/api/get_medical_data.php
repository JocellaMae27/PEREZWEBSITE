<?php
require_once '../config/database.php';
require_once '../core/security.php';
require_once '../models/MedicalRecord.php';

check_auth();

$appointmentId = $_GET['appointment_id'] ?? null;

if (!$appointmentId) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Appointment ID is required.']);
    exit();
}

try {
    $medicalRecordModel = new MedicalRecord($pdo);
    $data = $medicalRecordModel->getDataForModal($appointmentId);

    echo json_encode(['status' => 'success', 'data' => sanitize_output($data)]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}