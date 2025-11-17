<?php
require '../config/conexion.php';
$email = $_POST['email'];
$token = bin2hex(random_bytes(16)); //Genera un token aleatorio
$expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

// Guardar token
$stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)"); //Inserta en la tabla password_resets
$stmt->bind_param("sss", $email, $token, $expires);
$stmt->execute();

// Simular envío de correo localmente
$link = "http://localhost/Progra4PHP/LoginApp/Proyecto/reset_password.php?token=$token";
echo "Enlace de recuperación (simulado): <a href='$link'>$link</a>";


echo "<script>
    alert('Se ha generado el enlace de recuperación. Revisa el correo (simulado).');
    window.location.href='login.php';
</script>";

header('Location: update_password.php');
exit();

 //en una version de produccion aqui se enviaria el correo real y se redigiria al login



?>