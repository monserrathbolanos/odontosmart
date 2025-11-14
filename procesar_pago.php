<?php
// procesar_pago.php 
session_start();
include('conexion.php');

// Verificar que hay productos en el carrito
if (!isset($_SESSION['carrito']) || empty($_SESSION['carrito'])) {
    $_SESSION['error'] = "El carrito está vacío";
    header("Location: pagar.php");
    exit();
}

$carrito = $_SESSION['carrito'];

// Calcular totales
$subtotal = 0;
foreach ($carrito as $item) {
    $subtotal += $item['precio'] * $item['cantidad'];
}
$iva_monto = $subtotal * 0.13;
$total = $subtotal + $iva_monto;

// Iniciar transacción
$conn->begin_transaction();

try {
    // 1. Obtener un usuario válido
    $sql_usuario = "SELECT id_usuario FROM usuarios WHERE estado = 'activo' LIMIT 1";
    $result_usuario = $conn->query($sql_usuario);
    
    if ($result_usuario->num_rows === 0) {
        throw new Exception("No hay usuarios activos en el sistema.");
    }
    
    $usuario_data = $result_usuario->fetch_assoc();
    $id_usuario_valido = $usuario_data['id_usuario'];

    // 2. Insertar venta
    $numero_factura = "FAC-" . date('Ymd-His');
    $id_cliente = 1;
    $id_vendedor = 1;
    $metodo_pago = "efectivo";
    $fecha_actual = date('Y-m-d');

    $sql_venta = "INSERT INTO ventas 
                 (numero_factura, fecha, id_cliente, id_vendedor, subtotal, iva_monto, total, metodo_pago, id_usuario) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_venta = $conn->prepare($sql_venta);
    $stmt_venta->bind_param("ssiidddsi", $numero_factura, $fecha_actual, $id_cliente, $id_vendedor, $subtotal, $iva_monto, $total, $metodo_pago, $id_usuario_valido);
    
    if (!$stmt_venta->execute()) {
        throw new Exception("Error al insertar venta: " . $stmt_venta->error);
    }
    
    $id_venta = $conn->insert_id;

    // 3. Insertar detalles
    foreach ($carrito as $item) {
        $precio_unitario = $item['precio'];
        $cantidad = $item['cantidad'];
        $subtotal_item = $precio_unitario * $cantidad;
        $iva_item = $subtotal_item * 0.13;
        $total_item = $subtotal_item + $iva_item;
        
        $sql_detalle = "INSERT INTO detalle_venta 
                       (id_venta, id_producto, cantidad, iva_monto, total_definitivo) 
                       VALUES (?, ?, ?, ?, ?)";
        $stmt_detalle = $conn->prepare($sql_detalle);
        $stmt_detalle->bind_param("iiidd", $id_venta, $item['id_producto'], $cantidad, $iva_item, $total_item);
        
        if (!$stmt_detalle->execute()) {
            throw new Exception("Error al insertar detalle: " . $stmt_detalle->error);
        }
    }

    // 4. Confirmar transacción
    $conn->commit();

    // 5. Limpiar y redirigir
    $_SESSION['ultima_venta'] = $id_venta;
    $_SESSION['success'] = "✅ Venta procesada exitosamente. Factura: $numero_factura";
    unset($_SESSION['carrito']);

    header("Location: factura.php?id_venta=" . $id_venta);
    exit();

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = "Error al procesar el pago: " . $e->getMessage();
    header("Location: pagar.php");
    exit();
}
?>