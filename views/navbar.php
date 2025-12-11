<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*  CONTROL DE ACCESO
   
   Si no existe un usuario en la sesión, se redirige al módulo de
   inicio seseion y NO se permite el acceso directo por URL.
*/
$rol = $_SESSION['user']['role'] ?? null;
$rolesPermitidos = ['Administrador', 'Médico', 'Cliente', 'Recepcionista']; 
if (!in_array($rol, $rolesPermitidos)) {
    header('Location: ../auth/iniciar_sesion.php?error=' . urlencode('Debes iniciar sesión o registrarte.'));
    exit;
}

// Datos del usuario logueado
$rol      = $_SESSION['user']['role']     ?? null;      // 'Cliente', 'Administrador', 'Medico', 'Recepcionista', etc.
$permisos = $_SESSION['user']['permisos'] ?? [];        // array de strings

// Mapa de permisos por rol (forzamos lo que cada rol puede ver)
$permisosPorRol = [
    'Cliente' => [
        'ver_info_clinica',
        'ver_servicios',
        'agendar_cita',   // gestión de sus citas
        'ir_a_pagar',
    
    ],
    'Administrador' => [
        'ver_info_clinica',
        'ver_servicios',
        // 'agendar_cita',
        'ir_a_pagar',
        'ver_inventario',
        'control_inventario',
        'gestion_usuarios',
        'ver_historial_ventas',
        'gestion_citas',
    ],
    'Médico' => [
        'ver_info_clinica',
        'ver_inventario',
        'gestion_citas',
    ],
    'Recepcionista' => [
        'ver_info_clinica',
        'ver_inventario',
        'gestion_citas',
    ],
];

// Si el rol está definido en el mapa, usamos esos permisos fijos
if (isset($permisosPorRol[$rol])) {
    $permisos = $permisosPorRol[$rol];
}

// Función helper para revisar permisos
function tienePermiso(string $permiso, array $permisos): bool {
    return in_array($permiso, $permisos);
}
?>

<!-- Navbar ahora responde a los permisos de usuario segun el rol asignado -->
<div class="navbar">
    <?php

    echo '<a href="/odontosmart/public/home.php"> 🏠 Inicio</a>';
    
    if (tienePermiso('ver_info_clinica', $permisos)) {
        echo '<a href="/odontosmart/public/info_clinica.php"> ℹ️ Sobre nosotros</a>';
    }

    if (tienePermiso('ver_servicios', $permisos)) {
        echo '<a href="/odontosmart/modulos/ventas/servicios.php"> 🦷 Servicios</a>';
    }

    if (tienePermiso('agendar_cita', $permisos)) {
        echo '<a href="/odontosmart/modulos/citas/agendar_cita.php"> 📅 Agendar cita</a>';
    }

    if (tienePermiso('ir_a_pagar', $permisos)) {
        echo '<a href="/odontosmart/modulos/ventas/carrito.php">🛒 Ver carrito</a>';
    }

    if (tienePermiso('ver_inventario', $permisos)) {
        echo '<a href="/odontosmart/modulos/inventario/total_inventario.php">📦 Inventario</a>';
    }

    if (tienePermiso('control_inventario', $permisos)) {
        echo '<a href="/odontosmart/modulos/inventario/inventario.php"> 📊 Control de inventario</a>';
    }

    if (tienePermiso('gestion_usuarios', $permisos)) {
        echo '<a href="/odontosmart/modulos/usuarios/gestion_usuarios.php"> 👥 Gestión de usuarios</a>';
    }

    if (tienePermiso('ver_historial_ventas', $permisos)) {
        echo '<a href="/odontosmart/modulos/ventas/historial_ventas.php">📈 Historial Ventas</a>';
    }

    if (tienePermiso('gestion_citas', $permisos)) {
        echo '<a href="/odontosmart/modulos/citas/gestion_citas.php"> 📋 Gestión de citas</a>';
    }

    
    ?>

    <!-- Cerrar sesión -->
<a href="/odontosmart/auth/cerrar_sesion.php" class="btn-logout">Cerrar Sesión</a>

<style>
  .btn-logout {
    margin-top: 50px;
    margin-left: 50px;
    background: #dc3545;
    padding: 6px 12px;
    font-size: 15px;
    border-radius: 5px;
    display: inline-block;
    color: white;
    text-decoration: none;
  }

  .btn-logout:hover {
    background: #bb2d3b;
  }
</style>

</div>
