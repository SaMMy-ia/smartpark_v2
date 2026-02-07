<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('funcionario');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservaId = (int)$_POST['reserva_id'];
    $acao = $_POST['acao']; // 'autorizar' ou 'negar'
    $employeeId = $_SESSION['user_id'];
    
    // Check if reservation exists and has pending exit request
    $stmt = $pdo->prepare("SELECT id, status_saida, matricula FROM reservas WHERE id = ?");
    $stmt->execute([$reservaId]);
    $reserva = $stmt->fetch();
    
    if (!$reserva) {
        redirectWithMessage('/smartpark/funcionario/dashboard.php', 'Reserva não encontrada.', 'error');
    }
    
    if ($reserva['status_saida'] !== 'pendente') {
        redirectWithMessage('/smartpark/funcionario/dashboard.php', 'Esta reserva não possui uma solicitação de saída pendente.', 'error');
    }
    
    if ($acao === 'autorizar') {
        $stmt = $pdo->prepare("
            UPDATE reservas 
            SET status_saida = 'autorizada', 
                autorizado_por = ?, 
                data_saida_real = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $stmt->execute([$employeeId, $reservaId]);
        
        logAction($employeeId, "Saída AUTORIZADA para reserva #$reservaId (Matrícula: {$reserva['matricula']})");
        redirectWithMessage('/smartpark/funcionario/dashboard.php', 'Saída autorizada com sucesso!');
        
    } elseif ($acao === 'negar') {
        $stmt = $pdo->prepare("UPDATE reservas SET status_saida = 'negada' WHERE id = ?");
        $stmt->execute([$reservaId]);
        
        logAction($employeeId, "Saída NEGADA para reserva #$reservaId (Matrícula: {$reserva['matricula']})");
        redirectWithMessage('/smartpark/funcionario/dashboard.php', 'Saída negada.');
    } else {
        redirectWithMessage('/smartpark/funcionario/dashboard.php', 'Ação inválida.', 'error');
    }
} else {
    header('Location: /smartpark/funcionario/dashboard.php');
    exit;
}
