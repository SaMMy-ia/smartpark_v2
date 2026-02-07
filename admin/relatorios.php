<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

// Handle CSV Export
if (isset($_GET['export'])) {
    $tipo = $_GET['export'];
    $dataInicio = $_GET['data_inicio'] ?? null;
    $dataFim = $_GET['data_fim'] ?? null;
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=relatorio_' . $tipo . '_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    if ($tipo === 'ocupacao') {
        fputcsv($output, ['Estacionamento', 'Capacidade Total', 'Vagas Livres', 'Vagas Ocupadas', 'Vagas Reservadas', 'Taxa Ocupação']);
        
        $stmt = $pdo->query("
            SELECT e.nome, e.capacidade_total,
                   SUM(CASE WHEN v.status = 'livre' THEN 1 ELSE 0 END) as livres,
                   SUM(CASE WHEN v.status = 'ocupada' THEN 1 ELSE 0 END) as ocupadas,
                   SUM(CASE WHEN v.status = 'reservada' THEN 1 ELSE 0 END) as reservadas
            FROM estacionamentos e
            LEFT JOIN vagas v ON e.id = v.estacionamento_id
            GROUP BY e.id
        ");
        
        while ($row = $stmt->fetch()) {
            $taxaOcupacao = $row['capacidade_total'] > 0 ? round((($row['ocupadas'] + $row['reservadas']) / $row['capacidade_total']) * 100, 2) : 0;
            fputcsv($output, [
                $row['nome'],
                $row['capacidade_total'],
                $row['livres'],
                $row['ocupadas'],
                $row['reservadas'],
                $taxaOcupacao . '%'
            ]);
        }
    } elseif ($tipo === 'receita') {
        fputcsv($output, ['Data', 'Reservas', 'Valor Total', 'Pagamentos Confirmados', 'Pendentes']);
        
        $query = "
            SELECT DATE(r.data_inicio) as data,
                   COUNT(r.id) as total_reservas,
                   SUM(r.valor_total) as valor_total,
                   SUM(CASE WHEN p.status = 'pago' THEN p.valor ELSE 0 END) as pago,
                   SUM(CASE WHEN p.status = 'pendente' THEN p.valor ELSE 0 END) as pendente
            FROM reservas r
            LEFT JOIN pagamentos p ON r.id = p.reserva_id
        ";
        
        if ($dataInicio && $dataFim) {
            $query .= " WHERE r.data_inicio BETWEEN '$dataInicio' AND '$dataFim'";
        }
        
        $query .= " GROUP BY DATE(r.data_inicio) ORDER BY data DESC";
        
        $stmt = $pdo->query($query);
        
        while ($row = $stmt->fetch()) {
            fputcsv($output, [
                $row['data'],
                $row['total_reservas'],
                number_format($row['valor_total'], 2, ',', '.') . ' MT',
                number_format($row['pago'], 2, ',', '.') . ' MT',
                number_format($row['pendente'], 2, ',', '.') . ' MT'
            ]);
        }
    } elseif ($tipo === 'usuarios') {
        fputcsv($output, ['ID', 'Nome', 'Email', 'Role', 'Total Reservas', 'Total Gasto', 'Data Cadastro']);
        
        $stmt = $pdo->query("
            SELECT u.id, u.nome, u.email, u.role, u.data_criacao,
                   COUNT(r.id) as total_reservas,
                   SUM(r.valor_total) as total_gasto
            FROM usuarios u
            LEFT JOIN reservas r ON u.id = r.usuario_id
            GROUP BY u.id
            ORDER BY u.id DESC
        ");
        
        while ($row = $stmt->fetch()) {
            fputcsv($output, [
                $row['id'],
                $row['nome'],
                $row['email'],
                $row['role'],
                $row['total_reservas'] ?? 0,
                number_format($row['total_gasto'] ?? 0, 2, ',', '.') . ' MT',
                date('d/m/Y', strtotime($row['data_criacao']))
            ]);
        }
    }
    
    fclose($output);
    exit;
}

// Get report data
$dataInicio = $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$dataFim = $_GET['data_fim'] ?? date('Y-m-d');

// Ocupação por estacionamento
$stmt = $pdo->query("
    SELECT e.nome, e.capacidade_total,
           SUM(CASE WHEN v.status = 'livre' THEN 1 ELSE 0 END) as livres,
           SUM(CASE WHEN v.status = 'ocupada' THEN 1 ELSE 0 END) as ocupadas,
           SUM(CASE WHEN v.status = 'reservada' THEN 1 ELSE 0 END) as reservadas
    FROM estacionamentos e
    LEFT JOIN vagas v ON e.id = v.estacionamento_id
    GROUP BY e.id
");
$ocupacao = $stmt->fetchAll();

// Receita por período
$stmt = $pdo->prepare("
    SELECT DATE(r.data_inicio) as data,
           COUNT(r.id) as total_reservas,
           SUM(r.valor_total) as valor_total,
           SUM(CASE WHEN p.status = 'pago' THEN p.valor ELSE 0 END) as pago
    FROM reservas r
    LEFT JOIN pagamentos p ON r.id = p.reserva_id
    WHERE r.data_inicio BETWEEN ? AND ?
    GROUP BY DATE(r.data_inicio)
    ORDER BY data DESC
    LIMIT 10
");
$stmt->execute([$dataInicio, $dataFim]);
$receita = $stmt->fetchAll();

// Top usuários
$stmt = $pdo->query("
    SELECT u.nome, u.email,
           COUNT(r.id) as total_reservas,
           SUM(r.valor_total) as total_gasto
    FROM usuarios u
    LEFT JOIN reservas r ON u.id = r.usuario_id
    WHERE u.role = 'usuario'
    GROUP BY u.id
    ORDER BY total_gasto DESC
    LIMIT 10
");
$topUsuarios = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            <i class="fas fa-chart-bar mr-2"></i> Relatórios e Análises
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Gere relatórios detalhados do sistema</p>
    </div>
    
    <!-- Date Filter -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Data Início</label>
                <input type="date" name="data_inicio" value="<?php echo $dataInicio; ?>" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Data Fim</label>
                <input type="date" name="data_fim" value="<?php echo $dataFim; ?>" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="w-full px-6 py-2 bg-primary dark:bg-secondary text-white rounded-lg hover:bg-blue-900 dark:hover:bg-green-600 transition">
                    <i class="fas fa-filter mr-2"></i> Filtrar
                </button>
            </div>
        </form>
    </div>
    
    <!-- Export Buttons -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <a href="?export=ocupacao" class="bg-blue-600 text-white p-4 rounded-lg hover:bg-blue-700 transition text-center shadow-lg">
            <i class="fas fa-download mr-2"></i> Exportar Relatório de Ocupação (CSV)
        </a>
        <a href="?export=receita&data_inicio=<?php echo $dataInicio; ?>&data_fim=<?php echo $dataFim; ?>" class="bg-green-600 text-white p-4 rounded-lg hover:bg-green-700 transition text-center shadow-lg">
            <i class="fas fa-download mr-2"></i> Exportar Relatório de Receita (CSV)
        </a>
        <a href="?export=usuarios" class="bg-purple-600 text-white p-4 rounded-lg hover:bg-purple-700 transition text-center shadow-lg">
            <i class="fas fa-download mr-2"></i> Exportar Relatório de Usuários (CSV)
        </a>
    </div>
    
    <!-- Ocupação Report -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
            <i class="fas fa-parking mr-2"></i> Relatório de Ocupação
        </h3>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Estacionamento</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Capacidade</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Livres</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Ocupadas</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Reservadas</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Taxa Ocupação</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($ocupacao as $ocu): ?>
                    <?php
                    $taxaOcupacao = $ocu['capacidade_total'] > 0 ? round((($ocu['ocupadas'] + $ocu['reservadas']) / $ocu['capacidade_total']) * 100, 2) : 0;
                    ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100"><?php echo e($ocu['nome']); ?></td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100"><?php echo $ocu['capacidade_total']; ?></td>
                        <td class="px-6 py-4 text-sm text-green-600 dark:text-green-400 font-semibold"><?php echo $ocu['livres']; ?></td>
                        <td class="px-6 py-4 text-sm text-red-600 dark:text-red-400 font-semibold"><?php echo $ocu['ocupadas']; ?></td>
                        <td class="px-6 py-4 text-sm text-yellow-600 dark:text-yellow-400 font-semibold"><?php echo $ocu['reservadas']; ?></td>
                        <td class="px-6 py-4 text-sm">
                            <div class="flex items-center">
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-2">
                                    <div class="bg-primary dark:bg-secondary h-2 rounded-full" style="width: <?php echo $taxaOcupacao; ?>%"></div>
                                </div>
                                <span class="font-semibold text-gray-900 dark:text-gray-100"><?php echo $taxaOcupacao; ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Receita Report -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
            <i class="fas fa-dollar-sign mr-2"></i> Relatório de Receita (Últimos 10 Dias)
        </h3>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Data</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Reservas</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Valor Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Pago</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($receita as $rec): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100"><?php echo formatDate($rec['data']); ?></td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100"><?php echo $rec['total_reservas']; ?></td>
                        <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-gray-100"><?php echo formatCurrency($rec['valor_total']); ?></td>
                        <td class="px-6 py-4 text-sm font-semibold text-green-600 dark:text-green-400"><?php echo formatCurrency($rec['pago']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Top Usuarios -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
            <i class="fas fa-trophy mr-2"></i> Top 10 Usuários (Por Gasto)
        </h3>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nome</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Total Reservas</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Total Gasto</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($topUsuarios as $index => $user): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                            <?php if ($index < 3): ?>
                            <i class="fas fa-medal text-yellow-500 mr-2"></i>
                            <?php endif; ?>
                            <?php echo e($user['nome']); ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100"><?php echo e($user['email']); ?></td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100"><?php echo $user['total_reservas'] ?? 0; ?></td>
                        <td class="px-6 py-4 text-sm font-bold text-primary dark:text-secondary"><?php echo formatCurrency($user['total_gasto'] ?? 0); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
