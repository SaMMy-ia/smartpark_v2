<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('usuario');

// Get user's history
$stmt = $pdo->prepare("
    SELECT r.*, v.numero_vaga, e.nome as estacionamento_nome,
           p.status as pagamento_status, p.metodo as pagamento_metodo
    FROM reservas r
    JOIN vagas v ON r.vaga_id = v.id
    JOIN estacionamentos e ON v.estacionamento_id = e.id
    LEFT JOIN pagamentos p ON r.id = p.reserva_id
    WHERE r.usuario_id = ? AND r.status IN ('concluida', 'cancelada')
    ORDER BY r.data_inicio DESC
");
$stmt->execute([$_SESSION['user_id']]);
$historico = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            <i class="fas fa-history mr-2"></i> Histórico de Reservas
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Visualize seu histórico completo</p>
    </div>

    <?php if (empty($historico)): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-12 text-center">
            <i class="fas fa-history text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
            <p class="text-gray-600 dark:text-gray-400">Nenhum histórico encontrado.</p>
        </div>
    <?php else: ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                Estacionamento</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                Vaga</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                Período</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                Valor</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($historico as $item): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    #<?php echo $item['id']; ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    <?php echo e($item['estacionamento_nome']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    <?php echo e($item['numero_vaga']); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    <?php echo formatDate($item['data_inicio']); ?> -
                                    <?php echo formatDate($item['data_fim']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-2 py-1 text-xs font-semibold rounded-full <?php echo getStatusBadge($item['status']); ?>">
                                        <?php echo ucfirst($item['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    <?php echo formatCurrency($item['valor_total']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>