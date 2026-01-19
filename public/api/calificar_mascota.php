<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/CalificacionController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\CalificacionController;
use Jaguata\Helpers\Session;

AppConfig::init();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido (solo POST)'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'paseador') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $controller = new CalificacionController();
    $res = $controller->calificarMascota($_POST);

    if (!empty($res['error'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $res['error']], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode($res, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Error interno',
        'detail'  => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
