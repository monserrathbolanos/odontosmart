<!-- historial_ventas.php -->
<!-- Este codigo se encarga de mostrar el historial de ventas realizadas, ademas de generar un reporte visual de datos importantes relacionados a ventas -->

<?php
//Tablas utilizadas: ventas y usuarios

session_start();

/* Validar rol permitido */
$rol = $_SESSION['user']['role'] ?? null;
$rolesPermitidos = ['Administrador','Recepcionista']; // ej.

if (!in_array($rol, $rolesPermitidos)) {
    // Aquí decides a dónde mandarlo: login, home o protegido.
    // Si quieres mandarlo al login:
    header('Location: ../../auth/iniciar_sesion.php?error=' . urlencode('Debes iniciar sesión o registrarte.'));
    exit;
}
include('../../config/conexion.php');

// Obtener historial de ventas
$sql_ventas = "SELECT 
                    v.id_venta,
                    v.fecha_venta,
                    v.id_cliente,
                    v.metodo_pago,
                    v.total,
                    CONCAT(u.nombre, ' ', COALESCE(u.apellido1, ''), ' ', COALESCE(u.apellido2, '')) AS vendedor_nombre
               FROM ventas v
               LEFT JOIN usuarios u ON v.id_usuario = u.id_usuario
               ORDER BY v.fecha_venta DESC
               LIMIT 50";

$ventas = $conn->query($sql_ventas);

//Consultas de los productos mas y menos vendidos del mes
// Productos más vendidos del mes
$sql_top = "
    SELECT p.nombre AS producto, SUM(dv.cantidad) AS total_vendido
    FROM detalle_venta dv
    INNER JOIN productos p ON dv.id_producto = p.id_producto
    INNER JOIN ventas v ON dv.id_venta = v.id_venta
    WHERE MONTH(v.fecha_venta) = MONTH(CURDATE())
      AND YEAR(v.fecha_venta) = YEAR(CURDATE())
    GROUP BY p.id_producto
    ORDER BY total_vendido DESC
    LIMIT 5
";
$top_vendidos = $conn->query($sql_top);

// Productos menos vendidos del mes
$sql_bottom = "
    SELECT p.nombre AS producto, SUM(dv.cantidad) AS total_vendido
    FROM detalle_venta dv
    INNER JOIN productos p ON dv.id_producto = p.id_producto
    INNER JOIN ventas v ON dv.id_venta = v.id_venta
    WHERE MONTH(v.fecha_venta) = MONTH(CURDATE())
      AND YEAR(v.fecha_venta) = YEAR(CURDATE())
    GROUP BY p.id_producto
    ORDER BY total_vendido ASC
    LIMIT 5
";
$menos_vendidos = $conn->query($sql_bottom);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title >Historial de Ventas</title>

     <!-- FAVICON -->
    <link rel="icon" type="image/png" href="../../assets/img/odonto1.png">

    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 0; 
            background: #f5f5f5;
        }
        .navbar {
            width: 220px;                      /* Ancho fijo del menú vertical */
            background-color: #69B7BF;         /* Color corporativo OdontoSmart */
            height: 100vh;                     /* Altura completa de la ventana */
            padding-top: 20px;
            position: fixed;                   /* Se mantiene fijo al hacer scroll */
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            transition: width 0.3s ease;
        }
        .navbar a {
              display: block;
              color: #fff;
            padding: 14px 20px;
            text-decoration: none;
            margin: 10px;
            border-radius: 8px;
            transition: background 0.3s, transform 0.2s;
        }
        .navbar a:hover {
             background-color: #264cbf;
             transform: scale(1.05);
        }
        .content { 
            margin-left: 240px; 
            padding: 20px; 
        }
        .seccion {
            background: linear-gradient(to bottom right, #f5f9fc, #8ef2ffff);
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
            bottom: 40px;   /* ajustar para subirlo o bajarlo */
            left: 50%;
            transform: translateX(-50%);
            width: 140px;   /* tamaño del logo */
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
        <h1 style="color: #69B7BF;">Historial de Ventas</h1>

          <!-- Estadísticas para obtener los datos de las ventas -->
        <h2>Reporte Financiero de Ventas</h2>
          <div class="estadisticas">
            
          <div class="estadistica-card">
                <h3>Total de Ventas Diarias</h3>
                <p style="font-size: 24px; margin: 0;">
                    ₡<?php 
                    $sql_hoy = "SELECT COALESCE(SUM(total), 0) AS total_hoy 
                                FROM ventas 
                                WHERE DATE(fecha_venta) = CURDATE()";
                    $result_hoy = $conn->query($sql_hoy);
                    echo number_format($result_hoy->fetch_assoc()['total_hoy'], 2);
                    ?>
                </p>
            </div>

            <div class="estadistica-card" style="background: #264CBF;">
            <h3>Total de Ventas Semanales</h3>
            <p style="font-size: 24px; margin: 0;">
                ₡<?php 
            //Consulta que obtiene el total de las ventas de la semana que incluya el dia en que se ejecuta el codigo
            $sql_semana = "SELECT COALESCE(SUM(total), 0) AS total_semana
                       FROM ventas 
                       WHERE YEARWEEK(fecha_venta, 1) = YEARWEEK(CURDATE(), 1)";

            $result_semana = $conn->query($sql_semana);
            echo number_format($result_semana->fetch_assoc()['total_semana'], 2);
            ?>
    </p>
</div>

            <div class="estadistica-card" style="background: #92c4e4ff;">
                <h3>Total de Ventas Mensuales</h3>
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

        <!-- Tabla de las ventas generales del mes -->
        <h2>Total de Ventas Mensuales</h2>
        <div class="seccion">
            
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
                           <a class="btn-ver" href="admin_factura.php?id_venta=<?php echo $venta['id_venta']; ?>" target="_blank">
                            Ver Factura
                          </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Tabla de los productos mas y menos vendidos del mes -->
        <h2> Reporte de Productos Vendidos</h2>
        
        <div class="seccion">
        <h3>Productos Más Vendidos del Mes</h3>
    
        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Total Vendido</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $top_vendidos->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['producto']; ?></td>
                    <td><?php echo $row['total_vendido']; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="seccion">
        <h3>Productos Menos Vendidos del Mes</h3>
        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Total Vendido</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $menos_vendidos->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['producto']; ?></td>
                    <td><?php echo $row['total_vendido']; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    </div>

</body>
</html>