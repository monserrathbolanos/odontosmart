<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']['id_usuario'])) {
    header("Location: ../../index.php");
    exit;
}

// Nuevo código servicios.php (Basado en total_inventario.php), trabaja con las tablas Productos, Categoria Productos.
include('../../config/conexion.php');

// Se definen las categorías permitidas para compras de cliente:
$categorias_permitidas = ["Servicios", "Productos de higiene"];

// Función para mostrar tabla por categoría (usada en total_inventario.php)
function mostrarCategoria($conn, $categoriaNombre) {
    echo "<div class='categoria-titulo'> $categoriaNombre</div>";
        // Consulta que trae los productos junto con su categoría
    $sql = "SELECT   p.id_producto, p.nombre, p.descripcion, p.unidad,
                     p.id_categoria, p.precio, p.costo_unidad, p.stock_total,
                     p.stock_minimo, p.fecha_creacion, p.actualizado_en,
                     p.fecha_caducidad, p.estado, c.nombre AS categoria
            FROM productos p
            JOIN categoria_productos c ON p.id_categoria = c.id_categoria
            WHERE c.nombre = ?";

   // Prepara la consulta para usar parámetros seguros
    $stmt = $conn->prepare($sql);

    // Asigna la categoría enviada a la consulta
    $stmt->bind_param("s", $categoriaNombre);

    // Ejecuta la consulta
    $stmt->execute();

    // Obtiene los resultados
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
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>";

        // Recorre cada producto y lo imprime en una fila
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

                        <td>
                         <!-- formulario para agregar el producto al carrito -->
                         <form action='agregar_carrito.php' method='POST'>
                            <!-- se envía el id del producto -->
                            <input type='hidden' name='id_producto' value='" . $row['id_producto'] . "'>
                            <!-- botón para agregar -->
                            <button type='submit' class='agregar-btn'>Agregar al Carrito</button>
                         </form>
                         </td>
                    </tr>";
        }
        echo "</tbody></table>";
    } else {
        // Si no hay productos, muestra este texto
        echo "<p class='sin-productos'>No hay productos en esta categoría.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Servicios y Productos - OdontoSmart</title>
<!-- Estilos de la página (solo diseño visual) -->
   <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 0; 
            background: #f5f5f5;
        }
        .navbar { 
            width: 220px; 
            background-color: #69B7BF; 
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
            background: #69B7BF; 
            color: white; 
        }
        tr:hover {
            background: #f9f9f9;
        }
        .categoria-titulo {
            background: #D5E7F2;
            padding: 15px;
            margin: 25px 0 10px 0;
            border-radius: 5px;
            font-size: 18px;
            font-weight: bold;
            border-left: 4px solid #69B7BF;
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
        .logo-navbar {
            position: absolute;
            bottom: 80px;        /* distancia desde abajo (ajustable) */
            left: 50%;
            transform: translateX(-50%);
            width: 140px;        /* tamaño del logo */
            opacity: 0.9;
        }
        .agregar-btn {
            background-color: #152fbf;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }
        .agregar-btn:hover {
            background-color: #0d1e80;
        }

        .btn-pagar {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            margin-top: 20px;
            display: inline-block;
            transition: background 0.3s;
        }
        .btn-pagar:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    
    <div class="navbar">
    <!-- Logo inferior del menú -->
    <?php include('../../views/navbar.php'); ?>
    <img src="../../assets/img/odonto1.png" class="logo-navbar" alt="Logo OdontoSmart">
</div>


    <div class="content">
        <div class="seccion">
            <h1 style="color: #69B7BF;"> Servicios y Productos</h1>
            <p>En esta sección se muestra el catálogo de productos y servicios disponibles en la clínica dental OdontoSmart.</p>

            <?php
            if (isset($_GET['agregado'])) {
            echo "<p style='color:green; font-weight:bold;'>Producto agregado correctamente.</p>";
            }

            // Mostrar las 2 categorías disponibles
            mostrarCategoria($conn, "Servicios");
            mostrarCategoria($conn, "Productos de higiene");
            ?>
            
            <!-- Botón para ver el carrito -->
            <a href="carrito.php">
                <button class="btn-pagar"> Ver Carrito</button>
            </a>
        </div>
    </div>
</body>
</html>



