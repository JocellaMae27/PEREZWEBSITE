<?php
require_once '../config/database.php';

check_admin();

try {
    $deleted_items = [];
    
    // Fetch deleted clients
    $deleted_items['clients'] = $pdo->query("SELECT id, full_name, deleted_at FROM clients WHERE deleted_at IS NOT NULL")->fetchAll();
    
    // Fetch deleted patients
    $deleted_items['patients'] = $pdo->query("SELECT id, pet_name, deleted_at FROM patients WHERE deleted_at IS NOT NULL")->fetchAll();

    // Fetch deleted appointments
    $deleted_items['appointments'] = $pdo->query("
        SELECT a.id, p.pet_name, a.deleted_at 
        FROM appointments a
        LEFT JOIN patients p ON a.patient_id = p.id
        WHERE a.deleted_at IS NOT NULL
    ")->fetchAll();

    // Fetch deleted users
    $deleted_items['users'] = $pdo->query("SELECT username as id, full_name, deleted_at FROM users WHERE deleted_at IS NOT NULL")->fetchAll();

    echo json_encode(['status' => 'success', 'data' => $deleted_items]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}