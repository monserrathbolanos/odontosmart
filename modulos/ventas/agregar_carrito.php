<?php
// Iniciar sesión para poder almacenar el carrito

//se relaciona con las tablas Productos.

//No guarda datos en la base de datos, solo en la sesión ($_SESSION['carrito']).
//El carrito se construye en memoria y luego se redirige a pagar.php(revisar si hay que cambiar el carrito a una tabla y no en memoria)

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