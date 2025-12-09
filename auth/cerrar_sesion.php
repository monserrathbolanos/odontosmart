<?php
// --- cerrar_sesion.php ---
// Cierra la sesión de forma segura y redirige al formulario de ingreso.

session_start(); // Inicia o reanuda la sesión para poder destruirla

// 1Limpiar todas las variables de sesión
// Se borra el contenido del array $_SESSION, pero la sesión aún existe en el servidor.

$_SESSION = [];

// Eliminar la cookie de sesión (si existe)
// Esto es importante para que el navegador "olvide" el identificador de sesión.

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), // Nombre de la cookie de sesión
        '',             // Valor vacío
        time() - 42000, // Fecha de expiración en el pasado
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destruir la sesión completamente en el servidor
session_destroy();

// Redirigir al login con mensaje de confirmación
// Se envía el parámetro GET 'info' para mostrar un mensaje al usuario.
header('Location: iniciar_sesion.php?info=' . urlencode('Sesión cerrada correctamente.'));
exit;
?>