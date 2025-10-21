<?php
require_once '../config/database.php';

// Calling check_auth() is enough. It will:
// 1. Start the session.
// 2. Check for timeout (which won't happen since we're active).
// 3. Update the 'last_activity' timestamp, effectively extending the session.
check_auth();

echo json_encode(['status' => 'success', 'message' => 'Session extended.']);