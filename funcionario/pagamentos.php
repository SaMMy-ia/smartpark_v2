<?php
$skipRoleCheck = true;
require_once __DIR__ . '/../includes/auth.php';
requireRole('funcionario');
require_once __DIR__ . '/../admin/pagamentos.php';
?>
