<?php
require_once 'config.php';

try {
    // Check if column exists
    $stmt = $pdo->prepare("SHOW COLUMNS FROM solicitacoes_veiculos LIKE 'visto_pelo_usuario'");
    $stmt->execute();
    $exists = $stmt->fetch();

    if (!$exists) {
        // Add column
        $sql = "ALTER TABLE solicitacoes_veiculos ADD COLUMN visto_pelo_usuario TINYINT(1) DEFAULT 0";
        $pdo->exec($sql);
        echo "Coluna 'visto_pelo_usuario' adicionada com sucesso.\n";
    } else {
        echo "Coluna 'visto_pelo_usuario' já existe.\n";
    }

} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
