<?php
// --- cerrar_sesion.php ---
// Cierra la sesión de forma segura y redirige al formulario de ingreso.

session_start(); // Inicia o reanuda la sesión para poder destruirla

require '../config/conexion.php'; // Conexión a la BD para registrar en bitácora

// Datos para bitácora ANTES de borrar la sesión
$id_usuario = $_SESSION['user']['id_usuario'] ?? null;
$ip         = $_SERVER['REMOTE_ADDR']     ?? null;
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

if ($id_usuario !== null) {
    $accion   = 'LOGOUT';
    $modulo   = 'login';
    $detalles = 'Cierre de sesión del usuario.';

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
}

// 1) Limpiar todas las variables de sesión
$_SESSION = [];

// 2) Eliminar la cookie de sesión (si existe)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// 3) Destruir la sesión completamente en el servidor
session_destroy();

// 4) Redirigir al login con mensaje de confirmación
header('Location: iniciar_sesion.php?info=' . urlencode('Sesión cerrada correctamente.'));
exit;
?>
