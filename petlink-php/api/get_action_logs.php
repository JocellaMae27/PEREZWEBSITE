<?php
require_once '../config/database.php';
require_once '../core/security.php';

check_admin();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15; // More entries per page for logs
$search = $_GET['search'] ?? '';

try {
    $offset = ($page - 1) * $limit;
    
    $whereClause = '';
    $params = [];
    if (!empty($search)) {
        $whereClause = "WHERE username LIKE ? OR action LIKE ? OR details LIKE ? OR ip_address LIKE ?";
        $searchTerm = "%{$search}%";
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
    }
    
    $countQuery = "SELECT COUNT(id) FROM action_logs {$whereClause}";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetchColumn();
    
    $sql = "SELECT * FROM action_logs {$whereClause} ORDER BY timestamp DESC LIMIT ? OFFSET ?";
    $finalParams = array_merge($params, [$limit, $offset]);
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(count($params) + 1, $limit, PDO::PARAM_INT);
    $stmt->bindParam(count($params) + 2, $offset, PDO::PARAM_INT);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key + 1, $val);
    }
    $stmt->execute();
    $logs = $stmt->fetchAll();

    $result = [
        'logs' => $logs,
        'totalPages' => ceil($totalRecords / $limit),
        'currentPage' => $page
    ];

    echo json_encode(['status' => 'success', 'data' => sanitize_output($result)]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}