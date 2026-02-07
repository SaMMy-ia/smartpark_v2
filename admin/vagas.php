<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($skipRoleCheck)) {
    requireRole('admin');
}

// Get estacionamento filter
$estacionamentoFilter = isset($_GET['estacionamento']) ? (int)$_GET['estacionamento'] : null;

// Handle status update
if (isset($_POST['update_status'])) {
    $vagaId = (int)$_POST['vaga_id'];
    $newStatus = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE vagas SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $vagaId]);
    logAction($_SESSION['user_id'], "Status da vaga ID $vagaId atualizado para $newStatus");
    redirectWithMessage($_SERVER['PHP_SELF'], 'Status atualizado com sucesso!');
}

// Handle CREATE/UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['update_status'])) {
    $id = $_POST['id'] ?? null;
    $estacionamento_id = (int)$_POST['estacionamento_id'];
    $numero_vaga = sanitize($_POST['numero_vaga']);
    $status = $_POST['status'];
    $tipo = $_POST['tipo'];
    
    if ($id) {
        $stmt = $pdo->prepare("UPDATE vagas SET estacionamento_id = ?, numero_vaga = ?, status = ?, tipo = ? WHERE id = ?");
        $stmt->execute([$estacionamento_id, $numero_vaga, $status, $tipo, $id]);
        logAction($_SESSION['user_id'], "Vaga ID $id atualizada");
    } else {
        $stmt = $pdo->prepare("INSERT INTO vagas (estacionamento_id, numero_vaga, status, tipo) VALUES (?, ?, ?, ?)");
        $stmt->execute([$estacionamento_id, $numero_vaga, $status, $tipo]);
        logAction($_SESSION['user_id'], "Vaga criada: $numero_vaga");
    }
    redirectWithMessage($_SERVER['PHP_SELF'], 'Vaga salva com sucesso!');
}

// Handle DELETE
// Handle DELETE
if (isset($_GET['delete'])) {
    if (!hasRole('admin')) {
        redirectWithMessage($_SERVER['PHP_SELF'], 'Permissão negada.', 'error');
    }
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM vagas WHERE id = ?");
    $stmt->execute([$id]);
    logAction($_SESSION['user_id'], "Vaga ID $id deletada");
    redirectWithMessage($_SERVER['PHP_SELF'], 'Vaga deletada com sucesso!');
}

// Get estacionamentos for dropdown
$stmt = $pdo->query("SELECT id, nome FROM estacionamentos ORDER BY nome");
$estacionamentos = $stmt->fetchAll();

// Get vagas
$query = "SELECT v.*, e.nome as estacionamento_nome FROM vagas v JOIN estacionamentos e ON v.estacionamento_id = e.id";
if ($estacionamentoFilter) {
    $query .= " WHERE v.estacionamento_id = $estacionamentoFilter";
}
$query .= " ORDER BY e.nome, v.numero_vaga";
$stmt = $pdo->query($query);
$vagas = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-parking mr-2"></i> Gerenciar Vagas
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Gerencie vagas de estacionamento</p>
        </div>
        <button onclick="openModal()" class="bg-primary dark:bg-secondary text-white px-6 py-3 rounded-lg hover:bg-blue-900 dark:hover:bg-green-600 transition shadow-lg">
            <i class="fas fa-plus mr-2"></i> Nova Vaga
        </button>
    </div>
    
    <!-- Filter -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-4 mb-6">
        <form method="GET" class="flex items-center space-x-4">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Filtrar por Estacionamento:</label>
            <select name="estacionamento" onchange="this.form.submit()" class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                <option value="">Todos</option>
                <?php foreach ($estacionamentos as $est): ?>
                <option value="<?php echo $est['id']; ?>" <?php echo $estacionamentoFilter == $est['id'] ? 'selected' : ''; ?>>
                    <?php echo e($est['nome']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    
    <!-- Vagas Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
        <?php foreach ($vagas as $vaga): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-4 text-center transform hover:scale-105 transition cursor-pointer"
             onclick='editVaga(<?php echo json_encode($vaga); ?>)'>
            <div class="text-3xl mb-2">
                <?php if ($vaga['status'] === 'livre'): ?>
                    <i class="fas fa-car text-green-500"></i>
                <?php elseif ($vaga['status'] === 'ocupada'): ?>
                    <i class="fas fa-car text-red-500"></i>
                <?php else: ?>
                    <i class="fas fa-car text-yellow-500"></i>
                <?php endif; ?>
            </div>
            <div class="font-bold text-lg text-gray-900 dark:text-white"><?php echo e($vaga['numero_vaga']); ?></div>
            <div class="text-xs text-gray-600 dark:text-gray-400 mt-1"><?php echo e($vaga['estacionamento_nome']); ?></div>
            <span class="inline-block mt-2 px-2 py-1 text-xs font-semibold rounded-full <?php echo getStatusBadge($vaga['status']); ?>">
                <?php echo ucfirst($vaga['status']); ?>
            </span>
            <span class="inline-block mt-1 px-2 py-1 text-xs font-semibold rounded-full <?php echo getTipoBadge($vaga['tipo']); ?>">
                <?php echo ucfirst($vaga['tipo']); ?>
            </span>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal -->
<div id="vagaModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="modalTitle">Nova Vaga</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="id" id="vaga_id">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Estacionamento</label>
                    <select name="estacionamento_id" id="estacionamento_id" required class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <?php foreach ($estacionamentos as $est): ?>
                        <option value="<?php echo $est['id']; ?>"><?php echo e($est['nome']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Número da Vaga</label>
                    <input type="text" name="numero_vaga" id="numero_vaga" required class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                    <select name="status" id="status" required class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="livre">Livre</option>
                        <option value="ocupada">Ocupada</option>
                        <option value="reservada">Reservada</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tipo</label>
                    <select name="tipo" id="tipo" required class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="normal">Normal</option>
                        <option value="deficiente">Deficiente</option>
                        <option value="eletrico">Elétrico</option>
                    </select>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-primary dark:bg-secondary text-white rounded-lg">Salvar</button>
                <?php if (hasRole('admin')): ?>
                <button type="button" onclick="deleteVaga()" id="deleteBtn" class="hidden px-4 py-2 bg-red-600 text-white rounded-lg">Deletar</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('vagaModal').classList.remove('hidden');
    document.getElementById('modalTitle').textContent = 'Nova Vaga';
    document.getElementById('vaga_id').value = '';
    document.getElementById('numero_vaga').value = '';
    document.getElementById('deleteBtn').classList.add('hidden');
}

function closeModal() {
    document.getElementById('vagaModal').classList.add('hidden');
}

function editVaga(vaga) {
    document.getElementById('vagaModal').classList.remove('hidden');
    document.getElementById('modalTitle').textContent = 'Editar Vaga';
    document.getElementById('vaga_id').value = vaga.id;
    document.getElementById('estacionamento_id').value = vaga.estacionamento_id;
    document.getElementById('numero_vaga').value = vaga.numero_vaga;
    document.getElementById('status').value = vaga.status;
    document.getElementById('tipo').value = vaga.tipo;
    document.getElementById('deleteBtn').classList.remove('hidden');
}

function deleteVaga() {
    const id = document.getElementById('vaga_id').value;
    if (confirm('Tem certeza que deseja deletar esta vaga?')) {
        window.location.href = '?delete=' + id;
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
