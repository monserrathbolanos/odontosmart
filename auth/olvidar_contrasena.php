<!--
    olvidar_contrasena.php
    
    Vista opcional para iniciar el flujo de recuperación de contraseña.
    Muestra un formulario donde el usuario ingresa su correo electrónico.
    El formulario envía los datos a enviar_contrasena.php.
-->

<!-- Esto no lo vimos en clase, queda como opcional, ya que no está 100% funcional -->

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Recuperar contraseña</title>

  <!-- FAVICON -->
  <link rel="icon" type="image/png" href="../assets/img/odonto1.png">

  <!-- Fuente Poppins desde Google Fonts -->
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins&display=swap"
    rel="stylesheet"
  >

  <!-- Bootstrap 5 para estilos y componentes responsivos -->
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
  >

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

    /* Animación del fondo en gradiente */
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
    

    /* Estilo del texto de las etiquetas del formulario */
    label {
      color: #020202ff;
    }

    /* Botón principal personalizado "Enviar enlace de recuperación" */
    .btn-custom {
      background-color: #ffffff;
      color: #152fbf;
      font-weight: bold;
    }

    /* Efecto hover sobre el botón principal */
    .btn-custom:hover {
      background-color: #69B7BF;
    }

    @media (max-width: 500px) {
    .card {
        padding: 20px;
    }
  }
  </style>
</head>
<body>

  <!--
      Tarjeta centrada con sombra que contiene el formulario
      de solicitud de recuperación de contraseña.
  -->
  <div class="card shadow">
    <h3 class="text-center mb-4 fw-bold">Recuperar contraseña</h3>

    <!--
        Formulario para enviar el correo de recuperación
        - method: POST
        - action: enviar_contrasena.php
        Envía el campo:
          - email: correo electrónico del usuario que desea recuperar su contraseña.
    -->
    <form method="POST" action="enviar_contrasena.php">
      <div class="mb-3">
          <label for="email">Correo electrónico:</label>
          <input
            type="email"
            name="email"
            id="email"
            class="form-control"
            required
          >
      </div>

      <button type="submit" class="btn btn-custom w-100">
        Enviar enlace de recuperación
      </button>

      <!-- Botón secundario para volver a la pantalla de inicio de sesión -->
      <a href="iniciar_sesion.php" class="btn btn-secondary w-100 mt-3">
        Volver
      </a>
    </form>
  </div>

</body>
</html>
