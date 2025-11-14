<?php
// factura.php
session_start();
include('conexion.php');

$id_venta = $_GET['id_venta'] ?? ($_SESSION['ultima_venta'] ?? '');

if (empty($id_venta)) {
    header("Location: servicios.php");
    exit();
}

// Obtener datos de la venta SIN la tabla pacientes
$sql_venta = "SELECT v.*, u.nombre_completo as vendedor_nombre
              FROM ventas v
              LEFT JOIN usuarios u ON v.id_usuario = u.id_usuario
              WHERE v.id_ventas = ?";
$stmt_venta = $conn->prepare($sql_venta);
$stmt_venta->bind_param("i", $id_venta);
$stmt_venta->execute();
$venta = $stmt_venta->get_result()->fetch_assoc();

if (!$venta) {
    die("Venta no encontrada");
}

// Obtener detalles de la venta -
$sql_detalles = "SELECT dv.*, p.nombre as producto_nombre, p.descripcion
                 FROM detalle_venta dv
                 LEFT JOIN productos p ON dv.id_producto = p.id_producto
                 WHERE dv.id_venta = ?";
$stmt_detalles = $conn->prepare($sql_detalles);
$stmt_detalles->bind_param("i", $id_venta);
$stmt_detalles->execute();
$detalles = $stmt_detalles->get_result();

$rol = "administrador"; // Temporal
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura - OdontoSmart</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 20px;
            background: #f5f5f5;
        }
        .factura-container {
            background: white;
            padding: 30px;
            margin: 0 auto;
            max-width: 800px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #152fbf;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .clinic-info h1 {
            color: #152fbf;
            margin: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #152fbf;
            color: white;
        }
        .totales {
            background: #e8f4ff;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .btn {
            padding: 10px 20px;
            background: #152fbf;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 10px;
            text-decoration: none;
            display: inline-block;
        }
        @media print {
            .no-print { display: none; }
            body { background: white; }
            .factura-container { box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="factura-container">
        <!-- Header -->
        <div class="header">
            <div class="clinic-info">
                <h1>OdontoSmart</h1>
                <p>Clínica Dental Especializada</p>
                <p>Teléfono: (506) 2222-2222 | Email: info@odontosmart.com</p>
            </div>
        </div>

        <!-- Información de Factura -->
        <div style="display: flex; justify-content: space-between; margin-bottom: 30px;">
            <div>
                <h3>Información de Venta</h3>
                <p><strong>Cliente:</strong> Cliente General</p>
                <p><strong>ID Cliente:</strong> <?php echo $venta['id_cliente']; ?></p>
                <p><strong>Vendedor:</strong> <?php echo $venta['vendedor_nombre'] ?? 'Sistema'; ?></p>
            </div>
            <div>
                <h3>Factura</h3>
                <p><strong>Número:</strong> <?php echo $venta['numero_factura']; ?></p>
                <p><strong>Fecha:</strong> <?php echo $venta['fecha']; ?></p>
                <p><strong>Método de Pago:</strong> <?php echo ucfirst($venta['metodo_pago']); ?></p>
            </div>
        </div>

        <!-- Detalles de Productos -->
        <h3>Detalles de la Venta</h3>
        <?php if ($detalles->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Descripción</th>
                        <th>Cantidad</th>
                        <th>Precio Unit.</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($detalle = $detalles->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $detalle['producto_nombre']; ?></td>
                        <td><?php echo $detalle['descripcion'] ?? 'N/A'; ?></td>
                        <td><?php echo $detalle['cantidad']; ?></td>
                        <td>₡<?php 
                            $precio_unitario = $detalle['total_definitivo'] / $detalle['cantidad'];
                            echo number_format($precio_unitario, 2); 
                        ?></td>
                        <td>₡<?php echo number_format($detalle['total_definitivo'], 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="color: #dc3545; text-align: center; padding: 20px;">
                No se encontraron detalles para esta venta.
            </p>
        <?php endif; ?>

        <!-- Totales -->
        <div class="totales">
            <h3>Resumen de Pagos</h3>
            <p><strong>Subtotal: ₡<?php echo number_format($venta['subtotal'], 2); ?></strong></p>
            <p>IVA (13%): ₡<?php echo number_format($venta['iva_monto'], 2); ?></p>
            <p style="font-size: 20px; color: #152fbf;"><strong>Total: ₡<?php echo number_format($venta['total'], 2); ?></strong></p>
        </div>

        <!-- Mensaje de agradecimiento -->
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <p><strong>¡Gracias por su compra!</strong></p>
            <p>Factura generada electrónicamente - <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>

        <!-- Botones de Acción -->
        <div class="no-print" style="text-align: center; margin-top: 20px;">
            <button class="btn" onclick="window.print()">Imprimir Factura</button>
            <a href="servicios.php" class="btn">Nueva Venta</a>
            <a href="historial_ventas.php" class="btn">Historial</a>
        </div>
    </div>
</body>
</html>