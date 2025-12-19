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

try {
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
} catch (Throwable $e) {
    try {
        if (isset($conn) && $conn instanceof mysqli) {
            if (isset($conn) && $conn instanceof mysqli && method_exists($conn, 'in_transaction') && $conn->in_transaction()) {
                try { $conn->rollback(); } catch (Throwable $__ignore) {}
            }
        }
    } catch (Throwable $__ignored) {}

    try {
        if (isset($conn)) { @$conn->close(); }
        include_once ('../../config/conexion.php');

        $id_usuario_log = $_SESSION['user']['id_usuario'] ?? null;
        $accion = 'SALES_HISTORY_ERROR';
        $modulo = 'modulos/ventas/historial_ventas';
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
        error_log("Fallo al escribir en bitácora (historial_ventas.php): " . $logError->getMessage());
    }
    // Show error message later in HTML
    $historial_error = true;
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title >Historial de Ventas</title>

     <!-- FAVICON -->
    <link rel="icon" type="image/png" href="../../assets/img/odonto1.png">

    <!-- Estilos -->
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/historial_ventas.css">
    
</head>
<body>
    <div class="sidebar">
    <!-- Logo inferior del menú -->
    <?php include('../../views/sidebar.php'); ?>
    <img src="../../assets/img/odonto1.png" class="logo-sidebar" alt="Logo OdontoSmart">
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

        <!-- Tabla de los productos más y menos vendidos del mes -->
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