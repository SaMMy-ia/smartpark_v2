<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';
?>

<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
        <i class="fas fa-lock text-6xl text-red-600 mb-4"></i>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
            Acesso Negado
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
            Você não tem permissão para acessar esta página.
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10 text-center">
            <p class="text-gray-700 mb-6">
                Se você acredita que isso é um erro, contate o administrador do sistema.
            </p>
            <a href="javascript:history.back()" class="font-medium text-primary hover:text-blue-500">
                <i class="fas fa-arrow-left mr-2"></i> Voltar
            </a>
            <span class="mx-2 text-gray-300">|</span>
            <a href="/smartpark/" class="font-medium text-primary hover:text-blue-500">
                Ir para o Início
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>