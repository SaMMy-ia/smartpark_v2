<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$user = getCurrentUser();
$success = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitize($_POST['nome']);
    $email = sanitize($_POST['email']);
    $senhaAtual = $_POST['senha_atual'] ?? '';
    $novaSenha = $_POST['nova_senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';
    
    // Validate email
    if (!isValidEmail($email)) {
        $error = 'Email inválido.';
    } elseif ($email !== $user['email']) {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user['id']]);
        if ($stmt->fetch()) {
            $error = 'Este email já está em uso.';
        }
    }
    
    if (!$error) {
        // Update basic info
        $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ? WHERE id = ?");
        $stmt->execute([$nome, $email, $user['id']]);
        
        // Update password if provided
        if (!empty($novaSenha)) {
            // Verify current password
            $stmt = $pdo->prepare("SELECT senha FROM usuarios WHERE id = ?");
            $stmt->execute([$user['id']]);
            $currentHash = $stmt->fetch()['senha'];
            
            if (!password_verify($senhaAtual, $currentHash)) {
                $error = 'Senha atual incorreta.';
            } elseif (strlen($novaSenha) < 6) {
                $error = 'A nova senha deve ter no mínimo 6 caracteres.';
            } elseif ($novaSenha !== $confirmarSenha) {
                $error = 'As senhas não coincidem.';
            } else {
                $hashedPassword = password_hash($novaSenha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $user['id']]);
            }
        }
        
        if (!$error) {
            $_SESSION['user_name'] = $nome;
            $_SESSION['user_email'] = $email;
            logAction($user['id'], "Perfil atualizado");
            $success = 'Perfil atualizado com sucesso!';
            $user = getCurrentUser(); // Refresh user data
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            <i class="fas fa-user-cog mr-2"></i> Meu Perfil
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Gerencie suas informações pessoais</p>
    </div>
    
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
        <?php if ($error): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
            <i class="fas fa-exclamation-circle mr-2"></i> <?php echo e($error); ?>
        </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
            <i class="fas fa-check-circle mr-2"></i> <?php echo e($success); ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="space-y-6">
                <!-- Basic Info -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informações Básicas</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nome Completo</label>
                            <input type="text" name="nome" value="<?php echo e($user['nome']); ?>" required
                                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email</label>
                            <input type="email" name="email" value="<?php echo e($user['email']); ?>" required
                                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary">
                        </div>
                    </div>
                </div>
                
                <!-- Change Password -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Alterar Senha</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Deixe em branco se não deseja alterar a senha</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Senha Atual</label>
                            <input type="password" name="senha_atual"
                                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nova Senha</label>
                            <input type="password" name="nova_senha" minlength="6"
                                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Confirmar Nova Senha</label>
                            <input type="password" name="confirmar_senha" minlength="6"
                                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary">
                        </div>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-3 bg-primary dark:bg-secondary text-white rounded-lg font-semibold hover:bg-blue-900 dark:hover:bg-green-600 transition shadow-lg">
                        <i class="fas fa-save mr-2"></i> Salvar Alterações
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
