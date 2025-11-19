<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// seguridad: verificar que el usuario tenga rol asignado
if (!isset($_SESSION['rol'])) {
    $_SESSION['rol'] = "administrador"; // temporal para pruebas
}


// Nuevo código servicios.php (Basado en total_inventario.php), trabaja con las tablas Productos, Categoria Productos.
include('../../config/conexion.php');

// Se definen las categorías permitidas para compras de cliente:
$categorias_permitidas = ["Servicios", "Productos de higiene"];

// Función para mostrar tabla por categoría (usada en total_inventario.php)
function mostrarCategoria($conn, $categoriaNombre) {
    echo "<div class='categoria-titulo'> $categoriaNombre</div>";
    $sql = "SELECT   p.id_producto, p.nombre, p.descripcion, p.unidad,
                     p.id_categoria, p.precio, p.costo_unidad, p.stock_total,
                     p.stock_minimo, p.fecha_creacion, p.actualizado_en,
                     p.fecha_caducidad, p.estado, c.nombre AS categoria
            FROM productos p
            JOIN categoria_productos c ON p.id_categoria = c.id_categoria
            WHERE c.nombre = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $categoriaNombre);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Unidad</th>
                        <th>Precio</th>
                        <th>Stock Mínimo</th>
                        <th>Stock Actual</th>
                        <th>Categoría</th>
                        <th>Caducidad</th>
                        <th>Costo</th>
                    </tr>
                </thead>
                <tbody>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['id_producto']}</td>
                    <td><strong>{$row['nombre']}</strong></td>
                    <td>{$row['descripcion']}</td>
                    <td>{$row['unidad']}</td>
                    <td>₡" . number_format($row['precio'], 2) . "</td>
                    <td>{$row['stock_minimo']}</td>
                    <td>{$row['stock_total']}</td>
                    <td>{$row['categoria']}</td>
                    <td>{$row['fecha_caducidad']}</td>
                    <td>₡" . number_format($row['costo_unidad'], 2) . "</td>
                  </tr>";
                  
        }
        echo "</tbody></table>";
    } else {
        echo "<p class='sin-productos'>No hay productos en esta categoría.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Servicios y Productos - OdontoSmart</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 0; 
            background: #f5f5f5;
        }
        .navbar { 
            width: 220px; 
            background-color: #152fbf; 
            height: 100vh; 
            padding-top: 20px; 
            position: fixed; 
        }
        .navbar a { 
            display: block; 
            color: #ecf0f1; 
            padding: 12px; 
            text-decoration: none; 
            margin: 5px 0; 
            border-radius: 4px; 
        }
        .navbar a:hover { 
            background-color: #264cbf; 
        }
        .content { 
            margin-left: 240px; 
            padding: 20px; 
        }
        .seccion {
            background: white;
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
            background: #152fbf; 
            color: white; 
        }
        tr:hover {
            background: #f9f9f9;
        }
        .categoria-titulo {
            background: #e8f4ff;
            padding: 15px;
            margin: 25px 0 10px 0;
            border-radius: 5px;
            font-size: 18px;
            font-weight: bold;
            border-left: 4px solid #152fbf;
        }
        .sin-productos {
            padding: 20px;
            text-align: center;
            color: #6c757d;
            font-style: italic;
        }
        .precio {
            color: #28a745;
            font-weight: bold;
        }
    </style>
</head>
<body>
    
    <div class="navbar">
        <?php include('../../views/navbar.php'); ?>
    </div>

    <div class="content">
        <div class="seccion">
            <h1> Servicios y Productos</h1>
            <p>En esta sección se muestra el catálogo de productos y servicios disponibles en la clínica dental OdontoSmart.</p>

            <?php
            // Mostrar las 2 categorías disponibles
            mostrarCategoria($conn, "Servicios");
            mostrarCategoria($conn, "Productos de higiene");
            ?>
             <a href="pagar.php">
                <button class="btn-pagar"> Ver Carrito</button>
             </a>
        </div>
    </div>
</body>
</html>



