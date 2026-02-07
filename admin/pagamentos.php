<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($skipRoleCheck)) {
    requireRole('admin');
}

// Get filter parameters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : null;
$metodoFilter = isset($_GET['metodo']) ? $_GET['metodo'] : null;

// Build query
$query = "
    SELECT p.*, r.id as reserva_id, r.valor_total as reserva_valor,
           u.nome as usuario_nome, u.email as usuario_email,
           v.numero_vaga, e.nome as estacionamento_nome
    FROM pagamentos p
    JOIN reservas r ON p.reserva_id = r.id
    JOIN usuarios u ON r.usuario_id = u.id
    JOIN vagas v ON r.vaga_id = v.id
    JOIN estacionamentos e ON v.estacionamento_id = e.id
    WHERE 1=1
";

if ($statusFilter) {
    $query .= " AND p.status = '$statusFilter'";
}
if ($metodoFilter) {
    $query .= " AND p.metodo = '$metodoFilter'";
}

$query .= " ORDER BY p.data_pagamento DESC";

$stmt = $pdo->query($query);
$pagamentos = $stmt->fetchAll();

// Get statistics
$stmt = $pdo->query("SELECT SUM(valor) as total FROM pagamentos WHERE status = 'pago'");
$totalPago = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->query("SELECT SUM(valor) as total FROM pagamentos WHERE status = 'pendente'");
$totalPendente = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->query("SELECT COUNT(*) as total FROM pagamentos WHERE status = 'pago'");
$countPago = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM pagamentos WHERE status = 'pendente'");
$countPendente = $stmt->fetch()['total'];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            <i class="fas fa-credit-card mr-2"></i> Gerenciar Pagamentos
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Visualize e gerencie todos os pagamentos do sistema</p>
    </div>
    
    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90 mb-1">Total Pago</p>
                    <p class="text-3xl font-bold"><?php echo formatCurrency($totalPago); ?></p>
                    <p class="text-xs opacity-75 mt-1"><?php echo $countPago; ?> pagamentos</p>
                </div>
                <i class="fas fa-check-circle text-4xl opacity-80"></i>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90 mb-1">Pendente</p>
                    <p class="text-3xl font-bold"><?php echo formatCurrency($totalPendente); ?></p>
                    <p class="text-xs opacity-75 mt-1"><?php echo $countPendente; ?> pagamentos</p>
                </div>
                <i class="fas fa-clock text-4xl opacity-80"></i>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90 mb-1">Total Geral</p>
                    <p class="text-3xl font-bold"><?php echo formatCurrency($totalPago + $totalPendente); ?></p>
                    <p class="text-xs opacity-75 mt-1"><?php echo count($pagamentos); ?> pagamentos</p>
                </div>
                <i class="fas fa-dollar-sign text-4xl opacity-80"></i>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90 mb-1">Taxa de Sucesso</p>
                    <p class="text-3xl font-bold"><?php echo $countPago + $countPendente > 0 ? round(($countPago / ($countPago + $countPendente)) * 100) : 0; ?>%</p>
                    <p class="text-xs opacity-75 mt-1">Pagamentos confirmados</p>
                </div>
                <i class="fas fa-chart-line text-4xl opacity-80"></i>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-4 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                <select name="status" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">Todos</option>
                    <option value="pago" <?php echo $statusFilter === 'pago' ? 'selected' : ''; ?>>Pago</option>
                    <option value="pendente" <?php echo $statusFilter === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                    <option value="falha" <?php echo $statusFilter === 'falha' ? 'selected' : ''; ?>>Falha</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Método</label>
                <select name="metodo" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">Todos</option>
                    <option value="cartao" <?php echo $metodoFilter === 'cartao' ? 'selected' : ''; ?>>Cartão</option>
                    <option value="pix" <?php echo $metodoFilter === 'pix' ? 'selected' : ''; ?>>Mpesa/ Emola/ Mkesh</option>
                    <option value="boleto" <?php echo $metodoFilter === 'boleto' ? 'selected' : ''; ?>>Boleto</option>
                </select>
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="w-full px-6 py-2 bg-primary dark:bg-secondary text-white rounded-lg hover:bg-blue-900 dark:hover:bg-green-600 transition">
                    <i class="fas fa-filter mr-2"></i> Filtrar
                </button>
            </div>
        </form>
    </div>
    
    <!-- Payments Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <input type="text" id="searchInput" onkeyup="filterTable('searchInput', 'pagamentosTable')"
                   placeholder="Buscar pagamentos..." 
                   class="w-full md:w-96 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="pagamentosTable">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Reserva</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Usuário</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Estacionamento</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Vaga</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Método</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Valor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Data</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($pagamentos as $pag): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">#<?php echo $pag['id']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">#<?php echo $pag['reserva_id']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"><?php echo e($pag['usuario_nome']); ?></td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100"><?php echo e($pag['estacionamento_nome']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"><?php echo e($pag['numero_vaga']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                            <i class="fas <?php echo $pag['metodo'] === 'cartao' ? 'fa-credit-card' : ($pag['metodo'] === 'pix' ? 'fa-qrcode' : 'fa-barcode'); ?> mr-1"></i>
                            <?php echo ucfirst($pag['metodo']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo getStatusBadge($pag['status']); ?>">
                                <?php echo ucfirst($pag['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-gray-100"><?php echo formatCurrency($pag['valor']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"><?php echo formatDateTime($pag['data_pagamento']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
