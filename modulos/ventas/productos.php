<?php
// Módulo para consultar productos disponibles (solo productos físicos)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rol = $_SESSION['user']['role'] ?? null;
$rolesPermitidos = ['Administrador', 'Cliente'];
if (!in_array($rol, $rolesPermitidos, true)) {
    header('Location: ../../auth/iniciar_sesion.php?error=' . urlencode('Debes iniciar sesión o registrarte.'));
    exit;
}
require '../../config/conexion.php';

function mostrarProductos(mysqli $conn): void
{
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
        WHERE LOWER(c.nombre) != 'servicios'
        ORDER BY c.nombre, p.nombre
    ";
    $result = $conn->query($sql);
    if ($result->num_rows === 0) {
        echo "<p class='sin-productos'>No hay productos disponibles.</p>";
        return;
    }
    echo "<table><thead><tr>";
    echo "<th>ID</th><th>Nombre</th><th>Descripción</th><th>Unidad</th><th>Precio</th><th>Stock mínimo</th><th>Stock actual</th><th>Categoría</th><th>Acción</th></tr></thead><tbody>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id_producto']}</td>";
        echo "<td><strong>{$row['nombre']}</strong></td>";
        echo "<td>{$row['descripcion']}</td>";
        echo "<td>{$row['unidad']}</td>";
        echo "<td class='precio'>₡" . number_format($row['precio'], 2) . "</td>";
        echo "<td>{$row['stock_minimo']}</td>";
        echo "<td>{$row['stock_total']}</td>";
        echo "<td>{$row['categoria']}</td>";
        echo "<td>";
        echo "<form action='agregar_carrito.php' method='POST' class='form-agregar'>";
        echo "<input type='hidden' name='id_producto' value='{$row['id_producto']}'>";
        echo "<input type='number' name='cantidad' value='1' min='1' max='{$row['stock_total']}' required>";
        echo "<button type='submit' class='agregar-btn'>Agregar</button>";
        echo "</form>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Productos - OdontoSmart</title>
    <link rel="icon" type="image/png" href="../../assets/img/odonto1.png">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/servicios.css">
</head>
<body>
<?php include '../../views/sidebar.php'; ?>
<div class="content">
    <div class="seccion">
        <h1 style="color:#69B7BF;">Productos</h1>
        <p>Catálogo de productos disponibles en la clínica OdontoSmart.</p>
        <?php if (isset($_GET['agregado'])): ?>
            <p class="success-msg">Producto agregado correctamente.</p>
        <?php endif; ?>
        <?php
        try {
            mostrarProductos($conn);
        } catch (Throwable $e) {
            try {
                $conn->close();
                require '../../config/conexion.php';
                $id_usuario = $_SESSION['user']['id_usuario'] ?? null;
                $accion     = 'PRODUCT_ERROR';
                $modulo     = 'productos';
                $ip         = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
                $ua         = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
                $detalle    = $e->getMessage();
                $log = $conn->prepare("CALL SP_USUARIO_BITACORA(?, ?, ?, ?, ?, ?)");
                if ($log) {
                    $log->bind_param("isssss", $id_usuario, $accion, $modulo, $ip, $ua, $detalle);
                    $log->execute();
                    $log->close();
                }
                $conn->close();
            } catch (Throwable $logError) {
                error_log($logError->getMessage());
            }
            echo "<p class='error-msg'>Error al cargar los productos.</p>";
        }
        ?>
        <a href="carrito.php" class="btn-ver-carrito">Ver carrito</a>
    </div>
</div>
</body>
</html>
