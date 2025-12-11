<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/CalificacionController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\CalificacionController;
use Jaguata\Helpers\Session;

AppConfig::init();
header('Content-Type: application/json; charset=utf-8');

if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'dueno') {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$controller = new CalificacionController();
$res = $controller->calificarPaseador($_POST);

echo json_encode($res);
