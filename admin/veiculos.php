<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

$success = '';
$error = '';

// Handle vehicle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_vehicle'])) {
    $vehicleId = (int) $_POST['vehicle_id'];
    $userId = (int) $_POST['usuario_id']; // For owner association correction
    $marca = sanitize($_POST['marca'] ?? '');
    $modelo = sanitize($_POST['modelo'] ?? '');
    $matricula = strtoupper(sanitize($_POST['matricula'] ?? ''));
    $cor = sanitize($_POST['cor'] ?? '');
    $proprietario_nome = sanitize($_POST['proprietario_nome'] ?? '');
    $contacto = sanitize($_POST['contacto'] ?? '');

    if (empty($marca) || empty($modelo) || empty($matricula)) {
        $error = 'Por favor, preencha a marca, modelo e matrícula.';
    } else {
        // Check if license plate exists for OTHER vehicles
        $stmt = $pdo->prepare("SELECT id FROM veiculos WHERE matricula = ? AND id != ?");
        $stmt->execute([$matricula, $vehicleId]);

        if ($stmt->fetch()) {
            $error = 'Esta matrícula já está em uso por outro veículo.';
        } else {
            $stmt = $pdo->prepare("UPDATE veiculos SET usuario_id = ?, marca = ?, modelo = ?, matricula = ?, cor = ?, proprietario_nome = ?, contacto = ? WHERE id = ?");
            if ($stmt->execute([$userId, $marca, $modelo, $matricula, $cor, $proprietario_nome, $contacto, $vehicleId])) {
                $success = 'Veículo atualizado com sucesso!';
                logAction($_SESSION['user_id'], "Admin atualizou veículo: $matricula (ID: $vehicleId)");
            } else {
                $error = 'Erro ao atualizar veículo.';
            }
        }
    }
}

// Get all vehicles with owner info
$stmt = $pdo->query("
    SELECT v.*, u.nome as dono_original, u.email as dono_email 
    FROM veiculos v 
    JOIN usuarios u ON v.usuario_id = u.id 
    ORDER BY v.created_at DESC
");
$allVeiculos = $stmt->fetchAll();

// Get all users for association dropdown
$stmt = $pdo->query("SELECT id, nome, email FROM usuarios ORDER BY nome");
$usuariosList = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Gerenciamento Geral de Veículos</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Visualize e edite todas as viaturas registradas no sistema
            </p>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow" role="alert">
            <div class="flex items-center"><i class="fas fa-check-circle mr-2"></i>
                <p>
                    <?php echo e($success); ?>
                </p>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow" role="alert">
            <div class="flex items-center"><i class="fas fa-exclamation-circle mr-2"></i>
                <p>
                    <?php echo e($error); ?>
                </p>
            </div>
        </div>
    <?php endif; ?>

    <div
        class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden border border-gray-100 dark:border-gray-700">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Matrícula</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Veículo</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Proprietário (Conta)</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Proprietário (Doc)</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Contacto</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (empty($allVeiculos)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">Nenhum veículo cadastrado.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($allVeiculos as $v): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-primary dark:text-secondary">
                                    <?php echo e($v['matricula']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <?php echo e($v['marca']); ?>
                                    <?php echo e($v['modelo']); ?> <br>
                                    <span class="text-xs text-gray-500">
                                        <?php echo e($v['cor']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <?php echo e($v['dono_original']); ?> <br>
                                    <span class="text-xs text-gray-500">
                                        <?php echo e($v['dono_email']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <?php echo e($v['proprietario_nome'] ?: '-'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <?php echo e($v['contacto'] ?: '-'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($v)); ?>)"
                                        class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden p-4">
    <div
        class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden animate__animated animate__fadeInUp">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
            <h3 class="text-xl font-bold">Editar Veículo</h3>
            <button onclick="document.getElementById('editModal').classList.add('hidden')"
                class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="" class="p-6">
            <input type="hidden" name="update_vehicle" value="1">
            <input type="hidden" name="vehicle_id" id="edit_vehicle_id">

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Matrícula</label>
                    <input type="text" name="matricula" id="edit_matricula" required
                        class="w-full px-4 py-2 rounded-lg border dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Cor</label>
                    <input type="text" name="cor" id="edit_cor"
                        class="w-full px-4 py-2 rounded-lg border dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Marca</label>
                    <input type="text" name="marca" id="edit_marca" required
                        class="w-full px-4 py-2 rounded-lg border dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Modelo</label>
                    <input type="text" name="modelo" id="edit_modelo" required
                        class="w-full px-4 py-2 rounded-lg border dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Associar a Usuário (Dono da Conta)</label>
                <select name="usuario_id" id="edit_usuario_id"
                    class="w-full px-4 py-2 rounded-lg border dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <?php foreach ($usuariosList as $user): ?>
                        <option value="<?php echo $user['id']; ?>">
                            <?php echo e($user['nome']); ?> (
                            <?php echo e($user['email']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="text-xs text-gray-500 mt-1">Isso mudará a qual usuário este veículo pertence.</p>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium mb-1">Nome no Documento</label>
                    <input type="text" name="proprietario_nome" id="edit_proprietario_nome"
                        class="w-full px-4 py-2 rounded-lg border dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Contacto no Documento</label>
                    <input type="text" name="contacto" id="edit_contacto"
                        class="w-full px-4 py-2 rounded-lg border dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
            </div>

            <button type="submit"
                class="w-full py-3 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 transition shadow-lg">
                Salvar Alterações
            </button>
        </form>
    </div>
</div>

<script>
    function openEditModal(v) {
        document.getElementById('edit_vehicle_id').value = v.id;
        document.getElementById('edit_matricula').value = v.matricula;
        document.getElementById('edit_marca').value = v.marca;
        document.getElementById('edit_modelo').value = v.modelo;
        document.getElementById('edit_cor').value = v.cor;
        document.getElementById('edit_usuario_id').value = v.usuario_id;
        document.getElementById('edit_proprietario_nome').value = v.proprietario_nome || '';
        document.getElementById('edit_contacto').value = v.contacto || '';

        document.getElementById('editModal').classList.remove('hidden');
    }

    // Close modal when clicking outside
    document.getElementById('editModal').addEventListener('click', function (e) {
        if (e.target === this) {
            this.classList.add('hidden');
        }
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>