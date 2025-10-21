<?php
$errorMessage = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Database credentials from your main config
    $host = 'localhost';
    $dbname = 'petlink_db';
    $username = 'root';
    $password = '';

    try {
        // 1. Connect to MySQL server (without selecting a database)
        $pdo_server = new PDO("mysql:host=$host", $username, $password);
        $pdo_server->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 2. Create the database if it doesn't exist
        $pdo_server->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        
        // 3. Connect to the newly created database
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 4. Read and execute the schema.sql file to create tables
        $sql_script = file_get_contents('schema.sql');
        if ($sql_script === false) {
            throw new Exception("Cannot read the schema.sql file. Make sure it's in the setup directory.");
        }
        $pdo->exec($sql_script);

        // 5. Create the initial admin account
        $adminUser = 'admin';
        $adminPass = '123';
        $hashedPassword = password_hash($adminPass, PASSWORD_DEFAULT);
        $adminFullName = 'Default Admin';
        $adminRole = 'admin';

        $stmt = $pdo->prepare("INSERT INTO users (username, full_name, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$adminUser, $adminFullName, $hashedPassword, $adminRole]);

        // 6. Create the lock file to prevent re-running the setup
        $lockFilePath = '../config/installed.lock';
        file_put_contents($lockFilePath, 'Setup completed on ' . date('c'));

        $successMessage = 'Setup completed successfully! You can now log in with the default account.';

    } catch (Exception $e) {
        $errorMessage = 'An error occurred during setup: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Setup - PetLink</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        body { display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: var(--bg-light); }
        .setup-box { max-width: 500px; text-align: center; }
        .error { color: var(--status-red-text); background-color: var(--status-red); padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .success { color: var(--status-green-text); background-color: var(--status-green); padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .login-link { margin-top: 1.5rem; }
    </style>
</head>
<body>
    <div class="login-box setup-box">
        <h1>Welcome to PetLink</h1>
        <p>Pet Visit Record System Setup</p>

        <?php if ($errorMessage): ?>
            <div class="error"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>

        <?php if ($successMessage): ?>
            <div class="success"><?php echo htmlspecialchars($successMessage); ?></div>
            <p><strong>Username:</strong> admin<br><strong>Password:</strong> 123</p>
            <p>Please change this password immediately after logging in for security.</p>
            <a href="../index.php" class="submit-btn login-link">Go to Login Page</a>
        <?php else: ?>
            <p>It looks like this is the first time you're running the application. Click the button below to set up the database and create the necessary tables.</p>
            <form method="POST">
                <button type="submit" class="submit-btn">Start Setup</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>