
<?php

// Inicia la sesión para manejar variables de usuario
session_start();

// Importa la conexión a la base de datos y funciones de protección CSRF

include('../config/conexion.php');
require '../config/csrf.php';

// Verifica que la solicitud sea POST y que el token CSRF sea válido

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validate_csrf_token($_POST['csrf_token'] ?? '')) {
    header('Location: login.php?error=' . urlencode('Acceso no permitido.'));
    exit;
}

// Obtiene y limpia los datos del formulario

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Verifica que ambos campos estén completos
if ($username === '' || $password === '') {
    header('Location: login.php?error=' . urlencode('Usuario y contraseña son obligatorios.'));
    exit;
}

// Consulta SQL para obtener los datos del usuario y su rol
$sql = "SELECT u.id_usuario, u.nombre_completo, u.email, u.hash_contrasena, r.nombre AS rol
        FROM usuarios u
        JOIN roles r ON u.id_rol = r.id_rol
        WHERE u.nombre_completo = ?";

// Prepara y ejecuta la consulta

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

// Si no se encuentra el usuario, redirige con error
if ($result->num_rows === 0) {
    header('Location: login.php?error=' . urlencode('Usuario o contraseña incorrectos.'));
    exit;
}

// Obtiene los datos del usuario
$user = $result->fetch_assoc();
$stmt->close();

// Verifica que la contraseña ingresada coincida con el hash almacenado
if (password_verify($password, $user['hash_contrasena'])) {
    session_regenerate_id(true); // Regenera el ID de sesión por seguridad
    
    // Guarda los datos del usuario en la sesión
$_SESSION['user'] = [
        'username' => $user['nombre_completo'],
        'email'    => $user['email'],
        'role'     => $user['rol']
    ];

    
    // Redirige al index.php tras inicio de sesión exitoso

    header('Location: ../public/home.php');
    exit;
} else {

    // Si la contraseña no coincide, redirige con mensaje de error
    header('Location: login.php?error=' . urlencode('Usuario o contraseña incorrectos.'));
    exit;
}

// Cierra la conexión a la base de datos

$conn->close();

