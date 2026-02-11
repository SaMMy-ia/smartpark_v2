<?php
/**
 * SmartPark - Database Configuration
 * PDO Connection + Secure Session Control
 */

// =========================
// DATABASE
// =========================
define('DB_HOST', 'localhost');
define('DB_NAME', 'smartpark_v9');
define('DB_USER', 'root');
define('DB_PASS', '');

// PDO connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die("Erro de conexão com o banco de dados: " . $e->getMessage());
}

// =========================
// TIMEZONE
// =========================
date_default_timezone_set('Africa/Maputo'); // ajustado para Moçambique

// =========================
// SESSION SEGURA
// =========================

// tempo máximo inativo (20 minutos)
define('SESSION_TIMEOUT', 1200);

// Configurar cookie de sessão ANTES do session_start
if (session_status() === PHP_SESSION_NONE) {
    session_name('SMARTPARK_SESSION_V8');
    session_set_cookie_params([
        'lifetime' => 0, // sessão morre ao fechar navegador
        'path' => '/',
        'domain' => '',
        'secure' => false, // colocar true quando usar HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);

    session_start();
}

// =========================
// SESSION TIMEOUT CONTROL
// =========================

// destrói sessão por inatividade
if (
    isset($_SESSION['LAST_ACTIVITY']) &&
    (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_TIMEOUT)
) {

    session_unset();
    session_destroy();

    header("Location: /smartpark/login?session_expired=1");
    exit;
}

// atualiza tempo de atividade
$_SESSION['LAST_ACTIVITY'] = time();
