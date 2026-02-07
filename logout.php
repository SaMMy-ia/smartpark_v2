<?php
require_once 'config.php';
require_once 'includes/auth.php';

logoutUser();

header('Location: /smartpark/index.php');
exit;
