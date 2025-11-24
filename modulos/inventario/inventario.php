<?php
// inventario.php // Trabaja con las tablas categoria_productos y productos
session_start();
include('../../config/conexion.php');
$rol = $_SESSION['user']['role'] ?? 'administrador';

$mensaje = "";

// Formulario para agregar un producto
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre          = $_POST["nombre"] ?? '';
    $descripcion     = $_POST["descripcion"] ?? '';
    $unidad          = $_POST["unidad"] ?? '';
    $precio          = floatval($_POST["precio"] ?? 0);
    $stock_minimo    = intval($_POST["stock_minimo"] ?? 0);
    $stock_total     = intval($_POST["stock_total"] ?? 0);
    $id_categoria    = intval($_POST["id_categoria"] ?? 0);
    $fecha_caducidad = $_POST["fecha_caducidad"] ?? null;
    $costo_unidad    = floatval($_POST["costo_unidad"] ?? 0);

    $idUsuarioSesion = intval($_SESSION['user']['id_usuario'] ?? 0);
    $ip_cliente      = $_SERVER['REMOTE_ADDR'] ?? 'DESCONOCIDA';

    // Llamar al SP que crea producto y registra en bitácora
    $stmt = $conn->prepare("
        CALL sp_productos_crear(?,?,?,?,?,?,?,?,?,?,?, @resultado)
    ");

    if ($stmt) {
        // Tipos: i s s s d d i i s i s  (11 parámetros)
        $stmt->bind_param(
            "isssddiisis",
            $id_categoria,      // i
            $nombre,            // s
            $descripcion,       // s
            $unidad,            // s
            $precio,            // d
            $costo_unidad,      // d
            $stock_total,       // i
            $stock_minimo,      // i
            $fecha_caducidad,   // s (DATE en formato 'Y-m-d')
            $idUsuarioSesion,   // i
            $ip_cliente         // s
        );

        if ($stmt->execute()) {
            $stmt->close();
            $conn->next_result(); // Limpia resultados del CALL

            // Leer el valor OUT del SP
            $res = $conn->query("SELECT @resultado AS res");
            $row = $res->fetch_assoc();
            $resultado = $row['res'] ?? null;

            if ($resultado === 'OK') {
                $mensaje = " El producto fue agregado correctamente.";
            } elseif ($resultado === 'DUPLICADO') {
                $mensaje = " Error: ya existe un producto con ese nombre en la misma categoría.";
            } else {
                $mensaje = " Error inesperado al agregar el producto.";
            }
        } else {
            $mensaje = " Error al ejecutar el procedimiento almacenado.";
            $stmt->close();
        }
    } else {
        $mensaje = " Error al preparar el procedimiento almacenado.";
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
