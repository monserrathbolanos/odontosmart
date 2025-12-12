<?php
/**
 * alerts.php
 * Helper para mostrar una alerta con SweetAlert2 y detener la ejecución.
 * Uso: require_once 'config/alerts.php'; stopWithAlert('Mensaje', 'Titulo', 'error');
 */

function stopWithAlert($msg, $title = 'Error', $icon = 'error', $redirect = null){
    $msgJs = json_encode($msg);
    $titleJs = json_encode($title);
    $iconJs = json_encode($icon);
    if ($redirect) {
        $redirectJs = json_encode($redirect);
    } else {
        $redirectJs = 'null';
    }

    echo "<!DOCTYPE html>\n<html lang='es'>\n<head>\n<meta charset='UTF-8'>\n<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>\n</head>\n<body>\n<script>\nSwal.fire({icon: $iconJs, title: $titleJs, text: $msgJs}).then(() => {\n    if ($redirectJs) { window.location.href = $redirectJs; } else { window.history.back(); }\n});\n</script>\n</body>\n</html>";
    exit;
}
