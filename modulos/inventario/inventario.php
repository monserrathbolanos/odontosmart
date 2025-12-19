<?php

// Permite agregar productos al inventario para usuarios autorizados

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configura mysqli para lanzar excepciones
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

// Procesa el formulario para agregar un producto y su lote
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {

        // Recupera los datos del formulario
        $nombre          = trim($_POST["nombre"] ?? '');
        $descripcion     = trim($_POST["descripcion"] ?? '');
        $unidad          = trim($_POST["unidad"] ?? '');
        $precio          = (float)($_POST["precio"] ?? 0);
        $stock_minimo    = (int)($_POST["stock_minimo"] ?? 0);
        $stock_total     = (int)($_POST["stock_total"] ?? 0);
        $id_categoria    = (int)($_POST["id_categoria"] ?? 0);
        $fecha_caducidad = $_POST["fecha_caducidad"] ?? null;
        $costo_unidad    = (float)($_POST["costo_unidad"] ?? 0);

        // Datos del lote (se guarda con la misma cantidad del stock inicial)
        $cantidad_lote = $stock_total;
        $fecha_lote    = $fecha_caducidad; // puede ser null
        $numero_lote   = (int)($_POST['numero_lote'] ?? 0);

        // Validaciones básicas de los datos
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

        // Si no hay mensaje de error, se procesa el SP y el lote
        if ($mensaje !== "") {
            // Se muestra el mensaje y no se ejecuta la operación
        } else {

            // 3) Datos para bitácora (cuando el SP los reciba)
            $idUsuarioSesion = (int)($_SESSION['user']['id_usuario'] ?? 0);
            $ip_cliente      = $_SERVER['REMOTE_ADDR'] ?? 'DESCONOCIDA';
            $modulo          = 'Inventario - Crear producto';
            $userAgent       = $_SERVER['HTTP_USER_AGENT'] ?? 'DESCONOCIDO';

            // 4) Detectar la firma del SP para llamar con los parámetros que existan
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
                            // Si aparece un parámetro no contemplado, se envía vacío para no romper la llamada
                            $bindTypes .= 's';
                            $bindValues[] = '';
                            break;
                    }
                }

                $callSql = "CALL $procName(" . implode(',', $placeholders) . ", @resultado)";
                $stmt = $conn->prepare($callSql);

                // bind_param con call_user_func_array requiere referencias
                $params = [];
                $params[] = $bindTypes;
                foreach ($bindValues as $k => &$v) {
                    $params[] = &$v;
                }
                unset($v);

                call_user_func_array([$stmt, 'bind_param'], $params);

                // Ejecutar SP (si falla, lanza excepción)
                $stmt->execute();
                $stmt->close();
                $conn->next_result();

                // Leer OUT @resultado
                $res = $conn->query("SELECT @resultado AS res");
                $row = $res->fetch_assoc();
                $resultado = $row['res'] ?? null;

                if ($resultado === 'OK') {

                    // Si el SP no devuelve el id, se usa MAX como está en tu versión
                    $res2 = $conn->query("SELECT MAX(id_producto) AS id FROM productos");
                    $id_producto = (int)($res2->fetch_assoc()['id'] ?? 0);

                    if ($id_producto <= 0) {
                        $mensaje = "Error: el producto se creó, pero no se pudo obtener el id del producto.";
                    } else {

                        // Insertar lote del producto
                        $stmt2 = $conn->prepare("
                            INSERT INTO lote_producto (id_producto, cantidad, numero_lote, fecha_caducidad)
                            VALUES (?, ?, ?, ?)
                        ");

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

        // Registrar en bitácora si algo falló
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

// Obtener categorías para el select
$categorias = $conn->query("SELECT id_categoria, nombre FROM categoria_productos");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Control de Inventario</title>

    <link rel="icon" type="image/png" href="../../assets/img/odonto1.png">

    <!-- ESTILOS CSS -->
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/inventario.css">
</head>
<body>

<div class="sidebar">
    <?php include('../../views/sidebar.php'); ?>
    <img src="../../assets/img/odonto1.png" class="logo-sidebar" alt="Logo OdontoSmart">
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
