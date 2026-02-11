<?php
/**
 * SmartPark - Landing Page
 */
require_once __DIR__ . '/config.php';

// Se já estiver logado, pode redirecionar para o dashboard apropriado
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['user_role'] ?? 'user';
    if ($role === 'admin') {
        header('Location: /smartpark/admin');
    } elseif ($role === 'funcionario') {
        header('Location: /smartpark/funcionario');
    } else {
        header('Location: /smartpark/user');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartPark - Bem-vindo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        body {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center text-white p-4">
    <div class="max-w-4xl w-full glass-card rounded-3xl p-8 md:p-12 shadow-2xl text-center">
        <div class="mb-8">
            <div
                class="w-20 h-20 bg-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg shadow-blue-500/50">
                <i class="fas fa-parking text-4xl"></i>
            </div>
            <h1
                class="text-4xl md:text-6xl font-extrabold mb-4 bg-clip-text text-transparent bg-gradient-to-r from-blue-400 to-indigo-400">
                SmartPark
            </h1>
            <p class="text-xl text-gray-400 max-w-2xl mx-auto">
                A solução inteligente e moderna para gestão de estacionamentos. Segurança, agilidade e controle total na
                palma da sua mão.
            </p>
        </div>

        <div class="grid md:grid-cols-2 gap-6 mt-12">
            <a href="/smartpark/login"
                class="group relative px-8 py-4 bg-blue-600 hover:bg-blue-500 rounded-2xl font-bold text-lg transition-all duration-300 transform hover:-translate-y-1 shadow-xl hover:shadow-blue-500/25">
                <i class="fas fa-sign-in-alt mr-2 group-hover:translate-x-1 transition-transform"></i>
                Acessar Minha Conta
            </a>
            <a href="/smartpark/register"
                class="group relative px-8 py-4 bg-transparent border-2 border-white/20 hover:border-white/40 rounded-2xl font-bold text-lg transition-all duration-300 transform hover:-translate-y-1">
                <i class="fas fa-user-plus mr-2 group-hover:scale-110 transition-transform"></i>
                Criar Nova Conta
            </a>
        </div>

        <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-8 text-left">
            <div class="p-4 rounded-xl hover:bg-white/5 transition-colors">
                <i class="fas fa-shield-alt text-blue-400 text-2xl mb-3"></i>
                <h3 class="font-bold mb-1">Segurança Total</h3>
                <p class="text-sm text-gray-500">Monitoramento e controle de acesso rigoroso.</p>
            </div>
            <div class="p-4 rounded-xl hover:bg-white/5 transition-colors">
                <i class="fas fa-clock text-indigo-400 text-2xl mb-3"></i>
                <h3 class="font-bold mb-1">Agilidade</h3>
                <p class="text-sm text-gray-500">Reservas rápidas sem complicação.</p>
            </div>
            <div class="p-4 rounded-xl hover:bg-white/5 transition-colors">
                <i class="fas fa-chart-line text-cyan-400 text-2xl mb-3"></i>
                <h3 class="font-bold mb-1">Gestão Inteligente</h3>
                <p class="text-sm text-gray-500">Relatórios e estatísticas em tempo real.</p>
            </div>
        </div>

        <footer class="mt-12 pt-8 border-t border-white/10 text-gray-500 text-sm">
            &copy; 2026 SmartPark. Todos os direitos reservados.
        </footer>
    </div>
</body>

</html>