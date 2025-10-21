<?php
require_once '../config/database.php';
require_once '../core/security.php';
require_once '../models/User.php';
require_once '../models/Patient.php';
require_once '../models/Appointment.php';
require_once '../models/Client.php';

check_auth();

try {
    $stmt = $pdo->prepare("SELECT last_run FROM app_tasks WHERE task_name = 'recycle_bin_cleanup'");
    $stmt->execute();
    $lastRun = $stmt->fetchColumn();

    $shouldRun = !$lastRun || (strtotime($lastRun) < strtotime('-24 hours'));

    if ($shouldRun) {
        require_once '../core/cleanup.php';
    }
} catch (PDOException $e) {
    // If the tasks table doesn't exist yet, we can ignore the error
    // It will be created during setup.
    if ($e->getCode() !== '42S02') { // '42S02' is the SQLSTATE for "table not found"
         error_log("Failed to check cleanup task status: " . $e->getMessage());
    }
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {
    $userModel = new User($pdo);
    $patientModel = new Patient($pdo);
    $appointmentModel = new Appointment($pdo);
    $clientModel = new Client($pdo); 

    $users = [];
    if ($_SESSION['user']['role'] === 'admin') {
        $users = $userModel->getAll();
    }

    $patients = $patientModel->getAll();
    $appointments = $appointmentModel->getAllWithPatientInfo();
    $clients = $clientModel->getAll();
    
    $responseData = [
        'csrfToken' => $_SESSION['csrf_token'],
        'users' => $users,
        'patients' => $patients,
        'visits' => $appointments,
        'clients' => $clients // New line
    ];

    echo json_encode(['status' => 'success', 'data' => sanitize_output($responseData)]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}