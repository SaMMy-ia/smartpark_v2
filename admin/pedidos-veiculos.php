<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Allow both admin and funcionario to manage these requests
if (!hasRole('admin') && !hasRole('funcionario')) {
    header('Location: /smartpark/403.php');
    exit;
}

$success = '';
$error = '';

// Handle Approval / Rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $requestId = (int) $_POST['request_id'];
    $action = $_POST['action']; // 'permitir' or 'negar'

    $stmt = $pdo->prepare("SELECT * FROM solicitacoes_veiculos WHERE id = ?");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();

    if ($request) {
        if ($action === 'permitir') {
            // Update vehicle flags
            $column = ($request['tipo'] === 'editar') ? 'pode_editar' : 'pode_eliminar';
            $stmt = $pdo->prepare("UPDATE veiculos SET $column = 1 WHERE id = ?");
            $stmt->execute([$request['veiculo_id']]);

            // Update request status
            $stmt = $pdo->prepare("UPDATE solicitacoes_veiculos SET status = 'aprovada' WHERE id = ?");
            $stmt->execute([$requestId]);

            $success = "Solicitação aprovada! O usuário já pode realizar a ação.";
            logAction($_SESSION['user_id'], "Solicitação de " . $request['tipo'] . " aprovada para veículo ID " . $request['veiculo_id']);
        } else {
            // Record rejection (we'll just mark as rejected)
            $stmt = $pdo->prepare("UPDATE solicitacoes_veiculos SET status = 'rejeitada' WHERE id = ?");
            $stmt->execute([$requestId]);

            $success = "Solicitação negada.";
            logAction($_SESSION['user_id'], "Solicitação de " . $request['tipo'] . " negada para veículo ID " . $request['veiculo_id']);
        }
    } else {
        $error = "Solicitação não encontrada.";
    }
}

// Get all pending requests
$stmt = $pdo->query("
    SELECT sv.*, v.matricula, v.marca, v.modelo, u.nome as usuario_nome, u.email as usuario_email
    FROM solicitacoes_veiculos sv
    JOIN veiculos v ON sv.veiculo_id = v.id
    JOIN usuarios u ON sv.usuario_id = u.id
    WHERE sv.status = 'pendente'
    ORDER BY sv.created_at ASC
");
$pedidos = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            <i class="fas fa-clipboard-list mr-2 text-primary dark:text-secondary"></i> Pedidos de Alteração
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Gerencie as solicitações de edição e exclusão de veículos dos
            usuários.</p>
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

    <?php if (empty($pedidos)): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
            <i class="fas fa-check-double text-6xl text-gray-200 dark:text-gray-700 mb-4"></i>
            <p class="text-gray-600 dark:text-gray-400">Não há pedidos pendentes no momento.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($pedidos as $p): ?>
                <div
                    class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden border border-gray-100 dark:border-gray-700 hover:shadow-xl transition">
                    <div
                        class="px-6 py-4 border-b border-gray-100 dark:border-gray-600 flex justify-between items-center bg-gray-50 dark:bg-gray-700/50">
                        <span
                            class="px-2 py-1 text-xs font-bold uppercase rounded-full <?php echo $p['tipo'] === 'editar' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800'; ?>">
                            Pedido de
                            <?php echo $p['tipo']; ?>
                        </span>
                        <span class="text-xs text-gray-500">
                            <?php echo formatDateTime($p['created_at']); ?>
                        </span>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center space-x-3 mb-4">
                            <div
                                class="w-10 h-10 rounded-full bg-primary dark:bg-secondary flex items-center justify-center text-white">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900 dark:text-white">
                                    <?php echo e($p['usuario_nome']); ?>
                                </h4>
                                <p class="text-xs text-gray-500">
                                    <?php echo e($p['usuario_email']); ?>
                                </p>
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-900/40 rounded-lg p-3 mb-6">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Veículo:</p>
                            <p class="font-semibold text-gray-900 dark:text-white">
                                <?php echo e($p['matricula']); ?>
                            </p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                <?php echo e($p['marca']); ?>
                                <?php echo e($p['modelo']); ?>
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <form method="POST" action="" class="flex-1">
                                <input type="hidden" name="request_id" value="<?php echo $p['id']; ?>">
                                <input type="hidden" name="action" value="permitir">
                                <button type="submit"
                                    class="w-full py-2 bg-green-600 text-white rounded hover:bg-green-700 transition font-semibold text-sm">
                                    <i class="fas fa-check mr-1"></i> Permitir
                                </button>
                            </form>
                            <form method="POST" action="" class="flex-1">
                                <input type="hidden" name="request_id" value="<?php echo $p['id']; ?>">
                                <input type="hidden" name="action" value="negar">
                                <button type="submit"
                                    class="w-full py-2 bg-red-600 text-white rounded hover:bg-red-700 transition font-semibold text-sm">
                                    <i class="fas fa-times mr-1"></i> Não Permitir
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>