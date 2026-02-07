<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/multas_service.php';
requireRole('usuario');

// Obter todas as multas do usuário (pendentes e pagas)
$todasMultas = getMultasUsuario($pdo, $_SESSION['user_id']);

// Separar multas pendentes e pagas
$multasPendentes = array_filter($todasMultas, function($multa) {
    return $multa['status_multa'] === 'pendente';
});

$multasPagas = array_filter($todasMultas, function($multa) {
    return $multa['status_multa'] === 'paga';
});

// Obter resumo
$resumo = getResumoMultasUsuario($pdo, $_SESSION['user_id']);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            <i class="fas fa-exclamation-triangle mr-2"></i> Minhas Multas
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Visualize e gerencie suas multas de estacionamento</p>
    </div>

    <!-- Resumo de Multas -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-red-50 dark:bg-red-900/30 rounded-lg shadow-lg p-6 border-2 border-red-200 dark:border-red-800">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-red-600 dark:text-red-300 font-medium">Multas Pendentes</p>
                    <p class="text-4xl font-bold text-red-700 dark:text-red-200 mt-2">
                        <?php echo $resumo['quantidade']; ?>
                    </p>
                    <p class="text-sm text-red-600 dark:text-red-300 mt-1">
                        Total: <?php echo formatCurrency($resumo['valor']); ?> MZN
                    </p>
                </div>
                <div class="text-red-500 dark:text-red-300">
                    <i class="fas fa-exclamation-circle text-6xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-green-50 dark:bg-green-900/30 rounded-lg shadow-lg p-6 border-2 border-green-200 dark:border-green-800">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-green-600 dark:text-green-300 font-medium">Multas Pagas</p>
                    <p class="text-4xl font-bold text-green-700 dark:text-green-200 mt-2">
                        <?php echo count($multasPagas); ?>
                    </p>
                    <p class="text-sm text-green-600 dark:text-green-300 mt-1">
                        Histórico
                    </p>
                </div>
                <div class="text-green-500 dark:text-green-300">
                    <i class="fas fa-check-circle text-6xl"></i>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($todasMultas)): ?>
    <!-- Nenhuma Multa -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-12 text-center">
        <i class="fas fa-smile text-6xl text-green-400 dark:text-green-500 mb-4"></i>
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Parabéns!</h3>
        <p class="text-gray-600 dark:text-gray-400">Você não possui multas registradas.</p>
        <a href="/smartpark/usuario/dashboard.php" class="inline-block mt-4 px-6 py-3 bg-primary dark:bg-secondary text-white rounded-lg hover:bg-blue-900 dark:hover:bg-green-600 transition">
            <i class="fas fa-home mr-2"></i> Voltar ao Dashboard
        </a>
    </div>
    <?php else: ?>

    <!-- Multas Pendentes -->
    <?php if (!empty($multasPendentes)): ?>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden mb-8">
        <div class="bg-red-600 text-white px-6 py-4">
            <h2 class="text-xl font-semibold flex items-center">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                Multas Pendentes (<?php echo count($multasPendentes); ?>)
            </h2>
            <p class="text-sm opacity-90 mt-1">Estas multas precisam ser pagas</p>
        </div>

        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            <?php foreach ($multasPendentes as $multa): ?>
            <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-3 mb-3">
                            <span class="px-3 py-1 bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 text-xs font-semibold rounded-full">
                                PENDENTE
                            </span>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                Reserva #<?php echo $multa['id']; ?>
                            </span>
                        </div>

                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                            <?php echo e($multa['estacionamento_nome']); ?>
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                            <i class="fas fa-map-marker-alt mr-1"></i>
                            <?php echo e($multa['estacionamento_endereco']); ?>
                        </p>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">Vaga</p>
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    <?php echo e($multa['numero_vaga']); ?>
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">Veículo</p>
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    <?php echo e(formatarMatricula($multa['matricula'])); ?>
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">Data da Multa</p>
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    <?php echo formatDateTime($multa['data_multa']); ?>
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">Valor da Reserva</p>
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    <?php echo formatCurrency($multa['valor_total']); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 md:mt-0 md:ml-6 text-center md:text-right">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Valor da Multa</p>
                        <p class="text-3xl font-bold text-red-600 dark:text-red-400 mb-3">
                            <?php echo formatCurrency($multa['valor_multa']); ?>
                        </p>
                        <a href="/smartpark/usuario/pagamento.php?reserva=<?php echo $multa['id']; ?>&multa=1" 
                           class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold transition shadow-lg">
                            <i class="fas fa-credit-card mr-2"></i>
                            Pagar Multa
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Multas Pagas (Histórico) -->
    <?php if (!empty($multasPagas)): ?>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
        <div class="bg-gray-100 dark:bg-gray-700 px-6 py-4 border-b border-gray-200 dark:border-gray-600">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-history mr-2"></i>
                Histórico de Multas Pagas (<?php echo count($multasPagas); ?>)
            </h2>
        </div>

        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            <?php foreach ($multasPagas as $multa): ?>
            <div class="p-6 opacity-75 hover:opacity-100 transition">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-3 mb-3">
                            <span class="px-3 py-1 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 text-xs font-semibold rounded-full">
                                <i class="fas fa-check mr-1"></i> PAGA
                            </span>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                Reserva #<?php echo $multa['id']; ?>
                            </span>
                        </div>

                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                            <?php echo e($multa['estacionamento_nome']); ?>
                        </h3>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">Vaga</p>
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    <?php echo e($multa['numero_vaga']); ?>
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">Veículo</p>
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    <?php echo e(formatarMatricula($multa['matricula'])); ?>
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">Data da Multa</p>
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    <?php echo formatDateTime($multa['data_multa']); ?>
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">Valor Pago</p>
                                <p class="font-semibold text-green-600 dark:text-green-400">
                                    <?php echo formatCurrency($multa['valor_multa']); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
