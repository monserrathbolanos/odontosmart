<?php
session_start();
require '../config/csrf.php'; //Incluye el archivo donde tienes la función que crea tokens CSRF.
$csrf_token = generate_csrf_token();   //Genera un token único de seguridad

// Si el usuario ya inició sesión, lo redirige al área protegida
if (!empty($_SESSION['user'])) {
    header('Location: protegido.php');
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Iniciar Sesión</title>

    <!-- FAVICON -->
    <link rel="icon" type="image/png" href="../assets/img/odonto1.png">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">  
</head>

<img src="../assets/img/odonto.png" class="logo-fixed" alt="OdontoSmart">


<style>
  body {
    margin: 0;
    padding: 0;
    font-family: 'Poppins', sans-serif;
    color: #fff;
    background: linear-gradient(270deg, #152FBF, #264CBF, #182940, #D5E7F2, #69B7BF);
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
    position: relative;
    background: #ffffffaf; /* fondo sólido */
    color: #000;
    border-radius: 16px;
    padding: 30px;
    max-width: 400px;
    width: 100%;
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 28px rgba(0,0,0,0.3);
}

.card h2 {
    color: #152FBF;  /* azul principal de la paleta */
}

.form-control {
    border-radius: 8px;
    padding: 10px 12px;
    font-size: 1em;
}

.btn-success {
    background: #69B7BF;  /* verde agua de la paleta */
    border: none;
    border-radius: 8px;
    font-weight: bold;
    transition: 0.3s;
}

.btn-success:hover {
    background: #264CBF;  /* azul secundario */
    transform: scale(1.05);
}

a {
    color: #264CBF;  /* azul secundario */
    transition: color 0.3s;
}

a:hover {
    color: #69B7BF;  /* azul principal */
    text-decoration: underline;
}

@media (max-width: 500px) {
    .card {
        padding: 20px;
    }
    
}

/* .background {
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: linear-gradient(270deg, #264CBF, #D5E7F2, #69B7BF);
    background-size: 300% 300%;
    animation: rgbFlow 200s ease infinite;
    z-index: -1;
} */

/* .logo-fixed {
    position: fixed;
    left: 20px;
    bottom: 20px;
    width: 120px;
    opacity: 0.9;
} */

    .form-label {
    color: #182940;  /* texto oscuro */
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
    <form method="POST" action="autenticar.php">
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
        <a href="../modulos/usuarios/crear_usuarios.php">Crear cuenta</a>
        <a href="olvidar_contrasena.php" class="text-decoration-none">¿Olvidaste tu contraseña?</a>
        

</div>
        <div class="mt-3">
    <a href="../index.php" class="btn btn-success  W-89" style="width: 70px;">Inicio</a>
</div>

        
    </div>
</div>

</body>
</html>