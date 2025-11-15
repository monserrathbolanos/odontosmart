<?php
require '../config/conexion.php';
$email = $_POST['email'];
$new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

// Actualizar contraseña
$stmt = $conn->prepare("UPDATE usuarios SET hash_contrasena = ? WHERE email = ?");
$stmt->bind_param("ss", $new_password, $email);
$stmt->execute();

// Eliminar token usado
$conn->query("DELETE FROM password_resets WHERE email = '$email'");

echo "Contraseña actualizada correctamente. <a href='login.php'>Iniciar sesión</a>";
?>