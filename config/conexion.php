<?php
// conexion.php
$host = "localhost";
$user = "root";
$password = ""; // Cambiar por si es necesario
$dbname = "odontosmart_db";

$conn = new mysqli($host, $user, $password, $dbname);

// Si la conexión falla, mostramos una alerta y no permitimos continuar
if ($conn->connect_error) {
    require_once __DIR__ . '/alerts.php';
    stopWithAlert('Error de conexión: ' . $conn->connect_error, 'Error de conexión', 'error');
}
?>