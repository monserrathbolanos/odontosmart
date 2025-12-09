<?php 

//   index.php
//   ---------
//   Página de inicio pública del sistema OdontoSmart.
 
//   Responsabilidades:
//   - Detectar la página actual para resaltar el enlace activo en la barra de navegación.
//   - Presentar una landing page informativa para usuarios no autenticados.
//   - Proveer accesos directos a:
//        - Iniciar sesión (auth/iniciar_sesion.php)
//        - Registrar usuario (modulos/usuarios/crear_usuarios.php)
 
//   Esta vista NO requiere sesión ni conexión a base de datos.
 

 // Obtiene el nombre del script actual (archivo PHP ejecutado).
 // Se utiliza para asignar la clase "active" al enlace correspondiente en la navbar.
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>OdontoSmart - Bienvenido</title>

    <!--
        FAVICON UNIFICADO
        -----------------
        Ícono global de la aplicación OdontoSmart.
        Ruta absoluta dentro del proyecto.
    -->
    <link rel="icon" href="/odontosmart/assets/img/odonto1.png">

    <!--
        Fuente tipográfica principal para esta landing page.
        Poppins se utiliza aquí para darle un estilo moderno al inicio.
    -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

    <!--
        HOJA DE ESTILOS INTERNA
        -----------------------
        Estilos específicos de la página de inicio (landing).
        En un entorno escalable, se recomienda migrar a un CSS separado.
    -->
    <style>
        /* ----------- BODY Y FONDO ----------- */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            color: #fff;
            /* Degradado animado tipo "hero" */
            background: linear-gradient(100deg, #69B7BF, #139ba5ff);
            background-size: 300% 300%;
            animation: rgbFlow 30s ease infinite;
            overflow-x: hidden; /* Evita scroll horizontal por efectos visuales */
        }

        /* Animación del degradado de fondo */
        /* @keyframes rgbFlow {
            0%   { background-position: 0% 50%; }
            50%  { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        } */

        /* ----------- NAVBAR ----------- */
        .navbar {
            padding: 15px 30px;
            background: rgba(255, 255, 255, 0.9); /*Fondo semi-transparente sobre el degradado*/
            display: flex;
            align-items: center;
            gap: 20px;
            position: sticky;       /* Fija la barra en la parte superior al hacer scroll */
            top: 0;
            z-index: 100;
            border-bottom: 2px solid #D5E7F2;
            border-radius: 0 0 12px 12px;
        }

        .navbar ul {
            list-style: none;
            display: flex;
            gap: 20px;
            margin: 0;
            padding: 0;
        }

        .navbar a {
            color: #264CBF;
            text-decoration: none;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        /* Estado hover y estado activo de los enlaces de navegación */
        .navbar a:hover,
        .navbar a.active {
            color: #fff;
            background-color: rgba(0,0,0,0.2);
        }

        /* ----------- HERO (sección principal) ----------- */
        .hero {
            max-width: 1000px;
            margin: 60px auto 40px auto;
            padding: 0 20px;
            text-align: center;
        }

        .hero h1 {
            font-size: 3em;
            color: #f1f4f5ff; /* Blanco ligeramente grisáceo para mejor contraste */
            margin-bottom: 20px;
            text-shadow: 1px 1px 5px rgba(0,0,0,0.3);
        }

        .hero h3 {
            color: #0b2f33ff;
            margin-bottom: 30px;
        }

        .hero p {
            max-width: 800px;
            margin: 10px auto;
            line-height: 1.7;
            font-size: 1.1em;
        }

        /* ----------- TARJETAS / SECCIONES INFORMATIVAS ----------- */
        .card-container {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;  /* Permite acomodar las tarjetas en varias líneas */
            gap: 20px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .card {
            background: rgba(255, 255, 255, 0.1);  /* Efecto "glassmorphism" */
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 25px;
            flex: 1 1 300px;
            max-width: 400px;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        }

        .card h3 {
            color: #264CBF;
            margin-bottom: 15px;
        }

        /* ----------- LOGO FIJO ----------- */
        .logo-fixed {
            position: fixed;
            left: 40px;
            bottom: 550px;   /* Ajuste vertical del logo (depende del diseño de la landing) */
            width: 300px;
            height: auto;
            z-index: 10;
            opacity: 0.9;
            pointer-events: none; /* Evita que el logo interfiera con clics sobre la página */
            transition: transform 0.3s;
        }

        .logo-fixed:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body>

    <!--
        BARRA DE NAVEGACIÓN PÚBLICA
        ---------------------------
        Ofrece acceso directo a:
        - Iniciar sesión (para usuarios ya registrados).
        - Registrarse (para nuevos usuarios).
        Usamos $currentPage para marcar el enlace activo según la URL.
    -->
    <nav class="navbar">
        <ul>
            <li>
                <a href="auth/iniciar_sesion.php"
                   class="<?= $currentPage == 'iniciar_sesion.php' ? 'active' : '' ?>">
                    Ingresar
                </a>
            </li>
            <li>
                <a href="modulos/usuarios/crear_usuarios.php"
                   class="<?= $currentPage == 'crear_usuarios.php' ? 'active' : '' ?>">
                    Registrarse
                </a>
            </li>
        </ul>
    </nav>

    <!--
        SECCIÓN HERO
        ------------
        Mensaje principal de bienvenida para usuarios no autenticados.
        Funciona como presentación comercial/institucional del sistema.
    -->
    <div class="hero">
        <h1>Bienvenido(a) a OdontoSmart</h1>
        <h3>Inicie sesión con su usuario y contraseña</h3>
        <p>
            En OdontoSmart nos dedicamos a transformar tu salud bucal con tecnología
            de vanguardia, atención personalizada y un enfoque humano en cada tratamiento.
        </p>
        <p>
            Nuestro compromiso es brindarte una experiencia cómoda, segura y transparente,
            desde tu primera consulta hasta el seguimiento final.
        </p>
        <p>
            Aquí encontrarás un equipo de especialistas que combina conocimiento,
            innovación y calidez para cuidar de tu sonrisa.
        </p>
        <p>
            Explora nuestros servicios, agenda tu cita y descubre por qué somos
            la opción inteligente para tu bienestar dental.
        </p>
    </div>

    <!--
        TARJETAS INFORMATIVAS
       
        Sección que resume servicios, funcionalidades y enfoque de atención.
        Estas tarjetas son estáticas y sirven como contenido de marketing y orientación.
    -->
    <div class="card-container">
        <div class="card">
            <h3>Nuestros Servicios</h3>
            <p>
                Consulta general, ortodoncia, estética dental, limpiezas y mucho más.
                Atención profesional con tecnología avanzada.
            </p>
        </div>
        <div class="card">
            <h3>Agenda tu Cita</h3>
            <p>
                Reserva tu cita de manera fácil y rápida desde nuestra plataforma
                y recibe recordatorios personalizados.
            </p>
        </div>
        <div class="card">
            <h3>Atención Personalizada</h3>
            <p>
                Cada paciente recibe un plan de tratamiento adaptado a sus necesidades
                y seguimiento continuo por nuestros especialistas.
            </p>
        </div>
    </div>

    <!--
        LOGO FIJO
       
        Elemento decorativo que refuerza la identidad visual de OdontoSmart.
        No afecta la interacción, ya que tiene pointer-events: none.
    -->
    <img src="assets/img/odonto.png" class="logo-fixed" alt="Logo OdontoSmart">

</body>
</html>
