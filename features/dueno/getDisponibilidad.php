<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/PaseadorController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\PaseadorController;

AppConfig::init();
header('Content-Type: application/json; charset=utf-8');

$paseadorId = (int)($_GET['id'] ?? 0);
if ($paseadorId <= 0) {
    echo json_encode(['error' => 'ID inválido']);
    exit;
}

$controller = new PaseadorController();
$disponibilidad = $controller->getDisponibilidad($paseadorId);

echo json_encode(['data' => $disponibilidad]);
