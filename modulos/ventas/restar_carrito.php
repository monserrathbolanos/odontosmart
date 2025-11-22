<?php
session_start();
require '../../config/conexion.php';

// Verificar que viene el id_detalle
if (!isset($_POST['id_detalle'])) {
    die("ID inválido");
}

$id_detalle = $_POST['id_detalle'];

// Ver si la cantidad es > 1
$sql = "SELECT cantidad FROM carrito_detalle WHERE id_detalle = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_detalle);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Producto no encontrado");
}

$row = $result->fetch_assoc();
$cantidad = $row['cantidad'];
$stmt->close();

// Si cantidad > 1 restar 1
if ($cantidad > 1) {
    $sql_update = "UPDATE carrito_detalle SET cantidad = cantidad - 1 WHERE id_detalle = ?";
    $stmt2 = $conn->prepare($sql_update);
    $stmt2->bind_param("i", $id_detalle);
    $stmt2->execute();
    $stmt2->close();
} else {
    // Si cantidad = 1 eliminar fila
    $sql_delete = "DELETE FROM carrito_detalle WHERE id_detalle = ?";
    $stmt3 = $conn->prepare($sql_delete);
    $stmt3->bind_param("i", $id_detalle);
    $stmt3->execute();
    $stmt3->close();
}

// Redirigir de vuelta al carrito
header("Location: carrito.php");
exit;
