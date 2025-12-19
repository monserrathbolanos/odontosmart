<?php
session_start();

// Verifica que la sesión esté activa y el usuario tenga un rol permitido
if (!isset($_SESSION['user'])) {
    header('Location: /odontosmart/auth/iniciar_sesion.php?error=' . urlencode('Debes iniciar sesión o registrarte.'));
    exit;
}

$rol = $_SESSION['user']['role'] ?? null;
$rolesPermitidos = ['Administrador', 'Médico', 'Recepcionista'];

if (!in_array($rol, $rolesPermitidos, true)) {
    header('Location: /odontosmart/auth/iniciar_sesion.php?error=' . urlencode('No tiene permiso para acceder a gestión de citas.'));
    exit;
}

// Incluye la conexión y funciones auxiliares
require_once '../../config/conexion.php';
require_once __DIR__ . '/../../config/alerts.php';

$idUsuarioSesion = intval($_SESSION['user']['id_usuario'] ?? 0);
if ($idUsuarioSesion <= 0) {
    stopWithAlert('No se pudo obtener el ID del usuario desde la sesión.', 'Sesión inválida', 'error');
}

// Obtiene el id_rol numérico para distinguir si es médico
$idRolSesion = intval($_SESSION['user']['id_rol'] ?? 0);

// Si el usuario es médico, obtiene su id_odontologo
$idOdontologoSesion = null;
if ($idRolSesion === 2) { // 2 = Médico
    $sqlOd = "SELECT id_odontologo 
              FROM odontologos 
              WHERE id_usuario = ?
              LIMIT 1";
    $stmtOd = $conn->prepare($sqlOd);
    if (!$stmtOd) {
        stopWithAlert('Error al preparar la consulta de odontólogo.', 'Error en consulta', 'error');
    }
    $stmtOd->bind_param("i", $idUsuarioSesion);
    $stmtOd->execute();
    $resOd = $stmtOd->get_result()->fetch_assoc();
    $stmtOd->close();

    if (!$resOd) {
        stopWithAlert('Este usuario no está registrado como odontólogo.', 'No Odontólogo', 'warning');
    }

    $idOdontologoSesion = intval($resOd['id_odontologo']);
}

// Inicializa los mensajes de error y éxito
$mensaje_error = '';
$mensaje_ok    = '';

function limpiarInt($v): int {
    return intval($v ?? 0);
}

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion      = $_POST['accion'] ?? '';
    $id_cita     = limpiarInt($_POST['id_cita'] ?? 0);
    $resultado   = null; // para el OUT del SP

    if ($id_cita <= 0) {
        $mensaje_error = 'Cita inválida.';
    } else {

        // Verificar si ya existe fila en atencion_cita
        $sqlBuscaAt = "SELECT id_atencion FROM atencion_cita WHERE id_cita = ? LIMIT 1";
        $stmtBusca  = $conn->prepare($sqlBuscaAt);
        $stmtBusca->bind_param("i", $id_cita);
        $stmtBusca->execute();
        $rowAt = $stmtBusca->get_result()->fetch_assoc();
        $stmtBusca->close();

        // Crear fila base en atencion_cita si no existe y la acción lo requiere
        if (
            !$rowAt &&
            in_array($accion, ['registrar_llegada','iniciar_atencion','finalizar_atencion','guardar_atencion'], true)
        ) {
            $sqlInsBase = "INSERT INTO atencion_cita (id_cita) VALUES (?)";
            $stmtBase   = $conn->prepare($sqlInsBase);
            $stmtBase->bind_param("i", $id_cita);
            $stmtBase->execute();
            $stmtBase->close();
        }

        $observaciones    = '';
        $requiere_control = 0;

        if ($accion === 'guardar_atencion') {
            $observaciones    = trim($_POST['observaciones'] ?? '');
            $requiere_control = isset($_POST['requiere_control']) ? 1 : 0;
        }

        $ip_cliente = $_SERVER['REMOTE_ADDR'] ?? 'DESCONOCIDA';

        // Llamar SP sp_citas_admin_accion
        $stmtSp = $conn->prepare("
            CALL sp_citas_admin_accion(?,?,?,?,?,?, @resultado)
        ");

        if ($stmtSp) {
            // tipos: s i s i i s
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
                $conn->next_result();

                // Leer OUT
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
                        // Registrar en bitácora la acción exitosa
                        $modulo_bitacora = 'modulos/citas/gestion_citas';
                        $accion_bitacora = strtoupper($accion);
                        $detalles_bitacora = 'Acción sobre cita ID: ' . $id_cita;
                        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
                        $stmtLog = $conn->prepare("CALL SP_USUARIO_BITACORA(?, ?, ?, ?, ?, ?)");
                        if ($stmtLog) {
                            $stmtLog->bind_param("isssss", $idUsuarioSesion, $accion_bitacora, $modulo_bitacora, $ip_cliente, $user_agent, $detalles_bitacora);
                            $stmtLog->execute();
                            $stmtLog->close();
                        }
                } elseif ($resultado === 'SIN_CAMBIO') {
                    $mensaje_error = 'No se realizaron cambios sobre la cita.';
                } else {
                    $mensaje_error = 'Hubo un error al procesar la acción sobre la cita.';
                }

            } else {
                $mensaje_error = 'Error al ejecutar el procedimiento almacenado de gestión de cita.';
                    // Registrar error en bitácora
                    $modulo_bitacora = 'modulos/citas/gestion_citas';
                    $accion_bitacora = 'ERROR';
                    $detalles_bitacora = 'Error al ejecutar SP: ' . $conn->error;
                    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
                    $stmtLog = $conn->prepare("CALL SP_USUARIO_BITACORA(?, ?, ?, ?, ?, ?)");
                    if ($stmtLog) {
                        $stmtLog->bind_param("isssss", $idUsuarioSesion, $accion_bitacora, $modulo_bitacora, $ip_cliente, $user_agent, $detalles_bitacora);
                        $stmtLog->execute();
                        $stmtLog->close();
                    }
            }
        } else {
            $mensaje_error = 'Error al preparar el procedimiento almacenado.';
                // Registrar error en bitácora
                $modulo_bitacora = 'modulos/citas/gestion_citas';
                $accion_bitacora = 'ERROR';
                $detalles_bitacora = 'Error al preparar SP: ' . $conn->error;
                $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
                $stmtLog = $conn->prepare("CALL SP_USUARIO_BITACORA(?, ?, ?, ?, ?, ?)");
                if ($stmtLog) {
                    $stmtLog->bind_param("isssss", $idUsuarioSesion, $accion_bitacora, $modulo_bitacora, $ip_cliente, $user_agent, $detalles_bitacora);
                    $stmtLog->execute();
                    $stmtLog->close();
                }
        }

        // Marcar cita como atendida sólo si el SP fue OK y la acción corresponde
        if ($resultado === 'OK' && in_array($accion, ['finalizar_atencion', 'guardar_atencion'], true)) {
            $sqlCita = "
                UPDATE citas
                   SET estado = 'atendida'
                 WHERE id_cita = ?
            ";
            $stmtCita = $conn->prepare($sqlCita);
            $stmtCita->bind_param("i", $id_cita);
            $stmtCita->execute();
            $stmtCita->close();
        }

        // Si la acción fue cancelar y el SP respondió OK, marcamos cancelada
        if ($resultado === 'OK' && $accion === 'cancelar_cita') {
            $sqlCita = "
                UPDATE citas
                   SET estado = 'cancelada'
                 WHERE id_cita = ?
            ";
            $stmtCita = $conn->prepare($sqlCita);
            $stmtCita->bind_param("i", $id_cita);
            $stmtCita->execute();
            $stmtCita->close();
        }
    }
}

// Consultar listado de citas
if ($idRolSesion === 2 && $idOdontologoSesion !== null) {
    // El médico sólo ve sus citas
    $sqlCitas = "
        SELECT 
            c.id_cita,
            c.fecha_cita,
            c.estado,
            c.motivo,

            CONCAT(uCli.nombre, ' ', uCli.apellido1, ' ', COALESCE(uCli.apellido2,'')) AS nombre_cliente,

            CONCAT(uOdo.nombre, ' ', uOdo.apellido1, ' ', COALESCE(uOdo.apellido2,'')) AS nombre_odontologo,

            ac.hora_llegada,
            ac.hora_inicio_atencion,
            ac.hora_fin_atencion,
            ac.observaciones,
            ac.requiere_control

        FROM citas c
        INNER JOIN clientes cli
            ON cli.id_cliente = c.id_cliente
        INNER JOIN usuarios uCli
            ON uCli.id_usuario = cli.id_usuario
        INNER JOIN odontologos o
            ON o.id_odontologo = c.id_odontologo
        INNER JOIN usuarios uOdo
            ON uOdo.id_usuario = o.id_usuario
        LEFT JOIN atencion_cita ac
            ON ac.id_cita = c.id_cita
        WHERE c.id_odontologo = ?
        ORDER BY c.fecha_cita DESC
    ";

    $stmtCitas = $conn->prepare($sqlCitas);
    $stmtCitas->bind_param("i", $idOdontologoSesion);
    $stmtCitas->execute();
    $resCitas = $stmtCitas->get_result();
    $stmtCitas->close();

} else {
    // Admin / Recepcionista ven todas las citas
    $sqlCitas = "
        SELECT 
            c.id_cita,
            c.fecha_cita,
            c.estado,
            c.motivo,

            CONCAT(uCli.nombre, ' ', uCli.apellido1, ' ', COALESCE(uCli.apellido2,'')) AS nombre_cliente,

            CONCAT(uOdo.nombre, ' ', uOdo.apellido1, ' ', COALESCE(uOdo.apellido2,'')) AS nombre_odontologo,

            ac.hora_llegada,
            ac.hora_inicio_atencion,
            ac.hora_fin_atencion,
            ac.observaciones,
            ac.requiere_control

        FROM citas c
        INNER JOIN clientes cli
            ON cli.id_cliente = c.id_cliente
        INNER JOIN usuarios uCli
            ON uCli.id_usuario = cli.id_usuario
        INNER JOIN odontologos o
            ON o.id_odontologo = c.id_odontologo
        INNER JOIN usuarios uOdo
            ON uOdo.id_usuario = o.id_usuario
        LEFT JOIN atencion_cita ac
            ON ac.id_cita = c.id_cita
        ORDER BY c.fecha_cita DESC
    ";
    $resCitas = $conn->query($sqlCitas);
}

// Detalle de cita (GET)
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

                CONCAT(uCli.nombre, ' ', uCli.apellido1, ' ', COALESCE(uCli.apellido2,'')) AS nombre_cliente,

                CONCAT(uOdo.nombre, ' ', uOdo.apellido1, ' ', COALESCE(uOdo.apellido2,'')) AS nombre_odontologo,

                ac.hora_llegada,
                ac.hora_inicio_atencion,
                ac.hora_fin_atencion,
                ac.observaciones,
                ac.requiere_control

            FROM citas c
            INNER JOIN clientes cli
                ON cli.id_cliente = c.id_cliente
            INNER JOIN usuarios uCli
                ON uCli.id_usuario = cli.id_usuario
            INNER JOIN odontologos o
                ON o.id_odontologo = c.id_odontologo
            INNER JOIN usuarios uOdo
                ON uOdo.id_usuario = o.id_usuario
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
    <link rel="icon" href="/odontosmart/assets/img/odonto1.png">

    <!-- ESTILOS CSS -->
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="/odontosmart/assets/css/gestion_citas.css">
    
</head>
<body>
    <div class="sidebar">
        <?php include('../../views/sidebar.php'); ?>
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
                        // Cálculo de tiempo espera
                        $tiempoEsperaTexto = '-';
                        $alertaEspera      = '';

                        $fechaCita = new DateTime($c['fecha_cita']);

                        if (!empty($c['hora_llegada']) && empty($c['hora_inicio_atencion'])) {
                            $llegada = new DateTime($c['hora_llegada']);
                            $ahora   = new DateTime();

                            if ($ahora < $fechaCita) {
                                // Llegó antes de la hora de cita
                                $diff = $ahora->diff($fechaCita);
                                $min  = $diff->h * 60 + $diff->i;
                                $tiempoEsperaTexto = 'Llega ' . $min . ' min antes';
                            } else {
                                // Ya pasó la hora de cita: contar espera
                                $inicioEspera = ($llegada > $fechaCita) ? $llegada : $fechaCita;
                                $diff = $inicioEspera->diff($ahora);
                                $min  = $diff->h * 60 + $diff->i;
                                $tiempoEsperaTexto = $min . ' min';

                                if ($min > 60) {
                                    $alertaEspera = 'Tiempo máximo de espera superado. Informar al paciente que la cita previa fue más tardada.';
                                }
                            }

                        } elseif (!empty($c['hora_llegada']) && !empty($c['hora_inicio_atencion'])) {
                            $llegada = new DateTime($c['hora_llegada']);
                            $inicio  = new DateTime($c['hora_inicio_atencion']);

                            if ($inicio <= $fechaCita) {
                                $tiempoEsperaTexto = '0 min';
                            } else {
                                $inicioEspera = ($llegada > $fechaCita) ? $llegada : $fechaCita;
                                $diff = $inicioEspera->diff($inicio);
                                $min  = $diff->h * 60 + $diff->i;
                                $tiempoEsperaTexto = $min . ' min';
                            }
                        }
                        ?>
                        <tr>
                            <td><?php echo $c['id_cita']; ?></td>
                            <td><?php echo $c['fecha_cita']; ?></td>
                            <td><?php echo htmlspecialchars($c['nombre_cliente']); ?></td>
                            <td><?php echo htmlspecialchars($c['nombre_odontologo']); ?></td>
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
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="accion" value="registrar_llegada">
                                            <input type="hidden" name="id_cita" value="<?php echo $c['id_cita']; ?>">
                                            <button type="submit" class="btn btn-llegada">Registrar llegada</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if (!empty($c['hora_llegada']) && empty($c['hora_inicio_atencion'])): ?>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="accion" value="iniciar_atencion">
                                            <input type="hidden" name="id_cita" value="<?php echo $c['id_cita']; ?>">
                                            <button type="submit" class="btn btn-inicio">Iniciar atención</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if (!empty($c['hora_inicio_atencion']) && empty($c['hora_fin_atencion'])): ?>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="accion" value="finalizar_atencion">
                                            <input type="hidden" name="id_cita" value="<?php echo $c['id_cita']; ?>">
                                            <button type="submit" class="btn btn-fin">Finalizar atención</button>
                                        </form>
                                    <?php endif; ?>

                                    <a href="gestion_citas.php?id_cita=<?php echo $c['id_cita']; ?>" class="btn btn-atender">
                                        Resultado / Control
                                    </a>

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
                <?php echo htmlspecialchars($detalle_cita['nombre_cliente']); ?>
            </p>
            <p><strong>Odontólogo:</strong>
                <?php echo htmlspecialchars($detalle_cita['nombre_odontologo']); ?>
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
