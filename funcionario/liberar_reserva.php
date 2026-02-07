<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/multas.php';
requireRole('funcionario');

$reservaId = (int)($_GET['id'] ?? 0);

if ($reservaId <= 0) {
    die("ID de reserva inválido.");
}

try {
    /*
    ============================================================
    1) BUSCAR RESERVA
    ============================================================
    */
    $stmt = $pdo->prepare("
        SELECT id, vaga_id, status, valor_multa
        FROM reservas
        WHERE id = ?
    ");
    $stmt->execute([$reservaId]);
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reserva) {
        die("Reserva não encontrada.");
    }

    // Não permitir se já estiver concluída ou cancelada
    if (in_array($reserva['status'], ['concluida', 'cancelada'])) {
        $_SESSION['error'] = "Esta reserva já foi encerrada anteriormente.";
        header("Location: reservas.php");
        exit;
    }

    $pdo->beginTransaction();

    $valorMulta = $reserva['valor_multa'] ?? 0;

    /*
    ============================================================
    2) FINALIZAR RESERVA E PARAR MULTA AUTOMÁTICA
    ============================================================
    */

    if ($reserva['status'] === 'em_multa') {
        // Reserva em multa → congelar multa e registrar saída
        $stmt = $pdo->prepare("
            UPDATE reservas
            SET status = 'multa_pendente',
                data_saida_real = NOW(),
                multa_fechada = 1
            WHERE id = ?
        ");
        $stmt->execute([$reservaId]);
    } else {
        // Reserva normal → apenas finalizar
        $stmt = $pdo->prepare("
            UPDATE reservas
            SET status = 'finalizada',
                data_saida_real = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$reservaId]);
    }

    /*
    ============================================================
    3) LIBERAR VAGA
    ============================================================
    */
    $stmt = $pdo->prepare("
        UPDATE vagas
        SET status = 'livre'
        WHERE id = ?
    ");
    $stmt->execute([$reserva['vaga_id']]);

    /*
    ============================================================
    4) ATUALIZAR CONTAGEM DE VAGAS DO ESTACIONAMENTO
    ============================================================
    */
    $stmt = $pdo->prepare("
        UPDATE estacionamentos e
        JOIN vagas v ON v.estacionamento_id = e.id
        SET e.vagas_disponiveis = e.vagas_disponiveis + 1
        WHERE v.id = ?
    ");
    $stmt->execute([$reserva['vaga_id']]);

    /*
    ============================================================
    5) REGISTRAR LOG
    ============================================================
    */
    $stmt = $pdo->prepare("
        INSERT INTO logs (usuario_id, acao)
        VALUES (?, ?)
    ");

    if ($valorMulta > 0) {
        $logMsg = "Vaga liberada com multa pendente. Reserva #$reservaId. Multa congelada em " . number_format($valorMulta, 2) . " MT";
    } else {
        $logMsg = "Reserva #$reservaId finalizada e vaga liberada.";
    }

    $stmt->execute([$_SESSION['user_id'] ?? 1, $logMsg]);

    $pdo->commit();

    /*
    ============================================================
    6) MENSAGEM DE SUCESSO
    ============================================================
    */
    if ($valorMulta > 0) {
        $_SESSION['success'] = "Vaga liberada! Multa congelada em " . number_format($valorMulta, 2) . " MT aguardando pagamento.";
    } else {
        $_SESSION['success'] = "Vaga liberada com sucesso!";
    }

    header("Location: reservas.php");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("Erro ao processar a reserva: " . $e->getMessage());
}
