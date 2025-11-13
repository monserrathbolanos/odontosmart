<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario</title>
</head>
<body>
    <h1>Inventario General</h1>
    <p>
        En la siguiente seccion se muestra el inventario completo de productos disponibles en la clínica dental OdontoSmart. Aquí podrás ver detalles como el nombre del producto, descripción, unidad, precio, stock mínimo, categoría, ID de lote, fecha de caducidad y costo por unidad.
    </p>
</body>
</html>
<?php

// Conexión a la base de datos
$conn = new mysqli("localhost", "root", "", "odontosmart_db");
if ($conn->connect_error) { die("Error de conexión: " . $conn->connect_error); }

// Función para mostrar tabla por categoría
function mostrarCategoria($conn, $categoriaNombre) {
    echo "<h2>$categoriaNombre</h2>";
    $sql = "SELECT p.id_producto, p.nombre, p.descripcion, p.unidad, p.precio, p.stock_minimo, 
                   c.nombre AS categoria, p.id_lote, p.fecha_caducidad, p.costo_unidad
            FROM productos p
            JOIN categoria_productos c ON p.id_categoria = c.id_categoria
            WHERE c.nombre = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $categoriaNombre);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>
                <tr>
                    <th>ID Producto</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Unidad</th>
                    <th>Precio</th>
                    <th>Stock Mínimo</th>
                    <th>Categoría</th>
                    <th>ID Lote</th>
                    <th>Fecha de Caducidad</th>
                    <th>Costo por Unidad</th>
                </tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['id_producto']}</td>
                    <td>{$row['nombre']}</td>
                    <td>{$row['descripcion']}</td>
                    <td>{$row['unidad']}</td>
                    <td>{$row['precio']}</td>
                    <td>{$row['stock_minimo']}</td>
                    <td>{$row['categoria']}</td>
                    <td>{$row['id_lote']}</td>
                    <td>{$row['fecha_caducidad']}</td>
                    <td>{$row['costo_unidad']}</td>
                  </tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No hay productos aun.</p>";
    }
}

// Mostrar las 4 secciones
mostrarCategoria($conn, "Medicamentos");
mostrarCategoria($conn, "Servicios");
mostrarCategoria($conn, "Equipo Medico Complejo");
mostrarCategoria($conn, "Instrumento Dental");

$conn->close();
?>
</body>
</html>
