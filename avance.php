<?php 
include("includes/header.php");

// Solo permitir a practicantes
if ($_SESSION['rol'] != 'PRACTICANTE') {
  header("Location: index.php");
  exit;
}

// Ya tenemos $id_usuario, $usuario y $rol desde header.php

// Obtener el dni_practicante desde la tabla usuarios
$stmt_user = $conn->prepare("SELECT dni_practicante FROM usuarios WHERE id_usuario = ?");
$stmt_user->bind_param("i", $id_usuario);
$stmt_user->execute();
$result_user = $stmt_user->get_result();

if ($row_user = $result_user->fetch_assoc()) {
    $dni_practicante = $row_user['dni_practicante'];
    
    // Obtener datos del practicante
    $stmt = $conn->prepare("
      SELECT p.dni, p.nombres, p.apellidos, p.semestre, e.nm_especialidades, m.nm_modulos, m.horas_requeridas, p.foto
      FROM practicantes p
      LEFT JOIN especialidades e ON p.id_especialidades = e.id_especialidades
      LEFT JOIN modulos m ON p.id_modulos = m.id_modulos
      WHERE p.dni = ?
    ");
    $stmt->bind_param("i", $dni_practicante);
    $stmt->execute();
    $res = $stmt->get_result();
    $practicante = $res->fetch_assoc();

    if (!$practicante) {
        echo "⚠️ No se encontró información del practicante.";
        exit;
    }

    $dni = $practicante['dni'];
    $horas_requeridas = $practicante['horas_requeridas'] ?: 120;

    // Calcular total de horas asistidas (SOLO JORNADAS COMPLETADAS Y VÁLIDAS)
    $query = $conn->prepare("
        SELECT SUM(
            CASE 
                WHEN hora_salida IS NULL OR hora_salida = '' OR hora_salida = '00:00:00' THEN 0
                WHEN TIMESTAMPDIFF(MINUTE, hora_ingreso, hora_salida) < 0 THEN 0
                ELSE TIMESTAMPDIFF(MINUTE, hora_ingreso, hora_salida) / 60
            END
        ) AS total_horas 
        FROM asistencia 
        WHERE dni_practicante = ? 
        AND estado = 'COMPLETADO'
    ");
    $query->bind_param("i", $dni);
    $query->execute();
    $result = $query->get_result();
    $data = $result->fetch_assoc();
    $total_horas = $data['total_horas'] ? (float)$data['total_horas'] : 0.00;

    // Calcular total de días completados (SOLO JORNADAS COMPLETADAS Y VÁLIDAS)
    $query_dias = $conn->prepare("
        SELECT COUNT(*) AS total_dias 
        FROM asistencia 
        WHERE dni_practicante = ? 
        AND estado = 'COMPLETADO'
        AND hora_salida IS NOT NULL 
        AND hora_salida != '' 
        AND hora_salida != '00:00:00'
        AND TIMESTAMPDIFF(MINUTE, hora_ingreso, hora_salida) > 0
    ");
    $query_dias->bind_param("i", $dni);
    $query_dias->execute();
    $result_dias = $query_dias->get_result();
    $data_dias = $result_dias->fetch_assoc();
    $total_dias = $data_dias['total_dias'] ?: 0;

    // Calcular promedio de horas por día (SOLO JORNADAS COMPLETADAS Y VÁLIDAS)
    $query_promedio = $conn->prepare("
        SELECT AVG(
            CASE 
                WHEN hora_salida IS NULL OR hora_salida = '' OR hora_salida = '00:00:00' THEN 0
                WHEN TIMESTAMPDIFF(MINUTE, hora_ingreso, hora_salida) < 0 THEN 0
                ELSE TIMESTAMPDIFF(MINUTE, hora_ingreso, hora_salida) / 60
            END
        ) AS promedio 
        FROM asistencia 
        WHERE dni_practicante = ? 
        AND estado = 'COMPLETADO'
        AND hora_salida IS NOT NULL 
        AND hora_salida != '' 
        AND hora_salida != '00:00:00'
        AND TIMESTAMPDIFF(MINUTE, hora_ingreso, hora_salida) > 0
    ");
    $query_promedio->bind_param("i", $dni);
    $query_promedio->execute();
    $result_promedio = $query_promedio->get_result();
    $data_promedio = $result_promedio->fetch_assoc();
    $promedio_horas = $data_promedio['promedio'] ? number_format((float)$data_promedio['promedio'], 2) : '0.00';

    $porcentaje = ($horas_requeridas > 0) ? min(100, round(($total_horas / $horas_requeridas) * 100, 2)) : 0;
    $estado = ($porcentaje >= 100) ? 'COMPLETADO' : 'EN CURSO';
    $horas_restantes = max(0, $horas_requeridas - $total_horas);

} else {
    echo "⚠️ No se encontró el usuario en el sistema.";
    exit;
}

// Mostrar mensajes de éxito/error desde parámetros GET
if (isset($_GET['success'])) {
    $success_message = htmlspecialchars($_GET['success']);
}
if (isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Mi Avance - Sistema PPP</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
    }
    .card {
      border-radius: 15px;
      box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }
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
    .progress { 
      height: 25px; 
      font-weight: bold;
    }
    .stat-card {
      transition: transform 0.2s;
    }
    .stat-card:hover {
      transform: translateY(-5px);
    }
    
    /* Estilos para la foto de perfil */
    .profile-photo-container {
      position: relative;
      display: inline-block;
      margin-right: 15px;
    }
    .profile-photo {
      width: 125px;
      height: 125px;
      object-fit: cover;
      border-radius: 50%;
      border: 4px solid #007bff;
      box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    .edit-photo-btn {
      position: absolute;
      bottom: 5px;
      right: 5px;
      background: #007bff;
      color: white;
      border-radius: 50%;
      width: 35px;
      height: 35px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      border: 3px solid white;
      transition: all 0.3s;
    }
    .edit-photo-btn:hover {
      background: #0056b3;
      transform: scale(1.1);
    }
    
    /* Modal para subir foto */
    .modal-foto .modal-dialog {
      max-width: 500px;
    }
    .preview-container {
      width: 150px;
      height: 150px;
      margin: 0 auto 20px;
      border: 2px dashed #ddd;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
    }
    .preview-img {
      max-width: 100%;
      max-height: 100%;
      object-fit: cover;
    }
  </style>
</head>
<body>
<div class="container-fluid">
  <div class="row">
    <?php include("includes/sidebar.php"); ?>

    <!-- CONTENIDO PRINCIPAL -->
    <div class="col-md-9 col-lg-10 p-4">
      <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <?= $success_message ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php elseif (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?= $error_message ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <h2 class="mb-4">
        <div class="profile-photo-container">
          <?php if (!empty($practicante['foto']) && file_exists($practicante['foto'])): ?>
            <img src="<?= $practicante['foto'] ?>?t=<?= time() ?>" class="profile-photo" alt="Foto de perfil">
          <?php else: ?>
            <img src="image/perfil.png?t=<?= time() ?>" class="profile-photo" alt="Foto de perfil por defecto">
          <?php endif; ?>
          
          <div class="edit-photo-btn" data-bs-toggle="modal" data-bs-target="#modalSubirFoto">
            <i class="fas fa-pencil-alt"></i>
          </div>
        </div>
        
        <?= htmlspecialchars($practicante['nombres'] . ' ' . $practicante['apellidos']) ?>
      </h2>

      <!-- ESTADÍSTICAS RÁPIDAS -->
      <div class="row g-3 mb-4">
        <div class="col-md-3">
          <div class="card p-3 text-center stat-card">
            <h5>📊 Progreso</h5>
            <h2 class="<?= ($porcentaje >= 100) ? 'text-success' : 'text-primary' ?>"><?= $porcentaje ?>%</h2>
            <small><?= number_format($total_horas, 1) ?> / <?= $horas_requeridas ?> horas</small>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card p-3 text-center stat-card">
            <h5>🕒 Horas Totales</h5>
            <h2 class="text-info"><?= number_format($total_horas, 1) ?></h2>
            <small>Acumuladas</small>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card p-3 text-center stat-card">
            <h5>📅 Días Completados</h5>
            <h2 class="text-warning"><?= $total_dias ?></h2>
            <small>Jornadas trabajadas</small>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card p-3 text-center stat-card">
            <h5>⏱️ Promedio/Día</h5>
            <h2 class="text-success"><?= $promedio_horas ?></h2>
            <small>Horas por día</small>
          </div>
        </div>
      </div>

      <!-- INFORMACIÓN DEL PRACTICANTE Y PROGRESO -->
      <div class="row g-4">
        <!-- INFORMACIÓN PERSONAL -->
        <div class="col-md-6">
          <div class="card p-4 h-100">
            <h4 class="mb-3">📋 Información Personal</h4>
            <div class="row">
              <div class="col-12 mb-2">
                <strong>Nombre completo:</strong><br>
                <?= htmlspecialchars($practicante['nombres'] . ' ' . $practicante['apellidos']) ?>
              </div>
              <div class="col-6 mb-2">
                <strong>DNI:</strong><br>
                <?= htmlspecialchars($practicante['dni']) ?>
              </div>
              <div class="col-6 mb-2">
                <strong>Semestre:</strong><br>
                <?= htmlspecialchars($practicante['semestre']) ?>
              </div>
              <div class="col-12 mb-2">
                <strong>Especialidad:</strong><br>
                <?= htmlspecialchars($practicante['nm_especialidades']) ?>
              </div>
              <div class="col-12">
                <strong>Módulo Actual:</strong><br>
                <?= htmlspecialchars($practicante['nm_modulos']) ?>
              </div>
            </div>
          </div>
        </div>

        <!-- BARRA DE PROGRESO -->
        <div class="col-md-6">
          <div class="card p-4 h-100">
            <h4 class="mb-3">🎯 Progreso del Módulo</h4>
            
            <div class="mb-3">
              <div class="d-flex justify-content-between mb-2">
                <span>Completado:</span>
                <span><strong><?= $porcentaje ?>%</strong></span>
              </div>
              <div class="progress">
                <div class="progress-bar <?= ($estado == 'COMPLETADO') ? 'bg-success' : 'bg-info' ?>" 
                     style="width: <?= $porcentaje ?>%;"
                     role="progressbar" 
                     aria-valuenow="<?= $porcentaje ?>" 
                     aria-valuemin="0" 
                     aria-valuemax="100">
                  <?= $porcentaje ?>%
                </div>
              </div>
            </div>

            <div class="row text-center">
              <div class="col-6">
                <div class="border rounded p-2 bg-light">
                  <h5 class="text-success mb-1"><?= number_format($total_horas, 1) ?></h5>
                  <small>Horas completadas</small>
                </div>
              </div>
              <div class="col-6">
                <div class="border rounded p-2 bg-light">
                  <h5 class="text-warning mb-1"><?= number_format($horas_restantes, 1) ?></h5>
                  <small>Horas restantes</small>
                </div>
              </div>
            </div>

            <div class="mt-3 text-center">
              <span class="badge <?= ($estado == 'COMPLETADO') ? 'bg-success' : 'bg-warning text-dark' ?> fs-6 p-2">
                <?php if ($estado == 'COMPLETADO'): ?>
                  ✅ MÓDULO COMPLETADO
                <?php else: ?>
                  📚 MÓDULO EN CURSO
                <?php endif; ?>
              </span>
            </div>

            <?php if ($estado != 'COMPLETADO'): ?>
              <div class="mt-3 alert alert-info">
                <small>
                  <strong>💡 Información:</strong> 
                  Necesitas completar <?= number_format($horas_restantes, 1) ?> horas más para finalizar este módulo.
                </small>
              </div>
            <?php else: ?>
              <div class="mt-3 alert alert-success">
                <small>
                  <strong>🎉 ¡Felicidades!</strong> 
                  Has completado exitosamente todas las horas requeridas para este módulo.
                </small>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- DETALLES ADICIONALES -->
      <div class="row mt-4">
        <div class="col-12">
          <div class="card p-4">
            <h4 class="mb-3">📈 Resumen Detallado</h4>
            <div class="row">
              <div class="col-md-4">
                <div class="text-center p-3">
                  <h3 class="text-primary"><?= $horas_requeridas ?></h3>
                  <p class="mb-0"><strong>Horas Requeridas</strong></p>
                  <small>Total del módulo</small>
                </div>
              </div>
              <div class="col-md-4">
                <div class="text-center p-3">
                  <h3 class="text-success"><?= number_format($total_horas, 1) ?></h3>
                  <p class="mb-0"><strong>Horas Realizadas</strong></p>
                  <small><?= $porcentaje ?>% del total</small>
                </div>
              </div>
              <div class="col-md-4">
                <div class="text-center p-3">
                  <h3 class="text-warning"><?= number_format($horas_restantes, 1) ?></h3>
                  <p class="mb-0"><strong>Horas Pendientes</strong></p>
                  <small>Para completar</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- HISTORIAL DE TAREAS RECIENTES -->
<div class="row mt-4">
  <div class="col-12">
    <div class="card p-4">
      <h4 class="mb-3">
        <i class="fas fa-clipboard-list me-2"></i>Tareas Realizadas Recientemente
      </h4>
      
      <?php
      // Obtener últimas tareas realizadas
      $query_tareas = $conn->prepare("
          SELECT fecha, tareas_realizadas, hora_ingreso, hora_salida 
          FROM asistencia 
          WHERE dni_practicante = ? 
          AND tareas_realizadas IS NOT NULL 
          AND tareas_realizadas != ''
          ORDER BY fecha DESC 
          LIMIT 5
      ");
      $query_tareas->bind_param("i", $dni);
      $query_tareas->execute();
      $result_tareas = $query_tareas->get_result();
      
      if ($result_tareas->num_rows > 0): ?>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead class="table-light">
              <tr>
                <th>Fecha</th>
                <th>Jornada</th>
                <th>Tareas Realizadas</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($tarea = $result_tareas->fetch_assoc()): ?>
                <tr>
                  <td style="width: 15%;">
                    <strong><?= date("d/m/Y", strtotime($tarea['fecha'])) ?></strong>
                  </td>
                  <td style="width: 20%;">
                    <span class="badge bg-info">
                      <?= date("H:i", strtotime($tarea['hora_ingreso'])) ?> - 
                      <?= date("H:i", strtotime($tarea['hora_salida'])) ?>
                    </span>
                  </td>
                  <td>
                    <div class="tareas-content">
                      <?= nl2br(htmlspecialchars($tarea['tareas_realizadas'])) ?>
                    </div>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="text-center py-4 text-muted">
          <i class="fas fa-clipboard fa-3x mb-3"></i>
          <p>No hay tareas registradas aún.</p>
          <p class="small">Las tareas que registres al marcar salida aparecerán aquí.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<style>
.tareas-content {
  white-space: pre-line;
  line-height: 1.6;
  max-height: 100px;
  overflow-y: auto;
  padding: 8px;
  background-color: #f8f9fa;
  border-radius: 5px;
  border-left: 4px solid #007bff;
}

.tareas-content::-webkit-scrollbar {
  width: 6px;
}

.tareas-content::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.tareas-content::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 3px;
}

.tareas-content::-webkit-scrollbar-thumb:hover {
  background: #555;
}
</style>

<!-- Modal para subir foto -->
<div class="modal fade modal-foto" id="modalSubirFoto" tabindex="-1" aria-labelledby="modalSubirFotoLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalSubirFotoLabel">📷 Subir Foto de Perfil</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="upload.php" enctype="multipart/form-data">
        <div class="modal-body">
          <div class="preview-container">
            <img id="previewFoto" src="<?php echo (!empty($practicante['foto']) && file_exists($practicante['foto'])) ? $practicante['foto'] . '?t=' . time() : 'image/perfil.png?t=' . time(); ?>" 
                 class="preview-img" alt="Vista previa">
          </div>
          
          <div class="mb-3">
            <label for="foto_perfil" class="form-label">Seleccionar imagen:</label>
            <input type="file" class="form-control" id="foto_perfil" name="foto_perfil" accept="image/*" required>
            <div class="form-text">
              Formatos permitidos: JPG, JPEG, PNG, GIF. Tamaño máximo: 2MB
            </div>
          </div>
          
          <div class="alert alert-info">
            <small>
              <i class="fas fa-info-circle"></i> La foto será visible en tu perfil.
            </small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Subir Foto</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include("includes/footer.php"); ?>

<script>
// Mostrar vista previa de la foto seleccionada
document.getElementById('foto_perfil').addEventListener('change', function(e) {
  const reader = new FileReader();
  reader.onload = function(e) {
    document.getElementById('previewFoto').src = e.target.result;
  }
  reader.readAsDataURL(this.files[0]);
});
</script>