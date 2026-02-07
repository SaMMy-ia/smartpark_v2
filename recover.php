<?php
require_once 'config.php';
require_once 'includes/functions.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Por favor, informe seu email.';
    } elseif (!isValidEmail($email)) {
        $error = 'Email inválido.';
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $success = 'Um link de recuperação foi enviado para seu email.';
            
            // Fake email sending (console log)
            $resetLink = "http://localhost/SmartPark/reset-password.php?token=" . bin2hex(random_bytes(16));
            echo "<script>console.log('Email de recuperação enviado para: $email');</script>";
            echo "<script>console.log('Link de recuperação: $resetLink');</script>";
        } else {
            $error = 'Email não encontrado em nosso sistema.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - SmartPark</title>
    
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
    
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-green-50 dark:from-gray-900 dark:to-gray-800 min-h-screen flex items-center justify-center transition-colors duration-200">
    
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
            <div class="inline-flex items-center justify-center w-20 h-20 bg-primary dark:bg-secondary rounded-full mb-4 shadow-lg">
                <i class="fas fa-car text-white text-3xl"></i>
            </div>
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">SmartPark</h1>
            <p class="text-gray-600 dark:text-gray-400">Recuperar Senha</p>
        </div>
        
        <!-- Recover Card -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 transition-colors duration-200">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Esqueceu sua senha?</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                Digite seu email e enviaremos um link para redefinir sua senha.
            </p>
            
            <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <p><?php echo e($error); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded" role="alert">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <p><?php echo e($success); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <!-- Email -->
                <div class="mb-6">
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-envelope mr-1"></i> Email
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           required
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary dark:focus:ring-secondary focus:border-transparent transition"
                           placeholder="seu@email.com"
                           value="<?php echo e($_POST['email'] ?? ''); ?>">
                </div>
                
                <!-- Submit Button -->
                <button type="submit" 
                        class="w-full bg-primary dark:bg-secondary text-white font-semibold py-3 rounded-lg hover:bg-blue-900 dark:hover:bg-green-600 transition transform hover:scale-105 shadow-lg">
                    <i class="fas fa-paper-plane mr-2"></i> Enviar Link de Recuperação
                </button>
            </form>
            
            <!-- Back to Login -->
            <div class="mt-6 text-center">
                <a href="index.php" class="text-sm text-primary dark:text-secondary hover:underline">
                    <i class="fas fa-arrow-left mr-1"></i> Voltar para o login
                </a>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="text-center mt-8 text-sm text-gray-600 dark:text-gray-400">
            <p>&copy; <?php echo date('Y'); ?> SmartPark. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>
