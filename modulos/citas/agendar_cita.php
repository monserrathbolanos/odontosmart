<?php
session_start();

// Funciona para asegurar que solo usuarios logueados puedan acceder a esta página.
if (!isset($_SESSION['user'])) {
    header('Location: /odontosmart/auth/login.php?error=Acceso no autorizado');
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

//Se utiliza para obtener la lista de los odontolodos que estan registrados en la base de datos.
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

//Procesa el formulario cuando se envia una solicitud de agendar cita por el metodo POST.
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
$stmt2 = $conn->prepare("
    CALL sp_citas_crear(?,?,?,?,?,?, @resultado)
");

if ($stmt2) {
    $ip_usuario = $_SERVER['REMOTE_ADDR'] ?? 'DESCONOCIDA';

    // iissis = int, int, string, string, int, string
    $stmt2->bind_param(
        "iissis",
        $id_usuario,       // INT
        $id_odontologo,    // INT
        $fecha_cita,       // DATETIME como string 'Y-m-d H:i:s'
        $motivo,           // VARCHAR
        $idUsuarioSesion,  // usuario que agenda (desde sesión)
        $ip_usuario        // IP
    );

    if ($stmt2->execute()) {
        $stmt2->close();
        $conn->next_result(); // limpiar resultados del CALL

        // Leer el OUT del SP
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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agendar Cita - OdontoSmart</title>
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
        }
        .mensaje-error {
            color: #dc3545;
            margin-bottom: 10px;
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
    </div>

</body>
</html>