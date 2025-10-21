<?php
// Function to log an action to the database.
// This should be included in files that need to perform logging.

function log_action($pdo, $action, $details = '') {
    // Determine the username. Try the session first, but allow for pre-login actions.
    $username = $_SESSION['user']['username'] ?? 'system';
    
    // Get the user's IP address
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO action_logs (username, ip_address, action, details) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$username, $ip_address, $action, $details]);
    } catch (PDOException $e) {
        // Don't let a logging failure crash the main application.
        // Log it to the server's error log instead.
        error_log("Failed to log action: " . $e->getMessage());
    }
}