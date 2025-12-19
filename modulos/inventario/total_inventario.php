<?php

// Muestra el inventario total usando las tablas Productos y Categoria Productos
session_start();
include('../../config/conexion.php');

// Verifica que el usuario tenga un rol permitido
$rol = $_SESSION['user']['role'] ?? null;
$rolesPermitidos = ['Administrador', 'Médico','Recepcionista']; // ej.

if (!in_array($rol, $rolesPermitidos)) {
    header('Location: ../../auth/iniciar_sesion.php?error=' . urlencode('Debes iniciar sesión o registrarte.'));
    exit;
}

// Muestra la tabla de productos por categoría
function mostrarCategoria($conn, $categoriaNombre) {
    echo "<div class='categoria-titulo'> $categoriaNombre</div>";
    $sql = "SELECT   p.id_producto, p.nombre, p.descripcion, p.unidad,
                     p.id_categoria, p.precio, p.costo_unidad, p.stock_total,
                     p.stock_minimo, p.fecha_creacion, p.actualizado_en,
                     p.fecha_caducidad, p.estado, c.nombre AS categoria
            FROM productos p
            JOIN categoria_productos c ON p.id_categoria = c.id_categoria
            WHERE c.nombre = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $categoriaNombre);
    $stmt->execute();
    $result = $stmt->get_result();

    // Crear arreglo para alertas
    $productosAgotados = [];

    if ($result->num_rows > 0) {
        echo "<table>
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
                        <th>Costo</th>
                    </tr>
                </thead>
                <tbody>";
        while ($row = $result->fetch_assoc()) {
            // Valida el stock de cada producto
            if ($row['stock_total'] <= $row['stock_minimo']) {
                $productosAgotados[] = $row['nombre'];
            }

            echo "<tr>
                    <td>{$row['id_producto']}</td>
                    <td><strong>{$row['nombre']}</strong></td>
                    <td>{$row['descripcion']}</td>
                    <td>{$row['unidad']}</td>
                    <td>₡" . number_format($row['precio'], 2) . "</td>
                    <td>{$row['stock_minimo']}</td>
                    <td>{$row['stock_total']}</td>
                    <td>{$row['categoria']}</td>
                    <td>₡" . number_format($row['costo_unidad'], 2) . "</td>
                  </tr>";
        }
        echo "</tbody></table>";

        // Mostrar alerta si existen productos con bajo stock
        if (!empty($productosAgotados)) {
            echo "<p style='color:red; font-weight:bold;'>
                    Productos casi agotados o sin stock:
                  </p>";
            echo "<ul>";
            foreach ($productosAgotados as $p) {
                echo "<li>$p</li>";
            }
            echo "</ul>";
        }
    } else {
        echo "<p class='sin-productos'>No hay productos en esta categoría.</p>";
    }
}
$inventario_error = false;

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario General</title>
    <!-- FAVICON -->
    <link rel="icon" type="image/png" href="../../assets/img/odonto1.png">

    <!-- ESTILOS CSS -->
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/total_inventario.css">
</head>
<body>
    <div class="sidebar">
    <?php include('../../views/sidebar.php'); ?>

    <!-- Logo inferior del menú -->
    <img src="../../assets/img/odonto1.png" class="logo-sidebar" alt="Logo OdontoSmart">
</div>

    <div class="content">
        <div class="seccion">
            <h1 style="color: #69B7BF;"> Inventario General</h1>
            <p>En esta sección se muestra el inventario completo de productos disponibles en la clínica dental OdontoSmart. Aquí podrás ver detalles como el nombre del producto, descripción, unidad, precio, stock mínimo, categoría, fecha de caducidad y costo por unidad.</p>

            <?php
            if (isset($inventario_error) && $inventario_error) {
                echo "<p style='color:red; font-weight:bold;'>Error al cargar el inventario. Por favor, intente más tarde.</p>";
            }
            ?>
            <?php
            // Categorías a mostrar
            try {
            // Mostrar las 5 categorías
            mostrarCategoria($conn, "Medicamentos");
            mostrarCategoria($conn, "Servicios");
            mostrarCategoria($conn, "Equipo médico complejo");
            mostrarCategoria($conn, "Instrumento dental");
            mostrarCategoria($conn, "Productos de higiene");
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
                $accion = 'INVENTORY_QUERY_ERROR';
                $modulo = 'modulos/inventario/total_inventario';
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
                error_log("Fallo al escribir en bitácora (total_inventario.php): " . $logError->getMessage());
            }
            $inventario_error = true;
            }
            ?>
        </div>
    </div>
</body>
</html>
