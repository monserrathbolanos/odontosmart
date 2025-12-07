<?php
require '../config/conexion.php';

// Verificar que viene el token
if (!isset($_GET['token'])) {
    die("Token no proporcionado.");
}

$token = $_GET['token'];

// ✅ CONSULTA CORRECTA SEGÚN TU TABLA
$stmt = $conn->prepare("SELECT id_usuario FROM restablecer_contrasenas WHERE token = ? AND expira > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

// Verificar si el token es válido
if ($result->num_rows === 0) {
    die("Token inválido o expirado.");
}
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Restablecer contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light d-flex align-items-center justify-content-center vh-100">

<div class="card shadow p-4" style="max-width: 400px; width: 100%;">
    <h2 class="text-center mb-4"><strong>Restablecer contraseña</strong></h2>

    <form action="actualizar_contrasena.php" method="POST">

        <!-- Token oculto -->
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <div class="mb-3">
            <label class="form-label">Nueva contraseña</label>
            <input type="password" name="new_password" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-success w-100">
            Actualizar contraseña
        </button>

    </form>
</div>

</body>
</html>
