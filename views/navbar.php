<?php
// navbar.php

// Asegurar que la sesión esté iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Datos del usuario logueado
$rol      = $_SESSION['user']['role']     ?? null;      // 'Cliente', 'Administrador', 'Medico', 'Recepcionista', etc.
$permisos = $_SESSION['user']['permisos'] ?? [];        // array de strings, ej: ['ver_servicios', 'ir_a_pagar']

// Función helper para revisar permisos
function tienePermiso(string $permiso, array $permisos): bool {
    return in_array($permiso, $permisos);
}
?>
<!-- Navbar ahora responde a los permisos de usuario segun el rol asignado -->
<div class="navbar">
    <?php

    echo '<a href="/odontosmart/public/home.php"> Inicio</a>';
    
    if (tienePermiso('ver_info_clinica', $permisos)) {
        echo '<a href="/odontosmart/public/info_clinica.php"> Sobre Nosotros</a>';
    }

    if (tienePermiso('ver_servicios', $permisos)) {
        echo '<a href="/odontosmart/modulos/ventas/servicios.php"> Servicios</a>';
    }

    if (tienePermiso('ir_a_pagar', $permisos)) {
        echo '<a href="/odontosmart/modulos/ventas/carrito.php"> Ir a pagar</a>';
    }

    if (tienePermiso('ver_inventario', $permisos)) {
        echo '<a href="/odontosmart/modulos/inventario/total_inventario.php"> Inventario</a>';
    }

    if (tienePermiso('control_inventario', $permisos)) {
        echo '<a href="/odontosmart/modulos/inventario/inventario.php"> Control de inventario</a>';
    }

    if (tienePermiso('gestion_usuarios', $permisos)) {
        echo '<a href="/odontosmart/modulos/usuarios/gestion_usuarios.php"> Gestión de usuarios</a>';
    }

    if (tienePermiso('ver_historial_ventas', $permisos)) {
        echo '<a href="/odontosmart/modulos/ventas/historial_ventas.php"> Historial Ventas</a>';
    }

    if (tienePermiso('gestion_citas', $permisos)) {
        //echo '<a href="/odontosmart/modulos/citas/gestion_citas.php"> Gestión de Citas</a>';
    }

    //Menu por defecto donde si el usuario no tiene permisos asignados entonces se le muestra un menu con lo minimo que es informacion clinica, servicios y pagar. 
    if (empty($permisos)) {
        echo '<a href="/odontosmart/public/info_clinica.php"> Sobre Nosotros</a>';
        echo '<a href="/odontosmart/modulos/ventas/servicios.php"> Servicios</a>';
        echo '<a href="/odontosmart/modulos/ventas/pagar.php"> Ir a pagar</a>';
    }
    ?>

    <!-- Cerrar sesión -->
    <a href="/odontosmart/auth/logout.php" style="margin-top: 50px; background: #dc3545;"> Cerrar Sesión</a>
</div>
