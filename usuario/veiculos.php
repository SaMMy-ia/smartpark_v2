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

// Handle vehicle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_vehicle'])) {
    $vehicleId = (int) $_POST['vehicle_id'];
    $marca = sanitize($_POST['marca'] ?? '');
    $modelo = sanitize($_POST['modelo'] ?? '');
    $matricula = strtoupper(sanitize($_POST['matricula'] ?? ''));
    $cor = sanitize($_POST['cor'] ?? '');
    $proprietario_nome = sanitize($_POST['proprietario_nome'] ?? '');
    $contacto = sanitize($_POST['contacto'] ?? '');

    // Check if user is admin or has permission (pode_editar)
    $stmt = $pdo->prepare("SELECT pode_editar FROM veiculos WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$vehicleId, $_SESSION['user_id']]);
    $veh = $stmt->fetch();

    if (hasRole('admin') || ($veh && $veh['pode_editar'])) {
        if (empty($marca) || empty($modelo) || empty($matricula)) {
            $error = 'Por favor, preencha a marca, modelo e matrícula.';
        } else {
            // Check if license plate exists for OTHER vehicles
            $stmt = $pdo->prepare("SELECT id FROM veiculos WHERE matricula = ? AND id != ?");
            $stmt->execute([$matricula, $vehicleId]);

            if ($stmt->fetch()) {
                $error = 'Esta matrícula já está em uso por outro veículo.';
            } else {
                $stmt = $pdo->prepare("UPDATE veiculos SET marca = ?, modelo = ?, matricula = ?, cor = ?, proprietario_nome = ?, contacto = ?, pode_editar = 0 WHERE id = ?");
                if ($stmt->execute([$marca, $modelo, $matricula, $cor, $proprietario_nome, $contacto, $vehicleId])) {
                    $success = 'Veículo atualizado com sucesso!';
                    logAction($_SESSION['user_id'], "Veículo atualizado: $matricula (ID: $vehicleId)");

                    // Also mark the request as completed
                    $stmt = $pdo->prepare("DELETE FROM solicitacoes_veiculos WHERE veiculo_id = ? AND status = 'pendente'");
                    $stmt->execute([$vehicleId]);
                } else {
                    $error = 'Erro ao atualizar veículo.';
                }
            }
        }
    } else {
        $error = "Você não tem permissão para editar este veículo. Solicite autorização primeiro.";
    }
}

// Handle vehicle request (Edit or Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_action'])) {
    $vehicleId = (int) $_POST['vehicle_id'];
    $tipo = sanitize($_POST['request_type'] ?? ''); // 'editar' or 'eliminar'

    if (in_array($tipo, ['editar', 'eliminar'])) {
        // Check if there is already a pending request for this vehicle
        $stmt = $pdo->prepare("SELECT id FROM solicitacoes_veiculos WHERE veiculo_id = ? AND status = 'pendente'");
        $stmt->execute([$vehicleId]);

        if ($stmt->fetch()) {
            $error = "Já existe uma solicitação pendente para este veículo.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO solicitacoes_veiculos (usuario_id, veiculo_id, tipo) VALUES (?, ?, ?)");
            if ($stmt->execute([$_SESSION['user_id'], $vehicleId, $tipo])) {
                $success = "Solicitação de " . ($tipo === 'editar' ? 'edição' : 'eliminação') . " enviada com sucesso!";
                logAction($_SESSION['user_id'], "Solicitação de $tipo enviada para veículo ID $vehicleId");
            } else {
                $error = "Erro ao enviar solicitação.";
            }
        }
    }
}

// Handle request cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_request'])) {
    $requestId = (int) $_POST['request_id'];
    $stmt = $pdo->prepare("DELETE FROM solicitacoes_veiculos WHERE id = ? AND usuario_id = ? AND status = 'pendente'");
    if ($stmt->execute([$requestId, $_SESSION['user_id']])) {
        $success = "Solicitação cancelada com sucesso!";
    } else {
        $error = "Erro ao cancelar solicitação.";
    }
}

// Handle vehicle deletion (Only if permitted or Admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_vehicle'])) {
    $vehicleId = (int) $_POST['vehicle_id'];

    // Check if user is admin or if the vehicle is marked as deletable by the user
    $stmt = $pdo->prepare("SELECT id, pode_eliminar FROM veiculos WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$vehicleId, $_SESSION['user_id']]);
    $veh = $stmt->fetch();

    if (hasRole('admin') || ($veh && $veh['pode_eliminar'])) {
        $stmt = $pdo->prepare("DELETE FROM veiculos WHERE id = ?");
        if ($stmt->execute([$vehicleId])) {
            $success = "Veículo removido com sucesso!";
            logAction($_SESSION['user_id'], "Veículo removido: ID $vehicleId");
        } else {
            $error = "Erro ao remover veículo.";
        }
    } else {
        $error = "Você não tem permissão para remover este veículo diretamente. Solicite autorização.";
    }
}


// Get user's vehicles with pending requests status
$stmt = $pdo->prepare("
    SELECT v.*, sv.id as request_id, sv.tipo as request_type, sv.status as request_status
    FROM veiculos v
    LEFT JOIN solicitacoes_veiculos sv ON v.id = sv.veiculo_id AND sv.status = 'pendente'
    WHERE v.usuario_id = ? 
    ORDER BY v.created_at DESC
");
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
            <div class="flex items-center"><i class="fas fa-check-circle mr-2"></i>
                <p><?php echo e($success); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow" role="alert">
            <div class="flex items-center"><i class="fas fa-exclamation-circle mr-2"></i>
                <p><?php echo e($error); ?></p>
            </div>
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
                <div
                    class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden border border-gray-100 dark:border-gray-700 hover:shadow-xl transition transform hover:-translate-y-1">
                    <div
                        class="bg-gray-50 dark:bg-gray-700 px-6 py-4 border-b border-gray-100 dark:border-gray-600 flex justify-between items-center">
                        <span
                            class="text-lg font-bold text-primary dark:text-secondary"><?php echo e($veiculo['matricula']); ?></span>
                        <i class="fas fa-car text-gray-400"></i>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Marca/Modelo:</span>
                                <span class="font-medium text-gray-900 dark:text-white"><?php echo e($veiculo['marca']); ?>
                                    <?php echo e($veiculo['modelo']); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Cor:</span>
                                <span
                                    class="font-medium text-gray-900 dark:text-white"><?php echo e($veiculo['cor'] ?: 'Não informada'); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Proprietário:</span>
                                <span
                                    class="font-medium text-gray-900 dark:text-white"><?php echo e($veiculo['proprietario_nome'] ?: '-'); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Contacto:</span>
                                <span
                                    class="font-medium text-gray-900 dark:text-white"><?php echo e($veiculo['contacto'] ?: '-'); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Registrado em:</span>
                                <span
                                    class="text-sm text-gray-600 dark:text-gray-500"><?php echo formatDateTime($veiculo['created_at']); ?></span>
                            </div>
                        </div>

                        <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700">
                            <?php if ($veiculo['request_status'] === 'pendente'): ?>
                                <div
                                    class="bg-yellow-50 dark:bg-yellow-900/20 p-3 rounded-lg border border-yellow-200 dark:border-yellow-800 mb-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <i class="fas fa-hourglass-half text-yellow-600 dark:text-yellow-400 mr-2"></i>
                                            <span class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                                Pedido de <?php echo $veiculo['request_type']; ?> pendente
                                            </span>
                                        </div>
                                        <form method="POST" action="" class="inline">
                                            <input type="hidden" name="request_id" value="<?php echo $veiculo['request_id']; ?>">
                                            <button type="submit" name="cancel_request"
                                                class="text-xs text-red-600 hover:text-red-800 underline">
                                                Cancelar
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="flex gap-2">
                                <?php if (hasRole('admin') || $veiculo['pode_editar']): ?>
                                    <button onclick="editVehicle(<?php echo htmlspecialchars(json_encode($veiculo)); ?>)"
                                        class="flex-1 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition flex items-center justify-center">
                                        <i class="fas fa-edit mr-2"></i> Editar
                                    </button>
                                <?php else: ?>
                                    <button onclick="requestAction(<?php echo $veiculo['id']; ?>, 'editar')"
                                        class="flex-1 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600 transition flex items-center justify-center">
                                        <i class="fas fa-edit mr-2"></i> Pedir Edição
                                    </button>
                                <?php endif; ?>

                                <?php if (hasRole('admin') || $veiculo['pode_eliminar']): ?>
                                    <form method="POST" action="" class="flex-1" id="formDelete<?php echo $veiculo['id']; ?>">
                                        <input type="hidden" name="vehicle_id" value="<?php echo $veiculo['id']; ?>">
                                        <input type="hidden" name="delete_vehicle" value="1">
                                        <button type="button" onclick="confirmDelete(<?php echo $veiculo['id']; ?>)"
                                            class="w-full px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition flex items-center justify-center">
                                            <i class="fas fa-trash-alt mr-2"></i> Eliminar
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button onclick="requestAction(<?php echo $veiculo['id']; ?>, 'eliminar')"
                                        class="flex-1 px-4 py-2 bg-gray-100 dark:bg-gray-800 text-red-600 dark:text-red-400 rounded border border-red-200 dark:border-red-900 hover:bg-red-50 dark:hover:bg-red-900/20 transition flex items-center justify-center">
                                        <i class="fas fa-trash-alt mr-2"></i> Pedir Remoção
                                    </button>
                                <?php endif; ?>
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
    <div
        class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate__animated animate__fadeInUp">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white" id="modalTitle">Novo Veículo</h3>
            <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="" class="p-6">
            <input type="hidden" name="add_vehicle" value="1">
            <input type="hidden" name="update_vehicle" value="1" disabled>
            <input type="hidden" name="vehicle_id" id="vehicle_id" value="">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Marca</label>
                <input type="text" name="marca" required
                    class="w-full px-4 py-2 rounded-lg border dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-primary">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Modelo</label>
                <input type="text" name="modelo" required
                    class="w-full px-4 py-2 rounded-lg border dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-primary">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Matrícula</label>
                <input type="text" name="matricula" required
                    class="w-full px-4 py-2 rounded-lg border dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-primary"
                    placeholder="ABC-123-XY">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Cor (opcional)</label>
                <input type="text" name="cor"
                    class="w-full px-4 py-2 rounded-lg border dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-primary">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Proprietário</label>
                <input type="text" name="proprietario_nome" placeholder="Nome do proprietário"
                    class="w-full px-4 py-2 rounded-lg border dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-primary">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Contacto</label>
                <input type="text" name="contacto" placeholder="Telefone do proprietário"
                    class="w-full px-4 py-2 rounded-lg border dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-primary">
            </div>
            <button type="submit"
                class="w-full py-3 bg-primary dark:bg-secondary text-white font-bold rounded-lg hover:shadow-lg transition">
                Adicionar Veículo
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
    function editVehicle(veiculo) {
        document.getElementById('modalTitle').textContent = 'Editar Veículo';
        document.getElementById('vehicle_id').value = veiculo.id;
        document.querySelector('input[name="update_vehicle"]').disabled = false;
        document.querySelector('input[name="add_vehicle"]').disabled = true;

        // Set form field values
        document.querySelector('input[name="marca"]').value = veiculo.marca;
        document.querySelector('input[name="modelo"]').value = veiculo.modelo;
        document.querySelector('input[name="matricula"]').value = veiculo.matricula;
        document.querySelector('input[name="cor"]').value = veiculo.cor;
        document.querySelector('input[name="proprietario_nome"]').value = veiculo.proprietario_nome;
        document.querySelector('input[name="contacto"]').value = veiculo.contacto;

        // Change submit button text
        const submitBtn = document.querySelector('#modalAddVehicle button[type="submit"]');
        submitBtn.textContent = 'Salvar Alterações';
        submitBtn.classList.remove('bg-primary', 'dark:bg-secondary');
        submitBtn.classList.add('bg-blue-600');

        document.getElementById('modalAddVehicle').classList.remove('hidden');
    }

    function requestAction(vehicleId, type) {
        const title = type === 'editar' ? 'Pedir Edição' : 'Pedir Remoção';
        const text = type === 'editar'
            ? 'Deseja solicitar autorização para editar os dados deste veículo? Um administrador analisará seu pedido.'
            : 'Deseja solicitar autorização para remover este veículo do sistema?';

        Swal.fire({
            title: title,
            text: text,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sim, solicitar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#1E3A8A'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="request_action" value="1">
                    <input type="hidden" name="vehicle_id" value="${vehicleId}">
                    <input type="hidden" name="request_type" value="${type}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    function confirmDelete(vehicleId) {
        Swal.fire({
            title: 'Confirmar Remoção',
            text: 'Tem certeza que deseja remover este veículo? Esta ação não pode ser desfeita.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#DC2626',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Sim, remover',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('formDelete' + vehicleId).submit();
            }
        });
    }

    // Reset modal when closing
    document.getElementById('modalAddVehicle').addEventListener('click', function (e) {
        if (e.target === this) {
            closeModal();
        }
    });

    function closeModal() {
        document.getElementById('modalAddVehicle').classList.add('hidden');
        // Optional: reset form
        // location.reload(); 
    }
</script>