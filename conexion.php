<?php
// conexion.php
$host = "localhost";
$user = "root";
$password = ""; // Cambiar por si es necesario
$dbname = "odontosmart_db";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>