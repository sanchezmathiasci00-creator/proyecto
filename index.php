<?php
// Incluir el header que ya maneja la sesión, conexión y variables de usuario
include('includes/header.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel Principal - Sistema PPP</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
    }
    .card {
      border-radius: 15px;
      box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }
    /* Mantenemos el CSS del sidebar aquí ya que sidebar.php no lo incluye */
    .sidebar {
      height: 100vh;
      background-color: #343a40;
      color: white;
      padding-top: 20px;
    }
    .sidebar a {
      color: white;
      text-decoration: none;
      display: block;
      padding: 10px 20px;
      border-radius: 10px;
    }
    .sidebar a:hover {
      background-color: #495057;
    }
  </style>
</head>
<body>
<div class="container-fluid">
  <div class="row">
    <!-- SIDEBAR -->
    <?php include('includes/sidebar.php'); ?>

    <!-- CONTENIDO PRINCIPAL -->
    <div class="col-md-9 col-lg-10 p-4">
      <h2 class="mb-4">Bienvenido, <?= htmlspecialchars($usuario) ?> 👋</h2>
      
      <?php if ($rol == 'ADMIN' || $rol == 'COORDINADOR' || $rol == 'ASISTENTE'): ?>
        <!-- PANEL ADMIN -->
        <div class="row g-3">
          <div class="col-md-4">
            <div class="card p-3 text-center">
              👥 Practicantes</h5>
              <?php
                $q = $conn->query("SELECT COUNT(*) AS total FROM practicantes");
                $row = $q->fetch_assoc();
              ?>
              <h2><?= $row['total'] ?></h2>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card p-3 text-center">
              <h5>📘 Módulos</h5>
              <?php
                $q = $conn->query("SELECT COUNT(*) AS total FROM modulos");
                $row = $q->fetch_assoc();
              ?>
              <h2><?= $row['total'] ?></h2>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card p-3 text-center">
              <h5>🕒 Registros de Asistencia</h5>
              <?php
                $q = $conn->query("SELECT COUNT(*) AS total FROM asistencia");
                $row = $q->fetch_assoc();
              ?>
              <h2><?= $row['total'] ?></h2>
            </div>
          </div>
        </div>
      <?php elseif ($rol == 'PRACTICANTE'): ?>
        <!-- PANEL PRACTICANTE -->
        <div class="card p-4">
          <h4>Tu progreso en las prácticas</h4>
          <p>Desde aquí podrás registrar tu asistencia y ver tus avances.</p>
          <a href="asistencia.php" class="btn btn-primary">Registrar Asistencia</a>
          <a href="avance.php" class="btn btn-outline-secondary">Ver Avance</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include('includes/footer.php'); ?>