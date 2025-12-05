<?php
// pagar.php - Página para que el usuario ingrese datos de tarjeta
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user']['id_usuario'])) {
    echo "<p>No hay usuario logueado.</p>";
    exit;
}

$id_usuario = $_SESSION['user']['id_usuario']; // ID del usuario autenticado
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <meta charset="UTF-8">
    <title>OdontoSmart - Pago con Tarjeta</title>

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
        .logo-navbar {
            position: absolute;
            bottom: 80px;
            left: 50%;
            transform: translateX(-50%);
            width: 140px;
            opacity: 0.9;
        }

        .content { 
            margin-left: 240px; 
            padding: 20px; 
        }

        .seccion {
            background: white;
            padding: 30px;
            margin: 15px auto;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            max-width: 500px;
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

        input {
            padding: 12px;
            margin: 5px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 100%;
            font-size: 14px;
        }

        input:focus {
            border-color: #152fbf;
            outline: none;
            box-shadow: 0 0 5px rgba(21, 47, 191, 0.3);
        }

        button {
            padding: 12px 25px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
            width: 100%;
            font-weight: bold;
        }

        button:hover {
            background: #218838;
        }

        a {
            display: block;
            margin-top: 15px;
            color: #152fbf;
            text-decoration: none;
            text-align: center;
        }
    </style>
</head>

<body>

    <!-- Menú lateral -->
    <div class="navbar">
        <?php include('../../views/navbar.php'); ?>
    </div>

    <div class="content">

        <div class="seccion">
            <h1 style="color: #69B7BF;">Confirmar Pago</h1>
            <p>Ingrese los datos de su tarjeta para procesar el pago.</p>

            <form action="procesar_pago.php" method="POST">

                <!-- Nombre del titular -->
                <div class="form-group">
                    <label class="required">Nombre en la tarjeta:</label>
                    <input type="text" name="nombre" required>
                </div>

                <!-- Número de tarjeta -->
                <div class="form-group">
                    <label class="required">Número de tarjeta:</label>
                    <input type="text" name="tarjeta" maxlength="16" minlength="16" 
                           required placeholder="16 dígitos">
                </div>

                <!-- Fecha igual a inventario: YYYY-MM-DD -->
              <div class="form-group">
    <label class="required">Fecha de vencimiento:</label>
    <input type="month" name="vencimiento" required>
</div>

                <!-- CVV -->
                <div class="form-group">
                    <label class="required">CVV:</label>
                    <input type="password" name="cvv" maxlength="3" minlength="3" 
                           required placeholder="3 dígitos">
                </div>

                <button type="submit">Procesar pago</button>
            </form>

            <a href="carrito.php">← Volver al carrito</a>
        </div>

    </div>

</body>
</html>
