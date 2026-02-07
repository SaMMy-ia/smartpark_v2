<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/multas.php';
requireRole('funcionario');

/* ============================= */
/* FUNÇÃO AUXILIAR PARA LIBERAR VAGA */
function liberarVagaDaReserva($pdo, $reservaId) {
    // Buscar a vaga da reserva
    $stmt = $pdo->prepare("SELECT vaga_id, status, valor_multa FROM reservas WHERE id = ?");
    $stmt->execute([$reservaId]);
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reserva) {
        throw new Exception("Reserva não encontrada.");
    }

    $vagaId = $reserva['vaga_id'];
    $valorMulta = $reserva['valor_multa'] ?? 0;

    // Atualizar status da reserva
    if ($reserva['status'] === 'em_multa') {
        $stmt = $pdo->prepare("
            UPDATE reservas
            SET status = 'vaga_liberada',
                data_saida_real = NOW(),
                multa_fechada = 1
            WHERE id = ?
        ");
        $stmt->execute([$reservaId]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE reservas
            SET status = 'vaga_liberada',
                data_saida_real = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$reservaId]);
    }

    // Liberar vaga
    $stmt = $pdo->prepare("UPDATE vagas SET status = 'livre' WHERE id = ?");
    $stmt->execute([$vagaId]);

    // Atualizar contagem de vagas disponíveis no estacionamento
    $stmt = $pdo->prepare("
        UPDATE estacionamentos e
        JOIN vagas v ON v.estacionamento_id = e.id
        SET e.vagas_disponiveis = e.vagas_disponiveis + 1
        WHERE v.id = ?
    ");
    $stmt->execute([$vagaId]);

    // Log
    $logMsg = $valorMulta > 0
        ? "Vaga liberada com multa pendente. Reserva #$reservaId. Multa congelada em " . number_format($valorMulta, 2) . " MT"
        : "Reserva #$reservaId finalizada e vaga liberada.";
    logAction($_SESSION['user_id'], $logMsg);

    return $valorMulta;
}

/* ============================= */
/* AÇÃO: LIBERAR VAGA */
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'liberar_vaga') {
    $reservaId = (int)$_GET['id'];

    try {
        $pdo->beginTransaction();

        $valorMulta = liberarVagaDaReserva($pdo, $reservaId);

        $pdo->commit();

        if ($valorMulta > 0) {
            $_SESSION['success'] = "Vaga liberada! Multa congelada em " . number_format($valorMulta, 2) . " MT aguardando pagamento.";
        } else {
            $_SESSION['success'] = "Vaga liberada com sucesso!";
        }

        header("Location: multas.php");
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['error'] = "Erro ao liberar vaga: " . $e->getMessage();
        header("Location: multas.php");
        exit;
    }
}

/* ============================= */
/* AÇÃO: RESOLVER MULTA (PAGAR/CANCELAR) */
if (isset($_GET['action'], $_GET['id'])) {
    $action = $_GET['action'];
    $reservaId = (int)$_GET['id'];
    
    if ($action === 'resolver_pagar') {
        if (resolverMulta($pdo, $reservaId, 'pagar')) {
            logAction($_SESSION['user_id'], "Multa resolvida (paga) - Reserva #$reservaId");
            redirectWithMessage($_SERVER['PHP_SELF'], 'Multa marcada como paga e reserva concluída.');
        }
    } elseif ($action === 'resolver_cancelar') {
        if (resolverMulta($pdo, $reservaId, 'cancelar')) {
            logAction($_SESSION['user_id'], "Multa resolvida (cancelada) - Reserva #$reservaId");
            redirectWithMessage($_SERVER['PHP_SELF'], 'Reserva cancelada e vaga liberada.');
        }
    }
}

/* ============================= */
/* Verificar multas automáticas */
$multasAplicadas = verificarMultasAutomaticas($pdo);

/* ============================= */
/* Estatísticas e multas */
$estatisticas = getEstatisticasMultas($pdo);
$multas = getReservasEmMulta($pdo);

/* ============================= */
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            <i class="fas fa-exclamation-triangle mr-2"></i> Visualizar Multas
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Consulte veículos em situação irregular</p>

        <?php if ($multasAplicadas > 0): ?>
        <div class="mt-4 bg-yellow-100 dark:bg-yellow-900 border-l-4 border-yellow-500 p-4 rounded">
            <p class="text-yellow-800 dark:text-yellow-200">
                <i class="fas fa-info-circle mr-2"></i>
                <?php echo $multasAplicadas; ?> multa<?php echo $multasAplicadas > 1 ? 's foram aplicadas' : ' foi aplicada'; ?> automaticamente.
            </p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-red-50 dark:bg-red-900 rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-red-600 dark:text-red-300 font-medium">Multas Ativas</p>
                    <p class="text-3xl font-bold text-red-700 dark:text-red-200 mt-2">
                        <?php echo $estatisticas['ativas']['quantidade']; ?>
                    </p>
                    <p class="text-sm text-red-600 dark:text-red-300 mt-1">
                        Total: <?php echo formatCurrency($estatisticas['ativas']['valor']); ?>
                    </p>
                </div>
                <div class="text-red-500 dark:text-red-300">
                    <i class="fas fa-exclamation-circle text-5xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-green-50 dark:bg-green-900 rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-green-600 dark:text-green-300 font-medium">Multas Pagas (Histórico)</p>
                    <p class="text-3xl font-bold text-green-700 dark:text-green-200 mt-2">
                        <?php echo $estatisticas['pagas']['quantidade']; ?>
                    </p>
                    <p class="text-sm text-green-600 dark:text-green-300 mt-1">
                        Total: <?php echo formatCurrency($estatisticas['pagas']['valor']); ?>
                    </p>
                </div>
                <div class="text-green-500 dark:text-green-300">
                    <i class="fas fa-check-circle text-5xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Fines Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-list mr-2"></i> Multas Ativas
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                O funcionário pode apenas liberar a vaga para interromper a multa
            </p>
        </div>

        <?php if (empty($multas)): ?>
        <div class="p-8 text-center">
            <i class="fas fa-check-circle text-6xl text-green-300 dark:text-green-600 mb-4"></i>
            <p class="text-gray-600 dark:text-gray-400">Nenhuma multa ativa no momento.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th>Reserva</th>
                        <th>Vaga</th>
                        <th>Veículo</th>
                        <th>Proprietário</th>
                        <th>Contacto</th>
                        <th>Período</th>
                        <th>Multa</th>
                        <th>Ação</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php foreach ($multas as $multa): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td>#<?php echo $multa['id']; ?></td>
                        <td><?php echo e($multa['numero_vaga']); ?></td>
                        <td>
                            <p class="font-semibold"><?php echo e($multa['marca_veiculo']); ?></p>
                            <p class="text-xs text-gray-500"><?php echo e(formatarMatricula($multa['matricula'])); ?></p>
                        </td>
                        <td><?php echo e($multa['proprietario']); ?></td>
                        <td><?php echo e($multa['contacto']); ?></td>
                        <td><?php echo formatDateTime($multa['data_fim']); ?></td>
                        <td class="text-red-600 font-semibold"><?php echo formatCurrency($multa['valor_multa']); ?></td>
                        <td>
                            <div class="flex flex-col gap-2">
                                <a href="?action=liberar_vaga&id=<?php echo $multa['id']; ?>"
                                   onclick="return confirm('Confirmar liberação da vaga? Isso interrompe a multa.')"
                                   class="inline-flex items-center px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-semibold">
                                    <i class="fas fa-unlock mr-1"></i> Liberar vaga
                                </a>
                                <a href="?action=resolver_pagar&id=<?php echo $multa['id']; ?>" 
                                   onclick="return confirm('Confirmar que a multa foi paga?')"
                                   class="inline-flex items-center px-3 py-1 bg-green-600 hover:bg-green-700 text-white rounded text-xs font-semibold transition"
                                   title="Marcar como paga">
                                    <i class="fas fa-check mr-1"></i> Pagar
                                </a>
                                <a href="?action=resolver_cancelar&id=<?php echo $multa['id']; ?>" 
                                   onclick="return confirm('Cancelar esta reserva e liberar a vaga?')"
                                   class="inline-flex items-center px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-xs font-semibold transition"
                                   title="Cancelar reserva">
                                    <i class="fas fa-times mr-1"></i> Cancelar
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
