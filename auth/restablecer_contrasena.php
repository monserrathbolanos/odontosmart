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
// Helper para mostrar alertas bonitas
require_once __DIR__ . '/../config/alerts.php';

// Verificar que viene el token en la URL (?token=...)
if (!isset($_GET['token'])) {
  stopWithAlert('Token no proporcionado.', 'Token', 'error');
}

$token = $_GET['token'];

// Consulta para validar que el token existe y no ha expirado
$stmt = $conn->prepare("
    SELECT id_usuario
    FROM restablecer_contrasenas
    WHERE token = ?
      AND expira > NOW()
");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

// Verificar si el token es válido (existe y no está vencido)
if ($result->num_rows === 0) {
  stopWithAlert('Token inválido o expirado.', 'Token inválido', 'error');
}

// No necesitas usar el id_usuario aquí directamente; sólo confirmas
// que el token es correcto y luego sigues con el formulario.
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Restablecer contraseña</title>
   
    <!-- FAVICON -->
    <link rel="icon" type="image/png" href="../assets/img/odonto1.png">

    <!-- Bootstrap 5: estilos base y sistema de grid -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">  
</head>

<body class="bg-light d-flex align-items-center justify-content-center vh-100">

  <!-- Fuente Poppins para la tipografía -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">

  <!-- (De nuevo) Bootstrap 5 para estilos y componentes responsivos -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    /* Estilos generales del cuerpo de la página */
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

    /* Definición de la animación del fondo */
    @keyframes rgbFlow {
      0%   { background-position: 0% 50%; }
      50%  { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }

    /* Tarjeta que contiene el formulario */
    .card {
      position: relative;
      background: #ffffffaf; /* fondo semitransparente */
      color: #000;
      border-radius: 16px;
      padding: 30px;
      max-width: 400px;
      width: 100%;
      box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    }

    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 12px 28px rgba(0,0,0,0.3);
    }

    .card h2 {
      color: #152FBF;  /* azul principal de la paleta */
    }

    /* Estilo del texto de las etiquetas */
    label {
      color: #020202ff;
    }

    /* Estilo personalizado para el botón (no usado específicamente aquí) */
    .btn-custom {
      background-color: #ffffff;
      color: #152fbf;
      font-weight: bold;
    }

    .btn-custom:hover {
      background-color: #e0e0e0;
    }

    .form-label {
      color: #182940;  /* texto oscuro para etiquetas */
    }
  </style>

  <!--
      TARJETA DE RESTABLECIMIENTO
      ---------------------------
      Muestra un formulario donde el usuario escribe la nueva contraseña.
      El token se envía oculto en un campo hidden.
  -->
  <div class="card shadow p-4" style="max-width: 400px; width: 100%;">
      <h2 class="text-center mb-4">
          <strong>Restablecer contraseña</strong>
      </h2>

      <!--
          Formulario para enviar la nueva contraseña
          - action: actualizar_contrasena.php
          - method: POST

          Campos:
            - token        (hidden)
            - new_password (password)
      -->
      <form action="actualizar_contrasena.php" method="POST">

          <!-- Token oculto que identifica esta solicitud de restablecimiento -->
          <input
              type="hidden"
              name="token"
              value="<?= htmlspecialchars($token) ?>"
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
              <small id="msgPassword" class="form-text" style="font-size:12px;color:#6c757d;" role="status" aria-live="polite">Mínimo 8 caracteres, una mayúscula, un número y un carácter especial.</small>
            </div>

          <button type="submit" class="btn btn-success w-100">
              Actualizar contraseña
          </button>

            <a href="iniciar_sesion.php" class="btn btn-secondary w-100 mt-2">Volver</a>

      </form>
      <script>
        (function(){
          const form = document.querySelector('form[action="actualizar_contrasena.php"]');
          const pwd = document.getElementById('new_password');
          const msg = document.getElementById('msgPassword');

          // Regex: min 8 chars, at least one uppercase, one digit, one special char
          const strongRe = /^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#._\-])[A-Za-z\d@$!%*?&#._\-]{8,}$/;

          function validate(){
            const v = pwd.value || '';
            const instruction = 'Mínimo 8 caracteres, una mayúscula, un número y un carácter especial.';
            if (!v) {
              pwd.style.borderColor = '';
              msg.style.color = '#6c757d';
              msg.textContent = instruction;
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
  </div>

</body>
</html>
