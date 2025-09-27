<?php

require_once __DIR__ . '/../../src/Helpers/PagoHelper.php';

use Jaguata\Helpers\PagoHelper;

$pagoRoutes = [
    // GET /api/pagos
    'GET /' => [
        'controller' => 'PagoController',
        'action' => 'apiIndex',
        'middleware' => ['auth']
    ],

    // GET /api/pagos/{id}
    'GET /{id}' => [
        'controller' => 'PagoController',
        'action' => 'apiShow',
        'middleware' => ['auth']
    ],

    // POST /api/pagos
    'POST /' => [
        'controller' => 'PagoController',
        'action' => 'apiStore',
        'middleware' => [
            'auth',
            ['validation' => 'pago']
        ]
    ],

    // PUT /api/pagos/{id}
    'PUT /{id}' => [
        'controller' => 'PagoController',
        'action' => 'apiUpdate',
        'middleware' => ['auth']
    ],

    // DELETE /api/pagos/{id}
    'DELETE /{id}' => [
        'controller' => 'PagoController',
        'action' => 'apiDestroy',
        'middleware' => ['auth']
    ],

    // ---------------- Métodos de Pago ----------------

    'GET /metodos' => [
        'controller' => 'PagoController',
        'action' => 'apiGetMetodos',
        'middleware' => ['auth']
    ],

    'POST /metodos' => [
        'controller' => 'PagoController',
        'action' => 'apiAddMetodo',
        'middleware' => [
            'auth',
            ['validation' => 'metodo']
        ]
    ],

    'PUT /metodos/{id}' => [
        'controller' => 'PagoController',
        'action' => 'apiUpdateMetodo',
        'middleware' => ['auth']
    ],

    'DELETE /metodos/{id}' => [
        'controller' => 'PagoController',
        'action' => 'apiDeleteMetodo',
        'middleware' => ['auth']
    ],

    // ---------------- Otros endpoints ----------------

    'GET /estadisticas' => [
        'controller' => 'PagoController',
        'action' => 'apiGetEstadisticas',
        'middleware' => ['auth']
    ],

    'POST /{id}/reembolsar' => [
        'controller' => 'PagoController',
        'action' => 'apiReembolsar',
        'middleware' => [
            'auth',
            ['role' => 'admin']
        ]
    ],

    'GET /reportes' => [
        'controller' => 'PagoController',
        'action' => 'apiGetReportes',
        'middleware' => ['auth']
    ]
];

/**
 * Middleware de validación
 */
function runValidation($type, $data)
{
    if ($type === 'pago') {
        return PagoHelper::validatePagoData($data);
    }
    if ($type === 'metodo') {
        return PagoHelper::validateMetodoPagoData($data);
    }
    return [];
}

/**
 * Manejo de rutas de pagos
 */
function handlePagoRoute($method, $path, $params = [])
{
    global $pagoRoutes;

    $matchedRoute = null;
    $routeParams = [];

    foreach ($pagoRoutes as $route => $config) {
        [$routeMethod, $routePath] = explode(' ', $route);

        if ($routeMethod !== $method) continue;

        // Regex para {id}
        $pattern = preg_replace('/{id}/', '([0-9]+)', $routePath);
        $pattern = str_replace('/', '\/', $pattern);
        $pattern = '/^' . $pattern . '$/';

        if (preg_match($pattern, $path, $matches)) {
            $matchedRoute = $config;
            if (isset($matches[1])) {
                $routeParams['id'] = (int)$matches[1];
            }
            break;
        }
    }

    if (!$matchedRoute) {
        return [
            'success' => false,
            'error' => 'Ruta no encontrada',
            'code' => 'NOT_FOUND'
        ];
    }

    // Ejecutar middleware
    foreach ($matchedRoute['middleware'] ?? [] as $middleware) {
        if ($middleware === 'auth') {
            if (!isset($_SESSION['usuario_id'])) {
                return ['success' => false, 'error' => 'No autorizado', 'code' => 'UNAUTHORIZED'];
            }
        } elseif (is_array($middleware) && isset($middleware['role'])) {
            if ($_SESSION['rol'] !== $middleware['role']) {
                return ['success' => false, 'error' => 'Acceso denegado', 'code' => 'FORBIDDEN'];
            }
        } elseif (is_array($middleware) && isset($middleware['validation'])) {
            $errors = runValidation($middleware['validation'], $_POST);
            if (!empty($errors)) {
                return [
                    'success' => false,
                    'error' => 'Datos inválidos',
                    'details' => $errors,
                    'code' => 'VALIDATION_ERROR'
                ];
            }
        }
    }

    // Permisos por recurso
    if (in_array($method, ['GET', 'PUT', 'DELETE']) && isset($routeParams['id'])) {
        if (strpos($path, '/metodos') !== false) {
            $permissionResult = PagoHelper::checkMetodoPagoPermissions($routeParams['id'], $_SESSION['usuario_id']);
        } else {
            $permissionResult = PagoHelper::checkPagoPermissions($routeParams['id'], $_SESSION['usuario_id'], $_SESSION['rol']);
        }
        if ($permissionResult) return $permissionResult;
    }

    // Ejecutar controlador
    $controllerName = $matchedRoute['controller'];
    $action = $matchedRoute['action'];

    if (!class_exists($controllerName)) {
        return ['success' => false, 'error' => 'Controlador no encontrado', 'code' => 'CONTROLLER_NOT_FOUND'];
    }

    $controller = new $controllerName();
    if (!method_exists($controller, $action)) {
        return ['success' => false, 'error' => 'Acción no encontrada', 'code' => 'ACTION_NOT_FOUND'];
    }

    try {
        return isset($routeParams['id'])
            ? $controller->$action($routeParams['id'])
            : $controller->$action();
    } catch (\Exception $e) {
        error_log('Pago Route Error: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Error interno del servidor', 'code' => 'INTERNAL_ERROR'];
    }
}

// Exportar
return [
    'routes' => $pagoRoutes,
    'handler' => 'handlePagoRoute'
];
