<?php
// gestion_usuarios.php, se relaciona con las tablas Usuarios, y Roles (el campo id_rol indica que existe una relación con la tabla roles para asignar el tipo de usuario )
session_start();
include('../../config/conexion.php');
$mensaje = "";
$usuario = null;

/* Validar rol permitido */
$rol = $_SESSION['user']['role'] ?? null;
$rolesPermitidos = ['Administrador']; 

if (!in_array($rol, $rolesPermitidos)) {
    // Aquí decides a dónde mandarlo: login, home o protegido.
    // Si quieres mandarlo al login:
    header('Location: ../../auth/iniciar_sesion.php?error=' . urlencode('Debes iniciar sesión o registrarte.'));
    exit;
}

// Buscar usuario por cédula
if (isset($_POST["buscar"])) {
    $identificacion = $_POST["identificacion"];
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE identificacion=?");
    $stmt->bind_param("s", $identificacion);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado && $resultado->num_rows > 0) {
        $usuario = $resultado->fetch_assoc();
    } else {
        $mensaje = "No se encontró usuario con esa cédula.";
    }

    //Cierra el statement y libera resultados, stantement es para consultas preparadas y next_result es para procedimientos almacenados
    $stmt->close();
    $conn->next_result();

}

// Actualizar usuario
if (isset($_POST["actualizar"])) {

    $id_usuario      = intval($_POST["id_usuario"]);
    $nombre          = trim($_POST["nombre"] ?? '');
    $apellido1       = trim($_POST["apellido1"] ?? '');
    $apellido2       = trim($_POST["apellido2"] ?? '');
    $email           = trim($_POST["email"] ?? '');
    $telefono        = trim($_POST["telefono"] ?? '');
    $identificacion  = trim($_POST["identificacion"] ?? '');
    $id_rol          = intval($_POST["rol"]);

    $ip       = $_SERVER['REMOTE_ADDR']      ?? 'DESCONOCIDA';
    $modulo   = 'gestion_usuarios';
    $ua       = $_SERVER['HTTP_USER_AGENT']  ?? '';

    // Actualizar usuario directamente (la DB ahora tiene `nombre`, `apellido1`, `apellido2` en lugar de `nombre_completo`)
    $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, apellido1 = ?, apellido2 = ?, email = ?, telefono = ?, identificacion = ?, id_rol = ? WHERE id_usuario = ?");

    if (!$stmt) {
        $mensaje = "Error al preparar el procedimiento almacenado: " . $conn->error;
    } else {

        $stmt->bind_param(
            "ssssssii",
            $nombre,
            $apellido1,
            $apellido2,
            $email,
            $telefono,
            $identificacion,
            $id_rol,
            $id_usuario
        );

        if ($stmt->execute()) {
            $stmt->close();
            $conn->next_result(); // limpiar resultados si hubieran
            $mensaje = "Usuario actualizado correctamente.";
        } else {
            $mensaje = "Error al actualizar usuario: " . $stmt->error;
            $stmt->close();
        }
        
    }
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
    background: #152FBF;  /* Azul principal */
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    margin: 5px 0;
    transition: 0.3s ease-in-out; /* transición suave */
}
       button:hover {
    background: #264CBF;   /* Azul más oscuro */
    transform: scale(1.05); /* aumenta un 5% */
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
            bottom: 40px;   /* ajustar para subirlo o bajarlo */
            left: 50%;
            transform: translateX(-50%);
            width: 140px;   /* tamaño del logo */
            opacity: 0.9;
        }
        select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 5px;
    width: 250px;
    transition: all 0.3s ease-in-out; /* transición suave para hover */
    font-size: 1em;
    cursor: pointer;
}

/* Efecto al pasar el mouse */
select:hover {
    border-color: #152FBF;       /* cambiar color del borde */
    transform: scale(1.03);      /* ligero aumento de tamaño */
    box-shadow: 0 4px 8px rgba(0,0,0,0.1); /* sombra suave */
}

    </style>
</head>
<body>
    <div class="navbar">
    <?php include('../../views/navbar.php'); ?>

    <!-- Logo inferior del menú -->
    <img src="../../assets/img/odonto1.png" class="logo-navbar" alt="Logo OdontoSmart">
</div>

    <div class="content">
        <div class="seccion">
            <h1 style="color: #69B7BF;"> Gestión de Usuarios</h1>
            
            <!-- Búsqueda de usuario -->
            <div class="form-group">
                <h3> Buscar Usuario</h3>
                <form method="POST">
                    <input type="text" name="identificacion" placeholder="Ingrese la cédula" required>
                    <button type="submit" name="buscar">Buscar Usuario</button>
                    
                </form>
            
                    
               <button onclick="location.href='/odontosmart/modulos/usuarios/admin_crear_usuarios.php'">Crear Usuario</button>

            </div>

            

            <!-- Mensajes -->
            <?php if (!empty($mensaje)): ?>
                <div class="mensaje <?php echo strpos($mensaje, '') !== false ? 'exito' : 'error'; ?>">
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>

            <!-- Formulario de edición -->
            <?php if ($usuario): ?>
            <div class="seccion">
                <h3> Editar Usuario</h3>
                <form method="POST">
                    <input type="hidden" name="id_usuario" value="<?= $usuario['id_usuario'] ?>">
                    
                    <div class="row g-2">
                        <div class="col-md-4 form-group">
                            <label>Nombre:</label>
                            <input type="text" name="nombre" value="<?= htmlspecialchars($usuario['nombre'] ?? '') ?>" placeholder="Nombre" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Apellido 1:</label>
                            <input type="text" name="apellido1" value="<?= htmlspecialchars($usuario['apellido1'] ?? '') ?>" placeholder="Apellido 1" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Apellido 2:</label>
                            <input type="text" name="apellido2" value="<?= htmlspecialchars($usuario['apellido2'] ?? '') ?>" placeholder="Apellido 2">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" name="email" value="<?= $usuario['email'] ?>" placeholder="Email" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Teléfono:</label>
                        <input type="text" name="telefono" value="<?= $usuario['telefono'] ?>" placeholder="Teléfono" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Cédula:</label>
                        <input type="text" name="identificacion" value="<?= $usuario['identificacion'] ?>" placeholder="Cédula" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Rol:</label>
                        <select name="rol" required>
                            <option value="1" <?= ($usuario['id_rol']==1?"selected":"") ?>> Administrador</option>
                            <option value="2" <?= ($usuario['id_rol']==2?"selected":"") ?>> Médico</option>
                            <option value="3" <?= ($usuario['id_rol']==3?"selected":"") ?>> Cliente</option>
                            <option value="4" <?= ($usuario['id_rol']==4?"selected":"") ?>> Recepcionista</option>
                        </select>

                    
                    
                    <button type="submit" name="actualizar"> Actualizar Usuario</button>
                    
                    

                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>