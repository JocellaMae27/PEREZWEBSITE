<?php
require_once '../config/database.php';
require_once '../core/security.php';
require_once '../models/Appointment.php';

check_auth();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$search = $_GET['search'] ?? '';

try {
    $appointmentModel = new Appointment($pdo);
    $result = $appointmentModel->getAllPaginated($page, $limit, $search);

    echo json_encode(['status' => 'success', 'data' => sanitize_output($result)]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}