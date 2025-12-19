<?php

// Permite el registro público de usuarios con rol Cliente

session_start();

require '../../config/conexion.php';
require '../../config/csrf.php';

// Configura MySQLi para usar try/catch
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Genera un token CSRF para el formulario
$csrf_token = generate_csrf_token();

// El rol asignado es Cliente
$role_id = 3;

try {

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // Validar CSRF
        if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
            $error = 'Token de seguridad inválido. Intente nuevamente.';
        } else {

            // Datos del formulario
            $nombre         = trim($_POST['nombre'] ?? '');
            $apellido1      = trim($_POST['apellido1'] ?? '');
            $apellido2      = trim($_POST['apellido2'] ?? '');
            $email          = trim($_POST['email'] ?? '');
            $password       = $_POST['password'] ?? '';
            $confirm_pass   = $_POST['confirm_password'] ?? '';
            $tipo_doc       = $_POST['tipo_doc'] ?? '';
            $identificacion = trim($_POST['identificacion'] ?? '');
            $telefono       = trim($_POST['telefono'] ?? '');

            // Validaciones básicas del formulario
            if ($nombre === '' || $apellido1 === '' || $email === '' || $password === '') {
                $error = 'Todos los campos obligatorios deben estar completos.';
            }

            // Valida el formato de la identificación según el tipo
            if (!isset($error)) {
                $patrones = [
                    'cedula'    => '/^[1-9]-\d{4}-\d{4}$/',
                    'dimex'     => '/^\d{8}[A-Z]$/',
                    'pasaporte' => '/^[a-zA-Z0-9]{6,9}$/',
                    'juridica'  => '/^\d{1}-\d{3}-\d{6}$/'
                ];

                if (!isset($patrones[$tipo_doc])) {
                    $error = 'Debe seleccionar un tipo de identificación válido.';
                } elseif (!preg_match($patrones[$tipo_doc], $identificacion)) {
                    $error = 'Formato inválido para el tipo de documento.';
                }
            }

            // Valida el correo electrónico
            if (!isset($error) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Correo inválido.';
            }

            // Validar teléfono
            if (!isset($error) && !preg_match('/^\+\d{8,15}$/', $telefono)) {
                $error = 'Formato de teléfono inválido. Ej: +50688889999';
            }

            // Validar contraseña (mínimo 8, 1 mayúscula, 1 número, 1 especial)
            if (
                !isset($error) &&
                !preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#._-]).{8,}$/', $password)
            ) {
                $error = 'La contraseña no cumple con los requisitos.';
            }

            // Confirmar contraseña
            if (!isset($error) && $password !== $confirm_pass) {
                $error = 'Las contraseñas no coinciden.';
            }

            // Crear usuario (SP)
            if (!isset($error)) {

                // Normalizar tipo de documento
                switch ($tipo_doc) {
                    case 'cedula':    $tipo_doc_norm = 'CEDULA'; break;
                    case 'dimex':     $tipo_doc_norm = 'DIMEX'; break;
                    case 'pasaporte': $tipo_doc_norm = 'PASAPORTE'; break;
                    case 'juridica':  $tipo_doc_norm = 'RUC'; break;
                    default:          $tipo_doc_norm = strtoupper($tipo_doc);
                }

                // Hash contraseña
                $hash = password_hash($password, PASSWORD_DEFAULT);

                // Auditoría básica
                $ip_cliente = $_SERVER['REMOTE_ADDR'] ?? null;
                $modulo     = 'Registro público - Crear usuario';
                $userAgent  = $_SERVER['HTTP_USER_AGENT'] ?? null;

                // Ejecutar SP
                $stmtSp = $conn->prepare('CALL sp_crear_usuario(?,?,?,?,?,?,?,?,?,?,?,?, @p_resultado)');
                $stmtSp->bind_param(
                    'ssssssssisss',
                    $nombre,
                    $apellido1,
                    $apellido2,
                    $email,
                    $telefono,
                    $tipo_doc_norm,
                    $identificacion,
                    $hash,
                    $role_id,
                    $ip_cliente,
                    $modulo,
                    $userAgent
                );

                $stmtSp->execute();
                $stmtSp->close();
                $conn->next_result();

                // Leer resultado del SP
                $res = $conn->query('SELECT @p_resultado AS resultado');
                $row = $res->fetch_assoc();
                $resultado = $row['resultado'] ?? null;

                if ($resultado === 'OK') {
                    $success = 'Usuario creado exitosamente.';
                } elseif ($resultado === 'DUPLICADO') {
                    $error = 'El usuario o correo ya existe.';
                } else {
                    $error = 'No se pudo crear el usuario.';
                }
            }
        }
    }

} catch (Throwable $e) {

    // Intentar rollback si hay transacción activa
    try {
        if (isset($conn) && $conn instanceof mysqli && method_exists($conn, 'in_transaction') && $conn->in_transaction()) {
            try { $conn->rollback(); } catch (Throwable $ignore) {}
        }
    } catch (Throwable $ignore) {}

    // Registrar en bitácora (si existe el SP), sin reventar la app si falla el logging
    try {
        $id_usuario_log = $_SESSION['user']['id_usuario'] ?? null;
        $accion         = 'CREAR_USUARIO_ERROR';
        $modulo         = 'usuarios';
        $ip             = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $userAgent      = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
        $detalles       = 'Error técnico: ' . $e->getMessage();

        if (isset($conn)) { @ $conn->close(); }
        include_once __DIR__ . '/../../config/conexion.php';

        $stmtLog = $conn->prepare('CALL SP_USUARIO_BITACORA(?, ?, ?, ?, ?, ?)');
        if ($stmtLog) {
            $stmtLog->bind_param('isssss', $id_usuario_log, $accion, $modulo, $ip, $userAgent, $detalles);
            $stmtLog->execute();
            $stmtLog->close();
        }

        if (isset($conn)) { @ $conn->close(); }

    } catch (Throwable $logError) {
        error_log('Fallo al escribir en bitácora: ' . $logError->getMessage());
    }

    // Mensaje final para el usuario (limpio)
    $error = 'Ocurrió un error inesperado al crear el usuario. Intente nuevamente.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Usuario</title>

    <!-- FAVICON -->
    <link rel="icon" type="image/png" href="../../assets/img/odonto1.png">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Fuente -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Estilos de esta página -->
    <link rel="stylesheet" href="../../assets/css/crear_usuarios.css">
</head>

<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow-lg" style="max-width: 920px; margin: auto;">
        <div class="card-inner">

            <div class="card-aside">
                <img src="../../assets/img/odonto.png" alt="OdontoSmart">
            </div>

            <div class="card-main">

                <h3 class="text-center mb-4"><strong>Crear Usuario</strong></h3>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success text-center">
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php elseif (isset($error)): ?>
                    <div class="alert alert-danger text-center">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="form-grid">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                    <div class="row g-2 form-full">
                        <div class="col-md-4 mb-3">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input
                                type="text"
                                name="nombre"
                                id="nombre"
                                class="form-control"
                                value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>"
                                required
                            >
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="apellido1" class="form-label">Apellido 1</label>
                            <input
                                type="text"
                                name="apellido1"
                                id="apellido1"
                                class="form-control"
                                value="<?= htmlspecialchars($_POST['apellido1'] ?? '') ?>"
                                required
                            >
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="apellido2" class="form-label">Apellido 2</label>
                            <input
                                type="text"
                                name="apellido2"
                                id="apellido2"
                                class="form-control"
                                value="<?= htmlspecialchars($_POST['apellido2'] ?? '') ?>"
                            >
                        </div>
                    </div>

                    <div class="mb-3 form-full">
                        <label for="email" class="form-label">Correo electrónico</label>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            class="form-control"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            required
                        >
                    </div>

                    <div class="mb-3 form-full">
                        <label for="tipo_doc" class="form-label">Tipo de Identificación</label>
                        <select name="tipo_doc" id="tipo_doc" class="form-select" required>
                            <option value="">Seleccione</option>
                            <option value="cedula"    <?= (($_POST['tipo_doc'] ?? '')=='cedula')?'selected':'' ?>>Cédula CR</option>
                            <option value="dimex"     <?= (($_POST['tipo_doc'] ?? '')=='dimex')?'selected':'' ?>>DIMEX</option>
                            <option value="pasaporte" <?= (($_POST['tipo_doc'] ?? '')=='pasaporte')?'selected':'' ?>>Pasaporte</option>
                            <option value="juridica"  <?= (($_POST['tipo_doc'] ?? '')=='juridica')?'selected':'' ?>>Cédula Jurídica</option>
                        </select>
                    </div>

                    <div class="mb-3 form-full">
                        <label for="identificacion" class="form-label">Número de Identificación</label>
                        <input
                            type="text"
                            name="identificacion"
                            id="identificacion"
                            class="form-control"
                            value="<?= htmlspecialchars($_POST['identificacion'] ?? '') ?>"
                            required
                        >
                        <small id="msgFormato" class="text-danger" style="font-size:12px;"></small>
                    </div>

                    <div class="mb-3 form-full">
                        <label for="telefono" class="form-label">Teléfono</label>
                        <input
                            type="text"
                            name="telefono"
                            id="telefono"
                            class="form-control"
                            placeholder="+50688889999"
                            value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>"
                            required
                        >
                        <small id="msgTelefono" class="text-danger" style="font-size:12px;"></small>
                    </div>

                    <div class="mb-3 form-full">
                        <label for="password" class="form-label">Contraseña</label>
                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="form-control"
                            placeholder="Ej: Odonto&2025"
                            required
                        >
                        <small id="msgPassword" style="font-size:12px;"></small>
                    </div>

                    <div class="mb-3 form-full">
                        <label for="confirm_password" class="form-label">Confirmar contraseña</label>
                        <input
                            type="password"
                            name="confirm_password"
                            id="confirm_password"
                            class="form-control"
                            placeholder="Repita la contraseña"
                            required
                        >
                        <small id="msgConfirmPassword" style="font-size:12px;"></small>
                    </div>

                    <div class="form-actions form-full d-flex gap-2">
                        <button type="submit" class="btn btn-success flex-fill">Crear usuario</button>
                        <a href="/odontosmart/index.php" class="btn btn-primary flex-fill">Volver al inicio</a>
                    </div>

                    <a href="../../auth/iniciar_sesion.php" class="btn btn-secondary form-full mt-2">Iniciar sesión</a>
                </form>

            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {

    const selectTipo    = document.getElementById("tipo_doc");
    const inputIdent    = document.getElementById("identificacion");
    const msgFormato    = document.getElementById("msgFormato");

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
        msgFormato.textContent = "";
        inputIdent.style.borderColor = "";
    });

    inputIdent.addEventListener("input", function () {
        const tipo = selectTipo.value;

        if (tipo && validaciones[tipo]) {
            if (!validaciones[tipo].regex.test(this.value)) {
                this.style.borderColor = "red";
                msgFormato.textContent = validaciones[tipo].msg;
            } else {
                this.style.borderColor = "green";
                msgFormato.textContent = "";
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
            msgPassword.textContent = "Contraseña débil: mínimo 8 caracteres, 1 mayúscula, 1 número y 1 símbolo.";
            msgPassword.style.color = "red";
        } else {
            msgPassword.textContent = "Contraseña válida.";
            msgPassword.style.color = "green";
        }
    });

    inputConfirm.addEventListener("input", function () {
        if (this.value !== inputPassword.value) {
            msgConfirm.textContent = "Las contraseñas no coinciden.";
            msgConfirm.style.color = "red";
        } else {
            msgConfirm.textContent = "Las contraseñas coinciden.";
            msgConfirm.style.color = "green";
        }
    });

});
</script>

</body>
</html>
