<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Models/MetodoPago.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Models\MetodoPago;
use Jaguata\Helpers\Session;

// Inicializar aplicación
AppConfig::init();

// Definir cabeceras API
header("Content-Type: application/json; charset=UTF-8");

$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;

$metodoModel = new MetodoPago();

// Verificar sesión (solo usuarios logueados pueden acceder)
if (!Session::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$usuarioId = Session::get('usuario_id');

// Enrutamiento según método HTTP
switch ($method) {
    case 'GET':
        if ($id) {
            echo json_encode($metodoModel->find((int)$id));
        } elseif (isset($_GET['default'])) {
            echo json_encode($metodoModel->getDefault($usuarioId));
        } else {
            echo json_encode($metodoModel->getByUsuario($usuarioId));
        }
        break;

    case 'POST':
        $action = $_GET['action'] ?? null;
        $input  = $_POST;

        if ($action === 'setDefault' && $id) {
            echo json_encode($metodoModel->setDefault($usuarioId, (int)$id)
                ? ['success' => true]
                : ['error' => 'No se pudo actualizar método por defecto']);
        } elseif ($id) {
            // Actualizar método de pago
            echo json_encode($metodoModel->update((int)$id, [
                'tipo'       => $input['tipo'] ?? '',
                'alias'      => $input['alias'] ?? '',
                'expiracion' => $input['expiracion'] ?? null,
                'is_default' => $input['is_default'] ?? 0,
            ]) ? ['success' => true] : ['error' => 'No se pudo actualizar']);
        } else {
            // Crear nuevo método de pago
            echo json_encode([
                'id' => $metodoModel->create([
                    'usu_id'     => $usuarioId,
                    'tipo'       => $input['tipo'] ?? '',
                    'alias'      => $input['alias'] ?? '',
                    'expiracion' => $input['expiracion'] ?? null,
                    'is_default' => $input['is_default'] ?? 0,
                ])
            ]);
        }
        break;

    case 'DELETE':
        if ($id) {
            echo json_encode($metodoModel->delete((int)$id)
                ? ['success' => true]
                : ['error' => 'No se pudo eliminar']);
        } else {
            echo json_encode(['error' => 'ID requerido para eliminar']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no soportado']);
        break;
}
