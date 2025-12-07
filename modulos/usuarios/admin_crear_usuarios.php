<?php
// --- Login para crear usuarios desde el rol de administrador, se debe agregaar el boton en gestion de usuarios, que rerefecnie a este fichero
// Inicia sesión y carga dependencias necesarias
 
require '../../config/conexion.php'; // Conexión a la base de datos
require '../../config/csrf.php';  // Protección contra ataques CSRF
 
session_start();
 
// Genera un token CSRF para proteger el formulario
$csrf_token = generate_csrf_token();
 
// Obtener roles de la base de datos
$roles = [];
$result = $conn->query("SELECT id_rol, nombre FROM roles");
while ($row = $result->fetch_assoc()) {
    $roles[] = $row;  //Guarda los roles disponibles en un arreglo
}
 
// Formulario para enviar datos a la base de datos cuando se crea un nuevo usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 
    //Verificaion del token CSRF
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Token de seguridad inválido. Por favor, vuelve a intentarlo.";
    } else {
 
        //Limpia y recupera los datos del formulario para que no hayan errores cuando se envie a la base de datos
        $nombre_completo = trim($_POST['nombre_completo'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $role_id = intval($_POST['role'] ?? 0);
        $identificacion = trim($_POST['identificacion'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
 
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

        // Validación del email (IMPORTANTE: después del doc)

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Correo inválido.";
    }

     // VALIDACIÓN TELÉFONO
        elseif (!preg_match('/^\+\d{8,15}$/', $telefono)) {
    $error = "El teléfono debe tener solo números y código de país. Ej: +50688889999";
    }

    //  VALIDACIÓN FUERTE DE CONTRASEÑA (OBLIGATORIA)
elseif (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#._-])[A-Za-z\d@$!%*?&#._-]{8,}$/', $password)) {
    $error = "La contraseña debe tener mínimo 8 caracteres, una mayúscula, un número y un carácter especial.";
}

// CONFIRMACIÓN
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
 
                    // Insertar usuario Y AGREGAR IP PARA EL CALL
                    $ip_cliente = $_SERVER['REMOTE_ADDR'] ?? 'DESCONOCIDA';

                    $hash = password_hash($password, PASSWORD_DEFAULT);
 
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
    max-width: 500px;
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
 
            <!-- Campo: Cédula -->
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
                <input type="password" name="password" id="password" class="form-control"
                 placeholder="Ej: Odonto*2025" required>

                 <small id="msgPassword" style="font-size:12px;"></small>
            </div>
 
            <div class="mb-3">
                <input type="password" name="confirm_password" id="confirm_password" class="form-control mt-2"
                placeholder="Repita la contraseña" required>

                <small id="msgConfirmPassword" style="font-size:12px;"></small>
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
            <a href="/odontosmart/modulos/usuarios/gestion_usuarios.php" class="btn btn-primary w-100 mt-2">Volver</a>
 
        </form>
 
    </div>
</div>
 
<script>
document.addEventListener("DOMContentLoaded", function () {

    const selectTipo = document.getElementById("tipo_doc");
    const inputIdent = document.getElementById("identificacion");
    const msg = document.getElementById("msgFormato");

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

    // VALIDACIÓN TELÉFONO SOLO NÚMEROS + CÓDIGO PAÍS
const inputTelefono = document.getElementById("telefono");
const msgTelefono = document.getElementById("msgTelefono");

inputTelefono.addEventListener("input", function () {
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

//  VALIDACIÓN DE CONTRASEÑA SEGURA
const inputPassword = document.getElementById("password");
const inputConfirm = document.getElementById("confirm_password");
const msgPassword = document.getElementById("msgPassword");
const msgConfirm = document.getElementById("msgConfirmPassword");

inputPassword.addEventListener("input", function () {
    const value = this.value;

    const tieneMayuscula = /[A-Z]/.test(value);
    const tieneNumero = /[0-9]/.test(value);
    const tieneEspecial = /[@$!%*?&#._-]/.test(value);
    const tieneLongitud = value.length >= 8;

    if (!tieneLongitud || !tieneMayuscula || !tieneNumero || !tieneEspecial) {
        msgPassword.textContent = "❌ Contraseña débil";
        msgPassword.style.color = "red";
    } else {
        msgPassword.textContent = "✅ Contraseña segura";
        msgPassword.style.color = "green";
    }
});

// CONFIRMACIÓN DE CONTRASEÑA
inputConfirm.addEventListener("input", function () {
    if (this.value !== inputPassword.value) {
        msgConfirm.textContent = "❌ Las contraseñas no coinciden";
        msgConfirm.style.color = "red";
    } else {
        msgConfirm.textContent = "✅ Las contraseñas coinciden";
        msgConfirm.style.color = "green";
    }
});


});
</script>

</body>
</html>