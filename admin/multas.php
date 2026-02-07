<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/multas.php';
requireRole('admin');

// Verificar multas automáticas
$multasAplicadas = verificarMultasAutomaticas($pdo);

// Handle fine resolution
if (isset($_GET['action']) && isset($_GET['id'])) {
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

// Get fines statistics
$estatisticas = getEstatisticasMultas($pdo);

// Get all fines
$multas = getReservasEmMulta($pdo);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            <i class="fas fa-exclamation-triangle mr-2"></i> Gerenciar Multas
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Visualize e gerencie veículos em situação irregular</p>
        
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Reserva</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Vaga</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Veículo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Proprietário</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Contacto</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Período</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Multa</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($multas as $multa): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                            #<?php echo $multa['id']; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                <?php echo e($multa['numero_vaga']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                            <div>
                                <p class="font-semibold"><?php echo e($multa['marca_veiculo']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo e(formatarMatricula($multa['matricula'])); ?></p>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                            <div>
                                <p class="font-medium"><?php echo e($multa['proprietario']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo e($multa['usuario_nome']); ?></p>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                            <i class="fas fa-phone mr-1"></i> <?php echo e($multa['contacto']); ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                            <div>
                                <p class="text-xs text-gray-500">Término previsto:</p>
                                <p class="font-medium"><?php echo formatDateTime($multa['data_fim']); ?></p>
                                <p class="text-xs text-red-600 dark:text-red-400 mt-1">
                                    Multa em: <?php echo formatDateTime($multa['data_multa']); ?>
                                </p>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div>
                                <p class="font-semibold text-red-600 dark:text-red-400">
                                    <?php echo formatCurrency($multa['valor_multa']); ?>
                                </p>
                                <p class="text-xs text-gray-500">
                                    Reserva: <?php echo formatCurrency($multa['valor_total']); ?>
                                </p>
                                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mt-1">
                                    Total: <?php echo formatCurrency($multa['valor_total'] + $multa['valor_multa']); ?>
                                </p>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="flex flex-col gap-2">
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
