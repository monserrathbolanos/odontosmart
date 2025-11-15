<?php
require '../config/conexion.php';
$email = $_POST['email'];
$token = bin2hex(random_bytes(16));
$expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

// Guardar token
$stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $email, $token, $expires);
$stmt->execute();

// Simular envío de correo localmente
//$link = "http://localhost/Progra4PHP/LoginApp/Proyecto/reset_password.php?token=$token";
//echo "Enlace de recuperación (simulado): <a href='$link'>$link</a>";
 header('Location: login.php');
?>