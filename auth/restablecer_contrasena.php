<?php

// Permite al usuario definir una nueva contraseña usando un enlace de recuperación con token

require '../config/conexion.php';
require_once __DIR__ . '/../config/alerts.php';

// Verifica que el token esté presente en la URL
if (!isset($_GET['token']) || $_GET['token'] === '') {
    stopWithAlert('Token no proporcionado.', 'Token faltante', 'error');
}

$token = $_GET['token'];

// Consulta para validar que el token existe, no ha expirado y el usuario existe
$stmt = $conn->prepare("
    SELECT rc.id_usuario
    FROM restablecer_contrasenas rc
    INNER JOIN usuarios u ON u.id_usuario = rc.id_usuario
    WHERE rc.token = ?
      AND rc.expira > NOW()
    LIMIT 1
");
if (!$stmt) {
    stopWithAlert('Error al preparar la consulta del token.', 'Error interno', 'error');
}

$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

// Verifica si el token es válido (existe y no está vencido)
if ($result->num_rows === 0) {
    $stmt->close();
    stopWithAlert('Token inválido o expirado.', 'Token inválido', 'error');
}

// Puedes guardar el id_usuario en sesión si lo necesitas
$row        = $result->fetch_assoc();
$idUsuario  = (int)$row['id_usuario'];
$stmt->close();

// A partir de aquí solo se muestra el formulario; el cambio real de contraseña se hace en actualizar_contrasena.php usando el token
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Restablecer contraseña</title>

    <!-- FAVICON -->
    <link rel="icon" type="image/png" href="../assets/img/odonto1.png">

    <!-- Fuente Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

     <!-- Estilos propios -->
    <link rel="stylesheet" href="../assets/css/restablecer_contrasena.css">

</head>

<body class="d-flex align-items-center justify-content-center vh-100">

  <div class="card shadow p-4">
      <h2 class="text-center mb-4">
          <strong>Restablecer contraseña</strong>
      </h2>

      <!-- Formulario para enviar la nueva contraseña -->
      <form action="actualizar_contrasena.php" method="POST" id="formReset">
          <!-- Token oculto que identifica esta solicitud de restablecimiento -->
          <input
              type="hidden"
              name="token"
              value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>"
          >

          <div class="mb-3">
              <label class="form-label" for="new_password">Nueva contraseña</label>
              <input
                  type="password"
                  name="new_password"
                  id="new_password"
                  class="form-control"
                  required
                  aria-describedby="msgPassword"
              >
              <small id="msgPassword"
                     class="form-text"
                     style="font-size:12px;color:#6c757d;"
                     role="status"
                     aria-live="polite">
                 Mínimo 8 caracteres, una mayúscula, un número y un carácter especial.
              </small>
          </div>

          <button type="submit" class="btn btn-success w-100">
              Actualizar contraseña
          </button>

          <a href="iniciar_sesion.php" class="btn btn-secondary w-100 mt-2">
              Volver
          </a>
      </form>
  </div>

  <script>
    (function(){
      const form = document.getElementById('formReset');
      const pwd  = document.getElementById('new_password');
      const msg  = document.getElementById('msgPassword');

      // min 8 chars, 1 mayúscula, 1 número, 1 caracter especial
      const strongRe = /^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#._\-])[A-Za-z\d@$!%*?&#._\-]{8,}$/;

      function validate(){
        const v = pwd.value || '';
        const baseText = 'Mínimo 8 caracteres, una mayúscula, un número y un carácter especial.';

        if (!v) {
          pwd.style.borderColor = '';
          msg.style.color = '#6c757d';
          msg.textContent = baseText;
          return false;
        }

        if (!strongRe.test(v)) {
          pwd.style.borderColor = 'red';
          msg.style.color = '#b02a37';
          msg.textContent = 'La contraseña debe tener mínimo 8 caracteres, una mayúscula, un número y un carácter especial.';
          return false;
        }

        pwd.style.borderColor = 'green';
        msg.textContent = '';
        return true;
      }

      pwd.addEventListener('input', validate);

      form.addEventListener('submit', function(e){
        if (!validate()){
          e.preventDefault();
          pwd.focus();
        }
      });
    })();
  </script>

</body>
</html>
