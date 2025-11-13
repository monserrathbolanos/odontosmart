<?php
// Conexión a la base de datos
$conn = new mysqli("localhost", "root", "", "odontosmart_db");
if ($conn->connect_error) { die("Error: " . $conn->connect_error); }

$mensaje = "";
$usuario = null;

// Buscar usuario por cédula
if (isset($_POST["buscar"])) {
    $cedula = $_POST["cedula"];
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE cedula=?");
    $stmt->bind_param("s", $cedula);
    $stmt->execute();
    $resultado = $stmt->get_result();
    if ($resultado->num_rows > 0) {
        $usuario = $resultado->fetch_assoc();
    } else {
        $mensaje = "No se encontró usuario con esa cédula.";
    }
}

// Actualizar usuario
if (isset($_POST["actualizar"])) {
    $stmt = $conn->prepare("UPDATE usuarios SET nombre_completo=?, email=?, telefono=?, cedula=?, id_rol=? WHERE id_usuario=?");
    $stmt->bind_param("ssssii", $_POST["nombre"], $_POST["email"], $_POST["telefono"], $_POST["cedula"], $_POST["rol"], $_POST["id_usuario"]);
    if ($stmt->execute()) { $mensaje = "Usuario actualizado correctamente."; }
    else { $mensaje = "Error al actualizar."; }
}
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Gestión de Usuarios</title></head>
<body>
<h2>Gestión de Usuarios</h2>

<form method="POST">
    <input type="text" name="cedula" placeholder="Cédula" required>
    <button type="submit" name="buscar">Buscar</button>
</form>

<?php if ($usuario) { ?>
<form method="POST">
    <input type="hidden" name="id_usuario" value="<?= $usuario['id_usuario'] ?>">
    <p><input type="text" name="nombre" value="<?= $usuario['nombre_completo'] ?>" placeholder="Nombre" required></p>
    <p><input type="email" name="email" value="<?= $usuario['email'] ?>" placeholder="Email" required></p>
    <p><input type="text" name="telefono" value="<?= $usuario['telefono'] ?>" placeholder="Teléfono" required></p>
    <p><input type="text" name="cedula" value="<?= $usuario['cedula'] ?>" placeholder="Cédula" required></p>
    <p>
        <select name="rol" required>
            <option value="1" <?= ($usuario['id_rol']==1?"selected":"") ?>>Cliente</option>
            <option value="2" <?= ($usuario['id_rol']==2?"selected":"") ?>>Administrador</option>
            <option value="3" <?= ($usuario['id_rol']==3?"selected":"") ?>>Médico</option>
        </select>
    </p>
    <button type="submit" name="actualizar">Actualizar</button>
</form>
<?php } ?>

<p><strong><?= $mensaje ?></strong></p>
</body>
</html>

