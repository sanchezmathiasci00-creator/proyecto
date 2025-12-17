<?php
// Configurar zona horaria de Perú
date_default_timezone_set('America/Lima');
// Configurar encoding UTF-8
header('Content-Type: text/html; charset=utf-8');

session_start();
if (!isset($_SESSION['usuario'])) {
  header("Location: login.php");
  exit;
}
include("conexion.php");

// Obtener datos del usuario para usar en toda la aplicación
$usuario = $_SESSION['usuario'];
$rol = $_SESSION['rol'];
$id_usuario = $_SESSION['id_usuario'] ?? null; // Usar operador null coalescing por si no existe
?>