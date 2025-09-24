<?php
/**
 * Rutas de Mascotas - API Jaguata
 */

// Rutas de mascotas
$mascotaRoutes = [
    // GET /api/mascotas
    'GET /' => [
        'controller' => 'MascotaController',
        'action' => 'apiIndex',
        'middleware' => ['auth']
    ],
    
    // GET /api/mascotas/{id}
    'GET /{id}' => [
        'controller' => 'MascotaController',
        'action' => 'apiShow',
        'middleware' => ['auth']
    ],
    
    // POST /api/mascotas
    'POST /' => [
        'controller' => 'MascotaController',
        'action' => 'apiStore',
        'middleware' => ['auth', 'validation' => [
            'nombre' => ['required' => true, 'type' => 'string'],
            'raza' => ['required' => false, 'type' => 'string'],
            'tamano' => ['required' => false, 'type' => 'enum', 'values' => ['pequeno', 'mediano', 'grande']],
            'edad' => ['required' => false, 'type' => 'integer', 'min' => 0, 'max' => 30],
            'observaciones' => ['required' => false, 'type' => 'string']
        ]]
    ],
    
    // PUT /api/mascotas/{id}
    'PUT /{id}' => [
        'controller' => 'MascotaController',
        'action' => 'apiUpdate',
        'middleware' => ['auth', 'validation' => [
            'nombre' => ['required' => true, 'type' => 'string'],
            'raza' => ['required' => false, 'type' => 'string'],
            'tamano' => ['required' => false, 'type' => 'enum', 'values' => ['pequeno', 'mediano', 'grande']],
            'edad' => ['required' => false, 'type' => 'integer', 'min' => 0, 'max' => 30],
            'observaciones' => ['required' => false, 'type' => 'string']
        ]]
    ],
    
    // DELETE /api/mascotas/{id}
    'DELETE /{id}' => [
        'controller' => 'MascotaController',
        'action' => 'apiDestroy',
        'middleware' => ['auth']
    ],
    
    // GET /api/mascotas/{id}/paseos
    'GET /{id}/paseos' => [
        'controller' => 'MascotaController',
        'action' => 'apiGetPaseos',
        'middleware' => ['auth']
    ],
    
    // POST /api/mascotas/{id}/foto
    'POST /{id}/foto' => [
        'controller' => 'MascotaController',
        'action' => 'apiUploadFoto',
        'middleware' => ['auth']
    ],
    
    // DELETE /api/mascotas/{id}/foto
    'DELETE /{id}/foto' => [
        'controller' => 'MascotaController',
        'action' => 'apiDeleteFoto',
        'middleware' => ['auth']
    ]
];

// Función para validar datos de mascota
function validateMascotaData($data) {
    $errors = [];
    
    // Validar nombre
    if (empty($data['nombre'])) {
        $errors[] = 'El nombre es requerido';
    } elseif (strlen($data['nombre']) > 50) {
        $errors[] = 'El nombre no puede tener más de 50 caracteres';
    }
    
    // Validar raza
    if (!empty($data['raza']) && strlen($data['raza']) > 50) {
        $errors[] = 'La raza no puede tener más de 50 caracteres';
    }
    
    // Validar tamaño
    if (!empty($data['tamano']) && !in_array($data['tamano'], ['pequeno', 'mediano', 'grande'])) {
        $errors[] = 'El tamaño debe ser: pequeno, mediano o grande';
    }
    
    // Validar edad
    if (!empty($data['edad'])) {
        $edad = (int)$data['edad'];
        if ($edad < 0 || $edad > 30) {
            $errors[] = 'La edad debe estar entre 0 y 30 años';
        }
    }
    
    // Validar observaciones
    if (!empty($data['observaciones']) && strlen($data['observaciones']) > 1000) {
        $errors[] = 'Las observaciones no pueden tener más de 1000 caracteres';
    }
    
    return $errors;
}

// Función para verificar permisos de mascota
function checkMascotaPermissions($mascotaId, $usuarioId) {
    $mascotaModel = new \Jaguata\Models\Mascota();
    $mascota = $mascotaModel->find($mascotaId);
    
    if (!$mascota) {
        return [
            'success' => false,
            'error' => 'Mascota no encontrada',
            'code' => 'NOT_FOUND'
        ];
    }
    
    if ($mascota['dueno_id'] != $usuarioId) {
        return [
            'success' => false,
            'error' => 'No tienes permisos para acceder a esta mascota',
            'code' => 'FORBIDDEN'
        ];
    }
    
    return null;
}

// Función para manejar rutas de mascotas
function handleMascotaRoute($method, $path, $params = []) {
    global $mascotaRoutes;
    
    // Construir clave de ruta
    $routeKey = $method . ' /';
    
    // Buscar ruta específica
    $matchedRoute = null;
    $routeParams = [];
    
    foreach ($mascotaRoutes as $route => $config) {
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
            } elseif (is_array($middleware) && $middleware[0] === 'validation') {
                $validationRules = $middleware[1];
                $errors = validateMascotaData($_POST);
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
        $permissionResult = checkMascotaPermissions($routeParams['id'], $_SESSION['usuario_id']);
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
        error_log('Mascota Route Error: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Error interno del servidor',
            'code' => 'INTERNAL_ERROR'
        ];
    }
}

// Exportar configuración
return [
    'routes' => $mascotaRoutes,
    'middleware' => [
        'auth' => 'authMiddleware',
        'validation' => 'validateMascotaData',
        'permissions' => 'checkMascotaPermissions'
    ],
    'handler' => 'handleMascotaRoute'
];
