<?php
// servicios.php
session_start();
include('conexion.php');

// Obtener lista de productos
$sql = "SELECT * FROM productos WHERE activo = 'si'";
$result = $conn->query($sql);

$rol = "administrador"; // Temporal - luego será dinámico por sesión
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Servicios - OdontoSmart</title>
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
        .producto-card {
            background: white;
            padding: 20px;
            margin: 15px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #152fbf;
        }
        button {
            padding: 8px 15px;
            background: #152fbf;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }
        button:hover {
            background: #264cbf;
        }
        .btn-pagar {
            background: #28a745;
            padding: 12px 25px;
            font-size: 16px;
        }
        .btn-pagar:hover {
            background: #218838;
        }
        .cantidad-input {
            width: 70px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }
        .mensaje {
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .mensaje-exito {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .mensaje-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .header-acciones {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .precio {
            color: #28a745;
            font-weight: bold;
            font-size: 16px;
        }
        .stock-info {
            color: #6c757d;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <!-- Menú Lateral -->
    <div class="navbar">
        <?php include('navbar.php'); ?>
    </div>

    <div class="content">
        <div class="header-acciones">
            <h1> Servicios y Productos - OdontoSmart</h1>
            <a href="pagar.php">
                <button class="btn-pagar"> Ver Carrito</button>
            </a>
        </div>

        <!-- Mensajes de éxito/error -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="mensaje mensaje-exito">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="mensaje mensaje-error">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="seccion">
            <h2> Productos Disponibles</h2>
            
            <?php if ($result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Producto</th>
                            <th>Descripción</th>
                            <th>Unidad</th>
                            <th>Precio</th>
                            <th>ID Lote</th>
                            <th>Caducidad</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id_producto'] ?></td>
                            <td><strong><?= $row['nombre'] ?></strong></td>
                            <td><?= $row['descripcion'] ?></td>
                            <td><?= $row['unidad'] ?></td>
                            <td class="precio">₡<?= number_format($row['precio'], 2) ?></td>
                            <td><?= $row['id_lote'] ?></td>
                            <td class="stock-info"><?= $row['fecha_caducidad'] ?? 'No aplica' ?></td>
                            <td>
                                <form action="agregar_carrito.php" method="POST" style="display: flex; align-items: center; gap: 10px;">
                                    <input type="hidden" name="id_producto" value="<?= $row['id_producto'] ?>">
                                    <input type="number" name="cantidad" value="1" min="1" class="cantidad-input" required>
                                    <button type="submit"> Agregar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #6c757d;">
                    <h3>No hay productos disponibles</h3>
                    <p>No se encontraron productos activos en el sistema.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>