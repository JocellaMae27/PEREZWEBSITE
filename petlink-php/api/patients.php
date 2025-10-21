<?php
require_once '../config/database.php';
require_once '../models/Patient.php';

// Helper function for validating patient data
function validate_patient_data($data, $is_update = false) {
    $errors = [];
    if ($is_update && (empty($data['id']) || !is_numeric($data['id']))) {
        $errors[] = 'A valid patient ID is required for updates.';
    }
    if (empty($data['clientId']) || !is_numeric($data['clientId'])) {
        $errors[] = 'A valid client (owner) must be selected.';
    }
    if (empty(trim($data['petName']))) {
        $errors[] = 'Pet name is required.';
    }
    if (empty($data['firstVisitDate'])) {
        $errors[] = 'First visit date is required.';
    } else {
        $d = DateTime::createFromFormat('Y-m-d', $data['firstVisitDate']);
        if (!$d || $d->format('Y-m-d') !== $data['firstVisitDate']) {
            $errors[] = 'First visit date is not a valid date (format YYYY-MM-DD).';
        }
    }
    if (!empty($data['dob']) && $data['dob'] !== '') {
        $d = DateTime::createFromFormat('Y-m-d', $data['dob']);
        if (!$d || $d->format('Y-m-d') !== $data['dob']) {
            $errors[] = 'Date of birth is not a valid date (format YYYY-MM-DD).';
        }
    }
    return $errors;
}


$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);

check_auth();

if ($method !== 'GET') {
    check_csrf();
}

$patientModel = new Patient($pdo);

try {
    switch ($method) {
        case 'POST':
            $errors = validate_patient_data($data);
            if (!empty($errors)) {
                http_response_code(400); // Bad Request
                echo json_encode(['status' => 'error', 'message' => implode(', ', $errors)]);
                exit();
            }
            $patientModel->create($data);
            echo json_encode(['status' => 'success', 'id' => $pdo->lastInsertId()]);
            break;

        case 'PUT':
            $errors = validate_patient_data($data, true); // Pass true for update validation
            if (!empty($errors)) {
                http_response_code(400); // Bad Request
                echo json_encode(['status' => 'error', 'message' => implode(', ', $errors)]);
                exit();
            }
            $patientModel->update($data);
            echo json_encode(['status' => 'success']);
            break;

        case 'DELETE':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Patient ID is required.']);
                exit();
            }
            $patientModel->delete($id);
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