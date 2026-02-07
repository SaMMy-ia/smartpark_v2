<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/multas.php';

if (!isset($skipRoleCheck)) {
    requireRole('admin');
}

// Verificar multas automáticas
verificarMultasAutomaticas($pdo);

// Handle Actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = (int)$_GET['id'];
    
    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE reservas SET status = 'ativa' WHERE id = ?");
        $stmt->execute([$id]);
        logAction($_SESSION['user_id'], "Reserva ID $id aprovada");
        $redirectUrl = $_SERVER['PHP_SELF'];
        redirectWithMessage($redirectUrl, 'Reserva aprovada com sucesso!');
    } elseif ($action === 'cancel') {
        $stmt = $pdo->prepare("UPDATE reservas SET status = 'cancelada' WHERE id = ?");
        $stmt->execute([$id]);
        
        // Free the spot
        $stmt = $pdo->prepare("UPDATE vagas v JOIN reservas r ON v.id = r.vaga_id SET v.status = 'livre' WHERE r.id = ?");
        $stmt->execute([$id]);
        
        logAction($_SESSION['user_id'], "Reserva ID $id cancelada");
        $redirectUrl = $_SERVER['PHP_SELF'];
        redirectWithMessage($redirectUrl, 'Reserva cancelada com sucesso!');
    }
}

// Get all reservations
$stmt = $pdo->query("
    SELECT r.*, u.nome as usuario_nome, v.numero_vaga, e.nome as estacionamento_nome,
           p.status as pagamento_status
    FROM reservas r
    JOIN usuarios u ON r.usuario_id = u.id
    JOIN vagas v ON r.vaga_id = v.id
    JOIN estacionamentos e ON v.estacionamento_id = e.id
    LEFT JOIN pagamentos p ON r.id = p.reserva_id
    ORDER BY r.data_inicio DESC
");
$reservas = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            <i class="fas fa-calendar-check mr-2"></i> Gerenciar Reservas
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Visualize e gerencie todas as reservas do Parque Rivas</p>
    </div>
    
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <input type="text" id="searchInput" onkeyup="filterTable('searchInput', 'reservasTable')"
                   placeholder="Buscar por matrícula, proprietário, vaga..." 
                   class="w-full md:w-96 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="reservasTable">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Vaga</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Veículo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Proprietário</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Contacto</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Período</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Valor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($reservas as $reserva): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">#<?php echo $reserva['id']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-100">
                            <?php echo e($reserva['numero_vaga']); ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                            <div>
                                <p class="font-semibold"><?php echo e($reserva['marca_veiculo'] ?? '-'); ?></p>
                                <p class="text-xs text-gray-500"><?php echo e(isset($reserva['matricula']) ? formatarMatricula($reserva['matricula']) : '-'); ?></p>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                            <div>
                                <p class="font-medium"><?php echo e($reserva['proprietario'] ?? '-'); ?></p>
                                <p class="text-xs text-gray-500"><?php echo e($reserva['usuario_nome']); ?></p>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                            <?php echo e($reserva['contacto'] ?? '-'); ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                            <?php echo formatDateTime($reserva['data_inicio']); ?><br>
                            <span class="text-xs text-gray-500">até <?php echo formatDateTime($reserva['data_fim']); ?></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo getStatusBadge($reserva['status']); ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $reserva['status'])); ?>
                            </span>
                            <?php if ($reserva['status'] === 'em_multa'): ?>
                            <p class="text-xs text-red-600 dark:text-red-400 mt-1">
                                Multa: <?php echo formatCurrency($reserva['valor_multa'] ?? 0); ?>
                            </p>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-100">
                            <?php echo formatCurrency($reserva['valor_total']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <?php if ($reserva['status'] === 'pendente'): ?>
                            <a href="?action=approve&id=<?php echo $reserva['id']; ?>" class="text-green-600 hover:text-green-900 dark:text-green-400 mr-3" title="Aprovar">
                                <i class="fas fa-check"></i>
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($reserva['status'] === 'ativa' || $reserva['status'] === 'pendente'): ?>
                            <a href="?action=cancel&id=<?php echo $reserva['id']; ?>" onclick="return confirm('Tem certeza que deseja cancelar esta reserva?')" class="text-red-600 hover:text-red-900 dark:text-red-400" title="Cancelar">
                                <i class="fas fa-times"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function filterTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const filter = input.value.toUpperCase();
    const table = document.getElementById(tableId);
    const tr = table.getElementsByTagName('tr');
    
    for (let i = 1; i < tr.length; i++) {
        const td = tr[i].getElementsByTagName('td');
        let found = false;
        
        for (let j = 0; j < td.length; j++) {
            if (td[j]) {
                const txtValue = td[j].textContent || td[j].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }
        
        tr[i].style.display = found ? '' : 'none';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
