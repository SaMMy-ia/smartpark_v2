<?php
// Configuration
$host = 'localhost';
$user = 'root';
$pass = '';
$name = 'smartpark_v9';
$outputFile = 'smartpark_v9.sql';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $output = "-- SmartPark v9 Database Dump\n";
    $output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
    $output .= "SET FOREIGN_KEY_CHECKS=0;\n";
    $output .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $output .= "START TRANSACTION;\n";
    $output .= "SET time_zone = \"+02:00\";\n\n";

    // Get tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        $output .= "-- --------------------------------------------------------\n\n";
        $output .= "-- Table structure for table `$table`\n\n";

        $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
        $createTable = $stmt->fetchColumn(1);
        $output .= "DROP TABLE IF EXISTS `$table`;\n";
        $output .= $createTable . ";\n\n";

        $output .= "-- Dumping data for table `$table`\n\n";

        $stmt = $pdo->query("SELECT * FROM `$table`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($rows) > 0) {
            $output .= "INSERT INTO `$table` (";
            $first = true;
            foreach ($rows[0] as $key => $value) {
                if (!$first)
                    $output .= ", ";
                $output .= "`$key`";
                $first = false;
            }
            $output .= ") VALUES\n";

            $rowCount = 0;
            foreach ($rows as $row) {
                if ($rowCount > 0)
                    $output .= ",\n";
                $output .= "(";
                $colCount = 0;
                foreach ($row as $value) {
                    if ($colCount > 0)
                        $output .= ", ";
                    if ($value === null) {
                        $output .= "NULL";
                    } else {
                        $output .= $pdo->quote($value);
                    }
                    $colCount++;
                }
                $output .= ")";
                $rowCount++;
            }
            $output .= ";\n\n";
        }
    }

    $output .= "SET FOREIGN_KEY_CHECKS=1;\n";
    $output .= "COMMIT;\n";

    file_put_contents($outputFile, $output);
    echo "Database exported to $outputFile successfully!";

} catch (PDOException $e) {
    die("Export failed: " . $e->getMessage());
}
