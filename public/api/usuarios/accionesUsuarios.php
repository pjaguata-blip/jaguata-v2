<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../../src/Controllers/UsuarioController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\UsuarioController;

AppConfig::init();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido (solo POST)']);
    exit;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$accion = trim($_POST['accion'] ?? '');

if ($id <= 0 || $accion === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Datos incompletos o inválidos']);
    exit;
}

try {
    $controller = new UsuarioController();
    $resultado = $controller->ejecutarAccion($accion, $id);

    http_response_code($resultado['ok'] ? 200 : 400);
    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error interno: ' . $e->getMessage()]);
}
