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
    echo json_encode([]);
    exit;
}

$paseoId = (int)($_GET['paseo_id'] ?? 0);
if (!$paseoId) {
    echo json_encode([]);
    exit;
}

$model = new Mensaje();
echo json_encode($model->getMensajes($paseoId));
