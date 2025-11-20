<?php
// --- Create_users.php ---
 
require '../../config/conexion.php';
require '../../config/csrf.php';
 
session_start();
 
$csrf_token = generate_csrf_token();
 
// Obtener roles
$roles = [];
$result = $conn->query("SELECT id_rol, nombre FROM roles WHERE id_rol = 3");
while ($row = $result->fetch_assoc()) {
    $roles[] = $row;
}
 
// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Token de seguridad inválido.";
    } else {
 
        // Recuperar datos
        $nombre_completo   = trim($_POST['nombre_completo'] ?? '');
        $email             = trim($_POST['email'] ?? '');
        $password          = $_POST['password'] ?? '';
        $confirm_password  = $_POST['confirm_password'] ?? '';
        $role_id           = intval($_POST['role'] ?? 0);
        $identificacion    = trim($_POST['identificacion'] ?? '');
        $telefono          = trim($_POST['telefono'] ?? '');
 
        // Validaciones
        if ($nombre_completo === '' || $email === '' || $password === '') {
            $error = "Todos los campos son obligatorios.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Correo inválido.";
        } elseif ($password !== $confirm_password) {
            $error = "Las contraseñas no coinciden.";
        } elseif (strlen($password) < 6) {
            $error = "La contraseña debe tener al menos 6 caracteres.";
        } else {
 
            // Validar rol
            $stmtRole = $conn->prepare("SELECT id_rol FROM roles WHERE id_rol = ?");
            $stmtRole->bind_param("i", $role_id);
            $stmtRole->execute();
 
            if ($stmtRole->get_result()->num_rows === 0) {
                $error = "Rol inválido.";
            } else {
 
                // Validar si existe usuario/correo/identificación
                $stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ? OR nombre_completo = ? OR identificacion = ?");
                $stmt->bind_param("sss", $email, $nombre_completo, $identificacion);
                $stmt->execute();
 
                if ($stmt->get_result()->num_rows > 0) {
                    $error = "Usuario, identificación o correo ya está en uso.";
                } else {
 
                    // Insertar usuario
                    $hash = password_hash($password, PASSWORD_DEFAULT);
 
                    $stmtInsert = $conn->prepare("
                        INSERT INTO usuarios (nombre_completo, email, password, id_rol, telefono, identificacion)
                        VALUES (?, ?, ?,?, ?, ?)
                    ");
 
                    $stmtInsert->bind_param(
                        "sssiss",
                        $nombre_completo,
                        $email,
                        $hash,
                        $role_id,
                        $telefono,
                        $identificacion
                    );
 
                    if ($stmtInsert->execute()) {
                        $success = "Usuario creado exitosamente.";
                    } else {
                        $error = "Error al crear usuario.";
                    }
 
                    $stmtInsert->close();
                }
 
                $stmt->close();
            }
 
            $stmtRole->close();
        }
    }
}
 
$conn->close();
?>
 
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Usuario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
 
<style>
body {
    background: linear-gradient(270deg, #152FBF, #264CBF, #182940, #D5E7F2, #69B7BF);
    background-size: 300% 300%;
    animation: rgbFlow 150s ease infinite;
    font-family: 'Poppins', sans-serif;
    color: #ffffff;
}
</style>
 
<body class="bg-light">
 
<div class="container mt-5">
    <div class="card shadow-lg p-4" style="max-width: 500px; margin: auto;">
        <h3 class="text-center mb-4"><strong>Crear Nuevo Usuario</strong></h3>
 
        <?php if (isset($success)): ?>
            <div class="alert alert-success text-center"><?= htmlspecialchars($success) ?></div>
        <?php elseif (isset($error)): ?>
            <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
 
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
 
            <div class="mb-3">
                <label for="nombre_completo" class="form-label">Nombre completo</label>
                <input type="text" name="nombre_completo" id="nombre_completo" class="form-control"
                       value="<?= htmlspecialchars($_POST['nombre_completo'] ?? '') ?>" required>
            </div>
 
            <div class="mb-3">
                <label for="email" class="form-label">Correo electrónico</label>
                <input type="email" name="email" id="email" class="form-control"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
 
            <div class="mb-3">
                <label for="identificacion" class="form-label">Cédula</label>
                <input type="text" name="identificacion" id="identificacion" class="form-control"
                       value="<?= htmlspecialchars($_POST['identificacion'] ?? '') ?>" required>
            </div>
 
            <div class="mb-3">
                <label for="telefono" class="form-label">Teléfono</label>
                <input type="text" name="telefono" id="telefono" class="form-control"
                       value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>" required>
            </div>
 
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>
 
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirmar contraseña</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
            </div>
 
            <div class="mb-3">
                <label for="role" class="form-label">Rol</label>
                <select name="role" id="role" class="form-select" required>
                    <option value="">Seleccione un rol</option>
                    <?php foreach ($roles as $rol): ?>
                        <option value="<?= $rol['id_rol'] ?>"
                            <?= (($_POST['role'] ?? '') == $rol['id_rol']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($rol['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
 
            <button type="submit" class="btn btn-success w-100">Crear usuario</button>
            <a href="/odontosmart/index.php" class="btn btn-primary w-100 mt-2">Volver al inicio</a>
            <a href="../../auth/login.php" class="btn btn-secondary w-100 mt-2">Iniciar sesión</a>
 
        </form>
 
    </div>
</div>
 
</body>
</html>