<?php
/**
 * info_clinica.php
 * -----------------
 * Módulo de presentación de información institucional ("Sobre Nosotros")
 * del sistema OdontoSmart.
 *
 * Características técnicas:
 * - Requiere sesión iniciada únicamente si el sistema lo define a nivel global.
 * - No ejecuta operaciones sobre la base de datos en esta versión.
 * - Depende del layout de navegación definido en: ../views/navbar.php
 * - Utiliza recursos estáticos (imágenes) desde: /odontosmart/assets/img/
 */

session_start(); // Inicializa o reanuda la sesión del usuario autenticado.

/*  CONTROL DE ACCESO
   
   Si no existe un usuario en la sesión, se redirige al módulo de
   autenticación y NO se permite el acceso directo por URL.
*/
$rol = $_SESSION['user']['role'] ?? null;
$rolesPermitidos = ['Administrador', 'Médico', 'Cliente', 'Recepcionista']; 
if (!in_array($rol, $rolesPermitidos)) {
    header('Location: ../auth/iniciar_sesion.php?error=' . urlencode('Debes iniciar sesión o registrarte.'));
    exit;
}

// Inclusión de la configuración de conexión a BD.
// NOTA: Aunque esta página no ejecuta queries, la inclusión se mantiene por
// estandarización del framework del sistema y para posibles extensiones futuras.
include('../config/conexion.php');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sobre Nosotros</title>

    <!--
        FAVICON GLOBAL DEL SISTEMA
        --------------------------
        Se utiliza un ícono unificado para todas las vistas de OdontoSmart,
        referenciado desde la ruta absoluta del proyecto.
    -->
    <link rel="icon" href="/odontosmart/assets/img/odonto1.png">

    <!--
        HOJA DE ESTILOS INTERNA
        -----------------------
        Esta vista define sus propios estilos CSS embebidos. En una versión
        productiva podría migrarse a un archivo .css común para mantener
        un mejor desacoplamiento de presentación.
    -->
    <style>
        /* Estilos base de la vista */
        body {
            font-family: 'Roboto', Arial, sans-serif;  /* Tipografía estándar del sistema */
            margin: 0;
            padding: 0;
            background: linear-gradient(to bottom right, #f5f9fc, #e0f7fa);
            color: #333;
        }

        /* Contenedor de la barra de navegación lateral (layout principal) */
        .navbar {
            width: 220px;                       /* Ancho fijo del sidebar */
            background-color: #69B7BF;          /* Color institucional OdontoSmart */
            height: 100vh;                      /* Ocupa toda la altura de la ventana */
            padding-top: 20px;
            position: fixed;                    /* Se mantiene fijo al hacer scroll */
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            transition: width 0.3s ease;        /* Transición suave para ajustes de ancho */
        }

        /* Estilo para los enlaces del menú de navegación */
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
            background-color: #264cbf;          /* Color de realce al pasar el cursor */
            transform: scale(1.05);             /* Pequeño efecto de zoom */
        }

        /* Área principal de contenido. Se desplaza hacia la derecha por el sidebar */
        .content {
            margin-left: 240px;                 /* Desfase respecto al ancho de la navbar */
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

        /* Logo ubicado en la parte inferior de la barra de navegación */
        .logo-navbar {
            position: absolute;
            bottom: 40px;                       /* Separación desde la parte inferior */
            left: 50%;
            transform: translateX(-50%);        /* Centrado horizontal dentro del sidebar */
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
            gap: 20px;                          /* Separación entre tarjetas */
            margin: 30px 0;
            flex-wrap: wrap;                    /* Permite adaptar a pantallas pequeñas */
        }

        /* Componente genérico de tarjeta reutilizable */
        .card {
            background: #fff;
            flex: 1 1 45%;                      /* Ocupa aprox. el 45% del ancho disponible */
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);        /* Levanta la tarjeta al hacer hover */
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        }

        .card h3 {
            color: #69B7BF;                     /* Color corporativo para títulos de tarjetas */
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

    <!--
        BLOQUE: BARRA DE NAVEGACIÓN LATERAL
        -----------------------------------
        Este bloque renderiza el menú principal reutilizable del sistema.
        La estructura del menú se centraliza en ../views/navbar.php para
        garantizar consistencia entre módulos.
    -->
    <div class="navbar">
        <?php 
        /**
         * Inclusión del componente de navegación principal.
         * Este archivo suele contener:
         * - Enlaces a módulos (Dashboard, Pacientes, Citas, Ventas, etc.)
         * - Validación visual del rol/permiso (si se maneja a nivel de vista)
         */
        include('../views/navbar.php'); 
        ?>

        <!--
            LOGO CORPORATIVO
            ----------------
            Elemento decorativo e identificativo de la clínica OdontoSmart
            dentro de la barra lateral.
        -->
        <img src="../assets/img/odonto1.png" class="logo-navbar" alt="Logo OdontoSmart">
    </div>

    <!--
        BLOQUE: CONTENIDO PRINCIPAL
        ---------------------------
        Sección informativa "Sobre Nosotros".
        No realiza operaciones dinámicas con BD; el contenido es estático
        y puede ser modificado directamente por el equipo de desarrollo.
    -->
    <div class="content">
        <div class="seccion">
            <!-- Título principal de la sección -->
            <h1 style="color: #69B7BF;">Sobre Nosotros - Clínica OdontoSmart</h1>

            <!-- Párrafo introductorio institucional -->
            <p>
                Bienvenidos a la Clínica OdontoSmart. Somos un equipo de profesionales 
                dedicados a brindar atención odontológica de calidad, con un enfoque humano 
                y cercano a nuestros pacientes.
            </p>

            <!--
                CONTENEDOR: Misión y Visión
                ---------------------------
                Se utilizan dos tarjetas (.card) para mostrar la información
                institucional clave de la clínica.
            -->
            <div class="mision-vision">
                <!-- Tarjeta: Misión -->
                <div class="card">
                    <h3>Nuestra Misión</h3>
                    <p>
                        Cuidar tu salud bucal y ofrecerte tratamientos modernos, seguros y 
                        accesibles. Creemos en la importancia de la prevención y en 
                        acompañarte en cada etapa de tu cuidado dental.
                    </p>
                </div>

                <!-- Tarjeta: Visión -->
                <div class="card">
                    <h3>Nuestra Visión</h3>
                    <p>
                        Ser la clínica dental de referencia en la comunidad, reconocida por 
                        nuestra excelencia en el servicio y compromiso con la salud bucal de 
                        nuestros pacientes.
                    </p>
                </div>
            </div>

            <!-- Texto explicativo sobre el objetivo del sitio web -->
            <p>
                Este sitio web fue creado con la idea de que puedas tener una mejor experiencia 
                como usuario de nuestros servicios, y que puedas acceder fácilmente a la información 
                y herramientas que necesitas para cuidar tu salud bucal.
            </p>

            <!-- Mensaje de cierre institucional -->
            <p>
                <strong>¡Gracias por preferirnos y estar aquí! Tu salud dental es nuestra prioridad.</strong>
            </p>
        </div>
    </div>

</body>
</html>
