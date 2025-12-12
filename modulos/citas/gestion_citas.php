<?php
session_start();

$rol = $_SESSION['user']['role'] ?? null;
$rolesPermitidos = ['Administrador', 'Médico', 'Recepcionista']; // ej.
 
if (!in_array($rol, $rolesPermitidos)) {
    // Aquí decides a dónde mandarlo: login, home o protegido.
    // Si quieres mandarlo al login:
    header('Location: ../../auth/iniciar_sesion.php?error=' . urlencode('Debes iniciar sesión o registrarte.'));
    exit;
}

//Da el acceso a la gestion de citas solo a los usuarios que tengan el permiso asignado.
if (!isset($_SESSION['user'])) {
    header('Location: /odontosmart/auth/iniciar_sesion.php?error=Acceso no autorizado');
    exit;
}

//Verifica que el usuario si tenga el permiso para acceder a la gestion de citas.
$permisos = $_SESSION['user']['permisos'] ?? [];
if (!in_array('gestion_citas', $permisos, true)) {
    stopWithAlert('No tiene permiso para gestionar citas.', 'Permisos', 'warning');
}

//Se conecta a la base de datos. 
require_once '../../config/conexion.php'; 
require_once __DIR__ . '/../../config/alerts.php';

//Se obtiene el ID del usuario desde la sesión, para que funcione con el storage procedure
$idUsuarioSesion = intval($_SESSION['user']['id_usuario'] ?? 0);
if ($idUsuarioSesion <= 0) {
    stopWithAlert('No se pudo obtener el ID del usuario desde la sesión.', 'Sesión inválida', 'error');
}

//Para que solo le salgan las citas al doctor que corresponde al usuario que ha iniciado sesión, se puede agregar un filtro adicional en las consultas SQL si es necesario.
// Rol del usuario en sesión
$rolSesion   = $_SESSION['user']['role']   ?? '';
$idRolSesion = intval($_SESSION['user']['id_rol'] ?? 0); // <-- MUY IMPORTANTE


// Si es médico, obtener su id_odontologo
$idOdontologoSesion = null;

if ($idRolSesion === 2) {  // 2 = Médico
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

        // SOLO marcar como atendida si la acción fue finalizar_atencion
        // (y opcionalmente guardar_atencion, si así lo querés)
        if ($resultado === 'OK' && in_array($accion, ['finalizar_atencion', 'guardar_atencion'], true)) {
            $sqlCita = "UPDATE citas
                        SET estado = 'atendida'
                        WHERE id_cita = ?";
            $stmtCita = $conn->prepare($sqlCita);
            $stmtCita->bind_param("i", $id_cita);
            $stmtCita->execute();
            $stmtCita->close();
        }
    }
}


//Consultas para obtener los datos necesarios para mostrar en la pagina, como el cuadro general de citas y el detalle de una cita especifica.
if ($idRolSesion === 2 && $idOdontologoSesion !== null) {
    // El médico solo ve sus propias citas
    $sqlCitas = "
        SELECT 
            c.id_cita,
            c.fecha_cita,
            c.estado,
            c.motivo,

            cli.nombre   AS nombre_cliente,
            

            o.nombre     AS nombre_odontologo,
            

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
        WHERE o.id_odontologo = ?
        ORDER BY c.fecha_cita DESC
    ";

    $stmtCitas = $conn->prepare($sqlCitas);
    $stmtCitas->bind_param("i", $idOdontologoSesion);
    $stmtCitas->execute();
    $resCitas = $stmtCitas->get_result();
    $stmtCitas->close();

} else {
    // Otros roles (Administrador, Recepcionista, etc.) ven todas las citas
    $sqlCitas = "
        SELECT 
            c.id_cita,
            c.fecha_cita,
            c.estado,
            c.motivo,

            cli.nombre   AS nombre_cliente,
       

            o.nombre     AS nombre_odontologo,
           

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
}


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
                

                o.nombre     AS nombre_odontologo,
                

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
            width: 220px;                 /* Ancho fijo del menú (220 píxeles) */
            background-color: #69B7BF;    /* Color de fondo del menú (celestito de OdontoSmart) */
            height: 100vh;                /* Altura igual al 100% de la ventana (viewport height) */
            padding-top: 20px;            /* Espacio de 20px arriba, antes de los enlaces */
            position: fixed;              /* El menú queda “pegado” a la pantalla al hacer scroll */
            box-shadow: 2px 0 5px rgba(0,0,0,0.1); /* Sombra suave en el borde derecho */
            transition: width 0.3s ease;  /* Si algún día cambias el width con JS / hover,
                                     la animación tarda 0.3s y es suave */
        }
         .logo-navbar {
            position: absolute;
            bottom: 40px;   /* ajustar para subirlo o bajarlo */
            left: 50%;
            transform: translateX(-50%);
            width: 140px;   /* tamaño del logo */
            opacity: 0.9;
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
                        // Calcular tiempo de espera
                        // Calcular tiempo de espera
                $tiempoEsperaTexto = '-';
                $alertaEspera      = '';

                $fechaCita = new DateTime($c['fecha_cita']); // fecha y hora programadas de la cita

                if (!empty($c['hora_llegada']) && empty($c['hora_inicio_atencion'])) {
                    // El paciente ya llegó, pero aún no se inicia la atención
                    $llegada = new DateTime($c['hora_llegada']);
                    $ahora   = new DateTime();

                if ($ahora < $fechaCita) {
                    // Todavía no es la hora de la cita → llegó antes
                    $diff = $ahora->diff($fechaCita);
                    $min  = $diff->h * 60 + $diff->i;
                    $tiempoEsperaTexto = 'Llega ' . $min . ' min antes';
                    // Sin alerta, porque aún no está "esperando después de la hora de la cita"
                } else {
                        // Ya pasó la hora de la cita entonces ahora sí cuenta como espera
                        // La espera comienza desde la hora programada, no desde que llegó temprano
                    $inicioEspera = ($llegada > $fechaCita) ? $llegada : $fechaCita;
                    $diff = $inicioEspera->diff($ahora);
                    $min  = $diff->h * 60 + $diff->i;
                    $tiempoEsperaTexto = $min . ' min';

                    if ($min > 60) {
                         $alertaEspera = 'Tiempo máximo de espera superado. Informar al paciente que la cita previa fue más tardada.';
                        }
                    }

} elseif (!empty($c['hora_llegada']) && !empty($c['hora_inicio_atencion'])) {
    // La atención ya inició: mostrar cuánto tiempo esperó el paciente antes de ser atendido
    $llegada = new DateTime($c['hora_llegada']);
    $inicio  = new DateTime($c['hora_inicio_atencion']);

    if ($inicio <= $fechaCita) {
        // Se atendió a tiempo o antes de la hora programada
        $tiempoEsperaTexto = '0 min';
    } else {
        // La espera se cuenta desde la hora de cita o desde que llegó tarde, lo que ocurra después
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