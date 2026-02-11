<?php
require_once 'config.php';

try {
    $stmt = $pdo->prepare("SELECT id, nome, email, role FROM usuarios WHERE email = ?");
    $stmt->execute(['admin@smartpark.com']);
    $user = $stmt->fetch();
    echo "<pre>";
    print_r($user);
    echo "</pre>";
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
