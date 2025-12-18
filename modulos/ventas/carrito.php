<?php
// iniciar sesión
session_start();

require '../../config/conexion.php';



/* Validar rol permitido */
$rol = $_SESSION['user']['role'] ?? null;
$rolesPermitidos = ['Administrador','Cliente']; // ej.

if (!in_array($rol, $rolesPermitidos)) {
    // Aquí decides a dónde mandarlo: login, home o protegido.
    // Si quieres mandarlo al login:
    header('Location: ../../auth/iniciar_sesion.php?error=' . urlencode('Debes iniciar sesión o registrarte.'));
    exit;
}
$id_usuario = $_SESSION['user']['id_usuario'];

// mostrar los productos del carrito
function mostrarCarrito($conn, $id_usuario) {
    try {

    echo "<div class='categoria-titulo'> Productos en el carrito</div>";

    // buscar el carrito del usuario
    $sql_carrito = "SELECT id_carrito FROM carrito WHERE id_usuario = ? LIMIT 1";
    $stmt = $conn->prepare($sql_carrito);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result_carrito = $stmt->get_result();

    if ($result_carrito->num_rows === 0) {
        echo "<p class='sin-productos'>No hay productos en el carrito.</p>";
        return;
    }

    $carrito = $result_carrito->fetch_assoc();
    $id_carrito = $carrito['id_carrito'];
    $stmt->close();

    // buscar productos en el carrito con información de promociones
    $sql_detalle = "SELECT 
                    cd.id_detalle,
                        cd.cantidad,
                        p.id_producto,
                        p.nombre,
                        p.descripcion,
                        p.stock_total,
                        p.precio,
                        p.unidad,
                        vp.id_promocion,
                        vp.nombre_promocion,
                        vp.precio_con_descuento,
                        vp.monto_descuento,
                        vp.tipo_descuento,
                        vp.valor_descuento
                    FROM carrito_detalle cd
                    JOIN productos p ON cd.id_producto = p.id_producto
                    LEFT JOIN v_productos_con_promocion vp ON p.id_producto = vp.id_producto
                    WHERE cd.id_carrito = ?";

    $stmt2 = $conn->prepare($sql_detalle);
    $stmt2->bind_param("i", $id_carrito);
    $stmt2->execute();
    $result_detalle = $stmt2->get_result();

    // Recolectar filas para validar stock y poder re-renderizar tras ajustes si es necesario
    $detalles = [];
    while ($r = $result_detalle->fetch_assoc()) {
        $detalles[] = $r;
    }

    // Si no hay filas, regresar
    if (count($detalles) === 0) {
        echo "<p class='sin-productos'>No hay productos en el carrito.</p>";
        return;
    }
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
            $accion = 'CART_DISPLAY_ERROR';
            $modulo = 'modulos/ventas/carrito';
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
            error_log("Fallo al escribir en bitácora (carrito.php): " . $logError->getMessage());
        }
        echo "<p class='sin-productos'>Error al cargar el carrito. Por favor, intente más tarde.</p>";
    }

    // Validar stock real y ajustar cantidades o eliminar items si es necesario
    $stockIssues = [];
    $stmt_update = $conn->prepare("UPDATE carrito_detalle SET cantidad = ? WHERE id_detalle = ?");
    $stmt_delete = $conn->prepare("DELETE FROM carrito_detalle WHERE id_detalle = ?");
    foreach ($detalles as $index => $row) {
        $id_detalle = $row['id_detalle'];
        $cantidad = (int)$row['cantidad'];
        $stock_total = (int)$row['stock_total'];

        if ($stock_total <= 0) {
            // Eliminar el producto del carrito
            $stmt_delete->bind_param("i", $id_detalle);
            $stmt_delete->execute();
            $stockIssues[] = "El producto <strong>{$row['nombre']}</strong> fue eliminado porque no hay stock disponible.";
            // quitar del array para no procesarlo luego
            unset($detalles[$index]);
            continue;
        }
        if ($stock_total < $cantidad) {
            // Ajustar cantidad al stock disponible
            $stmt_update->bind_param("ii", $stock_total, $id_detalle);
            $stmt_update->execute();
            $stockIssues[] = "La cantidad de <strong>{$row['nombre']}</strong> se ajustó a {$stock_total} porque no hay suficiente stock.";
            // actualizar en array
            $detalles[$index]['cantidad'] = $stock_total;
        }
    }

    // Si hubo cambios, se recomienda reconsultar los detalles para reflejar los ajustes con datos frescos
    if (!empty($stockIssues)) {
        // Re-fetch detalles actualizados
        $stmt2->execute();
        $result_detalle = $stmt2->get_result();
        $detalles = [];
        while ($r = $result_detalle->fetch_assoc()) {
            $detalles[] = $r;
        }
    }

    // Si después de los ajustes ya no hay productos
    if (empty($detalles)) {
        echo "<p class='sin-productos'>No hay productos en el carrito.</p>";
        // cerrar statements y retornar
        if (isset($stmt_update) && $stmt_update) $stmt_update->close();
        if (isset($stmt_delete) && $stmt_delete) $stmt_delete->close();
        return;
    }

    // Cerrar statements usados para ajustes
    if (isset($stmt_update) && $stmt_update) $stmt_update->close();
    if (isset($stmt_delete) && $stmt_delete) $stmt_delete->close();

    if ($result_detalle->num_rows === 0) {
        echo "<p class='sin-productos'>No hay productos en el carrito.</p>";
        return;
    }

    // Mostrar alertas de stock si hubo ajustes
    if (!empty($stockIssues)) {
        echo "<div style='background:#fff3cd;color:#856404;padding:12px;border-radius:6px;margin-bottom:15px;font-weight:600;'>";
        foreach ($stockIssues as $si) {
            echo "<div style='margin-bottom:6px;'>" . $si . "</div>";
        }
        echo "</div>";
    }

    echo "<table>
            <thead>
                <tr>
                    <th>ID Producto</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Unidad</th>
                    <th>Cantidad</th>
                    <th>Precio</th>
                    <th>Subtotal</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>";

    $totalGeneral = 0;
    $descuentoTotal = 0;

    foreach ($detalles as $row) {

    // Detectar si tiene promoción
    $tiene_promocion = !empty($row['id_promocion']);
    $precio_unitario = $tiene_promocion ? $row['precio_con_descuento'] : $row['precio'];

    // Calcular subtotal
    $subtotal = $precio_unitario * $row['cantidad'];
    $totalGeneral += $subtotal;

    // Acumular descuento total aplicado
    if ($tiene_promocion) {
        $descuentoTotal += ($row['monto_descuento'] * $row['cantidad']);
    }

    echo "<tr>
            <td>{$row['id_producto']}</td>
            <td><strong>{$row['nombre']}</strong>";

    // Badge de promoción
    if ($tiene_promocion) {
        echo " <span style='background:linear-gradient(135deg,#ff6b6b,#ee5a6f);color:white;padding:2px 6px;border-radius:8px;font-size:10px;font-weight:bold;margin-left:5px;'>¡OFERTA!</span>";
    }

    echo "</td>
            <td>{$row['descripcion']}</td>
            <td>{$row['unidad']}</td>
            <td>{$row['cantidad']}</td>
            <td>";

    // Mostrar precio
    if ($tiene_promocion) {
        // Precio original tachado
        echo "<span style='text-decoration:line-through;color:#999;font-size:12px;'>₡" . number_format($row['precio'], 2) . "</span><br>";

        // Precio con descuento
        echo "<span style='color:#28a745;font-weight:bold;'>₡" . number_format($precio_unitario, 2) . "</span>";

        // Mostrar tipo de descuento
        if ($row['tipo_descuento'] === 'porcentaje') {
            echo " <span style='background:#ffe08a;color:#b88600;padding:2px 4px;border-radius:5px;font-size:10px;'>-{$row['valor_descuento']}%</span>";
        } else {
            echo " <span style='background:#ffe08a;color:#b88600;padding:2px 4px;border-radius:5px;font-size:10px;'>-₡" . number_format($row['valor_descuento'], 0) . "</span>";
        }

    } else {
        // Sin promoción
        echo "₡" . number_format($row['precio'], 2);
    }

    echo "</td>
            <td>₡" . number_format($subtotal, 2) . "</td>

            <td>
                <form action='restar_carrito.php' method='POST' style='display:inline;'>
                   <input type='hidden' name='id_detalle' value='{$row['id_detalle']}'>
                   <input type='hidden' name='id_carrito' value='{$id_carrito}'>

                   <button type='submit' 
                       style='background:#dc3545;color:white;border:none;padding:6px 12px;border-radius:4px;cursor:pointer;font-weight:bold;'>
                       -
                   </button>
                </form>
            </td>
          </tr>";
}

// Mostrar fila del descuento si existen promociones
if ($descuentoTotal > 0) {
    echo "<tr style='background:#fff3cd;'>
            <td colspan='6' style='text-align:right;color:#856404;font-weight:bold;'>Descuento aplicado:</td>
            <td style='color:#28a745;font-weight:bold;'>-₡" . number_format($descuentoTotal, 2) . "</td>
            <td></td>
          </tr>";
}

// TOTAL GENERAL
echo "<tr style='font-weight:bold;background:#eef4ff;'>
        <td colspan='6' style='text-align:right;'>TOTAL:</td>
        <td>₡" . number_format($totalGeneral, 2) . "</td>
        <td></td>
      </tr>";

echo "</tbody></table>";

echo "<div style='text-align:right;margin-top:20px;'>
        <form action='pagar.php' method='GET'>
            <button type='submit'
                style='background:#28a745;color:#fff;padding:12px 28px;border:none;border-radius:5px;font-size:16px;cursor:pointer;font-weight:bold;'>
                Confirmar pago
            </button>
        </form>
      </div>";

$stmt2->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Carrito de compras</title>
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
            width: 220px;
            background-color: #69B7BF;
            height: 100vh;
            padding-top: 20px;
            position: fixed;
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
         /* Logo institucional ubicado en la parte inferior del menú lateral */
        .logo-navbar {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);       /* Centrado horizontal */
            width: 140px;
            opacity: 0.9;
            transition: transform 0.3s;
        }

        .logo-navbar:hover {
            transform: translateX(-50%) scale(1.1); /* Efecto de zoom al pasar el cursor */
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
            background: #f9f9f9;
        }
        .categoria-titulo {
            background: #D5E7F2;
            padding: 15px;
            margin: 25px 0 10px 0;
            border-radius: 5px;
            font-size: 18px;
            font-weight: bold;
            border-left: 4px solid #69B7BF;
        }
        .sin-productos {
            padding: 20px;
            text-align: center;
            color: #6c757d;
            font-style: italic;
        }
    </style>
</head>

<body>

    <div class="navbar">
        <?php include('../../views/navbar.php'); ?>
        <img src="../../assets/img/odonto1.png" class="logo-navbar" alt="Logo OdontoSmart">
    </div>

    <div class="content">
        <div class="seccion">
            <h1 style="color:#69B7BF;">Carrito de compras</h1>
            <p>Aquí se muestran los productos añadidos al carrito por el usuario.</p>

            <?php mostrarCarrito($conn, $id_usuario); ?>

        </div>
    </div>

</body>
</html>
