<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('usuario');

// Get available vagas
$estacionamentoFilter = isset($_GET['estacionamento']) ? (int)$_GET['estacionamento'] : null;
$tipoFilter = isset($_GET['tipo']) ? $_GET['tipo'] : null;

$query = "SELECT v.*, e.nome as estacionamento_nome, e.endereco, e.preco_hora
          FROM vagas v
          JOIN estacionamentos e ON v.estacionamento_id = e.id
          WHERE v.status = 'livre'";

if ($estacionamentoFilter) {
    $query .= " AND v.estacionamento_id = $estacionamentoFilter";
}
if ($tipoFilter) {
    $query .= " AND v.tipo = '$tipoFilter'";
}

$query .= " ORDER BY e.nome, v.numero_vaga";
$stmt = $pdo->query($query);
$vagasDisponiveis = $stmt->fetchAll();

// Get estacionamentos for filter
$stmt = $pdo->query("SELECT id, nome FROM estacionamentos ORDER BY nome");
$estacionamentos = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            <i class="fas fa-search mr-2"></i> Buscar Vagas Disponíveis
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Encontre a vaga perfeita para você</p>
    </div>
    
    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Estacionamento</label>
                <select name="estacionamento" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">Todos</option>
                    <?php foreach ($estacionamentos as $est): ?>
                    <option value="<?php echo $est['id']; ?>" <?php echo $estacionamentoFilter == $est['id'] ? 'selected' : ''; ?>>
                        <?php echo e($est['nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tipo de Vaga</label>
                <select name="tipo" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">Todos</option>
                    <option value="normal" <?php echo $tipoFilter === 'normal' ? 'selected' : ''; ?>>Normal</option>
                    <option value="deficiente" <?php echo $tipoFilter === 'deficiente' ? 'selected' : ''; ?>>Deficiente</option>
                    <option value="eletrico" <?php echo $tipoFilter === 'eletrico' ? 'selected' : ''; ?>>Elétrico</option>
                </select>
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="w-full px-6 py-2 bg-primary dark:bg-secondary text-white rounded-lg hover:bg-blue-900 dark:hover:bg-green-600 transition">
                    <i class="fas fa-filter mr-2"></i> Filtrar
                </button>
            </div>
        </form>
    </div>
    
    <!-- Results -->
    <div class="mb-4">
        <p class="text-gray-700 dark:text-gray-300">
            <strong><?php echo count($vagasDisponiveis); ?></strong> vagas disponíveis
        </p>
    </div>
    
    <?php if (empty($vagasDisponiveis)): ?>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-12 text-center">
        <i class="fas fa-car-side text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
        <p class="text-gray-600 dark:text-gray-400">Nenhuma vaga disponível com os filtros selecionados.</p>
    </div>
    <?php else: ?>
        <?php
        $temMultas = usuarioTemMultasPendentes($pdo, $_SESSION['user_id']);
        if ($temMultas):
        ?>
        <div class="mb-6 bg-red-100 border-l-4 border-red-600 p-4 rounded shadow-md">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-600 text-xl mr-3"></i>
                <div>
                    <p class="text-red-800 font-bold">Acesso Restrito!</p>
                    <p class="text-red-700 text-sm">Você possui multas pendentes. Por favor, regularize sua situação para poder realizar novas reservas.</p>
                </div>
                <a href="/smartpark/usuario/minhas-multas.php" class="ml-auto px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-semibold hover:bg-red-700 transition">
                    Ver Multas
                </a>
            </div>
        </div>
        <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($vagasDisponiveis as $vaga): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden hover:shadow-xl transform hover:scale-105 transition">
            <div class="bg-gradient-to-r from-primary to-blue-600 dark:from-secondary dark:to-green-600 p-4 text-white">
                <h3 class="font-bold text-lg"><?php echo e($vaga['estacionamento_nome']); ?></h3>
                <p class="text-sm opacity-90"><?php echo e($vaga['endereco']); ?></p>
            </div>
            
            <div class="p-4">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-center">
                        <i class="fas fa-parking text-4xl text-green-500 mb-2"></i>
                        <p class="font-bold text-2xl text-gray-900 dark:text-white"><?php echo e($vaga['numero_vaga']); ?></p>
                    </div>
                    
                    <div class="text-right">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Preço/hora</p>
                        <p class="font-bold text-2xl text-primary dark:text-secondary"><?php echo formatCurrency($vaga['preco_hora']); ?></p>
                    </div>
                </div>
                
                <div class="flex space-x-2 mb-4">
                    <span class="px-3 py-1 text-xs font-semibold rounded-full <?php echo getStatusBadge($vaga['status']); ?>">
                        <?php echo ucfirst($vaga['status']); ?>
                    </span>
                    <span class="px-3 py-1 text-xs font-semibold rounded-full <?php echo getTipoBadge($vaga['tipo']); ?>">
                        <?php echo ucfirst($vaga['tipo']); ?>
                    </span>
                </div>
                
                <?php if ($temMultas): ?>
                <button disabled 
                   class="block w-full text-center px-4 py-3 bg-gray-400 text-white rounded-lg font-semibold cursor-not-allowed opacity-75"
                   title="Regularize suas multas para reservar">
                    <i class="fas fa-lock mr-2"></i> Bloqueado
                </button>
                <?php else: ?>
                <a href="/smartpark/usuario/reservar.php?vaga=<?php echo $vaga['id']; ?>" 
                   class="block w-full text-center px-4 py-3 bg-primary dark:bg-secondary text-white rounded-lg font-semibold hover:bg-blue-900 dark:hover:bg-green-600 transition">
                    <i class="fas fa-calendar-plus mr-2"></i> Reservar Agora
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
