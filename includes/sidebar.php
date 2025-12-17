<?php
// Este archivo asume que $usuario y $rol están definidos por header.php
?>
<div class="col-md-3 col-lg-2 sidebar">
  <h4 class="text-center mb-4">Sistema PPP</h4>
  <p class="text-center">Usuario: <strong><?= htmlspecialchars($usuario) ?></strong></p>
  <p class="text-center"><span class="badge bg-primary"><?= htmlspecialchars($rol) ?></span></p>
  <hr>
  <a href="index.php">🏠 Dashboard</a>
  
  <?php if ($rol == 'ADMIN'): ?>
    <a href="admin_usuarios.php">👑 Administrar Usuarios</a>
    <a href="admin_practicantes.php">👥 Gestionar Practicantes</a>
  <?php endif; ?>
  
  <?php if ($rol == 'ADMIN' || $rol == 'COORDINADOR' || $rol == 'ASISTENTE'): ?>
    <a href="panel_asistencias.php">🕒 Reportes de Asistencia</a>
  <?php endif; ?>
  
  <?php if ($rol == 'PRACTICANTE'): ?>
    <a href="asistencia.php">🕒 Registrar Asistencia</a>
    <a href="avance.php">📊 Mi Avance</a>
  <?php endif; ?>
  
  <hr>
  <a href="logout.php" class="text-danger">🚪 Cerrar Sesión</a>
</div>