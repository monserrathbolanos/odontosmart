<?php
// gestion_usuarios.php
// Trabaja con las tablas usuarios, roles, clientes y odontologos

session_start();
include('../../config/conexion.php');

$mensaje = "";
$usuario = null;

/* Validar rol permitido */
$rol = $_SESSION['user']['role'] ?? null;
$rolesPermitidos = ['Administrador'];

if (!in_array($rol, $rolesPermitidos)) {
    header('Location: ../../auth/iniciar_sesion.php?error=' . urlencode('Debes iniciar sesión o registrarte.'));
    exit;
}

try {
    // ==============================
    // 1) Buscar usuario por cédula
    // ==============================
    if (isset($_POST["buscar"])) {
        $identificacion = trim($_POST["identificacion"] ?? '');

        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE identificacion = ?");
        if ($stmt) {
            $stmt->bind_param("s", $identificacion);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado && $resultado->num_rows > 0) {
                $usuario = $resultado->fetch_assoc();
            } else {
                $mensaje = "No se encontró usuario con esa identificación.";
            }

            $stmt->close();
        } else {
            $mensaje = "Error al preparar la consulta de búsqueda: " . $conn->error;
        }
    }

    // ==============================
    // 2) Actualizar usuario (vía SP)
    // ==============================
    if (isset($_POST["actualizar"])) {

        $id_usuario      = intval($_POST["id_usuario"]);
        $nombre          = trim($_POST["nombre"] ?? '');
        $apellido1       = trim($_POST["apellido1"] ?? '');
        $apellido2       = trim($_POST["apellido2"] ?? '');
        $email           = trim($_POST["email"] ?? '');
        $telefono        = trim($_POST["telefono"] ?? '');
        $identificacion  = trim($_POST["identificacion"] ?? '');
        $id_rol          = intval($_POST["rol"] ?? 0);

        $ip       = $_SERVER['REMOTE_ADDR']      ?? 'DESCONOCIDA';
        $modulo   = 'gestion_usuarios';
        $ua       = $_SERVER['HTTP_USER_AGENT']  ?? 'DESCONOCIDO';

        // Validaciones básicas
        if ($nombre === '' || $apellido1 === '' || $email === '' || $identificacion === '') {
            $mensaje = "Todos los campos obligatorios deben estar completos.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mensaje = "Correo electrónico inválido.";
        } else {

            // 2.1) Llamar al SP sp_actualizar_usuario
            $stmtSp = $conn->prepare("
                CALL sp_actualizar_usuario(?,?,?,?,?,?,?,?,?,?,?, @p_resultado)
            ");

            if (!$stmtSp) {
                $mensaje = "Error al preparar el procedimiento almacenado: " . $conn->error;
            } else {
                // Tipos: i (id_usuario), s,s,s,s,s,s, i (id_rol), s,s,s
                $stmtSp->bind_param(
                    "issssssisss",
                    $id_usuario,     // p_id_usuario
                    $nombre,         // p_nombre
                    $apellido1,      // p_apellido1
                    $apellido2,      // p_apellido2
                    $email,          // p_email
                    $telefono,       // p_telefono
                    $identificacion, // p_identificacion
                    $id_rol,         // p_id_rol
                    $ip,             // p_ip
                    $modulo,         // p_modulo
                    $ua              // p_user_agent
                );

                if (!$stmtSp->execute()) {
                    $mensaje = "Error al ejecutar el procedimiento almacenado: " . $stmtSp->error;
                }

                $stmtSp->close();
                // Limpiar posibles resultados del CALL
                $conn->next_result();

                // Leer valor OUT @p_resultado
                if (empty($mensaje)) {
                    $res = $conn->query("SELECT @p_resultado AS resultado");
                    if ($res) {
                        $row          = $res->fetch_assoc();
                        $resultado_sp = $row['resultado'] ?? null;
                    } else {
                        $resultado_sp = null;
                    }

                    if ($resultado_sp === 'DUPLICADO') {
                        $mensaje = "Correo o identificación ya está en uso por otro usuario.";
                    } elseif ($resultado_sp === 'OK') {

                        // ================================
                        // 2.2) Lógica especial de CLIENTES
                        // (la de ODONTOLOGOS ya la hace el SP)
                        // ================================
                        if (intval($id_rol) === 3) {
                            // Ver si ya existe cliente para ese usuario
                            $stmtChkCli = $conn->prepare("SELECT id_cliente FROM clientes WHERE id_usuario = ?");
                            if ($stmtChkCli) {
                                $stmtChkCli->bind_param("i", $id_usuario);
                                $stmtChkCli->execute();
                                $resChkCli = $stmtChkCli->get_result();

                                if ($resChkCli && $resChkCli->num_rows === 0) {
                                    // No existe -> insertar nuevo cliente (solo id_usuario; fecha_registro la pone la DB)
                                    $stmtCliIns = $conn->prepare("
                                        INSERT INTO clientes (id_usuario)
                                        VALUES (?)
                                    ");
                                    if ($stmtCliIns) {
                                        $stmtCliIns->bind_param("i", $id_usuario);
                                        $stmtCliIns->execute();
                                        $stmtCliIns->close();
                                    }
                                }
                                $stmtChkCli->close();
                            }
                        }

                        // Mensaje final
                        $mensaje = "Usuario actualizado correctamente.";

                        // Recargar datos del usuario actualizado para mostrarlos
                        $stmtReload = $conn->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
                        if ($stmtReload) {
                            $stmtReload->bind_param("i", $id_usuario);
                            $stmtReload->execute();
                            $resReload = $stmtReload->get_result();
                            if ($resReload && $resReload->num_rows > 0) {
                                $usuario = $resReload->fetch_assoc();
                            }
                            $stmtReload->close();
                        }

                    } else {
                        $mensaje = "Error inesperado al actualizar el usuario. Código: " . ($resultado_sp ?? 'NULL');
                    }
                }
            }
        }
    }

} catch (Throwable $e) {
    // Intentar rollback
    try {
        if (isset($conn) && $conn instanceof mysqli) {
            if (isset($conn) && $conn instanceof mysqli && method_exists($conn, 'in_transaction') && $conn->in_transaction()) {
                try { $conn->rollback(); } catch (Throwable $__ignore) {}
            }
        }
    } catch (Throwable $__ignored) {}

    // Registrar en bitácora
    try {
        if (isset($conn)) { @$conn->close(); }
        include_once '../../config/conexion.php';

        $id_usuario_log = $_SESSION['user']['id_usuario'] ?? null;
        $accion = 'GESTION_USUARIOS_ERROR';
        $modulo = 'usuarios/gestion_usuarios';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
        $detalles = 'Error técnico: ' . $e->getMessage();

        $stmtLog = $conn->prepare("CALL SP_USUARIO_BITACORA(?, ?, ?, ?, ?, ?)");
        if ($stmtLog) {
            $stmtLog->bind_param("isssss", $id_usuario_log, $accion, $modulo, $ip, $user_agent, $detalles);
            $stmtLog->execute();
            $stmtLog->close();
        }
        if (isset($conn)) { @$conn->close(); }
    } catch (Throwable $logError) {
        error_log("Fallo al escribir en bitácora (gestion_usuarios): " . $logError->getMessage());
    }

    $mensaje = "Ocurrió un error técnico. Por favor intente nuevamente.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios</title>
    <!-- FAVICON UNIFICADO -->
    <link rel="icon" href="/odontosmart/assets/img/odonto1.png">
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 0; 
            background: #f5f5f5;
        }
        .navbar {
            width: 220px;
            background-color: #69B7BF;
            height: 100vh;
            padding-top: 20px;
            position: fixed;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            transition: width 0.3s ease;
        }
        .navbar a {
            display: block;
            color: #fff;
            padding: 14px 20px;
            text-decoration: none;
            margin: 10px;
            border-radius: 8px;
            transition: background 0.3s, transform 0.2s;
        }
        .navbar a:hover {
            background-color: #264cbf;
            transform: scale(1.05);
        }
        .content { 
            margin-left: 240px; 
            padding: 20px; 
        }
        .seccion {
            background: linear-gradient(to bottom right, #f5f9fc, #8ef2ffff);
            padding: 20px;
            margin: 15px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        button {
            padding: 8px 15px;
            background: #152FBF;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px 0;
            transition: 0.3s ease-in-out;
        }
        button:hover {
            background: #264CBF;
            transform: scale(1.05);
        }
        input, select {
            padding: 8px;
            margin: 5px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 250px;
        }
        .mensaje {
            padding: 12px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .exito {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .form-group {
            margin: 15px 0;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .logo-navbar {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            width: 140px;
            opacity: 0.9;
        }
        select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 250px;
            transition: all 0.3s ease-in-out;
            font-size: 1em;
            cursor: pointer;
        }
        select:hover {
            border-color: #152FBF;
            transform: scale(1.03);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="navbar">
        <?php include('../../views/navbar.php'); ?>
        <img src="../../assets/img/odonto1.png" class="logo-navbar" alt="Logo OdontoSmart">
    </div>

    <div class="content">
        <div class="seccion">
            <h1 style="color: #69B7BF;">Gestión de Usuarios</h1>
            
            <!-- Búsqueda de usuario -->
            <div class="form-group">
                <h3>Buscar Usuario</h3>
                <form method="POST">
                    <input type="text" name="identificacion" placeholder="Ingrese la identificación" required>
                    <button type="submit" name="buscar">Buscar Usuario</button>
                </form>

                <button onclick="location.href='/odontosmart/modulos/usuarios/admin_crear_usuarios.php'">
                    Crear Usuario
                </button>
            </div>

            <!-- Mensajes -->
            <?php if (!empty($mensaje)): ?>
                <div class="mensaje <?php echo (strpos($mensaje, 'correctamente') !== false ? 'exito' : 'error'); ?>">
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php endif; ?>

            <!-- Formulario de edición -->
            <?php if ($usuario): ?>
            <div class="seccion">
                <h3>Editar Usuario</h3>
                <form method="POST">
                    <input type="hidden" name="id_usuario" value="<?= (int)$usuario['id_usuario'] ?>">
                    
                    <div class="row g-2">
                        <div class="col-md-4 form-group">
                            <label>Nombre:</label>
                            <input type="text" name="nombre" 
                                   value="<?= htmlspecialchars($usuario['nombre'] ?? '') ?>" 
                                   placeholder="Nombre" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Apellido 1:</label>
                            <input type="text" name="apellido1" 
                                   value="<?= htmlspecialchars($usuario['apellido1'] ?? '') ?>" 
                                   placeholder="Apellido 1" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Apellido 2:</label>
                            <input type="text" name="apellido2" 
                                   value="<?= htmlspecialchars($usuario['apellido2'] ?? '') ?>" 
                                   placeholder="Apellido 2">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" name="email" 
                               value="<?= htmlspecialchars($usuario['email'] ?? '') ?>" 
                               placeholder="Email" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Teléfono:</label>
                        <input type="text" name="telefono" 
                               value="<?= htmlspecialchars($usuario['telefono'] ?? '') ?>" 
                               placeholder="Teléfono" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Identificación:</label>
                        <input type="text" name="identificacion" 
                               value="<?= htmlspecialchars($usuario['identificacion'] ?? '') ?>" 
                               placeholder="Identificación" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Rol:</label>
                        <select name="rol" required>
                            <option value="1" <?= ($usuario['id_rol']==1 ? "selected" : "") ?>>Administrador</option>
                            <option value="2" <?= ($usuario['id_rol']==2 ? "selected" : "") ?>>Médico</option>
                            <option value="3" <?= ($usuario['id_rol']==3 ? "selected" : "") ?>>Cliente</option>
                            <option value="4" <?= ($usuario['id_rol']==4 ? "selected" : "") ?>>Recepcionista</option>
                        </select>
                    </div>

                    <button type="submit" name="actualizar">Actualizar Usuario</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
