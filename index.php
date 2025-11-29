<?php
// Detecta la página actual
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>OdontoSmart - Bienvenido</title>

<!-- Fuente moderna -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

<style>
/* ----------- BODY Y FONDO ----------- */
body {
    margin: 0;
    padding: 0;
    font-family: 'Poppins', sans-serif;
    color: #fff;
    background: linear-gradient(270deg,  #264CBF, #182940, #69B7BF);
    background-size: 300% 300%;
    animation: rgbFlow 30s ease infinite;
    overflow-x: hidden;
}

/* Animación del degradado */
@keyframes rgbFlow {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* ----------- NAVBAR ----------- */
.navbar {
    padding: 15px 30px;
    background: rgba(105, 183, 191, 0.9);
    display: flex;
    align-items: center;
    gap: 20px;
    position: sticky;
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

.navbar a:hover, .navbar a.active {
    color: #fff;
    background-color: rgba(0,0,0,0.2);
}

/* ----------- HERO ----------- */
.hero {
    max-width: 1000px;
    margin: 60px auto 40px auto;
    padding: 0 20px;
    text-align: center;
}

.hero h1 {
    font-size: 3em;
    color: #f1f4f5ff;
    margin-bottom: 20px;
    text-shadow: 1px 1px 5px rgba(0,0,0,0.3);
}

.hero h3 {
    color: #69B7BF;
    margin-bottom: 30px;
}

.hero p {
    max-width: 800px;
    margin: 10px auto;
    line-height: 1.7;
    font-size: 1.1em;
}

/* ----------- TARJETAS / SECCIONES ----------- */
.card-container {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 20px;
    margin: 40px auto;
}

.card {
    background: rgba(255, 255, 255, 0.1);
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
    color: #69B7BF;
    margin-bottom: 15px;
}

/* ----------- LOGO FIJO ----------- */
.logo-fixed {
    position: fixed;
    left: 40px;
    bottom: 550px;
    width: 300px;
    height: auto;
    z-index: 10;
    opacity: 0.9;
    pointer-events: none;
    transition: transform 0.3s;
}

.logo-fixed:hover {
    transform: scale(1.05);
}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <ul>
        <li><a href="auth/login.php" class="<?= $currentPage == 'login.php' ? 'active' : '' ?>">Ingresar</a></li>
        <li><a href="modulos/usuarios/create_users.php" class="<?= $currentPage == 'create_users.php' ? 'active' : '' ?>">Registrarse</a></li>
    </ul>
</nav>

<!-- HERO -->
<div class="hero">
    <h1>Bienvenido(a) a OdontoSmart</h1>
    <h3>Inicie sesión con su usuario y contraseña</h3>
    <p>En OdontoSmart nos dedicamos a transformar tu salud bucal con tecnología de vanguardia, atención personalizada y un enfoque humano en cada tratamiento.</p>
    <p>Nuestro compromiso es brindarte una experiencia cómoda, segura y transparente, desde tu primera consulta hasta el seguimiento final.</p>
    <p>Aquí encontrarás un equipo de especialistas que combina conocimiento, innovación y calidez para cuidar de tu sonrisa.</p>
    <p>Explora nuestros servicios, agenda tu cita y descubre por qué somos la opción inteligente para tu bienestar dental.</p>
</div>

<!-- Tarjetas informativas -->
<div class="card-container">
    <div class="card">
        <h3>Nuestros Servicios</h3>
        <p>Consulta general, ortodoncia, estética dental, limpiezas y mucho más. Atención profesional con tecnología avanzada.</p>
    </div>
    <div class="card">
        <h3>Agenda tu Cita</h3>
        <p>Reserva tu cita de manera fácil y rápida desde nuestra plataforma y recibe recordatorios personalizados.</p>
    </div>
    <div class="card">
        <h3>Atención Personalizada</h3>
        <p>Cada paciente recibe un plan de tratamiento adaptado a sus necesidades y seguimiento continuo por nuestros especialistas.</p>
    </div>
</div>

<!-- Logo fijo -->
<img src="assets/img/odonto.png" class="logo-fixed" alt="Logo OdontoSmart">

</body>
</html>
