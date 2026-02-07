<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('usuario');

$userId = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle form submission (add new vehicle)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_vehicle'])) {
    $marca = sanitize($_POST['marca'] ?? '');
    $modelo = sanitize($_POST['modelo'] ?? '');
    $matricula = strtoupper(sanitize($_POST['matricula'] ?? ''));
    $cor = sanitize($_POST['cor'] ?? '');
    $proprietario_nome = sanitize($_POST['proprietario_nome'] ?? '');
    $contacto = sanitize($_POST['contacto'] ?? '');
    
    if (empty($marca) || empty($modelo) || empty($matricula)) {
        $error = 'Por favor, preencha a marca, modelo e matrícula.';
    } else {
        // Check if license plate exists
        $stmt = $pdo->prepare("SELECT id FROM veiculos WHERE matricula = ?");
        $stmt->execute([$matricula]);
        
        if ($stmt->fetch()) {
            $error = 'Esta matrícula já está cadastrada no sistema.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO veiculos (usuario_id, marca, modelo, matricula, cor, proprietario_nome, contacto) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$userId, $marca, $modelo, $matricula, $cor, $proprietario_nome, $contacto])) {
                $success = 'Veículo adicionado com sucesso!';
                logAction($userId, "Novo veículo registrado: $matricula");
            } else {
                $error = 'Erro ao adicionar veículo.';
            }
        }
    }
}

// Get user's vehicles
$stmt = $pdo->prepare("SELECT * FROM veiculos WHERE usuario_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$veiculos = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Meus Veículos</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Gerencie as viaturas associadas à sua conta</p>
        </div>
        <button onclick="document.getElementById('modalAddVehicle').classList.remove('hidden')" 
                class="mt-4 md:mt-0 px-6 py-3 bg-primary dark:bg-secondary text-white rounded-lg hover:bg-blue-900 dark:hover:bg-green-600 transition shadow-lg">
            <i class="fas fa-plus mr-2"></i> Adicionar Veículo
        </button>
    </div>

    <?php if ($success): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow" role="alert">
        <div class="flex items-center"><i class="fas fa-check-circle mr-2"></i><p><?php echo e($success); ?></p></div>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow" role="alert">
        <div class="flex items-center"><i class="fas fa-exclamation-circle mr-2"></i><p><?php echo e($error); ?></p></div>
    </div>
    <?php endif; ?>

    <!-- Vehicle List -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($veiculos)): ?>
            <div class="col-span-full bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
                <i class="fas fa-car-side text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                <p class="text-gray-600 dark:text-gray-400">Nenhum veículo encontrado.</p>
            </div>
        <?php else: ?>
            <?php foreach ($veiculos as $veiculo): ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden border border-gray-100 dark:border-gray-700 hover:shadow-xl transition transform hover:-translate-y-1">
                <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 border-b border-gray-100 dark:border-gray-600 flex justify-between items-center">
                    <span class="text-lg font-bold text-primary dark:text-secondary"><?php echo e($veiculo['matricula']); ?></span>
                    <i class="fas fa-car text-gray-400"></i>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Marca/Modelo:</span>
                            <span class="font-medium text-gray-900 dark:text-white"><?php echo e($veiculo['marca']); ?> <?php echo e($veiculo['modelo']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Cor:</span>
                            <span class="font-medium text-gray-900 dark:text-white"><?php echo e($veiculo['cor'] ?: 'Não informada'); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Proprietário:</span>
                            <span class="font-medium text-gray-900 dark:text-white"><?php echo e($veiculo['proprietario_nome'] ?: '-'); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Contacto:</span>
                            <span class="font-medium text-gray-900 dark:text-white"><?php echo e($veiculo['contacto'] ?: '-'); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Registrado em:</span>
                            <span class="text-sm text-gray-600 dark:text-gray-500"><?php echo formatDateTime($veiculo['created_at']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Add Vehicle -->
<div id="modalAddVehicle" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate__animated animate__fadeInUp">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">Novo Veículo</h3>
            <button onclick="document.getElementById('modalAddVehicle').classList.add('hidden')" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="" class="p-6">
            <input type="hidden" name="add_vehicle" value="1">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Marca</label>
                <input type="text" name="marca" required class="w-full px-4 py-2 rounded-lg border dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-primary">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Modelo</label>
                <input type="text" name="modelo" required class="w-full px-4 py-2 rounded-lg border dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-primary">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Matrícula</label>
                <input type="text" name="matricula" required class="w-full px-4 py-2 rounded-lg border dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-primary" placeholder="ABC-123-XY">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Cor (opcional)</label>
                <input type="text" name="cor" class="w-full px-4 py-2 rounded-lg border dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-primary">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Proprietário</label>
                <input type="text" name="proprietario_nome" placeholder="Nome do proprietário" class="w-full px-4 py-2 rounded-lg border dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-primary">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Contacto</label>
                <input type="text" name="contacto" placeholder="Telefone do proprietário" class="w-full px-4 py-2 rounded-lg border dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-primary">
            </div>
            <button type="submit" class="w-full py-3 bg-primary dark:bg-secondary text-white font-bold rounded-lg hover:shadow-lg transition">
                Adicionar Veículo
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
