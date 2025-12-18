<?php
/*
  enviar_contrasena.php
  Procesa la solicitud de recuperación de contraseña
  MODO ESTRICTO: no adapta el código a BD vieja, solo captura errores
*/

require '../config/conexion.php';

// Forzar a mysqli a lanzar excepciones
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/../config/alerts.php';

try {

    // 1) Validar que venga el correo
    if (!isset($_POST['email'])) {
        stopWithAlert('Solicitud inválida.', 'Solicitud inválida', 'error');
    }

    $emailForm = trim($_POST['email']);

    // 2) Buscar usuario por email
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
    $id_usuario = (int)$data['id_usuario'];
    $stmtUser->close();

    // 3) Limpiar tokens anteriores
    $del = $conn->prepare("DELETE FROM restablecer_contrasenas WHERE id_usuario = ?");
    $del->bind_param("i", $id_usuario);
    $del->execute();
    $del->close();

    // 4) Generar token y expiración
    $token  = bin2hex(random_bytes(32));
    $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // 5) INSERT ORIGINAL (DEBE FALLAR EN BD VIEJA)
    $stmt = $conn->prepare("
        INSERT INTO restablecer_contrasenas (id_usuario, token, expira)
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param("iss", $id_usuario, $token, $expira);

    // Aquí ocurre el error y salta al catch
    $stmt->execute();

    // Si llegara a pasar (BD correcta), no es error
    $stmt->close();

    // 6) Registrar bitácora SOLO si el INSERT funcionó
    $ip         = $_SERVER['REMOTE_ADDR']     ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $accion   = 'RECOVERY_REQUEST';
    $modulo   = 'login';
    $detalles = 'Usuario solicitó enlace de recuperación de contraseña.';

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

    // 7) Generar link (SOLO SI TODO SALIÓ BIEN)
    $link = "http://localhost/odontosmart/auth/restablecer_contrasena.php?token=$token";

} catch (Throwable $e) {

    // Intentar rollback si hay transacción activa
    try {
        if (isset($conn) && $conn instanceof mysqli) {
            if (isset($conn) && $conn instanceof mysqli && method_exists($conn, 'in_transaction') && $conn->in_transaction()) {
                try { $conn->rollback(); } catch (Throwable $__ignore) {}
            }
        }
    } catch (Throwable $__ignored) {}

    // Preparar datos de bitácora
    $id_usuario_log = $id_usuario ?? null;
    $accion         = 'RECOVERY_ERROR';
    $modulo         = 'login';
    $ip             = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $user_agent     = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
    $detalles       = 'Error técnico: ' . $e->getMessage();

    // Registrar en bitácora usando conexión limpia, protegida en su propio try
    try {
        if (isset($conn)) { @$conn->close(); }
        include_once __DIR__ . '/../config/conexion.php';

        $stmtLog = $conn->prepare("CALL SP_USUARIO_BITACORA(?, ?, ?, ?, ?, ?)");
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
        if (isset($conn)) { @$conn->close(); }
    } catch (Throwable $logError) {
        error_log("Fallo al escribir en bitácora (recovery): " . $logError->getMessage());
    }

    // Mensaje limpio al usuario y detener flujo
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
    <!-- FAVICON -->
    <link rel="icon" type="image/png" href="../assets/img/odonto1.png">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
    body {
        margin: 0;
        padding: 0;
        font-family: 'Poppins', sans-serif;
        color: #fff;
        background: linear-gradient(270deg , #D5E7F2, #69B7BF, #d5e7f2);
        background-size: 300% 300%;
        animation: rgbFlow 100s ease infinite;
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    @keyframes rgbFlow {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
    .card {
        background: #ffffffaf;
        color: #000;
        border-radius: 16px;
        padding: 30px;
        max-width: 500px;
        margin: auto;
        box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 28px rgba(0,0,0,0.3);
    }
    h3 {
        color: #69B7BF;
        margin-bottom: 20px;
        text-align: center;
    }
    .btn-success {
        background: #69B7BF;
        border: none;
        color: #fff;
        font-weight: bold;
    }
    .btn-success:hover {
        background: #264CBF;
        transform: scale(1.05);
    }
    .btn-secondary {
        background: #182940;
        border: none;
        color: #fff;
        font-weight: bold;
    }
    .btn-secondary:hover {
        background: #264CBF;
        transform: scale(1.05);
    }
    </style>
</head>

<body class="d-flex align-items-center justify-content-center vh-100">

    <div class="card shadow-lg text-center">
        <h3>Enlace de recuperación generado</h3>

        <p class="mt-3">
            Copia y abre el siguiente enlace para restablecer tu contraseña:
        </p>

        <a href="<?= $link ?>" target="_blank" class="btn btn-success w-100 mb-3">
            Abrir enlace de recuperación
        </a>

        <small style="color:#333; word-break: break-all;">
            <?= $link ?>
        </small>

        <a href="iniciar_sesion.php" class="btn btn-secondary w-100 mt-4">
            Volver al inicio de sesión
        </a>
    </div>

</body>
</html>
