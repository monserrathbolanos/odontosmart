<?php
// navbar.php

// Obtener el rol de la sesión 
$rol = $_SESSION['user']['role'] ?? 'cliente'; 
?>

<div class="navbar">
    <?php
    if ($rol == "cliente") {
        echo '<a href="/ProyectoOdonto/public/info_clinica.php"> Sobre Nosotros</a>';
        echo '<a href="/ProyectoOdonto/modulos/ventas/servicios.php"> Servicios</a>';
        echo '<a href="/ProyectoOdonto/modulos/ventas/pagar.php"> Ir a pagar</a>';
    } elseif ($rol == "administrador") {
        echo '<a href="/ProyectoOdonto/public/info_clinica.php"> Sobre Nosotros</a>';
        echo '<a href="/ProyectoOdonto/modulos/inventario/total_inventario.php"> Inventario</a>';
        echo '<a href="/ProyectoOdonto/modulos/inventario/inventario.php"> Control de inventario</a>';
        echo '<a href="/ProyectoOdonto/modulos/usuarios/gestion_usuarios.php">Gestión de usuarios</a>';
        echo '<a href="/ProyectoOdonto/modulos/ventas/servicios.php"> Servicios</a>';
        echo '<a href="/ProyectoOdonto/modulos/ventas/historial_ventas.php"> Historial Ventas</a>';
        
    } elseif ($rol == "medico") {
        echo '<a href="/ProyectoOdonto/public/info_clinica.php"> Sobre Nosotros</a>';
        echo '<a href="gestion_citas.php"> Gestión de Citas</a>';  //no esta el fichero
        echo '<a href="/ProyectoOdonto/modulos/ventas/servicios.php"> Servicios</a>';
    } else {
        // Por defecto mostrar menú de cliente
        echo '<a href="/public/info_clinica.php"> Sobre Nosotros</a>';
        echo '<a href="/modulos/ventas/servicios.php"> Servicios</a>';
        echo '<a href="/modulos/ventas/pagar.php"> Ir a pagar</a>';
    }
    ?>
    
    <!-- Cerrar sesión -->
    <a href="/ProyectoOdonto/auth/logout.php" style="margin-top: 50px; background: #dc3545;"> Cerrar Sesión</a>
</div>