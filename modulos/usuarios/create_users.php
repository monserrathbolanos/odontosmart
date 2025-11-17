<?php
// --- Create_users.php ---
// Inicia sesión y carga dependencias necesarias

require '../../config/conexion.php'; // Conexión a la base de datos
require '../../config/csrf.php';  // Protección contra ataques CSRF

session_start();

// Genera un token CSRF para proteger el formulario

$csrf_token = generate_csrf_token();

// Obtener roles de la base de datos
$roles = [];
$result = $conn->query("SELECT id_rol, nombre FROM roles where id_rol=1");
while ($row = $result->fetch_assoc()) {
    $roles[] = $row;  //Guarda los roles disponibles en un arreglo
}

// --- Procesar formulario cuando se envía vía POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

      // Verifica el token CSRF
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Token de seguridad inválido. Por favor, vuelve a intentarlo.";
    } else {

        // Sanitiza y valida los datos del formulario

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $role_id = intval($_POST['role'] ?? 0);
        $cedula = trim($_POST['cedula'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        
          // Validaciones básicas

        if ($username === '' || $email === '' || $password === '') {
            $error = "Todos los campos son obligatorios.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Correo inválido.";
        } elseif ($password !== $confirm_password) {
            $error = "Las contraseñas no coinciden.";
        } elseif (strlen($password) < 6) {
            $error = "La contraseña debe tener al menos 6 caracteres.";
        } else {
            // Verifica que el rol seleccionado exista

            $stmtRole = $conn->prepare("SELECT id_rol FROM roles WHERE id_rol = ?");
            $stmtRole->bind_param("i", $role_id);
            $stmtRole->execute();
            if ($stmtRole->get_result()->num_rows === 0) {
                $error = "Rol inválido.";
            } else {
                 // Verifica si el usuario o correo ya existen
                $stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ? OR nombre_completo = ? OR cedula =?");
                $stmt->bind_param("ssi", $email, $username, $cedula);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    $error = "Usuario, cedula o correo ya se encuentra en uso.";
                } else {

                    // Inserta el nuevo usuario en la base de datos
                    $hash = password_hash($password, PASSWORD_DEFAULT);  // Encripta la contraseña

                    $stmtInsert = $conn->prepare("
                        INSERT INTO usuarios (nombre_completo, email, hash_contrasena, estado, id_rol, telefono, cedula)
                        VALUES (?, ?, ?, 'activo', ?,?,?)
                    ");
                    $stmtInsert->bind_param("sssiii", $username, $email, $hash, $role_id, $telefono, $cedula);
                    if ($stmtInsert->execute()) {
                        $success = "✅ Usuario creado exitosamente.";
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
  background: linear-gradient(270deg, #152fbf, #264cbf, #182940, #69b7bf); /* Gradiente animado */
   /* background-image: url(' Odonto.png');  */
  background-size: 300% 300%;
  animation: rgbFlow 150s ease infinite;  /* Movimiento suave del fondo */

  font-family: 'Poppins', sans-serif;
  color: #ffffff;
}

</style>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow-lg p-4" style="max-width: 500px; margin: auto;">
        <h3 class="text-center mb-4"><strong>Crear Nuevo Usuario</strong></h3>

        <!-- Mensajes de éxito o error -->
        <?php if (isset($success)): ?>
            <div class="alert alert-success text-center"><?= htmlspecialchars($success) ?></div>
        <?php elseif (isset($error)): ?>
            <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

            <!-- Formulario de registro -->
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
             
            <!-- Campo: Nombre completo -->

            <div class="mb-3">
                <label for="username" class="form-label">Nombre completo</label>
                <input type="text" name="username" id="username" class="form-control"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
            </div>

              <!-- Campo: Correo electrónico -->

            <div class="mb-3">
                <label for="email" class="form-label">Correo electrónico</label>
                <input type="email" name="email" id="email" class="form-control"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
               
            <!-- Campo: Cédula -->

            <div class="mb-3">
                <label for="cedula" class="form-label">Cédula</label>
                <input type="cedula" name="cedula" id="cedula" class="form-control"
                       value="<?= htmlspecialchars($_POST['cedula'] ?? '') ?>" required>
            </div>

               <!-- Campo: Teléfono -->

            <div class="mb-3">
                <label for="telefono" class="form-label">Teléfono</label>
                <input type="telefono" name="telefono" id="telefono" class="form-control" required>
            </div>

               <!-- Campo: Contraseña -->

            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>

              <!-- Campo: Confirmar contraseña -->

            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirmar contraseña</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
            </div>
              
            <!-- Campo: Rol -->

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
               
            <!-- Botones de acción -->
            <button type="submit" class="btn btn-success w-100">Crear usuario</button>
            <a href="../../index.php" class="btn btn-primary w-100 mt-2">Volver al inicio</a>
            
            <a href="../../auth/login.php" class="btn btn-secondary  W-500 mt-2" style="width: 450px;">Iniciar sesión</a>
            

            
        </form>

        
   </div>
           
    </div>

    
</div>

</body>
</html>