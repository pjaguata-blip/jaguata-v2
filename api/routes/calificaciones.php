<?php
/**
 * Rutas de Calificaciones - API Jaguata
 */

// Rutas de calificaciones
$calificacionRoutes = [
    // GET /api/calificaciones
    'GET /' => [
        'controller' => 'CalificacionController',
        'action' => 'apiIndex',
        'middleware' => ['auth']
    ],
    
    // GET /api/calificaciones/{id}
    'GET /{id}' => [
        'controller' => 'CalificacionController',
        'action' => 'apiShow',
        'middleware' => ['auth']
    ],
    
    // POST /api/calificaciones
    'POST /' => [
        'controller' => 'CalificacionController',
        'action' => 'apiStore',
        'middleware' => ['auth', 'validation' => [
            'paseo_id' => ['required' => true, 'type' => 'integer'],
            'calificacion' => ['required' => true, 'type' => 'integer', 'min' => 1, 'max' => 5],
            'comentario' => ['required' => false, 'type' => 'string'],
            'tipo' => ['required' => true, 'type' => 'enum', 'values' => ['paseador', 'mascota']]
        ]]
    ],
    
    // PUT /api/calificaciones/{id}
    'PUT /{id}' => [
        'controller' => 'CalificacionController',
        'action' => 'apiUpdate',
        'middleware' => ['auth']
    ],
    
    // DELETE /api/calificaciones/{id}
    'DELETE /{id}' => [
        'controller' => 'CalificacionController',
        'action' => 'apiDestroy',
        'middleware' => ['auth']
    ],
    
    // GET /api/calificaciones/paseador/{id}
    'GET /paseador/{id}' => [
        'controller' => 'CalificacionController',
        'action' => 'apiGetByPaseador',
        'middleware' => ['auth']
    ],
    
    // GET /api/calificaciones/mascota/{id}
    'GET /mascota/{id}' => [
        'controller' => 'CalificacionController',
        'action' => 'apiGetByMascota',
        'middleware' => ['auth']
    ],
    
    // GET /api/calificaciones/paseo/{id}
    'GET /paseo/{id}' => [
        'controller' => 'CalificacionController',
        'action' => 'apiGetByPaseo',
        'middleware' => ['auth']
    ],
    
    // GET /api/calificaciones/estadisticas
    'GET /estadisticas' => [
        'controller' => 'CalificacionController',
        'action' => 'apiGetEstadisticas',
        'middleware' => ['auth']
    ],
    
    // GET /api/calificaciones/promedio/{tipo}/{id}
    'GET /promedio/{tipo}/{id}' => [
        'controller' => 'CalificacionController',
        'action' => 'apiGetPromedio',
        'middleware' => ['auth']
    ]
];

// Función para validar datos de calificación
function validateCalificacionData($data) {
    $errors = [];
    
    // Validar paseo_id
    if (empty($data['paseo_id'])) {
        $errors[] = 'El ID de paseo es requerido';
    } else {
        $paseoModel = new \Jaguata\Models\Paseo();
        $paseo = $paseoModel->find($data['paseo_id']);
        if (!$paseo) {
            $errors[] = 'Paseo no encontrado';
        } elseif ($paseo['estado'] !== 'completo') {
            $errors[] = 'El paseo debe estar completado para poder calificar';
        }
    }
    
    // Validar calificacion
    if (empty($data['calificacion'])) {
        $errors[] = 'La calificación es requerida';
    } else {
        $calificacion = (int)$data['calificacion'];
        if ($calificacion < 1 || $calificacion > 5) {
            $errors[] = 'La calificación debe estar entre 1 y 5';
        }
    }
    
    // Validar comentario
    if (!empty($data['comentario']) && strlen($data['comentario']) > 500) {
        $errors[] = 'El comentario no puede tener más de 500 caracteres';
    }
    
    // Validar tipo
    if (empty($data['tipo'])) {
        $errors[] = 'El tipo de calificación es requerido';
    } elseif (!in_array($data['tipo'], ['paseador', 'mascota'])) {
        $errors[] = 'El tipo debe ser paseador o mascota';
    }
    
    // Validar que no se haya calificado antes
    if (!empty($data['paseo_id']) && !empty($data['tipo'])) {
        $calificacionModel = new \Jaguata\Models\Calificacion();
        $existing = $calificacionModel->findByPaseoAndTipo($data['paseo_id'], $data['tipo'], $_SESSION['usuario_id']);
        if ($existing) {
            $errors[] = 'Ya has calificado este ' . $data['tipo'];
        }
    }
    
    return $errors;
}

// Función para verificar permisos de calificación
function checkCalificacionPermissions($calificacionId, $usuarioId) {
    $calificacionModel = new \Jaguata\Models\Calificacion();
    $calificacion = $calificacionModel->find($calificacionId);
    
    if (!$calificacion) {
        return [
            'success' => false,
            'error' => 'Calificación no encontrada',
            'code' => 'NOT_FOUND'
        ];
    }
    
    if ($calificacion['rater_id'] != $usuarioId) {
        return [
            'success' => false,
            'error' => 'No tienes permisos para acceder a esta calificación',
            'code' => 'FORBIDDEN'
        ];
    }
    
    return null;
}

// Función para verificar permisos de calificación por paseo
function checkCalificacionPaseoPermissions($paseoId, $usuarioId, $tipo) {
    $paseoModel = new \Jaguata\Models\Paseo();
    $paseo = $paseoModel->find($paseoId);
    
    if (!$paseo) {
        return [
            'success' => false,
            'error' => 'Paseo no encontrado',
            'code' => 'NOT_FOUND'
        ];
    }
    
    // Verificar que el usuario puede calificar este paseo
    $mascotaModel = new \Jaguata\Models\Mascota();
    $mascota = $mascotaModel->find($paseo['mascota_id']);
    
    if ($tipo === 'paseador') {
        // Solo el dueño puede calificar al paseador
        if ($mascota['dueno_id'] != $usuarioId) {
            return [
                'success' => false,
                'error' => 'Solo el dueño puede calificar al paseador',
                'code' => 'FORBIDDEN'
            ];
        }
    } elseif ($tipo === 'mascota') {
        // Solo el paseador puede calificar a la mascota
        if ($paseo['paseador_id'] != $usuarioId) {
            return [
                'success' => false,
                'error' => 'Solo el paseador puede calificar a la mascota',
                'code' => 'FORBIDDEN'
            ];
        }
    }
    
    return null;
}

// Función para validar filtros de calificación
function validateCalificacionFilters($filters) {
    $errors = [];
    
    // Validar tipo
    if (isset($filters['tipo']) && !in_array($filters['tipo'], ['paseador', 'mascota'])) {
        $errors[] = 'El tipo debe ser paseador o mascota';
    }
    
    // Validar calificacion_min
    if (isset($filters['calificacion_min'])) {
        $min = (int)$filters['calificacion_min'];
        if ($min < 1 || $min > 5) {
            $errors[] = 'La calificación mínima debe estar entre 1 y 5';
        }
    }
    
    // Validar calificacion_max
    if (isset($filters['calificacion_max'])) {
        $max = (int)$filters['calificacion_max'];
        if ($max < 1 || $max > 5) {
            $errors[] = 'La calificación máxima debe estar entre 1 y 5';
        }
    }
    
    // Validar fecha_inicio
    if (isset($filters['fecha_inicio']) && !strtotime($filters['fecha_inicio'])) {
        $errors[] = 'La fecha de inicio no es válida';
    }
    
    // Validar fecha_fin
    if (isset($filters['fecha_fin']) && !strtotime($filters['fecha_fin'])) {
        $errors[] = 'La fecha de fin no es válida';
    }
    
    // Validar que fecha_inicio sea anterior a fecha_fin
    if (isset($filters['fecha_inicio']) && isset($filters['fecha_fin'])) {
        $inicio = strtotime($filters['fecha_inicio']);
        $fin = strtotime($filters['fecha_fin']);
        if ($inicio > $fin) {
            $errors[] = 'La fecha de inicio debe ser anterior a la fecha de fin';
        }
    }
    
    return $errors;
}

// Función para procesar filtros de calificación
function processCalificacionFilters($filters) {
    $processed = [];
    
    // Procesar tipo
    if (isset($filters['tipo'])) {
        $processed['tipo'] = $filters['tipo'];
    }
    
    // Procesar calificacion_min
    if (isset($filters['calificacion_min'])) {
        $processed['calificacion_min'] = (int)$filters['calificacion_min'];
    }
    
    // Procesar calificacion_max
    if (isset($filters['calificacion_max'])) {
        $processed['calificacion_max'] = (int)$filters['calificacion_max'];
    }
    
    // Procesar fecha_inicio
    if (isset($filters['fecha_inicio'])) {
        $processed['fecha_inicio'] = $filters['fecha_inicio'];
    }
    
    // Procesar fecha_fin
    if (isset($filters['fecha_fin'])) {
        $processed['fecha_fin'] = $filters['fecha_fin'];
    }
    
    // Procesar limite
    if (isset($filters['limite'])) {
        $processed['limite'] = (int)$filters['limite'];
    } else {
        $processed['limite'] = 20; // Valor por defecto
    }
    
    return $processed;
}

// Función para manejar rutas de calificaciones
function handleCalificacionRoute($method, $path, $params = []) {
    global $calificacionRoutes;
    
    // Construir clave de ruta
    $routeKey = $method . ' /';
    
    // Buscar ruta específica
    $matchedRoute = null;
    $routeParams = [];
    
    foreach ($calificacionRoutes as $route => $config) {
        $routeParts = explode(' ', $route);
        $routeMethod = $routeParts[0];
        $routePath = $routeParts[1];
        
        if ($routeMethod !== $method) continue;
        
        // Convertir ruta a patrón regex
        $pattern = str_replace(['{id}', '{tipo}', '/'], ['([0-9]+)', '([a-z]+)', '\/'], $routePath);
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
                $errors = validateCalificacionData($_POST);
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
        $permissionResult = checkCalificacionPermissions($routeParams['id'], $_SESSION['usuario_id']);
        if ($permissionResult) {
            return $permissionResult;
        }
    }
    
    // Verificar permisos para calificaciones por paseo
    if ($method === 'POST' && isset($_POST['paseo_id']) && isset($_POST['tipo'])) {
        $permissionResult = checkCalificacionPaseoPermissions($_POST['paseo_id'], $_SESSION['usuario_id'], $_POST['tipo']);
        if ($permissionResult) {
            return $permissionResult;
        }
    }
    
    // Validar filtros para GET
    if ($method === 'GET' && !isset($routeParams['id'])) {
        $filters = $_GET;
        $filterErrors = validateCalificacionFilters($filters);
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
        error_log('Calificacion Route Error: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Error interno del servidor',
            'code' => 'INTERNAL_ERROR'
        ];
    }
}

// Exportar configuración
return [
    'routes' => $calificacionRoutes,
    'middleware' => [
        'auth' => 'authMiddleware',
        'validation' => 'validateCalificacionData',
        'permissions' => 'checkCalificacionPermissions',
        'filters' => 'validateCalificacionFilters'
    ],
    'handler' => 'handleCalificacionRoute'
];
