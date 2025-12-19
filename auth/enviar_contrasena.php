<?php

// Procesa la solicitud de recuperación de contraseña

require '../config/conexion.php';
require_once __DIR__ . '/../config/alerts.php';

// Configura mysqli para lanzar excepciones
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {

    // Verifica que el formulario haya enviado el correo
    if (!isset($_POST['email'])) {
        stopWithAlert('Solicitud inválida.', 'Solicitud inválida', 'error');
    }

    $emailForm = trim($_POST['email']);

    // Busca el usuario por correo
    $stmtUser = $conn->prepare("
        SELECT id_usuario, email
        FROM usuarios
        WHERE email = ?
    ");
    $stmtUser->bind_param("s", $emailForm);
    $stmtUser->execute();
    $resultUser = $stmtUser->get_result();

    if ($resultUser->num_rows === 0) {
        $stmtUser->close();
        stopWithAlert('No existe una cuenta con ese correo.', 'No existe cuenta', 'error');
    }

    $data       = $resultUser->fetch_assoc();
    $id_usuario = (int) $data['id_usuario'];
    $stmtUser->close();

    // Elimina tokens anteriores del usuario
    $del = $conn->prepare("
        DELETE FROM restablecer_contrasenas
        WHERE id_usuario = ?
    ");
    $del->bind_param("i", $id_usuario);
    $del->execute();
    $del->close();

    // Genera un token y la fecha de expiración
    $token  = bin2hex(random_bytes(32));
    $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Inserta el nuevo token (puede fallar en bases de datos antiguas)
    $stmt = $conn->prepare("
        INSERT INTO restablecer_contrasenas (id_usuario, token, expira)
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param("iss", $id_usuario, $token, $expira);
    $stmt->execute();
    $stmt->close();

    // Registrar bitácora solo si el proceso anterior funcionó
    $ip         = $_SERVER['REMOTE_ADDR']     ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $accion   = 'RECOVERY_REQUEST';
    $modulo   = 'login';
    $detalles = 'Usuario solicitó enlace de recuperación de contraseña.';

    $stmtLog = $conn->prepare("
        CALL SP_USUARIO_BITACORA(?, ?, ?, ?, ?, ?)
    ");
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

    // Generar enlace de recuperación
    $link = "http://localhost/odontosmart/auth/restablecer_contrasena.php?token=$token";

} catch (Throwable $e) {

    // Intentar rollback si existe una transacción activa
    try {
        if (
            isset($conn)
            && $conn instanceof mysqli
            && method_exists($conn, 'in_transaction')
            && $conn->in_transaction()
        ) {
            $conn->rollback();
        }
    } catch (Throwable $ignore) {}

    // Preparar datos para bitácora de error
    $id_usuario_log = $id_usuario ?? null;
    $accion         = 'RECOVERY_ERROR';
    $modulo         = 'login';
    $ip             = $_SERVER['REMOTE_ADDR']     ?? 'UNKNOWN';
    $user_agent     = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
    $detalles       = 'Error técnico: ' . $e->getMessage();

    // Registrar el error en bitácora usando una conexión limpia
    try {
        if (isset($conn)) {
            @$conn->close();
        }

        require '../config/conexion.php';

        $stmtLog = $conn->prepare("
            CALL SP_USUARIO_BITACORA(?, ?, ?, ?, ?, ?)
        ");
        if ($stmtLog) {
            $stmtLog->bind_param(
                "isssss",
                $id_usuario_log,
                $accion,
                $modulo,
                $ip,
                $user_agent,
                $detalles
            );
            $stmtLog->execute();
            $stmtLog->close();
        }

        if (isset($conn)) {
            @$conn->close();
        }

    } catch (Throwable $logError) {
        error_log(
            "Fallo al escribir en bitácora (recuperación): "
            . $logError->getMessage()
        );
    }

    // Mensaje genérico al usuario
    stopWithAlert(
        'Ocurrió un error inesperado al procesar la solicitud. Intente más tarde.',
        'Error',
        'error'
    );
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Enlace de recuperación</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/img/odonto1.png">

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Estilos propios -->
    <link rel="stylesheet" href="../assets/css/enviar_contrasena.css">
</head>

<body class="d-flex align-items-center justify-content-center vh-100">

    <div class="card shadow-lg text-center" style="max-width: 500px;">
        <h3>Enlace de recuperación generado</h3>

        <p class="mt-3">
            Copia y abre el siguiente enlace para restablecer tu contraseña:
        </p>

        <a href="<?= $link ?>" target="_blank" class="btn btn-success w-100 mb-3">
            Abrir enlace de recuperación
        </a>

        <small class="text-muted" style="word-break: break-all;">
            <?= $link ?>
        </small>

        <a href="iniciar_sesion.php" class="btn btn-secondary w-100 mt-4">
            Volver al inicio de sesión
        </a>
    </div>

</body>
</html>
