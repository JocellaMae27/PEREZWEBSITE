<?php
require_once '../config/database.php';

check_admin();

$dbname = 'petlink_db'; 

$tables = [];
$stmt = $pdo->query("SHOW TABLES");
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    $tables[] = $row[0];
}

$output = "-- PetLink DB Backup\n";
$output .= "-- Generation Time: " . date('Y-m-d H:i:s') . "\n";
$output .= "SET NAMES utf8;\n";
$output .= "SET time_zone = '+00:00';\n";
$output .= "SET foreign_key_checks = 0;\n";
$output .= "SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';\n\n";

foreach ($tables as $table) {
    $output .= "DROP TABLE IF EXISTS `$table`;\n";
    
    $createStmt = $pdo->query("SHOW CREATE TABLE `$table`");
    $createRow = $createStmt->fetch(PDO::FETCH_ASSOC);
    $output .= $createRow['Create Table'] . ";\n\n";
    
    $dataStmt = $pdo->query("SELECT * FROM `$table`");
    $rowCount = $dataStmt->rowCount();

    if ($rowCount > 0) {
        while ($row = $dataStmt->fetch(PDO::FETCH_ASSOC)) {
            $output .= "INSERT INTO `$table` (`" . implode("`, `", array_keys($row)) . "`) VALUES (";
            $values = [];
            foreach ($row as $value) {
                if (is_null($value)) {
                    $values[] = "NULL";
                } else {
                    $values[] = $pdo->quote($value);
                }
            }
            $output .= implode(", ", $values) . ");\n";
        }
        $output .= "\n";
    }
}

$output .= "SET foreign_key_checks = 1;\n";

$backup_file_name = $dbname . '_backup_' . date('Y-m-d_H-i-s') . '.sql';

header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . $backup_file_name . '"');
header('Content-Length: ' . strlen($output));

echo $output;
exit();