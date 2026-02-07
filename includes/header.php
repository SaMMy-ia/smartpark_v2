<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

// Verificação automática de multas (com cache para evitar sobrecarga)
require_once __DIR__ . '/multas_service.php';
if (isLoggedIn()) {
    verificarMultasAutomaticasComCache($pdo);
}

$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="pt-BR" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0f172a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="SmartPark">
    <title>SmartPark - Sistema de Gerenciamento de Estacionamentos</title>
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="/smartpark/manifest.json">
    
    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" href="/smartpark/assets/icons/icon-192.png">
    <link rel="apple-touch-icon" sizes="512x512" href="/smartpark/assets/icons/icon-512.png">
    
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
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Chart.js (for dashboards) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- PWA Scripts -->
    <script src="/smartpark/js/pwa-install.js" defer></script>
    <script src="/smartpark/js/pwa-status.js" defer></script>
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 transition-colors duration-200">
    
    <!-- Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow-lg sticky top-0 z-50" x-data="{ mobileMenuOpen: false, userMenuOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo and Brand -->
                <div class="flex items-center">
                    <a href="<?php echo getRoleRedirect($_SESSION['user_role'] ?? 'usuario'); ?>" class="flex items-center space-x-2">
                        <i class="fas fa-car text-primary dark:text-secondary text-2xl"></i>
                        <span class="text-xl font-bold text-primary dark:text-secondary">SmartPark</span>
                    </a>
                </div>
                
                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center space-x-4">
                    <?php if (isLoggedIn()): ?>
                        <?php if (hasRole('admin')): ?>
                            <a href="/smartpark/admin/dashboard.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <i class="fas fa-chart-line mr-1"></i> Dashboard
                            </a>
                            <a href="/smartpark/admin/reservas.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <i class="fas fa-calendar-check mr-1"></i> Reservas
                            </a>
                            <a href="/smartpark/admin/pesquisa-matricula.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <i class="fas fa-search mr-1"></i> Pesquisar
                            </a>
                            <a href="/smartpark/admin/multas.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <i class="fas fa-exclamation-triangle mr-1"></i> Multas
                            </a>
                            <a href="/smartpark/admin/usuarios.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <i class="fas fa-users mr-1"></i> Usuários
                            </a>
                        <?php elseif (hasRole('funcionario')): ?>
                            <a href="/smartpark/funcionario/dashboard.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <i class="fas fa-chart-line mr-1"></i> Dashboard
                            </a>
                            <a href="/smartpark/funcionario/reservas.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <i class="fas fa-calendar-check mr-1"></i> Reservas
                            </a>
                            <a href="/smartpark/funcionario/pesquisa-matricula.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <i class="fas fa-search mr-1"></i> Pesquisar
                            </a>
                            <a href="/smartpark/funcionario/multas.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <i class="fas fa-exclamation-triangle mr-1"></i> Multas
                            </a>
                        <?php elseif (isAccountant()): ?>
                            <a href="/smartpark/contabilista/dashboard.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <i class="fas fa-calculator mr-1"></i> Dashboard
                            </a>
                            <a href="/smartpark/contabilista/relatorios.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <i class="fas fa-file-invoice mr-1"></i> Relatórios
                            </a>
                        <?php else: ?>
                            <a href="/smartpark/usuario/dashboard.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <i class="fas fa-chart-line mr-1"></i> Dashboard
                            </a>
                            <a href="/smartpark/usuario/buscar-vagas.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <i class="fas fa-search mr-1"></i> Buscar Vagas
                            </a>
                            <a href="/smartpark/usuario/minhas-reservas.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <i class="fas fa-calendar mr-1"></i> Minhas Reservas
                            </a>
                            <a href="/smartpark/usuario/veiculos.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <i class="fas fa-car mr-1"></i> Meus Veículos
                            </a>
                            <a href="/smartpark/usuario/minhas-multas.php" class="relative px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <i class="fas fa-exclamation-triangle mr-1"></i> Minhas Multas
                                <?php 
                                $resumoMultasMenu = getResumoMultasUsuario($pdo, $_SESSION['user_id']);
                                if ($resumoMultasMenu['quantidade'] > 0): 
                                ?>
                                <span class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-600 rounded-full">
                                    <?php echo $resumoMultasMenu['quantidade']; ?>
                                </span>
                                <?php endif; ?>
                            </a>
                            <a href="/smartpark/usuario/historico.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <i class="fas fa-history mr-1"></i> Histórico
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <!-- Dark Mode Toggle -->
                    <button @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)" 
                            class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <i class="fas fa-moon" x-show="!darkMode"></i>
                        <i class="fas fa-sun" x-show="darkMode" x-cloak></i>
                    </button>
                    
                    <?php if (isLoggedIn()): ?>
                    <!-- User Menu -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center space-x-2 px-3 py-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                            <i class="fas fa-user-circle text-xl"></i>
                            <span class="text-sm font-medium"><?php echo e($currentUser['nome']); ?></span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        
                        <div x-show="open" 
                             @click.away="open = false"
                             x-transition
                             class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity-5">
                            <a href="/smartpark/usuario/perfil.php" class="block px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700">
                                <i class="fas fa-user mr-2"></i> Meu Perfil
                            </a>
                            <a href="/smartpark/logout.php" class="block px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 text-red-600 dark:text-red-400">
                                <i class="fas fa-sign-out-alt mr-2"></i> Sair
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Mobile menu -->
        <div x-show="mobileMenuOpen" 
             x-transition
             class="md:hidden bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <?php if (isLoggedIn()): ?>
                    <?php if (hasRole('admin')): ?>
                        <a href="/smartpark/admin/dashboard.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-chart-line mr-2"></i> Dashboard
                        </a>
                        <a href="/smartpark/admin/reservas.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-calendar-check mr-2"></i> Reservas
                        </a>
                        <a href="/smartpark/admin/pesquisa-matricula.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-search mr-2"></i> Pesquisar Matrícula
                        </a>
                        <a href="/smartpark/admin/multas.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-exclamation-triangle mr-2"></i> Multas
                        </a>
                        <a href="/smartpark/admin/usuarios.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-users mr-2"></i> Usuários
                        </a>
                    <?php elseif (hasRole('funcionario')): ?>
                        <a href="/smartpark/funcionario/dashboard.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-chart-line mr-2"></i> Dashboard
                        </a>
                        <a href="/smartpark/funcionario/reservas.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-calendar-check mr-2"></i> Reservas
                        </a>
                        <a href="/smartpark/funcionario/pesquisa-matricula.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-search mr-2"></i> Pesquisar Matrícula
                        </a>
                        <a href="/smartpark/funcionario/multas.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-exclamation-triangle mr-2"></i> Multas
                        </a>
                    <?php elseif (isAccountant()): ?>
                        <a href="/smartpark/contabilista/dashboard.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-calculator mr-2"></i> Dashboard
                        </a>
                        <a href="/smartpark/contabilista/relatorios.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-file-invoice mr-2"></i> Relatórios
                        </a>
                    <?php else: ?>
                        <a href="/smartpark/usuario/dashboard.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-chart-line mr-2"></i> Dashboard
                        </a>
                        <a href="/smartpark/usuario/buscar-vagas.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-search mr-2"></i> Buscar Vagas
                        </a>
                        <a href="/smartpark/usuario/minhas-reservas.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-calendar mr-2"></i> Minhas Reservas
                        </a>
                        <a href="/smartpark/usuario/veiculos.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-car mr-2"></i> Meus Veículos
                        </a>
                        <a href="/smartpark/usuario/minhas-multas.php" class="relative block px-3 py-2 rounded-md text-base font-medium hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-exclamation-triangle mr-2"></i> Minhas Multas
                            <?php 
                            $resumoMultasMobile = getResumoMultasUsuario($pdo, $_SESSION['user_id']);
                            if ($resumoMultasMobile['quantidade'] > 0): 
                            ?>
                            <span class="ml-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-600 rounded-full">
                                <?php echo $resumoMultasMobile['quantidade']; ?>
                            </span>
                            <?php endif; ?>
                        </a>
                        <a href="/smartpark/usuario/historico.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-history mr-2"></i> Histórico
                        </a>
                    <?php endif; ?>
                    
                    <hr class="my-2 border-gray-200 dark:border-gray-700">
                    
                    <a href="/smartpark/usuario/perfil.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="fas fa-user mr-2"></i> Meu Perfil
                    </a>
                    <a href="/smartpark/logout.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-gray-100 dark:hover:bg-gray-700 text-red-600 dark:text-red-400">
                        <i class="fas fa-sign-out-alt mr-2"></i> Sair
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <?php
    // Alerta de Multas Pendentes (apenas para usuários/clientes)
    if (isLoggedIn() && hasRole('usuario')):
        $resumoMultas = getResumoMultasUsuario($pdo, $_SESSION['user_id']);
        if ($resumoMultas['quantidade'] > 0):
    ?>
    <div class="bg-red-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
            <div class="flex items-center justify-between flex-wrap">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-exclamation-triangle text-2xl animate-pulse"></i>
                    <div>
                        <p class="font-semibold">
                            Você possui <?php echo $resumoMultas['quantidade']; ?> multa<?php echo $resumoMultas['quantidade'] > 1 ? 's' : ''; ?> pendente<?php echo $resumoMultas['quantidade'] > 1 ? 's' : ''; ?>
                        </p>
                        <p class="text-sm opacity-90">
                            Valor total: <?php echo formatCurrency($resumoMultas['valor']); ?> MZN
                        </p>
                    </div>
                </div>
                <a href="/smartpark/usuario/minhas-multas.php" 
                   class="mt-2 sm:mt-0 inline-flex items-center px-4 py-2 bg-white text-red-600 rounded-lg font-semibold text-sm hover:bg-gray-100 transition">
                    <i class="fas fa-eye mr-2"></i>
                    Ver Detalhes
                </a>
            </div>
        </div>
    </div>
    <?php 
        endif;
    endif;
    ?>
    
    <!-- Flash Messages -->
    <?php
    $flash = getFlashMessage();
    if ($flash):
    ?>
    <div x-data="{ show: true }" 
         x-show="show"
         x-init="setTimeout(() => show = false, 5000)"
         x-transition
         class="fixed top-20 right-4 z-50 max-w-md">
        <div class="<?php echo $flash['type'] === 'success' ? 'bg-green-100 border-green-500 text-green-900' : 'bg-red-100 border-red-500 text-red-900'; ?> border-l-4 p-4 rounded shadow-lg">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas <?php echo $flash['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
                    <p><?php echo e($flash['message']); ?></p>
                </div>
                <button @click="show = false" class="ml-4">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Main Content -->
    <main class="min-h-screen">
