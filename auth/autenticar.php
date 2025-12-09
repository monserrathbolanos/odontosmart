

<?php
/*
  autenticar.php
 
  Script de autenticación de usuarios para el sistema OdontoSmart.
 
  Responsabilidades:
   - Validar que la solicitud provenga de un formulario (método POST).
   - Verificar el token CSRF para evitar ataques de falsificación de petición.
   - Consultar la tabla `usuarios` y obtener datos del usuario + rol.
   - Validar la contraseña usando password_verify().
   - Cargar los permisos asociados al rol desde `permisos` y `rol_permisos`.
   - Iniciar sesión y almacenar los datos relevantes en $_SESSION['user'].
   - Redirigir al panel principal (../public/home.php) tras login exitoso.
 */

session_start(); // Inicia la sesión para manejar variables de usuario

// Importa la conexión a la base de datos y funciones de protección CSRF
include('../config/conexion.php');
require '../config/csrf.php';

// Verifica que la solicitud sea POST y que el token CSRF sea válido
// (si existe csrf_token en el POST lo usa, si no, usa cadena vacía)
if (
    $_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !validate_csrf_token($_POST['csrf_token'] ?? '')
) {
    // Redirige de vuelta al login con mensaje de error
    header('Location: iniciar_sesion.php?error=' . urlencode('Acceso no permitido.'));
    exit;
}

// Obtiene y limpia los datos del formulario
$email    = trim($_POST['email']    ?? '');
$password =       $_POST['password'] ?? '';

// Verifica que ambos campos estén completados
if ($email === '' || $password === '') {
    // Si faltan datos, redirige al formulario de inicio de sesión con mensaje
    header('Location: iniciar_sesion.php?error=' . urlencode('Correo y contraseña son obligatorios.'));
    exit;
}


/*
  Consulta de la tabla usuarios para obtener:
   - id_usuario
   - nombre_completo
   - email
   - password (hash)
   - id_rol
   - nombre del rol (alias: rol)
 
  Se hace JOIN con la tabla roles para obtener el nombre del rol.*/
  $sql = "SELECT u.id_usuario, u.nombre_completo, u.email, u.password, u.id_rol, r.nombre AS rol
        FROM usuarios u
        JOIN roles r ON u.id_rol = r.id_rol
        WHERE u.email = ?";

// Prepara y ejecuta la consulta
$stmt = $conn->prepare($sql);        // Crea la consulta preparada a partir de la sentencia SQL
$stmt->bind_param("s", $email);      // Asocia el valor de $email al parámetro de la consulta
$stmt->execute();                    // Ejecuta la consulta preparada
$result = $stmt->get_result();       // Obtiene el resultado en un objeto mysqli_result

// Si no se encuentra el usuario, redirige con error genérico
if ($result->num_rows === 0) {
    header('Location: iniciar_sesion.php?error=' . urlencode('Correo o contraseña incorrectos.'));
    exit;
}

// Obtiene los datos del usuario como un array asociativo
$user = $result->fetch_assoc(); // Claves = nombres de columnas (id_usuario, nombre_completo, etc.)
$stmt->close();

// Verifica que la contraseña ingresada coincida con el hash almacenado en BD
if (!password_verify($password, $user['password'])) {
    header('Location: iniciar_sesion.php?error=' . urlencode('Usuario o contraseña incorrectos.'));
    exit;
}

/*
  Carga de permisos según rol
  
  Se consultan las tablas:
   - permisos (p)
   - rol_permisos (rp)
  para obtener la lista de nombres de permisos asociados al rol del usuario.
 */
$permisos = [];
    
$stmtPerm = $conn->prepare("
    SELECT p.nombre
    FROM permisos p
    INNER JOIN rol_permisos rp ON p.id_permiso = rp.id_permiso
    WHERE rp.id_rol = ?
");
$stmtPerm->bind_param("i", $user['id_rol']);
$stmtPerm->execute();
$resPerm = $stmtPerm->get_result();

while ($row = $resPerm->fetch_assoc()) {
    $permisos[] = $row['nombre']; // Se almacena el nombre del permiso en el arreglo
}

$stmtPerm->close();

/*
  Regeneración del ID de sesión
  
  Por seguridad, se genera un nuevo ID de sesión después de un login exitoso
  para evitar ataques de fijación de sesión.
 */
session_regenerate_id(true); // true = borra la sesión anterior en el servidor

/*
  Guardar datos del usuario en la sesión
  
  Se almacena un array asociativo en $_SESSION['user'] con:
   - id_usuario
   - nombre_completo
   - email
   - role (nombre del rol: Cliente, Administrador, Médico, etc.)
   - id_rol (clave numérica del rol)
   - permisos (array con nombres de permisos)
 */

$_SESSION['user'] = [
    'id_usuario'      => $user['id_usuario'],
    'nombre_completo' => $user['nombre_completo'],
    'email'           => $user['email'],
    'role'            => $user['rol'],      // Cliente, Administrador, Médico, Recepcionista
    'id_rol'          => $user['id_rol'],
    'permisos'        => $permisos 
];

// Cierra la conexión a la base de datos antes de redirigir
$conn->close();

// Redirige al home principal tras inicio de sesión exitoso
header('Location: ../public/home.php');
exit;


