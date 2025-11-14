<?php

 // $rol = "administrador"; //Puede variar segun el usuario logueado, debe de poder cambiar segun el rol del usuario que inicie sesion //cliente, administrador, medico
 $rol = "cliente";
// $rol = "medico";

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
            background-color: #264cbf;
        }
        .content {
            margin-left: 240px;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <?php
        //Menu dinamico según rol
        if ($rol == "cliente") {
            echo '<a href="info_clinica.php" style="text-align:center; display:block;">Sobre Nosotros - Clínica Dental</a>';
            echo '<a href="servicios.php" style="text-align:center; display:block;">Servicios</a>';
            echo '<a href="pagar.php" style="text-align:center; display:block;">Ir a pagar</a>';
        } elseif ($rol == "administrador") {
            echo '<a href="info_clinica.php" style="text-align:center; display:block;">Sobre Nosotros - Clínica Dental</a>';
            echo '<a href="total_inventario.php" style="text-align:center; display:block;">Inventario</a>';
            echo '<a href="inventario.php" style="text-align:center; display:block;">Control de inventario</a>';
            echo '<a href="gestion_usuarios.php" style="text-align:center; display:block;">Gestión de usuarios</a>';
        } elseif ($rol == "medico") {
            echo '<a href="info_clinica.php" style="text-align:center; display:block;">Sobre Nosotros - Clínica Dental</a>';
        } else {
            echo '<a href="#" style="text-align:center; display:block;">Sobre Nosotros - Clínica Dental</a>';
        
        }
        ?>
    </div>

    <div class="content">
        <h1>Bienvenido a OdontoSmart</h1>
        <h2>Perfil actual: <?php echo ucfirst($rol); ?></h2>
    </div>
</body>
</html>
