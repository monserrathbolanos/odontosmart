<?php
session_start();
require '../../config/conexion.php';

// Verificar que se recibió un id_venta por URL
if (!isset($_GET['id_venta'])) {
    die("No se indicó la venta.");
}

$id_venta = $_GET['id_venta'];

// Obtener datos generales de la venta junto con los datos del cliente
$sql_venta = "SELECT v.id_venta, v.fecha_venta, v.subtotal, v.impuestos, v.total, 
                     v.metodo_pago, c.nombre, c.apellido, c.telefono, c.correo
              FROM ventas v
              JOIN clientes c ON v.id_cliente = c.id_cliente
              WHERE v.id_venta = ?";

$stmt = $conn->prepare($sql_venta);
$stmt->bind_param("i", $id_venta);
$stmt->execute();
$venta = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Verificar si la venta existe
if (!$venta) {
    die("Venta no encontrada.");
}

// Consultar detalle de productos comprados
$sql_detalle = "SELECT dv.id_producto, p.nombre, dv.cantidad, dv.precio_unitario, dv.total
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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura #<?php echo $venta['id_venta']; ?></title>

    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        h1 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
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
    <p><strong>Cliente:</strong> <?php echo $venta['nombre'] . " " . $venta['apellido']; ?></p>
    <p><strong>Teléfono:</strong> <?php echo $venta['telefono']; ?> |
       <strong>Correo:</strong> <?php echo $venta['correo']; ?></p>
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
                <td>₡<?php echo number_format($p['total'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>

        <tfoot>
            <tr>
                <td colspan="3" style="text-align:right;">Subtotal:</td>
                <td>₡<?php echo number_format($venta['subtotal'], 2); ?></td>
            </tr>
            <tr>
                <td colspan="3" style="text-align:right;">Impuestos:</td>
                <td>₡<?php echo number_format($venta['impuestos'], 2); ?></td>
            </tr>
            <tr>
                <td colspan="3" style="text-align:right;">Total:</td>
                <td>₡<?php echo number_format($venta['total'], 2); ?></td>
            </tr>
        </tfoot>
    </table>

    <div style="text-align: center;">
        <a href="carrito.php" class="boton-carrito">Volver al carrito</a>
    </div>

</body>
</html>
