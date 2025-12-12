<?php
// --- crear_usuarios.php ---
// Crear usuarios desde el rol Administrador

require '../../config/conexion.php'; // Conexión a la base de datos
require '../../config/csrf.php';     // Protección CSRF

session_start();

/* Validar rol permitido: solo Administrador */
$rolUsuario = $_SESSION['user']['role'] ?? null;
$rolesPermitidos = ['Administrador'];

if (!in_array($rolUsuario, $rolesPermitidos)) {
    header('Location: ../../auth/iniciar_sesion.php?error=' . urlencode('Debes iniciar sesión o registrarte.'));
    exit;
}

// Genera un token CSRF para proteger el formulario
$csrf_token = generate_csrf_token();

// Obtener roles de la base de datos
$roles = [];
$result = $conn->query("SELECT id_rol, nombre FROM roles");
while ($row = $result->fetch_assoc()) {
    $roles[] = $row;  // Guarda los roles disponibles en un arreglo
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Verificación del token CSRF
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Token de seguridad inválido. Por favor, vuelve a intentarlo.";
    } else {

        // Recuperar datos del formulario
        $nombre         = trim($_POST['nombre'] ?? '');
        $apellido1      = trim($_POST['apellido1'] ?? '');
        $apellido2      = trim($_POST['apellido2'] ?? '');
        $email          = trim($_POST['email'] ?? '');
        $password       = $_POST['password'] ?? '';
        $confirm_pass   = $_POST['confirm_password'] ?? '';
        $role_id        = intval($_POST['role'] ?? 0);
        $tipo_doc       = $_POST['tipo_doc'] ?? '';
        $identificacion = trim($_POST['identificacion'] ?? '');
        $telefono       = trim($_POST['telefono'] ?? '');

        // =========================
        // VALIDACIONES
        // =========================

        if ($nombre === '' || $apellido1 === '' || $email === '' || $password === '') {
            $error = "Todos los campos obligatorios deben estar completos.";
        }

        // Validación del tipo de documento y formato
        if (!isset($error)) {

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
        }

        // Validación de correo
        if (!isset($error) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Correo inválido.";
        }

        // Validación de teléfono
        if (!isset($error) && !preg_match('/^\+\d{8,15}$/', $telefono)) {
            $error = "El teléfono debe tener solo números y código de país. Ej: +50688889999";
        }

        // Validación fuerte de contraseña
        if (
            !isset($error) &&
            !preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#._-])[A-Za-z\d@$!%*?&#._-]{8,}$/', $password)
        ) {
            $error = "La contraseña debe tener mínimo 8 caracteres, una mayúscula, un número y un carácter especial.";
        }

        // Confirmación de contraseña
        if (!isset($error) && $password !== $confirm_pass) {
            $error = "Las contraseñas no coinciden.";
        }

        // Validar rol existe
        if (!isset($error)) {
            $stmtRole = $conn->prepare("SELECT id_rol FROM roles WHERE id_rol = ?");
            $stmtRole->bind_param("i", $role_id);
            $stmtRole->execute();
            if ($stmtRole->get_result()->num_rows === 0) {
                $error = "Rol inválido.";
            }
            $stmtRole->close();
        }

        // Si hasta aquí no hay error, continuamos
        if (!isset($error)) {

            // Normaliza tipo_doc
            switch ($tipo_doc) {
                case "cedula":    $tipo_doc_norm = "CEDULA";    break;
                case "dimex":     $tipo_doc_norm = "DIMEX";     break;
                case "pasaporte": $tipo_doc_norm = "PASAPORTE"; break;
                case "juridica":  $tipo_doc_norm = "RUC";       break;
                default:          $tipo_doc_norm = strtoupper($tipo_doc); break;
            }

            // Hashear contraseña
            $hash = password_hash($password, PASSWORD_DEFAULT);

            // Datos para bitácora
            $ip_cliente = $_SERVER['REMOTE_ADDR'] ?? 'DESCONOCIDA';
            $modulo     = 'Gestión de usuarios - Crear usuario';
            $userAgent  = $_SERVER['HTTP_USER_AGENT'] ?? 'DESCONOCIDO';

            // =========================
            // LLAMADA AL SP sp_crear_usuario
            // =========================

            $stmtSp = $conn->prepare("
                CALL sp_crear_usuario(?,?,?,?,?,?,?,?,?,?,?,?, @p_resultado)
            ");

            if (!$stmtSp) {
                $error = "Error al preparar el procedimiento almacenado: " . $conn->error;
            } else {

                // 11 strings + 1 int => "ssssssssisss"
                $stmtSp->bind_param(
                    "ssssssssisss",
                    $nombre,          // p_nombre
                    $apellido1,       // p_apellido1
                    $apellido2,       // p_apellido2
                    $email,           // p_email
                    $telefono,        // p_telefono
                    $tipo_doc_norm,   // p_tipo_doc
                    $identificacion,  // p_identificacion
                    $hash,            // p_password
                    $role_id,         // p_id_rol (i)
                    $ip_cliente,      // p_ip
                    $modulo,          // p_modulo
                    $userAgent        // p_user_agent
                );

                if (!$stmtSp->execute()) {
                    $error = "Error al ejecutar el procedimiento almacenado: " . $stmtSp->error;
                }

                $stmtSp->close();
                $conn->next_result(); // Limpia resultado del CALL

                // Leer OUT p_resultado
                if (!isset($error)) {
                    $res = $conn->query("SELECT @p_resultado AS resultado");
                    $row = $res->fetch_assoc();
                    $resultado = $row['resultado'] ?? null;

                    if ($resultado === 'DUPLICADO') {
                        $error = "Usuario, identificación o correo ya está en uso.";
                    } elseif ($resultado === 'OK') {

                        // Buscar id_usuario recién creado para usarlo en clientes
                        $new_user_id = 0;
                        $stmtId = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ? LIMIT 1");
                        if ($stmtId) {
                            $stmtId->bind_param("s", $email);
                            $stmtId->execute();
                            $rowId = $stmtId->get_result()->fetch_assoc();
                            $new_user_id = $rowId['id_usuario'] ?? 0;
                            $stmtId->close();
                        }

                     // Si el rol es Cliente (3), insertamos también en la tabla clientes
// Tabla clientes: id_cliente (AI), id_usuario, fecha_registro (DEFAULT CURRENT_TIMESTAMP)
if (intval($role_id) === 3 && $new_user_id > 0) {
    $stmtCliente = $conn->prepare("
        INSERT INTO clientes (id_usuario)
        VALUES (?)
    ");
    if ($stmtCliente) {
        $stmtCliente->bind_param("i", $new_user_id);
        if (!$stmtCliente->execute()) {
            $error = "Usuario creado, pero no se pudo crear registro de cliente: " . $stmtCliente->error;
        }
        $stmtCliente->close();
    } else {
        $error = "Usuario creado, pero no se pudo preparar inserción en clientes: " . $conn->error;
    }
}


                        if (!isset($error)) {
                            $success = "Usuario creado exitosamente.";
                        }

                    } else {
                        $error = "Error inesperado al crear usuario (resultado SP: " . ($resultado ?? 'NULL') . ").";
                    }
                }
            }
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
    background: #ffffffaf;
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

h3 {
    color: #69B7BF;
    margin-bottom: 25px;
    text-align: center;
}

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

.alert {
    text-align: center;
    font-weight: bold;
}

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
    <div class="card shadow-lg p-4">
        <h3><strong>Crear Nuevo Usuario</strong></h3>

        <!-- Mensajes de éxito o error -->
        <?php if (isset($success)): ?>
            <div class="alert alert-success text-center"><?= htmlspecialchars($success) ?></div>
        <?php elseif (isset($error)): ?>
            <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <div class="row g-2">
                <div class="col-md-4 mb-3">
                    <label for="nombre" class="form-label">Nombre</label>
                    <input type="text" name="nombre" id="nombre" class="form-control"
                           value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="apellido1" class="form-label">Apellido 1</label>
                    <input type="text" name="apellido1" id="apellido1" class="form-control"
                           value="<?= htmlspecialchars($_POST['apellido1'] ?? '') ?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="apellido2" class="form-label">Apellido 2</label>
                    <input type="text" name="apellido2" id="apellido2" class="form-control"
                           value="<?= htmlspecialchars($_POST['apellido2'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Correo electrónico</label>
                <input type="email" name="email" id="email" class="form-control"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>

            <div class="mb-3">
                <label for="tipo_doc" class="form-label">Tipo de Identificación</label>
                <select name="tipo_doc" id="tipo_doc" class="form-select" required>
                    <option value="">Seleccione</option>
                    <option value="cedula"   <?= (($_POST['tipo_doc'] ?? '')=='cedula')?'selected':'' ?>>Cédula CR</option>
                    <option value="dimex"    <?= (($_POST['tipo_doc'] ?? '')=='dimex')?'selected':'' ?>>DIMEX</option>
                    <option value="pasaporte"<?= (($_POST['tipo_doc'] ?? '')=='pasaporte')?'selected':'' ?>>Pasaporte</option>
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

            <div class="mb-3">
                <label for="role" class="form-label">Rol</label>
                <select name="role" id="role" class="form-select" required>
                    <option value="">Seleccione un rol</option>
                    <?php foreach ($roles as $rolRow): ?>
                        <option value="<?= $rolRow['id_rol'] ?>"
                            <?= (($_POST['role'] ?? '') == $rolRow['id_rol']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($rolRow['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-success w-100">Crear usuario</button>
            <a href="/odontosmart/modulos/usuarios/gestion_usuarios.php" class="btn btn-primary w-100 mt-2">Volver</a>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const selectTipo    = document.getElementById("tipo_doc");
    const inputIdent    = document.getElementById("identificacion");
    const msg           = document.getElementById("msgFormato");
    const inputTelefono = document.getElementById("telefono");
    const msgTelefono   = document.getElementById("msgTelefono");
    const inputPassword = document.getElementById("password");
    const inputConfirm  = document.getElementById("confirm_password");
    const msgPassword   = document.getElementById("msgPassword");
    const msgConfirm    = document.getElementById("msgConfirmPassword");

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

    selectTipo.addEventListener("change", function () {
        inputIdent.value = "";
        msg.textContent = "";
        inputIdent.style.borderColor = "";
    });

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

    inputPassword.addEventListener("input", function () {
        const value = this.value;
        const tieneMayuscula = /[A-Z]/.test(value);
        const tieneNumero    = /[0-9]/.test(value);
        const tieneEspecial  = /[@$!%*?&#._-]/.test(value);
        const tieneLongitud  = value.length >= 8;

        if (!tieneLongitud || !tieneMayuscula || !tieneNumero || !tieneEspecial) {
            msgPassword.textContent = "❌ Contraseña débil";
            msgPassword.style.color = "red";
        } else {
            msgPassword.textContent = "✅ Contraseña segura";
            msgPassword.style.color = "green";
        }
    });

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
