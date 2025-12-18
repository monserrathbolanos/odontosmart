<?php
/*
  actualizar_contrasena.php
  --------------------------
  Endpoint que recibe el token de recuperación y la nueva contraseña
  desde el formulario de restablecimiento y actualiza la contraseña
  del usuario en la tabla `usuarios`.

  Flujo:
   1. Valida que la petición POST incluya `token` y `new_password`.
   2. Busca en `restablecer_contrasenas` un token válido (no expirado),
      uniendo con `usuarios` para obtener el email.
   3. Obtiene id_usuario y el correo asociado al token.
   4. Encripta la nueva contraseña.
   5. Actualiza la contraseña del usuario en `usuarios`.
   6. Elimina el token ya utilizado.
   7. Registra el evento en bitácoras.
   8. Redirige al login.
*/

require '../config/conexion.php'; // Conexión a la base de datos
require_once __DIR__ . '/../config/alerts.php';

try {
    // Validar que venga por POST y con los campos requeridos
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
        !isset($_POST['token'], $_POST['new_password'])) {
        stopWithAlert('Solicitud inválida.', 'Solicitud inválida', 'error');
    }

    // Datos recibidos desde el formulario
    $token        = $_POST['token'];
    $new_password = $_POST['new_password'];

    // Validación de la nueva contraseña
    $pwd_ok = preg_match(
        '/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#._\-])[A-Za-z\d@$!%*?&#._\-]{8,}$/',
        $new_password
    );

    if (!$pwd_ok) {
        echo "<script>
                alert('La contraseña debe tener mínimo 8 caracteres, una mayúscula, un número y un carácter especial.');
                window.location.href='restablecer_contrasena.php?token=" . urlencode($token) . "';
              </script>";
        exit;
    }

// 1) Buscar token válido y obtener id_usuario + email (JOIN con usuarios)
    $stmt = $conn->prepare("
        SELECT rc.id_usuario, u.email
        FROM restablecer_contrasenas rc
        INNER JOIN usuarios u ON u.id_usuario = rc.id_usuario
        WHERE rc.token = ?
          AND rc.expira > NOW()
        LIMIT 1
    ");
    if (!$stmt) {
        stopWithAlert('Error al preparar la consulta de token.', 'Error interno', 'error');
    }

    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    // Si no existe un registro con ese token vigente, se detiene el proceso
    if ($result->num_rows === 0) {
        $stmt->close();
        stopWithAlert('Token inválido o expirado.', 'Token inválido', 'error');
    }

    $data       = $result->fetch_assoc();
    $id_usuario = (int)$data['id_usuario'];
    $email      = $data['email'];
    $stmt->close();

    // 2) Encriptar contraseña nueva con password_hash
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // 3) Actualizar contraseña en la tabla `usuarios` por id_usuario
    $update_stmt = $conn->prepare("
        UPDATE usuarios
        SET password = ?
        WHERE id_usuario = ?
    ");
    if (!$update_stmt) {
        stopWithAlert('Error al preparar la actualización de contraseña.', 'Error interno', 'error');
    }

    $update_stmt->bind_param("si", $hashed_password, $id_usuario);
    $update_stmt->execute();
    $update_stmt->close();

    // 4) Eliminar el/los token(s) usados de la tabla de restablecimiento
    //    Puedes borrar solo el token usado o todos los del usuario.
    //    Aquí borro por token concreto:
    $delete_stmt = $conn->prepare("
        DELETE FROM restablecer_contrasenas
        WHERE token = ?
    ");
    if ($delete_stmt) {
        $delete_stmt->bind_param("s", $token);
        $delete_stmt->execute();
        $delete_stmt->close();
    }

    // 5) Registrar en bitácora el restablecimiento de contraseña
    $ip         = $_SERVER['REMOTE_ADDR']     ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $accion   = 'PASSWORD_RESET';
    $modulo   = 'login';
    $detalles = 'Usuario restableció su contraseña mediante enlace de recuperación.';

    $stmtLog = $conn->prepare("CALL SP_USUARIO_BITACORA(?, ?, ?, ?, ?, ?)");
    if ($stmtLog) {
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
    }

    // 6) Redirigir al formulario de inicio de sesión con bandera de éxito
    header('Location: iniciar_sesion.php?reset=1');
    exit;

} catch (Throwable $e) {
    // Intentar rollback
    try {
        if (isset($conn) && $conn instanceof mysqli) {
            if (isset($conn) && $conn instanceof mysqli && method_exists($conn, 'in_transaction') && $conn->in_transaction()) {
                try { $conn->rollback(); } catch (Throwable $__ignore) {}
            }
        }
    } catch (Throwable $__ignored) {}

    // Registrar en bitácora
    try {
        if (isset($conn)) { @$conn->close(); }
        require_once '../config/conexion.php';

        $id_usuario_log = null;
        $accion = 'PASSWORD_UPDATE_ERROR';
        $modulo = 'login';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
        $detalles = 'Error al actualizar contraseña: ' . $e->getMessage();

        $stmtLog = $conn->prepare("CALL SP_USUARIO_BITACORA(?, ?, ?, ?, ?, ?)");
        if ($stmtLog) {
            $stmtLog->bind_param("isssss", $id_usuario_log, $accion, $modulo, $ip, $user_agent, $detalles);
            $stmtLog->execute();
            $stmtLog->close();
        }
        if (isset($conn)) { @$conn->close(); }
    } catch (Throwable $logError) {
        error_log("Fallo al escribir en bitácora (actualizar_contrasena): " . $logError->getMessage());
    }

    stopWithAlert('Ocurrió un error al actualizar la contraseña. Intente nuevamente.', 'Error', 'error');
}
?>
