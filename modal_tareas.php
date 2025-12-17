<?php
// modal_tareas.php
session_start();
include("conexion.php");

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    die("Acceso denegado");
}

// Solo para practicantes
if ($_SESSION['rol'] != 'PRACTICANTE') {
    die("Acceso denegado");
}

// Obtener datos del usuario
$id_usuario = $_SESSION['id_usuario'];
$stmt_user = $conn->prepare("SELECT dni_practicante FROM usuarios WHERE id_usuario = ?");
$stmt_user->bind_param("i", $id_usuario);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user = $result_user->fetch_assoc();

if (!$user || !$user['dni_practicante']) {
    die("Usuario no encontrado");
}

$dni_practicante = $user['dni_practicante'];
$fecha_hoy = date("Y-m-d");

// Procesar el formulario de tareas
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tareas'])) {
    $tareas = trim($_POST['tareas']);
    $hora_salida = date("H:i:s");
    
    // Validar que haya una entrada hoy
    $stmt_check = $conn->prepare("SELECT id_asistencia FROM asistencia WHERE dni_practicante = ? AND fecha = ? AND hora_ingreso IS NOT NULL");
    $stmt_check->bind_param("is", $dni_practicante, $fecha_hoy);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows > 0) {
        // Actualizar con tareas
        $stmt_update = $conn->prepare("UPDATE asistencia SET hora_salida = ?, tareas_realizadas = ?, estado = 'COMPLETADO' WHERE dni_practicante = ? AND fecha = ?");
        $stmt_update->bind_param("ssis", $hora_salida, $tareas, $dni_practicante, $fecha_hoy);
        
        if ($stmt_update->execute()) {
            echo json_encode(['success' => true, 'message' => '✅ Salida registrada correctamente a las ' . $hora_salida]);
        } else {
            echo json_encode(['success' => false, 'message' => '❌ Error al registrar la salida: ' . $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => '❌ No tienes una entrada registrada hoy']);
    }
    exit;
}
?>

<!-- Modal para registrar tareas -->
<div class="modal fade" id="modalTareas" tabindex="-1" aria-labelledby="modalTareasLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalTareasLabel">
          <i class="fas fa-tasks me-2"></i>Registrar Tareas del Día
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formTareas" method="POST">
        <div class="modal-body">
          <div class="mb-3">
            <label for="fecha_hoy" class="form-label">Fecha:</label>
            <input type="text" class="form-control" id="fecha_hoy" value="<?= date('d/m/Y') ?>" readonly>
          </div>
          
          <div class="mb-3">
            <label for="hora_salida" class="form-label">Hora de salida:</label>
            <input type="text" class="form-control" id="hora_salida" value="<?= date('H:i:s') ?>" readonly>
          </div>
          
          <div class="mb-3">
            <label for="tareas" class="form-label">
              <i class="fas fa-clipboard-list me-1"></i>Tareas realizadas hoy:
            </label>
            <textarea 
              class="form-control" 
              id="tareas" 
              name="tareas" 
              rows="5" 
              placeholder="Describe las actividades que realizaste hoy."
              required></textarea>
            <div class="form-text">
              Describe de manera clara y breve las actividades que realizaste durante la jornada.
            </div>
          </div>
          
          <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <small>
              Esta información será registrada en tu historial de asistencias y podrá ser vista por tu coordinador.
            </small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times me-1"></i>Cancelar
          </button>
          <button type="submit" class="btn btn-primary" id="btnRegistrar">
            <i class="fas fa-check-circle me-1"></i>Registrar Salida con Tareas
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
    // Actualizar hora cada segundo
    function actualizarHora() {
        const now = new Date();
        const hora = now.getHours().toString().padStart(2, '0');
        const minuto = now.getMinutes().toString().padStart(2, '0');
        const segundo = now.getSeconds().toString().padStart(2, '0');
        $('#hora_salida').val(`${hora}:${minuto}:${segundo}`);
    }
    
    setInterval(actualizarHora, 1000);
    
    // Manejar envío del formulario
    $('#formTareas').on('submit', function(e) {
        e.preventDefault();
        
        const tareas = $('#tareas').val().trim();
        if (tareas === '') {
            alert('Por favor, describe las tareas realizadas');
            return;
        }
        
        // Mostrar loading
        const submitBtn = $('#btnRegistrar');
        const originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Procesando...');
        
        $.ajax({
            url: 'modal_tareas.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Mostrar mensaje de éxito
                    alert(response.message);
                    
                    // Cerrar modal
                    $('#modalTareas').modal('hide');
                    
                    // Recargar la página después de 1 segundo
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    alert(response.message);
                    submitBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function() {
                alert('Error al procesar la solicitud');
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Mostrar modal cuando se cargue
    $('#modalTareas').modal('show');
});
</script>