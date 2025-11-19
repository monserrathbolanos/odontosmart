<!-- //codigo comentado, para probar el metodo p0ost si fuese necesario// -->
<!-- require '../config/conexion.php';
$email = $_POST['email'];
$new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

// Actualizar contraseña
$stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE email = ?");
$stmt->bind_param("ss", $new_password, $email);
$stmt->execute();

// Eliminar token usado
$conn->query("DELETE FROM password_resets WHERE email = '$email'");

echo "Contraseña actualizada correctamente. <a href='login.php'>Iniciar sesión</a>"; -->
 








<?php
require '../config/conexion.php';

// Verificar que se envió el token y la nueva contraseña
if (!isset($_POST['token'], $_POST['new_password'])) {
    die("Solicitud inválida.");
}

$token = $_POST['token'];
$new_password = $_POST['new_password'];

// Validar que el token existe y no ha expirado
$stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Token inválido o expirado.");
}

$email = $result->fetch_assoc()['email'];

// Hashear la nueva contraseña
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Actualizar la contraseña en la tabla usuarios
$update_stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE email = ?");
$update_stmt->bind_param("ss", $hashed_password, $email);
$update_stmt->execute();

// Eliminar el token usado
$delete_stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
$delete_stmt->bind_param("s", $email);
$delete_stmt->execute();

echo "Contraseña actualizada correctamente. <a href='login.phpesión</a>";
?>
