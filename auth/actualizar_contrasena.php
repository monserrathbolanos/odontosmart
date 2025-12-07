<?php
require '../config/conexion.php';

// Validar POST
if (!isset($_POST['token'], $_POST['new_password'])) {
    die("Solicitud inválida.");
}

$token = $_POST['token'];
$new_password = $_POST['new_password'];

// 1️ Buscar token válido y obtener EMAIL
$stmt = $conn->prepare("
    SELECT id_usuario, email 
    FROM restablecer_contrasenas 
    WHERE token = ? AND expira > NOW()
");

$id_usuario = $data['id_usuario'];
$email = $data['email'];
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Token inválido o expirado.");
}

$email = $result->fetch_assoc()['email'];
$stmt->close();

// 2️ Encriptar contraseña nueva
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// 3️ Actualizar contraseña usando EMAIL
$update_stmt = $conn->prepare("
    UPDATE usuarios 
    SET password = ? 
    WHERE email = ?
");
$update_stmt->bind_param("ss", $hashed_password, $email);
$update_stmt->execute();
$update_stmt->close();

// 4️ Eliminar token usado
$delete_stmt = $conn->prepare("
    DELETE FROM restablecer_contrasenas 
    WHERE email = ?
");
$delete_stmt->bind_param("s", $email);
$delete_stmt->execute();
$delete_stmt->close();

// 5️ Mensaje final y redirección
echo "
<script>
alert('Contraseña actualizada correctamente');
window.location.href='iniciar_sesion.php';
</script>
";
