
<?php

// --- Función para generar un token CSRF único ---

function generate_csrf_token() {

    // Si no existe un token en la sesión, se genera uno nuevo

    if (empty($_SESSION['csrf_token'])) {
        // Crea un token seguro de 64 caracteres hexadecimales
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    // Devuelve el token actual (nuevo o existente)
    return $_SESSION['csrf_token'];
}

// --- Función para validar el token CSRF recibido desde el formulario ---

function validate_csrf_token($token) {
    // Verifica que el token exista en la sesión y que coincida exactamente con el recibido

    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
