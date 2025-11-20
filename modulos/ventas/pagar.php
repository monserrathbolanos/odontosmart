<?php
// pagar.php 

//no se relaciona directamente con tablas o base de datos, todo el flujo se maneja en la sesion carrito, SIN EMBARGO depende indirectamente de datos que provienen de la tabla Productos
session_start();
// Vaciar carrito si se recibe la acción.


// Los datos del carrito (id_producto, nombre, precio, id_lote) provienen originalmente de la tabla productos, pero fueron cargados en el archivo agregar_carrito.php.
// Cuando el usuario hace clic en "Pagar ahora", se envía el formulario a procesar_pago.php, y ahí sí se insertan datos en las tablas:

// ventas
// detalle_venta
// Se consulta usuarios.


if (isset($_GET['vaciar'])) {
    unset($_SESSION['carrito']);
    header("Location: pagar.php");
    exit();
}

// Verificar si el carrito está vacío
$carrito_vacio = !isset($_SESSION['carrito']) || empty($_SESSION['carrito']);
$carrito = $carrito_vacio ? [] : $_SESSION['carrito'];

// Calcular totales
$subtotal = 0;
foreach ($carrito as $item) {
    $subtotal += $item['precio'] * $item['cantidad'];
}
$iva = $subtotal * 0.13;
$total = $subtotal + $iva;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Carrito de Compras - OdontoSmart</title>
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
        .totales {
            background: #e8f4ff;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        button {
            padding: 10px 15px;
            background: #152fbf;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }
        .btn-pagar {
            background: #28a745;
            padding: 12px 25px;
            font-size: 16px;
        }
        .btn-vaciar {
            background: #dc3545;
        }
        .logo-navbar {
            position: absolute;
            bottom: 80px;   /* ajustá si querés subirlo o bajarlo */
            left: 50%;
            transform: translateX(-50%);
            width: 140px;   /* tamaño del logo */
            opacity: 0.9;
        }

    </style>
</head>
<body>
    <!-- Menú -->
    <div class="navbar">
    <!-- Logo inferior del menú -->
    <?php include('../../views/navbar.php'); ?>
    <img src="../../assets/img/odonto1.png" class="logo-navbar" alt="Logo OdontoSmart">
</div>


    <div class="content">
        <h1 style="color: #69B7BF;">Carrito de Compras - OdontoSmart</h1>

        <?php if ($carrito_vacio): ?>
            <div class="seccion">
                <h2>No hay productos en el carrito.</h2>
                <a href="servicios.php"><button>Seguir comprando</button></a>
            </div>

        <?php else: ?>
            <div class="seccion">
                <h2>Productos Seleccionados</h2>
                <table>
                    <tr>
                        <th>ID Producto</th>
                        <th>Nombre</th>
                        <th>ID Lote</th>
                        <th>Cantidad</th>
                        <th>Precio Unitario</th>
                        <th>Total</th>
                    </tr>

                    <?php foreach ($carrito as $item): 
                        $total_producto = $item['precio'] * $item['cantidad'];
                    ?>
                        <tr>
                            <td><?= $item['id_producto'] ?></td>
                            <td><?= $item['nombre'] ?></td>
                            <td><?= $item['id_lote'] ?></td>
                            <td><?= $item['cantidad'] ?></td>
                            <td>₡<?= number_format($item['precio'], 2) ?></td>
                            <td>₡<?= number_format($total_producto, 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>

                <!-- Totales -->
                <div class="totales">
                    <h3>Resumen de Pago</h3>
                    <p><strong>Subtotal: ₡<?= number_format($subtotal, 2) ?></strong></p>
                    <p>IVA (13%): ₡<?= number_format($iva, 2) ?></p>
                    <p style="font-size: 18px; color: #152fbf;"><strong>Total: ₡<?= number_format($total, 2) ?></strong></p>
                </div>

                <!-- Botones de acción -->
                <div>
                    <a href="servicios.php"><button> Seguir comprando</button></a>
                    <a href="pagar.php?vaciar=1"><button class="btn-vaciar">Vaciar carrito</button></a>
                    
                  
                    <form action="procesar_pago.php" method="POST" style="display: inline;">
                        <button type="submit" class="btn-pagar">Pagar ahora</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>