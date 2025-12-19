<?php
// pagar.php - Página para que el usuario ingrese datos de tarjeta
session_start();

/* Validar rol permitido */
$rol = $_SESSION['user']['role'] ?? null;
$rolesPermitidos = ['Administrador','Cliente']; // ej.

if (!in_array($rol, $rolesPermitidos)) {
    // Aquí decides a dónde mandarlo: login, home o protegido.
    // Si quieres mandarlo al login:
    header('Location: ../../auth/iniciar_sesion.php?error=' . urlencode('Debes iniciar sesión o registrarte.'));
    exit;
}

$id_usuario = $_SESSION['user']['id_usuario']; // ID del usuario autenticado
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <meta charset="UTF-8">
    <title>Pago con tarjeta</title>

        <!-- FAVICON -->
    <link rel="icon" type="image/png" href="../../assets/img/odonto1.png">

    <!-- ESTILOS CSS -->
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/pagar.css">

</head>

<body>

    <!-- Menú lateral -->
    <div class="sidebar">
        <?php include('../../views/sidebar.php'); ?>
         <img src="../../assets/img/odonto1.png" class="logo-sidebar" alt="Logo OdontoSmart">
    </div>

    <div class="content">

        <div class="seccion">
            <h1 style="color: #69B7BF;">Pago con tarjeta</h1>
            <p>Ingrese los datos de su tarjeta para procesar el pago.</p>

            <form action="procesar_pago.php" method="POST">

                <!-- Nombre del titular -->
                <div class="form-group">
                    <label class="required">Nombre en la tarjeta:</label>
                    <input
                        type="text"
                        name="nombre"
                        required
                        maxlength="50"
                        pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ ]+"
                        title="Solo letras y espacios"
                    >
                </div>

                <!-- Número de tarjeta -->
                <div class="form-group">
                    <label class="required">Número de tarjeta:</label>
                    <input
                        type="text"
                        name="tarjeta"
                        required
                        inputmode="numeric"
                        pattern="[0-9]{16}"
                        maxlength="16"
                        minlength="16"
                        placeholder="16 dígitos"
                        title="Debe contener exactamente 16 números"
                    >
    
                </div>

                <!-- Fecha igual a inventario: YYYY-MM-DD -->
              <div class="form-group">
    <label class="required">Fecha de vencimiento:</label>
    <input type="month" name="vencimiento" required>
</div>

                <!-- CVV -->
                <div class="form-group">
                    <label class="required">CVV:</label>
                   <input
                        type="password"
                        name="cvv"
                        required
                        inputmode="numeric"
                        pattern="[0-9]{3}"
                        maxlength="3"
                        minlength="3"
                        placeholder="3 dígitos"
                        title="Debe contener 3 números"
                    >
                </div>

                <button type="submit">Procesar pago</button>
            </form>

            <a href="carrito.php">Volver al carrito</a>
        </div>

    </div>

</body>
</html>
