<?php
session_start();

//Da el acceso a la gestion de citas solo a los usuarios que tengan el permiso asignado.
if (!isset($_SESSION['user'])) {
    header('Location: /odontosmart/auth/login.php?error=Acceso no autorizado');
    exit;
}

//Verifica que el usuario si tenga el permiso para acceder a la gestion de citas.
$permisos = $_SESSION['user']['permisos'] ?? [];
if (!in_array('gestion_citas', $permisos, true)) {
    die('No tiene permiso para gestionar citas.');
}

//Se conecta a la base de datos. 
require_once '../../config/conexion.php'; 

//Se obtiene el ID del usuario desde la sesión, para que funcione con el storage procedure
$idUsuarioSesion = intval($_SESSION['user']['id_usuario'] ?? 0);
if ($idUsuarioSesion <= 0) {
    die('No se pudo obtener el ID del usuario desde la sesión.');
}

//Inicializa variables para mensajes los mensajes.
$mensaje_error = '';
$mensaje_ok    = '';

//Funcion para limpiar los valores enteros recibidos por GET o POST.
function limpiarInt($v): int {
    return intval($v ?? 0);
}

//Envia los datos del formulario para actualizar el estado de la cita, por medio del metodo POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion  = $_POST['accion'] ?? '';
    $id_cita = limpiarInt($_POST['id_cita'] ?? 0);

    if ($id_cita <= 0) {
        $mensaje_error = 'Cita inválida.';
    } else {

       // Verifica que si exista una fila llamada atencion_cita para la cita que se esta procesando.
        $sqlBuscaAt = "SELECT id_atencion FROM atencion_cita WHERE id_cita = ? LIMIT 1"; 
        $stmtBusca  = $conn->prepare($sqlBuscaAt);
        $stmtBusca->bind_param("i", $id_cita);
        $stmtBusca->execute();
        $rowAt = $stmtBusca->get_result()->fetch_assoc(); // Si existe, devuelve la fila, si no, devuelve null.
        $stmtBusca->close(); // Cierra de la consulta.

        //Crea una fila en la tabla atencion_cita si no existe para la cita que se esta procesando.
        if (!$rowAt && in_array($accion, ['registrar_llegada','iniciar_atencion','finalizar_atencion','guardar_atencion'], true)) {
            //Inserta una fila base en atencion_cita para la cita actual.
            $sqlInsBase = "INSERT INTO atencion_cita (id_cita) VALUES (?)";
            $stmtBase   = $conn->prepare($sqlInsBase);
            $stmtBase->bind_param("i", $id_cita);
            $stmtBase->execute();
            $stmtBase->close();
        }

        //Procesa distintas acciones segun el boton que se haya presionado en el formulario.
       $observaciones    = '';
        $requiere_control = 0;

        if ($accion === 'guardar_atencion') {
            $observaciones    = trim($_POST['observaciones'] ?? '');
            $requiere_control = isset($_POST['requiere_control']) ? 1 : 0;
        }

        $ip_cliente = $_SERVER['REMOTE_ADDR'] ?? 'DESCONOCIDA';

        $stmtSp = $conn->prepare("
            CALL sp_citas_admin_accion(?,?,?,?,?,?, @resultado)
        ");

        if ($stmtSp) {
            // sisiis = string, int, string, int, int, string
            $stmtSp->bind_param(
                "sisiis",
                $accion,
                $id_cita,
                $observaciones,
                $requiere_control,
                $idUsuarioSesion,
                $ip_cliente
            );

            if ($stmtSp->execute()) {
                $stmtSp->close();
                $conn->next_result(); // limpiar resultados del CALL, ya que si no se realiza esto, puede fallar la siguiente consulta.

                // Leer valor OUT, para que asi se pueda mostrar el mensaje correspondiente.
                $res = $conn->query("SELECT @resultado AS res");
                $row = $res->fetch_assoc();
                $resultado = $row['res'] ?? null;

                if ($resultado === 'OK') {
                    switch ($accion) {
                        case 'registrar_llegada':
                            $mensaje_ok = 'Hora de llegada fue registrada correctamente.';
                            break;
                        case 'iniciar_atencion':
                            $mensaje_ok = 'Hora de inicio de atención registrada correctamente.';
                            break;
                        case 'finalizar_atencion':
                            $mensaje_ok = 'Hora de fin de atención registrada correctamente.';
                            break;
                        case 'cancelar_cita':
                            $mensaje_ok = 'La cita ha sido cancelada correctamente.';
                            break;
                        case 'guardar_atencion':
                            $mensaje_ok = 'La atención fue guardada correctamente.';
                            break;
                    }
                } elseif ($resultado === 'SIN_CAMBIO') {
                    $mensaje_error = 'No se realizaron cambios sobre la cita.';
                } else {
                    $mensaje_error = 'Hubo un error al procesar la acción sobre la cita.';
                }

            } else {
                $mensaje_error = 'Error al ejecutar el procedimiento almacenado de gestión de cita.';
            }
        } else {
            $mensaje_error = 'Error al preparar el procedimiento almacenado.';
        }
            //Se marca la cita como 'atendida' en la tabla citas.
            $sqlCita = "UPDATE citas
                        SET estado = 'atendida'
                        WHERE id_cita = ?";
            $stmtCita = $conn->prepare($sqlCita);
            $stmtCita->bind_param("i", $id_cita);
            $stmtCita->execute();
            $stmtCita->close();
        }
    }

//Consultas para obtener los datos necesarios para mostrar en la pagina, como el cuadro general de citas y el detalle de una cita especifica.
$sqlCitas = "
    SELECT 
        c.id_cita,
        c.fecha_cita,
        c.estado,
        c.motivo,

        cli.nombre   AS nombre_cliente,
        cli.apellido AS apellido_cliente,

        o.nombre     AS nombre_odontologo,
        o.apellido   AS apellido_odontologo,

        ac.hora_llegada,
        ac.hora_inicio_atencion,
        ac.hora_fin_atencion,
        ac.observaciones,
        ac.requiere_control

    FROM citas c
    INNER JOIN clientes cli
        ON cli.id_cliente = c.id_cliente
    INNER JOIN odontologos o
        ON o.id_odontologo = c.id_odontologo
    LEFT JOIN atencion_cita ac
        ON ac.id_cita = c.id_cita
    ORDER BY c.fecha_cita DESC
";
$resCitas = $conn->query($sqlCitas);

//Si se ha solicitado ver el detalle de una cita específica, obtener esos datos.
$detalle_cita = null;
if (isset($_GET['id_cita'])) {
    $id_det = limpiarInt($_GET['id_cita']);
    if ($id_det > 0) {
        $sqlDet = "
            SELECT 
                c.id_cita,
                c.fecha_cita,
                c.estado,
                c.motivo,

                cli.nombre   AS nombre_cliente,
                cli.apellido AS apellido_cliente,

                o.nombre     AS nombre_odontologo,
                o.apellido   AS apellido_odontologo,

                ac.hora_llegada,
                ac.hora_inicio_atencion,
                ac.hora_fin_atencion,
                ac.observaciones,
                ac.requiere_control

            FROM citas c
            INNER JOIN clientes cli
                ON cli.id_cliente = c.id_cliente
            INNER JOIN odontologos o
                ON o.id_odontologo = c.id_odontologo
            LEFT JOIN atencion_cita ac
                ON ac.id_cita = c.id_cita
            WHERE c.id_cita = ?
            LIMIT 1
        ";
        $stmtDet = $conn->prepare($sqlDet);
        $stmtDet->bind_param("i", $id_det);
        $stmtDet->execute();
        $detalle_cita = $stmtDet->get_result()->fetch_assoc();
        $stmtDet->close();
    }
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Citas - OdontoSmart</title>
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
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px;
        }
        th, td { 
            padding: 10px; 
            text-align: left; 
            border-bottom: 1px solid #ddd; 
        }
        th { 
            background: #69B7BF; 
            color: #fff; 
        }
        .btn {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
        }
        .btn-llegada { background: #28a745; color: #fff; }
        .btn-inicio  { background: #17a2b8; color: #fff; }
        .btn-fin     { background: #6c757d; color: #fff; }
        .btn-cancelar{ background: #dc3545; color: #fff; }
        .btn-atender { background: #264CBF; color: #fff; text-decoration:none; display:inline-block; }
        .logo-navbar {
            position: absolute;
            bottom: 80px;
            left: 50%;
            transform: translateX(-50%);
            width: 140px;
            opacity: 0.9;
        }
        .mensaje-ok {
            color: #28a745;
            margin-bottom: 10px;
        }
        .mensaje-error {
            color: #dc3545;
            margin-bottom: 10px;
        }
        .badge-alerta {
            color: #fff;
            background: #dc3545;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            display: inline-block;
            margin-top: 3px;
        }
        label { font-weight: bold; }
        textarea {
            width: 100%;
            padding: 8px;
            margin: 5px 0 12px 0;
            border-radius: 4px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <?php include('../../views/navbar.php'); ?>
        <img src="../../assets/img/odonto1.png" class="logo-navbar" alt="Logo OdontoSmart">
    </div>

    <div class="content">
        <h1 style="color:#69B7BF;">Gestión de Citas - OdontoSmart</h1>

        <div class="seccion">
            <?php if ($mensaje_error): ?>
                <div class="mensaje-error"><?php echo htmlspecialchars($mensaje_error); ?></div>
            <?php endif; ?>
            <?php if ($mensaje_ok): ?>
                <div class="mensaje-ok"><?php echo htmlspecialchars($mensaje_ok); ?></div>
            <?php endif; ?>

            <h3>Cuadro general de citas agendadas</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha y Hora</th>
                        <th>Cliente</th>
                        <th>Odontólogo</th>
                        <th>Estado</th>
                        <th>Tiempo de espera</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($resCitas && $resCitas->num_rows > 0): ?>
                    <?php while ($c = $resCitas->fetch_assoc()): ?>
                        <?php
                        // Calcular tiempo de espera
                        $tiempoEsperaTexto = '-';
                        $alertaEspera      = '';

                        if (!empty($c['hora_llegada']) && empty($c['hora_inicio_atencion'])) {
                            //Indica que el paciente ya llegó pero aún no se inicia la atención, mostrar tiempo de espera actual
                            $llegada = new DateTime($c['hora_llegada']);
                            $ahora   = new DateTime();
                            $diff    = $llegada->diff($ahora);
                            $min     = $diff->h * 60 + $diff->i;
                            $tiempoEsperaTexto = $min . ' min';
                            if ($min > 60) {
                                $alertaEspera = 'Tiempo máximo de espera superado. Informar al paciente que la cita previa fue más tardada.';
                            }
                        } elseif (!empty($c['hora_llegada']) && !empty($c['hora_inicio_atencion'])) {
                            //Indica que la atención ya inició, mostrar cuánto tiempo esperó el paciente
                            $llegada = new DateTime($c['hora_llegada']);
                            $inicio  = new DateTime($c['hora_inicio_atencion']);
                            $diff    = $llegada->diff($inicio);
                            $min     = $diff->h * 60 + $diff->i;
                            $tiempoEsperaTexto = $min . ' min';
                        }
                        ?>
                        <tr>
                            <td><?php echo $c['id_cita']; ?></td>
                            <td><?php echo $c['fecha_cita']; ?></td>
                            <td><?php echo htmlspecialchars($c['nombre_cliente'] . ' ' . $c['apellido_cliente']); ?></td>
                            <td><?php echo htmlspecialchars($c['nombre_odontologo'] . ' ' . $c['apellido_odontologo']); ?></td>
                            <td><?php echo ucfirst($c['estado']); ?></td>
                            <td>
                                <?php echo $tiempoEsperaTexto; ?>
                                <?php if ($alertaEspera): ?>
                                    <div class="badge-alerta">
                                        <?php echo htmlspecialchars($alertaEspera); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($c['estado'] !== 'cancelada' && $c['estado'] !== 'atendida'): ?>
                                    <?php if (empty($c['hora_llegada'])): ?>
                                        <!-- Registra la llegada del paciente a la cita -->
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="accion" value="registrar_llegada">
                                            <input type="hidden" name="id_cita" value="<?php echo $c['id_cita']; ?>">
                                            <button type="submit" class="btn btn-llegada">Registrar llegada</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if (!empty($c['hora_llegada']) && empty($c['hora_inicio_atencion'])): ?>
                                        <!-- Marca el inicio de la atención al paciente -->
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="accion" value="iniciar_atencion">
                                            <input type="hidden" name="id_cita" value="<?php echo $c['id_cita']; ?>">
                                            <button type="submit" class="btn btn-inicio">Iniciar atención</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if (!empty($c['hora_inicio_atencion']) && empty($c['hora_fin_atencion'])): ?>
                                        <!-- Marca el fin de la atención al paciente -->
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="accion" value="finalizar_atencion">
                                            <input type="hidden" name="id_cita" value="<?php echo $c['id_cita']; ?>">
                                            <button type="submit" class="btn btn-fin">Finalizar atención</button>
                                        </form>
                                    <?php endif; ?>

                                    <!-- Registro del resultado y control de la atención al paciente -->
                                    <a href="gestion_citas.php?id_cita=<?php echo $c['id_cita']; ?>" class="btn btn-atender">
                                        Resultado / Control
                                    </a>

                                    <!-- Marca la cancelación de la cita -->
                                    <form method="post" style="display:inline;" onsubmit="return confirm('¿Seguro que desea cancelar esta cita?');">
                                        <input type="hidden" name="accion" value="cancelar_cita">
                                        <input type="hidden" name="id_cita" value="<?php echo $c['id_cita']; ?>">
                                        <button type="submit" class="btn btn-cancelar">Cancelar</button>
                                    </form>
                                <?php else: ?>
                                    <?php if ($c['estado'] === 'cancelada'): ?>
                                        <span style="color:#dc3545;">Cancelada</span>
                                    <?php else: ?>
                                        <span style="color:#28a745;">Atendida</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">No hay citas registradas.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($detalle_cita): ?>
        <div class="seccion">
            <h3>Atención de la cita #<?php echo $detalle_cita['id_cita']; ?></h3>
            <p><strong>Cliente:</strong>
                <?php echo htmlspecialchars($detalle_cita['nombre_cliente'] . ' ' . $detalle_cita['apellido_cliente']); ?>
            </p>
            <p><strong>Odontólogo:</strong>
                <?php echo htmlspecialchars($detalle_cita['nombre_odontologo'] . ' ' . $detalle_cita['apellido_odontologo']); ?>
            </p>
            <p><strong>Fecha/Hora cita:</strong> <?php echo $detalle_cita['fecha_cita']; ?></p>

            <?php if (!empty($detalle_cita['hora_llegada'])): ?>
                <p><strong>Hora de llegada:</strong> <?php echo $detalle_cita['hora_llegada']; ?></p>
            <?php endif; ?>
            <?php if (!empty($detalle_cita['hora_inicio_atencion'])): ?>
                <p><strong>Inicio de atención:</strong> <?php echo $detalle_cita['hora_inicio_atencion']; ?></p>
            <?php endif; ?>
            <?php if (!empty($detalle_cita['hora_fin_atencion'])): ?>
                <p><strong>Fin de atención:</strong> <?php echo $detalle_cita['hora_fin_atencion']; ?></p>
            <?php endif; ?>

            <form method="post" action="gestion_citas.php">
                <input type="hidden" name="accion" value="guardar_atencion">
                <input type="hidden" name="id_cita" value="<?php echo $detalle_cita['id_cita']; ?>">

                <label for="observaciones">Resultado de la cita y detalles de control:</label>
                <textarea id="observaciones" name="observaciones" rows="4" required><?php
                    echo htmlspecialchars($detalle_cita['observaciones'] ?? '');
                ?></textarea>

                <label>
                    <input type="checkbox" name="requiere_control"
                        <?php echo (!empty($detalle_cita['requiere_control']) ? 'checked' : ''); ?>>
                    Requiere programación de cita de control
                </label>

                <p style="font-size: 13px; color:#555;">
                    Si requiere control, indique en las observaciones la fecha sugerida y los detalles que debe considerar el paciente.
                </p>

                <button type="submit" class="btn btn-atender">Guardar atención</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>





<!-- Estructura de las tablas de la base de datos que se utiliza para este codigo -->
<!--
   citas
     - id_cita
     - id_cliente
     - id_odontologo
     - fecha_cita (DATETIME)
     - estado ENUM('pendiente','confirmada','cancelada','atendida')
     - motivo (TEXT)

   clientes
     - id_cliente
     - nombre
     - apellido

   odontologos
     - id_odontologo
     - nombre
     - apellido

   atencion_cita
     - id_atencion
     - id_cita
     - hora_llegada (DATETIME)
     - hora_inicio_atencion (DATETIME)
     - hora_fin_atencion (DATETIME)
     - observaciones (TEXT)         
     - requiere_control (TINYINT(1))
-->