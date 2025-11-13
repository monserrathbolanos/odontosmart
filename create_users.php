<?php
// Conexion a la case de datos
$host = "localhost";
$user = "root"; 
$password = ""; 
$dbname = "odontosmart_db";

//Para crear la conexion
$conn = new mysqli($host, $user, $password, $dbname);

// Para verificar la conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$mensaje = ""; //Guarda mensajes de exito o error

// Formulario para crear un nuevo usuario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST["nombre_completo"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $cedula = $_POST["cedula"];
    $telefono = $_POST["telefono"];
    $rol = $_POST["rol"]; //Ya sea cliente, administrador o medico

    //Hash para la protección de la contraseña
    $hash_password = password_hash($password, PASSWORD_DEFAULT);

    //id, segun el rol seleccionado
    $sqlRol = "SELECT id_rol FROM roles WHERE nombre = ?";
    $stmtRol = $conn->prepare($sqlRol);
    $stmtRol->bind_param("s", $rol);
    $stmtRol->execute();
    $resultRol = $stmtRol->get_result();

    if ($resultRol->num_rows > 0) {
        $rowRol = $resultRol->fetch_assoc();
        $id_rol = $rowRol["id_rol"];

        //Inserts de usuario
        $sqlUser = "INSERT INTO usuarios (nombre_completo, email, hash_contrasena, cedula, telefono, estado, id_rol) 
                    VALUES (?, ?, ?, ?, ?, 'activo', ?)";
        $stmtUser = $conn->prepare($sqlUser);
        $stmtUser->bind_param("sssssi", $nombre, $email, $hash_password, $cedula, $telefono, $id_rol);

        if ($stmtUser->execute()) {
            $mensaje = "El usuario se agrego, bajo el rol de: $rol";
        } else {
            $mensaje = "El usuario no se agrego correctamente: " . $conn->error;
        }
    } else {
        $mensaje = "Error.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Usuario - OdontoSmart</title>
</head>
<body>
    <h1>Registrar Usuario</h1>
    <form method="POST" action="">
        <label>Nombre Completo:</label><br>
        <input type="text" name="nombre_completo" required><br><br>

        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>

        <label>Contraseña:</label><br>
        <input type="password" name="password" required><br><br>

        <label>Número de Cédula:</label><br>
        <input type="text" name="cedula" required><br><br>

        <label>Teléfono:</label><br>
        <input type="text" name="telefono" required><br><br>

        <label>Rol:</label><br>
        <select name="rol" required>
            <option value="cliente">Cliente</option>
            <option value="administrador">Administrador</option>
            <option value="medico">Médico</option>
        </select><br><br>

        <button type="submit">Crear Usuario</button>
    </form>

    <?php
    if (!empty($mensaje)) {
        echo "<p><strong>$mensaje</strong></p>";
    }
    ?>
</body>
</html>
