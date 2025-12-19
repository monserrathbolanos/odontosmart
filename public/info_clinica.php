<?php

// Página informativa sobre la clínica OdontoSmart

session_start();


// Verifica que el usuario tenga un rol permitido para acceder
$rol = $_SESSION['user']['role'] ?? null;
$rolesPermitidos = ['Administrador', 'Médico', 'Cliente', 'Recepcionista'];

if (!in_array($rol, $rolesPermitidos, true)) {
    header(
        'Location: ../auth/iniciar_sesion.php?error=' .
        urlencode('Debes iniciar sesión o registrarte.')
    );
    exit;
}


// Se incluye la conexión por consistencia en todo el proyecto
include('../config/conexion.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sobre Nosotros - OdontoSmart</title>

    <!-- Favicon del sistema -->
    <link rel="icon" href="/odontosmart/assets/img/odonto1.png">

    <!-- Fuente principal -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">

    <!-- Estilos -->
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/info_clinica.css">
</head>
<body>

    <!-- Sidebar del sistema -->
    <?php include('../views/sidebar.php'); ?>

    <!-- Contenido principal -->
    <div class="content">
        <div class="seccion">

            <h1 style="color:#69B7BF;">Sobre Nosotros - Clínica OdontoSmart</h1>

            <p>
                Bienvenidos a la Clínica OdontoSmart. Somos un equipo de profesionales
                dedicados a brindar atención odontológica de calidad, con un enfoque
                humano y cercano a nuestros pacientes.
            </p>

            <div class="mision-vision">
                <div class="card">
                    <h3>Nuestra Misión</h3>
                    <p>
                        Cuidar tu salud bucal y ofrecerte tratamientos modernos,
                        seguros y accesibles. Creemos en la prevención y en el
                        acompañamiento constante de nuestros pacientes.
                    </p>
                </div>

                <div class="card">
                    <h3>Nuestra Visión</h3>
                    <p>
                        Ser la clínica dental de referencia en la comunidad,
                        reconocida por la calidad del servicio y el compromiso
                        con la salud bucal.
                    </p>
                </div>
            </div>

            <p>
                Este sitio web fue creado para facilitar el acceso a la información
                y a los servicios de la clínica, mejorando la experiencia del usuario.
            </p>

            <p>
                <strong>
                    ¡Gracias por preferirnos! Tu salud dental es nuestra prioridad.
                </strong>
            </p>

        </div>
    </div>

</body>
</html>
