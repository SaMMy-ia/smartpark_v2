<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('funcionario');

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM estacionamentos");
$totalEstacionamentos = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM vagas WHERE status = 'livre'");
$vagasLivres = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM reservas WHERE status = 'ativa'");
$reservasAtivas = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM reservas WHERE status_saida = 'pendente'");
$saidasPendentesCount = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM solicitacoes_veiculos WHERE status = 'pendente'");
$pedidosVeiculosCount = $stmt->fetch()['total'];


// Get pending exit requests
$stmt = $pdo->query("
    SELECT r.*, u.nome as usuario_nome, v.numero_vaga, e.id as estacionamento_id, e.nome as estacionamento_nome
    FROM reservas r
    JOIN usuarios u ON r.usuario_id = u.id
    JOIN vagas v ON r.vaga_id = v.id
    JOIN estacionamentos e ON v.estacionamento_id = e.id
    WHERE r.status_saida = 'pendente'
    ORDER BY r.data_inicio ASC
");
$solicitacoesSaida = $stmt->fetchAll();

// Get pending reservations
$stmt = $pdo->query("
    SELECT r.*, u.nome as usuario_nome, v.numero_vaga, e.nome as estacionamento_nome
    FROM reservas r
    JOIN usuarios u ON r.usuario_id = u.id
    JOIN vagas v ON r.vaga_id = v.id
    JOIN estacionamentos e ON v.estacionamento_id = e.id
    WHERE r.status = 'ativa'
    ORDER BY r.data_inicio DESC
    LIMIT 10
");
$reservasPendentes = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            Dashboard Funcionário
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Bem-vindo, <?php echo e($currentUser['nome']); ?>!</p>
    </div>

    <!-- Vehicle Requests Alert -->
    <?php if ($pedidosVeiculosCount > 0): ?>
        <div
            class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-600 p-4 mb-8 rounded shadow-sm flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-clipboard-list text-blue-600 dark:text-blue-400 text-xl mr-3"></i>
                <div>
                    <p class="text-blue-800 dark:text-blue-200 font-bold text-lg">
                        Há <?php echo $pedidosVeiculosCount; ?> pedido(s) de alteração de veículos aguardando análise.
                    </p>
                    <p class="text-blue-600 dark:text-blue-400 text-sm">Libere ou negue as solicitações dos usuários.</p>
                </div>
            </div>
            <a href="/smartpark/admin/pedidos-veiculos.php"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg font-bold hover:bg-blue-700 transition">
                Ver Pedidos
            </a>
        </div>
    <?php endif; ?>


    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div
            class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white transform hover:scale-105 transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90 mb-1">Estacionamentos</p>
                    <p class="text-3xl font-bold"><?php echo $totalEstacionamentos; ?></p>
                </div>
                <div class="bg-white bg-opacity-20 p-4 rounded-full">
                    <i class="fas fa-building text-2xl"></i>
                </div>
            </div>
        </div>


        <div
            class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-lg shadow-lg p-6 text-white transform hover:scale-105 transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90 mb-1">Vagas Livres</p>
                    <p class="text-3xl font-bold"><?php echo $vagasLivres; ?></p>
                </div>
                <div class="bg-white bg-opacity-20 p-4 rounded-full">
                    <i class="fas fa-parking text-2xl"></i>
                </div>
            </div>
        </div>


        <div
            class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-lg shadow-lg p-6 text-white transform hover:scale-105 transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90 mb-1">Reservas Ativas</p>
                    <p class="text-3xl font-bold"><?php echo $reservasAtivas; ?></p>
                </div>
                <div class="bg-white bg-opacity-20 p-4 rounded-full">
                    <i class="fas fa-calendar-check text-2xl"></i>
                </div>
            </div>
        </div>


        <div
            class="bg-gradient-to-br from-rose-500 to-rose-600 rounded-lg shadow-lg p-6 text-white border-l-4 border-red-700 transform hover:scale-105 transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90 mb-1">Saídas Pendentes</p>
                    <p class="text-3xl font-bold"><?php echo $saidasPendentesCount; ?></p>
                </div>
                <div class="bg-white bg-opacity-20 p-4 rounded-full">
                    <i class="fas fa-sign-out-alt text-2xl animate-pulse"></i>
                </div>
            </div>
        </div>

    </div>

    <!-- Exit Requests Section -->
    <?php if (!empty($solicitacoesSaida)): ?>
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg shadow-lg p-6 mb-8">
            <h3 class="text-lg font-bold text-red-800 dark:text-red-400 mb-4">
                <i class="fas fa-exclamation-triangle mr-2"></i> Solicitações de Saída (Ação Necessária)
            </h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-red-200 dark:divide-red-800">
                    <thead>
                        <tr class="text-xs font-medium text-red-700 dark:text-red-300 uppercase">
                            <th class="px-6 py-3 text-left">Usuário / Viatura</th>
                            <th class="px-6 py-3 text-left">Vaga / Parque</th>
                            <th class="px-6 py-3 text-left">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-red-100 dark:divide-red-900">
                        <?php foreach ($solicitacoesSaida as $req): ?>
                            <tr class="text-sm">
                                <td class="px-6 py-4">
                                    <p class="font-bold text-gray-900 dark:text-white"><?php echo e($req['usuario_nome']); ?>
                                    </p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400"><?php echo e($req['marca_veiculo']); ?>
                                        (<?php echo e($req['matricula']); ?>)</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-gray-900 dark:text-white"><?php echo e($req['numero_vaga']); ?>
                                    </p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">
                                        <?php echo e($req['estacionamento_nome']); ?>
                                    </p>
                                </td>
                                <td class="px-6 py-4 flex space-x-2">
                                    <form action="autorizar-saida.php" method="POST" class="inline">
                                        <input type="hidden" name="reserva_id" value="<?php echo $req['id']; ?>">
                                        <input type="hidden" name="acao" value="autorizar">
                                        <button type="submit"
                                            class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 transition">
                                            <i class="fas fa-check"></i> Autorizar
                                        </button>
                                    </form>
                                    <form action="autorizar-saida.php" method="POST" class="inline">
                                        <input type="hidden" name="reserva_id" value="<?php echo $req['id']; ?>">
                                        <input type="hidden" name="acao" value="negar">
                                        <button type="submit"
                                            class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 transition">
                                            <i class="fas fa-times"></i> Negar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Recent Reservations -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            <i class="fas fa-calendar-check mr-2"></i> Reservas Ativas
        </h3>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            Usuário</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            Estacionamento</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            Vaga</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            Data</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            Valor</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($reservasPendentes as $reserva): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                #<?php echo $reserva['id']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                <?php echo e($reserva['usuario_nome']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                <?php echo e($reserva['estacionamento_nome']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                <?php echo e($reserva['numero_vaga']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                <?php echo formatDateTime($reserva['data_inicio']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-100">
                                <?php echo formatCurrency($reserva['valor_total']); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>