
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../config/conexion.php';

$token = $_GET['token'] ?? '';
// echo "Token recibido: " . htmlspecialchars($token); PARA VALIDAR QUE EL TOKEN LLEGA

$stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Token inválido o expirado.");
}

$email = $result->fetch_assoc()['email'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Restablecer contraseña</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      margin: 0;
      padding: 0;
      height: 100vh;
      background: linear-gradient(270deg, #152fbf, #264cbf, #182940, #69b7bf);
      background-size: 300% 300%;
      animation: rgbFlow 150s ease infinite;
      font-family: 'Poppins', sans-serif;
      color: #ffffff;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    @keyframes rgbFlow {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }

    .card {
      background-color: rgba(247, 243, 243, 0.6);
      padding: 30px;
      border-radius: 10px;
      max-width: 400px;
      width: 100%;
    }

    label {
      color: #ffffff;
    }

    .btn-custom {
      background-color: #ffffff;
      color: #152fbf;
      font-weight: bold;
    }

    .btn-custom:hover {
      background-color: #e0e0e0;
    }
  </style>
</head>
<body>

  <div class="card shadow">
    <h3 class="text-center mb-4 fw-bold">Restablecer contraseña</h3>
    <form method="POST" action="update_password.php">
      <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
      <div class="mb-3">
        <label for="new_password">Nueva contraseña:</label>
        <input type="password" name="new_password" id="new_password" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-custom w-100">Actualizar</button>
    </form>
  </div>

</body>
</html>


