<?php

/*
  actualizar_contrasena.php
  Endpoint que recibe el token de recuperación y la nueva contraseña
  desde el formulario de restablecimiento y actualiza la contraseña
  del usuario en la tabla `usuarios`.
 
  Flujo:
   1. Valida que la petición POST incluya `token` y `new_password`.
   2. Busca en `restablecer_contrasenas` un token válido (no expirado).
   3. Obtiene id_usuario y el correo asociado al token.
   4. Encripta la nueva contraseña.
   5. Actualiza la contraseña del usuario en `usuarios`.
   6. Elimina el token ya utilizado.
   7. Registra el evento en bitácoras.
   8. Muestra mensaje de éxito y redirige al login.
 */
require '../config/conexion.php'; // Conexión a la base de datos

// Validar que el formulario envió los campos necesarios por POST
if (!isset($_POST['token'], $_POST['new_password'])) {
    die("Solicitud inválida.");
}

// Datos recibidos desde el formulario de restablecimiento
$token        = $_POST['token'];
$new_password = $_POST['new_password'];

// 1) Buscar token válido y obtener id_usuario + email
$stmt = $conn->prepare("
    SELECT id_usuario, email 
    FROM restablecer_contrasenas 
    WHERE token = ? AND expira > NOW()
");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

// Si no existe un registro con ese token vigente, se detiene el proceso
if ($result->num_rows === 0) {
    die("Token inválido o expirado.");
}

$data       = $result->fetch_assoc();
$id_usuario = $data['id_usuario'];
$email      = $data['email'];
$stmt->close();

// 2) Encriptar contraseña nueva con password_hash
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// 3) Actualizar contraseña en la tabla `usuarios`
$update_stmt = $conn->prepare("
    UPDATE usuarios 
    SET password = ? 
    WHERE email = ?
");
$update_stmt->bind_param("ss", $hashed_password, $email);
$update_stmt->execute();
$update_stmt->close();

// 4) Eliminar el token ya usado de la tabla de restablecimiento
$delete_stmt = $conn->prepare("
    DELETE FROM restablecer_contrasenas 
    WHERE email = ?
");
$delete_stmt->bind_param("s", $email);
$delete_stmt->execute();
$delete_stmt->close();

// 5) Registrar en bitácora el restablecimiento de contraseña
$ip         = $_SERVER['REMOTE_ADDR']     ?? null;
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

$accion   = 'PASSWORD_RESET';
$modulo   = 'login';
$detalles = 'Usuario restableció su contraseña mediante enlace de recuperación.';

$stmtLog = $conn->prepare("CALL SP_USUARIO_BITACORA(?, ?, ?, ?, ?, ?)");
$stmtLog->bind_param(
    "isssss",
    $id_usuario,
    $accion,
    $modulo,
    $ip,
    $user_agent,
    $detalles
);
$stmtLog->execute();
$stmtLog->close();

// 6) Mensaje final y redirección al formulario de inicio de sesión
echo "
<script>
alert('Contraseña actualizada correctamente');
window.location.href='iniciar_sesion.php';
</script>
";
