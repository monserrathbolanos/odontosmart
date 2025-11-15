<?php
// --- logout.php ---
// Cierra la sesión de forma segura y redirige al login

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

//  Redirigir al login con mensaje de confirmación
header('Location: login.php?info=' . urlencode('Sesión cerrada correctamente.'));
exit;
?>
