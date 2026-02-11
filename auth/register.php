<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . getRoleRedirect($_SESSION['user_role']));
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitize($_POST['nome'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Dados do Veículo
    $marca = sanitize($_POST['marca'] ?? '');
    $modelo = sanitize($_POST['modelo'] ?? '');
    $matricula = strtoupper(sanitize($_POST['matricula'] ?? ''));
    $cor = sanitize($_POST['cor'] ?? '');

    // Validation
    if (empty($nome) || empty($email) || empty($password) || empty($confirmPassword) || empty($marca) || empty($modelo) || empty($matricula)) {
        $error = 'Por favor, preencha todos os campos obrigatórios (incluindo os do veículo).';
    } elseif (!isValidEmail($email)) {
        $error = 'Email inválido.';
    } elseif (strlen($password) < 6) {
        $error = 'A senha deve ter no mínimo 6 caracteres.';
    } elseif ($password !== $confirmPassword) {
        $error = 'As senhas não coincidem.';
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = 'Este email já está cadastrado.';
        } else {
            // Check if license plate already exists
            $stmt = $pdo->prepare("SELECT id FROM veiculos WHERE matricula = ?");
            $stmt->execute([$matricula]);

            if ($stmt->fetch()) {
                $error = 'Esta matrícula já está registrada no sistema.';
            } else {
                try {
                    $pdo->beginTransaction();

                    // Create user
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, role) VALUES (?, ?, ?, 'usuario')");
                    $stmt->execute([$nome, $email, $hashedPassword]);
                    $userId = $pdo->lastInsertId();

                    // Create vehicle
                    $stmt = $pdo->prepare("INSERT INTO veiculos (usuario_id, marca, modelo, matricula, cor) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$userId, $marca, $modelo, $matricula, $cor]);

                    $pdo->commit();

                    $success = 'Conta criada com sucesso! Você já pode fazer login.';
                    logAction($userId, 'Conta e veículo registrados');

                    // Auto-login
                    loginUser($email, $password);
                    header('Location: /smartpark/usuario/dashboard.php');
                    exit;
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = 'Erro ao criar conta: ' . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" :class="{ 'dark': darkMode }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar - SmartPark</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#1E3A8A',
                        secondary: '#10B981',
                    }
                }
            }
        }
    </script>
    <!-- Service Worker -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/smartpark/service-worker.js')
                    .then(() => console.log('Service Worker registrado com sucesso'))
                    .catch(err => console.log('Erro ao registrar Service Worker:', err));
            });
        }
    </script>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body
    class="bg-gradient-to-br from-blue-50 to-green-50 dark:from-gray-900 dark:to-gray-800 min-h-screen flex items-center justify-center py-12 transition-colors duration-200">

    <!-- Dark Mode Toggle -->
    <div class="absolute top-4 right-4">
        <button @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)"
            class="p-3 rounded-full bg-white dark:bg-gray-800 shadow-lg hover:shadow-xl transition">
            <i class="fas fa-moon text-gray-700 dark:text-gray-300" x-show="!darkMode"></i>
            <i class="fas fa-sun text-yellow-500" x-show="darkMode" x-cloak></i>
        </button>
    </div>

    <div class="w-full max-w-md px-6">
        <!-- Logo and Title -->
        <div class="text-center mb-8">
            <div
                class="inline-flex items-center justify-center w-20 h-20 bg-primary dark:bg-secondary rounded-full mb-4 shadow-lg">
                <i class="fas fa-car text-white text-3xl"></i>
            </div>
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">SmartPark</h1>
            <p class="text-gray-600 dark:text-gray-400">Crie sua conta</p>
        </div>

        <!-- Register Card -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 transition-colors duration-200">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Registrar</h2>

            <?php if ($error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <p>
                            <?php echo e($error); ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded" role="alert">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <p>
                            <?php echo e($success); ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="registerForm">
                <!-- Nome -->
                <div class="mb-4">
                    <label for="nome" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-user mr-1"></i> Nome Completo
                    </label>
                    <input type="text" id="nome" name="nome" required
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary dark:focus:ring-secondary focus:border-transparent transition"
                        placeholder="Seu nome completo" value="<?php echo e($_POST['nome'] ?? ''); ?>">
                </div>

                <!-- Email -->
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-envelope mr-1"></i> Email
                    </label>
                    <input type="email" id="email" name="email" required
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary dark:focus:ring-secondary focus:border-transparent transition"
                        placeholder="seu@email.com" value="<?php echo e($_POST['email'] ?? ''); ?>">
                </div>

                <!-- Password -->
                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-lock mr-1"></i> Senha
                    </label>
                    <input type="password" id="password" name="password" required minlength="6"
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary dark:focus:ring-secondary focus:border-transparent transition"
                        placeholder="Mínimo 6 caracteres">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Mínimo de 6 caracteres</p>
                </div>

                <!-- Confirm Password -->
                <div class="mb-6">
                    <label for="confirm_password"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-lock mr-1"></i> Confirmar Senha
                    </label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6"
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary dark:focus:ring-secondary focus:border-transparent transition"
                        placeholder="Digite a senha novamente">
                </div>

                <div class="mb-6 border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Dados do Veículo</h3>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="mb-4">
                            <label for="marca"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Marca</label>
                            <input type="text" id="marca" name="marca" required
                                class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary dark:focus:ring-secondary transition"
                                placeholder="Ex: Toyota" value="<?php echo e($_POST['marca'] ?? ''); ?>">
                        </div>
                        <div class="mb-4">
                            <label for="modelo"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Modelo</label>
                            <input type="text" id="modelo" name="modelo" required
                                class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary dark:focus:ring-secondary transition"
                                placeholder="Ex: Hilux" value="<?php echo e($_POST['modelo'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="mb-4">
                            <label for="matricula"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Matrícula</label>
                            <input type="text" id="matricula" name="matricula" required
                                class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary dark:focus:ring-secondary transition"
                                placeholder="ABC-123-XY" value="<?php echo e($_POST['matricula'] ?? ''); ?>">
                        </div>
                        <div class="mb-4">
                            <label for="cor"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Cor</label>
                            <input type="text" id="cor" name="cor"
                                class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary dark:focus:ring-secondary transition"
                                placeholder="Ex: Branco" value="<?php echo e($_POST['cor'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                    class="w-full bg-primary dark:bg-secondary text-white font-semibold py-3 rounded-lg hover:bg-blue-900 dark:hover:bg-green-600 transition transform hover:scale-105 shadow-lg">
                    <i class="fas fa-user-plus mr-2"></i> Criar Conta
                </button>
            </form>

            <!-- Login Link -->
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Já tem uma conta?
                    <a href="login" class="text-primary dark:text-secondary font-semibold hover:underline">
                        Faça login aqui
                    </a>
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8 text-sm text-gray-600 dark:text-gray-400">
            <p>&copy;
                <?php echo date('Y'); ?> SmartPark. Todos os direitos reservados.
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Client-side validation
        document.getElementById('registerForm').addEventListener('submit', function (e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Erro no Registo',
                    text: 'As senhas não coincidem.',
                    confirmButtonColor: '#1E3A8A'
                });
                return false;
            }

            if (password.length < 6) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Senha Fraca',
                    text: 'A senha deve ter no mínimo 6 caracteres.',
                    confirmButtonColor: '#1E3A8A'
                });
                return false;
            }
        });
    </script>
</body>

</html>