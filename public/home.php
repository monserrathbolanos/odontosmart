<!-- //CODIGO INDEX.PHP*/ -->

<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php?error=Acceso no autorizado");
    exit;
}

$rol = $_SESSION['user']['role']; // cliente, administrador, medico
$username = $_SESSION['user']['nombre_completo'];
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>OdontoSmart - Clínica Dental | Perfil: <?php echo ucfirst($rol); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>

        
        /* Menu Vertical*/
        body {
             font-family: 'Roboto', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(to bottom right, #f5f9fc, #e0f7fa);
            color: #333;
             }
        .navbar {
            width: 220px;
            background-color: #69B7BF;
            height: 100vh;
            padding-top: 20px;
            position: fixed;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            transition: width 0.3s ease;
          }
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
             background-color: #264cbf;
             transform: scale(1.05);
        }
        .content {
             margin-left: 240px;
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
                    .logo-navbar {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            width: 140px;
            opacity: 0.9;
            transition: transform 0.3s;
        }

        .logo-navbar:hover {
            transform: translateX(-50%) scale(1.1);        
        }
        .mision-vision {
            display: flex;
            gap: 20px;
            margin: 30px 0;
            flex-wrap: wrap;
         }

         .card {
         background: #fff;
         flex: 1 1 45%;
          padding: 20px;
          border-radius: 12px;
          box-shadow: 0 4px 8px rgba(0,0,0,0.1);
          transition: transform 0.3s, box-shadow 0.3s;
         }

         .card:hover {
             transform: translateY(-5px);
             box-shadow: 0 8px 16px rgba(0,0,0,0.2);
         }

         .card h3 {
             color: #69B7BF;
             margin-bottom: 15px;
         }
    </style>
</head>
<body>
     <div class="navbar">
    <?php include('../views/navbar.php'); ?>

    <img src="../assets/img/odonto1.png" class="logo-navbar" alt="Logo OdontoSmart">
</div>
    <div class="content">
        <h1 style="color: #69B7BF;">OdontoSmart - Clínica Dental</h1>
        <h2 style="color:#264CBF;">Bienvenido (a): <?php echo htmlspecialchars($_SESSION['user']['nombre_completo']); ?></h2>
        <h2>Perfil actual: <?php echo ucfirst($rol); ?></h2>
        

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

<!-- Ya el logout se encuentra en el Navbar -->
<!-- <a href="http://localhost/ProyectoOdonto/auth/logout.php" style="text-align:center; display:block;">Cerrar sesión</a> -->
    </div>
</body>
</html>