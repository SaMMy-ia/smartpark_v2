<?php
/**
 * SmartPark - API para Verificação de Vagas
 * Endpoint AJAX para verificar disponibilidade e status de vagas
 */

header('Content-Type: application/json');
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

// Verificar se usuário está logado
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'verificar_disponibilidade':
        verificarDisponibilidade();
        break;
    
    case 'listar_vagas':
        listarVagas();
        break;
    
    case 'verificar_sobreposicao':
        verificarSobreposicao();
        break;
    
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Ação inválida']);
}

/**
 * Verificar disponibilidade de uma vaga específica
 */
function verificarDisponibilidade() {
    global $pdo;
    
    $vagaId = (int)($_GET['vaga_id'] ?? 0);
    $dataInicio = $_GET['data_inicio'] ?? '';
    $dataFim = $_GET['data_fim'] ?? '';
    
    if (!$vagaId || !$dataInicio || !$dataFim) {
        http_response_code(400);
        echo json_encode(['error' => 'Parâmetros inválidos']);
        return;
    }
    
    // Verificar se há sobreposição
    $temConflito = verificarSobreposicaoHorario($pdo, $vagaId, $dataInicio, $dataFim);
    
    // Buscar informações da vaga
    $stmt = $pdo->prepare("
        SELECT v.*, e.nome as estacionamento_nome, e.preco_hora
        FROM vagas v
        JOIN estacionamentos e ON v.estacionamento_id = e.id
        WHERE v.id = ?
    ");
    $stmt->execute([$vagaId]);
    $vaga = $stmt->fetch();
    
    if (!$vaga) {
        http_response_code(404);
        echo json_encode(['error' => 'Vaga não encontrada']);
        return;
    }
    
    // Calcular valor estimado
    $hours = calculateHours($dataInicio, $dataFim);
    $valorEstimado = $hours * $vaga['preco_hora'];
    
    echo json_encode([
        'disponivel' => !$temConflito,
        'vaga' => [
            'id' => $vaga['id'],
            'numero_vaga' => $vaga['numero_vaga'],
            'tipo' => $vaga['tipo'],
            'preco_hora' => $vaga['preco_hora']
        ],
        'valor_estimado' => $valorEstimado,
        'horas' => round($hours, 2),
        'mensagem' => $temConflito 
            ? 'Esta vaga já está reservada para o horário selecionado.' 
            : 'Vaga disponível para o horário selecionado.'
    ]);
}

/**
 * Listar todas as vagas com status para um período
 */
function listarVagas() {
    global $pdo;
    
    $dataInicio = $_GET['data_inicio'] ?? date('Y-m-d H:i:s');
    $dataFim = $_GET['data_fim'] ?? date('Y-m-d H:i:s', strtotime('+2 hours'));
    
    $vagas = getStatusTodasVagas($pdo, $dataInicio, $dataFim);
    
    echo json_encode([
        'vagas' => $vagas,
        'periodo' => [
            'inicio' => $dataInicio,
            'fim' => $dataFim
        ]
    ]);
}

/**
 * Verificar sobreposição de horário
 */
function verificarSobreposicao() {
    global $pdo;
    
    $vagaId = (int)($_GET['vaga_id'] ?? 0);
    $dataInicio = $_GET['data_inicio'] ?? '';
    $dataFim = $_GET['data_fim'] ?? '';
    
    if (!$vagaId || !$dataInicio || !$dataFim) {
        http_response_code(400);
        echo json_encode(['error' => 'Parâmetros inválidos']);
        return;
    }
    
    $temConflito = verificarSobreposicaoHorario($pdo, $vagaId, $dataInicio, $dataFim);
    
    echo json_encode([
        'tem_conflito' => $temConflito,
        'mensagem' => $temConflito 
            ? 'Horário indisponível - já existe uma reserva neste período.' 
            : 'Horário disponível para reserva.'
    ]);
}
