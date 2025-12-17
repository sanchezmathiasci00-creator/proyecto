<?php
// admin_usuarios.php
include('includes/header.php');

// Verificar que solo ADMIN puede acceder
if ($rol != 'ADMIN') {
    header("Location: index.php");
    exit;
}

// Obtener datos para dropdowns
$especialidades = $conn->query("SELECT * FROM especialidades ORDER BY nm_especialidades");
$modulos = $conn->query("SELECT * FROM modulos ORDER BY nm_modulos");
$turnos = $conn->query("SELECT * FROM turnos");

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // ========== CREAR USUARIO ==========
    if (isset($_POST['crear_usuario'])) {
        $nombre = $_POST['nombre'];
        $apellidos = $_POST['apellidos'] ?? '';
        $cargo = $_POST['cargo'];
        $usuario_nuevo = $_POST['usuario'];
        $clave = $_POST['clave'];
        $rol_usuario = $_POST['rol'];
        $dni = $_POST['dni'] ?? null;
        
        // Hash de la contraseña
        $clave_hash = password_hash($clave, PASSWORD_DEFAULT);
        
        // Iniciar transacción
        $conn->begin_transaction();
        
        try {
            // 1. Crear usuario en tabla usuarios
            $stmt = $conn->prepare("INSERT INTO usuarios (nombre, cargo, usuario, clave, rol) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $nombre, $cargo, $usuario_nuevo, $clave_hash, $rol_usuario);
            $stmt->execute();
            $id_usuario_nuevo = $stmt->insert_id;
            
            // 2. Si es PRACTICANTE, crear también en tabla practicantes
            if ($rol_usuario == 'PRACTICANTE' && $dni) {
                $semestre = $_POST['semestre'];
                $id_especialidades = $_POST['id_especialidades'] ?? null;
                $id_modulos = $_POST['id_modulos'] ?? null;
                $id_turnos = $_POST['id_turnos'] ?? null;
                $foto = 'default.png'; // Foto por defecto
                
                $stmt2 = $conn->prepare("INSERT INTO practicantes (dni, nombres, apellidos, semestre, id_especialidades, id_modulos, id_turnos, id_usuario, foto) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt2->bind_param("isssiiiis", $dni, $nombre, $apellidos, $semestre, $id_especialidades, $id_modulos, $id_turnos, $id_usuario_nuevo, $foto);
                $stmt2->execute();
                
                // 3. Actualizar tabla usuarios con el DNI del practicante
                $stmt3 = $conn->prepare("UPDATE usuarios SET dni_practicante = ? WHERE id_usuario = ?");
                $stmt3->bind_param("ii", $dni, $id_usuario_nuevo);
                $stmt3->execute();
            }
            
            $conn->commit();
            $mensaje = "✅ Usuario creado exitosamente";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "❌ Error al crear usuario: " . $e->getMessage();
        }
    }
    
    // ========== EDITAR CONTRASEÑA ==========
    if (isset($_POST['editar_contraseña'])) {
        $id_usuario = $_POST['id_usuario'];
        $nueva_clave = $_POST['nueva_clave'];
        
        $clave_hash = password_hash($nueva_clave, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("UPDATE usuarios SET clave = ? WHERE id_usuario = ?");
        $stmt->bind_param("si", $clave_hash, $id_usuario);
        
        if ($stmt->execute()) {
            $mensaje = "✅ Contraseña actualizada exitosamente";
        } else {
            $error = "❌ Error al actualizar contraseña";
        }
    }
    
    // ========== ELIMINAR USUARIO ==========
    if (isset($_POST['eliminar_usuario'])) {
        $id_usuario = $_POST['id_usuario'];
        
        $conn->begin_transaction();
        
        try {
            // 1. Eliminar primero de practicantes (si existe)
            // Esto automáticamente establecerá dni_practicante = NULL en usuarios
            $conn->query("DELETE FROM practicantes WHERE id_usuario = $id_usuario");
            
            // 2. Eliminar registros relacionados
            $conn->query("DELETE FROM asistencia WHERE id_usuario = $id_usuario");
            
            // 3. Eliminar usuario
            $stmt = $conn->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
            $stmt->bind_param("i", $id_usuario);
            $stmt->execute();
            
            $conn->commit();
            $mensaje = "✅ Usuario eliminado exitosamente";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "❌ Error al eliminar usuario: " . $e->getMessage();
        }
    }
    
    // ========== EDITAR ROL ==========
    if (isset($_POST['editar_rol'])) {
        $id_usuario = $_POST['id_usuario'];
        $nuevo_rol = $_POST['nuevo_rol'];
        
        $stmt = $conn->prepare("UPDATE usuarios SET rol = ? WHERE id_usuario = ?");
        $stmt->bind_param("si", $nuevo_rol, $id_usuario);
        
        if ($stmt->execute()) {
            $mensaje = "✅ Rol actualizado exitosamente";
        } else {
            $error = "❌ Error al actualizar rol";
        }
    }
    
    // ========== ASIGNAR DNI A USUARIO EXISTENTE ==========
    if (isset($_POST['asignar_dni'])) {
        $id_usuario = $_POST['id_usuario'];
        $dni = $_POST['dni'];
        $nombres = $_POST['nombres'];
        $apellidos = $_POST['apellidos'];
        $semestre = $_POST['semestre'];
        $id_especialidades = $_POST['id_especialidades'];
        $id_modulos = $_POST['id_modulos'];
        $id_turnos = $_POST['id_turnos'];
        $foto = 'default.png';
        
        $conn->begin_transaction();
        
        try {
            // 1. Crear en tabla practicantes
            $stmt1 = $conn->prepare("INSERT INTO practicantes (dni, nombres, apellidos, semestre, id_especialidades, id_modulos, id_turnos, id_usuario, foto) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt1->bind_param("isssiiiis", $dni, $nombres, $apellidos, $semestre, $id_especialidades, $id_modulos, $id_turnos, $id_usuario, $foto);
            $stmt1->execute();
            
            // 2. Actualizar tabla usuarios con DNI
            $stmt2 = $conn->prepare("UPDATE usuarios SET dni_practicante = ? WHERE id_usuario = ?");
            $stmt2->bind_param("ii", $dni, $id_usuario);
            $stmt2->execute();
            
            // 3. Actualizar rol a PRACTICANTE
            $stmt3 = $conn->prepare("UPDATE usuarios SET rol = 'PRACTICANTE' WHERE id_usuario = ?");
            $stmt3->bind_param("i", $id_usuario);
            $stmt3->execute();
            
            $conn->commit();
            $mensaje = "✅ DNI asignado y usuario convertido a PRACTICANTE exitosamente";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "❌ Error al asignar DNI: " . $e->getMessage();
        }
    }
}

// Obtener lista de usuarios con LEFT JOIN para ver datos de practicantes
$usuarios = $conn->query("
    SELECT u.*, p.nombres as p_nombres, p.apellidos as p_apellidos, p.semestre, 
           p.id_especialidades, p.id_modulos, p.id_turnos 
    FROM usuarios u 
    LEFT JOIN practicantes p ON u.dni_practicante = p.dni 
    ORDER BY u.id_usuario DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Administración de Usuarios - Sistema PPP</title>
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
        .practicante-row {
            background-color: #f0f8ff !important;
        }
        .section-title {
            border-left: 4px solid #0d6efd;
            padding-left: 15px;
            margin: 30px 0 15px 0;
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
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- SIDEBAR -->
        <?php include('includes/sidebar.php'); ?>

        <!-- CONTENIDO PRINCIPAL -->
        <div class="col-md-9 col-lg-10 p-4">
            <h2 class="mb-4"><i class="fas fa-users-cog"></i> Administración de Usuarios</h2>
            
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
                        <h5>👥 Total Usuarios</h5>
                        <?php
                            $q = $conn->query("SELECT COUNT(*) AS total FROM usuarios");
                            $row = $q->fetch_assoc();
                        ?>
                        <h2><?= $row['total'] ?></h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card p-3 text-center">
                        <h5>🎓 Practicantes</h5>
                        <?php
                            $q = $conn->query("SELECT COUNT(*) AS total FROM usuarios WHERE rol = 'PRACTICANTE'");
                            $row = $q->fetch_assoc();
                        ?>
                        <h2><?= $row['total'] ?></h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card p-3 text-center">
                        <h5>👔 Administrativos</h5>
                        <?php
                            $q = $conn->query("SELECT COUNT(*) AS total FROM usuarios WHERE rol IN ('ADMIN', 'COORDINADOR', 'ASISTENTE')");
                            $row = $q->fetch_assoc();
                        ?>
                        <h2><?= $row['total'] ?></h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card p-3 text-center">
                        <h5>👑 Administradores</h5>
                        <?php
                            $q = $conn->query("SELECT COUNT(*) AS total FROM usuarios WHERE rol = 'ADMIN'");
                            $row = $q->fetch_assoc();
                        ?>
                        <h2><?= $row['total'] ?></h2>
                    </div>
                </div>
            </div>
            
            <!-- Card para crear nuevo usuario -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-user-plus"></i> Crear Nuevo Usuario</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="formCrearUsuario">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre Completo *</label>
                                <input type="text" class="form-control" name="nombre" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Apellidos (solo para practicantes)</label>
                                <input type="text" class="form-control" name="apellidos">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Cargo</label>
                                <input type="text" class="form-control" name="cargo">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nombre de Usuario *</label>
                                <input type="text" class="form-control" name="usuario" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contraseña *</label>
                                <input type="password" class="form-control" name="clave" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Rol *</label>
                                <select class="form-select" name="rol" id="selectRol" required>
                                    <option value="">Seleccionar rol...</option>
                                    <option value="PRACTICANTE">Practicante</option>
                                    <option value="ASISTENTE">Asistente</option>
                                    <option value="COORDINADOR">Coordinador</option>
                                    <option value="ADMIN">Administrador</option>
                                </select>
                            </div>
                            
                            <!-- Campos específicos para PRACTICANTE -->
                            <div id="camposPracticante" style="display: none;">
                                <hr>
                                <h6><i class="fas fa-graduation-cap"></i> Datos del Practicante</h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">DNI *</label>
                                        <input type="number" class="form-control" name="dni" id="dni">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Semestre *</label>
                                        <select class="form-select" name="semestre" id="semestre">
                                            <option value="I">I</option>
                                            <option value="II">II</option>
                                            <option value="III">III</option>
                                            <option value="IV">IV</option>
                                            <option value="V">V</option>
                                            <option value="VI">VI</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Turno *</label>
                                        <select class="form-select" name="id_turnos" required>
                                            <option value="">Seleccionar turno...</option>
                                            <?php while ($turno = $turnos->fetch_assoc()): ?>
                                                <option value="<?= $turno['id_turnos'] ?>"><?= $turno['nm_turnos'] ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Especialidad *</label>
                                        <select class="form-select" name="id_especialidades" id="selectEspecialidad" required>
                                            <option value="">Seleccionar especialidad...</option>
                                            <?php while ($esp = $especialidades->fetch_assoc()): ?>
                                                <option value="<?= $esp['id_especialidades'] ?>"><?= $esp['nm_especialidades'] ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Módulo *</label>
                                        <select class="form-select" name="id_modulos" id="selectModulo" required>
                                            <option value="">Primero seleccione especialidad</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-12 mt-3">
                                <button type="submit" name="crear_usuario" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Crear Usuario
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Tabla de usuarios existentes -->
            <div class="card">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Usuarios del Sistema</h5>
                    <span class="badge bg-light text-dark">Total: <?= $usuarios->num_rows ?></span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Usuario</th>
                                    <th>Rol</th>
                                    <th>DNI</th>
                                    <th>Detalles Practicante</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $counter = 0;
                                while ($user = $usuarios->fetch_assoc()): 
                                    $counter++;
                                ?>
                                <tr class="<?= $user['rol'] == 'PRACTICANTE' ? 'practicante-row' : '' ?>">
                                    <td><strong><?= $user['id_usuario'] ?></strong></td>
                                    <td>
                                        <?= htmlspecialchars($user['nombre']) ?>
                                        <?php if ($user['p_nombres']): ?>
                                            <br><small class="text-muted"><?= $user['p_nombres'] ?> <?= $user['p_apellidos'] ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($user['usuario']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $user['rol'] == 'ADMIN' ? 'danger' : 
                                            ($user['rol'] == 'COORDINADOR' ? 'warning' : 
                                            ($user['rol'] == 'ASISTENTE' ? 'info' : 'primary')) 
                                        ?>">
                                            <?= $user['rol'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($user['dni_practicante']): ?>
                                            <span class="badge bg-dark"><?= $user['dni_practicante'] ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($user['dni_practicante']): ?>
                                            <small>
                                                <span class="badge bg-info">Sem: <?= $user['semestre'] ?></span><br>
                                                <?php 
                                                    // Obtener nombres de especialidad y módulo
                                                    if ($user['id_especialidades']) {
                                                        $esp = $conn->query("SELECT nm_especialidades FROM especialidades WHERE id_especialidades = " . $user['id_especialidades'])->fetch_assoc();
                                                        echo "<small>Esp: " . $esp['nm_especialidades'] . "</small><br>";
                                                    }
                                                    if ($user['id_modulos']) {
                                                        $mod = $conn->query("SELECT nm_modulos FROM modulos WHERE id_modulos = " . $user['id_modulos'])->fetch_assoc();
                                                        echo "<small>Mód: " . $mod['nm_modulos'] . "</small>";
                                                    }
                                                ?>
                                            </small>
                                        <?php else: ?>
                                            <?php if ($user['rol'] != 'PRACTICANTE'): ?>
                                                <button type="button" class="btn btn-sm btn-outline-success" 
                                                        data-bs-toggle="modal" data-bs-target="#modalAsignarDNI<?= $user['id_usuario'] ?>">
                                                    <i class="fas fa-id-card"></i> Asignar DNI
                                                </button>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Sin DNI asignado</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-warning" 
                                                data-bs-toggle="modal" data-bs-target="#modalEditarRol<?= $user['id_usuario'] ?>">
                                            <i class="fas fa-user-tag"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                data-bs-toggle="modal" data-bs-target="#modalEditarPass<?= $user['id_usuario'] ?>">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        <?php if ($user['id_usuario'] != $id_usuario): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                data-bs-toggle="modal" data-bs-target="#modalEliminar<?= $user['id_usuario'] ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                
                                <!-- Modal para asignar DNI a usuario existente -->
                                <div class="modal fade" id="modalAsignarDNI<?= $user['id_usuario'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Asignar DNI y Convertir a PRACTICANTE</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="id_usuario" value="<?= $user['id_usuario'] ?>">
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label">DNI *</label>
                                                            <input type="number" class="form-control" name="dni" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Nombres *</label>
                                                            <input type="text" class="form-control" name="nombres" value="<?= htmlspecialchars($user['nombre']) ?>" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Apellidos *</label>
                                                            <input type="text" class="form-control" name="apellidos" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Semestre *</label>
                                                            <select class="form-select" name="semestre" required>
                                                                <option value="I">I</option>
                                                                <option value="II">II</option>
                                                                <option value="III">III</option>
                                                                <option value="IV">IV</option>
                                                                <option value="V">V</option>
                                                                <option value="VI" selected>VI</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Especialidad *</label>
                                                            <select class="form-select" name="id_especialidades" required>
                                                                <option value="">Seleccionar...</option>
                                                                <?php 
                                                                $especialidades2 = $conn->query("SELECT * FROM especialidades ORDER BY nm_especialidades");
                                                                while ($esp = $especialidades2->fetch_assoc()): ?>
                                                                    <option value="<?= $esp['id_especialidades'] ?>"><?= $esp['nm_especialidades'] ?></option>
                                                                <?php endwhile; ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Módulo *</label>
                                                            <select class="form-select" name="id_modulos" required>
                                                                <option value="">Seleccionar...</option>
                                                                <?php 
                                                                $modulos2 = $conn->query("SELECT * FROM modulos ORDER BY nm_modulos");
                                                                while ($mod = $modulos2->fetch_assoc()): ?>
                                                                    <option value="<?= $mod['id_modulos'] ?>"><?= $mod['nm_modulos'] ?></option>
                                                                <?php endwhile; ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Turno *</label>
                                                            <select class="form-select" name="id_turnos" required>
                                                                <option value="">Seleccionar...</option>
                                                                <?php 
                                                                $turnos2 = $conn->query("SELECT * FROM turnos");
                                                                while ($turno = $turnos2->fetch_assoc()): ?>
                                                                    <option value="<?= $turno['id_turnos'] ?>"><?= $turno['nm_turnos'] ?></option>
                                                                <?php endwhile; ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="alert alert-info mt-3">
                                                        <i class="fas fa-info-circle"></i> El usuario será convertido automáticamente a PRACTICANTE
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" name="asignar_dni" class="btn btn-success">Asignar DNI</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Modal para editar rol -->
                                <div class="modal fade" id="modalEditarRol<?= $user['id_usuario'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Editar Rol de Usuario</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="id_usuario" value="<?= $user['id_usuario'] ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label">Nuevo Rol</label>
                                                        <select class="form-select" name="nuevo_rol" required>
                                                            <option value="PRACTICANTE" <?= $user['rol'] == 'PRACTICANTE' ? 'selected' : '' ?>>Practicante</option>
                                                            <option value="ASISTENTE" <?= $user['rol'] == 'ASISTENTE' ? 'selected' : '' ?>>Asistente</option>
                                                            <option value="COORDINADOR" <?= $user['rol'] == 'COORDINADOR' ? 'selected' : '' ?>>Coordinador</option>
                                                            <option value="ADMIN" <?= $user['rol'] == 'ADMIN' ? 'selected' : '' ?>>Administrador</option>
                                                        </select>
                                                    </div>
                                                    <?php if ($user['dni_practicante'] && $user['rol'] == 'PRACTICANTE'): ?>
                                                    <div class="alert alert-warning">
                                                        <i class="fas fa-exclamation-triangle"></i> Si cambia el rol, el DNI seguirá asignado pero el usuario ya no será practicante
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" name="editar_rol" class="btn btn-warning">Actualizar Rol</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Modal para editar contraseña -->
                                <div class="modal fade" id="modalEditarPass<?= $user['id_usuario'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Cambiar Contraseña</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="id_usuario" value="<?= $user['id_usuario'] ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label">Nueva Contraseña</label>
                                                        <input type="password" class="form-control" name="nueva_clave" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" name="editar_contraseña" class="btn btn-primary">Cambiar Contraseña</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Modal para eliminar usuario -->
                                <div class="modal fade" id="modalEliminar<?= $user['id_usuario'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Confirmar Eliminación</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="id_usuario" value="<?= $user['id_usuario'] ?>">
                                                    <p>¿Está seguro de eliminar al usuario <strong><?= htmlspecialchars($user['nombre']) ?></strong> (<?= htmlspecialchars($user['usuario']) ?>)?</p>
                                                    <?php if ($user['dni_practicante']): ?>
                                                    <p class="text-danger"><i class="fas fa-exclamation-triangle"></i> También se eliminarán sus datos de practicante (DNI: <?= $user['dni_practicante'] ?>)</p>
                                                    <?php endif; ?>
                                                    <p class="text-danger"><strong>Esta acción no se puede deshacer.</strong></p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" name="eliminar_usuario" class="btn btn-danger">Eliminar Usuario</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php endwhile; ?>
                                
                                <?php if ($counter == 0): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="fas fa-users-slash fa-2x mb-3"></i><br>
                                        No hay usuarios registrados en el sistema.
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-muted">
                    <small>Mostrando <?= $counter ?> usuario(s)</small>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Mostrar/ocultar campos de practicante según rol seleccionado
document.getElementById('selectRol').addEventListener('change', function() {
    const camposPracticante = document.getElementById('camposPracticante');
    if (this.value === 'PRACTICANTE') {
        camposPracticante.style.display = 'block';
        // Hacer campos obligatorios
        document.getElementById('dni').required = true;
        document.getElementById('semestre').required = true;
    } else {
        camposPracticante.style.display = 'none';
        // Quitar requeridos
        document.getElementById('dni').required = false;
        document.getElementById('semestre').required = false;
    }
});

// Cargar módulos según especialidad seleccionada
document.getElementById('selectEspecialidad').addEventListener('change', function() {
    const especialidadId = this.value;
    const moduloSelect = document.getElementById('selectModulo');
    
    if (!especialidadId) {
        moduloSelect.innerHTML = '<option value="">Primero seleccione especialidad</option>';
        return;
    }
    
    // Hacer petición AJAX para obtener módulos
    fetch('ajax_cargar_modulos.php?especialidad=' + especialidadId)
        .then(response => response.text())
        .then(data => {
            moduloSelect.innerHTML = data;
        })
        .catch(error => {
            console.error('Error:', error);
            moduloSelect.innerHTML = '<option value="">Error al cargar módulos</option>';
        });
});
</script>
</body>
</html>