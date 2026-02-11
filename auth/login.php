<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Redireciona se já estiver logado
if (isLoggedIn()) {
    header('Location: ' . getRoleRedirect($_SESSION['user_role']));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Por favor, preencha todos os campos.';
    } elseif (!isValidEmail($email)) {
        $error = 'Email inválido.';
    } else {
        if (loginUser($email, $password)) {
            session_write_close(); // Garantir que os dados da sessão sejam salvos antes do redirecionamento
            header('Location: ' . getRoleRedirect($_SESSION['user_role']));
            exit;
        } else {
            $error = 'Email ou senha incorretos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" :class="{ 'dark': darkMode }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0f172a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-title" content="SmartPark">
    <title>Login - SmartPark</title>

    <!-- Manifest PWA -->
    <link rel="manifest" href="../manifest.json">


    <!-- Tailwind CSS -->
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

    <!-- AlpineJS -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body
    class="bg-gradient-to-br from-blue-50 to-green-50 dark:from-gray-900 dark:to-gray-800 min-h-screen flex items-center justify-center transition-colors duration-200">

    <!-- Dark Mode Toggle -->
    <div class="absolute top-4 right-4">
        <button @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)"
            class="p-3 rounded-full bg-white dark:bg-gray-800 shadow-lg hover:shadow-xl transition">
            <i class="fas fa-moon text-gray-700 dark:text-gray-300" x-show="!darkMode"></i>
            <i class="fas fa-sun text-yellow-500" x-show="darkMode" x-cloak></i>
        </button>
    </div>

    <div class="w-full max-w-md px-6">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div
                class="inline-flex items-center justify-center w-20 h-20 bg-primary dark:bg-secondary rounded-full mb-4 shadow-lg">
                <i class="fas fa-car text-white text-3xl"></i>
            </div>
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">SmartPark</h1>
            <p class="text-gray-600 dark:text-gray-400">Sistema de Gerenciamento de Estacionamentos</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 transition-colors duration-200">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Entrar</h2>

            <?php if ($error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <p><?php echo e($error); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm">
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-envelope mr-1"></i> Email
                    </label>
                    <input type="email" id="email" name="email" required
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary dark:focus:ring-secondary focus:border-transparent transition"
                        placeholder="seu@email.com" value="<?php echo e($_POST['email'] ?? ''); ?>">
                </div>

                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-lock mr-1"></i> Senha
                    </label>
                    <input type="password" id="password" name="password" required
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary dark:focus:ring-secondary focus:border-transparent transition"
                        placeholder="••••••••">
                </div>

                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <input type="checkbox" id="remember" name="remember"
                            class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                        <label for="remember" class="ml-2 text-sm text-gray-700 dark:text-gray-300">Lembrar-me</label>
                    </div>
                    <a href="../recover.php" class="text-sm text-primary dark:text-secondary hover:underline">Esqueceu a
                        senha?</a>
                </div>

                <button type="submit"
                    class="w-full bg-primary dark:bg-secondary text-white font-semibold py-3 rounded-lg hover:bg-blue-900 dark:hover:bg-green-600 transition transform hover:scale-105 shadow-lg">
                    <i class="fas fa-sign-in-alt mr-2"></i> Entrar
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Não tem uma conta?
                    <a href="register"
                        class="text-primary dark:text-secondary font-semibold hover:underline">Registre-se aqui</a>
                </p>
            </div>
        </div>
    </div>

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
</body>

</html>