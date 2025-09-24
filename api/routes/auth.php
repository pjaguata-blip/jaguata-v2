<?php
/**
 * Rutas de Autenticación - API Jaguata
 */

// Rutas de autenticación
$authRoutes = [
    // POST /api/auth/login
    'POST /login' => [
        'controller' => 'AuthController',
        'action' => 'apiLogin',
        'middleware' => []
    ],
    
    // POST /api/auth/register
    'POST /register' => [
        'controller' => 'AuthController',
        'action' => 'apiRegister',
        'middleware' => []
    ],
    
    // POST /api/auth/logout
    'POST /logout' => [
        'controller' => 'AuthController',
        'action' => 'apiLogout',
        'middleware' => ['auth']
    ],
    
    // GET /api/auth/profile
    'GET /profile' => [
        'controller' => 'AuthController',
        'action' => 'apiGetProfile',
        'middleware' => ['auth']
    ],
    
    // PUT /api/auth/profile
    'PUT /profile' => [
        'controller' => 'AuthController',
        'action' => 'apiUpdateProfile',
        'middleware' => ['auth']
    ],
    
    // POST /api/auth/change-password
    'POST /change-password' => [
        'controller' => 'AuthController',
        'action' => 'apiChangePassword',
        'middleware' => ['auth']
    ],
    
    // POST /api/auth/forgot-password
    'POST /forgot-password' => [
        'controller' => 'AuthController',
        'action' => 'apiForgotPassword',
        'middleware' => []
    ],
    
    // POST /api/auth/reset-password
    'POST /reset-password' => [
        'controller' => 'AuthController',
        'action' => 'apiResetPassword',
        'middleware' => []
    ],
    
    // POST /api/auth/verify-email
    'POST /verify-email' => [
        'controller' => 'AuthController',
        'action' => 'apiVerifyEmail',
        'middleware' => ['auth']
    ],
    
    // GET /api/auth/session
    'GET /session' => [
        'controller' => 'AuthController',
        'action' => 'apiGetSession',
        'middleware' => ['auth']
    ]
];

// Middleware de autenticación
function authMiddleware() {
    if (!isset($_SESSION['usuario_id'])) {
        return [
            'success' => false,
            'error' => 'No autorizado',
            'code' => 'UNAUTHORIZED'
        ];
    }
    return null;
}

// Middleware de validación de rol
function roleMiddleware($requiredRole) {
    $authResult = authMiddleware();
    if ($authResult) return $authResult;
    
    if ($_SESSION['rol'] !== $requiredRole) {
        return [
            'success' => false,
            'error' => 'Acceso denegado',
            'code' => 'FORBIDDEN'
        ];
    }
    return null;
}

// Middleware de validación de datos
function validationMiddleware($rules) {
    $errors = [];
    
    foreach ($rules as $field => $rule) {
        $value = $_POST[$field] ?? $_GET[$field] ?? null;
        
        if (isset($rule['required']) && $rule['required'] && empty($value)) {
            $errors[] = "El campo {$field} es requerido";
        }
        
        if (!empty($value) && isset($rule['type'])) {
            switch ($rule['type']) {
                case 'email':
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[] = "El campo {$field} debe ser un email válido";
                    }
                    break;
                case 'phone':
                    if (!preg_match('/^[0-9]{4}-[0-9]{3}-[0-9]{3}$/', $value)) {
                        $errors[] = "El campo {$field} debe tener el formato 0981-123-456";
                    }
                    break;
                case 'password':
                    if (strlen($value) < 8) {
                        $errors[] = "El campo {$field} debe tener al menos 8 caracteres";
                    }
                    break;
            }
        }
    }
    
    if (!empty($errors)) {
        return [
            'success' => false,
            'error' => 'Datos inválidos',
            'details' => $errors,
            'code' => 'VALIDATION_ERROR'
        ];
    }
    
    return null;
}

// Función para ejecutar middleware
function executeMiddleware($middlewares) {
    foreach ($middlewares as $middleware) {
        $result = null;
        
        if ($middleware === 'auth') {
            $result = authMiddleware();
        } elseif (is_array($middleware) && $middleware[0] === 'role') {
            $result = roleMiddleware($middleware[1]);
        } elseif (is_array($middleware) && $middleware[0] === 'validation') {
            $result = validationMiddleware($middleware[1]);
        }
        
        if ($result) {
            return $result;
        }
    }
    
    return null;
}

// Función para manejar rutas
function handleAuthRoute($method, $path) {
    global $authRoutes;
    
    $routeKey = $method . ' ' . $path;
    
    if (!isset($authRoutes[$routeKey])) {
        return [
            'success' => false,
            'error' => 'Ruta no encontrada',
            'code' => 'NOT_FOUND'
        ];
    }
    
    $route = $authRoutes[$routeKey];
    
    // Ejecutar middleware
    if (!empty($route['middleware'])) {
        $middlewareResult = executeMiddleware($route['middleware']);
        if ($middlewareResult) {
            return $middlewareResult;
        }
    }
    
    // Ejecutar controlador
    $controllerName = $route['controller'];
    $action = $route['action'];
    
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
        $result = $controller->$action();
        return $result;
    } catch (Exception $e) {
        error_log('Auth Route Error: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Error interno del servidor',
            'code' => 'INTERNAL_ERROR'
        ];
    }
}

// Exportar configuración
return [
    'routes' => $authRoutes,
    'middleware' => [
        'auth' => 'authMiddleware',
        'role' => 'roleMiddleware',
        'validation' => 'validationMiddleware'
    ],
    'handler' => 'handleAuthRoute'
];
