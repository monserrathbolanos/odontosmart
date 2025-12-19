<?php
session_start();
require '../../config/conexion.php';
require_once __DIR__ . '/../../config/alerts.php';

// Inicializar variables
$venta = null;
$productos = [];
$promociones = [];
$descuento_total = 0;

try {
    // Verificar que se recibió un id_venta por URL
    if (!isset($_GET['id_venta'])) {
        stopWithAlert('No se indicó la venta.', 'Venta no indicada', 'error');
    }

    $id_venta = (int)$_GET['id_venta'];

    // Obtener datos generales de la venta junto con los datos del cliente
    // AHORA: los datos del cliente vienen de usuarios (uc), no de clientes (c)
    $sql_venta = "
        SELECT 
            v.id_venta,
            v.fecha_venta,
            v.subtotal,
            v.impuestos,
            v.total,
            v.metodo_pago,

            CONCAT(uc.nombre, ' ', uc.apellido1, ' ', IFNULL(uc.apellido2, '')) AS cliente_nombre,
            uc.telefono   AS cliente_telefono,
            uc.email      AS cliente_correo

        FROM ventas v
        INNER JOIN clientes c ON v.id_cliente = c.id_cliente
        INNER JOIN usuarios uc ON c.id_usuario = uc.id_usuario
        WHERE v.id_venta = ?
    ";

    $stmt = $conn->prepare($sql_venta);
    $stmt->bind_param("i", $id_venta);
    $stmt->execute();
    $venta = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Verificar si la venta existe
    if (!$venta) {
        stopWithAlert('Venta no encontrada.', 'Venta no encontrada', 'error');
    }

    // Consultar detalle de productos comprados
    $sql_detalle = "SELECT dv.id_producto, p.nombre, dv.cantidad, dv.precio_unitario, dv.total, dv.descuento
                    FROM detalle_venta dv
                    JOIN productos p ON dv.id_producto = p.id_producto
                    WHERE dv.id_venta = ?";

    $stmt2 = $conn->prepare($sql_detalle);
    $stmt2->bind_param("i", $id_venta);
    $stmt2->execute();
    $result_detalle = $stmt2->get_result();

    // Arreglo para almacenar los productos de la factura
    $productos = [];
    while ($row = $result_detalle->fetch_assoc()) {
        $productos[] = $row;
    }
    $stmt2->close();

    // Consultar promociones aplicadas a esta venta
    $sql_promociones = "SELECT vp.descuento_aplicado, p.nombre as nombre_promocion, p.tipo_descuento, p.valor_descuento
                        FROM ventas_promociones vp
                        JOIN promociones p ON vp.id_promocion = p.id_promocion
                        WHERE vp.id_venta = ?";

    $stmt3 = $conn->prepare($sql_promociones);
    $stmt3->bind_param("i", $id_venta);
    $stmt3->execute();
    $result_promociones = $stmt3->get_result();

    $promociones = [];
    $descuento_total = 0;
    while ($row = $result_promociones->fetch_assoc()) {
        $promociones[] = $row;
        $descuento_total += $row['descuento_aplicado'];
    }
    $stmt3->close();

} catch (Throwable $e) {
    // Intentar rollback
    try {
        if (isset($conn) && $conn instanceof mysqli) {
            if (isset($conn) && $conn instanceof mysqli && method_exists($conn, 'in_transaction') && $conn->in_transaction()) {
                try { $conn->rollback(); } catch (Throwable $__ignore) {}
            }
        }
    } catch (Throwable $__ignored) {}

    // Registrar en bitácora
    try {
        if (isset($conn)) { @$conn->close(); }
        include_once '../../config/conexion.php';

        $id_usuario_log = $_SESSION['user']['id_usuario'] ?? null;
        $accion = 'FACTURA_ERROR';
        $modulo = 'ventas/factura';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
        $detalles = 'Error técnico: ' . $e->getMessage();

        $stmtLog = $conn->prepare("CALL SP_USUARIO_BITACORA(?, ?, ?, ?, ?, ?)");
        if ($stmtLog) {
            $stmtLog->bind_param("isssss", $id_usuario_log, $accion, $modulo, $ip, $user_agent, $detalles);
            $stmtLog->execute();
            $stmtLog->close();
        }
        if (isset($conn)) { @$conn->close(); }
    } catch (Throwable $logError) {
        error_log("Fallo al escribir en bitácora (factura): " . $logError->getMessage());
    }

    stopWithAlert('Ocurrió un error al cargar la factura.', 'Error', 'error');
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura #<?php echo $venta['id_venta']; ?></title>
    <!-- FAVICON -->
    <link rel="icon" type="image/png" href="../../assets/img/odonto1.png">

    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        h1 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #69B7BF; padding: 10px; text-align: left; }
        th { background: #152fbf; color: white; }
        tfoot td { font-weight: bold; }

        .boton-carrito {
            display: inline-block;
            background: #28a745;
            color: #fff;
            padding: 12px 28px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 16px;
            font-weight: bold;
            margin-top: 20px;
        }
    </style>
</head>
<body>

    <h1>Factura #<?php echo $venta['id_venta']; ?></h1>
    <p><strong>Fecha:</strong> <?php echo $venta['fecha_venta']; ?></p>
    <p><strong>Cliente:</strong> <?php echo $venta['cliente_nombre']; ?></p>
    <p><strong>Teléfono:</strong> <?php echo $venta['cliente_telefono']; ?> |
       <strong>Correo:</strong> <?php echo $venta['cliente_correo']; ?></p>
    <p><strong>Método de pago:</strong> <?php echo $venta['metodo_pago']; ?></p>

    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio unitario</th>
                <th>Total</th>
            </tr>
        </thead>

        <tbody>
        <?php foreach ($productos as $p): ?>
            <tr>
                <td><?php echo $p['nombre']; ?></td>
                <td><?php echo $p['cantidad']; ?></td>
                <td>₡<?php echo number_format($p['precio_unitario'], 2); ?></td>
                <td>
                    ₡<?php echo number_format($p['total'], 2); ?>
                    <?php if ($p['descuento'] > 0) {
                        echo " (descuento: ₡" . number_format($p['descuento'], 2) . ")";
                    } ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>

        <tfoot>
            <tr>
                <td colspan="3" style="text-align:right;">Subtotal:</td>
                <td>₡<?php echo number_format($venta['subtotal'], 2); ?></td>
            </tr>
          <?php if (!empty($promociones)): ?>
                <tr>
                    <td colspan="4" style="text-align:right; font-style:italic; color:#155724;">
                        * El subtotal incluye descuentos promocionales aplicados.
                    </td>
                </tr>
                <?php endif; ?>
            <tr>
                <td colspan="3" style="text-align:right;">Impuestos:</td>
                <td>₡<?php echo number_format($venta['impuestos'], 2); ?></td>
            </tr>
            <tr style="background:#eef4ff;">
                <td colspan="3" style="text-align:right; font-size:18px;">TOTAL A PAGAR:</td>
                <td style="font-size:18px;">₡<?php echo number_format($venta['total'], 2); ?></td>
            </tr>
        </tfoot>
    </table>

    <div style="text-align: center;">
        <a href="carrito.php" class="boton-carrito">Volver al carrito</a>
    </div>

</body>
</html>
