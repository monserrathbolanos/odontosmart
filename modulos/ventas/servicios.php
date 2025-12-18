<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// validar rol
$rol = $_SESSION['user']['role'] ?? null;
$rolesPermitidos = ['Administrador', 'Cliente'];

if (!in_array($rol, $rolesPermitidos)) {
    header(
        'Location: ../../auth/iniciar_sesion.php?error=' .
        urlencode('Debes iniciar sesión o registrarte.')
    );
    exit;
}

// conexión
include '../../config/conexion.php';

// mostrar productos por categoría
function mostrarCategoria($conn, $categoriaNombre)
{
    echo "<div class='categoria-titulo'>{$categoriaNombre}</div>";

    $sql = "
        SELECT
            p.id_producto,
            p.nombre,
            p.descripcion,
            p.unidad,
            p.id_categoria,
            p.precio,
            p.stock_total,
            p.stock_minimo,
            p.fecha_creacion,
            p.actualizado_en,
            p.estado,
            c.nombre AS categoria
        FROM productos p
        JOIN categoria_productos c
            ON p.id_categoria = c.id_categoria
        WHERE c.nombre = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $categoriaNombre);
    $stmt->execute();
    $result = $stmt->get_result();

    $productosAgotados = [];

    if ($result->num_rows === 0) {
        echo "<p class='sin-productos'>No hay productos disponibles.</p>";
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
                <th>Stock Mínimo</th>
                <th>Stock Actual</th>
                <th>Categoría</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
    ";

    while ($row = $result->fetch_assoc()) {

        if ($row['stock_total'] <= $row['stock_minimo']) {
            $productosAgotados[] = $row['nombre'];
        }

        $tiene_promocion = !empty($row['id_promocion']);
        $precio_mostrar  = $tiene_promocion
            ? $row['precio_con_descuento']
            : $row['precio'];

        echo "
        <tr>
            <td>{$row['id_producto']}</td>
            <td>
                <strong>{$row['nombre']}</strong>";

        if ($tiene_promocion) {
            echo " <span class='promo-badge'>¡OFERTA!</span>";
        }

        echo "
            </td>
            <td>{$row['descripcion']}</td>
            <td>{$row['unidad']}</td>
            <td class='precio-cell'>";

        if ($tiene_promocion) {

            echo "
                <span class='precio-original'>₡" . number_format($row['precio'], 2) . "</span><br>
                <span class='precio-promo'>₡" . number_format($precio_mostrar, 2) . "</span>
            ";

            if ($row['tipo_descuento'] === 'porcentaje') {
                echo " <span class='descuento-badge'>-{$row['valor_descuento']}%</span>";
            } else {
                echo " <span class='descuento-badge'>-₡" .
                     number_format($row['valor_descuento'], 0) .
                     "</span>";
            }

        } else {
            echo "₡" . number_format($row['precio'], 2);
        }

        echo "
            </td>
            <td>{$row['stock_minimo']}</td>
            <td>{$row['stock_total']}</td>
            <td>{$row['categoria']}</td>
            <td>
                <form action='agregar_carrito.php'
                      method='POST'
                      style='display:flex; gap:8px; align-items:center;'>
                    <input type='hidden'
                           name='id_producto'
                           value='{$row['id_producto']}'>
                    <input type='number'
                           name='cantidad'
                           value='1'
                           min='1'
                           max='{$row['stock_total']}'
                           required>
                    <button type='submit' class='agregar-btn'>
                        Agregar
                    </button>
                </form>
            </td>
        </tr>
        ";
    }

    echo "</tbody></table>";

    if (!empty($productosAgotados)) {
        echo "<p class='stock-alert'>Productos casi agotados o sin stock:</p><ul>";
        foreach ($productosAgotados as $p) {
            echo "<li>{$p}</li>";
        }
        echo "</ul>";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Servicios y Productos - OdontoSmart</title>

    <link rel="icon" type="image/png" href="../../assets/img/odonto1.png">
    <link rel="stylesheet" href="../../assets/css/servicios.css">
</head>
<body>

<div class="navbar">
    <?php include '../../views/navbar.php'; ?>
    <img src="../../assets/img/odonto1.png" class="logo-navbar" alt="OdontoSmart">
</div>

<div class="content">
    <div class="seccion">

        <h1>Servicios y Productos</h1>

        <p>
            Catálogo de productos y servicios disponibles en la clínica OdontoSmart.
        </p>

        <?php
        if (isset($_GET['agregado'])) {
            echo "<p class='success-msg'>Producto agregado correctamente.</p>";
        }

        try {
            mostrarCategoria($conn, 'Servicios');
            mostrarCategoria($conn, 'Productos de higiene');
            mostrarCategoria($conn, 'Medicamentos');
        } catch (Throwable $e) {

            try {
                if ($conn && method_exists($conn, 'in_transaction') && $conn->in_transaction()) {
                    $conn->rollback();
                }
            } catch (Throwable $ignore) {}

            try {
                $conn->close();
                include '../../config/conexion.php';

                $id_usuario = $_SESSION['user']['id_usuario'] ?? null;
                $accion = 'SERVICE_ERROR';
                $modulo = 'servicios';
                $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
                $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
                $detalle = $e->getMessage();

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

        <a href="carrito.php">
            <button class='btn-ver-carrito'>Ver Carrito</button>
        </a>

    </div>
</div>

</body>
</html>
