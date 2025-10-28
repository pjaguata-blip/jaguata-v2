<?php
require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/SoporteController.php';

use Jaguata\Controllers\SoporteController;
use Jaguata\Config\AppConfig;

AppConfig::init();
header('Content-Type: application/json');

$controller = new SoporteController();

$ticketId = (int)($_POST['ticket_id'] ?? 0);
$respuesta = trim($_POST['respuesta'] ?? '');

if (!$ticketId || $respuesta === '') {
    echo json_encode(['error' => 'Datos incompletos']);
    exit;
}

$ok = $controller->responderTicket($ticketId, $respuesta);
echo json_encode(['success' => $ok]);
