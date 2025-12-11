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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OdontoSmart - Bienvenido</title>

    <!--
        FAVICON UNIFICADO
        -----------------
        Ícono global de la aplicación OdontoSmart.
        Ruta absoluta dentro del proyecto.
    -->
    <link rel="icon" href="/odontosmart/assets/img/odonto1.png">

    <!--
        Bootstrap 5 CSS
        ----------------
        Framework CSS para diseño responsivo profesional.
    -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!--
        Fuente tipográfica principal para esta landing page.
        Poppins se utiliza aquí para darle un estilo moderno al inicio.
    -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!--
        HOJA DE ESTILOS PERSONALIZADA
        -----------------------
        Estilos personalizados que complementan Bootstrap.
    -->
    <style>
        :root {
            --primary-color: #139BA5;
            --secondary-color: #69B7BF;
            --accent-color: #264CBF;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #69B7BF 0%, #139BA5 100%);
            background-attachment: fixed;
            min-height: 100vh;
        }

        /* ----------- NAVBAR PERSONALIZADA ----------- */
        .navbar {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            color: var(--accent-color) !important;
        }

        .nav-link {
            font-weight: 600;
            font-size: 0.95rem;
            color: #1a1a1a !important;
            transition: all 0.3s ease;
            position: relative;
            margin-left: 10px;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 50%;
            width: 0;
            height: 2px;
            background-color: var(--accent-color);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }

        .nav-link:hover::after,
        .nav-link.active::after {
            width: 100%;
        }

        /* ----------- HERO SECTION ----------- */
        .hero-section {
            padding: 80px 20px 60px 20px;
            color: white;
        }

        .hero-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .hero-text {
            text-align: left;
        }

        .hero-text h1 {
            font-size: clamp(2.5rem, 5vw, 3.5rem);
            font-weight: 700;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
            margin-bottom: 20px;
        }

        .hero-text .subtitle {
            font-size: 1.3rem;
            font-weight: 500;
            margin-bottom: 30px;
            opacity: 0.95;
        }

        .hero-text p {
            font-size: 1.05rem;
            line-height: 1.8;
            margin: 12px 0;
            opacity: 0.85;
            font-weight: 400;
        }

        .hero-carousel {
            text-align: center;
        }

        /* ----------- CARDS SECTION ----------- */
        .cards-section {
            padding: 60px 20px;
        }

        .service-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            border-radius: 16px;
            padding: 32px 28px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            color: white;
            height: 100%;
            position: relative;
        }

        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            border-radius: 16px 16px 0 0;
        }

        .service-card:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.25) !important;
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .service-card h5 {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 16px;
            letter-spacing: -0.3px;
        }

        .service-card p {
            font-size: 1rem;
            line-height: 1.7;
            margin-bottom: 0;
            opacity: 0.85;
        }

        /* ----------- CAROUSEL SECTION ----------- */
        .carousel-section {
            padding: 60px 20px;
        }

        .carousel-container {
            max-width: 900px;
            margin: 0 auto;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .carousel-inner {
            border-radius: 16px;
        }

        .carousel-item img {
            height: 400px;
            object-fit: cover;
            width: 100%;
        }

        .carousel-caption {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent);
            padding: 40px 20px 20px 20px;
            color: white;
            text-align: center;
        }

        .carousel-caption h6 {
            font-size: 1.3rem;
            font-weight: 600;
            margin: 0;
            letter-spacing: -0.3px;
        }

        .carousel-caption p {
            font-size: 0.95rem;
            margin: 8px 0 0 0;
            opacity: 0.9;
        }

        .carousel-control-prev,
        .carousel-control-next {
            width: 50px;
            height: 50px;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .carousel-control-prev:hover,
        .carousel-control-next:hover {
            background: rgba(0, 0, 0, 0.6);
        }

        .carousel-indicators {
            bottom: 15px;
        }

        .carousel-indicators button {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.5);
            transition: all 0.3s ease;
        }

        .carousel-indicators button.active {
            background-color: white;
        }

        /* ----------- NAVBAR LOGO ----------- */
        .navbar-logo {
            height: 40px;
            width: auto;
            transition: all 0.3s ease;
        }

        .navbar-logo:hover {
            transform: scale(1.05);
        }

        /* ----------- RESPONSIVE DESIGN ----------- */
        @media (max-width: 768px) {
            .hero-content {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .hero-text {
                text-align: center;
            }

            .hero-text h1 {
                font-size: 2rem;
            }

            .hero-text .subtitle {
                font-size: 1.1rem;
            }

            .carousel-container {
                max-width: 100%;
            }

            .carousel-item img {
                height: 300px;
            }

            .cards-section {
                padding: 40px 20px;
            }
        }

        @media (max-width: 480px) {
            .hero-section {
                padding: 50px 16px 30px 16px;
            }

            .hero-content {
                gap: 20px;
            }

            .hero-text h1 {
                font-size: 1.75rem;
            }

            .hero-text p {
                font-size: 0.95rem;
            }

            .carousel-item img {
                height: 250px;
            }

            .carousel-caption h6 {
                font-size: 1.1rem;
            }

            .carousel-caption p {
                font-size: 0.85rem;
            }

            .service-card h5 {
                font-size: 1.2rem;
            }
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
    <nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="assets/img/odonto.png" alt="Logo OdontoSmart" class="navbar-logo me-2">
                <span>OdontoSmart</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage == 'iniciar_sesion.php' ? 'active' : '' ?>" href="auth/iniciar_sesion.php">
                            Ingresar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage == 'crear_usuarios.php' ? 'active' : '' ?>" href="modulos/usuarios/crear_usuarios.php">
                            Registrarse
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!--
        SECCIÓN HERO CON CARRUSEL
        -------------------------
        Mensaje principal de bienvenida con galería de servicios lado a lado.
        Presenta texto informativo y visualización profesional del trabajo realizado.
    -->
    <section class="hero-section">
        <div class="hero-content">
            <div class="hero-text">
                <h1>Bienvenido(a) a OdontoSmart</h1>
                <p class="subtitle">Inicie sesión con su usuario y contraseña</p>
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
            
            <div class="hero-carousel">
                <div class="carousel-container">
                    <div id="serviceCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="6000">
                        <div class="carousel-indicators">
                            <button type="button" data-bs-target="#serviceCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                            <button type="button" data-bs-target="#serviceCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
                            <button type="button" data-bs-target="#serviceCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
                            <button type="button" data-bs-target="#serviceCarousel" data-bs-slide-to="3" aria-label="Slide 4"></button>
                            <button type="button" data-bs-target="#serviceCarousel" data-bs-slide-to="4" aria-label="Slide 5"></button>
                            <button type="button" data-bs-target="#serviceCarousel" data-bs-slide-to="5" aria-label="Slide 6"></button>
                        </div>
                        <div class="carousel-inner">
                            <div class="carousel-item active">
                                <img src="assets/img/odonto2.png" class="d-block w-100" alt="Servicio Dental 1">
                                <div class="carousel-caption">
                                    <h6>Consulta General</h6>
                                    <p>Evaluación completa de tu salud bucal</p>
                                </div>
                            </div>
                            <div class="carousel-item">
                                <img src="assets/img/odonto3.png" class="d-block w-100" alt="Servicio Dental 2">
                                <div class="carousel-caption">
                                    <h6>Ortodoncia</h6>
                                    <p>Alineación perfecta de tus dientes</p>
                                </div>
                            </div>
                            <div class="carousel-item">
                                <img src="assets/img/odonto4.png" class="d-block w-100" alt="Servicio Dental 3">
                                <div class="carousel-caption">
                                    <h6>Estética Dental</h6>
                                    <p>Mejora el aspecto de tu sonrisa</p>
                                </div>
                            </div>
                            <div class="carousel-item">
                                <img src="assets/img/odonto5.png" class="d-block w-100" alt="Servicio Dental 4">
                                <div class="carousel-caption">
                                    <h6>Limpieza Profesional</h6>
                                    <p>Higiene bucal profunda y efectiva</p>
                                </div>
                            </div>
                            <div class="carousel-item">
                                <img src="assets/img/odonto6.png" class="d-block w-100" alt="Servicio Dental 5">
                                <div class="carousel-caption">
                                    <h6>Tratamiento de Encías</h6>
                                    <p>Cuidado especializado de tu salud gingival</p>
                                </div>
                            </div>
                            <div class="carousel-item">
                                <img src="assets/img/odonto7.png" class="d-block w-100" alt="Servicio Dental 6">
                                <div class="carousel-caption">
                                    <h6>Endodoncia</h6>
                                    <p>Tratamiento de conducto especializado</p>
                                </div>
                            </div>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#serviceCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#serviceCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Next</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
        
        <!-- Sección que resume servicios, funcionalidades y enfoque de atención.
        Estas tarjetas son estáticas y sirven como contenido de marketing y orientación. -->
    
    <section class="cards-section">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="card service-card border-0">
                        <div class="card-body">
                            <h5 class="card-title">Nuestros Servicios</h5>
                            <p class="card-text">
                                Consulta general, ortodoncia, estética dental, limpiezas y mucho más.
                                Atención profesional con tecnología avanzada.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="card service-card border-0">
                        <div class="card-body">
                            <h5 class="card-title">Agenda tu Cita</h5>
                            <p class="card-text">
                                Reserva tu cita de manera fácil y rápida desde nuestra plataforma
                                y recibe recordatorios personalizados.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="card service-card border-0">
                        <div class="card-body">
                            <h5 class="card-title">Atención Personalizada</h5>
                            <p class="card-text">
                                Cada paciente recibe un plan de tratamiento adaptado a sus necesidades
                                y seguimiento continuo por nuestros especialistas.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>



    <!--
        Bootstrap 5 JavaScript
        ----------------------
        Necesario para funcionalidad interactiva (navbar toggle en móviles, etc.)
    -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Configurar el carrusel para cambiar cada 6 segundos
        document.addEventListener('DOMContentLoaded', function() {
            const carousel = document.getElementById('serviceCarousel');
            if (carousel) {
                const bootstrapCarousel = new bootstrap.Carousel(carousel, {
                    interval: 6000,
                    wrap: true
                });
            }
        });
    </script>

</body>
</html>
