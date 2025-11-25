<?php
// historial_ventas.php

//utiliza las tablas Ventas y Usuarios
session_start();
include('../../config/conexion.php');

// Obtener historial de ventas
$sql_ventas = "SELECT 
                    v.id_venta,
                    v.fecha_venta,
                    v.id_cliente,
                    v.metodo_pago,
                    v.total,
                    u.nombre_completo AS vendedor_nombre
               FROM ventas v
               LEFT JOIN usuarios u ON v.id_usuario = u.id_usuario
               ORDER BY v.fecha_venta DESC
               LIMIT 50";

$ventas = $conn->query($sql_ventas);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title >Historial de Ventas - OdontoSmart</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 0; 
            background: #f5f5f5;
        }
        .navbar { 
            width: 220px; 
            background-color: #69B7BF; 
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
            background: #69B7BF; 
            color: white; 
        }
        tr:hover {
            background: #f5f5f5;
        }
        .btn-ver {
            padding: 5px 10px;
            background: #264CBF;
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
            background: #182940;
            color: white;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
        }
        .logo-navbar {
            position: absolute;
            bottom: 80px;        /* Ajustá lo alto o bajo que querás */
            left: 50%;
            transform: translateX(-50%);
            width: 140px;        /* Tamaño del logo */
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="navbar">
    <!-- Logo inferior del menú -->
    <?php include('../../views/navbar.php'); ?>
    <img src="../../assets/img/odonto1.png" class="logo-navbar" alt="Logo OdontoSmart">
</div>

    <div class="content">
        <h1 style="color: #69B7BF;"> Historial de Ventas - OdontoSmart</h1>

          <!-- Estadísticas Rápidas -->
        <div class="estadisticas">
            <div class="estadistica-card">
                <h3>Total Ventas Hoy</h3>
                <p style="font-size: 24px; margin: 0;">
                    ₡<?php 
                    $sql_hoy = "SELECT COALESCE(SUM(total), 0) AS total_hoy 
                                FROM ventas 
                                WHERE fecha_venta = CURDATE()";
                    $result_hoy = $conn->query($sql_hoy);
                    echo number_format($result_hoy->fetch_assoc()['total_hoy'], 2);
                    ?>
                </p>
            </div>

            <div class="estadistica-card" style="background: #28a745;">
                <h3>Ventas del Mes</h3>
                <p style="font-size: 24px; margin: 0;">
                    ₡<?php 
                    $sql_mes = "SELECT COALESCE(SUM(total), 0) AS total_mes 
                               FROM ventas 
                               WHERE MONTH(fecha_venta) = MONTH(CURDATE()) 
                               AND YEAR(fecha_venta) = YEAR(CURDATE())";

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
                        <th>ID Venta</th>
                        <th>Fecha</th>
                        <th>ID Cliente</th>
                        <th>Vendedor</th>
                        <th>Método Pago</th>
                        <th>Total</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while($venta = $ventas->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $venta['id_venta']; ?></td>
                        <td><?php echo $venta['fecha_venta']; ?></td>
                        <td><?php echo $venta['id_cliente']; ?></td>
                        <td><?php echo $venta['vendedor_nombre'] ?? 'Sistema'; ?></td>
                        <td><?php echo ucfirst($venta['metodo_pago']); ?></td>
                        <td>₡<?php echo number_format($venta['total'], 2); ?></td>
                        <td>
                           <a class="btn-ver" href="factura.php?id_venta=<?php echo $venta['id_venta']; ?>" target="_blank">
                            Ver Factura
                          </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>