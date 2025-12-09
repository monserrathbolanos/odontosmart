<?php
// --- crear_usuarios.php ---
 
require '../../config/conexion.php';
require '../../config/csrf.php';
 
session_start();

 // Genera un token CSRF para proteger el formulario
$csrf_token = generate_csrf_token();
 
// Obtener roles
$roles = [];
$result = $conn->query("SELECT id_rol, nombre FROM roles WHERE id_rol = 3");
while ($row = $result->fetch_assoc()) {
    $roles[] = $row;
}
 
// Formulario para enviar datos a la base de datos cuando se crea un nuevo usuario
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
        }

        
// Validación del documento según tipo
$tipo_doc = $_POST['tipo_doc'] ?? '';

$patrones = [
    "cedula"   => "/^[1-9]-\d{4}-\d{4}$/",
    "dimex"    => "/^\d{8}[A-Z]$/",
    "pasaporte"=> "/^[a-zA-Z0-9]{6,9}$/",
    "juridica" => "/^\d{1}-\d{3}-\d{6}$/"
];

if (!isset($patrones[$tipo_doc])) {
    $error = "Debe seleccionar un tipo de identificación válido.";
} elseif (!preg_match($patrones[$tipo_doc], $identificacion)) {
    $error = "Formato inválido para el tipo de documento seleccionado.";
}

// Validación del email
elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Correo inválido.";
}

//  TELÉFONO CON SOLO NÚMEROS Y CÓDIGO DE PAÍS
elseif (!preg_match('/^\+\d{8,15}$/', $telefono)) {
    $error = "El teléfono debe tener solo números y código de país. Ej: +50688889999";
}


// VALIDACIÓN FUERTE DE CONTRASEÑA (OBLIGATORIA)
elseif (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#._-])[A-Za-z\d@$!%*?&#._-]{8,}$/', $password)) {
    $error = "La contraseña debe tener mínimo 8 caracteres, una mayúscula, un número y un carácter especial.";
}

// CONFIRMAR CONTRASEÑA
elseif ($password !== $confirm_password) {
    $error = "Las contraseñas no coinciden.";
}
 else {
 
            // Verifica que el rol seleccionado exista
            $stmtRole = $conn->prepare("SELECT id_rol FROM roles WHERE id_rol = ?");
            $stmtRole->bind_param("i", $role_id);
            $stmtRole->execute();
 
            if ($stmtRole->get_result()->num_rows === 0) {
                $error = "Rol inválido.";
            } else {
 
                // Validar si existe usuario/correo/identificación para que no hayan duplicados
                $stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ? OR nombre_completo = ? OR identificacion = ?");
                $stmt->bind_param("sss", $email, $nombre_completo, $identificacion);
                $stmt->execute();
 
                if ($stmt->get_result()->num_rows > 0) {
                    $error = "Usuario, identificación o correo ya está en uso.";
                } else {
 
                    // Insertar IP para el call y usuario

                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $ip_cliente = $_SERVER['REMOTE_ADDR'] ?? 'DESCONOCIDA';
 
                  //Aqui se realiza el ingreso del usuario usando el procedimiento almacenado sp_crear_usuario.
                $stmtCheck = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ? OR nombre_completo = ? OR identificacion = ?");
                $stmtCheck->bind_param("sss", $email, $nombre_completo, $identificacion);
                $stmtCheck->execute();

                    if ($stmtCheck->get_result()->num_rows > 0) {
                         $error = "Usuario, identificación o correo ya está en uso.";
                    } else {

                 // 2)Se encripta la contraseña y se llama al procedimiento almacenado para crear el usuario.
                $hash = password_hash($password, PASSWORD_DEFAULT);

// normaliza antes de llamar SP:
switch ($tipo_doc) {
    case "cedula": $tipo_doc = "CEDULA"; break;
    case "dimex":  $tipo_doc = "DIMEX"; break;
    case "pasaporte": $tipo_doc = "PASAPORTE"; break;
    case "juridica": $tipo_doc = "RUC"; break;
}

//Se llama al procedimiento almacenado
$stmtSp = $conn->prepare("CALL sp_crear_usuario(?,?,?,?,?,?,?,?, @resultado)");

$stmtSp->bind_param(
    "ssssssis",
    $nombre_completo,
    $email,
    $telefono,
    $tipo_doc,
    $identificacion,
    $hash,
    $role_id,
    $ip_cliente
);

$stmtSp->execute();

                 $res = $conn->query("SELECT @resultado AS res")->fetch_assoc();
                 $resultado = $res['res'];

    if ($resultado === "OK") {
        $success = "Usuario creado exitosamente.";
    } elseif ($resultado === "DUPLICADO") {
        $error = "Usuario, identificación o correo ya está en uso.";
    } else {
        $error = "Error inesperado.";
    }

    $stmtSp->close();
}

$stmtCheck->close();
}
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

    <!-- FAVICON -->
    <link rel="icon" type="image/png" href="../../assets/img/odonto1.png">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

 
<style>

/* Estilos generales del cuerpo de la página */
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

/* Tarjeta */
.card {
    background: #ffffffaf; /* Fondo semi-transparente */
    color: #000;
    border-radius: 16px;
    padding: 30px;
    max-width: 200px;
    margin: auto;
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 28px rgba(0,0,0,0.3);
}

/* Títulos */
h3 {
    color: #69B7BF;
    margin-bottom: 25px;
    text-align: center;
}

/* Inputs y select */
input, select {
    padding: 10px 12px;
    border-radius: 8px;
    border: 1px solid #ddd;
    width: 100%;
    font-size: 1em;
    margin-bottom: 15px;
    transition: all 0.3s ease-in-out;
}

input:focus, select:focus {
    border-color: #152FBF;
    transform: scale(1.02);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    outline: none;
}

/* Botones */
.btn {
    border-radius: 8px;
    font-weight: bold;
    transition: all 0.3s ease;
}

.btn-success {
    background: #69B7BF;
    border: none;
    color: #fff;
}

.btn-success:hover {
    background: #264CBF;
    transform: scale(1.05);
}

.btn-primary {
    background: #152FBF;
    border: none;
    color: #fff;
}

.btn-primary:hover {
    background: #264CBF;
    transform: scale(1.05);
}

.btn-secondary {
    background: #182940;
    border: none;
    color: #fff;
}

.btn-secondary:hover {
    background: #264CBF;
    transform: scale(1.05);
}

/* Alertas */
.alert {
    text-align: center;
    font-weight: bold;
}

/* Media queries */
@media (max-width: 576px) {
    .card {
        padding: 20px;
    }
    h3 {
        font-size: 1.5em;
    }
}

</style>
 
<body class="bg-light">
 
<div class="container mt-5">
    <div class="card shadow-lg p-4" style="max-width: 500px; margin: auto;">
        <h3 class="text-center mb-4"><strong>Crear Usuario</strong></h3>
        
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
                <label for="nombre_completo" class="form-label">Nombre completo</label>
                <input type="text" name="nombre_completo" id="nombre_completo" class="form-control"
                       value="<?= htmlspecialchars($_POST['nombre_completo'] ?? '') ?>" required>
            </div>
            
            <!-- Campo: Correo electrónico -->
            <div class="mb-3">
                <label for="email" class="form-label">Correo electrónico</label>
                <input type="email" name="email" id="email" class="form-control"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
 
            <div class="mb-3">
    <label for="tipo_doc" class="form-label">Tipo de Identificación</label>
    <select name="tipo_doc" id="tipo_doc" class="form-select" required>
        <option value="">Seleccione</option>
        <option value="cedula" <?= (($_POST['tipo_doc'] ?? '')=='cedula')?'selected':'' ?>>Cédula CR</option>
        <option value="dimex" <?= (($_POST['tipo_doc'] ?? '')=='dimex')?'selected':'' ?>>DIMEX</option>
        <option value="pasaporte" <?= (($_POST['tipo_doc'] ?? '')=='pasaporte')?'selected':'' ?>>Pasaporte</option>
        <option value="juridica" <?= (($_POST['tipo_doc'] ?? '')=='juridica')?'selected':'' ?>>Cédula Jurídica</option>
    </select>
</div>

<div class="mb-3">
    <label for="identificacion" class="form-label">Número de Identificación</label>
    <input type="text" name="identificacion" id="identificacion" class="form-control"
           value="<?= htmlspecialchars($_POST['identificacion'] ?? '') ?>" required>

    <small id="msgFormato" style="color: red; font-size: 12px;"></small>
</div>
 
            <div class="mb-3">
                <label for="telefono" class="form-label">Teléfono</label>
                <input type="text" name="telefono" id="telefono" class="form-control"
                  placeholder="+50688889999"
                  inputmode="numeric"
                  value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>" required>

<small id="msgTelefono" style="color:red; font-size:12px;"></small>

            </div>
 
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" name="password" id="password" class="form-control"
                 placeholder="Ej: Odonto&2025" required>

                <small id="msgPassword" style="font-size:12px;"></small>
            </div>

            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirmar contraseña</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control mt-2"
                 placeholder="Repita la contraseña" required>

                <small id="msgConfirmPassword" style="font-size:12px;"></small>
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
            <a href="../../auth/iniciar_sesion.php" class="btn btn-secondary w-100 mt-2">Iniciar sesión</a>
 
        </form>
 
    </div>
</div>
 
<script>
document.addEventListener("DOMContentLoaded", function () {

    const selectTipo = document.getElementById("tipo_doc");
    const inputIdent = document.getElementById("identificacion");
    const msg = document.getElementById("msgFormato");

    const inputTelefono = document.getElementById("telefono");
    const msgTelefono = document.getElementById("msgTelefono");

 inputTelefono.addEventListener("input", function () {

    // Elimina TODO lo que no sea número o +
    this.value = this.value.replace(/[^0-9+]/g, "");

    const regexTelefono = /^\+\d{8,15}$/;

    if (!regexTelefono.test(this.value)) {
        this.style.borderColor = "red";
        msgTelefono.textContent = "Formato correcto: +50688889999";
    } else {
        this.style.borderColor = "green";
        msgTelefono.textContent = "";
    }

});
 

    // Expresiones regulares y mensajes claros
    const validaciones = {
        cedula: {
            regex: /^[1-9]-\d{4}-\d{4}$/,
            msg: "Ejemplo válido: 1-2345-6789"
        },
        dimex: {
            regex: /^\d{8}[A-Z]$/,
            msg: "Debe tener 8 dígitos más 1 letra mayúscula. Ej: 12345678A"
        },
        pasaporte: {
            regex: /^[a-zA-Z0-9]{6,9}$/,
            msg: "Entre 6 y 9 caracteres alfanuméricos."
        },
        juridica: {
            regex: /^\d{1}-\d{3}-\d{6}$/,
            msg: "Ejemplo válido: 3-101-123456"
        }
    };

    // Reset cuando cambia tipo
    selectTipo.addEventListener("change", function () {
        inputIdent.value = "";
        msg.textContent = "";
        inputIdent.style.borderColor = "";
    });

    // Validación en tiempo real
    inputIdent.addEventListener("input", function () {
        let tipo = selectTipo.value;

        if (tipo && validaciones[tipo]) {
            if (!validaciones[tipo].regex.test(this.value)) {
                this.style.borderColor = "red";
                msg.textContent = validaciones[tipo].msg;
            } else {
                this.style.borderColor = "green";
                msg.textContent = "";
            }
        }
    });
});
</script>

</body>
</html>