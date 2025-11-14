//CODIGO INVENTARIO.PHP

<?php
// Conexion a la case de datos
$host = "localhost";
$user = "root"; 
$password = ""; 
$dbname = "odontosmart_db";

//Para crear la conexion
$conn = new mysqli($host, $user, $password, $dbname);

// Para verificar la conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$mensaje = "";

// Formulario para agregar un producto
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST["nombre"];
    $descripcion = $_POST["descripcion"];
    $unidad = $_POST["unidad"];
    $precio = $_POST["precio"];
    $stock_minimo = $_POST["stock_minimo"];
    $id_categoria = $_POST["id_categoria"];
    $id_lote = $_POST["id_lote"];
    $fecha_caducidad = $_POST["fecha_caducidad"];
    $costo_unidad = $_POST["costo_unidad"];

    $sql = "INSERT INTO productos 
            (id_categoria, nombre, descripcion, unidad, precio, stock_minimo, activo, id_lote, fecha_caducidad, costo_unidad) 
            VALUES (?, ?, ?, ?, ?, ?, 'si', ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssdii sd", $id_categoria, $nombre, $descripcion, $unidad, $precio, $stock_minimo, $id_lote, $fecha_caducidad, $costo_unidad);

    if ($stmt->execute()) {
        $mensaje = "El producto si fue agregado correctamente.";
    } else {
        $mensaje = "El producto no fue agregado correctamente." . $conn->error;
    }
}


// Estas son las diferentes categorias que se pueden seleccionar
$categorias = $conn->query("SELECT id_categoria, nombre FROM categoria_productos");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario - Productos</title>
</head>
<body>
    <h1>Agregar Inventario</h1>
    <form method="POST" action="">
        <label>Nombre del producto:</label><br>
        <input type="text" name="nombre" required><br><br>

        <label>Descripción:</label><br>
        <input type="text" name="descripcion"><br><br>

        <label>Unidad:</label><br>
        <input type="text" name="unidad" placeholder="ejemplo: caja, litro, paquete"><br><br>

        <label>Precio:</label><br>
        <input type="number" step="0.01" name="precio" required><br><br>

        <label>Stock mínimo:</label><br>
        <input type="number" name="stock_minimo" required><br><br>

        <label>Categoría:</label><br>
        <select name="id_categoria" required>
            <option value="">Seleccione una categoría</option>
            <?php while($cat = $categorias->fetch_assoc()) {
                echo "<option value='".$cat["id_categoria"]."'>".$cat["nombre"]."</option>";
            } ?>
        </select><br><br>

        <label>ID Lote:</label><br>
        <input type="number" name="id_lote" required><br><br>

        <label>Fecha de caducidad:</label><br>
        <input type="date" name="fecha_caducidad" required><br><br>

        <label>Costo por unidad:</label><br>
        <input type="number" step="0.01" name="costo_unidad" required><br><br>

        <button type="submit">Guardar Producto</button>
    </form>

    <?php
    if (!empty($mensaje)) {
        echo "<p><strong>$mensaje</strong></p>";
    }

    ?>
</body>

</html>