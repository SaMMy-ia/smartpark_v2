<?php
require_once __DIR__ . '/config.php';

try {
    $pdo->exec("ALTER TABLE `veiculos` ADD COLUMN `proprietario_nome` VARCHAR(100) DEFAULT NULL AFTER `cor` ");
    $pdo->exec("ALTER TABLE `veiculos` ADD COLUMN `contacto` VARCHAR(20) DEFAULT NULL AFTER `proprietario_nome` ");
    echo "Tabela 'veiculos' atualizada com sucesso!\n";
} catch (PDOException $e) {
    echo "Erro (ou coluna já existe): " . $e->getMessage() . "\n";
}
