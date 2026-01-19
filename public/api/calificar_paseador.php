<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/CalificacionController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\CalificacionController;
use Jaguata\Helpers\Session;

AppConfig::init();

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido (solo POST)']);
        exit;
    }

    if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'dueno') {
        http_response_code(401);
        echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
        exit;
    }

    $controller = new CalificacionController();
    $res = $controller->calificarPaseador($_POST);

    http_response_code(!empty($res['ok']) ? 200 : 400);
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error interno del servidor',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
