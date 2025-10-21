<?php
// This script should not be accessed directly.
// It is included and run by other authenticated scripts.

if (!isset($pdo)) {
    die('Unauthorized access.');
}

try {
    // Define the retention period. '30 DAY' can be changed to '1 WEEK', etc.
    $retention_period = '30 DAY';

    // Get the cutoff date
    $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_period}"));

    // List of tables to clean up.
    $tables_to_clean = [
        'clients' => 'id',
        'patients' => 'id',
        'appointments' => 'id',
        'users' => 'username'
    ];

    foreach ($tables_to_clean as $table => $id_column) {
        $stmt = $pdo->prepare("DELETE FROM `{$table}` WHERE `deleted_at` IS NOT NULL AND `deleted_at` < ?");
        $stmt->execute([$cutoff_date]);
    }
    
    // Update the task tracker
    $stmt_update_task = $pdo->prepare("
        INSERT INTO app_tasks (task_name, last_run) VALUES ('recycle_bin_cleanup', NOW())
        ON DUPLICATE KEY UPDATE last_run = NOW()
    ");
    $stmt_update_task->execute();

} catch (PDOException $e) {
    // Silently log the error, don't break the user's page load.
    error_log("Recycle bin cleanup failed: " . $e->getMessage());
}