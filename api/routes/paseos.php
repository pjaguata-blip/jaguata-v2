<?php
/**
 * Rutas de Paseos - API Jaguata
 */

// Rutas de paseos
$paseoRoutes = [
    // GET /api/paseos
    'GET /' => [
        'controller' => 'PaseoController',
        'action' => 'apiIndex',
        'middleware' => ['auth']
    ],
    
    // GET /api/paseos/{id}
    'GET /{id}' => [
        'controller' => 'PaseoController',
        'action' => 'apiShow',
        'middleware' => ['auth']
    ],
    
    // POST /api/paseos
    'POST /' => [
        'controller' => 'PaseoController',
        'action' => 'apiStore',
        'middleware' => ['auth', 'validation' => [
            'mascota_id' => ['required' => true, 'type' => 'integer'],
            'paseador_id' => ['required' => true, 'type' => 'integer'],
            'inicio' => ['required' => true, 'type' => 'datetime'],
            'duracion' => ['required' => true, 'type' => 'integer', 'min' => 15, 'max' => 240]
        ]]
    ],
    
    // PUT /api/paseos/{id}
    'PUT /{id}' => [
        'controller' => 'PaseoController',
        'action' => 'apiUpdate',
        'middleware' => ['auth']
    ],
    
    // DELETE /api/paseos/{id}
    'DELETE /{id}' => [
        'controller' => 'PaseoController',
        'action' => 'apiDestroy',
        'middleware' => ['auth']
    ],
    
    // PUT /api/paseos/{id}/confirmar
    'PUT /{id}/confirmar' => [
        'controller' => 'PaseoController',
        'action' => 'apiConfirmar',
        'middleware' => ['auth', 'role' => 'paseador']
    ],
    
    // PUT /api/paseos/{id}/iniciar
    'PUT /{id}/iniciar' => [
        'controller' => 'PaseoController',
        'action' => 'apiIniciar',
        'middleware' => ['auth', 'role' => 'paseador']
    ],
    
    // PUT /api/paseos/{id}/completar
    'PUT /{id}/completar' => [
        'controller' => 'PaseoController',
        'action' => 'apiCompletar',
        'middleware' => ['auth', 'role' => 'paseador']
    ],
    
    // PUT /api/paseos/{id}/cancelar
    'PUT /{id}/cancelar' => [
        'controller' => 'PaseoController',
        'action' => 'apiCancelar',
        'middleware' => ['auth']
    ],
    
    // GET /api/paseos/{id}/ubicacion
    'GET /{id}/ubicacion' => [
        'controller' => 'PaseoController',
        'action' => 'apiGetUbicacion',
        'middleware' => ['auth']
    ],
    
    // PUT /api/paseos/{id}/ubicacion
    'PUT /{id}/ubicacion' => [
        'controller' => 'PaseoController',
        'action' => 'apiUpdateUbicacion',
        'middleware' => ['auth', 'role' => 'paseador']
    ],
    
    // GET /api/paseos/{id}/fotos
    'GET /{id}/fotos' => [
        'controller' => 'PaseoController',
        'action' => 'apiGetFotos',
        'middleware' => ['auth']
    ],
    
    // POST /api/paseos/{id}/fotos
    'POST /{id}/fotos' => [
        'controller' => 'PaseoController',
        'action' => 'apiUploadFotos',
        'middleware' => ['auth', 'role' => 'paseador']
    ]
];

// Función para validar datos de paseo
function validatePaseoData($data) {
    $errors = [];
    
    // Validar mascota_id
    if (empty($data['mascota_id'])) {
        $errors[] = 'El ID de mascota es requerido';
    } else {
        $mascotaModel = new \Jaguata\Models\Mascota();
        $mascota = $mascotaModel->find($data['mascota_id']);
        if (!$mascota || $mascota['dueno_id'] != $_SESSION['usuario_id']) {
            $errors[] = 'Mascota no válida o no tienes permisos';
        }
    }
    
    // Validar paseador_id
    if (empty($data['paseador_id'])) {
        $errors[] = 'El ID de paseador es requerido';
    } else {
        $paseadorModel = new \App\Models\Paseador();
        $paseador = $paseadorModel->find($data['paseador_id']);
        if (!$paseador || !$paseador['disponibilidad']) {
            $errors[] = 'Paseador no válido o no disponible';
        }
    }
    
    // Validar inicio
    if (empty($data['inicio'])) {
        $errors[] = 'La fecha y hora de inicio es requerida';
    } else {
        $inicio = new DateTime($data['inicio']);
        $ahora = new DateTime();
        $minimo = (new DateTime())->add(new DateInterval('PT2H')); // 2 horas mínimo
        
        if ($inicio < $minimo) {
            $errors[] = 'El paseo debe solicitarse con al menos 2 horas de anticipación';
        }
        
        if ($inicio < $ahora) {
            $errors[] = 'La fecha de inicio no puede ser en el pasado';
        }
    }
    
    // Validar duración
    if (empty($data['duracion'])) {
        $errors[] = 'La duración es requerida';
    } else {
        $duracion = (int)$data['duracion'];
        if ($duracion < 15 || $duracion > 240) {
            $errors[] = 'La duración debe estar entre 15 y 240 minutos';
        }
    }
    
    return $errors;
}

// Función para verificar permisos de paseo
function checkPaseoPermissions($paseoId, $usuarioId, $rol) {
    $paseoModel = new \Jaguata\Models\Paseo();
    $paseo = $paseoModel->find($paseoId);
    
    if (!$paseo) {
        return [
            'success' => false,
            'error' => 'Paseo no encontrado',
            'code' => 'NOT_FOUND'
        ];
    }
    
    // Verificar permisos según rol
    if ($rol === 'dueno') {
        $mascotaModel = new \Jaguata\Models\Mascota();
        $mascota = $mascotaModel->find($paseo['mascota_id']);
        if (!$mascota || $mascota['dueno_id'] != $usuarioId) {
            return [
                'success' => false,
                'error' => 'No tienes permisos para acceder a este paseo',
                'code' => 'FORBIDDEN'
            ];
        }
    } elseif ($rol === 'paseador') {
        if ($paseo['paseador_id'] != $usuarioId) {
            return [
                'success' => false,
                'error' => 'No tienes permisos para acceder a este paseo',
                'code' => 'FORBIDDEN'
            ];
        }
    }
    
    return null;
}

// Función para validar transición de estado
function validateEstadoTransition($estadoActual, $nuevoEstado, $rol) {
    $transicionesValidas = [
        'solicitado' => ['confirmado', 'cancelado'],
        'confirmado' => ['en_curso', 'cancelado'],
        'en_curso' => ['completo', 'cancelado'],
        'completo' => [],
        'cancelado' => []
    ];
    
    if (!isset($transicionesValidas[$estadoActual])) {
        return 'Estado actual no válido';
    }
    
    if (!in_array($nuevoEstado, $transicionesValidas[$estadoActual])) {
        return 'Transición de estado no válida';
    }
    
    // Validar permisos por rol
    if ($nuevoEstado === 'confirmado' && $rol !== 'paseador') {
        return 'Solo el paseador puede confirmar un paseo';
    }
    
    if ($nuevoEstado === 'iniciar' && $rol !== 'paseador') {
        return 'Solo el paseador puede iniciar un paseo';
    }
    
    if ($nuevoEstado === 'completar' && $rol !== 'paseador') {
        return 'Solo el paseador puede completar un paseo';
    }
    
    return null;
}

// Función para manejar rutas de paseos
function handlePaseoRoute($method, $path, $params = []) {
    global $paseoRoutes;
    
    // Construir clave de ruta
    $routeKey = $method . ' /';
    
    // Buscar ruta específica
    $matchedRoute = null;
    $routeParams = [];
    
    foreach ($paseoRoutes as $route => $config) {
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
                $errors = validatePaseoData($_POST);
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
        $permissionResult = checkPaseoPermissions($routeParams['id'], $_SESSION['usuario_id'], $_SESSION['rol']);
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
        error_log('Paseo Route Error: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Error interno del servidor',
            'code' => 'INTERNAL_ERROR'
        ];
    }
}

// Exportar configuración
return [
    'routes' => $paseoRoutes,
    'middleware' => [
        'auth' => 'authMiddleware',
        'role' => 'roleMiddleware',
        'validation' => 'validatePaseoData',
        'permissions' => 'checkPaseoPermissions',
        'estado' => 'validateEstadoTransition'
    ],
    'handler' => 'handlePaseoRoute'
];
