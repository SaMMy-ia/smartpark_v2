<?php
/**
 * SmartPark - Central Router
 * Handlers all incoming requests and routes them to the correct file.
 */

// 1. Configurações Iniciais e Sessão
require_once 'config.php';

// 2. Detecção automática da BasePath
$scriptName = $_SERVER['SCRIPT_NAME']; // e.g. /smartpark/index.php
$basePath = str_replace('/index.php', '', $scriptName);

$requestPath = $_SERVER['REQUEST_URI'];
$requestPath = strtok($requestPath, '?'); // Remove query string

// Remove o BasePath do início da URL de forma insensível a maiúsculas (para Windows/XAMPP)
if ($basePath !== '' && stripos($requestPath, $basePath) === 0) {
    $path = substr($requestPath, strlen($basePath));
} else {
    $path = $requestPath;
}

$path = trim($path, '/');

// 3. Mapeamento de Rotas Estáticas
$routes = [
    '' => 'home.php',
    'login' => 'auth/login.php',
    'register' => 'auth/register.php',
    'logout' => 'auth/logout.php',
    'admin' => 'admin/dashboard.php',
    'user' => 'usuario/dashboard.php',
    'usuario' => 'usuario/dashboard.php'
];

// 4. Lógica de Roteamento
if (array_key_exists($path, $routes)) {
    $targetFile = $routes[$path];
} else {
    // Tenta encontrar o arquivo dinamicamente (ex: /admin/veiculos -> admin/veiculos.php)
    $targetFile = $path . '.php';

    // Fallback para pastas (ex: /admin/ -> admin/dashboard.php)
    if (is_dir($path)) {
        $targetFile = $path . '/dashboard.php';
    }
}

// 5. Inclusão do Arquivo ou 404
if ($targetFile && file_exists($targetFile) && is_file($targetFile)) {
    require_once $targetFile;
    exit;
} else {
    // Erro 404 Personalizado
    http_response_code(404);
    if (file_exists('errors/404.php')) {
        require_once 'errors/404.php';
    } else {
        echo "<h1>404 - Página Não Encontrada</h1>";
        echo "<p>A rota <strong>/" . htmlspecialchars($path) . "</strong> não existe.</p>";
    }
    exit;
}
