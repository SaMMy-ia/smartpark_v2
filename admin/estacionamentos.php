<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($skipRoleCheck)) {
    requireRole('admin');
}

// Handle DELETE
// Handle DELETE
if (isset($_GET['delete'])) {
    if (!hasRole('admin')) {
        redirectWithMessage($_SERVER['PHP_SELF'], 'Permissão negada.', 'error');
    }
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM estacionamentos WHERE id = ?");
    if ($stmt->execute([$id])) {
        logAction($_SESSION['user_id'], "Estacionamento ID $id deletado");
        redirectWithMessage($_SERVER['PHP_SELF'], 'Estacionamento deletado com sucesso!');
    }
}

// Handle CREATE/UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $nome = sanitize($_POST['nome']);
    $endereco = sanitize($_POST['endereco']);
    $capacidade_total = (int)$_POST['capacidade_total'];
    $vagas_disponiveis = (int)$_POST['vagas_disponiveis'];
    $preco_hora = (float)$_POST['preco_hora'];
    
    if ($id) {
        // UPDATE
        $stmt = $pdo->prepare("UPDATE estacionamentos SET nome = ?, endereco = ?, capacidade_total = ?, vagas_disponiveis = ?, preco_hora = ? WHERE id = ?");
        $stmt->execute([$nome, $endereco, $capacidade_total, $vagas_disponiveis, $preco_hora, $id]);
        logAction($_SESSION['user_id'], "Estacionamento ID $id atualizado");
        redirectWithMessage($_SERVER['PHP_SELF'], 'Estacionamento atualizado com sucesso!');
    } else {
        // CREATE
        $stmt = $pdo->prepare("INSERT INTO estacionamentos (nome, endereco, capacidade_total, vagas_disponiveis, preco_hora) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $endereco, $capacidade_total, $vagas_disponiveis, $preco_hora]);
        logAction($_SESSION['user_id'], "Estacionamento criado: $nome");
        redirectWithMessage($_SERVER['PHP_SELF'], 'Estacionamento criado com sucesso!');
    }
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Get total count
$stmt = $pdo->query("SELECT COUNT(*) as total FROM estacionamentos");
$totalEstacionamentos = $stmt->fetch()['total'];
$totalPages = ceil($totalEstacionamentos / $perPage);

// Get estacionamentos
$stmt = $pdo->prepare("SELECT * FROM estacionamentos ORDER BY id DESC LIMIT ? OFFSET ?");
$stmt->execute([$perPage, $offset]);
$estacionamentos = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-building mr-2"></i> Gerenciar Estacionamentos
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Adicione, edite ou remova estacionamentos</p>
        </div>
        <button onclick="openModal()" class="bg-primary dark:bg-secondary text-white px-6 py-3 rounded-lg hover:bg-blue-900 dark:hover:bg-green-600 transition shadow-lg">
            <i class="fas fa-plus mr-2"></i> Novo Estacionamento
        </button>
    </div>
    
    <!-- Estacionamentos Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <input type="text" 
                   id="searchInput" 
                   onkeyup="filterTable('searchInput', 'estacionamentosTable')"
                   placeholder="Buscar estacionamentos..." 
                   class="w-full md:w-96 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary dark:focus:ring-secondary">
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="estacionamentosTable">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nome</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Endereço</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Capacidade</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Disponíveis</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Preço/Hora</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($estacionamentos as $est): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">#<?php echo $est['id']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100"><?php echo e($est['nome']); ?></td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100"><?php echo e($est['endereco']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"><?php echo $est['capacidade_total']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                <?php echo $est['vagas_disponiveis']; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-100"><?php echo formatCurrency($est['preco_hora']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <button onclick='editEstacionamento(<?php echo json_encode($est); ?>)' class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-3">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if (hasRole('admin')): ?>
                            <a href="?delete=<?php echo $est['id']; ?>" onclick="return confirmDelete('Tem certeza que deseja deletar este estacionamento?')" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                <i class="fas fa-trash"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 flex items-center justify-between border-t border-gray-200 dark:border-gray-600">
            <div class="flex-1 flex justify-between sm:hidden">
                <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Anterior</a>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Próximo</a>
                <?php endif; ?>
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        Mostrando <span class="font-medium"><?php echo $offset + 1; ?></span> a <span class="font-medium"><?php echo min($offset + $perPage, $totalEstacionamentos); ?></span> de <span class="font-medium"><?php echo $totalEstacionamentos; ?></span> resultados
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="<?php echo $i === $page ? 'bg-primary text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'; ?> relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>
                    </nav>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal -->
<div id="estacionamentoModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" x-data="{ show: false }">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="modalTitle">Novo Estacionamento</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="id" id="estacionamento_id">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nome</label>
                    <input type="text" name="nome" id="nome" required class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Endereço</label>
                    <textarea name="endereco" id="endereco" required rows="2" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Capacidade Total</label>
                    <input type="number" name="capacidade_total" id="capacidade_total" required min="1" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Vagas Disponíveis</label>
                    <input type="number" name="vagas_disponiveis" id="vagas_disponiveis" required min="0" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Preço por Hora (MZN)</label>
                    <input type="number" name="preco_hora" id="preco_hora" required min="0" step="0.01" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary">
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-400 dark:hover:bg-gray-500 transition">
                    Cancelar
                </button>
                <button type="submit" class="px-4 py-2 bg-primary dark:bg-secondary text-white rounded-lg hover:bg-blue-900 dark:hover:bg-green-600 transition">
                    Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('estacionamentoModal').classList.remove('hidden');
    document.getElementById('modalTitle').textContent = 'Novo Estacionamento';
    document.getElementById('estacionamento_id').value = '';
    document.getElementById('nome').value = '';
    document.getElementById('endereco').value = '';
    document.getElementById('capacidade_total').value = '';
    document.getElementById('vagas_disponiveis').value = '';
    document.getElementById('preco_hora').value = '';
}

function closeModal() {
    document.getElementById('estacionamentoModal').classList.add('hidden');
}

function editEstacionamento(est) {
    document.getElementById('estacionamentoModal').classList.remove('hidden');
    document.getElementById('modalTitle').textContent = 'Editar Estacionamento';
    document.getElementById('estacionamento_id').value = est.id;
    document.getElementById('nome').value = est.nome;
    document.getElementById('endereco').value = est.endereco;
    document.getElementById('capacidade_total').value = est.capacidade_total;
    document.getElementById('vagas_disponiveis').value = est.vagas_disponiveis;
    document.getElementById('preco_hora').value = est.preco_hora;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
