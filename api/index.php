<?php

/**
 * API Router Principal - Jaguata
 * Maneja todas las peticiones a la API REST
 */

require_once __DIR__ . '/../src/Config/AppConfig.php';
require_once __DIR__ . '/../src/Controllers/AuthController.php';
require_once __DIR__ . '/../src/Controllers/MascotaController.php';
require_once __DIR__ . '/../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../src/Controllers/PagoController.php';
require_once __DIR__ . '/../src/Controllers/NotificacionController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\MascotaController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Controllers\PagoController;
use Jaguata\Controllers\NotificacionController;
use Exception; // <-- Importar Exception

// Inicializar aplicación
AppConfig::init();

// Configurar headers para API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-TOKEN');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Función para enviar respuesta JSON
function sendResponse($data, $statusCode = 200)
{
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// Función para manejar errores
function handleError($message, $statusCode = 400)
{
    sendResponse([
        'success' => false,
        'error' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ], $statusCode);
}

// Función para validar autenticación
function validateAuth()
{
    if (!isset($_SESSION['usuario_id'])) {
        handleError('No autorizado', 401);
    }
}

// Función para validar rol
function validateRole($requiredRole)
{
    validateAuth();
    if ($_SESSION['rol'] !== $requiredRole) {
        handleError('Acceso denegado', 403);
    }
}

// Obtener método HTTP y ruta
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Ajuste dinámico: elimina el directorio base
$scriptName = dirname($_SERVER['SCRIPT_NAME']);
if ($scriptName !== '/' && str_starts_with($path, $scriptName)) {
    $path = substr($path, strlen($scriptName));
}

$path = trim($path, '/');

// Dividir la ruta en segmentos
$segments = explode('/', $path);

// Obtener recurso principal
$resource = $segments[0] ?? '';
$id = $segments[1] ?? null;
$action = $segments[2] ?? null;

try {
    // Enrutamiento principal
    switch ($resource) {
        case 'auth':
            $controller = new AuthController();
            switch ($method) {
                case 'POST':
                    if ($action === 'login') {
                        $controller->apiLogin();
                    } elseif ($action === 'register') {
                        $controller->apiRegister();
                    } elseif ($action === 'logout') {
                        $controller->apiLogout();
                    } else {
                        handleError('Acción no válida', 400);
                    }
                    break;
                case 'GET':
                    if ($action === 'profile') {
                        validateAuth();
                        $controller->apiGetProfile();
                    } else {
                        handleError('Acción no válida', 400);
                    }
                    break;
                default:
                    handleError('Método no permitido', 405);
            }
            break;

        case 'mascotas':
            $controller = new MascotaController();
            validateAuth();

            switch ($method) {
                case 'GET':
                    if ($id) {
                        $result = $controller->show($id);
                        sendResponse(['success' => true, 'data' => $result]);
                    } else {
                        $result = $controller->index();
                        sendResponse(['success' => true, 'data' => $result]);
                    }
                    break;
                case 'POST':
                    $result = $controller->Store();
                    sendResponse(['success' => true, 'data' => $result]);
                    break;
                case 'PUT':
                    if (!$id) handleError('ID requerido', 400);
                    $result = $controller->Update($id);
                    sendResponse(['success' => true, 'data' => $result]);
                    break;
                case 'DELETE':
                    if (!$id) handleError('ID requerido', 400);
                    $result = $controller->Destroy($id);
                    sendResponse(['success' => true, 'data' => $result]);
                    break;
                default:
                    handleError('Método no permitido', 405);
            }
            break;

        case 'paseos':
            $controller = new PaseoController();
            validateAuth();

            switch ($method) {
                case 'GET':
                    if ($id) {
                        $result = $controller->show($id);
                        sendResponse(['success' => true, 'data' => $result]);
                    } else {
                        $result = $controller->index();
                        sendResponse(['success' => true, 'data' => $result]);
                    }
                    break;
                case 'POST':
                    $result = $controller->Store();
                    sendResponse(['success' => true, 'data' => $result]);
                    break;
                case 'PUT':
                    if (!$id) handleError('ID requerido', 400);
                    if ($action === 'confirmar') {
                        $result = $controller->Confirmar($id);
                    } elseif ($action === 'iniciar') {
                        $result = $controller->apiIniciar($id);
                    } elseif ($action === 'completar') {
                        $result = $controller->apiCompletar($id);
                    } elseif ($action === 'cancelar') {
                        $result = $controller->apiCancelar($id);
                    } else {
                        $result = $controller->Update($id);
                    }
                    sendResponse(['success' => true, 'data' => $result]);
                    break;
                case 'DELETE':
                    if (!$id) handleError('ID requerido', 400);
                    $result = $controller->Destroy($id);
                    sendResponse(['success' => true, 'data' => $result]);
                    break;
                default:
                    handleError('Método no permitido', 405);
            }
            break;

        case 'pagos':
            $controller = new PagoController();
            validateAuth();

            switch ($method) {
                case 'GET':
                    if ($id) {
                        $result = $controller->show($id);
                        sendResponse(['success' => true, 'data' => $result]);
                    } else {
                        $result = $controller->index();
                        sendResponse(['success' => true, 'data' => $result]);
                    }
                    break;
                case 'POST':
                    $result = $controller->apiStore();
                    sendResponse(['success' => true, 'data' => $result]);
                    break;
                case 'PUT':
                    if (!$id) handleError('ID requerido', 400);
                    $result = $controller->apiUpdate($id);
                    sendResponse(['success' => true, 'data' => $result]);
                    break;
                case 'DELETE':
                    if (!$id) handleError('ID requerido', 400);
                    $result = $controller->apiDestroy($id);
                    sendResponse(['success' => true, 'data' => $result]);
                    break;
                default:
                    handleError('Método no permitido', 405);
            }
            break;

        case 'notificaciones':
            $controller = new NotificacionController();
            validateAuth();

            switch ($method) {
                case 'GET':
                    if ($action === 'contador') {
                        $result = $controller->getContadorNoLeidas();
                    } elseif ($action === 'recientes') {
                        $result = $controller->getRecientes();
                    } elseif ($action === 'estadisticas') {
                        $result = $controller->getEstadisticas();
                    } else {
                        $result = $controller->index();
                    }
                    sendResponse(['success' => true, 'data' => $result]);
                    break;
                case 'POST':
                    $result = $controller->crear();
                    sendResponse(['success' => true, 'data' => $result]);
                    break;
                case 'PUT':
                    if (!$id) handleError('ID requerido', 400);
                    if ($action === 'leer') {
                        $result = $controller->marcarLeida($id);
                    } elseif ($action === 'leer-todas') {
                        $result = $controller->marcarTodasLeidas();
                    } else {
                        $result = $controller->update($id); // corregido
                    }
                    sendResponse(['success' => true, 'data' => $result]);
                    break;
                case 'DELETE':
                    if (!$id) handleError('ID requerido', 400);
                    $result = $controller->eliminar($id);
                    sendResponse(['success' => true, 'data' => $result]);
                    break;
                default:
                    handleError('Método no permitido', 405);
            }
            break;

        case 'paseadores':
            $controller = new \Jaguata\Controllers\PaseadorController();
            validateAuth();

            switch ($method) {
                case 'GET':
                    if ($id) {
                        $result = $controller->show($id);
                    } else {
                        $result = $controller->index();
                    }
                    sendResponse(['success' => true, 'data' => $result]);
                    break;
                case 'POST':
                    validateRole('paseador');
                    $result = $controller->apiStore();
                    sendResponse(['success' => true, 'data' => $result]);
                    break;
                case 'PUT':
                    if (!$id) handleError('ID requerido', 400);
                    validateRole('paseador');
                    $result = $controller->apiUpdate($id);
                    sendResponse(['success' => true, 'data' => $result]);
                    break;
                default:
                    handleError('Método no permitido', 405);
            }
            break;

        case 'calificaciones':
            $controller = new \Jaguata\Controllers\CalificacionController();
            validateAuth();

            switch ($method) {
                case 'GET':
                    if ($id) {
                        $result = $controller->show($id);
                    } else {
                        $result = $controller->index();
                    }
                    sendResponse(['success' => true, 'data' => $result]);
                    break;
                case 'POST':
                    $result = $controller->apiStore();
                    sendResponse(['success' => true, 'data' => $result]);
                    break;
                case 'PUT':
                    if (!$id) handleError('ID requerido', 400);
                    $result = $controller->apiUpdate($id);
                    sendResponse(['success' => true, 'data' => $result]);
                    break;
                case 'DELETE':
                    if (!$id) handleError('ID requerido', 400);
                    $result = $controller->apiDestroy($id);
                    sendResponse(['success' => true, 'data' => $result]);
                    break;
                default:
                    handleError('Método no permitido', 405);
            }
            break;

        case 'reportes':
            $controller = new \Jaguata\Controllers\ReporteController();
            validateAuth();

            switch ($method) {
                case 'GET':
                    if ($action === 'ganancias') {
                        validateRole('paseador');
                        $result = $controller->getGanancias();
                    } elseif ($action === 'estadisticas') {
                        $result = $controller->getEstadisticas();
                    } else {
                        handleError('Acción no válida', 400);
                    }
                    sendResponse(['success' => true, 'data' => $result]);
                    break;
                default:
                    handleError('Método no permitido', 405);
            }
            break;

        case 'health':
            sendResponse([
                'success' => true,
                'status' => 'OK',
                'timestamp' => date('Y-m-d H:i:s'),
                'version' => '1.0.0'
            ]);
            break;

        default:
            handleError('Recurso no encontrado', 404);
    }
} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    handleError('Error interno del servidor', 500);
}
