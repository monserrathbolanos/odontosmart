<?php
session_start();
require '../../config/conexion.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user']['id_usuario'])) {
    // Error fatal si no hay usuario, redirigir
    header("Location: ../../auth/login.php");
    exit;
}

$id_usuario = $_SESSION['user']['id_usuario'];

// Verificar si el usuario ya existe como cliente, si no, crearlo
$sql_cliente = "SELECT id_cliente FROM clientes WHERE id_usuario = ?";

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare($sql_cliente);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {

    // *** NUEVA VERSIÓN ***
    // La tabla clientes ahora solo tiene: id_cliente (AI), id_usuario, fecha_registro
    $sql_insert = "INSERT INTO clientes (id_usuario, fecha_registro)
                   VALUES (?, NOW())";
    $stmt3 = $conn->prepare($sql_insert);
    $stmt3->bind_param("i", $id_usuario);

    if (!$stmt3->execute()) {
    if (!$stmt3->execute()) {
        throw new Exception("Error al insertar cliente: " . $stmt3->error);
    }
    }

    // id_cliente que acaba de crearse
    $id_cliente = $stmt3->insert_id;

    $stmt3->close();

} else {

    // Cliente ya existe → sacar id_cliente
    $rowCliente = $result->fetch_assoc();
    $id_cliente = $rowCliente['id_cliente'];
}

$stmt->close();


// Obtener el carrito del usuario
$sql_carrito = "SELECT id_carrito FROM carrito WHERE id_usuario = ? LIMIT 1";
$stmt = $conn->prepare($sql_carrito);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result_carrito = $stmt->get_result();

if ($result_carrito->num_rows === 0) {
if ($result_carrito->num_rows === 0) {
    throw new Exception("No se encontró carrito para este usuario.");
}
}

$carrito = $result_carrito->fetch_assoc();
$id_carrito = $carrito['id_carrito'];
$stmt->close();


// Obtener productos del carrito
$sql_detalle = "SELECT cd.id_detalle, cd.cantidad, cd.id_producto, p.precio, p.stock_total,
                       vp.id_promocion, vp.precio_con_descuento, vp.monto_descuento
                FROM carrito_detalle cd
                JOIN productos p ON cd.id_producto = p.id_producto
                LEFT JOIN v_productos_con_promocion vp ON p.id_producto = vp.id_producto
                WHERE cd.id_carrito = ?";

$stmt2 = $conn->prepare($sql_detalle);
$stmt2->bind_param("i", $id_carrito);
$stmt2->execute();
$result_detalle = $stmt2->get_result();

// Validación de carrito
if ($result_detalle->num_rows === 0) {
if ($result_detalle->num_rows === 0) {
    throw new Exception("El carrito está vacío. No se puede procesar la venta.");
}
}

$productos = [];
$subtotal = 0;
$descuento_total = 0;
$promociones_aplicadas = []; // Para rastrear qué promociones se aplicaron

while ($row = $result_detalle->fetch_assoc()) {

    // Validar stock disponible
    if ($row['stock_total'] < $row['cantidad']) {
    if ($row['stock_total'] < $row['cantidad']) {
        throw new Exception("No hay suficiente stock para el producto ID {$row['id_producto']}.");
    }
    }

    // Determinar el precio a usar (con o sin promoción)
    $precio_unitario = $row['precio']; // Precio original
    $tiene_promocion = !empty($row['id_promocion']);

    if ($tiene_promocion) {
        $precio_unitario = $row['precio_con_descuento']; // Precio con descuento
        $descuento_producto = $row['monto_descuento'] * $row['cantidad'];
        $descuento_total += $descuento_producto;
        
        // Registrar la promoción aplicada
        if (!isset($promociones_aplicadas[$row['id_promocion']])) {
            $promociones_aplicadas[$row['id_promocion']] = 0;
        }
        $promociones_aplicadas[$row['id_promocion']] += $descuento_producto;
    }

    $total_producto = $precio_unitario * $row['cantidad'];
    $subtotal += $total_producto;

    $productos[] = [
        "id_producto" => $row['id_producto'],
        "cantidad" => $row['cantidad'],
        "precio_unitario" => $precio_unitario, // ya tiene el descuento si aplica
        "total" => $total_producto,
        "descuento" => $tiene_promocion ? $descuento_producto : 0
    ];
}

$stmt2->close();


// Calcular subtotal sumando los totales de los productos (ya con descuento aplicado)
$subtotal = 0;
foreach ($productos as $p) {
    $subtotal += $p['total'];
}

// Calcular impuestos (IVA 13%)
$impuestos = $subtotal * 0.13;

// Total final
$total = $subtotal + $impuestos;


// Registrar venta
$metodo_pago = "Tarjeta";
$estado = 1;

$sql_venta = "INSERT INTO ventas (id_usuario, id_cliente, fecha_venta, subtotal, impuestos, total, metodo_pago, estado)
              VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)";

$stmt3 = $conn->prepare($sql_venta);
$stmt3->bind_param("iidddsi", $id_usuario, $id_cliente, $subtotal, $impuestos, $total, $metodo_pago, $estado);

if (!$stmt3->execute()) {
if (!$stmt3->execute()) {
    throw new Exception("Error al registrar la venta: " . $stmt3->error);
}
}

$id_venta = $stmt3->insert_id;


// Registro de pago (TARJETA)

// Datos enviados desde pagar.php
$nombre_titular = $_POST['nombre'] ?? null;
$numero_tarjeta = $_POST['tarjeta'] ?? null;
$vencimiento = $_POST['vencimiento'] ?? null;

if (!$nombre_titular || !$numero_tarjeta || !$vencimiento) {
    echo "<script>alert('Error: Datos de tarjeta incompletos.'); window.history.back();</script>";
    exit;
}

// Normalizar formato YYYY-MM desde el input type="month"
$vencimiento = trim($vencimiento);

// Validar formato YYYY-MM real del input
if (!preg_match("/^\d{4}-(0[1-9]|1[0-2])$/", $vencimiento)) {
    echo "<script>
        Swal.fire({
            icon: 'error',
            title: 'Formato inválido',
            text: 'Seleccione una fecha válida de vencimiento.',
            confirmButtonColor: '#3085d6'
        }).then(() => { window.history.back(); });
    </script>";
    exit;
}

// Separar año y mes
list($anio, $mes) = explode("-", $vencimiento);

// Crear fecha de vencimiento (último día del mes)
$fecha_vencimiento = DateTime::createFromFormat("Y-m-d", "$anio-$mes-01");
$fecha_vencimiento->modify("last day of this month");
$fecha_vencimiento->setTime(23,59,59);

// Fecha actual
$hoy = new DateTime("today");
$hoy->setTime(0,0,0);

// Validación final
if ($fecha_vencimiento < $hoy) {

    echo "
<!DOCTYPE html>
<html lang='es'>
<head>
<meta charset='UTF-8'>
<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
</head>
<body>

<script>
Swal.fire({
    icon: 'error',
    title: 'Tarjeta vencida',
    text: 'Por favor use una tarjeta válida.',
    confirmButtonColor: '#d33'
}).then(() => {
    window.history.back();
});
</script>

</body>
</html>";

    exit;
}

// Solo guardar los últimos 4 dígitos
$tarjeta_4 = substr($numero_tarjeta, -4);

// Inserción del pago
$sql_pago = "INSERT INTO pagos (id_venta, monto, fecha_pago, metodo, digitos_tarjeta, vencimiento)
             VALUES (?, ?, NOW(), 'Tarjeta', ?, ?)";

$stmtPago = $conn->prepare($sql_pago);
$stmtPago->bind_param("idss", $id_venta, $total, $tarjeta_4, $vencimiento);

if (!$stmtPago->execute()) {
if (!$stmtPago->execute()) {
    throw new Exception("Error al registrar el pago: " . $stmtPago->error);
}
}

$stmt3->close();


// Insertar detalle de venta + actualizar stock
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
    $stmt4->execute();

    // Actualizar stock
    $stmt_stock->bind_param("ii", $p['cantidad'], $p['id_producto']);
    $stmt_stock->execute();

    // Actualizar cantidad en lote_producto
    $sql_update_lote = "UPDATE lote_producto 
                        SET cantidad = cantidad - ? 
                        WHERE id_producto = ?";
    $stmt_lote = $conn->prepare($sql_update_lote);
    $stmt_lote->bind_param("ii", $p['cantidad'], $p['id_producto']);
    $stmt_lote->execute();
    $stmt_lote->close();
}

$stmt4->close();
$stmt_stock->close();

// Registrar las promociones aplicadas en la tabla ventas_promociones
if (!empty($promociones_aplicadas)) {
    $sql_promo = "INSERT INTO ventas_promociones (id_venta, id_promocion, descuento_aplicado) VALUES (?, ?, ?)";
    $stmt_promo = $conn->prepare($sql_promo);
    
    foreach ($promociones_aplicadas as $id_promocion => $descuento_aplicado) {
        $stmt_promo->bind_param("iid", $id_venta, $id_promocion, $descuento_aplicado);
        $stmt_promo->execute();
    }
    
    $stmt_promo->close();
}

// Vaciar carrito
$conn->query("DELETE FROM carrito_detalle WHERE id_carrito = $id_carrito");
$conn->query("DELETE FROM carrito WHERE id_carrito = $id_carrito");

// Registro en bitácora con SP
$ip_cliente = $_SERVER['REMOTE_ADDR'] ?? 'DESCONOCIDA';

$stmtLog = $conn->prepare("
    CALL sp_ventas_registrar_bitacora(?,?,?, ?, @resultado)
");

if ($stmtLog) {
    $stmtLog->bind_param("iids", $id_usuario, $id_venta, $total, $ip_cliente);

    if ($stmtLog->execute()) {
        $stmtLog->close();
        $conn->next_result();

        $res = $conn->query("SELECT @resultado AS res");
        $row = $res->fetch_assoc();
        $resultado_bitacora = $row['res'] ?? null;

    } else {
        $stmtLog->close();
    }
}

// Redirigir a factura
    $conn->commit();
    header("Location: factura.php?id_venta=" . $id_venta);
    exit;

} catch (Throwable $t) {
    // Revertir transacción
    if (isset($conn) && $conn instanceof mysqli) { 
        try { $conn->rollback(); } catch (Throwable $e) {}
    }

    // LOGGING CON NUEVO ESTÁNDAR Y CONEXIÓN LIMPIA
    try {
        if (isset($conn)) { @$conn->close(); }
        include '../../config/conexion.php'; 

        $id_usuario_log = $_SESSION['user']['id_usuario'] ?? null;
        $accion         = "VENTA_FALLIDA";
        $modulo         = "ventas/procesar_pago";
        $ip             = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $user_agent     = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
        $detalles       = "Error: " . $t->getMessage();

        $stmtLog = $conn->prepare("CALL SP_USUARIO_BITACORA(?, ?, ?, ?, ?, ?)");
        if ($stmtLog) {
            $stmtLog->bind_param("isssss", $id_usuario_log, $accion, $modulo, $ip, $user_agent, $detalles);
            $stmtLog->execute();
            $stmtLog->close();
        }
    } catch (Throwable $logError) {
        error_log("Fallo crítico en logging: " . $logError->getMessage());
    }

    // Redirigir con mensaje de error
    $msg = "Error al procesar el pago: " . $t->getMessage();
    header("Location: pagar.php?error=" . urlencode($msg));
    exit;
}
?>
