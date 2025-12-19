<?php

// Módulo para consultar servicios y productos disponibles
// Permite visualizar productos por categoría y agregarlos al carrito

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Verifica que el usuario tenga un rol permitido para acceder a este módulo
$rol = $_SESSION['user']['role'] ?? null;
$rolesPermitidos = ['Administrador', 'Cliente'];

if (!in_array($rol, $rolesPermitidos, true)) {
    header(
        'Location: ../../auth/iniciar_sesion.php?error=' .
        urlencode('Debes iniciar sesión o registrarte.')
    );
    exit;
}


// Conexión a la base de datos
require '../../config/conexion.php';


// Muestra los productos de una categoría específica y permite agregarlos al carrito
function mostrarCategoria(mysqli $conn, string $categoriaNombre): void
{
    echo "<div class='categoria-titulo'>{$categoriaNombre}</div>";

    $sql = "
        SELECT
            p.id_producto,
            p.nombre,
            p.descripcion,
            p.unidad,
            p.precio,
            p.stock_total,
            p.stock_minimo,
            c.nombre AS categoria
        FROM productos p
        INNER JOIN categoria_productos c
            ON p.id_categoria = c.id_categoria
        WHERE c.nombre = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $categoriaNombre);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo "<p class='sin-productos'>No hay productos disponibles.</p>";
        $stmt->close();
        return;
    }

    echo "
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Unidad</th>
                    <th>Precio</th>
                    <th>Stock mínimo</th>
                    <th>Stock actual</th>
                    <th>Categoría</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
    ";

    while ($row = $result->fetch_assoc()) {
        echo "
            <tr>
                <td>{$row['id_producto']}</td>
                <td><strong>{$row['nombre']}</strong></td>
                <td>{$row['descripcion']}</td>
                <td>{$row['unidad']}</td>
                <td class='precio'>₡" . number_format($row['precio'], 2) . "</td>
                <td>{$row['stock_minimo']}</td>
                <td>{$row['stock_total']}</td>
                <td>{$row['categoria']}</td>
                <td>
                    <form action='agregar_carrito.php' method='POST' class='form-agregar'>
                        <input type='hidden' name='id_producto' value='{$row['id_producto']}'>
                        <input
                            type='number'
                            name='cantidad'
                            value='1'
                            min='1'
                            max='{$row['stock_total']}'
                            required
                        >
                        <button type='submit' class='agregar-btn'>Agregar</button>
                    </form>
                </td>
            </tr>
        ";
    }

    echo "</tbody></table>";

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Servicios y Productos - OdontoSmart</title>

    <link rel="icon" type="image/png" href="../../assets/img/odonto1.png">

    <!-- Estilos -->
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/servicios.css">
</head>
<body>

<?php include '../../views/sidebar.php'; ?>

<div class="content">
    <div class="seccion">

        <h1 style="color:#69B7BF;" >Servicios y Productos</h1>

        <p>
            Catálogo de productos y servicios disponibles en la clínica OdontoSmart.
        </p>

        <?php if (isset($_GET['agregado'])): ?>
            <p class="success-msg">Producto agregado correctamente.</p>
        <?php endif; ?>

        <?php
        try {
            mostrarCategoria($conn, 'Servicios');
            mostrarCategoria($conn, 'Productos de higiene');
            mostrarCategoria($conn, 'Medicamentos');
        } catch (Throwable $e) {

            try {
                $conn->close();
                require '../../config/conexion.php';

                $id_usuario = $_SESSION['user']['id_usuario'] ?? null;
                $accion     = 'SERVICE_ERROR';
                $modulo     = 'servicios';
                $ip         = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
                $ua         = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
                $detalle    = $e->getMessage();

                $log = $conn->prepare(
                    "CALL SP_USUARIO_BITACORA(?, ?, ?, ?, ?, ?)"
                );

                if ($log) {
                    $log->bind_param(
                        "isssss",
                        $id_usuario,
                        $accion,
                        $modulo,
                        $ip,
                        $ua,
                        $detalle
                    );
                    $log->execute();
                    $log->close();
                }

                $conn->close();
            } catch (Throwable $logError) {
                error_log($logError->getMessage());
            }

            echo "<p class='error-msg'>Error al cargar los servicios.</p>";
        }
        ?>

        <a href="carrito.php" class="btn-ver-carrito">
            Ver carrito
        </a>

    </div>
</div>

</body>
</html>
