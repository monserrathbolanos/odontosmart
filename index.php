<?php
// index.php
// Página de inicio pública del sistema OdontoSmart.
// No requiere sesión ni conexión a base de datos.

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OdontoSmart - Bienvenido</title>

    <!-- Favicon -->
    <link rel="icon" href="assets/img/odonto1.png">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Fuente principal -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Estilos propios -->
    <link rel="stylesheet" href="assets/css/index.css">
</head>
<body>

    <!-- Barra de navegación -->
    <nav class="navbar sticky-top">
        <div class="container-fluid px-4">

            <!-- Logo y nombre del sistema -->
            <a class="navbar-brand" href="index.php">
                <img src="assets/img/odonto.png" alt="OdontoSmart" class="navbar-logo">
                <span>OdontoSmart</span>
            </a>

            <!-- Enlaces principales -->
            <ul class="navbar-nav flex-row gap-2">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'iniciar_sesion.php' ? 'active' : '' ?>"
                       href="auth/iniciar_sesion.php">
                        Ingresar
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link"
                       href="modulos/usuarios/crear_usuarios.php">
                        Registrarse
                    </a>
                </li>
            </ul>

        </div>
    </nav>

    <!-- Sección principal -->
    <section class="hero-section">
        <div class="hero-content">

            <!-- Texto principal -->
            <div class="hero-text">
                <h1>Bienvenido(a) a OdontoSmart</h1>
                <p class="subtitle">Inicie sesión con su usuario y contraseña</p>

                <p>
                    En OdontoSmart nos enfocamos en brindar atención dental de calidad,
                    apoyándonos en tecnología moderna y un trato cercano al paciente.
                </p>

                <p>
                    Nuestro objetivo es facilitar la gestión de citas, tratamientos
                    y seguimiento clínico en un solo lugar.
                </p>

                <p>
                    Contamos con un equipo profesional comprometido con tu bienestar
                    y la salud de tu sonrisa.
                </p>
            </div>

            <!-- Carrusel de servicios -->
            <div class="hero-carousel">
                <div class="carousel-container">
                    <div id="serviceCarousel"
                         class="carousel slide"
                         data-bs-ride="carousel"
                         data-bs-interval="6000">

                        <!-- Indicadores -->
                        <div class="carousel-indicators">
                            <button type="button" data-bs-target="#serviceCarousel" data-bs-slide-to="0" class="active"></button>
                            <button type="button" data-bs-target="#serviceCarousel" data-bs-slide-to="1"></button>
                            <button type="button" data-bs-target="#serviceCarousel" data-bs-slide-to="2"></button>
                            <button type="button" data-bs-target="#serviceCarousel" data-bs-slide-to="3"></button>
                            <button type="button" data-bs-target="#serviceCarousel" data-bs-slide-to="4"></button>
                            <button type="button" data-bs-target="#serviceCarousel" data-bs-slide-to="5"></button>
                        </div>

                        <!-- Imágenes -->
                        <div class="carousel-inner">
                            <div class="carousel-item active">
                                <img src="assets/img/odonto2.png" class="d-block w-100" alt="Consulta general">
                                <div class="carousel-caption">
                                    <h6>Consulta General</h6>
                                    <p>Evaluación completa de tu salud bucal</p>
                                </div>
                            </div>

                            <div class="carousel-item">
                                <img src="assets/img/odonto3.png" class="d-block w-100" alt="Ortodoncia">
                                <div class="carousel-caption">
                                    <h6>Ortodoncia</h6>
                                    <p>Alineación dental personalizada</p>
                                </div>
                            </div>

                            <div class="carousel-item">
                                <img src="assets/img/odonto4.png" class="d-block w-100" alt="Estética dental">
                                <div class="carousel-caption">
                                    <h6>Estética Dental</h6>
                                    <p>Mejora la apariencia de tu sonrisa</p>
                                </div>
                            </div>

                            <div class="carousel-item">
                                <img src="assets/img/odonto5.png" class="d-block w-100" alt="Limpieza">
                                <div class="carousel-caption">
                                    <h6>Limpieza Profesional</h6>
                                    <p>Higiene profunda y preventiva</p>
                                </div>
                            </div>

                            <div class="carousel-item">
                                <img src="assets/img/odonto6.png" class="d-block w-100" alt="Encías">
                                <div class="carousel-caption">
                                    <h6>Salud de Encías</h6>
                                    <p>Cuidado especializado gingival</p>
                                </div>
                            </div>

                            <div class="carousel-item">
                                <img src="assets/img/odonto7.png" class="d-block w-100" alt="Endodoncia">
                                <div class="carousel-caption">
                                    <h6>Endodoncia</h6>
                                    <p>Tratamiento de conducto seguro</p>
                                </div>
                            </div>
                        </div>

                        <!-- Controles -->
                        <button class="carousel-control-prev" type="button"
                                data-bs-target="#serviceCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon"></span>
                        </button>

                        <button class="carousel-control-next" type="button"
                                data-bs-target="#serviceCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon"></span>
                        </button>

                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- Sección de tarjetas -->
    <section class="cards-section">
        <div class="container">
            <div class="row g-4">

                <div class="col-lg-4 col-md-6">
                    <div class="card service-card border-0">
                        <h5>Nuestros Servicios</h5>
                        <p>
                            Consulta general, ortodoncia, estética dental y otros
                            tratamientos enfocados en tu salud bucal.
                        </p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="card service-card border-0">
                        <h5>Agenda tu Cita</h5>
                        <p>
                            Reserva tu cita de manera sencilla desde la plataforma
                            y mantén un mejor control de tu atención.
                        </p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="card service-card border-0">
                        <h5>Atención Personalizada</h5>
                        <p>
                            Cada paciente recibe un plan de tratamiento adaptado
                            a sus necesidades específicas.
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
