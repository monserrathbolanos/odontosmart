
<?php

/*
  enviar_contrasena.php
  ---------------------
  Script que procesa la solicitud de recuperación de contraseña.
 
  Flujo:
   1. Recibe el correo electrónico desde el formulario (POST).
   2. Verifica que exista un usuario con ese correo en la tabla `usuarios`.
   3. Genera un token seguro y una fecha/hora de expiración.
   4. Inserta el registro de recuperación en la tabla `restablecer_contrasenas`.
   5. Construye un enlace de recuperación apuntando a `restablecer_contrasena.php`.
   6. Muestra en pantalla el enlace generado (modo local / demo).
 
  NOTA: En un entorno productivo, este enlace se enviaría por correo electrónico.
 */
require '../config/conexion.php';// Conexión a la base de datos 

// 1 Verificar que venga el correo desde el formulario
if (!isset($_POST['email'])) {
    die("Solicitud inválida.");
}

$email = trim($_POST['email']); // Se limpia el correo para eliminar espacios extra

// 2️ Buscar el usuario por email y obtener id_usuario
$stmtUser = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
$stmtUser->bind_param("s", $email);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();

// Si no existe ninguna cuenta con ese correo, se detiene el proceso
if ($resultUser->num_rows === 0) {
    die("No existe una cuenta con ese correo.");
}

// Se obtiene el id_usuario asociado a ese email
$data = $resultUser->fetch_assoc();
$id_usuario = $data['id_usuario'];
$stmtUser->close();

// 3️ Generar token y vencimiento
//    - token: cadena aleatoria segura en formato hexadecimal.
//    - expira: fecha/hora en la que el enlace deja de ser válido (1 hora a partir de ahora).
$token  = bin2hex(random_bytes(32));
$expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

// 4️ Insertar correctamente TODOS los datos en la tabla de restablecimiento
$stmt = $conn->prepare("
    INSERT INTO restablecer_contrasenas (id_usuario, email, token, expira) 
    VALUES (?, ?, ?, ?)
");
$stmt->bind_param("isss", $id_usuario, $email, $token, $expira);
$stmt->execute();
$stmt->close();

// 5️ Generar link local para restablecer la contraseña
//    Este enlace será consumido por restablecer_contrasena.php, que
//    mostrará el formulario para definir la nueva contraseña.
$link = "http://localhost/odontosmart/auth/restablecer_contrasena.php?token=$token";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Enlace de recuperación</title>
    <!-- FAVICON -->
    <link rel="icon" type="image/png" href="../assets/img/odonto1.png">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Tu CSS global -->
    <style>
 body {
    margin: 0;
    padding: 0;
    font-family: 'Poppins', sans-serif;
    color: #fff;
    background: linear-gradient(270deg , #D5E7F2, #69B7BF, #d5e7f2);
    background-size: 300% 300%;
    animation: rgbFlow 100s ease infinite;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
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

    <!--
        TARJETA DE CONFIRMACIÓN
        
        Muestra el enlace de recuperación generado para que el usuario
        pueda abrirlo o copiarlo manualmente.
    -->
    <div class="card shadow-lg text-center">
        <h3>Enlace de recuperación generado</h3>

        <p class="mt-3">
            Copia y abre el siguiente enlace para restablecer tu contraseña:
        </p>

        <!-- Botón para abrir el enlace de recuperación en una nueva pestaña -->
        <a href="<?= $link ?>" target="_blank" class="btn btn-success w-100 mb-3">
            Abrir enlace de recuperación
        </a>

        <!-- Muestra el enlace completo como texto para copiar/pegar -->
        <small style="color:#333; word-break: break-all;">
            <?= $link ?>
        </small>

        <!-- Enlace para regresar a la pantalla de inicio de sesión -->
        <a href="iniciar_sesion.php" class="btn btn-secondary w-100 mt-4">
            Volver al inicio de sesión
        </a>
    </div>

</body>
</html>
