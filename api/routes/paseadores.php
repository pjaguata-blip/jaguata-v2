<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/PaseadorController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\PaseadorController;

// Inicializar aplicación
AppConfig::init();

// Crear instancia del controlador
$controller = new PaseadorController();

// Definir cabeceras para API
header("Content-Type: application/json; charset=UTF-8");

// Obtener método y parámetros
$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

// Enrutamiento según método HTTP
switch ($method) {
    case 'GET':
        if ($id) {
            echo json_encode($controller->show($id));
        } elseif (isset($_GET['disponibles'])) {
            echo json_encode($controller->disponibles());
        } else {
            echo json_encode($controller->index());
        }
        break;

    case 'POST':
        $action = $_GET['action'] ?? null;
        if ($action === 'setDisponible' && $id) {
            echo json_encode($controller->apiSetDisponible($id));
        } elseif ($action === 'updateCalificacion' && $id) {
            echo json_encode($controller->apiUpdateCalificacion($id));
        } elseif ($action === 'incrementarPaseos' && $id) {
            echo json_encode($controller->apiIncrementarPaseos($id));
        } elseif ($id) {
            echo json_encode($controller->apiUpdate($id));
        } else {
            echo json_encode($controller->apiStore());
        }
        break;

    case 'DELETE':
        if ($id) {
            echo json_encode($controller->apiDelete($id));
        } else {
            echo json_encode(['error' => 'ID requerido para eliminar']);
        }
        break;

    default:
        http_response_code(405); // Método no permitido
        echo json_encode(['error' => 'Método no soportado']);
        break;
}
