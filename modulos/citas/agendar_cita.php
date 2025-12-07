<?php
session_start();

// Funciona para asegurar que solo usuarios logueados puedan acceder a esta página.
if (!isset($_SESSION['user'])) {
    header('Location: /odontosmart/auth/iniciar_sesion.php?error=Acceso no autorizado');
    exit;
}

//Se incluye la conexion con la base de datos. 
require_once '../../config/conexion.php';

$mensaje_error = '';
$mensaje_ok    = '';

$idUsuarioSesion = intval($_SESSION['user']['id_usuario'] ?? 0);

if ($idUsuarioSesion <= 0) {
    die('No se pudo obtener el ID del usuario desde la sesión.');
}

// Buscar el usuario asociado a ese usuario
$sqlCli = "SELECT id_usuario FROM usuarios WHERE id_usuario = ?";
$stmtCli = $conn->prepare($sqlCli);

if (!$stmtCli) {
    die('Error al preparar la consulta de usuario.');
}

//Asigna los paramentros que son los datos que se van a buscar en la base de datos.
$stmtCli->bind_param("i", $idUsuarioSesion);
$stmtCli->execute();
$resCli = $stmtCli->get_result()->fetch_assoc();
$stmtCli->close();

//Verifica que el usuario tenga un usuario asociado.
if (!$resCli) {
    die('Este usuario no está registrado como usuario. Por favor complete su registro como usuario.');
}

//Obtiene el id del usuario.
$id_usuario = intval($resCli['id_usuario']);

if ($id_usuario <= 0) {
    die('ID de usuario inválido.');
}

//Obtener el id_cliente para el usuario logueado
$sqlCli2 = "SELECT id_cliente 
            FROM clientes 
            WHERE id_usuario = ?";

$stmtCli2 = $conn->prepare($sqlCli2);

if (!$stmtCli2) {
    die('Error al preparar la consulta de cliente.');
}

$stmtCli2->bind_param("i", $id_usuario);
$stmtCli2->execute();
$resCli2 = $stmtCli2->get_result()->fetch_assoc();
$stmtCli2->close();

if (!$resCli2) {
    die('Este usuario no está registrado como cliente. Por favor complete su registro como cliente.');
}

$id_cliente = intval($resCli2['id_cliente']);

if ($id_cliente <= 0) {
    die('ID de cliente inválido.');
}


//Cancelar la cita: por medio de un get permite que el usuario pueda cancelar una cita agendada.
if (isset($_GET['accion']) && $_GET['accion'] === 'cancelar') {
    $id_cita_cancelar = intval($_GET['id_cita'] ?? 0);

    if ($id_cita_cancelar > 0) {
        $sqlCancel = "UPDATE citas 
                      SET estado = 'cancelada'
                      WHERE id_cita = ? 
                        AND id_cliente = ?";

        $stmtCancel = $conn->prepare($sqlCancel);

        if ($stmtCancel) {
            $stmtCancel->bind_param("ii", $id_cita_cancelar, $id_cliente);
            $stmtCancel->execute();

            if ($stmtCancel->affected_rows > 0) {
                $mensaje_ok = 'La cita se canceló correctamente. 
                Puede agendar otra cita volviendo a la opción "Agendar cita".';
            } else {
                $mensaje_error = 'No se pudo cancelar la cita. 
                Verifique que la cita exista y pertenezca a su usuario.';
            }

            $stmtCancel->close();
        } else {
            $mensaje_error = 'Error al preparar la cancelación de la cita.';
        }
    } else {
        $mensaje_error = 'Cita inválida para cancelar.';
    }
}

// Obtener la lista de odontólogos disponibles: Se utiliza para obtener la lista de los odontolodos que estan registrados en la base de datos.
$odontologos = [];

//Se realiza la consulta para obtener los odontologos.
$sqlOd = "SELECT o.id_odontologo, u.nombre_completo
          FROM odontologos o
          INNER JOIN usuarios u ON o.id_usuario = u.id_usuario";

//Se ejecuta la consulta para obtener los odontologos.
if ($resOd = $conn->query($sqlOd)) {
    while ($row = $resOd->fetch_assoc()) {
        $odontologos[] = $row;
    }
    $resOd->free();
}

//Formulario para procesar la cita: Procesa el formulario cuando se envia una solicitud de agendar cita por el metodo POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha         = trim($_POST['fecha_cita'] ?? '');
    $hora          = trim($_POST['hora_cita'] ?? '');
    $motivo        = trim($_POST['motivo'] ?? '');
    $id_odontologo = intval($_POST['id_odontologo'] ?? 0);

    //Funcion para que el usuario solo agende citas en los dias habilitados de lunes a sabado.  
    if ($fecha === '' || $hora === '') {
        $mensaje_error = 'Seleccione una fecha y una hora.';
    } elseif ($id_odontologo <= 0) {
        $mensaje_error = 'Seleccione a uno de nuestros odontólogos disponibles para su cita.';
    } else {
        // Validación de la hora que selecciona el usuario. 
        $partesHora = explode(':', $hora);
        $h = isset($partesHora[0]) ? intval($partesHora[0]) : -1;
        $m = isset($partesHora[1]) ? intval($partesHora[1]) : -1;

        // Solo permite al usuario elegir citas en el tiempo definido con intervalos de 30 minutos.
        if ($h < 8 || $h > 16 || !in_array($m, [0, 30], true) || ($h == 16 && $m != 0)) {
            $mensaje_error = 'La hora seleccionada no es válida. Nuestro horario de atencion es de 8:00 a.m. a 4:00 p.m..';
        } else {
            // Combina la fecha y hora
            $fecha_cita = $fecha . ' ' . $hora . ':00';

            //No permite que el usuario agende citas en fechas pasadas.
            if ($fecha < date('Y-m-d')) {
                $mensaje_error = 'Su cita no puede ser agendada en una fecha pasada, por favor seleccione otra fecha.';
            } else {
                //Los domingos no se permiten agendar citas.
                $diaSemana = date('w', strtotime($fecha)); // 0 = domingo, 1 = lunes...

                if ($diaSemana == 0) {
                    $mensaje_error = 'Las citas solo se pueden agendar de lunes a sábado. Por favor seleccione otra fecha.';
                }
            }
        }
    }

    //Control de errores, si no hay errores entonces se procede a guardar la cita en la base de datos.
    if ($mensaje_error === '') {

        //Verifica que no exista otra cita agendada en esa misma fecha y hora.
        $sqlCheck = "SELECT COUNT(*) AS total
                     FROM citas
                     WHERE fecha_cita = ?
                       AND estado <> 'cancelada'";

        //Prepara y ejecuta la consulta para verificar la disponibilidad de la cita. 
        $stmt = $conn->prepare($sqlCheck);
        if ($stmt) {
            $stmt->bind_param("s", $fecha_cita);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($res['total'] > 0) {
                $mensaje_error = 'La fecha y hora seleccionadas ya están ocupadas. Por favor, elija otro horario.';
            } else {

                // Llamar SP que inserta la cita y registra en bitácora
// Llamar SP que inserta la cita y registra en bitácora
$stmt2 = $conn->prepare("
    CALL sp_citas_crear(?,?,?,?,?,?, @resultado)
");

if ($stmt2) {
    $ip_usuario = $_SERVER['REMOTE_ADDR'] ?? 'DESCONOCIDA';

    // iissis = int, int, string, string, int, string
    $stmt2->bind_param(
        "iissis",
        $id_cliente,     
        $id_odontologo,
        $fecha_cita,
        $motivo,
        $idUsuarioSesion,
        $ip_usuario
    );

    if ($stmt2->execute()) {
        $stmt2->close();
        $conn->next_result();

        $res = $conn->query("SELECT @resultado AS res");
        $row = $res->fetch_assoc();
        $resultado_sp = $row['res'] ?? null;

        if ($resultado_sp === 'OK') {
            $mensaje_ok = "Su cita se agendó correctamente.";
        } else {
            $mensaje_error = "Lo sentimos, su cita no se pudo agendar. Intente nuevamente.";
        }
    } else {
        $mensaje_error = "Error al ejecutar el procedimiento para agendar la cita.";
        $stmt2->close();
    }
} else {
    $mensaje_error = "Error al preparar el procedimiento para agendar la cita.";
}

            }
        } else {
            $mensaje_error = "Error en la verificación de disponibilidad.";
        }
    }
}

//Tabla citas paciente: Se utiliza para mostrar las citas agendadas por el usuario.
$citas = [];

$sqlCitas = "
    SELECT 
        c.id_cita,
        c.fecha_cita,
        c.estado,
        c.motivo,
        uo.nombre_completo AS nombre_odontologo
    FROM citas c
    INNER JOIN odontologos o ON c.id_odontologo = o.id_odontologo
    INNER JOIN usuarios uo ON o.id_usuario = uo.id_usuario
    WHERE c.id_cliente = ?
    ORDER BY c.fecha_cita DESC
";

$stmtCitas = $conn->prepare($sqlCitas);
if ($stmtCitas) {
    $stmtCitas->bind_param("i", $id_cliente);
    $stmtCitas->execute();
    $resCitas = $stmtCitas->get_result();

    while ($row = $resCitas->fetch_assoc()) {
        $citas[] = $row;
    }

    $stmtCitas->close();
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agendar Cita - OdontoSmart</title>
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
            font-weight: bold;
        }
        .mensaje-error {
            color: #dc3545;
            margin-bottom: 10px;
            font-weight: bold;
        }
        label {
            font-weight: bold;
        }
        input[type="date"],
        input[type="time"],
        select,
        textarea {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            margin-bottom: 15px;
            border-radius: 4px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }
        button {
            padding: 10px 18px;
            background: #264CBF;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background: #182940;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background: #ffffffcc;
        }
        table thead {
            background-color: #69B7BF;
            color: #fff;
        }
        table th, table td {
            padding: 8px 10px;
            border: 1px solid #ddd;
            text-align: left;
            font-size: 14px;
        }
        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .btn-eliminar {
            background: #dc3545;
            color: #fff;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 13px;
        }
        .btn-eliminar:hover {
            background: #a71d2a;
        }
        details summary {
            cursor: pointer;
            font-weight: bold;
            color: #264CBF;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <?php include('../../views/navbar.php'); ?>
        <img src="../../assets/img/odonto1.png" class="logo-navbar" alt="Logo OdontoSmart">
    </div>

    <div class="content">
        <h1 style="color: #69B7BF;">Agendar Cita - OdontoSmart</h1>

        <div class="seccion">
            <?php if ($mensaje_error): ?>
                <div class="mensaje-error"><?php echo htmlspecialchars($mensaje_error); ?></div>
            <?php endif; ?>

            <?php if ($mensaje_ok): ?>
                <div class="mensaje-ok"><?php echo htmlspecialchars($mensaje_ok); ?></div>
            <?php endif; ?>

            <form method="post" action="">
                <label for="fecha_cita">Fecha de la cita:</label>
                <input 
                    type="date" 
                    id="fecha_cita" 
                    name="fecha_cita" 
                    required
                >

                <label for="hora_cita">Hora de la cita:</label>
                <select id="hora_cita" name="hora_cita" required>
                    <option value="">-- Seleccione una hora --</option>
                    <?php
                        // Generar horas desde 08:00 hasta 16:00 cada 30 minutos
                        $horaInicio = new DateTime('08:00');
                        $horaFin    = new DateTime('16:00');
                        $intervalo  = new DateInterval('PT30M');

                        for ($h = clone $horaInicio; $h <= $horaFin; $h->add($intervalo)) {
                            $horaStr = $h->format('H:i'); // ejemplo: 08:00, 08:30
                            echo '<option value="' . $horaStr . '">' . $horaStr . '</option>';
                        }
                    ?>
                </select>

                <label for="id_odontologo">Odontólogo:</label>
                <select id="id_odontologo" name="id_odontologo" required>
                    <option value="">-- Seleccione un odontólogo --</option>
                    <?php foreach ($odontologos as $od): ?>
                        <option value="<?php echo $od['id_odontologo']; ?>">
                            <?php echo htmlspecialchars($od['nombre_completo']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="motivo">Motivo / Observaciones:</label>
                <textarea id="motivo" name="motivo" rows="3"></textarea>

                <button type="submit">Reservar cita</button>
            </form>
        </div>

        <!-- TABLA: MIS CITAS AGENDADAS -->
        <div class="seccion">
            <h2>Mis citas agendadas</h2>

            <?php if (empty($citas)): ?>
                <p>No tiene citas agendadas por el momento.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Fecha y hora</th>
                            <th>Odontólogo</th>
                            <th>Estado</th>
                            <th>Detalles</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($citas as $cita): ?>
                            <tr>
                                <td>
                                    <?php 
                                        $fechaHora = strtotime($cita['fecha_cita']);
                                        echo date('d/m/Y H:i', $fechaHora);
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($cita['nombre_odontologo']); ?></td>
                                <td><?php echo htmlspecialchars($cita['estado']); ?></td>
                                <td>
                                    <details>
                                        <summary>Ver detalles</summary>
                                        <p><strong>Motivo:</strong> 
                                            <?php echo nl2br(htmlspecialchars($cita['motivo'])); ?>
                                        </p>
                                        <p><strong>Odontólogo:</strong> 
                                            <?php echo htmlspecialchars($cita['nombre_odontologo']); ?>
                                        </p>
                                        <p><strong>Estado:</strong> 
                                            <?php echo htmlspecialchars($cita['estado']); ?>
                                        </p>
                                    </details>
                                </td>
                                <td>
                                    <?php if ($cita['estado'] !== 'cancelada'): ?>
                                        <a 
                                            href="agendar_cita.php?accion=cancelar&id_cita=<?php echo $cita['id_cita']; ?>" 
                                            class="btn-eliminar"
                                            onclick="return confirmarCancelacion();"
                                        >
                                            Eliminar cita
                                        </a>
                                    <?php else: ?>
                                        <em>Cita cancelada</em>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function confirmarCancelacion() {
            return confirm(
                'Si elimina esta cita, podrá reagendar otra volviendo a la opción "Agendar cita".\n\n¿Desea continuar?'
            );
        }
    </script>
</body>
</html>