<?php
// upload.php
include("includes/header.php");

// Solo permitir a practicantes
if ($_SESSION['rol'] != 'PRACTICANTE') {
  header("Location: index.php");
  exit;
}

// Obtener el dni_practicante
$stmt_user = $conn->prepare("SELECT dni_practicante FROM usuarios WHERE id_usuario = ?");
$stmt_user->bind_param("i", $id_usuario);
$stmt_user->execute();
$result_user = $stmt_user->get_result();

if ($row_user = $result_user->fetch_assoc()) {
    $dni_practicante = $row_user['dni_practicante'];
} else {
    die("❌ No se encontró el usuario en el sistema.");
}

// Procesar subida de foto
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] == UPLOAD_ERR_OK) {
    
    // Obtener datos del practicante para el DNI
    $stmt = $conn->prepare("SELECT dni FROM practicantes WHERE dni = ?");
    $stmt->bind_param("i", $dni_practicante);
    $stmt->execute();
    $res = $stmt->get_result();
    $practicante = $res->fetch_assoc();
    
    if (!$practicante) {
        die("❌ No se encontró información del practicante.");
    }
    
    $dni = $practicante['dni'];

    // Configuración para subir la foto
    $fileTmpPath = $_FILES['foto_perfil']['tmp_name'];
    $originalName = $_FILES['foto_perfil']['name'];
    $uploadFolder = 'uploads/fotos_perfil/';
    
    // Crear carpeta si no existe
    if (!is_dir($uploadFolder)) {
        mkdir($uploadFolder, 0755, true);
    }

    // Generar nombre único para la foto
    $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (in_array($fileExtension, $allowedExtensions)) {
        // Verificar tamaño máximo (2MB)
        $maxFileSize = 2 * 1024 * 1024; // 2MB en bytes
        if ($_FILES['foto_perfil']['size'] > $maxFileSize) {
            header("Location: avance.php?error=Tamaño de archivo excede el límite de 2MB");
            exit;
        }
        
        // Nombre del archivo: dni_usuario + timestamp
        $newFileName = $dni . '_' . time() . '.' . $fileExtension;
        $destination = $uploadFolder . $newFileName;

        // Mover archivo subido
        if (move_uploaded_file($fileTmpPath, $destination)) {
            // Actualizar la base de datos con la ruta de la foto
            $stmt_update = $conn->prepare("UPDATE practicantes SET foto = ? WHERE dni = ?");
            $stmt_update->bind_param("si", $destination, $dni);
            
            if ($stmt_update->execute()) {
                header("Location: avance.php?success=Foto de perfil actualizada correctamente!");
                exit;
            } else {
                header("Location: avance.php?error=Error al guardar en la base de datos");
                exit;
            }
        } else {
            header("Location: avance.php?error=Error al mover el archivo subido");
            exit;
        }
    } else {
        header("Location: avance.php?error=Formato de archivo no permitido. Use JPG, JPEG, PNG o GIF");
        exit;
    }
} else {
    header("Location: avance.php?error=Ocurrió un error en la subida o no se envió archivo");
    exit;
}
?>