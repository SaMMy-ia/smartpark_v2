<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isAccountant()) {
    header('Location: /smartpark/403.php');
    exit;
}

$isSenior = isSeniorAccountant();
$isPreliminar = isset($_GET['preliminar']);

// Filters
$dataInicio = $_GET['data_inicio'] ?? date('Y-m-01');
$dataFim = $_GET['data_fim'] ?? date('Y-m-d');

// Financial Report query
$stmtFin = $pdo->prepare("
    SELECT p.*, r.matricula, u.nome as usuario_nome
    FROM pagamentos p
    JOIN reservas r ON p.reserva_id = r.id
    JOIN usuarios u ON r.usuario_id = u.id
    WHERE p.status = 'pago' 
    AND DATE(p.data_pagamento) BETWEEN ? AND ?
    ORDER BY p.data_pagamento DESC
");
$stmtFin->execute([$dataInicio, $dataFim]);
$financialData = $stmtFin->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" id="reportContent">
    <div class="mb-8 flex justify-between items-end no-print">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Relatórios Financeiros</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Período: <?php echo date('d/m/Y', strtotime($dataInicio)); ?> a <?php echo date('d/m/Y', strtotime($dataFim)); ?></p>
        </div>
        
        <form class="flex space-x-2 items-end">
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">De</label>
                <input type="date" name="data_inicio" value="<?php echo $dataInicio; ?>" class="rounded border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Até</label>
                <input type="date" name="data_fim" value="<?php echo $dataFim; ?>" class="rounded border-gray-300 text-sm">
            </div>
            <button type="submit" class="bg-primary text-white px-4 py-2 rounded text-sm font-bold">Filtrar</button>
            <button onclick="window.print()" type="button" class="bg-green-600 text-white px-4 py-2 rounded text-sm font-bold">
                <i class="fas fa-print"></i>
            </button>
        </form>
    </div>

    <!-- Official Header for Print -->
    <div class="print-only mb-10 text-center border-b-2 border-gray-200 pb-6">
        <h1 class="text-2xl font-bold uppercase">SmartPark - Relatório Oficial de Contabilidade</h1>
        <p class="text-sm"><?php echo $isPreliminar ? 'DOCUMENTO PRELIMINAR - SEM VALOR FISCAL' : 'RELATÓRIO VALIDADO E OFICIAL'; ?></p>
        <p class="text-xs mt-2">Gerado em: <?php echo date('d/m/Y H:i'); ?> por <?php echo e($currentUser['nome']); ?></p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr class="text-left text-xs font-bold text-gray-500 uppercase">
                    <th class="px-6 py-3">Data</th>
                    <th class="px-6 py-3">Usuario/Matrícula</th>
                    <th class="px-6 py-3">Método</th>
                    <th class="px-6 py-3">Descrição</th>
                    <th class="px-6 py-3 text-right">Valor</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <?php 
                $total = 0;
                foreach ($financialData as $row): 
                    $total += $row['valor'];
                ?>
                <tr class="text-sm">
                    <td class="px-6 py-4 whitespace-nowrap"><?php echo date('d/m/Y', strtotime($row['data_pagamento'])); ?></td>
                    <td class="px-6 py-4">
                        <p class="font-bold"><?php echo e($row['usuario_nome']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo e($row['matricula']); ?></p>
                    </td>
                    <td class="px-6 py-4 uppercase text-xs"><?php echo e($row['metodo']); ?></td>
                    <td class="px-6 py-4 text-gray-600"><?php echo e($row['descricao'] ?: 'Reserva #'.$row['reserva_id']); ?></td>
                    <td class="px-6 py-4 text-right font-bold"><?php echo formatCurrency($row['valor']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <td colspan="4" class="px-6 py-4 text-right font-bold uppercase">Total Arrecadado</td>
                    <td class="px-6 py-4 text-right font-black text-xl text-primary dark:text-secondary">
                        <?php echo formatCurrency($total); ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Signature required for official reports -->
    <?php if (!$isPreliminar): ?>
    <div class="print-only mt-20 flex justify-around">
        <div class="text-center border-t border-black w-64 pt-2">
            <p class="text-xs font-bold uppercase">Assinatura Contabilista Sénior</p>
            <p class="text-xs"><?php echo e($currentUser['nome']); ?></p>
        </div>
        <div class="text-center border-t border-black w-64 pt-2">
            <p class="text-xs font-bold uppercase">Carimbo Oficial</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
@media print {
    .no-print { display: none !important; }
    .print-only { display: block !important; }
    body { background: white !important; }
    .shadow { shadow: none !important; }
    #reportContent { width: 100% !important; max-width: 100% !important; }
}
.print-only { display: none; }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
