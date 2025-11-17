

<?php
require '../config/conexion.php';

// Verificar que el token está en la URL
if (!isset($_GET['token'])) {
    die("Token no proporcionado.");
}

$token = $_GET['token'];

// Validar que el token existe y no ha expirado
$stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Token inválido o expirado.");
}
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Restablecer contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"y class="bg-light d-flex align-items-center justify-content-center vh-100">
<div class="card shadow p-4" style="max-width: 400px; width: 100%;">
    <h2 class="text-center mb-4"><strong>Restablecer contraseña</strong></h2>

    update_password.php
        <!-- Enviar el token oculto -->
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <div class="mb-3">
            <label for="new_password" class="form-label">Nueva contraseña</label>
            <input id="new_password" name="new_password" type="password" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-success w-100"><strong>Actualizar contraseña</strong></button>
    </form>
</div>
</body>
</html>