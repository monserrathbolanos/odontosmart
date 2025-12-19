<?php
// bitacora_sistema.php
// Módulo de visualización de la bitácora del sistema.
// Acceso exclusivo para el rol Administrador.

session_start();
require '../../config/conexion.php';

/* Control de acceso por rol */
$rol = $_SESSION['user']['role'] ?? null;
if ($rol !== 'Administrador') {
    header('Location: ../../auth/iniciar_sesion.php?error=' . urlencode('Acceso no autorizado'));
    exit;
}

/* Obtener registros de bitácora*/
$bitacoras = [];
$errorCarga = false;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {

    $sql = "
        SELECT
            b.id_bitacora,
            b.fecha,
            b.accion,
            b.modulo,
            b.ip,
            b.user_agent,
            b.detalles,
            CONCAT(
                u.nombre,
                ' ',
                COALESCE(u.apellido1, ''),
                ' ',
                COALESCE(u.apellido2, '')
            ) AS usuario
        FROM bitacoras b
        LEFT JOIN usuarios u ON b.id_usuario = u.id_usuario
        ORDER BY b.fecha DESC
        LIMIT 200
    ";

    $resultado = $conn->query($sql);
    $bitacoras = $resultado->fetch_all(MYSQLI_ASSOC);

} catch (Throwable $e) {
    $errorCarga = true;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Bitácora del Sistema - OdontoSmart</title>

    <!-- Favicon -->
    <link rel="icon" href="/odontosmart/assets/img/odonto1.png">

    <!-- Estilos -->
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/bitacora_sistema.css">

    
</head>

<body>
<div class="sidebar">
    <?php include('../../views/sidebar.php'); ?>
</div>


<div class="content">

    <h1 style="color:#69B7BF;">Bitácora del Sistema</h1>

    <div class="seccion">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Usuario</th>
                    <th>Acción</th>
                    <th>Módulo</th>
                    <th>IP</th>
                    <th>Detalles</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bitacoras as $b): ?>
                <tr>
                    <td><?= $b['id_bitacora'] ?></td>
                    <td><?= $b['fecha'] ?></td>
                    <td><?= htmlspecialchars($b['usuario'] ?? 'Sistema') ?></td>
                    <td><?= htmlspecialchars($b['accion']) ?></td>
                    <td><?= htmlspecialchars($b['modulo'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($b['ip'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($b['detalles'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>


</body>
</html>
