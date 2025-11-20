<?php
session_start();

//  Verificación de sesión
if (!isset($_SESSION['user'])) {
    header('Location: login.php?error=' . urlencode('Debes iniciar sesión.'));
    exit;
}

//  Cabeceras de seguridad
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

//  Datos del usuario
$nombre_completo = htmlspecialchars($_SESSION['user']['nombre_completo']);
$email = htmlspecialchars($_SESSION['user']['email']);
$role = htmlspecialchars($_SESSION['user']['role']);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Área protegida</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">

<div class="card shadow p-4 text-center" style="max-width: 500px; width: 100%;">
    <h2>Bienvenido, <?= $nombre_completo ?> </h2>
    <p><strong>Correo:</strong> <?= $email ?></p>
    <p><strong>Rol:</strong> <?= $role ?></p>
    <hr>
    <a href="logout.php" class="btn btn-danger w-100">Cerrar sesión</a>
    <a href="/Odontosmart/public/home.php" class="btn btn-secondary w-100">Continuar</a>
</div>

</body>
</html>