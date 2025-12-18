<?php
session_start();
require '../../config/conexion.php';
require_once __DIR__ . '/../../config/alerts.php';

try {
    // Verificar que viene el id_detalle
    if (!isset($_POST['id_detalle']) || !isset($_POST['id_carrito'])) {
        stopWithAlert('Datos incompletos', 'Datos incompletos', 'error');
    }

    $id_detalle = $_POST['id_detalle'];
    $id_carrito = $_POST['id_carrito'];


// Ver si la cantidad es > 1
$sql = "SELECT cantidad FROM carrito_detalle WHERE id_detalle = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_detalle);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    stopWithAlert('Producto no encontrado', 'Producto', 'error');
}

$row = $result->fetch_assoc();
$cantidad = $row['cantidad'];
$stmt->close();

// Si cantidad > 1 restar 1
if ($cantidad > 1) {
    $sql_update = "UPDATE carrito_detalle SET cantidad = cantidad - 1 WHERE id_detalle = ?";
    $stmt2 = $conn->prepare($sql_update);
    $stmt2->bind_param("i", $id_detalle);
    $stmt2->execute();
    $stmt2->close();
    } else {
        // Si cantidad = 1 eliminar fila
        $sql_delete = "DELETE FROM carrito_detalle WHERE id_detalle = ?";
        $stmt3 = $conn->prepare($sql_delete);
        $stmt3->bind_param("i", $id_detalle);
        $stmt3->execute();
        $stmt3->close();
    }

    // Redirigir de vuelta al carrito
    header("Location: carrito.php");
    exit;
} catch (Throwable $e) {
    try {
        if (isset($conn) && $conn instanceof mysqli) {
            if (isset($conn) && $conn instanceof mysqli && method_exists($conn, 'in_transaction') && $conn->in_transaction()) {
                try { $conn->rollback(); } catch (Throwable $__ignore) {}
            }
        }
    } catch (Throwable $__ignored) {}

    try {
        if (isset($conn)) { @$conn->close(); }
        include_once ('../../config/conexion.php');

        $id_usuario_log = $_SESSION['user']['id_usuario'] ?? null;
        $accion = 'CART_SUBTRACT_ERROR';
        $modulo = 'modulos/ventas/restar_carrito';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
        $detalles = 'Error técnico: ' . $e->getMessage();

        $stmtLog = $conn->prepare("CALL SP_USUARIO_BITACORA(?, ?, ?, ?, ?, ?)");
        if ($stmtLog) {
            $stmtLog->bind_param("isssss", $id_usuario_log, $accion, $modulo, $ip, $user_agent, $detalles);
            $stmtLog->execute();
            $stmtLog->close();
        }
        if (isset($conn)) { @$conn->close(); }
    } catch (Throwable $logError) {
        error_log("Fallo al escribir en bitácora (restar_carrito.php): " . $logError->getMessage());
    }

    stopWithAlert('Error al actualizar el carrito.', 'Error', 'error');
}
