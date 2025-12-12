<?php
/**
 * restablecer_contrasena.php
 * ---------------------------
 * Vista para que el usuario defina una nueva contraseña
 * a partir de un enlace de recuperación con token.
 *
 * Flujo:
 *  1. Recibe un token por GET (?token=...).
 *  2. Verifica en BD que el token exista y no haya expirado.
 *  3. Si el token es válido, muestra un formulario para ingresar
 *     la nueva contraseña.
 *  4. Envía el token y la nueva contraseña a actualizar_contrasena.php.
 */

require '../config/conexion.php';
require_once __DIR__ . '/../config/alerts.php';

// Verificar que viene el token en la URL (?token=...)
if (!isset($_GET['token']) || $_GET['token'] === '') {
    stopWithAlert('Token no proporcionado.', 'Token faltante', 'error');
}

$token = $_GET['token'];

// Consulta para validar que el token existe y no ha expirado
// y que el usuario asociado sigue existiendo
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

// Verificar si el token es válido (existe y no está vencido)
if ($result->num_rows === 0) {
    $stmt->close();
    stopWithAlert('Token inválido o expirado.', 'Token inválido', 'error');
}

// Opcionalmente podrías guardar el id_usuario en sesión si lo necesitaras
$row        = $result->fetch_assoc();
$idUsuario  = (int)$row['id_usuario'];
$stmt->close();

// A partir de aquí solo mostramos el formulario; el cambio real
// de contraseña se hará en actualizar_contrasena.php usando el token.
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

    <style>
      body {
        margin: 0;
        padding: 0;
        font-family: 'Poppins', sans-serif;
        color: #fff;
        background: linear-gradient(270deg, #D5E7F2, #69B7BF, #d5e7f2);
        background-size: 300% 300%;
        animation: rgbFlow 100s ease infinite;
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
      }

      @keyframes rgbFlow {
        0%   { background-position: 0% 50%; }
        50%  { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
      }

      .card {
        position: relative;
        background: #ffffffaf;
        color: #000;
        border-radius: 16px;
        padding: 30px;
        max-width: 400px;
        width: 100%;
        box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
      }

      .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 28px rgba(0,0,0,0.3);
      }

      .card h2 {
        color: #152FBF;
      }

      label.form-label {
        color: #182940;
      }

      .btn-success {
        background: #69B7BF;
        border: none;
        font-weight: bold;
      }

      .btn-success:hover {
        background: #264CBF;
        transform: scale(1.05);
      }

      .btn-secondary {
        background: #182940;
        border: none;
        font-weight: bold;
      }

      .btn-secondary:hover {
        background: #264CBF;
        transform: scale(1.05);
      }
    </style>
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
