<?php
// admin_practicantes.php
include('includes/header.php');

// Verificar que solo ADMIN puede acceder
if ($rol != 'ADMIN') {
    header("Location: index.php");
    exit;
}

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['eliminar_practicante'])) {
        $dni = $_POST['dni'];
        
        $conn->begin_transaction();
        
        try {
            // 1. Eliminar registros relacionados
            $conn->query("DELETE FROM asistencia WHERE dni_practicante = $dni");
            $conn->query("DELETE FROM progreso_modulo WHERE dni_practicante = $dni");
            
            // 2. Obtener id_usuario antes de eliminar
            $result = $conn->query("SELECT id_usuario FROM practicantes WHERE dni = $dni");
            if ($result->num_rows > 0) {
                $practicante = $result->fetch_assoc();
                $id_usuario = $practicante['id_usuario'];
                
                // 3. Eliminar practicante
                $conn->query("DELETE FROM practicantes WHERE dni = $dni");
                
                // 4. Actualizar usuario para quitar referencia
                if ($id_usuario) {
                    $conn->query("UPDATE usuarios SET dni_practicante = NULL, rol = 'PRACTICANTE' WHERE id_usuario = $id_usuario");
                }
            }
            
            $conn->commit();
            $mensaje = "✅ Practicante eliminado exitosamente";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "❌ Error al eliminar practicante: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['editar_practicante'])) {
        $dni = $_POST['dni'];
        $nombres = $_POST['nombres'];
        $apellidos = $_POST['apellidos'];
        $semestre = $_POST['semestre'];
        $id_especialidades = $_POST['id_especialidades'];
        $id_modulos = $_POST['id_modulos'];
        $id_turnos = $_POST['id_turnos'];
        
        $stmt = $conn->prepare("UPDATE practicantes SET nombres = ?, apellidos = ?, semestre = ?, id_especialidades = ?, id_modulos = ?, id_turnos = ? WHERE dni = ?");
        $stmt->bind_param("sssiiii", $nombres, $apellidos, $semestre, $id_especialidades, $id_modulos, $id_turnos, $dni);
        
        if ($stmt->execute()) {
            $mensaje = "✅ Practicante actualizado exitosamente";
        } else {
            $error = "❌ Error al actualizar practicante";
        }
    }
}

// Obtener datos para dropdowns
$especialidades = $conn->query("SELECT * FROM especialidades ORDER BY nm_especialidades");
$turnos = $conn->query("SELECT * FROM turnos");

// Obtener lista de practicantes con datos completos
$practicantes = $conn->query("
    SELECT p.*, e.nm_especialidades, m.nm_modulos, t.nm_turnos, u.usuario 
    FROM practicantes p
    LEFT JOIN especialidades e ON p.id_especialidades = e.id_especialidades
    LEFT JOIN modulos m ON p.id_modulos = m.id_modulos
    LEFT JOIN turnos t ON p.id_turnos = t.id_turnos
    LEFT JOIN usuarios u ON p.id_usuario = u.id_usuario
    ORDER BY p.dni
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Practicantes - Sistema PPP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        .table-hover tbody tr:hover {
            background-color: #f5f5f5;
        }
        /* ESTILOS DEL SIDEBAR COMO EN INDEX.PHP */
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
        .badge-practicante {
            background-color: #0dcaf0;
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
            <h2 class="mb-4"><i class="fas fa-users"></i> Gestión de Practicantes</h2>
            
            <?php if (isset($mensaje)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $mensaje ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Card de estadísticas -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card p-3 text-center">
                        <h5>👥 Total Practicantes</h5>
                        <?php
                            $q = $conn->query("SELECT COUNT(*) AS total FROM practicantes");
                            $row = $q->fetch_assoc();
                        ?>
                        <h2><?= $row['total'] ?></h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card p-3 text-center">
                        <h5>📘 Módulos Activos</h5>
                        <?php
                            $q = $conn->query("SELECT COUNT(DISTINCT id_modulos) AS total FROM practicantes WHERE id_modulos IS NOT NULL");
                            $row = $q->fetch_assoc();
                        ?>
                        <h2><?= $row['total'] ?></h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card p-3 text-center">
                        <h5>🌅 Turno Diurno</h5>
                        <?php
                            $q = $conn->query("SELECT COUNT(*) AS total FROM practicantes p JOIN turnos t ON p.id_turnos = t.id_turnos WHERE t.nm_turnos = 'Diurno'");
                            $row = $q->fetch_assoc();
                        ?>
                        <h2><?= $row['total'] ?></h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card p-3 text-center">
                        <h5>🌃 Turno Nocturno</h5>
                        <?php
                            $q = $conn->query("SELECT COUNT(*) AS total FROM practicantes p JOIN turnos t ON p.id_turnos = t.id_turnos WHERE t.nm_turnos = 'Nocturno'");
                            $row = $q->fetch_assoc();
                        ?>
                        <h2><?= $row['total'] ?></h2>
                    </div>
                </div>
            </div>
            
            <!-- Tabla de practicantes -->
            <div class="card">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Lista de Practicantes Registrados</h5>
                    <span class="badge bg-light text-dark">Total: <?= $practicantes->num_rows ?></span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>DNI</th>
                                    <th>Nombres</th>
                                    <th>Apellidos</th>
                                    <th>Semestre</th>
                                    <th>Especialidad</th>
                                    <th>Módulo</th>
                                    <th>Turno</th>
                                    <th>Usuario</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $counter = 0;
                                while ($p = $practicantes->fetch_assoc()): 
                                    $counter++;
                                ?>
                                <tr>
                                    <td><strong><?= $p['dni'] ?></strong></td>
                                    <td><?= htmlspecialchars($p['nombres']) ?></td>
                                    <td><?= htmlspecialchars($p['apellidos']) ?></td>
                                    <td>
                                        <span class="badge bg-info"><?= $p['semestre'] ?></span>
                                    </td>
                                    <td>
                                        <small><?= htmlspecialchars($p['nm_especialidades'] ?? 'Sin especialidad') ?></small>
                                    </td>
                                    <td>
                                        <small><?= htmlspecialchars($p['nm_modulos'] ?? 'Sin módulo') ?></small>
                                    </td>
                                    <td>
                                        <span class="badge <?= ($p['nm_turnos'] == 'Diurno') ? 'bg-warning text-dark' : 'bg-dark' ?>">
                                            <?= htmlspecialchars($p['nm_turnos'] ?? 'Sin turno') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($p['usuario']): ?>
                                            <span class="badge bg-success"><?= $p['usuario'] ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Sin usuario</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                data-bs-toggle="modal" data-bs-target="#modalEditar<?= $p['dni'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                data-bs-toggle="modal" data-bs-target="#modalEliminar<?= $p['dni'] ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                
                                <!-- Modal para editar practicante -->
                                <div class="modal fade" id="modalEditar<?= $p['dni'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Editar Practicante</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="dni" value="<?= $p['dni'] ?>">
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label">Nombres *</label>
                                                            <input type="text" class="form-control" name="nombres" value="<?= htmlspecialchars($p['nombres']) ?>" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Apellidos *</label>
                                                            <input type="text" class="form-control" name="apellidos" value="<?= htmlspecialchars($p['apellidos']) ?>" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Semestre *</label>
                                                            <select class="form-select" name="semestre" required>
                                                                <option value="I" <?= $p['semestre'] == 'I' ? 'selected' : '' ?>>I</option>
                                                                <option value="II" <?= $p['semestre'] == 'II' ? 'selected' : '' ?>>II</option>
                                                                <option value="III" <?= $p['semestre'] == 'III' ? 'selected' : '' ?>>III</option>
                                                                <option value="IV" <?= $p['semestre'] == 'IV' ? 'selected' : '' ?>>IV</option>
                                                                <option value="V" <?= $p['semestre'] == 'V' ? 'selected' : '' ?>>V</option>
                                                                <option value="VI" <?= $p['semestre'] == 'VI' ? 'selected' : '' ?>>VI</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Turno *</label>
                                                            <select class="form-select" name="id_turnos" required>
                                                                <option value="">Seleccionar turno...</option>
                                                                <?php 
                                                                $turnos2 = $conn->query("SELECT * FROM turnos");
                                                                while ($turno = $turnos2->fetch_assoc()): ?>
                                                                    <option value="<?= $turno['id_turnos'] ?>" <?= $p['id_turnos'] == $turno['id_turnos'] ? 'selected' : '' ?>>
                                                                        <?= $turno['nm_turnos'] ?>
                                                                    </option>
                                                                <?php endwhile; ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Especialidad *</label>
                                                            <select class="form-select" name="id_especialidades" required>
                                                                <option value="">Seleccionar especialidad...</option>
                                                                <?php 
                                                                $especialidades2 = $conn->query("SELECT * FROM especialidades ORDER BY nm_especialidades");
                                                                while ($esp = $especialidades2->fetch_assoc()): ?>
                                                                    <option value="<?= $esp['id_especialidades'] ?>" <?= $p['id_especialidades'] == $esp['id_especialidades'] ? 'selected' : '' ?>>
                                                                        <?= $esp['nm_especialidades'] ?>
                                                                    </option>
                                                                <?php endwhile; ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Módulo *</label>
                                                            <select class="form-select" name="id_modulos" required>
                                                                <option value="">Seleccionar módulo...</option>
                                                                <?php 
                                                                $modulos2 = $conn->query("SELECT * FROM modulos ORDER BY nm_modulos");
                                                                while ($mod = $modulos2->fetch_assoc()): ?>
                                                                    <option value="<?= $mod['id_modulos'] ?>" <?= $p['id_modulos'] == $mod['id_modulos'] ? 'selected' : '' ?>>
                                                                        <?= $mod['nm_modulos'] ?>
                                                                    </option>
                                                                <?php endwhile; ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" name="editar_practicante" class="btn btn-primary">Guardar Cambios</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Modal para eliminar practicante -->
                                <div class="modal fade" id="modalEliminar<?= $p['dni'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Confirmar Eliminación</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="dni" value="<?= $p['dni'] ?>">
                                                    <p>¿Está seguro de eliminar al practicante?</p>
                                                    <div class="alert alert-warning">
                                                        <strong>DNI:</strong> <?= $p['dni'] ?><br>
                                                        <strong>Nombre:</strong> <?= htmlspecialchars($p['nombres'] . ' ' . $p['apellidos']) ?><br>
                                                        <strong>Usuario asociado:</strong> <?= $p['usuario'] ?: 'Ninguno' ?>
                                                    </div>
                                                    <p class="text-danger">
                                                        <i class="fas fa-exclamation-triangle"></i> 
                                                        <strong>Advertencia:</strong> También se eliminarán sus registros de asistencia y progreso.
                                                    </p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" name="eliminar_practicante" class="btn btn-danger">Eliminar Practicante</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php endwhile; ?>
                                
                                <?php if ($counter == 0): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        <i class="fas fa-users-slash fa-2x mb-3"></i><br>
                                        No hay practicantes registrados en el sistema.
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-muted">
                    <small>Mostrando <?= $counter ?> practicante(s)</small>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Filtrar módulos según especialidad seleccionada
document.querySelectorAll('[name="id_especialidades"]').forEach(function(select) {
    select.addEventListener('change', function() {
        const especialidadId = this.value;
        const modal = this.closest('.modal');
        const moduloSelect = modal.querySelector('[name="id_modulos"]');
        
        if (!especialidadId) {
            moduloSelect.innerHTML = '<option value="">Seleccionar especialidad primero</option>';
            return;
        }
        
        // Cargar módulos de esta especialidad
        fetch('ajax_cargar_modulos.php?especialidad=' + especialidadId)
            .then(response => response.text())
            .then(data => {
                moduloSelect.innerHTML = data;
            });
    });
});
</script>
</body>
</html>