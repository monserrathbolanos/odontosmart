<?php
// --- cerrar_sesion.php ---
// Cierra la sesión de forma segura y redirige al ingresar

session_start();

//  Limpiar todas las variables de sesión
$_SESSION = [];

//  Eliminar la cookie de sesión (si existe)
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

//  Destruir la sesión completamente
session_destroy();

//  Redirigir al ingresar con mensaje de confirmación
header('Location: iniciar_sesion.php?info=' . urlencode('Sesión cerrada correctamente.'));
exit;
?>