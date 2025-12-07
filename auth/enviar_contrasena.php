<?php
require '../config/conexion.php';

// 1️Verificar que venga el correo
if (!isset($_POST['email'])) {
    die("Solicitud inválida.");
}

$email = trim($_POST['email']);

// 2️ Buscar el usuario por email y obtener id_usuario
$stmtUser = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
$stmtUser->bind_param("s", $email);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();

if ($resultUser->num_rows === 0) {
    die("No existe una cuenta con ese correo.");
}

$data = $resultUser->fetch_assoc();
$id_usuario = $data['id_usuario'];
$stmtUser->close();

// 3️ Generar token y vencimiento
$token  = bin2hex(random_bytes(32));
$expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

// 4️ Insertar correctamente TODOS los datos
$stmt = $conn->prepare("
    INSERT INTO restablecer_contrasenas (id_usuario, email, token, expira) 
    VALUES (?, ?, ?, ?)
");
$stmt->bind_param("isss", $id_usuario, $email, $token, $expira);
$stmt->execute();
$stmt->close();

// 5️ Generar link local
$link = "http://localhost/odontosmart/auth/restablecer_contrasena.php?token=$token";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Enlace de recuperación</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Tu CSS global -->
    <style>
body {
    background: linear-gradient(270deg, #152FBF, #264CBF, #182940, #D5E7F2, #69B7BF);
    background-size: 300% 300%;
    animation: rgbFlow 150s ease infinite;
    font-family: 'Poppins', sans-serif;
    color: #ffffff;
}

@keyframes rgbFlow {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

.card {
    background: #ffffffaf;
    color: #000;
    border-radius: 16px;
    padding: 30px;
    max-width: 500px;
    margin: auto;
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 28px rgba(0,0,0,0.3);
}

h3 {
    color: #69B7BF;
    margin-bottom: 20px;
    text-align: center;
}

.btn-success {
    background: #69B7BF;
    border: none;
    color: #fff;
    font-weight: bold;
}

.btn-success:hover {
    background: #264CBF;
    transform: scale(1.05);
}

.btn-secondary {
    background: #182940;
    border: none;
    color: #fff;
    font-weight: bold;
}

.btn-secondary:hover {
    background: #264CBF;
    transform: scale(1.05);
}
    </style>
</head>

<body class="d-flex align-items-center justify-content-center vh-100">

<div class="card shadow-lg text-center">
    <h3>Enlace de recuperación generado</h3>

    <p class="mt-3">
        Copia y abre el siguiente enlace para restablecer tu contraseña:
    </p>

    <a href="<?= $link ?>" target="_blank" class="btn btn-success w-100 mb-3">
        Abrir enlace de recuperación
    </a>

    <small style="color:#333; word-break: break-all;">
        <?= $link ?>
    </small>

    <a href="iniciar_sesion.php" class="btn btn-secondary w-100 mt-4">
        Volver al inicio de sesión
    </a>
</div>

</body>
</html>
