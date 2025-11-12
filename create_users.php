<?php
// Configuración de conexión
$host = "localhost";
$user = "root"; // usuario por defecto en Laragon
$password = ""; // contraseña vacía por defecto en Laragon
$dbname = "odontosmart_db";

// Crear conexión
$conn = new mysqli($host, $user, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$mensaje = ""; // variable para guardar el mensaje

// Procesar formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST["nombre_completo"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $rol = $_POST["rol"]; // cliente, administrador, medico

    // Hashear la contraseña
    $hash_password = password_hash($password, PASSWORD_DEFAULT);

    // Buscar id_rol según el nombre del rol
    $sqlRol = "SELECT id_rol FROM roles WHERE nombre = ?";
    $stmtRol = $conn->prepare($sqlRol);
    $stmtRol->bind_param("s", $rol);
    $stmtRol->execute();
    $resultRol = $stmtRol->get_result();

    if ($resultRol->num_rows > 0) {
        $rowRol = $resultRol->fetch_assoc();
        $id_rol = $rowRol["id_rol"];

        // Insertar usuario en la tabla
        $sqlUser = "INSERT INTO usuarios (nombre_completo, email, hash_contrasena, estado, id_rol) 
                    VALUES (?, ?, ?, 'activo', ?)";
        $stmtUser = $conn->prepare($sqlUser);
        $stmtUser->bind_param("sssi", $nombre, $email, $hash_password, $id_rol);

        if ($stmtUser->execute()) {
            $mensaje = "Usuario creado correctamente con rol: $rol";
        } else {
            $mensaje = "Error al crear usuario: " . $conn->error;
        }
    } else {
        $mensaje = "El rol seleccionado no existe en la tabla roles.";
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
