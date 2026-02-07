<?php
require_once __DIR__ . '/../includes/auth.php';

if (!isSeniorAccountant()) {
    header('Location: /smartpark/403.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Gerando PDF...</title>
    <script>
        window.onload = function() {
            window.location.href = 'relatorios.php?official=1';
            // Note: In a real production app, we would use a library like Dompdf.
            // For now, this redirects to a print-friendly view.
        };
    </script>
</head>
<body>
    <p>Preparando documento oficial...</p>
</body>
</html>
