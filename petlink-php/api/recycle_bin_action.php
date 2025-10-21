<?php
require_once '../config/database.php';
require_once '../core/logger.php';

check_admin();

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? null;
$type = $data['type'] ?? null;
$id = $data['id'] ?? null;

if (!$action || !$type || !$id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters.']);
    exit();
}

$table_map = [
    'clients' => 'clients',
    'patients' => 'patients',
    'appointments' => 'appointments',
    'users' => 'users'
];

if (!isset($table_map[$type])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid item type.']);
    exit();
}
$table = $table_map[$type];
$id_column = ($type === 'users') ? 'username' : 'id';

try {
    if ($action === 'restore') {
        $stmt = $pdo->prepare("UPDATE {$table} SET deleted_at = NULL WHERE {$id_column} = ?");
        $stmt->execute([$id]);
        log_action($pdo, 'ITEM_RESTORE', "Restored item of type '{$type}' with ID '{$id}'.");
    } elseif ($action === 'force_delete') {
        // This is a permanent deletion. Be careful.
        // For simplicity, we are not handling cascading permanent deletes here.
        // The models would need dedicated force_delete methods for that.
        $stmt = $pdo->prepare("DELETE FROM {$table} WHERE {$id_column} = ?");
        $stmt->execute([$id]);
        log_action($pdo, 'ITEM_PERMANENTLY_DELETED', "Permanently deleted item of type '{$type}' with ID '{$id}'.");
    } else {
        throw new Exception('Invalid action specified.');
    }
    
    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}