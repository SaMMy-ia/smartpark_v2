<?php
/**
 * SmartPark - Central Router
 * Manages clean URLs and maps them to internal PHP files
 */

// Basic setup
$request = $_SERVER['REQUEST_URI'];
$basePath = '/smartpark';

// Remove base path from request
if (strpos($request, $basePath) === 0) {
    $request = substr($request, strlen($basePath));
}

// Remove query string
$request = parse_url($request, PHP_URL_PATH);
$request = trim($request, '/');

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    require_once 'config.php';
}

// Default route (Home/Login)
if ($request === '' || $request === 'index.php') {
    require 'auth/login.php';
    exit;
}

// Static route mapping
$routes = [
    'login' => 'auth/login.php',
    'register' => 'auth/register.php',
    'logout' => 'auth/logout.php',
    'admin' => 'admin/dashboard.php',
    'user' => 'usuario/dashboard.php',
    'usuario' => 'usuario/dashboard.php',
    'funcionario' => 'funcionario/dashboard.php',
    'contabilista' => 'contabilista/dashboard.php',
    '403' => '403.php',
    '404' => '404.php'
];

if (isset($routes[$request])) {
    require $routes[$request];
    exit;
}

// Dynamic routing for subfolders (e.g., /admin/users -> admin/users.php)
$parts = explode('/', $request);
$folder = $parts[0];
$file = implode('/', array_slice($parts, 1));

$allowedFolders = ['admin', 'usuario', 'funcionario', 'contabilista', 'auth'];

if (in_array($folder, $allowedFolders)) {
    // If no second part, use dashboard
    if (empty($file)) {
        $file = 'dashboard';
    }

    $path = "$folder/$file.php";

    if (file_exists($path)) {
        require $path;
        exit;
    }
}

// Fallback to direct file if exists (for recover.php, etc.)
if (file_exists("$request.php")) {
    require "$request.php";
    exit;
}

// If no route matches
http_response_code(404);
if (file_exists('404.php')) {
    require '404.php';
} else {
    echo "<h1>404 - Página não encontrada</h1>";
}
