<?php
/**
 * Rutas de Notificaciones - API Jaguata
 */

// Rutas de notificaciones
$notificacionRoutes = [
    // GET /api/notificaciones
    'GET /' => [
        'controller' => 'NotificacionController',
        'action' => 'apiIndex',
        'middleware' => ['auth']
    ],
    
    // GET /api/notificaciones/{id}
    'GET /{id}' => [
        'controller' => 'NotificacionController',
        'action' => 'apiShow',
        'middleware' => ['auth']
    ],
    
    // POST /api/notificaciones
    'POST /' => [
        'controller' => 'NotificacionController',
        'action' => 'apiStore',
        'middleware' => ['auth', 'validation' => [
            'usu_id' => ['required' => true, 'type' => 'integer'],
            'tipo' => ['required' => true, 'type' => 'string'],
            'titulo' => ['required' => true, 'type' => 'string'],
            'mensaje' => ['required' => true, 'type' => 'string'],
            'paseo_id' => ['required' => false, 'type' => 'integer']
        ]]
    ],
    
    // PUT /api/notificaciones/{id}
    'PUT /{id}' => [
        'controller' => 'NotificacionController',
        'action' => 'apiUpdate',
        'middleware' => ['auth']
    ],
    
    // DELETE /api/notificaciones/{id}
    'DELETE /{id}' => [
        'controller' => 'NotificacionController',
        'action' => 'apiDestroy',
        'middleware' => ['auth']
    ],
    
    // PUT /api/notificaciones/{id}/leer
    'PUT /{id}/leer' => [
        'controller' => 'NotificacionController',
        'action' => 'apiMarcarLeida',
        'middleware' => ['auth']
    ],
    
    // PUT /api/notificaciones/leer-todas
    'PUT /leer-todas' => [
        'controller' => 'NotificacionController',
        'action' => 'apiMarcarTodasLeidas',
        'middleware' => ['auth']
    ],
    
    // GET /api/notificaciones/contador
    'GET /contador' => [
        'controller' => 'NotificacionController',
        'action' => 'apiGetContador',
        'middleware' => ['auth']
    ],
    
    // GET /api/notificaciones/recientes
    'GET /recientes' => [
        'controller' => 'NotificacionController',
        'action' => 'apiGetRecientes',
        'middleware' => ['auth']
    ],
    
    // GET /api/notificaciones/estadisticas
    'GET /estadisticas' => [
        'controller' => 'NotificacionController',
        'action' => 'apiGetEstadisticas',
        'middleware' => ['auth']
    ],
    
    // GET /api/notificaciones/tipo/{tipo}
    'GET /tipo/{tipo}' => [
        'controller' => 'NotificacionController',
        'action' => 'apiGetByTipo',
        'middleware' => ['auth']
    ],
    
    // POST /api/notificaciones/limpiar
    'POST /limpiar' => [
        'controller' => 'NotificacionController',
        'action' => 'apiLimpiarExpiradas',
        'middleware' => ['auth']
    ]
];

// Función para validar datos de notificación
function validateNotificacionData($data) {
    $errors = [];
    
    // Validar usu_id
    if (empty($data['usu_id'])) {
        $errors[] = 'El ID de usuario es requerido';
    } else {
        $usuarioModel = new \App\Models\Usuario();
        $usuario = $usuarioModel->find($data['usu_id']);
        if (!$usuario) {
            $errors[] = 'Usuario no encontrado';
        }
    }
    
    // Validar tipo
    if (empty($data['tipo'])) {
        $errors[] = 'El tipo de notificación es requerido';
    } else {
        $tiposValidos = [
            'nuevo_paseo',
            'paseo_confirmado',
            'paseo_cancelado',
            'pago_procesado',
            'nueva_calificacion',
            'sistema',
            'promocion'
        ];
        if (!in_array($data['tipo'], $tiposValidos)) {
            $errors[] = 'Tipo de notificación no válido';
        }
    }
    
    // Validar título
    if (empty($data['titulo'])) {
        $errors[] = 'El título es requerido';
    } elseif (strlen($data['titulo']) > 200) {
        $errors[] = 'El título no puede tener más de 200 caracteres';
    }
    
    // Validar mensaje
    if (empty($data['mensaje'])) {
        $errors[] = 'El mensaje es requerido';
    } elseif (strlen($data['mensaje']) > 1000) {
        $errors[] = 'El mensaje no puede tener más de 1000 caracteres';
    }
    
    // Validar paseo_id si se proporciona
    if (!empty($data['paseo_id'])) {
        $paseoModel = new \Jaguata\Models\Paseo();
        $paseo = $paseoModel->find($data['paseo_id']);
        if (!$paseo) {
            $errors[] = 'Paseo no encontrado';
        }
    }
    
    return $errors;
}

// Función para verificar permisos de notificación
function checkNotificacionPermissions($notificacionId, $usuarioId) {
    $notificacionModel = new \Jaguata\Models\Notificacion();
    $notificacion = $notificacionModel->find($notificacionId);
    
    if (!$notificacion) {
        return [
            'success' => false,
            'error' => 'Notificación no encontrada',
            'code' => 'NOT_FOUND'
        ];
    }
    
    if ($notificacion['usu_id'] != $usuarioId) {
        return [
            'success' => false,
            'error' => 'No tienes permisos para acceder a esta notificación',
            'code' => 'FORBIDDEN'
        ];
    }
    
    return null;
}

// Función para validar filtros de notificación
function validateNotificacionFilters($filters) {
    $errors = [];
    
    // Validar leido
    if (isset($filters['leido']) && !in_array($filters['leido'], ['true', 'false', '1', '0'])) {
        $errors[] = 'El filtro leido debe ser true o false';
    }
    
    // Validar limite
    if (isset($filters['limite'])) {
        $limite = (int)$filters['limite'];
        if ($limite < 1 || $limite > 100) {
            $errors[] = 'El límite debe estar entre 1 y 100';
        }
    }
    
    // Validar tipo
    if (isset($filters['tipo'])) {
        $tiposValidos = [
            'nuevo_paseo',
            'paseo_confirmado',
            'paseo_cancelado',
            'pago_procesado',
            'nueva_calificacion',
            'sistema',
            'promocion'
        ];
        if (!in_array($filters['tipo'], $tiposValidos)) {
            $errors[] = 'Tipo de notificación no válido';
        }
    }
    
    return $errors;
}

// Función para procesar filtros de notificación
function processNotificacionFilters($filters) {
    $processed = [];
    
    // Procesar leido
    if (isset($filters['leido'])) {
        $processed['leido'] = in_array($filters['leido'], ['true', '1']);
    }
    
    // Procesar limite
    if (isset($filters['limite'])) {
        $processed['limite'] = (int)$filters['limite'];
    } else {
        $processed['limite'] = 20; // Valor por defecto
    }
    
    // Procesar tipo
    if (isset($filters['tipo'])) {
        $processed['tipo'] = $filters['tipo'];
    }
    
    return $processed;
}

// Función para manejar rutas de notificaciones
function handleNotificacionRoute($method, $path, $params = []) {
    global $notificacionRoutes;
    
    // Construir clave de ruta
    $routeKey = $method . ' /';
    
    // Buscar ruta específica
    $matchedRoute = null;
    $routeParams = [];
    
    foreach ($notificacionRoutes as $route => $config) {
        $routeParts = explode(' ', $route);
        $routeMethod = $routeParts[0];
        $routePath = $routeParts[1];
        
        if ($routeMethod !== $method) continue;
        
        // Convertir ruta a patrón regex
        $pattern = str_replace(['{id}', '{tipo}', '/'], ['([0-9]+)', '([a-z_]+)', '\/'], $routePath);
        $pattern = '/^' . $pattern . '$/';
        
        if (preg_match($pattern, $path, $matches)) {
            $matchedRoute = $config;
            if (isset($matches[1])) {
                $routeParams['id'] = (int)$matches[1];
            }
            if (isset($matches[2])) {
                $routeParams['tipo'] = $matches[2];
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
            } elseif (is_array($middleware) && $middleware[0] === 'validation') {
                $validationRules = $middleware[1];
                $errors = validateNotificacionData($_POST);
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
        $permissionResult = checkNotificacionPermissions($routeParams['id'], $_SESSION['usuario_id']);
        if ($permissionResult) {
            return $permissionResult;
        }
    }
    
    // Validar filtros para GET
    if ($method === 'GET' && !isset($routeParams['id'])) {
        $filters = $_GET;
        $filterErrors = validateNotificacionFilters($filters);
        if (!empty($filterErrors)) {
            return [
                'success' => false,
                'error' => 'Filtros inválidos',
                'details' => $filterErrors,
                'code' => 'VALIDATION_ERROR'
            ];
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
        } elseif (isset($routeParams['tipo'])) {
            $result = $controller->$action($routeParams['tipo']);
        } else {
            $result = $controller->$action();
        }
        
        return $result;
    } catch (Exception $e) {
        error_log('Notificacion Route Error: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Error interno del servidor',
            'code' => 'INTERNAL_ERROR'
        ];
    }
}

// Exportar configuración
return [
    'routes' => $notificacionRoutes,
    'middleware' => [
        'auth' => 'authMiddleware',
        'validation' => 'validateNotificacionData',
        'permissions' => 'checkNotificacionPermissions',
        'filters' => 'validateNotificacionFilters'
    ],
    'handler' => 'handleNotificacionRoute'
];
