<?php
// inventario.php // Trabaja con las tablas categoria_productos y productos
session_start();
include('../../config/conexion.php');
$rol = $_SESSION['user']['role'] ?? 'administrador';

// Formulario para agregar un producto
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre = $_POST["nombre"];
    $descripcion = $_POST["descripcion"];
    $unidad = $_POST["unidad"];
    $precio = $_POST["precio"];
    $stock_minimo = $_POST["stock_minimo"];
    $stock_total = $_POST["stock_total"];
    $id_categoria = $_POST["id_categoria"];
    $fecha_caducidad = $_POST["fecha_caducidad"];
    $costo_unidad = $_POST["costo_unidad"];

    // Estructura REAL de productos
    $sql = "INSERT INTO productos 
            (id_categoria, nombre, descripcion, unidad, precio, costo_unidad, 
             stock_total, stock_minimo, fecha_caducidad, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo')";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "isssddiis", 
        $id_categoria,
        $nombre,
        $descripcion,
        $unidad,
        $precio,
        $costo_unidad,
        $stock_total,
        $stock_minimo,
        $fecha_caducidad
    );

    if ($stmt->execute()) {
        $mensaje = " El producto fue agregado correctamente.";
    } else {
        $mensaje = " Error al agregar el producto: " . $conn->error;
    }
}

// Obtener categorías
$categorias = $conn->query("SELECT id_categoria, nombre FROM categoria_productos");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>OdontoSmart - Control de Inventario</title>
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
            background-color: #69B7BF; 
        }
        .content { 
            margin-left: 240px; 
            padding: 20px; 
        }
        .seccion {
            background: white;
            padding: 30px;
            margin: 15px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-group {
            margin: 20px 0;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        input, select {
            padding: 12px;
            margin: 5px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 100%;
            max-width: 400px;
            font-size: 14px;
        }
        input:focus, select:focus {
            border-color: #69B7BF;
            outline: none;
            box-shadow: 0 0 5px rgba(105, 183, 191, 0.3);
        }
        button {
            padding: 12px 25px;
            background: #152fbf;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 0;
        }
        button:hover {
            background: #264cbf;
        }
        .mensaje {
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .exito {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .form-container {
            max-width: 500px;
        }
        .required::after {
            content: " *";
            color: #dc3545;
        }
        .form-hint {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
        .logo-navbar {
            position: absolute;
            bottom: 80px;        /* Ajustá lo alto o bajo que querás */
            left: 50%;
            transform: translateX(-50%);
            width: 140px;        /* Tamaño del logo */
            opacity: 0.9;
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
            <h1 style="color: #69B7BF;">Agregar Producto al Inventario</h1>
            <p>Complete el formulario para agregar un nuevo producto al sistema.</p>

            <?php if (!empty($mensaje)): ?>
                <div class="mensaje <?php echo strpos($mensaje, 'correctamente') !== false ? 'exito' : 'error'; ?>">
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="required">Nombre del producto:</label>
                        <input type="text" name="nombre" placeholder="Ej: Anestesia Lidocaína 2%" required>
                    </div>

                    <div class="form-group">
                        <label>Descripción:</label>
                        <input type="text" name="descripcion" placeholder="Descripción detallada del producto">
                        <div class="form-hint">Opcional - describe las características del producto</div>
                    </div>

                    <div class="form-group">
                        <label class="required">Unidad de medida:</label>
                        <input type="text" name="unidad" placeholder="Ej: caja, litro, paquete, unidad" required>
                        <div class="form-hint">Especifique cómo se mide el producto</div>
                    </div>

                    <div class="form-group">
                        <label class="required">Precio de venta:</label>
                        <input type="number" step="0.01" name="precio" placeholder="0.00" min="0" required>
                        <div class="form-hint">Precio en colones (₡) - Incluir decimales</div>
                    </div>

                    <div class="form-group">
                        <label class="required">Stock total:</label>
                        <input type="number" name="stock_total" placeholder="0" min="0" required>
                    </div>

                    <div class="form-group">
                        <label class="required">Stock mínimo:</label>
                        <input type="number" name="stock_minimo" placeholder="0" min="0" required>
                        <div class="form-hint">Cantidad mínima antes de alertar por bajo stock</div>
                    </div>

                    <div class="form-group">
                        <label class="required">Categoría:</label>
                        <select name="id_categoria" required>
                            <option value="">Seleccione una categoría</option>
                            <?php while($cat = $categorias->fetch_assoc()): ?>
                                <option value="<?php echo $cat["id_categoria"]; ?>">
                                    <?php echo $cat["nombre"]; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="required">Fecha de caducidad:</label>
                        <input type="date" name="fecha_caducidad" required>
                    </div>

                    <div class="form-group">
                        <label class="required">Costo por unidad:</label>
                        <input type="number" step="0.01" name="costo_unidad" placeholder="0.00" min="0" required>
                        <div class="form-hint">Costo de adquisición en colones (₡)</div>
                    </div>

                    <button type="submit">Guardar Producto</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
