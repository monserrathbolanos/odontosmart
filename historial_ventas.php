<?php
// historial_ventas.php
session_start();
include('conexion.php');

// Obtener historial de ventas
$sql_ventas = "SELECT v.*, u.nombre_completo as vendedor_nombre
               FROM ventas v
               LEFT JOIN usuarios u ON v.id_usuario = u.id_usuario
               ORDER BY v.fecha DESC, v.creado_en DESC
               LIMIT 50";
$ventas = $conn->query($sql_ventas);

$rol = "administrador"; // Temporal
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Ventas - OdontoSmart</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 0; 
            background: #f5f5f5;
        }
        .navbar { 
            width: 220px; 
            background-color: #152fbf; 
            height: 100vh; 
            padding-top: 20px; 
            position: fixed; 
        }
        .navbar a { 
            display: block; 
            color: #ecf0f1; 
            padding: 12px; 
            text-decoration: none; 
            margin: 5px 0; 
            border-radius: 4px; 
        }
        .navbar a:hover { 
            background-color: #264cbf; 
        }
        .content { 
            margin-left: 240px; 
            padding: 20px; 
        }
        .seccion {
            background: white;
            padding: 20px;
            margin: 15px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 15px 0;
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
        tr:hover {
            background: #f5f5f5;
        }
        .btn-ver {
            padding: 5px 10px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        .estadisticas {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        .estadistica-card {
            flex: 1;
            background: #152fbf;
            color: white;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <?php include('navbar.php'); ?>
    </div>

    <div class="content">
        <h1> Historial de Ventas - OdontoSmart</h1>

        <!-- Estadísticas Rápidas -->
        <div class="estadisticas">
            <div class="estadistica-card">
                <h3>Total Ventas Hoy</h3>
                <p style="font-size: 24px; margin: 0;">
                    ₡<?php 
                    $sql_hoy = "SELECT COALESCE(SUM(total), 0) as total_hoy FROM ventas WHERE fecha = CURDATE()";
                    $result_hoy = $conn->query($sql_hoy);
                    echo number_format($result_hoy->fetch_assoc()['total_hoy'], 2);
                    ?>
                </p>
            </div>
            <div class="estadistica-card" style="background: #28a745;">
                <h3>Ventas del Mes</h3>
                <p style="font-size: 24px; margin: 0;">
                    ₡<?php 
                    $sql_mes = "SELECT COALESCE(SUM(total), 0) as total_mes FROM ventas WHERE MONTH(fecha) = MONTH(CURDATE())";
                    $result_mes = $conn->query($sql_mes);
                    echo number_format($result_mes->fetch_assoc()['total_mes'], 2);
                    ?>
                </p>
            </div>
        </div>

        <!-- Tabla de Ventas -->
        <div class="seccion">
            <h3> Últimas Ventas</h3>
            <table>
                <thead>
                    <tr>
                        <th>Factura</th>
                        <th>Fecha</th>
                        <th>Cliente ID</th>
                        <th>Vendedor</th>
                        <th>Método Pago</th>
                        <th>Total</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($venta = $ventas->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $venta['numero_factura']; ?></td>
                        <td><?php echo $venta['fecha']; ?></td>
                        <td><?php echo $venta['id_cliente']; ?></td>
                        <td><?php echo $venta['vendedor_nombre'] ?? 'Sistema'; ?></td>
                        <td><?php echo ucfirst($venta['metodo_pago']); ?></td>
                        <td>₡<?php echo number_format($venta['total'], 2); ?></td>
                        <td>
                            <button class="btn-ver" onclick="verFactura(<?php echo $venta['id_ventas']; ?>)">
                                 Ver Factura
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function verFactura(idVenta) {
            window.open('factura.php?id_venta=' + idVenta, '_blank');
        }
    </script>
</body>
</html>