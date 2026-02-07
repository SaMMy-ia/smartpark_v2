<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

require_once __DIR__ . '/../includes/header.php';

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
$totalUsuarios = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM estacionamentos");
$totalEstacionamentos = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM vagas");
$totalVagas = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM vagas WHERE status = 'livre'");
$vagasLivres = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM reservas WHERE status = 'ativa'");
$reservasAtivas = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT SUM(valor) as total FROM pagamentos WHERE status = 'pago'");
$receitaTotal = $stmt->fetch()['total'] ?? 0;

// Get recent reservations
$stmt = $pdo->query("
    SELECT r.*, u.nome as usuario_nome, v.numero_vaga, e.nome as estacionamento_nome
    FROM reservas r
    JOIN usuarios u ON r.usuario_id = u.id
    JOIN vagas v ON r.vaga_id = v.id
    JOIN estacionamentos e ON v.estacionamento_id = e.id
    ORDER BY r.data_inicio DESC
    LIMIT 5
");
$recentReservas = $stmt->fetchAll();

// Get occupancy data for chart
$stmt = $pdo->query("
    SELECT e.nome, 
           e.capacidade_total,
           COUNT(CASE WHEN v.status = 'ocupada' OR v.status = 'reservada' THEN 1 END) as ocupadas
    FROM estacionamentos e
    LEFT JOIN vagas v ON e.id = v.estacionamento_id
    GROUP BY e.id
");
$occupancyData = $stmt->fetchAll();

// Get revenue data (last 7 days)
$stmt = $pdo->query("
    SELECT DATE(data_pagamento) as data, SUM(valor) as total
    FROM pagamentos
    WHERE status = 'pago' AND data_pagamento >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(data_pagamento)
    ORDER BY data
");
$revenueData = $stmt->fetchAll();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            <i class="fas fa-chart-line mr-2"></i> Dashboard Administrativo
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Visão geral do sistema SmartPark</p>
    </div>
    
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Usuarios -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 border-l-4 border-blue-500 transform hover:scale-105 transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Total de Usuários</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white"><?php echo $totalUsuarios; ?></p>
                </div>
                <div class="bg-blue-100 dark:bg-blue-900 p-4 rounded-full">
                    <i class="fas fa-users text-blue-600 dark:text-blue-300 text-2xl"></i>
                </div>
            </div>
        </div>
        
        <!-- Total Estacionamentos -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 border-l-4 border-green-500 transform hover:scale-105 transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Estacionamentos</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white"><?php echo $totalEstacionamentos; ?></p>
                </div>
                <div class="bg-green-100 dark:bg-green-900 p-4 rounded-full">
                    <i class="fas fa-building text-green-600 dark:text-green-300 text-2xl"></i>
                </div>
            </div>
        </div>
        
        <!-- Vagas Livres -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 border-l-4 border-yellow-500 transform hover:scale-105 transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Vagas Livres</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white"><?php echo $vagasLivres; ?> / <?php echo $totalVagas; ?></p>
                </div>
                <div class="bg-yellow-100 dark:bg-yellow-900 p-4 rounded-full">
                    <i class="fas fa-parking text-yellow-600 dark:text-yellow-300 text-2xl"></i>
                </div>
            </div>
        </div>
        
        <!-- Receita Total -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 border-l-4 border-purple-500 transform hover:scale-105 transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Receita Total</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white"><?php echo formatCurrency($receitaTotal); ?></p>
                </div>
                <div class="bg-purple-100 dark:bg-purple-900 p-4 rounded-full">
                    <i class="fas fa-dollar-sign text-purple-600 dark:text-purple-300 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Occupancy Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-chart-bar mr-2"></i> Taxa de Ocupação por Estacionamento
            </h3>
            <canvas id="occupancyChart"></canvas>
        </div>
        
        <!-- Revenue Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-chart-line mr-2"></i> Receita (Últimos 7 Dias)
            </h3>
            <canvas id="revenueChart"></canvas>
        </div>
    </div>
    
    <!-- Recent Reservations -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            <i class="fas fa-calendar-check mr-2"></i> Reservas Recentes
        </h3>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Usuário</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estacionamento</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Vaga</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Data Início</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Valor</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($recentReservas as $reserva): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">#<?php echo $reserva['id']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"><?php echo e($reserva['usuario_nome']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"><?php echo e($reserva['estacionamento_nome']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"><?php echo e($reserva['numero_vaga']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"><?php echo formatDateTime($reserva['data_inicio']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo getStatusBadge($reserva['status']); ?>">
                                <?php echo ucfirst($reserva['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-100"><?php echo formatCurrency($reserva['valor_total']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="mt-4 text-center">
            <a href="/smartpark/admin/reservas.php" class="text-primary dark:text-secondary hover:underline">
                Ver todas as reservas <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
    </div>
</div>

<script>
// Occupancy Chart
const occupancyCtx = document.getElementById('occupancyChart').getContext('2d');
const occupancyChart = new Chart(occupancyCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($occupancyData, 'nome')); ?>,
        datasets: [{
            label: 'Ocupadas',
            data: <?php echo json_encode(array_column($occupancyData, 'ocupadas')); ?>,
            backgroundColor: 'rgba(239, 68, 68, 0.7)',
            borderColor: 'rgba(239, 68, 68, 1)',
            borderWidth: 1
        }, {
            label: 'Capacidade Total',
            data: <?php echo json_encode(array_column($occupancyData, 'capacidade_total')); ?>,
            backgroundColor: 'rgba(16, 185, 129, 0.7)',
            borderColor: 'rgba(16, 185, 129, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Revenue Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($revenueData, 'data')); ?>,
        datasets: [{
            label: 'Receita (MZN)',
            data: <?php echo json_encode(array_column($revenueData, 'total')); ?>,
            backgroundColor: 'rgba(30, 58, 138, 0.2)',
            borderColor: 'rgba(30, 58, 138, 1)',
            borderWidth: 2,
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
