<?php
require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Models/Mensaje.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Models\Mensaje;

AppConfig::init();
header('Content-Type: application/json');

if (!Session::isLoggedIn()) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$paseoId       = (int)($_POST['paseo_id'] ?? 0);
$destinatario  = (int)($_POST['destinatario_id'] ?? 0);
$mensajeTexto  = trim($_POST['mensaje'] ?? '');

if (!$paseoId || !$destinatario || $mensajeTexto === '') {
    echo json_encode(['error' => 'Datos incompletos']);
    exit;
}

$model = new Mensaje();
$ok = $model->enviarMensaje($paseoId, Session::getUsuarioId(), $destinatario, $mensajeTexto);

echo json_encode(['success' => $ok]);
