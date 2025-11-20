<?php
session_start();
require '../config/csrf.php'; //Incluye el archivo donde tienes la función que crea tokens CSRF.
$csrf_token = generate_csrf_token();   //Genera un token único de seguridad

// Si el usuario ya inició sesión, lo redirige al área protegida
if (!empty($_SESSION['user'])) {
    header('Location: protected.php');
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Login</title>
    <!--  Enlace correcto a Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">  
</head>


<style>
  body {
  background: linear-gradient(270deg, #152FBF, #264CBF, #182940, #D5E7F2, #69B7BF); /* colores seleccionados */
   /* background-image: url(' Odonto.png');  */
  background-size: 300% 300%;
  animation: rgbFlow 150s ease infinite;
  font-family: 'Poppins', sans-serif;
  color: #ffffff;
}

</style>

<body class="bg-light d-flex align-items-center justify-content-center vh-100">

<div class="card shadow p-4" style="max-width: 400px; width: 100%;">
    <h2 class="text-center mb-4"><strong>Iniciar sesión</strong></h2>

    <!-- Mensajes de error o información -->
    <?php if (!empty($_GET['error'])): ?>
        <div class="alert alert-danger text-center"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php elseif (!empty($_GET['info'])): ?>
        <div class="alert alert-success text-center"><?= htmlspecialchars($_GET['info']) ?></div>
    <?php endif; ?>

    <!-- Formulario de autenticación -->
    <form method="POST" action="authenticate.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" name="email" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Contraseña</label>
            <input id="password" name="password" type="password" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-success w-100"><strong>Iniciar sesión</strong></button>
    </form>

    <div class="text-center mt-3">
        <a href="../modulos/usuarios/create_users.php">Crear cuenta</a>
        <a href="forgot_password.php" class="text-decoration-none">¿Olvidaste tu contraseña?</a>
        

</div>
        <div class="mt-3">
    <a href="../index.php" class="btn btn-primary  W-89" style="width: 70px;">Inicio</a>
</div>

        
    </div>
</div>

</body>
</html>