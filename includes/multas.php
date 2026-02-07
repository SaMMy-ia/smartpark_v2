<?php
/**
 * SmartPark - Sistema de Multas Automáticas
 * Gerenciamento de multas por excesso de tempo
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

/**
 * Verificar e aplicar multas automáticas para reservas expiradas
 * Esta função deve ser chamada periodicamente (via cron ou em cada acesso ao sistema)
 */
function verificarMultasAutomaticas($pdo) {
    $agora = date('Y-m-d H:i:s');
    
    // Buscar reservas ativas que já expiraram
    $stmt = $pdo->prepare("
        SELECT r.id, r.vaga_id, r.usuario_id, r.data_fim, r.valor_total,
               v.numero_vaga, u.nome as usuario_nome
        FROM reservas r
        JOIN vagas v ON r.vaga_id = v.id
        JOIN usuarios u ON r.usuario_id = u.id
        WHERE r.status = 'ativa'
        AND r.data_fim < ?
    ");
    $stmt->execute([$agora]);
    $reservasExpiradas = $stmt->fetchAll();
    
    $multasAplicadas = 0;
    
    foreach ($reservasExpiradas as $reserva) {
        aplicarMulta($pdo, $reserva['id']);
        $multasAplicadas++;
        
        // Log da ação
        logAction($reserva['usuario_id'], "Multa automática aplicada - Reserva #{$reserva['id']} - Vaga {$reserva['numero_vaga']}");
    }
    
    return $multasAplicadas;
}

/**
 * Aplicar multa a uma reserva específica
 * Calcula multa baseada no tempo excedente: valor_hora × 1.5 × horas_excedentes
 */
function aplicarMulta($pdo, $reservaId) {
    try {
        // Iniciar transação para garantir consistência
        $pdo->beginTransaction();
        
        $agora = date('Y-m-d H:i:s');
        
        // Buscar dados da reserva com informações do parque
        $stmt = $pdo->prepare("
            SELECT r.*, v.numero_vaga, e.preco_hora 
            FROM reservas r
            JOIN vagas v ON r.vaga_id = v.id
            JOIN estacionamentos e ON v.estacionamento_id = e.id
            WHERE r.id = ? AND r.status = 'ativa'
        ");
        $stmt->execute([$reservaId]);
        $reserva = $stmt->fetch();
        
        if (!$reserva) {
            $pdo->rollBack();
            return false;
        }
        
        // Verificar se realmente ultrapassou o horário
        if ($reserva['data_fim'] >= $agora) {
            $pdo->rollBack();
            return false;
        }
        
        // Calcular tempo excedente em horas
        $dataFim = new DateTime($reserva['data_fim']);
        $dataAtual = new DateTime($agora);
        $intervalo = $dataFim->diff($dataAtual);
        
        // Converter para horas decimais
        $horasExcedentes = $intervalo->h + ($intervalo->days * 24) + ($intervalo->i / 60);
        
        // Arredondar para cima (mínimo 1 hora)
        $horasExcedentes = max(1, ceil($horasExcedentes));
        
        // Calcular valor da multa: valor_hora × 1.5 × horas_excedentes
        $valorMulta = $reserva['preco_hora'] * 1.5 * $horasExcedentes;
        
        // Atualizar status da reserva para 'em_multa'
        $stmt = $pdo->prepare("
            UPDATE reservas 
            SET status = 'em_multa',
                valor_multa = ?,
                data_multa = ?
            WHERE id = ?
        ");
        $stmt->execute([$valorMulta, $agora, $reservaId]);
        
        // Atualizar status da vaga para 'ocupada_multa'
        $stmt = $pdo->prepare("
            UPDATE vagas 
            SET status = 'ocupada_multa'
            WHERE id = ?
        ");
        $stmt->execute([$reserva['vaga_id']]);
        
        // Registrar no log
        $mensagemLog = sprintf(
            "Reserva #%d entrou em multa por excesso de tempo - %s - %.1f horas excedentes - Multa: %.2f MT",
            $reservaId,
            $reserva['numero_vaga'],
            $horasExcedentes,
            $valorMulta
        );
        
        $stmt = $pdo->prepare("INSERT INTO logs (usuario_id, acao) VALUES (?, ?)");
        $stmt->execute([$reserva['usuario_id'], $mensagemLog]);
        
        // Confirmar transação
        $pdo->commit();
        
        return true;
        
    } catch (Exception $e) {
        // Reverter em caso de erro
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro ao aplicar multa: " . $e->getMessage());
        return false;
    }
}

/**
 * Atualizar valor de multa dinamicamente para reservas já em multa
 * Recalcula baseado no tempo excedente atual
 */
function atualizarMulta($pdo, $reservaId) {
    try {
        $pdo->beginTransaction();
        
        $agora = date('Y-m-d H:i:s');
        
        // Buscar reserva em multa
        $stmt = $pdo->prepare("
            SELECT r.*, v.numero_vaga, e.preco_hora 
            FROM reservas r
            JOIN vagas v ON r.vaga_id = v.id
            JOIN estacionamentos e ON v.estacionamento_id = e.id
            WHERE r.id = ? AND r.status = 'em_multa'
        ");
        $stmt->execute([$reservaId]);
        $reserva = $stmt->fetch();
        
        if (!$reserva) {
            $pdo->rollBack();
            return false;
        }
        
        // Calcular tempo excedente atual
        $dataFim = new DateTime($reserva['data_fim']);
        $dataAtual = new DateTime($agora);
        $intervalo = $dataFim->diff($dataAtual);
        
        $horasExcedentes = $intervalo->h + ($intervalo->days * 24) + ($intervalo->i / 60);
        $horasExcedentes = max(1, ceil($horasExcedentes));
        
        // Recalcular multa
        $novoValorMulta = $reserva['preco_hora'] * 1.5 * $horasExcedentes;
        
        // Atualizar apenas se o valor mudou
        if (abs($novoValorMulta - $reserva['valor_multa']) > 0.01) {
            $stmt = $pdo->prepare("
                UPDATE reservas 
                SET valor_multa = ?
                WHERE id = ?
            ");
            $stmt->execute([$novoValorMulta, $reservaId]);
            
            // Log da atualização
            $mensagemLog = sprintf(
                "Multa atualizada - Reserva #%d - %s - %.1f horas excedentes - Nova multa: %.2f MT",
                $reservaId,
                $reserva['numero_vaga'],
                $horasExcedentes,
                $novoValorMulta
            );
            
            $stmt = $pdo->prepare("INSERT INTO logs (usuario_id, acao) VALUES (?, ?)");
            $stmt->execute([$reserva['usuario_id'], $mensagemLog]);
        }
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro ao atualizar multa: " . $e->getMessage());
        return false;
    }
}


/**
 * Obter todas as reservas em situação de multa
 */
function getReservasEmMulta($pdo) {
    $stmt = $pdo->query("
        SELECT r.*, 
               u.nome as usuario_nome, 
               u.email as usuario_email,
               v.numero_vaga, 
               v.tipo as vaga_tipo,
               e.nome as estacionamento_nome
        FROM reservas r
        JOIN usuarios u ON r.usuario_id = u.id
        JOIN vagas v ON r.vaga_id = v.id
        JOIN estacionamentos e ON v.estacionamento_id = e.id
        WHERE r.status = 'em_multa'
        ORDER BY r.data_multa DESC
    ");
    
    return $stmt->fetchAll();
}

/**
 * Resolver multa (marcar como paga ou cancelar)
 * A vaga só é liberada após resolução da multa
 */
function resolverMulta($pdo, $reservaId, $acao = 'pagar') {
    try {
        // Iniciar transação
        $pdo->beginTransaction();
        
        // Buscar reserva com informações da vaga
        $stmt = $pdo->prepare("
            SELECT r.*, v.numero_vaga, u.nome as usuario_nome
            FROM reservas r
            JOIN vagas v ON r.vaga_id = v.id
            JOIN usuarios u ON r.usuario_id = u.id
            WHERE r.id = ?
        ");
        $stmt->execute([$reservaId]);
        $reserva = $stmt->fetch();
        
        if (!$reserva || $reserva['status'] !== 'em_multa') {
            $pdo->rollBack();
            return false;
        }
        
        if ($acao === 'pagar') {
            // Marcar como concluída e criar pagamento da multa
            $stmt = $pdo->prepare("
                UPDATE reservas 
                SET status = 'concluida'
                WHERE id = ?
            ");
            $stmt->execute([$reservaId]);
            
            // Criar registro de pagamento da multa
            $valorTotal = $reserva['valor_total'] + $reserva['valor_multa'];
            $stmt = $pdo->prepare("
                INSERT INTO pagamentos (reserva_id, metodo, status, valor)
                VALUES (?, 'cartao', 'pago', ?)
            ");
            $stmt->execute([$reservaId, $valorTotal]);
            
            // Log de pagamento
            $mensagemLog = sprintf(
                "Multa resolvida (paga) - Reserva #%d - %s - Valor total: %.2f MT (Reserva: %.2f MT + Multa: %.2f MT)",
                $reservaId,
                $reserva['numero_vaga'],
                $valorTotal,
                $reserva['valor_total'],
                $reserva['valor_multa']
            );
            
        } else {
            // Cancelar reserva
            $stmt = $pdo->prepare("
                UPDATE reservas 
                SET status = 'cancelada'
                WHERE id = ?
            ");
            $stmt->execute([$reservaId]);
            
            // Log de cancelamento
            $mensagemLog = sprintf(
                "Multa resolvida (cancelada) - Reserva #%d - %s - Multa: %.2f MT",
                $reservaId,
                $reserva['numero_vaga'],
                $reserva['valor_multa']
            );
        }
        
        // Liberar a vaga
        $stmt = $pdo->prepare("
            UPDATE vagas 
            SET status = 'livre'
            WHERE id = ?
        ");
        $stmt->execute([$reserva['vaga_id']]);
        
        // Registrar log
        $stmt = $pdo->prepare("INSERT INTO logs (usuario_id, acao) VALUES (?, ?)");
        $stmt->execute([$reserva['usuario_id'], $mensagemLog]);
        
        // Log adicional de liberação da vaga
        $stmt = $pdo->prepare("INSERT INTO logs (usuario_id, acao) VALUES (?, ?)");
        $stmt->execute([
            $reserva['usuario_id'], 
            "Vaga {$reserva['numero_vaga']} liberada - Reserva #{$reservaId} encerrada"
        ]);
        
        // Confirmar transação
        $pdo->commit();
        
        return true;
        
    } catch (Exception $e) {
        // Reverter em caso de erro
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro ao resolver multa: " . $e->getMessage());
        return false;
    }
}

/**
 * Obter estatísticas de multas
 */
function getEstatisticasMultas($pdo) {
    // Total de multas ativas
    $stmt = $pdo->query("
        SELECT COUNT(*) as total, SUM(valor_multa) as valor_total
        FROM reservas
        WHERE status = 'em_multa'
    ");
    $multasAtivas = $stmt->fetch();
    
    // Total de multas pagas (histórico)
    $stmt = $pdo->query("
        SELECT COUNT(*) as total, SUM(valor_multa) as valor_total
        FROM reservas
        WHERE valor_multa > 0 AND status = 'concluida'
    ");
    $multasPagas = $stmt->fetch();
    
    return [
        'ativas' => [
            'quantidade' => $multasAtivas['total'] ?? 0,
            'valor' => $multasAtivas['valor_total'] ?? 0
        ],
        'pagas' => [
            'quantidade' => $multasPagas['total'] ?? 0,
            'valor' => $multasPagas['valor_total'] ?? 0
        ]
    ];
}

/**
 * Buscar reservas por matrícula
 */
function buscarPorMatricula($pdo, $matricula) {
    $matricula = strtoupper(trim($matricula));
    
    $stmt = $pdo->prepare("
        SELECT r.*, 
               u.nome as usuario_nome,
               v.numero_vaga,
               v.tipo as vaga_tipo,
               e.nome as estacionamento_nome,
               p.status as pagamento_status
        FROM reservas r
        JOIN usuarios u ON r.usuario_id = u.id
        JOIN vagas v ON r.vaga_id = v.id
        JOIN estacionamentos e ON v.estacionamento_id = e.id
        LEFT JOIN pagamentos p ON r.id = p.reserva_id
        WHERE r.matricula LIKE ?
        ORDER BY r.data_inicio DESC
    ");
    $stmt->execute(["%$matricula%"]);
    
    return $stmt->fetchAll();
}

/**
 * Verificar se um veículo está atualmente em situação irregular
 */
function verificarSituacaoVeiculo($pdo, $matricula) {
    $matricula = strtoupper(trim($matricula));
    
    // Verificar se há multa ativa
    $stmt = $pdo->prepare("
        SELECT r.*, v.numero_vaga
        FROM reservas r
        JOIN vagas v ON r.vaga_id = v.id
        WHERE r.matricula LIKE ?
        AND r.status = 'em_multa'
        LIMIT 1
    ");
    $stmt->execute(["%$matricula%"]);
    $multaAtiva = $stmt->fetch();
    
    if ($multaAtiva) {
        return [
            'status' => 'em_multa',
            'mensagem' => "Veículo em situação irregular - Multa ativa na {$multaAtiva['numero_vaga']}",
            'reserva' => $multaAtiva
        ];
    }
    
    // Verificar se há reserva ativa
    $agora = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("
        SELECT r.*, v.numero_vaga
        FROM reservas r
        JOIN vagas v ON r.vaga_id = v.id
        WHERE r.matricula LIKE ?
        AND r.status = 'ativa'
        AND r.data_inicio <= ?
        AND r.data_fim >= ?
        LIMIT 1
    ");
    $stmt->execute(["%$matricula%", $agora, $agora]);
    $reservaAtiva = $stmt->fetch();
    
    if ($reservaAtiva) {
        return [
            'status' => 'estacionado',
            'mensagem' => "Veículo estacionado regularmente na {$reservaAtiva['numero_vaga']}",
            'reserva' => $reservaAtiva
        ];
    }
    
    return [
        'status' => 'livre',
        'mensagem' => 'Veículo não está atualmente no parque',
        'reserva' => null
    ];
}
