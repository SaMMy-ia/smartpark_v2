<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Verify it's an accountant
if (!isAccountant()) {
    header('Location: /smartpark/403.php');
    exit;
}

$isSenior = isSeniorAccountant();

// Statistics for Accountant
$stmt = $pdo->query("SELECT SUM(valor) as total FROM pagamentos WHERE status = 'pago'");
$receitaTotal = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->query("SELECT SUM(valor_multa) as total FROM multas WHERE resolvida = 1");
$multasPagas = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->query("SELECT COUNT(*) as total FROM reservas WHERE status = 'concluida'");
$reservasConcluidas = $stmt->fetch()['total'];

// Get pending "confirmation" data (simulated for now, or could be a new table/status)
// Request: "Confirmar, validar e aprovar todos os dados inseridos pelo estagiário"
// We'll assume a 'pendente_aprovacao' flag in pagamentos or reserves for accounting purposes.

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-calculator mr-2 text-primary dark:text-secondary"></i> Painel Contabilista
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">
                Nível: <span class="font-bold <?php echo $isSenior ? 'text-red-600' : 'text-blue-600'; ?>">
                    <?php echo $isSenior ? 'Sénior' : 'Estagiário'; ?>
                </span>
            </p>
        </div>

        <div class="flex space-x-3">
            <a href="relatorios.php"
                class="px-6 py-3 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg shadow hover:shadow-lg transition border border-gray-200 dark:border-gray-700">
                <i class="fas fa-file-invoice mr-2"></i> Relatórios
            </a>
            <?php if ($isSenior): ?>
                <a href="gerar-pdf.php"
                    class="px-6 py-3 bg-red-600 text-white rounded-lg shadow hover:bg-red-700 transition">
                    <i class="fas fa-file-pdf mr-2"></i> Gerar PDF Oficial
                </a>
            <?php else: ?>
                <a href="relatorios.php?preliminar=1"
                    class="px-6 py-3 bg-gray-500 text-white rounded-lg shadow hover:bg-gray-600 transition">
                    <i class="fas fa-file-alt mr-2"></i> Report Preliminar
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div
            class="bg-gradient-to-br from-violet-500 to-violet-600 rounded-lg shadow-lg p-6 text-white transform hover:scale-105 transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90 mb-1">Balanço Geral (Receita)</p>
                    <p class="text-3xl font-bold"><?php echo formatCurrency($receitaTotal); ?></p>
                </div>
                <div class="bg-white bg-opacity-20 p-4 rounded-full">
                    <i class="fas fa-money-bill-wave text-2xl"></i>
                </div>
            </div>
        </div>

        <div
            class="bg-gradient-to-br from-rose-500 to-rose-600 rounded-lg shadow-lg p-6 text-white transform hover:scale-105 transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90 mb-1">Multas Arrecadadas</p>
                    <p class="text-3xl font-bold"><?php echo formatCurrency($multasPagas); ?></p>
                </div>
                <div class="bg-white bg-opacity-20 p-4 rounded-full">
                    <i class="fas fa-exclamation-triangle text-2xl"></i>
                </div>
            </div>
        </div>

        <div
            class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white transform hover:scale-105 transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90 mb-1">Reservas Concluídas</p>
                    <p class="text-3xl font-bold"><?php echo $reservasConcluidas; ?></p>
                </div>
                <div class="bg-white bg-opacity-20 p-4 rounded-full">
                    <i class="fas fa-calendar-check text-2xl"></i>
                </div>
            </div>
        </div>
    </div>


    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- History / Recent Flows -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                <h2 class="font-bold text-gray-900 dark:text-white">Fluxo de Caixa Recente</h2>
                <a href="relatorios.php" class="text-xs text-blue-600 hover:underline">Ver tudo</a>
            </div>
            <div class="p-0">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr class="text-xs text-gray-500 uppercase text-left">
                            <th class="px-6 py-3">Data</th>
                            <th class="px-6 py-3">Descrição</th>
                            <th class="px-6 py-3">Valor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <!-- Simulated / Real data -->
                        <?php
                        $stmt = $pdo->query("SELECT * FROM pagamentos WHERE status = 'pago' ORDER BY data_pagamento DESC LIMIT 5");
                        while ($row = $stmt->fetch()):
                            ?>
                            <tr class="text-sm">
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                                    <?php echo date('d/m/Y', strtotime($row['data_pagamento'])); ?></td>
                                <td class="px-6 py-4 text-gray-900 dark:text-white">
                                    <?php echo $row['descricao'] ?: 'Reserva #' . $row['reserva_id']; ?></td>
                                <td class="px-6 py-4 font-bold text-green-600"><?php echo formatCurrency($row['valor']); ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Senior Actions / Pending Validations -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="font-bold text-gray-900 dark:text-white mb-4">Ações de Contabilidade</h2>

            <div class="space-y-4">
                <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600">
                    <p class="font-bold text-sm mb-2 text-gray-900 dark:text-white">Inserir Novo Lançamento</p>
                    <p class="text-xs text-gray-500 mb-4">Adicione despesas ou receitas manuais ao balanço.</p>
                    <button
                        class="w-full py-2 bg-primary dark:bg-secondary text-white rounded text-sm hover:opacity-90 transition">
                        <i class="fas fa-plus mr-1"></i> Inserir Dados
                    </button>
                </div>

                <?php if ($isSenior): ?>
                    <div class="p-4 rounded-lg bg-red-50 dark:bg-red-900/10 border border-red-100 dark:border-red-900">
                        <p class="font-bold text-sm mb-2 text-red-800 dark:text-red-400">Validação Pendente (Sénior)</p>
                        <p class="text-xs text-red-600 dark:text-red-500 mb-4">Existem 3 lançamentos inseridos por
                            estagiários que aguardam sua aprovação.</p>
                        <button class="w-full py-2 bg-red-600 text-white rounded text-sm hover:bg-red-700 transition">
                            <i class="fas fa-check-double mr-1"></i> Validar e Aprovar Todos
                        </button>
                    </div>
                <?php else: ?>
                    <div class="p-4 rounded-lg bg-blue-50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-900">
                        <p class="font-bold text-sm mb-2 text-blue-800 dark:text-blue-400">Status de Estágio</p>
                        <p class="text-xs text-blue-600 dark:text-blue-500">Seus lançamentos serão revisados por um
                            Contabilista Sénior antes de serem oficializados nos relatórios finais.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>