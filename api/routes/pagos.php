<?php
/**
 * Rutas de Pagos - API Jaguata
 */

// Rutas de pagos
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
        'middleware' => ['auth', 'validation' => [
            'paseo_id' => ['required' => true, 'type' => 'integer'],
            'metodo_id' => ['required' => true, 'type' => 'integer']
        ]]
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
    
    // GET /api/pagos/metodos
    'GET /metodos' => [
        'controller' => 'PagoController',
        'action' => 'apiGetMetodos',
        'middleware' => ['auth']
    ],
    
    // POST /api/pagos/metodos
    'POST /metodos' => [
        'controller' => 'PagoController',
        'action' => 'apiAddMetodo',
        'middleware' => ['auth', 'validation' => [
            'tipo' => ['required' => true, 'type' => 'enum', 'values' => ['transferencia', 'efectivo']],
            'alias' => ['required' => true, 'type' => 'string'],
            'expiracion' => ['required' => false, 'type' => 'string'],
            'is_default' => ['required' => false, 'type' => 'boolean']
        ]]
    ],
    
    // PUT /api/pagos/metodos/{id}
    'PUT /metodos/{id}' => [
        'controller' => 'PagoController',
        'action' => 'apiUpdateMetodo',
        'middleware' => ['auth']
    ],
    
    // DELETE /api/pagos/metodos/{id}
    'DELETE /metodos/{id}' => [
        'controller' => 'PagoController',
        'action' => 'apiDeleteMetodo',
        'middleware' => ['auth']
    ],
    
    // GET /api/pagos/estadisticas
    'GET /estadisticas' => [
        'controller' => 'PagoController',
        'action' => 'apiGetEstadisticas',
        'middleware' => ['auth']
    ],
    
    // POST /api/pagos/{id}/reembolsar
    'POST /{id}/reembolsar' => [
        'controller' => 'PagoController',
        'action' => 'apiReembolsar',
        'middleware' => ['auth', 'role' => 'admin']
    ],
    
    // GET /api/pagos/reportes
    'GET /reportes' => [
        'controller' => 'PagoController',
        'action' => 'apiGetReportes',
        'middleware' => ['auth']
    ]
];

// Función para validar datos de pago
function validatePagoData($data) {
    $errors = [];
    
    // Validar paseo_id
    if (empty($data['paseo_id'])) {
        $errors[] = 'El ID de paseo es requerido';
    } else {
        $paseoModel = new \Jaguata\Models\Paseo();
        $paseo = $paseoModel->find($data['paseo_id']);
        if (!$paseo) {
            $errors[] = 'Paseo no encontrado';
        } elseif ($paseo['estado_pago'] !== 'pendiente') {
            $errors[] = 'El paseo ya ha sido pagado';
        } elseif ($paseo['estado'] !== 'confirmado') {
            $errors[] = 'El paseo debe estar confirmado para poder pagarlo';
        }
    }
    
    // Validar metodo_id
    if (empty($data['metodo_id'])) {
        $errors[] = 'El método de pago es requerido';
    } else {
        $metodoModel = new \App\Models\MetodoPago();
        $metodo = $metodoModel->find($data['metodo_id']);
        if (!$metodo || $metodo['usu_id'] != $_SESSION['usuario_id']) {
            $errors[] = 'Método de pago no válido';
        }
    }
    
    return $errors;
}

// Función para validar datos de método de pago
function validateMetodoPagoData($data) {
    $errors = [];
    
    // Validar tipo
    if (empty($data['tipo'])) {
        $errors[] = 'El tipo de método de pago es requerido';
    } elseif (!in_array($data['tipo'], ['transferencia', 'efectivo'])) {
        $errors[] = 'El tipo debe ser transferencia o efectivo';
    }
    
    // Validar alias
    if (empty($data['alias'])) {
        $errors[] = 'El alias es requerido';
    } elseif (strlen($data['alias']) > 50) {
        $errors[] = 'El alias no puede tener más de 50 caracteres';
    }
    
    // Validar expiración para transferencias
    if ($data['tipo'] === 'transferencia' && empty($data['expiracion'])) {
        $errors[] = 'La fecha de expiración es requerida para transferencias';
    }
    
    // Validar formato de expiración
    if (!empty($data['expiracion']) && !preg_match('/^\d{2}\/\d{4}$/', $data['expiracion'])) {
        $errors[] = 'La fecha de expiración debe tener el formato MM/YYYY';
    }
    
    return $errors;
}

// Función para verificar permisos de pago
function checkPagoPermissions($pagoId, $usuarioId, $rol) {
    $pagoModel = new \Jaguata\Models\Pago();
    $pago = $pagoModel->find($pagoId);
    
    if (!$pago) {
        return [
            'success' => false,
            'error' => 'Pago no encontrado',
            'code' => 'NOT_FOUND'
        ];
    }
    
    // Verificar permisos según rol
    if ($rol === 'dueno') {
        if ($pago['dueno_id'] != $usuarioId) {
            return [
                'success' => false,
                'error' => 'No tienes permisos para acceder a este pago',
                'code' => 'FORBIDDEN'
            ];
        }
    } elseif ($rol === 'paseador') {
        if ($pago['paseador_id'] != $usuarioId) {
            return [
                'success' => false,
                'error' => 'No tienes permisos para acceder a este pago',
                'code' => 'FORBIDDEN'
            ];
        }
    }
    
    return null;
}

// Función para verificar permisos de método de pago
function checkMetodoPagoPermissions($metodoId, $usuarioId) {
    $metodoModel = new \App\Models\MetodoPago();
    $metodo = $metodoModel->find($metodoId);
    
    if (!$metodo) {
        return [
            'success' => false,
            'error' => 'Método de pago no encontrado',
            'code' => 'NOT_FOUND'
        ];
    }
    
    if ($metodo['usu_id'] != $usuarioId) {
        return [
            'success' => false,
            'error' => 'No tienes permisos para acceder a este método de pago',
            'code' => 'FORBIDDEN'
        ];
    }
    
    return null;
}

// Función para validar estado de pago
function validatePagoEstado($estadoActual, $nuevoEstado) {
    $transicionesValidas = [
        'pendiente' => ['procesado', 'fallido'],
        'procesado' => ['reembolsado'],
        'fallido' => ['pendiente'],
        'reembolsado' => []
    ];
    
    if (!isset($transicionesValidas[$estadoActual])) {
        return 'Estado actual no válido';
    }
    
    if (!in_array($nuevoEstado, $transicionesValidas[$estadoActual])) {
        return 'Transición de estado no válida';
    }
    
    return null;
}

// Función para manejar rutas de pagos
function handlePagoRoute($method, $path, $params = []) {
    global $pagoRoutes;
    
    // Construir clave de ruta
    $routeKey = $method . ' /';
    
    // Buscar ruta específica
    $matchedRoute = null;
    $routeParams = [];
    
    foreach ($pagoRoutes as $route => $config) {
        $routeParts = explode(' ', $route);
        $routeMethod = $routeParts[0];
        $routePath = $routeParts[1];
        
        if ($routeMethod !== $method) continue;
        
        // Convertir ruta a patrón regex
        $pattern = str_replace(['{id}', '/'], ['([0-9]+)', '\/'], $routePath);
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
    if (!empty($matchedRoute['middleware'])) {
        foreach ($matchedRoute['middleware'] as $middleware) {
            $result = null;
            
            if ($middleware === 'auth') {
                if (!isset($_SESSION['usuario_id'])) {
                    return [
                        'success' => false,
                        'error' => 'No autorizado',
                        'code' => 'UNAUTHORIZED'
                    ];
                }
            } elseif (is_array($middleware) && $middleware[0] === 'role') {
                if ($_SESSION['rol'] !== $middleware[1]) {
                    return [
                        'success' => false,
                        'error' => 'Acceso denegado',
                        'code' => 'FORBIDDEN'
                    ];
                }
            } elseif (is_array($middleware) && $middleware[0] === 'validation') {
                $validationRules = $middleware[1];
                if (strpos($path, '/metodos') !== false) {
                    $errors = validateMetodoPagoData($_POST);
                } else {
                    $errors = validatePagoData($_POST);
                }
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
    }
    
    // Verificar permisos para operaciones específicas
    if (in_array($method, ['GET', 'PUT', 'DELETE']) && isset($routeParams['id'])) {
        if (strpos($path, '/metodos') !== false) {
            $permissionResult = checkMetodoPagoPermissions($routeParams['id'], $_SESSION['usuario_id']);
        } else {
            $permissionResult = checkPagoPermissions($routeParams['id'], $_SESSION['usuario_id'], $_SESSION['rol']);
        }
        if ($permissionResult) {
            return $permissionResult;
        }
    }
    
    // Ejecutar controlador
    $controllerName = $matchedRoute['controller'];
    $action = $matchedRoute['action'];
    
    if (!class_exists($controllerName)) {
        return [
            'success' => false,
            'error' => 'Controlador no encontrado',
            'code' => 'CONTROLLER_NOT_FOUND'
        ];
    }
    
    $controller = new $controllerName();
    
    if (!method_exists($controller, $action)) {
        return [
            'success' => false,
            'error' => 'Acción no encontrada',
            'code' => 'ACTION_NOT_FOUND'
        ];
    }
    
    try {
        // Pasar parámetros a la acción
        if (isset($routeParams['id'])) {
            $result = $controller->$action($routeParams['id']);
        } else {
            $result = $controller->$action();
        }
        
        return $result;
    } catch (Exception $e) {
        error_log('Pago Route Error: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Error interno del servidor',
            'code' => 'INTERNAL_ERROR'
        ];
    }
}

// Exportar configuración
return [
    'routes' => $pagoRoutes,
    'middleware' => [
        'auth' => 'authMiddleware',
        'role' => 'roleMiddleware',
        'validation' => 'validatePagoData',
        'permissions' => 'checkPagoPermissions',
        'estado' => 'validatePagoEstado'
    ],
    'handler' => 'handlePagoRoute'
];
