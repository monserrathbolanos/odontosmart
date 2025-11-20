<?php
// info_clinica.php
session_start();   
include('../config/conexion.php');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>OdontoSmart - Sobre Nosotros</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 0; 
            background: #f5f5f5;
        }
        .navbar { 
            width: 220px; 
            background-color: #69B7BF; 
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
        .seccion {
            background: white;
            padding: 30px;
            margin: 15px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            line-height: 1.6;
        }
        .mision-vision {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }
        .card {
            background: #D5E7F2;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #69B7BF;
        }
        .logo-navbar {
            position: absolute;
            bottom: 80px;       /* distancia desde abajo (ajustable) */
            left: 50%;          
            transform: translateX(-50%);
             width: 140px;
            opacity: 0.9;
        }

    </style>
</head>
<body>
    <div class="navbar">
    <?php include('../views/navbar.php'); ?>

    <img src="../assets/img/odonto1.png" class="logo-navbar" alt="Logo OdontoSmart">
</div>


    <div class="content">
        <div class="seccion">
            <h1 style="color: #69B7BF;"> Sobre Nosotros - Clínica OdontoSmart</h1>
            <p>Bienvenidos a la Clínica OdontoSmart. Somos un equipo de profesionales dedicados a brindar atención odontológica de calidad, con un enfoque humano y cercano a nuestros pacientes.</p>
            
            <div class="mision-vision">
                <div class="card">
                    <h3> Nuestra Misión</h3>
                    <p>Cuidar tu salud bucal y ofrecerte tratamientos modernos, seguros y accesibles. Creemos en la importancia de la prevención y en acompañarte en cada etapa de tu cuidado dental.</p>
                </div>
                <div class="card">
                    <h3> Nuestra Visión</h3>
                    <p>Ser la clínica dental de referencia en la comunidad, reconocida por nuestra excelencia en el servicio y compromiso con la salud bucal de nuestros pacientes.</p>
                </div>
            </div>

            <p>Este sitio web fue creado con la idea de que puedas tener una mejor experiencia como usuario de nuestros servicios, y que puedas acceder fácilmente a la información y herramientas que necesitas para cuidar tu salud bucal.</p>
            
            <p><strong>¡Gracias por preferirnos y estar aquí! Tu salud dental es nuestra prioridad.</strong></p>
        </div>
    </div>
    
</body>
</html>