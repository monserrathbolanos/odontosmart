<?php
// gestion_usuarios.php, se relaciona con las tablas Usuarios, y Roles (el campo id_rol indica que existe una relación con la tabla roles para asignar el tipo de usuario )
session_start();
include('../../config/conexion.php');
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
    if ($stmt->execute()) { 
        $mensaje = " Usuario actualizado correctamente."; 
    } else { 
        $mensaje = " Error al actualizar usuario."; 
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>OdontoSmart - Gestión de Usuarios</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 0; 
            background: #f5f5f5;
        }
        .navbar { 
            width: 220px; 
            background-color: #152fbf; 
            height: 100vh; 
            padding-top: 20px; 
            position: fixed; 
        }
        .navbar a { 
            display: block; 
            color: #ecf0f1; 
            padding: 12px; 
            text-decoration: none; 
            margin: 5px 0; 
            border-radius: 4px; 
        }
        .navbar a:hover { 
            background-color: #264cbf; 
        }
        .content { 
            margin-left: 240px; 
            padding: 20px; 
        }
        .seccion {
            background: white;
            padding: 20px;
            margin: 15px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        button {
            padding: 8px 15px;
            background: #152fbf;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }
        button:hover {
            background: #264cbf;
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
    </style>
</head>
<body>
    <div class="navbar">
        <?php include('../../views/navbar.php'); ?>
    </div>

    <div class="content">
        <div class="seccion">
            <h1> Gestión de Usuarios</h1>
            
            <!-- Búsqueda de usuario -->
            <div class="form-group">
                <h3> Buscar Usuario</h3>
                <form method="POST">
                    <input type="text" name="cedula" placeholder="Ingrese la cédula" required>
                    <button type="submit" name="buscar">Buscar Usuario</button>
                </form>
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
                    
                    <div class="form-group">
                        <label>Nombre Completo:</label>
                        <input type="text" name="nombre" value="<?= $usuario['nombre_completo'] ?>" placeholder="Nombre completo" required>
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
                        <input type="text" name="cedula" value="<?= $usuario['cedula'] ?>" placeholder="Cédula" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Rol:</label>
                        <select name="rol" required>
                            <option value="1" <?= ($usuario['id_rol']==1?"selected":"") ?>> Cliente</option>
                            <option value="2" <?= ($usuario['id_rol']==2?"selected":"") ?>> Administrador</option>
                            <option value="3" <?= ($usuario['id_rol']==3?"selected":"") ?>> Médico</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="actualizar"> Actualizar Usuario</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>