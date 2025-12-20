<?php
// sidebar.php
// Menú lateral del sistema.
// Muestra opciones según el rol y permisos del usuario autenticado.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar que exista sesión de usuario
if (!isset($_SESSION['user'])) {
    header('Location: ../auth/iniciar_sesion.php?error=' . urlencode('Debes iniciar sesión.'));
    exit;
}

// Rol del usuario
$rol = $_SESSION['user']['role'] ?? null;

// Roles válidos del sistema
$rolesPermitidos = ['Administrador', 'Médico', 'Cliente', 'Recepcionista'];

if (!in_array($rol, $rolesPermitidos, true)) {
    header('Location: ../auth/iniciar_sesion.php?error=' . urlencode('Acceso no autorizado.'));
    exit;
}

// Permisos asignados al usuario
$permisos = $_SESSION['user']['permisos'] ?? [];

// Permisos definidos por rol
$permisosPorRol = [
    'Cliente' => [
        'ver_info_clinica',
        'ver_servicios',
        'agendar_cita',
        'ir_a_pagar',
    ],
    'Administrador' => [
        'ver_info_clinica',
        'ver_servicios',
        'ir_a_pagar',
        'ver_inventario',
        'control_inventario',
        'gestion_usuarios',
        'ver_historial_ventas',
        'gestion_citas',
        'bitacora_sistema',
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

// Si el rol tiene permisos definidos, se usan estos
if (isset($permisosPorRol[$rol])) {
    $permisos = $permisosPorRol[$rol];
}

// Verifica si el usuario tiene un permiso específico
function tienePermiso(string $permiso, array $permisos): bool {
    return in_array($permiso, $permisos, true);
}
?>

<div class="sidebar">

    <a href="/odontosmart/public/home.php">🏠 Inicio</a>

    <?php if (tienePermiso('ver_info_clinica', $permisos)): ?>
        <a href="/odontosmart/public/info_clinica.php">ℹ️ Sobre nosotros</a>
    <?php endif; ?>

    <?php if (tienePermiso('ver_servicios', $permisos)): ?>
        <a href="/odontosmart/modulos/ventas/productos.php">🦷 Productos</a>
    <?php endif; ?>

    <?php if (tienePermiso('agendar_cita', $permisos)): ?>
        <a href="/odontosmart/modulos/citas/agendar_cita.php">📅 Agendar cita</a>
    <?php endif; ?>

    <?php if (tienePermiso('ir_a_pagar', $permisos)): ?>
        <a href="/odontosmart/modulos/ventas/carrito.php">🛒 Ver carrito</a>
    <?php endif; ?>

    <?php if (tienePermiso('ver_inventario', $permisos)): ?>
        <a href="/odontosmart/modulos/inventario/total_inventario.php">📦 Inventario</a>
    <?php endif; ?>

    <?php if (tienePermiso('control_inventario', $permisos)): ?>
        <a href="/odontosmart/modulos/inventario/inventario.php">📊 Control de inventario</a>
    <?php endif; ?>

    <?php if (tienePermiso('gestion_usuarios', $permisos)): ?>
        <a href="/odontosmart/modulos/usuarios/gestion_usuarios.php">👥 Gestión de usuarios</a>
    <?php endif; ?>

    <?php if (tienePermiso('ver_historial_ventas', $permisos)): ?>
        <a href="/odontosmart/modulos/ventas/historial_ventas.php">📈 Historial ventas</a>
    <?php endif; ?>

    <?php if (tienePermiso('gestion_citas', $permisos)): ?>
        <a href="/odontosmart/modulos/citas/gestion_citas.php">📋 Gestión de citas</a>
    <?php endif; ?>

    <?php if (tienePermiso('bitacora_sistema', $permisos)): ?>
        <a href="/odontosmart/modulos/bitacora/bitacora_sistema.php">📄 Bitácora del sistema</a>
    <?php endif; ?>

      <a href="/odontosmart/auth/cerrar_sesion.php" class="btn-logout">
        Cerrar sesión
    </a>

    <img
    src="/odontosmart/assets/img/odonto1.png"
    alt="OdontoSmart"
    class="logo-sidebar"
    >

</div>
