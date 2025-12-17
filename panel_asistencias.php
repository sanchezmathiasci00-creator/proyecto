<?php
// Incluir el header que ya maneja la sesión, conexión y variables de usuario
include('includes/header.php');

// Solo permitir a administradores, coordinadores y asistentes
if ($_SESSION['rol'] != 'ADMIN' && $_SESSION['rol'] != 'COORDINADOR' && $_SESSION['rol'] != 'ASISTENTE') {
  header("Location: index.php");
  exit;
}

// Obtener lista de practicantes
$practicantes = $conn->query("
    SELECT 
        p.dni,
        CONCAT(p.nombres, ' ', p.apellidos) as nombre_completo,
        e.nm_especialidades as especialidad,
        m.nm_modulos as modulo,
        p.semestre
    FROM practicantes p
    LEFT JOIN especialidades e ON p.id_especialidades = e.id_especialidades
    LEFT JOIN modulos m ON p.id_modulos = m.id_modulos
    ORDER BY p.nombres, p.apellidos
");

// Si se seleccionó un practicante, mostrar su reporte
$practicante_seleccionado = null;
$asistencias = [];
$estadisticas = [];

if (isset($_GET['dni_practicante']) && !empty($_GET['dni_practicante'])) {
    $dni_practicante = $_GET['dni_practicante'];
    
    // Obtener datos del practicante seleccionado
    $stmt_practicante = $conn->prepare("
        SELECT 
            p.dni,
            CONCAT(p.nombres, ' ', p.apellidos) as nombre_completo,
            e.nm_especialidades as especialidad,
            m.nm_modulos as modulo,
            m.horas_requeridas,
            p.semestre
        FROM practicantes p
        LEFT JOIN especialidades e ON p.id_especialidades = e.id_especialidades
        LEFT JOIN modulos m ON p.id_modulos = m.id_modulos
        WHERE p.dni = ?
    ");
    $stmt_practicante->bind_param("i", $dni_practicante);
    $stmt_practicante->execute();
    $practicante_seleccionado = $stmt_practicante->get_result()->fetch_assoc();

    // Parámetros de filtro para el reporte
    $fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : date('Y-m-01');
    $fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : date('Y-m-d');

    // Obtener asistencias del practicante (¡AGREGADO tareas_realizadas!)
    $sql_asistencias = "
        SELECT 
            a.fecha,
            a.hora_ingreso,
            a.hora_salida,
            a.horas_dia,
            a.estado,
            a.tareas_realizadas,
            m.nm_modulos as modulo
        FROM asistencia a
        INNER JOIN modulos m ON a.id_modulo = m.id_modulos
        WHERE a.dni_practicante = ? 
        AND a.fecha BETWEEN ? AND ?
        ORDER BY a.fecha DESC, a.hora_ingreso DESC
    ";
    
    $stmt_asistencias = $conn->prepare($sql_asistencias);
    $stmt_asistencias->bind_param("iss", $dni_practicante, $fecha_desde, $fecha_hasta);
    $stmt_asistencias->execute();
    $asistencias = $stmt_asistencias->get_result();

    // Obtener estadísticas del practicante (SOLO JORNADAS COMPLETADAS Y VÁLIDAS)
    $sql_stats = "
        SELECT 
            COUNT(*) as total_asistencias,
            SUM(
                CASE 
                    WHEN hora_salida IS NULL OR hora_salida = '' OR hora_salida = '00:00:00' THEN 0
                    WHEN TIMESTAMPDIFF(MINUTE, hora_ingreso, hora_salida) < 0 THEN 0
                    ELSE TIMESTAMPDIFF(MINUTE, hora_ingreso, hora_salida) / 60
                END
            ) as total_horas,
            AVG(
                CASE 
                    WHEN hora_salida IS NULL OR hora_salida = '' OR hora_salida = '00:00:00' THEN 0
                    WHEN TIMESTAMPDIFF(MINUTE, hora_ingreso, hora_salida) < 0 THEN 0
                    ELSE TIMESTAMPDIFF(MINUTE, hora_ingreso, hora_salida) / 60
                END
            ) as promedio_horas,
            MIN(fecha) as primera_asistencia,
            MAX(fecha) as ultima_asistencia
        FROM asistencia 
        WHERE dni_practicante = ? 
        AND fecha BETWEEN ? AND ?
        AND estado = 'COMPLETADO'
    ";
    
    $stmt_stats = $conn->prepare($sql_stats);
    $stmt_stats->bind_param("iss", $dni_practicante, $fecha_desde, $fecha_hasta);
    $stmt_stats->execute();
    $estadisticas = $stmt_stats->get_result()->fetch_assoc();

    // Calcular progreso del módulo
    $horas_requeridas = $practicante_seleccionado['horas_requeridas'] ?: 120;
    $total_horas = $estadisticas['total_horas'] ?: 0;
    $porcentaje_progreso = $horas_requeridas > 0 ? min(100, ($total_horas / $horas_requeridas) * 100) : 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel de Asistencias - Sistema PPP</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <style>
    body {
      background-color: #f8f9fa;
    }
    .card {
      border-radius: 15px;
      box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }
    
    /* ESTILOS DEL SIDEBAR */
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
    
    .practicante-card {
      transition: transform 0.2s, box-shadow 0.2s;
      cursor: pointer;
    }
    .practicante-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    .practicante-seleccionado {
      border: 3px solid #007bff;
      background-color: #f8f9ff;
    }
    .stat-card {
      transition: transform 0.2s;
    }
    .stat-card:hover {
      transform: translateY(-3px);
    }
    .progress {
      height: 20px;
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
      <h2 class="mb-4">Reportes de Asistencia por Practicante 📊</h2>

      <!-- SELECCIÓN DE PRACTICANTE -->
      <div class="card p-4 mb-4">
        <h4 class="mb-3">👥 Seleccionar Practicante</h4>
        <p class="text-muted">Selecciona un practicante para ver su reporte de asistencia</p>
        
        <div class="row g-3">
          <?php while ($practicante = $practicantes->fetch_assoc()): ?>
            <div class="col-md-6 col-lg-4">
              <div class="card practicante-card <?= isset($practicante_seleccionado) && $practicante_seleccionado['dni'] == $practicante['dni'] ? 'practicante-seleccionado' : '' ?>"
                   onclick="seleccionarPracticante(<?= $practicante['dni'] ?>)">
                <div class="card-body">
                  <h5 class="card-title"><?= htmlspecialchars($practicante['nombre_completo']) ?></h5>
                  <p class="card-text mb-1">
                    <small class="text-muted">DNI: <?= $practicante['dni'] ?></small>
                  </p>
                  <p class="card-text mb-1">
                    <strong>Especialidad:</strong> <?= htmlspecialchars($practicante['especialidad']) ?>
                  </p>
                  <p class="card-text mb-1">
                    <strong>Módulo:</strong> <?= htmlspecialchars($practicante['modulo']) ?>
                  </p>
                  <p class="card-text">
                    <strong>Semestre:</strong> <?= htmlspecialchars($practicante['semestre']) ?>
                  </p>
                  <?php if (isset($practicante_seleccionado) && $practicante_seleccionado['dni'] == $practicante['dni']): ?>
                    <span class="badge bg-success">Seleccionado</span>
                  <?php else: ?>
                    <span class="badge bg-secondary">Click para seleccionar</span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      </div>

      <?php if (isset($practicante_seleccionado)): ?>
        <!-- REPORTE DEL PRACTICANTE SELECCIONADO -->
        <div class="card p-4 mb-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>📋 Reporte de: <?= htmlspecialchars($practicante_seleccionado['nombre_completo']) ?></h4>
            <div>
              <button class="btn btn-success" onclick="generarReportePDF()">
                <i class="fas fa-file-pdf"></i> Generar PDF
              </button>
              <a href="panel_asistencias.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cambiar Practicante
              </a>
            </div>
          </div>

          <!-- FILTROS PARA EL REPORTE -->
          <form method="GET" class="row g-3 mb-4">
            <input type="hidden" name="dni_practicante" value="<?= $practicante_seleccionado['dni'] ?>">
            <div class="col-md-4">
              <label class="form-label">Fecha Desde</label>
              <input type="date" name="fecha_desde" class="form-control" value="<?= $fecha_desde ?? date('Y-m-01') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Fecha Hasta</label>
              <input type="date" name="fecha_hasta" class="form-control" value="<?= $fecha_hasta ?? date('Y-m-d') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">&nbsp;</label>
              <div>
                <button type="submit" class="btn btn-primary">Aplicar Filtros</button>
                <a href="panel_asistencias.php?dni_practicante=<?= $practicante_seleccionado['dni'] ?>" class="btn btn-secondary">Restablecer</a>
              </div>
            </div>
          </form>

          <!-- INFORMACIÓN DEL PRACTICANTE -->
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <div class="card p-3">
                <h5>Información del Practicante</h5>
                <table class="table table-sm">
                  <tr>
                    <td><strong>Nombre:</strong></td>
                    <td><?= htmlspecialchars($practicante_seleccionado['nombre_completo']) ?></td>
                  </tr>
                  <tr>
                    <td><strong>DNI:</strong></td>
                    <td><?= $practicante_seleccionado['dni'] ?></td>
                  </tr>
                  <tr>
                    <td><strong>Especialidad:</strong></td>
                    <td><?= htmlspecialchars($practicante_seleccionado['especialidad']) ?></td>
                  </tr>
                  <tr>
                    <td><strong>Módulo:</strong></td>
                    <td><?= htmlspecialchars($practicante_seleccionado['modulo']) ?></td>
                  </tr>
                  <tr>
                    <td><strong>Semestre:</strong></td>
                    <td><?= htmlspecialchars($practicante_seleccionado['semestre']) ?></td>
                  </tr>
                </table>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card p-3">
                <h5>Progreso del Módulo</h5>
                <div class="text-center mb-3">
                  <h2 class="<?= $porcentaje_progreso >= 100 ? 'text-success' : 'text-primary' ?>">
                    <?= number_format($porcentaje_progreso, 1) ?>%
                  </h2>
                  <div class="progress">
                    <div class="progress-bar <?= $porcentaje_progreso >= 100 ? 'bg-success' : 'bg-primary' ?>" 
                         style="width: <?= $porcentaje_progreso ?>%">
                    </div>
                  </div>
                </div>
                <p class="mb-1"><strong>Horas completadas:</strong> <?= number_format($total_horas, 1) ?></p>
                <p class="mb-1"><strong>Horas requeridas:</strong> <?= $horas_requeridas ?></p>
                <p class="mb-0"><strong>Horas restantes:</strong> <?= number_format(max(0, $horas_requeridas - $total_horas), 1) ?></p>
              </div>
            </div>
          </div>

          <!-- ESTADÍSTICAS -->
          <div class="row g-3 mb-4">
            <div class="col-md-3">
              <div class="card p-3 text-center stat-card">
                <h5><i class="fas fa-calendar-check"></i> Total Asistencias</h5>
                <h2 class="text-primary"><?= $estadisticas['total_asistencias'] ?? 0 ?></h2>
                <small>Registros en el período</small>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card p-3 text-center stat-card">
                <h5><i class="fas fa-clock"></i> Total Horas</h5>
                <h2 class="text-success"><?= number_format($estadisticas['total_horas'] ?? 0, 1) ?></h2>
                <small>Horas acumuladas</small>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card p-3 text-center stat-card">
                <h5><i class="fas fa-chart-line"></i> Promedio/Día</h5>
                <h2 class="text-warning"><?= number_format($estadisticas['promedio_horas'] ?? 0, 2) ?></h2>
                <small>Horas por día</small>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card p-3 text-center stat-card">
                <h5><i class="fas fa-calendar"></i> Período</h5>
                <h2 class="text-info">
                  <?= date('d/m/Y', strtotime($fecha_desde)) ?><br>
                  <small>a</small><br>
                  <?= date('d/m/Y', strtotime($fecha_hasta)) ?>
                </h2>
              </div>
            </div>
          </div>

          <!-- TABLA DE ASISTENCIAS -->
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="mb-0">Historial de Asistencias</h5>
              <button class="btn btn-sm btn-info" type="button" data-bs-toggle="collapse" data-bs-target="#tablaTareas">
                <i class="fas fa-eye me-1"></i>Ver Tareas
              </button>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-striped table-hover">
                  <thead>
                    <tr>
                      <th>Fecha</th>
                      <th>Módulo</th>
                      <th>Hora Entrada</th>
                      <th>Hora Salida</th>
                      <th>Horas</th>
                      <th>Estado</th>
                      <th class="collapse" id="tablaTareas">Tareas Realizadas</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if ($asistencias->num_rows > 0): ?>
                      <?php while ($asistencia = $asistencias->fetch_assoc()): ?>
                        <tr>
                          <td><?= date('d/m/Y', strtotime($asistencia['fecha'])) ?></td>
                          <td><?= htmlspecialchars($asistencia['modulo']) ?></td>
                          <td><?= date('H:i', strtotime($asistencia['hora_ingreso'])) ?></td>
                          <td><?= $asistencia['hora_salida'] ? date('H:i', strtotime($asistencia['hora_salida'])) : '-' ?></td>
                          <td>
                            <span class="badge bg-info"><?= number_format($asistencia['horas_dia'], 2) ?>h</span>
                          </td>
                          <td>
                            <span class="badge <?= $asistencia['estado'] == 'COMPLETADO' ? 'bg-success' : 'bg-warning' ?>">
                              <?= $asistencia['estado'] == 'COMPLETADO' ? 'Completado' : 'En Curso' ?>
                            </span>
                          </td>
                          <td class="collapse" id="tablaTareas">
                            <?php if (!empty($asistencia['tareas_realizadas'])): ?>
                              <button type="button" class="btn btn-sm btn-outline-info" 
                                      data-bs-toggle="popover" 
                                      title="Tareas del <?= date('d/m/Y', strtotime($asistencia['fecha'])) ?>"
                                      data-bs-content="<?= htmlspecialchars($asistencia['tareas_realizadas']) ?>">
                                <i class="fas fa-list"></i> Ver
                              </button>
                            <?php else: ?>
                              <span class="text-muted">-</span>
                            <?php endif; ?>
                          </td>
                        </tr>
                      <?php endwhile; ?>
                    <?php else: ?>
                      <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                          <i class="fas fa-inbox fa-2x mb-3"></i>
                          <p>No se encontraron registros de asistencia para este período</p>
                        </td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          
          <script>
          // Inicializar popovers de Bootstrap
          $(document).ready(function(){
              $('[data-bs-toggle="popover"]').popover({
                  trigger: 'hover',
                  placement: 'left',
                  html: true,
                  sanitize: false,
                  content: function() {
                      return '<div style="max-height: 200px; overflow-y: auto; white-space: pre-line;">' + 
                            $(this).data('bs-content') + '</div>';
                  }
              });
          });
          </script>
        </div> <!-- Cierre del div del reporte del practicante -->
      <?php else: ?>
        <!-- MENSAJE CUANDO NO HAY PRACTICANTE SELECCIONADO -->
        <div class="card p-5 text-center">
          <div class="card-body">
            <i class="fas fa-user-clock fa-3x text-muted mb-3"></i>
            <h4 class="text-muted">Selecciona un practicante</h4>
            <p class="text-muted">Haz clic en una tarjeta de practicante para ver su reporte de asistencia</p>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function seleccionarPracticante(dni) {
  window.location.href = `panel_asistencias.php?dni_practicante=${dni}`;
}

function generarReportePDF() {
  const urlParams = new URLSearchParams(window.location.search);
  window.open(`generar_reporte_practicante.php?${urlParams.toString()}`, '_blank');
}
</script>

<?php include('includes/footer.php'); ?>