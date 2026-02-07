<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/multas.php';
requireRole('admin');

// Verificar multas automáticas
verificarMultasAutomaticas($pdo);

$searchTerm = '';
$resultados = [];
$situacaoVeiculo = null;

if (isset($_GET['matricula']) && !empty($_GET['matricula'])) {
    $searchTerm = sanitize($_GET['matricula']);
    $resultados = buscarPorMatricula($pdo, $searchTerm);
    $situacaoVeiculo = verificarSituacaoVeiculo($pdo, $searchTerm);
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            <i class="fas fa-search mr-2"></i> Pesquisa por Matrícula
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Busque veículos por matrícula e verifique seu status</p>
    </div>
    
    <!-- Search Form -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6">
        <form method="GET" action="" class="flex gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-id-card mr-1"></i> Matrícula do Veículo
                </label>
                <input type="text" 
                       name="matricula" 
                       value="<?php echo e($searchTerm); ?>"
                       placeholder="Ex: ABC-1234"
                       class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary uppercase"
                       autofocus>
            </div>
            <div class="flex items-end">
                <button type="submit" 
                        class="px-6 py-2 bg-primary dark:bg-secondary text-white rounded-lg font-semibold hover:bg-blue-900 dark:hover:bg-green-600 transition">
                    <i class="fas fa-search mr-2"></i> Buscar
                </button>
            </div>
        </form>
    </div>
    
    <?php if ($searchTerm): ?>
        <!-- Vehicle Status -->
        <?php if ($situacaoVeiculo): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-info-circle mr-2"></i> Situação Atual
            </h2>
            
            <div class="p-4 rounded-lg <?php 
                echo $situacaoVeiculo['status'] === 'em_multa' ? 'bg-red-100 dark:bg-red-900' : 
                     ($situacaoVeiculo['status'] === 'estacionado' ? 'bg-green-100 dark:bg-green-900' : 
                      'bg-gray-100 dark:bg-gray-700'); 
            ?>">
                <p class="text-lg font-semibold <?php 
                    echo $situacaoVeiculo['status'] === 'em_multa' ? 'text-red-800 dark:text-red-200' : 
                         ($situacaoVeiculo['status'] === 'estacionado' ? 'text-green-800 dark:text-green-200' : 
                          'text-gray-800 dark:text-gray-200'); 
                ?>">
                    <i class="fas fa-<?php 
                        echo $situacaoVeiculo['status'] === 'em_multa' ? 'exclamation-triangle' : 
                             ($situacaoVeiculo['status'] === 'estacionado' ? 'check-circle' : 'info-circle'); 
                    ?> mr-2"></i>
                    <?php echo e($situacaoVeiculo['mensagem']); ?>
                </p>
                
                <?php if ($situacaoVeiculo['reserva']): ?>
                <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <p class="text-gray-600 dark:text-gray-400">Proprietário:</p>
                        <p class="font-semibold"><?php echo e($situacaoVeiculo['reserva']['proprietario']); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600 dark:text-gray-400">Contacto:</p>
                        <p class="font-semibold"><?php echo e($situacaoVeiculo['reserva']['contacto']); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600 dark:text-gray-400">Marca:</p>
                        <p class="font-semibold"><?php echo e($situacaoVeiculo['reserva']['marca_veiculo']); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600 dark:text-gray-400">Período:</p>
                        <p class="font-semibold text-xs">
                            <?php echo formatDateTime($situacaoVeiculo['reserva']['data_inicio']); ?><br>
                            até <?php echo formatDateTime($situacaoVeiculo['reserva']['data_fim']); ?>
                        </p>
                    </div>
                </div>
                
                <?php if ($situacaoVeiculo['status'] === 'em_multa'): ?>
                <div class="mt-4 p-3 bg-white dark:bg-gray-800 rounded">
                    <p class="text-sm font-semibold text-red-600 dark:text-red-400">
                        Valor da Multa: <?php echo formatCurrency($situacaoVeiculo['reserva']['valor_multa']); ?>
                    </p>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                        Multa aplicada em: <?php echo formatDateTime($situacaoVeiculo['reserva']['data_multa']); ?>
                    </p>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Search Results -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-history mr-2"></i> Histórico de Reservas
                    <?php if (count($resultados) > 0): ?>
                        <span class="text-sm font-normal text-gray-600 dark:text-gray-400">
                            (<?php echo count($resultados); ?> encontrada<?php echo count($resultados) > 1 ? 's' : ''; ?>)
                        </span>
                    <?php endif; ?>
                </h2>
            </div>
            
            <?php if (empty($resultados)): ?>
            <div class="p-8 text-center">
                <i class="fas fa-inbox text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                <p class="text-gray-600 dark:text-gray-400">Nenhuma reserva encontrada para esta matrícula.</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
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
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($resultados as $reserva): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                #<?php echo $reserva['id']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-100">
                                <?php echo e($reserva['numero_vaga']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                <div>
                                    <p class="font-semibold"><?php echo e($reserva['marca_veiculo']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo e(formatarMatricula($reserva['matricula'])); ?></p>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                <?php echo e($reserva['proprietario']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                <?php echo e($reserva['contacto']); ?>
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
                                    Multa: <?php echo formatCurrency($reserva['valor_multa']); ?>
                                </p>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-100">
                                <?php echo formatCurrency($reserva['valor_total']); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
