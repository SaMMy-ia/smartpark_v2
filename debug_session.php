<?php
require_once 'config.php';

echo "<h1>Session Debug</h1>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Cookies</h2>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

echo "<h2>Server</h2>";
echo "<pre>";
echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "</pre>";

echo "<p><a href='/smartpark/login'>Ir para Login</a></p>";
echo "<p><a href='/smartpark/logout.php'>Logout</a></p>";
