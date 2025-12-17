<?php
// Configurar zona horaria de Perú
date_default_timezone_set('America/Lima');

$servername = "localhost";
$username = "root";
$password = ""; // o tu contraseña
$dbname = "pppga";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
  die("Error de conexión: " . $conn->connect_error);
}

// Configurar charset UTF-8
$conn->set_charset("utf8mb4");
?>