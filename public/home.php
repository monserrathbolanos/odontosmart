<?php

// Página principal del sistema OdontoSmart para usuarios autenticados

session_start();


// Control de acceso: verifica que la sesión esté activa
if (!isset($_SESSION['user'])) {
    header('Location: ../auth/iniciar_sesion.php?error=' . urlencode('Acceso no autorizado.'));
    exit;
}


// Verifica que el usuario tenga un rol permitido
$rol = $_SESSION['user']['role'] ?? null;
$rolesPermitidos = ['Administrador', 'Médico', 'Cliente', 'Recepcionista'];

if (!in_array($rol, $rolesPermitidos, true)) {
    header('Location: ../auth/iniciar_sesion.php?error=' . urlencode('Debes iniciar sesión.'));
    exit;
}


// Obtiene los datos del usuario para mostrar en la vista

$username = $_SESSION['user']['nombre_completo'] ?? 'Usuario';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">

    <title>
        OdontoSmart | Perfil: <?= htmlspecialchars(ucfirst($rol)) ?>
    </title>

    <!-- Favicon -->
    <link rel="icon" href="/odontosmart/assets/img/odonto1.png">

    <!-- Fuente base -->
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap"
        rel="stylesheet"
    >

    <!-- Estilos -->
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/home.css">
</head>

<body>

    <!-- Sidebar / sidebar lateral -->
    <?php include('../views/sidebar.php'); ?>

    <!-- Contenido principal -->
    <main class="content">

        <h1 style="color:#69B7BF;">OdontoSmart - Clínica Dental</h1>

        <h2 style="color:#264CBF;">
            Bienvenido(a): <?= htmlspecialchars($username) ?>
        </h2>

        <h2>
            Perfil actual: <?= htmlspecialchars(ucfirst($rol)) ?>
        </h2>

        <p>
            Bienvenidos a la Clínica OdontoSmart. Somos un equipo de profesionales
            dedicados a brindar atención odontológica de calidad, con un enfoque
            humano y cercano a nuestros pacientes.
        </p>

        <div class="mision-vision">
            <div class="card">
                <h3>Nuestra Misión</h3>
                <p>
                    Cuidar tu salud bucal y ofrecerte tratamientos modernos, seguros
                    y accesibles, acompañándote en cada etapa de tu cuidado dental.
                </p>
            </div>

            <div class="card">
                <h3>Nuestra Visión</h3>
                <p>
                    Ser la clínica dental de referencia en la comunidad, reconocida
                    por nuestra excelencia y compromiso con la salud bucal.
                </p>
            </div>
        </div>

        <p>
            Este sitio web fue creado para brindarte una mejor experiencia como
            usuario y facilitar el acceso a nuestros servicios odontológicos.
        </p>

        <p>
            <strong>
                ¡Gracias por preferirnos! Tu salud dental es nuestra prioridad.
            </strong>
        </p>

    </main>

</body>
</html>
