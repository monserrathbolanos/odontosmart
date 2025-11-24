<?php
session_start();
require '../../config/conexion.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user']['id_usuario'])) {
    die("Error: Usuario no autenticado.");
}

$id_usuario = $_SESSION['user']['id_usuario'];

// Verificar si el usuario ya existe como cliente, si no, crearlo
$sql_cliente = "SELECT id_cliente FROM clientes WHERE id_cliente = ?";
$stmt = $conn->prepare($sql_cliente);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Obtener datos del usuario
    $sql_user = "SELECT nombre_completo, telefono, email FROM usuarios WHERE id_usuario = ?";
    $stmt2 = $conn->prepare($sql_user);
    $stmt2->bind_param("i", $id_usuario);
    $stmt2->execute();
    $res_user = $stmt2->get_result()->fetch_assoc();
    $stmt2->close();

    // Insertar en clientes
    $sql_insert = "INSERT INTO clientes (id_cliente, nombre, apellido, telefono, correo, fecha_registro)
                   VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt3 = $conn->prepare($sql_insert);
    $apellido = ""; // dejamos vacío por ahora
    $stmt3->bind_param(
        "issss",
        $id_usuario,
        $res_user['nombre_completo'], // nombre
        $apellido,                    // apellido vacío
        $res_user['telefono'],
        $res_user['email']
    );
    if (!$stmt3->execute()) {
        die("Error al insertar cliente: " . $stmt3->error);
    }
    $stmt3->close();
}

$id_cliente = $id_usuario;

// Obtener el carrito del usuario
$sql_carrito = "SELECT id_carrito FROM carrito WHERE id_usuario = ? LIMIT 1";
$stmt = $conn->prepare($sql_carrito);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result_carrito = $stmt->get_result();

if ($result_carrito->num_rows === 0) {
    die("No se encontró carrito para este usuario.");
}

$carrito = $result_carrito->fetch_assoc();
$id_carrito = $carrito['id_carrito'];
$stmt->close();

// Obtener productos del carrito
$sql_detalle = "SELECT cd.id_detalle, cd.cantidad, cd.id_producto, p.precio, p.stock_total
                FROM carrito_detalle cd
                JOIN productos p ON cd.id_producto = p.id_producto
                WHERE cd.id_carrito = ?";

$stmt2 = $conn->prepare($sql_detalle);
$stmt2->bind_param("i", $id_carrito);
$stmt2->execute();
$result_detalle = $stmt2->get_result();

if ($result_detalle->num_rows === 0) {
    echo "<script>
        alert('El carrito está vacío.');
        window.location.href='carrito.php';
    </script>";
    exit;
}

$productos = [];
$subtotal = 0;

while ($row = $result_detalle->fetch_assoc()) {
    // Validar stock disponible
    if ($row['stock_total'] < $row['cantidad']) {
        echo "<script>
            alert('No hay suficiente stock para el producto ID {$row['id_producto']}');
            window.location.href='carrito.php';
        </script>";
        exit; // detenemos la ejecución del script
    }

    $total_producto = $row['precio'] * $row['cantidad'];
    $subtotal += $total_producto;
    $productos[] = [
        "id_producto" => $row['id_producto'],
        "cantidad" => $row['cantidad'],
        "precio_unitario" => $row['precio'],
        "total" => $total_producto
    ];
}
$stmt2->close();

// Calcular impuestos y total
$impuestos = 0; // Agregar el IVA de ser necesario
$total = $subtotal + $impuestos;

// Registrar venta en la tabla ventas
$metodo_pago = "Tarjeta";
$estado = 1; // 1 = Pagado

$sql_venta = "INSERT INTO ventas (id_usuario, id_cliente, fecha_venta, subtotal, impuestos, total, metodo_pago, estado)
              VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)";

$stmt3 = $conn->prepare($sql_venta);
$stmt3->bind_param("iisddsi", $id_usuario, $id_cliente, $subtotal, $impuestos, $total, $metodo_pago, $estado);

if (!$stmt3->execute()) {
    die("Error al registrar la venta: " . $stmt3->error);
}

$id_venta = $stmt3->insert_id;
$stmt3->close();

// Insertar cada producto en detalle_venta y actualizar stock
$sql_detalle_venta = "INSERT INTO detalle_venta (id_venta, id_producto, cantidad, precio_unitario, total)
                      VALUES (?, ?, ?, ?, ?)";
$stmt4 = $conn->prepare($sql_detalle_venta);

$sql_update_stock = "UPDATE productos SET stock_total = stock_total - ? WHERE id_producto = ?";
$stmt_stock = $conn->prepare($sql_update_stock);

foreach ($productos as $p) {
    // Insertar detalle
    $stmt4->bind_param(
        "iiidd",
        $id_venta,
        $p['id_producto'],
        $p['cantidad'],
        $p['precio_unitario'],
        $p['total']
    );
    if (!$stmt4->execute()) {
        die("Error al insertar detalle de venta: " . $stmt4->error);
    }

    // Actualizar stock
    $stmt_stock->bind_param("ii", $p['cantidad'], $p['id_producto']);
    if (!$stmt_stock->execute()) {
        die("Error al actualizar stock: " . $stmt_stock->error);
    }
}
$sql_detalle_venta = "INSERT INTO detalle_venta (id_venta, id_producto, cantidad, precio_unitario, total)
                      VALUES (?, ?, ?, ?, ?)";
$stmt4 = $conn->prepare($sql_detalle_venta);

$sql_update_stock = "UPDATE productos SET stock_total = stock_total - ? WHERE id_producto = ?";
$stmt_stock = $conn->prepare($sql_update_stock);

foreach ($productos as $p) {
    // Insertar detalle
    $stmt4->bind_param(
        "iiidd",
        $id_venta,
        $p['id_producto'],
        $p['cantidad'],
        $p['precio_unitario'],
        $p['total']
    );
    if (!$stmt4->execute()) {
        die("Error al insertar detalle de venta: " . $stmt4->error);
    }

    // Actualizar stock
    $stmt_stock->bind_param("ii", $p['cantidad'], $p['id_producto']);
    if (!$stmt_stock->execute()) {
        die("Error al actualizar stock: " . $stmt_stock->error);
    }
}
$stmt4->close();
$stmt_stock->close();

// Vaciar carrito
$conn->query("DELETE FROM carrito_detalle WHERE id_carrito = $id_carrito");
$conn->query("DELETE FROM carrito WHERE id_carrito = $id_carrito");

//Registtro de la venta en bitácora con SP
$ip_cliente = $_SERVER['REMOTE_ADDR'] ?? 'DESCONOCIDA';

$stmtLog = $conn->prepare("
    CALL sp_ventas_registrar_bitacora(?,?,?, ?, @resultado)
");

if ($stmtLog) {
    // i i d s  -> id_usuario, id_venta, total, ip
    $stmtLog->bind_param(
        "iids",
        $id_usuario,
        $id_venta,
        $total,
        $ip_cliente
    );

    if ($stmtLog->execute()) {
        $stmtLog->close();
        $conn->next_result(); //Limpia resultados del CALL, si no se hace esto puede fallar la siguiente consulta.

        //Lee el valor OUT del SP, para validar si se registro correctamente en la bitácora.
        $res = $conn->query("SELECT @resultado AS res");
        $row = $res->fetch_assoc();
        $resultado_bitacora = $row['res'] ?? null;
        //Validacion del resultado de la bitácora
    } else {
        //Control por si se produce un error al ejecutar el SP
        $stmtLog->close();
    }
}

// Redirigir a factura.php
header("Location: factura.php?id_venta=" . $id_venta);
exit;
?>
