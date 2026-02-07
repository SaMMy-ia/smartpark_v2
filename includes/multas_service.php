<?php
/**
 * SmartPark - Serviço Automático de Multas
 * Sistema de verificação automática com cache para evitar sobrecarga
 */

require_once __DIR__ . '/multas.php';

/**
 * Verificar multas automaticamente com sistema de cache
 * Evita verificações excessivas usando cache de sessão (1 verificação por minuto)
 */
function verificarMultasAutomaticasComCache($pdo) {
    // Obter timestamp da última verificação
    $ultimaVerificacao = $_SESSION['ultima_verificacao_multas'] ?? 0;
    $agora = time();
    
    // Verificar apenas se passou mais de 60 segundos desde a última verificação
    if ($agora - $ultimaVerificacao > 60) {
        try {
            // Executar verificação automática
            $multasAplicadas = verificarMultasAutomaticas($pdo);
            
            // Atualizar timestamp da última verificação
            $_SESSION['ultima_verificacao_multas'] = $agora;
            
            // Retornar número de multas aplicadas (para debug/log se necessário)
            return $multasAplicadas;
        } catch (Exception $e) {
            // Log de erro mas não interrompe a execução
            error_log("Erro na verificação automática de multas: " . $e->getMessage());
            return 0;
        }
    }
    
    return 0;
}

/**
 * Obter resumo de multas pendentes de um usuário específico
 * Retorna quantidade e valor total
 */
function getResumoMultasUsuario($pdo, $usuarioId) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as quantidade,
                COALESCE(SUM(valor_multa), 0) as valor_total
            FROM reservas
            WHERE usuario_id = ?
            AND status = 'em_multa'
            AND valor_multa > 0
        ");
        $stmt->execute([$usuarioId]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'quantidade' => (int)$resultado['quantidade'],
            'valor' => (float)$resultado['valor_total']
        ];
    } catch (Exception $e) {
        error_log("Erro ao obter resumo de multas: " . $e->getMessage());
        return [
            'quantidade' => 0,
            'valor' => 0
        ];
    }
}

/**
 * Obter todas as multas de um usuário específico (pendentes e pagas)
 */
function getMultasUsuario($pdo, $usuarioId) {
    try {
        $stmt = $pdo->prepare("
            SELECT r.*, 
                   v.numero_vaga, 
                   v.tipo as vaga_tipo,
                   e.nome as estacionamento_nome,
                   e.endereco as estacionamento_endereco,
                   CASE 
                       WHEN r.status = 'em_multa' THEN 'pendente'
                       WHEN r.status = 'concluida' AND r.valor_multa > 0 THEN 'paga'
                       ELSE 'cancelada'
                   END as status_multa
            FROM reservas r
            JOIN vagas v ON r.vaga_id = v.id
            JOIN estacionamentos e ON v.estacionamento_id = e.id
            WHERE r.usuario_id = ?
            AND r.valor_multa > 0
            ORDER BY 
                CASE 
                    WHEN r.status = 'em_multa' THEN 1
                    ELSE 2
                END,
                r.data_multa DESC
        ");
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Erro ao obter multas do usuário: " . $e->getMessage());
        return [];
    }
}

/**
 * Obter apenas multas pendentes de um usuário
 */
function getMultasPendentesUsuario($pdo, $usuarioId) {
    try {
        $stmt = $pdo->prepare("
            SELECT r.*, 
                   v.numero_vaga, 
                   v.tipo as vaga_tipo,
                   e.nome as estacionamento_nome,
                   e.endereco as estacionamento_endereco
            FROM reservas r
            JOIN vagas v ON r.vaga_id = v.id
            JOIN estacionamentos e ON v.estacionamento_id = e.id
            WHERE r.usuario_id = ?
            AND r.status = 'em_multa'
            AND r.valor_multa > 0
            ORDER BY r.data_multa DESC
        ");
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Erro ao obter multas pendentes: " . $e->getMessage());
        return [];
    }
}

/**
 * Verificar se usuário tem multas pendentes
 * Retorna boolean
 */
function usuarioTemMultasPendentes($pdo, $usuarioId) {
    $resumo = getResumoMultasUsuario($pdo, $usuarioId);
    return $resumo['quantidade'] > 0;
}
