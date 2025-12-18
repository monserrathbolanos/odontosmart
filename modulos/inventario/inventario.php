<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Para que mysqli lance excepciones y el try-catch sirva de verdad
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

session_start();
include('../../config/conexion.php');

$rol = $_SESSION['user']['role'] ?? null;
$rolesPermitidos = ['Administrador', 'Médico', 'Recepcionista'];

if (!in_array($rol, $rolesPermitidos, true)) {
    header('Location: ../../auth/iniciar_sesion.php?error=' . urlencode('Debes iniciar sesión o registrarte.'));
    exit;
}

$mensaje = "";

// Formulario para agregar un producto y lote
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {

        // =========================
        // 1) RECUPERAR DATOS
        // =========================
        $nombre          = trim($_POST["nombre"] ?? '');
        $descripcion     = trim($_POST["descripcion"] ?? '');
        $unidad          = trim($_POST["unidad"] ?? '');
        $precio          = (float)($_POST["precio"] ?? 0);
        $stock_minimo    = (int)($_POST["stock_minimo"] ?? 0);
        $stock_total     = (int)($_POST["stock_total"] ?? 0);
        $id_categoria    = (int)($_POST["id_categoria"] ?? 0);
        $fecha_caducidad = $_POST["fecha_caducidad"] ?? null;
        $costo_unidad    = (float)($_POST["costo_unidad"] ?? 0);

        // Para lote
        $cantidad_lote = $stock_total;
        $fecha_lote    = $fecha_caducidad; // puede ser null
        $numero_lote   = (int)($_POST['numero_lote'] ?? 0);

        // =========================
        // 2) VALIDACIONES
        // =========================
        if ($nombre === '') {
            $mensaje = "Error: el nombre del producto es obligatorio.";
        } elseif ($unidad === '') {
            $mensaje = "Error: la unidad de medida es obligatoria.";
        } elseif ($id_categoria <= 0) {
            $mensaje = "Error: la categoría es obligatoria.";
        } elseif ($precio <= 0) {
            $mensaje = "Error: el precio debe ser mayor a 0.";
        } elseif ($stock_total <= 0) {
            $mensaje = "Error: la cantidad a ingresar debe ser mayor a 0.";
        } elseif ($stock_minimo < 0) {
            $mensaje = "Error: el stock mínimo no puede ser negativo.";
        } elseif ($costo_unidad <= 0) {
            $mensaje = "Error: el costo por unidad debe ser mayor a 0.";
        }

        // Validar fecha (solo si viene)
        if ($mensaje === "" && $fecha_caducidad) {
            $hoy = new DateTime('today');
            $fechaCad = DateTime::createFromFormat('Y-m-d', $fecha_caducidad);

            if (!$fechaCad) {
                $mensaje = "Error: la fecha de caducidad no tiene un formato válido.";
            } elseif ($fechaCad < $hoy) {
                $mensaje = "Error: la fecha de caducidad no puede estar en el pasado.";
            }
        }

        // Si hay error, no seguimos
        if ($mensaje !== "") {
            // no tiramos excepción, solo mostramos mensaje
        } else {

            // =========================
            // 3) DATA PARA BITÁCORA (SP)
            // =========================
            $idUsuarioSesion = (int)($_SESSION['user']['id_usuario'] ?? 0);
            $ip_cliente      = $_SERVER['REMOTE_ADDR'] ?? 'DESCONOCIDA';
            $modulo          = 'Inventario - Crear producto';
            $userAgent       = $_SERVER['HTTP_USER_AGENT'] ?? 'DESCONOCIDO';

            // =========================
            // 4) LLAMAR SP (firma dinámica)
            // =========================
            $procName = 'sp_productos_crear';
            $procParams = [];

            $sqlParams = "
                SELECT PARAMETER_NAME
                FROM information_schema.parameters
                WHERE SPECIFIC_SCHEMA = DATABASE()
                  AND SPECIFIC_NAME = ?
                  AND PARAMETER_MODE <> 'OUT'
                ORDER BY ORDINAL_POSITION
            ";

            $stmtParams = $conn->prepare($sqlParams);
            $stmtParams->bind_param("s", $procName);
            $stmtParams->execute();
            $resParams = $stmtParams->get_result();

            while ($rowP = $resParams->fetch_assoc()) {
                $procParams[] = $rowP['PARAMETER_NAME'];
            }
            $stmtParams->close();

            if (empty($procParams)) {
                $mensaje = "Error: no se pudo detectar la firma del procedimiento almacenado '$procName'.";
            } else {

                $placeholders = [];
                $bindTypes = '';
                $bindValues = [];

                foreach ($procParams as $pname) {
                    $placeholders[] = '?';

                    switch ($pname) {
                        case 'p_id_categoria':      $bindTypes .= 'i'; $bindValues[] = $id_categoria; break;
                        case 'p_nombre':           $bindTypes .= 's'; $bindValues[] = $nombre; break;
                        case 'p_descripcion':      $bindTypes .= 's'; $bindValues[] = $descripcion; break;
                        case 'p_unidad':           $bindTypes .= 's'; $bindValues[] = $unidad; break;
                        case 'p_precio':           $bindTypes .= 'd'; $bindValues[] = $precio; break;
                        case 'p_costo_unidad':     $bindTypes .= 'd'; $bindValues[] = $costo_unidad; break;
                        case 'p_stock_total':      $bindTypes .= 'i'; $bindValues[] = $stock_total; break;
                        case 'p_stock_minimo':     $bindTypes .= 'i'; $bindValues[] = $stock_minimo; break;
                        case 'p_fecha_caducidad':  $bindTypes .= 's'; $bindValues[] = $fecha_caducidad; break;
                        case 'p_id_usuario':       $bindTypes .= 'i'; $bindValues[] = $idUsuarioSesion; break;
                        case 'p_ip':               $bindTypes .= 's'; $bindValues[] = $ip_cliente; break;
                        case 'p_modulo':           $bindTypes .= 's'; $bindValues[] = $modulo; break;
                        case 'p_user_agent':       $bindTypes .= 's'; $bindValues[] = $userAgent; break;
                        default:
                            // Si hay parámetros desconocidos, enviamos string vacío para no romper
                            $bindTypes .= 's';
                            $bindValues[] = '';
                            break;
                    }
                }

                $callSql = "CALL $procName(" . implode(',', $placeholders) . ", @resultado)";
                $stmt = $conn->prepare($callSql);

                // bind_param con call_user_func_array: requiere referencias
                $params = [];
                $params[] = $bindTypes;
                foreach ($bindValues as $k => &$v) {
                    $params[] = &$v;
                }
                unset($v); // buena práctica al usar referencias en foreach

                call_user_func_array([$stmt, 'bind_param'], $params);

                // Ejecutar SP (si falla, lanza excepción y cae en catch)
                $stmt->execute();
                $stmt->close();
                $conn->next_result();

                // Leer OUT @resultado
                $res = $conn->query("SELECT @resultado AS res");
                $row = $res->fetch_assoc();
                $resultado = $row['res'] ?? null;

                if ($resultado === 'OK') {

                    // ⚠️ OJO: MAX(id_producto) no es 100% seguro en concurrencia,
                    // pero lo dejamos si tu SP no devuelve el id.
                    $res2 = $conn->query("SELECT MAX(id_producto) AS id FROM productos");
                    $id_producto = (int)($res2->fetch_assoc()['id'] ?? 0);

                    if ($id_producto <= 0) {
                        $mensaje = "Error: el producto se creó, pero no se pudo obtener el id del producto.";
                    } else {

                        // Insertar lote
                        $stmt2 = $conn->prepare("
                            INSERT INTO lote_producto (id_producto, cantidad, numero_lote, fecha_caducidad)
                            VALUES (?, ?, ?, ?)
                        ");

                        // Si tu columna fecha_caducidad permite NULL, esto está bien.
                        $stmt2->bind_param("iiis", $id_producto, $cantidad_lote, $numero_lote, $fecha_lote);
                        $stmt2->execute();
                        $stmt2->close();

                        $mensaje = "El producto fue agregado correctamente.";
                    }

                } elseif ($resultado === 'DUPLICADO') {
                    $mensaje = "Error: ya existe un producto con ese nombre en la misma categoría.";
                } elseif ($resultado === 'CADUCADO') {
                    $mensaje = "Error: la fecha de caducidad no puede estar en el pasado.";
                } else {
                    $mensaje = "Error inesperado al agregar el producto.";
                }
            }
        }

    } catch (Throwable $e) {

        // Log en bitácora (sin cerrar la conexión principal, para que la página pueda seguir cargando)
        try {
            $id_usuario_log = $_SESSION['user']['id_usuario'] ?? null;
            $accion         = 'PRODUCT_CREATE_ERROR';
            $moduloLog      = 'modulos/inventario/inventario';
            $ip             = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
            $user_agent     = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
            $detalles       = 'Error técnico: ' . $e->getMessage();

            $stmtLog = $conn->prepare("CALL SP_USUARIO_BITACORA(?, ?, ?, ?, ?, ?)");
            $stmtLog->bind_param("isssss", $id_usuario_log, $accion, $moduloLog, $ip, $user_agent, $detalles);
            $stmtLog->execute();
            $stmtLog->close();
        } catch (Throwable $logError) {
            error_log("Fallo al escribir en bitácora (inventario.php): " . $logError->getMessage());
        }

        $mensaje = "Error al procesar el producto. Por favor, intente más tarde.";
    }
}

// Obtener categorías (siempre al final, con $conn vivo)
$categorias = $conn->query("SELECT id_categoria, nombre FROM categoria_productos");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Control de Inventario</title>
    <!-- FAVICON -->
    <link rel="icon" type="image/png" href="../../assets/img/odonto1.png">
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
            padding: 30px;
            margin: 15px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-group {
            margin: 20px 0;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        input, select {
            padding: 12px;
            margin: 5px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 100%;
            max-width: 400px;
            font-size: 14px;
        }
        input:focus, select:focus {
            border-color: #69B7BF;
            outline: none;
            box-shadow: 0 0 5px rgba(105, 183, 191, 0.3);
        }
        button {
            padding: 12px 25px;
            background: #152fbf;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 0;
        }
        button:hover {
            background: #264cbf;
        }
        .mensaje {
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
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
        .form-container {
            max-width: 500px;
        }
        .required::after {
            content: " *";
            color: #dc3545;
        }
        .form-hint {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
        .logo-navbar {
            position: absolute;
            bottom: 40px;   /* ajustar para subirlo o bajarlo */
            left: 50%;
            transform: translateX(-50%);
            width: 140px;   /* tamaño del logo */
            opacity: 0.9;
        }
 
    </style>
</head>
<body>
    <div class="navbar">
    <!-- Logo inferior del menú -->
    <?php include('../../views/navbar.php'); ?>
    <img src="../../assets/img/odonto1.png" class="logo-navbar" alt="Logo OdontoSmart">
</div>
 
    <div class="content">
        <div class="seccion">
            <h1 style="color: #51a1aaff;">Control de Inventario</h1>
            <h2 style="color: #69B7BF;">Agregar Producto al Inventario</h2>
            <p>Complete el formulario para agregar un nuevo producto al sistema.</p>
 
            <?php if (!empty($mensaje)): ?>
                <div class="mensaje <?php echo strpos($mensaje, 'correctamente') !== false ? 'exito' : 'error'; ?>">
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>
 
            <div class="form-container">
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="required">Nombre del producto:</label>
                        <input type="text" name="nombre" placeholder="Ej: Anestesia Lidocaína 2%" required>
                    </div>
 
                    <div class="form-group">
                        <label>Descripción:</label>
                        <input type="text" name="descripcion" placeholder="Descripción detallada del producto">
                        <div class="form-hint">Opcional - describe las características del producto</div>
                    </div>
 
                    <div class="form-group">
                        <label class="required">Unidad de medida:</label>
                        <input type="text" name="unidad" placeholder="Ej: caja, litro, paquete, unidad" required>
                        <div class="form-hint">Especifique cómo se mide el producto</div>
                    </div>
 
                    <div class="form-group">
                        <label class="required">Precio de venta:</label>
                        <input type="number" step="0.01" name="precio" placeholder="0.00" min="0" required>
                        <div class="form-hint">Precio en colones (₡) - Incluir decimales</div>
                    </div>
 
                    <div class="form-group">
                        <label class="required">Cantidad a ingresar:</label>
                        <input type="number" name="stock_total" placeholder="0" min="0" required>
                    </div>
 
                    <div class="form-group">
                        <label class="required">Stock mínimo:</label>
                        <input type="number" name="stock_minimo" placeholder="0" min="0" required>
                        <div class="form-hint">Cantidad mínima antes de alertar por bajo stock</div>
                    </div>
 
                    <div class="form-group">
                        <label class="required">Categoría:</label>
                        <select name="id_categoria" required>
                            <option value="">Seleccione una categoría</option>
                            <?php while($cat = $categorias->fetch_assoc()): ?>
                                <option value="<?php echo $cat["id_categoria"]; ?>">
                                    <?php echo $cat["nombre"]; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
 
                    <div class="form-group">
                        <label class="required">Fecha de caducidad:</label>
                        <input
                            type="date"
                            name="fecha_caducidad"
                            required
                            min="<?php echo date('Y-m-d'); ?>">
                    </div>
 
                    <div class="form-group">
                        <label class="required">Costo por unidad:</label>
                        <input type="number" step="0.01" name="costo_unidad" placeholder="0.00" min="0" required>
                        <div class="form-hint">Costo de adquisición en colones (₡)</div>
                    </div>
               
                    <div class="form-group">
                        <label class="required">Número de lote:</label>
                        <input type="number" step="1" name="numero_lote" placeholder="0" min="0" required>
                        <div class="form-hint">Ingrese el número de lote del producto</div>
                    </div>
 
                    <button type="submit">Guardar Producto</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
 