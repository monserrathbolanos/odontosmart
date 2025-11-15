<?php
// Iniciar sesión para poder almacenar el carrito
session_start();

include('../../config/conexion.php');

// Validar que venga el producto y cantidad
if (!isset($_POST['id_producto']) || !isset($_POST['cantidad'])) {
    echo "Error: No se seleccionó ningún producto.";
    exit();
}

// Guardar datos recibidos
$id_producto = $_POST['id_producto'];
$cantidad = $_POST['cantidad'];

// Obtener información del producto seleccionado
$sql = "SELECT * FROM productos WHERE id_producto = $id_producto";
$producto = $conn->query($sql)->fetch_assoc();

// Si no existe el carrito, lo creamos
// El carrito se guarda en la sesión
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Agregar producto al carrito (NO a la BD, solo sesión)
$_SESSION['carrito'][] = [
    'id_producto' => $producto['id_producto'],
    'nombre' => $producto['nombre'],
    'precio' => $producto['precio'],
    'id_lote' => $producto['id_lote'],
    'cantidad' => $cantidad
];

// Redirigir a pagar.php
header("Location: pagar.php");
exit();
?>
