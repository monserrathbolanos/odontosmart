<?php

//   home.php
  
//   Página principal (dashboard de inicio) del sistema OdontoSmart.
 
//   Responsabilidades:
//   - Validar que el usuario haya iniciado sesión.
//   - Determinar el rol del usuario autenticado.
//   - Mostrar un panel de bienvenida con información básica del perfil.
//   - Integrarse con el menú de navegación global (navbar.php).
 
//   Dependencias:
//   - Sesión PHP: requiere que el proceso de login haya definido $_SESSION['user'].
//  - ../views/navbar.php: componente de navegación principal.
//   - Recursos estáticos: ../assets/img/odonto1.png (logo) y favicon /odontosmart/assets/img/odonto1.png.
 

session_start(); // Inicializa o reanuda la sesión de usuario.

/* Validar rol permitido */
$rol = $_SESSION['user']['role'] ?? null;
$rolesPermitidos = ['Administrador', 'Médico','Cliente','Recepcionista']; // ej.

if (!in_array($rol, $rolesPermitidos)) {
    // Aquí decides a dónde mandarlo: login, home o protegido.
    // Si quieres mandarlo al login:
    header('Location: ../auth/iniciar_sesion.php?error=' . urlencode('Debes iniciar sesión o registrarte.'));
    exit;
}

//   CONTROL DE ACCESO 
//   -----------------
//  Si no  existe información de usuario en la sesión, se asume que el acceso 
//  es válido y se redirige al formulario de inicio de sesión.
 
if (!isset($_SESSION['user'])) {
    header("Location: iniciar_sesion.php?error=Acceso no autorizado");
    exit;
}


//   Extracción de datos relevantes del usuario autenticado.
//   Estos valores se utilizarán para personalizar el contenido del dashboard.
 
//   Campos esperados en $_SESSION['user']:
//  - role            (string): Rol del usuario (cliente, administrador, medico, etc.).
//  - nombre_completo (string): Nombre completo del usuario.
 
$rol = $_SESSION['user']['role'];                  // Ejemplo: 'cliente', 'administrador', 'medico','recepcionista'
$username = $_SESSION['user']['nombre_completo'];  // Nombre completo para mostrar en la vista.
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>
        OdontoSmart - Clínica Dental | Perfil:
        <?php echo htmlspecialchars(ucfirst($rol)); ?>
    </title>

    <!--
        FAVICON UNIFICADO
        -----------------
        Ícono global del sistema OdontoSmart, utilizado en todas las vistas.
    -->
    <link rel="icon" href="/odontosmart/assets/img/odonto1.png">

    <!-- Fuente principal utilizada en la interfaz -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">

    <!--
        HOJA DE ESTILOS INTERNA
      
        Estilos específicos para la estructura del dashboard inicial.
        En una arquitectura más escalable, estos estilos podrían migrarse a
        un archivo CSS externo compartido.
    -->
    <style>
        /* Estilos base de la página */
        body {
            font-family: 'Roboto', Arial, sans-serif; /* Tipografía corporativa */
            margin: 0;
            padding: 0;
            background: linear-gradient(to bottom right, #f5f9fc, #e0f7fa);
            color: #333;
        }

        /* Contenedor de la barra de navegación lateral */
        .navbar {
            width: 220px;                      /* Ancho fijo del menú vertical */
            background-color: #69B7BF;         /* Color corporativo OdontoSmart */
            height: 100vh;                     /* Altura completa de la ventana */
            padding-top: 20px;
            position: fixed;                   /* Se mantiene fijo al hacer scroll */
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            transition: width 0.3s ease;
        }

        /* Estilo de los enlaces dentro de la barra de navegación */
        .navbar a {
            display: block;
            color: #fff;
            padding: 14px 20px;
            text-decoration: none;
            margin: 10px;
            border-radius: 8px;
            transition: background 0.3s, transform 0.2s;
        }

        .navbar a:hover {
            background-color: #264cbf;         /* Color de realce al pasar el cursor */
            transform: scale(1.05);            /* Efecto de zoom ligero */
        }

        /* Área principal de contenido, desplazada para no solaparse con el sidebar */
        .content {
            margin-left: 240px;                /* Margen acorde al ancho de la navbar */
            padding: 40px;
        }

        .content h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .content h2 {
            color: #264CBF;
            margin-bottom: 20px;
        }

        .content p {
            line-height: 1.6;
            margin-bottom: 20px;
        }

        /* Logo institucional ubicado en la parte inferior del menú lateral */
        .logo-navbar {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);       /* Centrado horizontal */
            width: 140px;
            opacity: 0.9;
            transition: transform 0.3s;
        }

        .logo-navbar:hover {
            transform: translateX(-50%) scale(1.1); /* Efecto de zoom al pasar el cursor */
        }

        /* Contenedor flex para las tarjetas de Misión y Visión */
        .mision-vision {
            display: flex;
            gap: 20px;                         /* Separación entre tarjetas */
            margin: 30px 0;
            flex-wrap: wrap;                   /* Permite adaptación en pantallas pequeñas */
        }

        /* Componente de tarjeta reutilizable (Misión / Visión / otros bloques) */
        .card {
            background: #fff;
            flex: 1 1 45%;                     /* Ancho proporcional dentro del contenedor */
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);        /* Levanta la tarjeta visualmente */
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        }

        .card h3 {
            color: #69B7BF;                    /* Color corporativo para títulos de tarjetas */
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

    <!--
        BLOQUE: BARRA DE NAVEGACIÓN LATERAL
        -----------------------------------
        Este bloque integra el menú principal reutilizable en todo el sistema.
        La estructura de enlaces y la lógica visual por rol se definen en
        ../views/navbar.php.
    -->
    <div class="navbar">
        <?php
        /**
         * Inclusión del componente de navegación principal.
         * Suele contener:
         * - Enlaces a módulos (Dashboard, Pacientes, Citas, Inventario, Ventas, etc.).
         * - Lógica de visibilidad según el rol del usuario (si se implementa en la vista).
         */
        include('../views/navbar.php');
        ?>

        <!-- Logo institucional OdontoSmart dentro del sidebar -->
        <img src="../assets/img/odonto1.png" class="logo-navbar" alt="Logo OdontoSmart">
    </div>

    <!--
        BLOQUE: CONTENIDO PRINCIPAL DEL DASHBOARD
        -----------------------------------------
        Muestra un mensaje de bienvenida personalizado y datos básicos
        del perfil (rol actual). Esta vista funciona como página de inicio
        para cualquier usuario autenticado, independientemente de su rol.
    -->
    <div class="content">
        <!-- Título general del sistema -->
        <h1 style="color: #69B7BF;">OdontoSmart - Clínica Dental</h1>

        <!-- Mensaje de bienvenida personalizado con el nombre del usuario -->
        <h2 style="color:#264CBF;">
            Bienvenido(a):
            <?php echo htmlspecialchars($username); ?>
        </h2>

        <!-- Indicación del perfil/rol actual del usuario -->
        <h2>
            Perfil actual:
            <?php echo htmlspecialchars(ucfirst($rol)); ?>
        </h2>

        <!-- Contenido institucional general (similar al de "Sobre Nosotros") -->
        <p>
            Bienvenidos a la Clínica OdontoSmart. Somos un equipo de profesionales dedicados
            a brindar atención odontológica de calidad, con un enfoque humano y cercano a
            nuestros pacientes.
        </p>

        <!-- Sección de Misión y Visión en formato de tarjetas -->
        <div class="mision-vision">
            <div class="card">
                <h3>Nuestra Misión</h3>
                <p>
                    Cuidar tu salud bucal y ofrecerte tratamientos modernos, seguros y accesibles.
                    Creemos en la importancia de la prevención y en acompañarte en cada etapa
                    de tu cuidado dental.
                </p>
            </div>

            <div class="card">
                <h3>Nuestra Visión</h3>
                <p>
                    Ser la clínica dental de referencia en la comunidad, reconocida por nuestra
                    excelencia en el servicio y compromiso con la salud bucal de nuestros pacientes.
                </p>
            </div>
        </div>

        <!-- Texto explicativo sobre el propósito del sitio web -->
        <p>
            Este sitio web fue creado con la idea de que puedas tener una mejor experiencia como
            usuario de nuestros servicios, y que puedas acceder fácilmente a la información y
            herramientas que necesitas para cuidar tu salud bucal.
        </p>

        <!-- Mensaje de cierre institucional -->
        <p>
            <strong>¡Gracias por preferirnos y estar aquí! Tu salud dental es nuestra prioridad.</strong>
        </p>

        <!--
            NOTA:
            La opción de "Cerrar sesión" se encuentra incluida en el componente navbar.php,
            por lo que no es necesario duplicar el enlace en esta vista.
        -->
        <!--
        <a href="http://localhost/ProyectoOdonto/auth/cerrar_sesion.php"
           style="text-align:center; display:block;">
           Cerrar sesión
        </a>
        -->
    </div>
</body>
</html>
