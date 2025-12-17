<?php 
include("includes/header.php");

// Solo permitir a practicantes
if ($_SESSION['rol'] != 'PRACTICANTE') {
  header("Location: index.php");
  exit;
}

// Ya tenemos $usuario, $rol y $id_usuario desde header.php
$mensaje = "";

// Obtener el dni_practicante desde la tabla usuarios
$stmt_user = $conn->prepare("SELECT dni_practicante FROM usuarios WHERE id_usuario = ?");
$stmt_user->bind_param("i", $id_usuario);
$stmt_user->execute();
$result_user = $stmt_user->get_result();

if ($row_user = $result_user->fetch_assoc()) {
    $dni_practicante = $row_user['dni_practicante'];
    
    // Obtener los datos del practicante incluyendo el id_modulo
    $stmt = $conn->prepare("SELECT dni, nombres, id_modulos FROM practicantes WHERE dni = ?");
    $stmt->bind_param("i", $dni_practicante);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $dni_practicante = $row['dni'];
        $nombre_practicante = $row['nombres'];
        $id_modulo = $row['id_modulos'];
        
        // Verificar asistencia del día actual
        $fecha_hoy = date("Y-m-d");
        $stmt_asistencia = $conn->prepare("SELECT * FROM asistencia WHERE dni_practicante = ? AND fecha = ?");
        $stmt_asistencia->bind_param("is", $dni_practicante, $fecha_hoy);
        $stmt_asistencia->execute();
        $result_asistencia = $stmt_asistencia->get_result();
        $asistencia = $result_asistencia->fetch_assoc();

        // Procesar marcado de entrada
        if (isset($_POST['entrada'])) {
            $hora_ingreso = date("H:i:s");
            
            // Verificar que el practicante tenga un módulo asignado
            if (!$id_modulo) {
                $mensaje = "❌ Error: No tienes un módulo asignado. Contacta al administrador.";
            } else {
                $stmt_entrada = $conn->prepare("INSERT INTO asistencia (dni_practicante, id_modulo, fecha, hora_ingreso, estado) VALUES (?, ?, ?, ?, 'EN_CURSO')");
                $stmt_entrada->bind_param("iiss", $dni_practicante, $id_modulo, $fecha_hoy, $hora_ingreso);
                if ($stmt_entrada->execute()) {
                    $mensaje = "✅ Entrada registrada correctamente a las $hora_ingreso";
                    header("Location: asistencia.php");
                    exit;
                } else {
                    $mensaje = "❌ Error al registrar la entrada: " . $conn->error;
                }
            }
        }

        // Procesar marcado de salida
        if (isset($_POST['salida'])) {
            // Verificar que existe un registro de entrada para hoy
            if ($asistencia && $asistencia['hora_ingreso']) {
                // Considerar '00:00:00' como "sin hora de salida"
                $tiene_salida = ($asistencia['hora_salida'] && $asistencia['hora_salida'] != '00:00:00');
                
                if ($tiene_salida) {
                    $mensaje = "❌ Ya has marcado salida hoy a las " . $asistencia['hora_salida'];
                } else {
                    $hora_salida = date("H:i:s");
                    
                    $stmt_salida = $conn->prepare("UPDATE asistencia SET hora_salida = ?, estado = 'COMPLETADO' WHERE dni_practicante = ? AND fecha = ?");
                    $stmt_salida->bind_param("sis", $hora_salida, $dni_practicante, $fecha_hoy);
                    if ($stmt_salida->execute()) {
                        $mensaje = "✅ Salida registrada correctamente a las $hora_salida";
                        header("Location: asistencia.php");
                        exit;
                    } else {
                        $mensaje = "❌ Error al registrar la salida: " . $conn->error;
                    }
                }
            } else {
                $mensaje = "❌ Error: Primero debes marcar entrada antes de poder marcar salida";
            }
        }
        
    } else {
        echo "⚠️ No se encontró el practicante asociado a este usuario.";
        exit;
    }
} else {
    echo "⚠️ No se encontró el usuario en el sistema.";
    exit;
}

// Función para verificar si tiene hora de salida válida
function tieneSalidaValida($asistencia) {
    if (!$asistencia || !isset($asistencia['hora_salida'])) {
        return false;
    }
    
    // Considerar NULL, vacío, o '00:00:00' como "sin hora de salida"
    return !(empty($asistencia['hora_salida']) || $asistencia['hora_salida'] == '00:00:00');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Asistencia - Sistema PPP</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Agregar Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <!-- Agregar jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
    .progress { height: 25px; }
  </style>
</head>
<body>
<div class="container-fluid">
  <div class="row">
    <?php include("includes/sidebar.php"); ?>

    <!-- CONTENIDO PRINCIPAL -->
    <div class="col-md-9 col-lg-10 p-4">
      <h2 class="mb-4">Registro de Asistencia</h2>

      <?php if ($mensaje): ?>
        <div class="alert alert-info"><?= $mensaje ?></div>
      <?php endif; ?>

      <!-- TARJETA DE REGISTRO DE ASISTENCIA -->
      <div class="card p-4 mb-4">
        <div class="row">
          <div class="col-md-8">
            <h4 class="mb-3">Marcar Asistencia</h4>
            <p><strong>Practicante:</strong> <?= $nombre_practicante ?></p>
            <p><strong>Fecha de hoy:</strong> <?= date("d/m/Y") ?></p>
            <p><strong>Módulo:</strong> 
              <?php 
                if ($id_modulo) {
                  $stmt_mod = $conn->prepare("SELECT nm_modulos FROM modulos WHERE id_modulos = ?");
                  $stmt_mod->bind_param("i", $id_modulo);
                  $stmt_mod->execute();
                  $res_mod = $stmt_mod->get_result();
                  $modulo = $res_mod->fetch_assoc();
                  echo $modulo ? $modulo['nm_modulos'] : 'Módulo no encontrado';
                } else {
                  echo '<span class="text-danger">No asignado</span>';
                }
              ?>
            </p>
          </div>
          <div class="col-md-4 text-center">
            <form method="POST" class="d-flex flex-column gap-3">
              <button name="entrada" class="btn btn-success btn-lg py-3" 
                      <?= (($asistencia && $asistencia['hora_ingreso']) || !$id_modulo) ? 'disabled' : '' ?>>
                🟢 Marcar Entrada
              </button>
              
              <button name="salida" class="btn btn-danger btn-lg py-3" id="btnSalida"
                      <?= ($asistencia && $asistencia['hora_ingreso'] && !tieneSalidaValida($asistencia) && $id_modulo) ? '' : 'disabled' ?>>
                <i class="fas fa-sign-out-alt me-2"></i>🔴 Marcar Salida
              </button>
            </form>
          </div>
        </div>

        <?php if (!$id_modulo): ?>
          <div class="mt-3">
            <small class="text-danger">⚠️ No puedes marcar asistencia sin un módulo asignado</small>
          </div>
        <?php endif; ?>
        
        <?php if ($asistencia): ?>
          <div class="mt-3 pt-3 border-top">
            <p class="mb-1"><strong>Estado actual:</strong></p>
            <?php if ($asistencia['hora_ingreso'] && !tieneSalidaValida($asistencia)): ?>
              <span class="badge bg-warning text-dark fs-6">
                🟡 Entrada marcada a las <?= date("H:i:s", strtotime($asistencia['hora_ingreso'])) ?> - Puedes marcar salida
              </span>
            <?php elseif ($asistencia['hora_ingreso'] && tieneSalidaValida($asistencia)): ?>
              <span class="badge bg-success fs-6">
                ✅ Jornada completada 
                (Entrada: <?= date("H:i:s", strtotime($asistencia['hora_ingreso'])) ?> - 
                Salida: <?= date("H:i:s", strtotime($asistencia['hora_salida'])) ?>)
              </span>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- ESTADÍSTICAS RÁPIDAS -->
      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <div class="card p-3 text-center">
            <h5>📅 Asistencias Hoy</h5>
            <?php
              $q_hoy = $conn->prepare("SELECT COUNT(*) AS total FROM asistencia WHERE dni_practicante = ? AND fecha = ?");
              $q_hoy->bind_param("is", $dni_practicante, $fecha_hoy);
              $q_hoy->execute();
              $res_hoy = $q_hoy->get_result();
              $row_hoy = $res_hoy->fetch_assoc();
            ?>
            <h2><?= $row_hoy['total'] ?></h2>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card p-3 text-center">
            <h5>🕒 Total Horas</h5>
            <?php
              $q_total = $conn->prepare("
                SELECT SUM(
                    CASE 
                        WHEN hora_salida IS NULL OR hora_salida = '' OR hora_salida = '00:00:00' THEN 0
                        WHEN TIMESTAMPDIFF(MINUTE, hora_ingreso, hora_salida) < 0 THEN 0
                        ELSE TIMESTAMPDIFF(MINUTE, hora_ingreso, hora_salida) / 60
                    END
                ) AS total 
                FROM asistencia 
                WHERE dni_practicante = ? 
                AND estado = 'COMPLETADO'
              ");
              $q_total->bind_param("i", $dni_practicante);
              $q_total->execute();
              $res_total = $q_total->get_result();
              $row_total = $res_total->fetch_assoc();
              $total_horas = $row_total['total'] ?: 0;
            ?>
            <h2><?= number_format($total_horas, 1) ?></h2>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card p-3 text-center">
            <h5>✅ Días Completados</h5>
            <?php
              $q_completados = $conn->prepare("SELECT COUNT(*) AS total FROM asistencia WHERE dni_practicante = ? AND estado = 'COMPLETADO'");
              $q_completados->bind_param("i", $dni_practicante);
              $q_completados->execute();
              $res_completados = $q_completados->get_result();
              $row_completados = $res_completados->fetch_assoc();
            ?>
            <h2><?= $row_completados['total'] ?></h2>
          </div>
        </div>
      </div>

      <!-- HISTORIAL DE ASISTENCIAS -->
      <div class="card p-4">
        <h4 class="mb-3">Historial de Asistencias</h4>
        <div class="table-responsive">
          <table class="table table-striped align-middle">
            <thead>
              <tr>
                <th>Fecha</th>
                <th>Hora Ingreso</th>
                <th>Hora Salida</th>
                <th>Horas Día</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
              <?php
                $q = $conn->prepare("SELECT *, 
                                    CASE 
                                        WHEN hora_salida IS NULL OR hora_salida = '' OR hora_salida = '00:00:00' THEN '0.00'
                                        WHEN TIMESTAMPDIFF(MINUTE, hora_ingreso, hora_salida) < 0 THEN '0.00'
                                        ELSE ROUND(TIMESTAMPDIFF(MINUTE, hora_ingreso, hora_salida) / 60, 2)
                                    END as horas_calculadas
                                    FROM asistencia 
                                    WHERE dni_practicante = ? 
                                    ORDER BY fecha DESC");
                $q->bind_param("i", $dni_practicante);
                $q->execute();
                $res = $q->get_result();
                while ($r = $res->fetch_assoc()):
              ?>
              <tr>
                <td><?= date("d/m/Y", strtotime($r['fecha'])) ?></td>
                <td><?= $r['hora_ingreso'] ? date("H:i", strtotime($r['hora_ingreso'])) : '-' ?></td>
                <td>
                  <?php 
                    if (tieneSalidaValida($r)) {
                      echo date("H:i", strtotime($r['hora_salida']));
                    } else {
                      echo '-';
                    }
                  ?>
                </td>
                <td>
                  <?php 
                    if ($r['hora_ingreso'] && tieneSalidaValida($r)) {
                      $horas = $r['horas_calculadas'];
                      echo number_format($horas, 2);
                    } else {
                      echo '0.00';
                    }
                  ?>
                </td>
                <td>
                  <?php if ($r['estado'] == 'EN_CURSO' && $r['hora_ingreso'] && !tieneSalidaValida($r)): ?>
                    <span class="badge bg-warning text-dark">En curso</span>
                  <?php elseif ($r['estado'] == 'COMPLETADO' && $r['hora_ingreso'] && tieneSalidaValida($r)): ?>
                    <span class="badge bg-success">Completado</span>
                  <?php else: ?>
                    <span class="badge bg-secondary">Incompleto</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="modalTareasContainer"></div>

<script>
// Función para mostrar el modal de tareas
function mostrarModalTareas() {
    // Cargar el modal via AJAX
    $.ajax({
        url: 'modal_tareas.php',
        type: 'GET',
        success: function(data) {
            $('#modalTareasContainer').html(data);
            $('#modalTareas').modal('show');
        },
        error: function() {
            alert('Error al cargar el modal de tareas');
        }
    });
}

// Modificar el botón de salida para usar el modal
$(document).ready(function() {
    // Reemplazar el botón de salida original
    $('button[name="salida"]').off('click').on('click', function(e) {
        e.preventDefault();
        
        // Verificar si tiene entrada hoy
        <?php if ($asistencia && $asistencia['hora_ingreso'] && !tieneSalidaValida($asistencia) && $id_modulo): ?>
            // Mostrar modal de tareas
            mostrarModalTareas();
        <?php else: ?>
            // Si no puede marcar salida, mantener comportamiento original
            $(this).closest('form').submit();
        <?php endif; ?>
    });
});
</script>

<!-- Incluir jQuery si no está incluido -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<?php include("includes/footer.php"); ?>