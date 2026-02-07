<?php
/**
 * SmartPark - Authentication & Authorization Functions
 */

require_once __DIR__ . '/../config.php';

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

/**
 * Require login - redirect to index if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /smartpark/index.php');
        exit;
    }
}

/**
 * Check if user has required role
 */
function hasRole($role) {
    return isLoggedIn() && $_SESSION['user_role'] === $role;
}

/**
 * Require specific role - redirect if not authorized
 */
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header('Location: /smartpark/403.php');
        exit;
    }
}

/**
 * Get current user data
 */
function getCurrentUser() {
    global $pdo;
    
    if (!isLoggedIn()) {
        return null;
    }
    
    $stmt = $pdo->prepare("SELECT id, nome, email, role FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Login user
 */
function loginUser($email, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT id, nome, email, senha, role FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['senha'])) {
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['nome'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        
        // Log the login
        logAction($user['id'], 'Login realizado');
        
        return true;
    }
    
    return false;
}

/**
 * Logout user
 */
function logoutUser() {
    if (isLoggedIn()) {
        logAction($_SESSION['user_id'], 'Logout realizado');
    }
    
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Get redirect URL based on user role
 */
function getRoleRedirect($role) {
    switch ($role) {
        case 'admin':
            return '/smartpark/admin/dashboard.php';
        case 'funcionario':
            return '/smartpark/funcionario/dashboard.php';
        case 'contabilista_estagiario':
        case 'contabilista_senior':
            return '/smartpark/contabilista/dashboard.php';
        case 'usuario':
            return '/smartpark/usuario/dashboard.php';
        default:
            return '/smartpark/index.php';
    }
}

/**
 * Log user action
 */
function logAction($userId, $action) {
    global $pdo;
    
    // Convert 'sistema' to NULL or 0 if needed, but the DB allows NULL for usuario_id
    $uid = ($userId === 'sistema') ? null : $userId;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO logs (usuario_id, acao) VALUES (?, ?)");
        $stmt->execute([$uid, $action]);
    } catch (PDOException $e) {
        // Silently fail - logging should not break the application
        error_log("Failed to log action: " . $e->getMessage());
    }
}

/**
 * Check if user is an accountant
 */
function isAccountant() {
    return isLoggedIn() && in_array($_SESSION['user_role'], ['contabilista_estagiario', 'contabilista_senior']);
}

/**
 * Check if user is a senior accountant
 */
function isSeniorAccountant() {
    return isLoggedIn() && $_SESSION['user_role'] === 'contabilista_senior';
}

/**
 * Check if current user has permission (utility for Phase 5 & 6)
 */
function can($permission) {
    if (!isLoggedIn()) return false;
    $role = $_SESSION['user_role'];
    
    if ($role === 'admin') return true;
    
    switch ($permission) {
        case 'view_accounting':
            return isAccountant();
        case 'edit_accounting':
            return isSeniorAccountant();
        case 'generate_official_pdf':
            return isSeniorAccountant();
        case 'approve_data':
            return isSeniorAccountant();
        case 'record_exit':
            return in_array($role, ['admin', 'funcionario']);
        default:
            return false;
    }
}
