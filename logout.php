<?php
// Configurar zona horaria de Perú
date_default_timezone_set('America/Lima');

// Configurar encoding UTF-8
header('Content-Type: text/html; charset=utf-8');

session_start();
session_destroy();
header("Location: login.php");
exit;
?>
