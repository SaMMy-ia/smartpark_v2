<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('usuario');

// ============================================
// OCULTAÇÃO DE DADOS: Dados antigos não são mais excluídos, apenas ocultados para o usuário
// Esta funcionalidade substitui a limpeza automática (DELETE) por segurança.
// ============================================
// $umaSemanaAtras = date('Y-m-d H:i:s', strtotime('-7 days'));
// O código de exclusão foi desativado a pedido:
/*
$stmt = $pdo->prepare("
    DELETE FROM reservas 
    WHERE status IN ('concluida', 'cancelada') 
    AND data_fim < ?
");
$stmt->execute([$umaSemanaAtras]);
*/
// ============================================

// Handle exit request
if (isset($_GET['request_exit'])) {
    $reservaId = (int)$_GET['request_exit'];
    
    $stmt = $pdo->prepare("
        SELECT id, status 
        FROM reservas 
        WHERE id = ? AND usuario_id = ?
    ");
    $stmt->execute([$reservaId, $_SESSION['user_id']]);
    $reserva = $stmt->fetch();
    
    if (!$reserva) {
        redirectWithMessage('/smartpark/usuario/minhas-reservas.php', 'Reserva não encontrada.', 'error');
    }
    
    if ($reserva['status'] !== 'ativa' && $reserva['status'] !== 'em_multa') {
        redirectWithMessage('/smartpark/usuario/minhas-reservas.php', 'Apenas reservas ativas ou em multa podem solicitar saída.', 'error');
    }
    
    $stmt = $pdo->prepare("UPDATE reservas SET status_saida = 'pendente' WHERE id = ?");
    $stmt->execute([$reservaId]);
    
    logAction($_SESSION['user_id'], "Solicitação de saída para reserva ID $reservaId");
    redirectWithMessage('/smartpark/usuario/minhas-reservas.php', 'Solicitação de saída enviada! Aguarde a autorização do funcionário.');
}

// Handle cancellation
if (isset($_GET['cancel'])) {
    $reservaId = (int)$_GET['cancel'];
    
    $stmt = $pdo->prepare("
        SELECT id, data_fim, status 
        FROM reservas 
        WHERE id = ? AND usuario_id = ?
    ");
    $stmt->execute([$reservaId, $_SESSION['user_id']]);
    $reserva = $stmt->fetch();
    
    if (!$reserva) {
        redirectWithMessage('/smartpark/usuario/minhas-reservas.php', 'Reserva não encontrada.', 'error');
    }
    
    $agora = date('Y-m-d H:i:s');
    if ($reserva['data_fim'] < $agora) {
        redirectWithMessage('/smartpark/usuario/minhas-reservas.php', 'Não é possível cancelar reservas após o horário de término.', 'error');
    }
    
    if ($reserva['status'] === 'em_multa') {
        redirectWithMessage('/smartpark/usuario/minhas-reservas.php', 'Não é possível cancelar reservas em multa.', 'error');
    }
    
    $stmt = $pdo->prepare("UPDATE reservas SET status = 'cancelada' WHERE id = ?");
    $stmt->execute([$reservaId]);
    
    $stmt = $pdo->prepare("UPDATE vagas v JOIN reservas r ON v.id = r.vaga_id SET v.status = 'livre' WHERE r.id = ?");
    $stmt->execute([$reservaId]);
    
    logAction($_SESSION['user_id'], "Reserva ID $reservaId cancelada pelo usuário");
    redirectWithMessage('/smartpark/usuario/minhas-reservas.php', 'Reserva cancelada com sucesso!');
}

// ============================================
// Listar reservas ordenadas
// ============================================
$stmt = $pdo->prepare("
    SELECT r.*, 
           v.numero_vaga, 
           e.nome as estacionamento_nome, 
           e.endereco,
           p.status as pagamento_status, 
           p.metodo as pagamento_metodo,
           r.valor_multa,
           CASE 
               WHEN r.status = 'ativa' THEN 1
               WHEN r.status = 'em_multa' THEN 2
               WHEN r.status = 'concluida' THEN 3
               WHEN r.status = 'cancelada' THEN 4
               ELSE 5
           END as ordem_status,
           CASE 
               WHEN r.status = 'ativa' THEN r.data_inicio
               ELSE '9999-12-31 23:59:59'
           END as data_ordenacao_ativas
    FROM reservas r
    JOIN vagas v ON r.vaga_id = v.id
    JOIN estacionamentos e ON v.estacionamento_id = e.id
    LEFT JOIN pagamentos p 
      ON p.id = (
          SELECT id 
          FROM pagamentos 
          WHERE reserva_id = r.id 
          ORDER BY data_pagamento DESC 
          LIMIT 1
      )
    WHERE r.usuario_id = ? 
      AND (r.status NOT IN ('concluida', 'cancelada') OR r.data_fim >= DATE_SUB(NOW(), INTERVAL 7 DAY))
    ORDER BY 
        ordem_status ASC,
        data_ordenacao_ativas ASC,
        r.data_inicio DESC
");

$stmt->execute([$_SESSION['user_id']]);
$reservas = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            <i class="fas fa-calendar mr-2"></i> Minhas Reservas
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Gerencie todas as suas reservas</p>
    </div>
    
    <?php if (empty($reservas)): ?>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-12 text-center">
        <i class="fas fa-calendar-times text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
        <p class="text-gray-600 dark:text-gray-400 mb-4">Você ainda não tem reservas.</p>
        <a href="/smartpark/usuario/buscar-vagas.php" class="inline-block px-6 py-3 bg-primary dark:bg-secondary text-white rounded-lg hover:bg-blue-900 dark:hover:bg-green-600 transition">
            <i class="fas fa-search mr-2"></i> Buscar Vagas
        </a>
    </div>
    <?php else: ?>
    
    <div class="space-y-4">
        <?php 
        $currentStatus = null;
        foreach ($reservas as $reserva): 
            if ($currentStatus !== $reserva['status']):
                $currentStatus = $reserva['status'];
                $statusLabels = [
                    'ativa' => 'Reservas Ativas',
                    'em_multa' => 'Reservas com Multa',
                    'concluida' => 'Reservas Concluídas',
                    'cancelada' => 'Reservas Canceladas'
                ];
                $statusColors = [
                    'ativa' => 'bg-green-100 dark:bg-green-900/30 border-green-300 dark:border-green-800',
                    'em_multa' => 'bg-red-100 dark:bg-red-900/30 border-red-300 dark:border-red-800',
                    'concluida' => 'bg-gray-100 dark:bg-gray-800/50 border-gray-300 dark:border-gray-700',
                    'cancelada' => 'bg-gray-100 dark:bg-gray-800/50 border-gray-300 dark:border-gray-700'
                ];
        ?>
        <div class="<?php echo $statusColors[$currentStatus] ?? 'bg-gray-100'; ?> border rounded-lg p-3 mb-2">
            <h3 class="font-semibold text-gray-900 dark:text-white flex items-center">
                <?php if ($currentStatus === 'ativa'): ?>
                    <i class="fas fa-clock text-green-600 dark:text-green-400 mr-2"></i>
                <?php elseif ($currentStatus === 'em_multa'): ?>
                    <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 mr-2"></i>
                <?php else: ?>
                    <i class="fas fa-history text-gray-600 dark:text-gray-400 mr-2"></i>
                <?php endif; ?>
                <?php echo $statusLabels[$currentStatus] ?? ucfirst($currentStatus); ?>
            </h3>
        </div>
        <?php endif; ?>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div class="flex-1">
                    <div class="flex items-center space-x-3 mb-2">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo e($reserva['estacionamento_nome']); ?></h3>
                        <span class="px-3 py-1 text-xs font-semibold rounded-full <?php echo getStatusBadge($reserva['status']); ?>">
                            <?php echo ucfirst($reserva['status']); ?>
                        </span>
                        <?php if ($reserva['pagamento_status']): ?>
                        <span class="px-3 py-1 text-xs font-semibold rounded-full <?php 
                            $status_pagamento = ($reserva['pagamento_status'] === 'pendente') ? 'pago' : $reserva['pagamento_status'];
                            echo getStatusBadge($status_pagamento);
                        ?>">
                            Pag: <?php echo ucfirst($status_pagamento); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3"><?php echo e($reserva['endereco']); ?></p>
                    
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
                        <div>
                            <p class="text-gray-600 dark:text-gray-400">Vaga</p>
                            <p class="font-semibold text-gray-900 dark:text-white"><?php echo e($reserva['numero_vaga']); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-600 dark:text-gray-400">Início</p>
                            <p class="font-semibold text-gray-900 dark:text-white"><?php echo formatDateTime($reserva['data_inicio']); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-600 dark:text-gray-400">Término</p>
                            <p class="font-semibold text-gray-900 dark:text-white"><?php echo formatDateTime($reserva['data_fim']); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-600 dark:text-gray-400">Valor</p>
                            <p class="font-bold text-lg text-primary dark:text-secondary"><?php echo formatCurrency($reserva['valor_total']); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-600 dark:text-gray-400">Multa</p>
                            <?php if (!empty($reserva['valor_multa']) && $reserva['valor_multa'] > 0): ?>
                                <p class="font-bold text-lg text-red-600"><?php echo formatCurrency($reserva['valor_multa']); ?></p>
                            <?php else: ?>
                                <p class="text-gray-400 text-sm">—</p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <p class="text-gray-600 dark:text-gray-400">Saída</p>
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?php 
                                switch($reserva['status_saida']) {
                                    case 'pendente': echo 'bg-yellow-100 text-yellow-800'; break;
                                    case 'autorizada': echo 'bg-green-100 text-green-800'; break;
                                    case 'negada': echo 'bg-red-100 text-red-800'; break;
                                    default: echo 'bg-gray-100 text-gray-800';
                                }
                            ?>">
                                <?php echo ucfirst($reserva['status_saida'] ?: 'nenhum'); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 md:mt-0 md:ml-6 flex flex-col space-y-2">
                    <?php 
                    $agora = date('Y-m-d H:i:s');
                    $podeCancelar = ($reserva['status'] === 'ativa' && $reserva['data_fim'] >= $agora);
                    
                    // Regra dos 30 minutos após autorização de saída
                    if ($reserva['status_saida'] === 'autorizada' && !empty($reserva['data_saida_real'])) {
                        $dataAuth = new DateTime($reserva['data_saida_real']);
                        $agoraDT = new DateTime($agora);
                        $intervalo = $dataAuth->diff($agoraDT);
                        $minutosPassados = ($intervalo->days * 24 * 60) + ($intervalo->h * 60) + $intervalo->i;
                        
                        if ($minutosPassados >= 30) {
                            $podeCancelar = false;
                        }
                    }
                    ?>
                    
                    <!-- BOTÃO PAGAR MULTA (APENAS MULTAS) -->
                    <?php if (!empty($reserva['valor_multa']) && $reserva['valor_multa'] > 0 && $reserva['status'] === 'em_multa'): ?>
                    <a href="/smartpark/usuario/pagamento.php?reserva=<?php echo $reserva['id']; ?>&multa=1" 
                       class="px-4 py-2 bg-red-600 text-white rounded-lg text-center hover:bg-red-700 transition">
                        <i class="fas fa-exclamation-circle mr-1"></i> Pagar Multas
                    </a>
                    <?php endif; ?>

                    <!-- BOTÃO SOLICITAR SAÍDA -->
                    <?php if (($reserva['status'] === 'ativa' || $reserva['status'] === 'em_multa') && $reserva['status_saida'] !== 'autorizada' && $reserva['status_saida'] !== 'pendente'): ?>
                    <a href="?request_exit=<?php echo $reserva['id']; ?>" 
                       class="px-4 py-2 bg-blue-600 text-white rounded-lg text-center hover:bg-blue-700 transition btn-solicitar-saida">
                        <i class="fas fa-door-open mr-1"></i> Solicitar Saída
                    </a>
                    <?php elseif ($reserva['status_saida'] === 'pendente'): ?>
                    <div class="px-4 py-2 bg-yellow-500 text-white rounded-lg text-center animate-pulse">
                        <i class="fas fa-hourglass-half mr-1"></i> Aguardando Autorização...
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($podeCancelar): ?>
                    <a href="?cancel=<?php echo $reserva['id']; ?>" 
                       class="px-4 py-2 bg-red-600 text-white rounded-lg text-center hover:bg-red-700 transition btn-cancelar-reserva">
                        <i class="fas fa-times mr-1"></i> Cancelar
                    </a>
                    <?php elseif ($reserva['status'] === 'ativa' && $reserva['data_fim'] < $agora): ?>
                    <div class="px-4 py-2 bg-gray-400 text-white rounded-lg text-center cursor-not-allowed" title="Não é possível cancelar após o término">
                        <i class="fas fa-ban mr-1"></i> Expirada
                    </div>
                    <?php elseif ($reserva['status'] === 'em_multa'): ?>
                    <div class="px-4 py-2 bg-red-800 text-white rounded-lg text-center cursor-not-allowed" title="Contacte a administração">
                        <i class="fas fa-exclamation-triangle mr-1"></i> Em Multa
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
document.querySelectorAll('.btn-solicitar-saida').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        const url = this.getAttribute('href');
        Swal.fire({
            title: 'Solicitar Saída',
            text: 'Deseja solicitar autorização para retirar a viatura?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#1E3A8A',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Sim, solicitar',
            cancelButtonText: 'Não'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    });
});

document.querySelectorAll('.btn-cancelar-reserva').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        const url = this.getAttribute('href');
        Swal.fire({
            title: 'Confirmar Cancelamento',
            text: 'Tem certeza que deseja cancelar esta reserva? Esta ação não pode ser desfeita.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#DC2626',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Sim, cancelar',
            cancelButtonText: 'Manter reserva'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
