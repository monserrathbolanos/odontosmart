<?php
/**
 * protegido.php
 * --------------
 * Vista de área protegida intermedia después del inicio de sesión.
 *
 * Responsabilidades:
 *  - Verificar que exista una sesión de usuario activa.
 *  - Aplicar cabeceras de seguridad HTTP básicas.
 *  - Mostrar información básica del usuario autenticado.
 *  - Ofrecer acciones para:
 *      - Cerrar sesión.
 *      - Continuar al panel principal (home.php).
 */

session_start();

// 1️⃣ Verificación de sesión
// Si no hay información de usuario en la sesión, se redirige al login.
if (!isset($_SESSION['user'])) {
    header('Location: iniciar_sesion.php?error=' . urlencode('Debes iniciar sesión.'));
    exit;
}

// 2️⃣ Cabeceras de seguridad
// Ayudan a mitigar algunos tipos de ataques del lado del navegador.
header('Cache-Control: no-store, no-cache, must-revalidate'); // Evita caché de esta página
header('X-Frame-Options: DENY');                             // Evita que se cargue en iframes (clickjacking)
header('X-Content-Type-Options: nosniff');                   // Evita que el navegador "adivine" el tipo de contenido
header('X-XSS-Protection: 1; mode=block');                   // Activa filtro XSS en navegadores antiguos

// 3️⃣ Datos del usuario
// Se escapan con htmlspecialchars para evitar inyección de HTML en la vista.
$nombre_completo = htmlspecialchars($_SESSION['user']['nombre_completo']);
$email           = htmlspecialchars($_SESSION['user']['email']);
$role            = htmlspecialchars($_SESSION['user']['role']);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Área protegida</title>

    <!-- Bootstrap 5 para maquetado y estilos rápidos -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">

<style>
    /* Fondo general con gradiente (opcional animación) */
    body {
      margin: 0;
      padding: 0;
      font-family: 'Poppins', sans-serif;
      color: #fff;
      background: linear-gradient(270deg, #D5E7F2, #69B7BF, #d5e7f2);
      background-size: 300% 300%;
      animation: rgbFlow 100s ease infinite;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    /* Definición de la animación del fondo (si se desea activar) */
    @keyframes rgbFlow {
      0%   { background-position: 0% 50%; }
      50%  { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }

    /* Tarjeta que contiene la información del usuario y las acciones */
    .card {
      background-color: rgba(219, 210, 210, 0.94); /* Fondo semitransparente */
      padding: 30px;
      border-radius: 10px;
      max-width: 400px;
      width: 100%;
    }

    /* Estilo del texto de etiquetas (si se usan) */
    label {
      color: #020202ff;
    }

    /* Estilo personalizado para algún botón (no usado en este HTML concreto) */
    .btn-custom {
      background-color: #ffffff;
      color: #152fbf;
      font-weight: bold;
    }

    .btn-custom:hover {
      background-color: #e0e0e0;
    }
</style>

<!--
    TARJETA DE ÁREA PROTEGIDA
    -------------------------
    Muestra datos básicos del usuario logueado y dos acciones principales:
    - Cerrar sesión.
    - Continuar al home principal del sistema.
-->
<div class="card shadow p-4 text-center" style="max-width: 500px; width: 100%;">
    <h2>Bienvenido, <?= $nombre_completo ?> </h2>
    <p><strong>Correo:</strong> <?= $email ?></p>
    <p><strong>Rol:</strong> <?= $role ?></p>
    <hr>
    <!-- Botón para cerrar sesión (invoca cerrar_sesion.php) -->
    <a href="cerrar_sesion.php" class="btn btn-danger w-100">Cerrar sesión</a>

    <!-- Botón para continuar al home principal -->
    <a href="/Odontosmart/public/home.php" class="btn btn-secondary w-100 mt-2">Continuar</a>
</div>

</body>
</html>
