<!-- //CODIGO INDEX.PHP*/ -->

<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php?error=Acceso no autorizado");
    exit;
}

$rol = $_SESSION['user']['role']; // cliente, administrador, medico
$username = $_SESSION['user']['username'];
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>OdontoSmart - Clínica Dental | Perfil: <?php echo ucfirst($rol); ?></title>
    <style>
        /* Menu Vertical*/
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .navbar {
            width: 220px;
            background-color: #152fbf;
            height: 100vh;
            padding-top: 20px;
            position: fixed;
        }
        .navbar a {
            display: block;
            color: #ecf0f1;
            padding: 12px;
            text-decoration: none;
            margin: 5px 0;
            border-radius: 4px;
        }
        .navbar a:hover {
            background-color: #69b7bf;
        }
        .content {
            margin-left: 240px;
            padding: 20px;
        }
    </style>
</head>
<body>
     <div class="navbar">
        <?php include('../views/navbar.php'); ?>
    </div>
    <div class="content">
        <h1>Bienvenido a OdontoSmart</h1>
        <h2>Perfil actual: <?php echo ucfirst($rol); ?></h2>
        
<p>Usuario: <?php echo htmlspecialchars($_SESSION['user']['username']); ?></p>


<!-- Ya el logout se encuentra en el Navbar -->
<!-- <a href="http://localhost/ProyectoOdonto/auth/logout.php" style="text-align:center; display:block;">Cerrar sesión</a> -->
    </div>
</body>
</html>