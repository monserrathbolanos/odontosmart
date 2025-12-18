<?php
// olvidar_contrasena.php
// Pantalla para iniciar el proceso de recuperación de contraseña.
// El usuario ingresa su correo y se envía a enviar_contrasena.php.
// Esta funcionalidad es opcional y no forma parte del flujo principal del sistema.
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar contraseña</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/img/odonto1.png">

    <!-- Fuente Poppins -->
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins&display=swap"
        rel="stylesheet"
    >

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Estilos propios de esta página -->
    <link rel="stylesheet" href="../assets/css/olvidar_contrasena.css">
</head>

<body>

    <!-- Tarjeta con el formulario de recuperación -->
    <div class="card shadow">
        <h3 class="text-center mb-4 fw-bold">
            Recuperar contraseña
        </h3>

        <!-- Formulario -->
        <form method="POST" action="enviar_contrasena.php">

            <div class="mb-3">
                <label for="email" class="form-label">
                    Correo electrónico
                </label>
                <input
                    type="email"
                    name="email"
                    id="email"
                    class="form-control"
                    required
                >
            </div>

            <button type="submit" class="btn btn-custom w-100">
                Enviar enlace de recuperación
            </button>

            <!-- Volver a inicio de sesión -->
            <a href="iniciar_sesion.php" class="btn btn-secondary w-100 mt-3">
                Volver
            </a>
        </form>
    </div>

</body>
</html>
