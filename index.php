<?php
// Detecta la página actual
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<nav class="navbar">
    <ul>
       
        <li>
            <a href="http://localhost/odontosmart/auth/login.php" class="<?= $currentPage == '/auth/login.php' ? 'active' : '' ?>">Ingresar</a>
        </li>
        <li>
            <a href="http://localhost/odontosmart/modulos/usuarios/create_users.php" class="<?= $currentPage == '/modulos/usuarios/create_users.php' ? 'active' : '' ?>">Registrarse</a>
        </li>
        
    </ul>
</nav>

<style>
 

 body {
  background: linear-gradient(270deg, #152FBF, #264CBF, #182940, #92c4e4ff, #69B7BF); /* colores seleccionados */
   /* background-image: url(' Odonto.png');  */
  background-size: 300% 300%;
  animation: rgbFlow 200s ease infinite;
  font-family: 'Poppins', sans-serif;
  color: #ffffff;


}

.alineado-izquierda {
    text-align: left;
  }


.logo-fixed {
  position: fixed;
  left: 40px;
  bottom: 100px;
  width: 400px; /* tamaño pequeño */
  height: auto;
  z-index: 1000;
  opacity: 0.9;
  pointer-events: none; /* evita que interfiera con clics */
}


/* Animación suave del degradado */
@keyframes rgbFlow {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}
    .navbar {
        padding: 10px;
        background: #69B7BF;
        border-bottom: 2px solid #D5E7F2;
        font-family: Arial, sans-serif;
    }

    .navbar ul {
        list-style: none;
        display: flex;
        align-items: center;
        gap: 20px;
        margin: 0;
        padding: 0;
    }

    .navbar li.logout {
        margin-left: auto; /* Empuja el botón de cerrar sesión a la derecha */
    }

    .navbar a {
        color: black;
        text-decoration: none;
        font-weight: bold;
        transition: 0.3s;
    }

    .navbar a:hover {
        color: #D5E7F2;
    }

    .navbar a.active {
        color: 00bcd4#;
        border-bottom: 2px solid #D5E7F2;
    }
    
</style>

<h1 style="color: #69B7BF" >Bienvenido (a) a OdontoSmart</h1>
<h3 style="color: #69B7BF2" >Inicie sesion con su usuario y contraseña.</h3>

<p class="alineado-izquierda">
En OdontoSmart nos dedicamos a transformar tu salud bucal con tecnología de vanguardia, atención personalizada y un enfoque humano en cada tratamiento.</p>

<p class="alineado-izquierda">Nuestro compromiso es brindarte una experiencia cómoda, segura y transparente, desde tu primera consulta hasta el seguimiento final.</p>

<p class="alineado-izquierda">Aquí encontrarás un equipo de especialistas que combina conocimiento, innovación y calidez para cuidar de tu sonrisa.</p>
<p class="alineado-izquierda"> Explora nuestros servicios, agenda tu cita y descubre por qué somos la opción inteligente para tu bienestar dental.</p>

<img src="assets/img/odonto.png" class="logo-fixed" alt="Logo OdontoSmart">
