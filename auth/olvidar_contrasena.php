
<!-- Esto no lo vimos en clase, queda como opcional, ya que no esta 100% funcional -->

<!DOCTYPE html>
<!-- Define el tipo de documento como HTML5 -->
<html lang="es">
<head>
  <meta charset="UTF-8">
  <!-- Establece la codificación para caracteres especiales -->
  <title>Recuperar contraseña</title>

  <!-- Importa la fuente Poppins desde Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">

  <!-- Importa Bootstrap 5 para estilos y componentes responsivos -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    /* Estilos generales del cuerpo de la página */
    body {
      margin: 0;
      padding: 0;
      height: 100vh;
      background: linear-gradient(270deg, #152fbf, #264cbf, #182940, #69b7bf); /* Fondo animado con gradiente */
      background-size: 300% 300%;
      animation: rgbFlow 150s ease infinite; /* Animación suave del fondo */
      font-family: 'Poppins', sans-serif;
      color: #ffffff;
      display: flex;
      align-items: center;
      justify-content: center; /* Centra el contenido vertical y horizontalmente */
    }

    /* Definición de la animación del fondo */
    @keyframes rgbFlow {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }

    /* Estilo de la tarjeta que contiene el formulario */
    .card {
      background-color: rgba(219, 210, 210, 0.94); /* Fondo semitransparente */
      padding: 30px;
      border-radius: 10px;
      max-width: 400px;
      width: 100%;
    }

    /* Estilo del texto de las etiquetas */
    label {
      color: #020202ff;
    }

    /* Estilo personalizado para el botón */
    .btn-custom {
      background-color: #ffffff;
      color: #152fbf;
      font-weight: bold;
    }

    /* Efecto al pasar el mouse sobre el botón */
    .btn-custom:hover {
      background-color: #e0e0e0;
    }
  </style>
</head>
<body>

  <!-- Tarjeta centrada con sombra que contiene el formulario -->
  <div class="card shadow">
    <h3 class="text-center mb-4 fw-bold">Recuperar contraseña</h3>

    <!-- Formulario para enviar el correo de recuperación -->
  <form method="POST" action="enviar_contrasena.php">
    <div class="mb-3">
        <label for="email">Correo electrónico:</label>
        <input type="email" name="email" id="email" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-custom w-100">Enviar enlace de recuperación</button>
    <a href="iniciar_sesion.php" class="btn btn-secondary w-100 mt-2">Volver</a>
</form>

  </div>

</body>
</html>