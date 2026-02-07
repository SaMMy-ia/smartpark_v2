<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('usuario');

// Get user's active reservations
$stmt = $pdo->prepare("
    SELECT r.*, v.numero_vaga, e.nome as estacionamento_nome, e.endereco
    FROM reservas r
    JOIN vagas v ON r.vaga_id = v.id
    JOIN estacionamentos e ON v.estacionamento_id = e.id
    WHERE r.usuario_id = ? AND r.status = 'ativa'
    ORDER BY r.data_inicio DESC
");
$stmt->execute([$_SESSION['user_id']]);
$reservasAtivas = $stmt->fetchAll();

// Get user's stats
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM reservas WHERE usuario_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$totalReservas = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT SUM(valor_total) as total FROM reservas WHERE usuario_id = ? AND status = 'concluida'");
$stmt->execute([$_SESSION['user_id']]);
$totalGasto = $stmt->fetch()['total'] ?? 0;

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Welcome Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            Bem-vindo, <?php echo e($currentUser['nome']); ?>! 👋
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Gerencie suas reservas de estacionamento</p>
    </div>
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white transform hover:scale-105 transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90 mb-1">Reservas Ativas</p>
                    <p class="text-4xl font-bold"><?php echo count($reservasAtivas); ?></p>
                </div>
                <div class="bg-white bg-opacity-20 p-4 rounded-full">
                    <i class="fas fa-calendar-check text-3xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white transform hover:scale-105 transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90 mb-1">Total de Reservas</p>
                    <p class="text-4xl font-bold"><?php echo $totalReservas; ?></p>
                </div>
                <div class="bg-white bg-opacity-20 p-4 rounded-full">
                    <i class="fas fa-history text-3xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white transform hover:scale-105 transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90 mb-1">Total Gasto</p>
                    <p class="text-4xl font-bold"><?php echo formatCurrency($totalGasto); ?></p>
                </div>
                <div class="bg-white bg-opacity-20 p-4 rounded-full">
                    <i class="fas fa-dollar-sign text-3xl"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <a href="/smartpark/usuario/buscar-vagas.php" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 text-center hover:shadow-xl transform hover:scale-105 transition">
            <i class="fas fa-search text-4xl text-primary dark:text-secondary mb-3"></i>
            <h3 class="font-semibold text-gray-900 dark:text-white">Buscar Vagas</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Encontre vagas disponíveis</p>
        </a>
        
        <a href="/smartpark/usuario/minhas-reservas.php" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 text-center hover:shadow-xl transform hover:scale-105 transition">
            <i class="fas fa-calendar text-4xl text-primary dark:text-secondary mb-3"></i>
            <h3 class="font-semibold text-gray-900 dark:text-white">Minhas Reservas</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Gerencie suas reservas</p>
        </a>
        
        <a href="/smartpark/usuario/historico.php" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 text-center hover:shadow-xl transform hover:scale-105 transition">
            <i class="fas fa-history text-4xl text-primary dark:text-secondary mb-3"></i>
            <h3 class="font-semibold text-gray-900 dark:text-white">Histórico</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Veja seu histórico</p>
        </a>
        
        <a href="/smartpark/usuario/perfil.php" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 text-center hover:shadow-xl transform hover:scale-105 transition">
            <i class="fas fa-user-cog text-4xl text-primary dark:text-secondary mb-3"></i>
            <h3 class="font-semibold text-gray-900 dark:text-white">Meu Perfil</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Edite suas informações</p>
        </a>
    </div>
    
    <!-- Active Reservations -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
            <i class="fas fa-calendar-check mr-2"></i> Reservas Ativas
        </h3>
        
        <?php if (empty($reservasAtivas)): ?>
        <div class="text-center py-12">
            <i class="fas fa-calendar-times text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
            <p class="text-gray-600 dark:text-gray-400">Você não tem reservas ativas no momento.</p>
            <a href="/smartpark/usuario/buscar-vagas.php" class="inline-block mt-4 px-6 py-3 bg-primary dark:bg-secondary text-white rounded-lg hover:bg-blue-900 dark:hover:bg-green-600 transition">
                <i class="fas fa-search mr-2"></i> Buscar Vagas
            </a>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ($reservasAtivas as $reserva): ?>
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-lg transition">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white"><?php echo e($reserva['estacionamento_nome']); ?></h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo e($reserva['endereco']); ?></p>
                    </div>
                    <span class="px-3 py-1 text-xs font-semibold rounded-full <?php echo getStatusBadge($reserva['status']); ?>">
                        <?php echo ucfirst($reserva['status']); ?>
                    </span>
                </div>
                
                <div class="space-y-2 text-sm">
                    <div class="flex items-center text-gray-700 dark:text-gray-300">
                        <i class="fas fa-parking w-5"></i>
                        <span>Vaga: <strong><?php echo e($reserva['numero_vaga']); ?></strong></span>
                    </div>
                    <div class="flex items-center text-gray-700 dark:text-gray-300">
                        <i class="fas fa-clock w-5"></i>
                        <span><?php echo formatDateTime($reserva['data_inicio']); ?> - <?php echo formatDateTime($reserva['data_fim']); ?></span>
                    </div>
                    <div class="flex items-center text-gray-700 dark:text-gray-300">
                        <i class="fas fa-dollar-sign w-5"></i>
                        <span><strong><?php echo formatCurrency($reserva['valor_total']); ?></strong></span>
                    </div>
                </div>
                
                <div class="mt-4 flex space-x-2">
                    <a href="/smartpark/usuario/minhas-reservas.php" class="flex-1 text-center px-4 py-2 bg-primary dark:bg-secondary text-white rounded-lg text-sm hover:bg-blue-900 dark:hover:bg-green-600 transition">
                        Ver Detalhes
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
