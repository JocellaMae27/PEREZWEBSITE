<?php
// A simple script to generate password hashes.

// The passwords you want to use
$admin_password = 'admin123';
$staff_password = 'staff123';

// Generate the hashes
$admin_hash = password_hash($admin_password, PASSWORD_DEFAULT);
$staff_hash = password_hash($staff_password, PASSWORD_DEFAULT);

// Display the hashes
echo "<h1>Generated Password Hashes</h1>";
echo "<p>Copy these values into your database.</p>";
echo "<hr>";
echo "<p><strong>For user 'admin':</strong></p>";
echo "<textarea rows='3' cols='80' readonly>" . htmlspecialchars($admin_hash) . "</textarea>";
echo "<br><br>";
echo "<p><strong>For user 'staff':</strong></p>";
echo "<textarea rows='3' cols='80' readonly>" . htmlspecialchars($staff_hash) . "</textarea>";
echo "<hr>";
echo "<p><strong>IMPORTANT: Delete this file (generate_hash.php) after you are done!</strong></p>";