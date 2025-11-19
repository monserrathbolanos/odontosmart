
<?php
//Interactua con la tabla Usuarios
// Inicia la sesión para manejar variables de usuario
session_start();

// Importa la conexión a la base de datos y funciones de protección CSRF

include('../config/conexion.php');
require '../config/csrf.php';

// Verifica que la solicitud sea POST y que el token CSRF sea válido

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validate_csrf_token($_POST['csrf_token'] ?? '')) { //si existe csrf_token en el POST lo usa y si no, usa cadena vacía.
    header('Location: login.php?error=' . urlencode('Acceso no permitido.')); //Redirige al usuario a login.php
    exit;
}

// Obtiene y limpia los datos del formulario con trim

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Verifica que ambos campos estén completados
if ($username === '' || $password === '') {
    header('Location: login.php?error=' . urlencode('Usuario y contraseña son obligatorios.')); //sino redirige a login
    exit;
}

// Consulta de la tabla usuarios para obtener los datos del usuario y su rol
$sql = "SELECT u.id_usuario, u.nombre_completo, u.email, u.password, r.nombre AS rol
        FROM usuarios u
        JOIN roles r ON u.id_rol = r.id_rol
        WHERE u.nombre_completo = ?";

// Prepara y ejecuta la consulta

$stmt = $conn->prepare($sql);  //$conn es la conexión a la base de datos, prepare($sql) crea una consulta preparada a partir de la sentencia SQL que está en $sql
$stmt->bind_param("s", $username);   //Asocia el valor de la variable $username al parametro de la consulta preparada.
$stmt->execute(); //ejecuta la consulta preparada
$result = $stmt->get_result(); //Obtiene el resultado de la consulta en forma de objeto mysqli_result.

// Si no se encuentra el usuario, redirige con error
if ($result->num_rows === 0) {
    header('Location: login.php?error=' . urlencode('Usuario o contraseña incorrectos.'));
    exit;
}

// Obtiene los datos del usuario
$user = $result->fetch_assoc(); //obtiene una fila del resultado como un array asociativo, donde las claves son los nombres de las columnas.
$stmt->close();

// Verifica que la contraseña ingresada coincida con el hash almacenado
if (password_verify($password, $user['password'])) {
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