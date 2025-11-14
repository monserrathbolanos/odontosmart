
<?php
// navbar.php
// Iniciar sesión para obtener el rol
session_start();
$rol = $_SESSION['rol'] ?? 'cliente'; // Por defecto cliente si no hay sesión
?>

<div class="navbar">
    <?php
    if ($rol == "cliente") {
        echo '<a href="info_clinica.php">Sobre Nosotros</a>';
        echo '<a href="servicios.php">Servicios</a>';
        echo '<a href="pagar.php">Ir a pagar</a>';
    } elseif ($rol == "administrador") {
        echo '<a href="info_clinica.php">Sobre Nosotros</a>';
        echo '<a href="total_inventario.php">Inventario</a>';
        echo '<a href="inventario.php">Control de inventario</a>';
        echo '<a href="gestion_usuarios.php">Gestión de usuarios</a>';
        echo '<a href="servicios.php">Ventas</a>';
        echo '<a href="historial_ventas.php">Historial Ventas</a>';
    } elseif ($rol == "medico") {
        echo '<a href="info_clinica.php">Sobre Nosotros</a>';
        echo '<a href="gestion_citas.php">Gestión de Citas</a>';
    }
    ?>
    
    <!-- Cerrar sesión -->
    <a href="logout.php" style="margin-top: 50px; background: #dc3545;">Cerrar Sesión</a>
</div>