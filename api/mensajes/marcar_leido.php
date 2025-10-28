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

$paseoId = (int)($_POST['paseo_id'] ?? 0);
if (!$paseoId) {
    echo json_encode(['error' => 'ID de paseo requerido']);
    exit;
}

$model = new Mensaje();
$ok = $model->marcarLeido($paseoId, Session::getUsuarioId());

echo json_encode(['success' => $ok]);
