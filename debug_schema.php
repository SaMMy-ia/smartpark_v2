<?php
require_once 'config.php';

try {
    echo "Database: " . $dbname . "\n"; // Assuming $dbname is in config, or I'll check via query

    $stmt = $pdo->query("SELECT DATABASE()");
    echo "Connected Database: " . $stmt->fetchColumn() . "\n\n";

    echo "Columns in 'solicitacoes_veiculos':\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM solicitacoes_veiculos");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $col) {
        echo $col['Field'] . " (" . $col['Type'] . ")\n";
    }

} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
