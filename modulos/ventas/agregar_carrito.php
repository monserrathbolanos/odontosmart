<?php 
session_start();

// verificar sesión
if (!isset($_SESSION['user']['id_usuario'])) {
    die("No hay usuario logueado.");
}

include('../../config/conexion.php');

// obtener id_usuario
$id_usuario = $_SESSION['user']['id_usuario'];

// obtener id_producto desde POST
$id_producto = $_POST['id_producto'] ?? null;

if (!$id_producto) {
    die("No se ha enviado ningún producto.");
}

// verificar si el usuario ya tiene un carrito
$sql_carrito = "SELECT id_carrito FROM carrito WHERE id_usuario = ? LIMIT 1";
$stmt = $conn->prepare($sql_carrito);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $id_carrito = $result->fetch_assoc()['id_carrito'];
} else {
    // crear carrito
    $fecha_creacion = date("Y-m-d H:i:s");
    $sql_insert_carrito = "INSERT INTO carrito (id_usuario, fecha_creacion) VALUES (?, ?)";
    $stmt = $conn->prepare($sql_insert_carrito);
    $stmt->bind_param("is", $id_usuario, $fecha_creacion);
    $stmt->execute();
    $id_carrito = $conn->insert_id;
}

// verificar si el producto ya está en el carrito
$sql_check_detalle = "SELECT id_detalle, cantidad FROM carrito_detalle WHERE id_carrito = ? AND id_producto = ?";
$stmt = $conn->prepare($sql_check_detalle);
$stmt->bind_param("ii", $id_carrito, $id_producto);
$stmt->execute();
$result_detalle = $stmt->get_result();



// Cantidad enviada desde el input
$cantidad_agregada = isset($_POST['cantidad']) ? intval($_POST['cantidad']) : 1;

if ($result_detalle->num_rows > 0) {
    // Si ya existe, sumar la cantidad agregada
    $row_detalle = $result_detalle->fetch_assoc();
    $cantidad_nueva = $row_detalle['cantidad'] + $cantidad_agregada;

    $sql_update = "UPDATE carrito_detalle SET cantidad = ? WHERE id_detalle = ?";
    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param("ii", $cantidad_nueva, $row_detalle['id_detalle']);
    $stmt->execute();
} else {
    // Si no existe, insertar el producto con la cantidad enviada
    $sql_insert = "INSERT INTO carrito_detalle (id_carrito, id_producto, cantidad) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql_insert);
    $stmt->bind_param("iii", $id_carrito, $id_producto, $cantidad_agregada);
    $stmt->execute();
}
//  else {
//     // insertar nuevo registro
//     $cantidad = 1;
//     $sql_insert_detalle = "INSERT INTO carrito_detalle (id_carrito, id_producto, cantidad) VALUES (?, ?, ?)";
//     $stmt = $conn->prepare($sql_insert_detalle);
//     $stmt->bind_param("iii", $id_carrito, $id_producto, $cantidad);
//     $stmt->execute();
// }

header("Location: servicios.php?agregado=1");
exit;


?>
