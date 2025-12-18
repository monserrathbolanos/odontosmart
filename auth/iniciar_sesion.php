<?php
// iniciar_sesion.php
// Formulario de inicio de sesión.

session_start();
require '../config/csrf.php';

$csrf_token = generate_csrf_token();

// Si ya hay sesión, no mostrar el formulario
if (!empty($_SESSION['user'])) {
    header('Location: protegido.php');
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión - OdontoSmart</title>

    <link rel="icon" type="image/png" href="../assets/img/odonto1.png">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Fuente -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Estilos de esta página -->
    <link rel="stylesheet" href="../assets/css/iniciar_sesion.css">
</head>

<body>

    <!-- Logo decorativo -->
    <img src="../assets/img/odonto.png" class="logo-fixed" alt="OdontoSmart">

    <div class="card shadow p-4">
        <h2 class="text-center mb-4"><strong>Iniciar sesión</strong></h2>

        <!-- Mensajes -->
        <?php if (!empty($_GET['error'])): ?>
            <div class="alert alert-danger text-center" role="alert">
                <?= htmlspecialchars($_GET['error']) ?>
            </div>
        <?php elseif (!empty($_GET['info'])): ?>
            <div class="alert alert-success text-center" role="alert">
                <?= htmlspecialchars($_GET['info']) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($_GET['reset']) && $_GET['reset'] == '1'): ?>
            <div class="alert alert-success alert-dismissible fade show text-center" role="alert">
                <strong>Contraseña actualizada correctamente.</strong> Ya puedes iniciar sesión.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php endif; ?>

        <!-- Formulario -->
        <form method="POST" action="autenticar.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control"
                    required
                    autocomplete="username"
                >
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control"
                    required
                    autocomplete="current-password"
                >
            </div>

            <button type="submit" class="btn btn-success w-100">
                <strong>Iniciar sesión</strong>
            </button>
        </form>

        <!-- Enlaces -->
        <div class="text-center mt-3">
            <a href="../modulos/usuarios/crear_usuarios.php">Crear cuenta</a><br>
            <a href="olvidar_contrasena.php">¿Olvidaste tu contraseña?</a>
        </div>

        <div class="mt-3">
            <a href="../index.php" class="btn btn-success btn-sm" style="width: 70px;">
                Inicio
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
