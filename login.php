<?php
// Configurar zona horaria de Perú
date_default_timezone_set('America/Lima');

// Configurar encoding UTF-8
header('Content-Type: text/html; charset=utf-8');

session_start();
include("conexion.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $usuario = $_POST['usuario'];
  $clave = $_POST['clave'];

  $sql = "SELECT * FROM usuarios WHERE usuario = ? LIMIT 1";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $usuario);
  $stmt->execute();
  $resultado = $stmt->get_result();

  if ($resultado->num_rows === 1) {
    $user = $resultado->fetch_assoc();
    
    // Verificar si la contraseña está hasheada (60 caracteres = hash)
    if (strlen($user['clave']) == 60) {
      // Contraseña hasheada - usar password_verify
      if (password_verify($clave, $user['clave'])) {
        $_SESSION['id_usuario'] = $user['id_usuario'];
        $_SESSION['usuario'] = $user['usuario'];
        $_SESSION['rol'] = $user['rol'];
        header("Location: index.php");
        exit;
      } else {
        $error = "Contraseña incorrecta";
      }
    } else {
      // Contraseña en texto plano - compatibilidad con usuarios existentes
      if ($clave == $user['clave']) {
        $_SESSION['id_usuario'] = $user['id_usuario'];
        $_SESSION['usuario'] = $user['usuario'];
        $_SESSION['rol'] = $user['rol'];
        
        // Opcional: actualizar a hash para el próximo login
        // $hash = password_hash($clave, PASSWORD_DEFAULT);
        // $update_sql = "UPDATE usuarios SET clave = ? WHERE id_usuario = ?";
        // $update_stmt = $conn->prepare($update_sql);
        // $update_stmt->bind_param("si", $hash, $user['id_usuario']);
        // $update_stmt->execute();
        
        header("Location: index.php");
        exit;
      } else {
        $error = "Contraseña incorrecta";
      }
    }
  } else {
    $error = "Usuario no encontrado";
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | Sistema PPP</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(120deg, #007bff, #6610f2);
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .login-box {
      background: white;
      border-radius: 10px;
      padding: 40px;
      box-shadow: 0 0 20px rgba(0,0,0,0.1);
      width: 100%;
      max-width: 380px;
    }
    .login-box h3 {
      text-align: center;
      margin-bottom: 30px;
      font-weight: bold;
      color: #333;
    }
  </style>
</head>
<body>
  <div class="login-box">
    <h3>Ingreso al Sistema</h3>
    <?php if (isset($error)): ?>
      <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="mb-3">
        <label class="form-label">Usuario</label>
        <input type="text" name="usuario" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Contraseña</label>
        <input type="password" name="clave" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">Ingresar</button>
    </form>
  </div>
</body>
</html>