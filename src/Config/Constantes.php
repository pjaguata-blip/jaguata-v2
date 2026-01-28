<?php

// =========================
// CONSTANTES DE LA APLICACIÓN JAGUATA
// =========================

// Configuración de la aplicación
if (!defined('APP_NAME')) {
    define('APP_NAME', 'Jaguata');
}
if (!defined('APP_VERSION')) {
    define('APP_VERSION', '1.0.0');
}
if (!defined('APP_DESCRIPTION')) {
    define('APP_DESCRIPTION', 'Sistema de Paseo de Mascotas en Paraguay');
}
if (!defined('APP_AUTHOR')) {
    define('APP_AUTHOR', 'Equipo Jaguata');
}

// URLs y rutas (por defecto, AppConfig los recalcula dinámicamente)
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/jaguata');
}
if (!defined('API_URL')) {
    define('API_URL', BASE_URL . '/api');
}
if (!defined('ASSETS_URL')) {
    define('ASSETS_URL', BASE_URL . '/assets');
}
if (!defined('UPLOADS_URL')) {
    define('UPLOADS_URL', BASE_URL . '/assets/uploads');
}

// Rutas de archivos
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 2));
}
if (!defined('SRC_PATH')) {
    define('SRC_PATH', ROOT_PATH . '/src');
}
if (!defined('PUBLIC_PATH')) {
    define('PUBLIC_PATH', ROOT_PATH . '/public');
}
if (!defined('ASSETS_PATH')) {
    define('ASSETS_PATH', ROOT_PATH . '/assets');
}
if (!defined('UPLOADS_PATH')) {
    define('UPLOADS_PATH', ASSETS_PATH . '/uploads');
}

// Configuración de base de datos (AppConfig puede sobrescribir leyendo .env)
if (!defined('DB_HOST')) {
    define('DB_HOST', '127.0.0.1'); // ✅ TCP real
}
if (!defined('DB_PORT')) {
    define('DB_PORT', '3307'); // ✅ tu nuevo puerto
}

if (!defined('DB_NAME')) {
    define('DB_NAME', 'jaguata');
}
if (!defined('DB_USER')) {
    define('DB_USER', 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', '');
}
if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', 'utf8mb4');
}

// Configuración de sesión
if (!defined('SESSION_NAME')) {
    define('SESSION_NAME', 'jaguata_session');
}
if (!defined('SESSION_LIFETIME')) {
    define('SESSION_LIFETIME', 7200); // 2 horas
}
if (!defined('COOKIE_LIFETIME')) {
    define('COOKIE_LIFETIME', 86400 * 30); // 30 días
}

// Configuración de archivos
if (!defined('MAX_FILE_SIZE')) {
    define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
}
if (!defined('ALLOWED_IMAGE_TYPES')) {
    define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
}
if (!defined('UPLOAD_QUALITY')) {
    define('UPLOAD_QUALITY', 85);
}

// Configuración de paginación
if (!defined('ITEMS_PER_PAGE')) {
    define('ITEMS_PER_PAGE', 10);
}
if (!defined('MAX_ITEMS_PER_PAGE')) {
    define('MAX_ITEMS_PER_PAGE', 50);
}

// Estados básicos
if (!defined('ESTADO_ACTIVO')) {
    define('ESTADO_ACTIVO', 1);
}
if (!defined('ESTADO_INACTIVO')) {
    define('ESTADO_INACTIVO', 0);
}
if (!defined('ESTADO_PENDIENTE')) {
    define('ESTADO_PENDIENTE', 2);
}
if (!defined('ESTADO_CANCELADO')) {
    define('ESTADO_CANCELADO', 3);
}
if (!defined('ESTADO_COMPLETADO')) {
    define('ESTADO_COMPLETADO', 4);
}

// Tipos de usuario
if (!defined('TIPO_USUARIO_ADMIN')) {
    define('TIPO_USUARIO_ADMIN', 'admin');
}
if (!defined('TIPO_USUARIO_CLIENTE')) {
    define('TIPO_USUARIO_CLIENTE', 'dueno');
}
if (!defined('TIPO_USUARIO_PASEADOR')) {
    define('TIPO_USUARIO_PASEADOR', 'paseador');
}

// Tamaños de mascotas (individuales)
if (!defined('TAMANO_PEQUENO')) {
    define('TAMANO_PEQUENO', 'pequeno');
}
if (!defined('TAMANO_MEDIANO')) {
    define('TAMANO_MEDIANO', 'mediano');
}
if (!defined('TAMANO_GRANDE')) {
    define('TAMANO_GRANDE', 'grande');
}
if (!defined('TAMANO_EXTRA_GRANDE')) {
    define('TAMANO_EXTRA_GRANDE', 'extra_grande');
}

// Estados de paseo
if (!defined('PASEO_PENDIENTE')) {
    define('PASEO_PENDIENTE', 'pendiente');
}
if (!defined('PASEO_CONFIRMADO')) {
    define('PASEO_CONFIRMADO', 'confirmado');
}
if (!defined('PASEO_EN_PROGRESO')) {
    define('PASEO_EN_PROGRESO', 'en_curso');
}
if (!defined('PASEO_COMPLETADO')) {
    define('PASEO_COMPLETADO', 'completo');
}
if (!defined('PASEO_CANCELADO')) {
    define('PASEO_CANCELADO', 'cancelado');
}

// Estados de pago
if (!defined('PAGO_PENDIENTE')) {
    define('PAGO_PENDIENTE', 'pendiente');
}
if (!defined('PAGO_PROCESANDO')) {
    define('PAGO_PROCESANDO', 'procesando');
}
if (!defined('PAGO_COMPLETADO')) {
    define('PAGO_COMPLETADO', 'procesado');
}
if (!defined('PAGO_FALLIDO')) {
    define('PAGO_FALLIDO', 'fallido');
}
if (!defined('PAGO_REEMBOLSADO')) {
    define('PAGO_REEMBOLSADO', 'reembolsado');
}

// Tipos de notificación (arrays + individuales)
if (!defined('NOTIFICATION_TYPES')) {
    define('NOTIFICATION_TYPES', [
        'paseo_Pendiente' => 'Paseo Pendiente',
        'paseo_confirmado' => 'Paseo Confirmado',
        'paseo_iniciado' => 'Paseo Iniciado',
        'paseo_completado' => 'Paseo Completado',
        'paseo_cancelado' => 'Paseo Cancelado',
        'pago_procesado' => 'Pago Procesado',
        'pago_fallido' => 'Pago Fallido',
        'calificacion_recibida' => 'Calificación Recibida',
        'nueva_solicitud' => 'Nueva Solicitud',
        'mensaje_nuevo' => 'Mensaje Nuevo'
    ]);
}

// Roles (array)
if (!defined('ROLES')) {
    define('ROLES', [
        'dueno' => 'Dueño de Mascota',
        'paseador' => 'Paseador',
        'admin' => 'Administrador'
    ]);
}

// Estados de paseo (UI)
if (!defined('PASEO_ESTADOS')) {
    define('PASEO_ESTADOS', [
        'Pendiente' => 'pendiente',
        'confirmado' => 'Confirmado',
        'en_curso' => 'En Curso',
        'completo' => 'Completo',
        'cancelado' => 'Cancelado'
    ]);
}

// Estados de pago (UI)
if (!defined('PAGO_ESTADOS')) {
    define('PAGO_ESTADOS', [
        'pendiente' => 'pendiente',
        'procesado' => 'Procesado',
        'fallido' => 'Fallido'
    ]);
}

// Métodos de pago
if (!defined('METODOS_PAGO')) {
    define('METODOS_PAGO', [
        'transferencia' => 'Transferencia Bancaria',
        'efectivo' => 'Pago en Efectivo'
    ]);
}

// Tamaños de mascotas con rangos
if (!defined('TAMANOS_MASCOTA')) {
    define('TAMANOS_MASCOTA', [
        'pequeno' => [
            'label' => 'Pequeño',
            'rango' => '0 - 10 kg'
        ],
        'mediano' => [
            'label' => 'Mediano',
            'rango' => '11 - 25 kg'
        ],
        'grande' => [
            'label' => 'Grande',
            'rango' => '26 - 45 kg'
        ],
        'extra_grande' => [
            'label' => 'Extra Grande',
            'rango' => '46+ kg'
        ]
    ]);
}

// Zonas disponibles
if (!defined('ZONAS_DISPONIBLES')) {
    define('ZONAS_DISPONIBLES', [
        'Asunción Centro',
        'Lambaré',
        'San Lorenzo',
        'Fernando de la Mora',
        'Villa Elisa',
        'Capiatá',
        'Luque',
        'Mariano Roque Alonso',
        'Ñemby',
        'San Antonio'
    ]);
}

// Puntos por actividades
if (!defined('PUNTOS_ACTIVIDADES')) {
    define('PUNTOS_ACTIVIDADES', [
        'registro_usuario' => 20,
        'paseo_completado' => 10,
        'calificacion_realizada' => 5,
        'primer_paseo' => 50,
        'usuario_referido' => 100,
        'paseo_confirmado' => 5,
        'pago_realizado' => 15,
        'perfil_completado' => 10,
        'mascota_registrada' => 5,
        'metodo_pago_agregado' => 5
    ]);
}

// Configuración financiera
if (!defined('TARIFA_PLATAFORMA')) {
    define('TARIFA_PLATAFORMA', 0.10);
}
if (!defined('TARIFA_MINIMA')) {
    define('TARIFA_MINIMA', 1000);
}
if (!defined('TARIFA_MAXIMA')) {
    define('TARIFA_MAXIMA', 100000);
}
if (!defined('PRECIO_BASE_30MIN')) {
    define('PRECIO_BASE_30MIN', 50000);
}
if (!defined('PRECIO_BASE_60MIN')) {
    define('PRECIO_BASE_60MIN', 80000);
}
if (!defined('PRECIO_BASE_90MIN')) {
    define('PRECIO_BASE_90MIN', 110000);
}
if (!defined('PRECIO_BASE_120MIN')) {
    define('PRECIO_BASE_120MIN', 140000);
}
if (!defined('COMISION_PLATAFORMA')) {
    define('COMISION_PLATAFORMA', 0.15);
}
if (!defined('IVA_PARAGUAYO')) {
    define('IVA_PARAGUAYO', 0.10);
}

// Tiempo / paseos
if (!defined('TIEMPO_MINIMO_PASEO')) {
    define('TIEMPO_MINIMO_PASEO', 15);
}
if (!defined('TIEMPO_MAXIMO_PASEO')) {
    define('TIEMPO_MAXIMO_PASEO', 240);
}
if (!defined('ANTICIPACION_MINIMA')) {
    define('ANTICIPACION_MINIMA', 2);
}
if (!defined('ANTICIPACION_MAXIMA')) {
    define('ANTICIPACION_MAXIMA', 168);
}
if (!defined('DURACION_30_MIN')) {
    define('DURACION_30_MIN', 30);
}
if (!defined('DURACION_60_MIN')) {
    define('DURACION_60_MIN', 60);
}
if (!defined('DURACION_90_MIN')) {
    define('DURACION_90_MIN', 90);
}
if (!defined('DURACION_120_MIN')) {
    define('DURACION_120_MIN', 120);
}

// Calificaciones
if (!defined('CALIFICACION_MINIMA')) {
    define('CALIFICACION_MINIMA', 1);
}
if (!defined('CALIFICACION_MAXIMA')) {
    define('CALIFICACION_MAXIMA', 5);
}
if (!defined('CALIFICACION_DEFAULT')) {
    define('CALIFICACION_DEFAULT', 5);
}
if (!defined('CALIFICACION_MIN')) {
    define('CALIFICACION_MIN', 1);
}
if (!defined('CALIFICACION_MAX')) {
    define('CALIFICACION_MAX', 5);
}

// Notificaciones
if (!defined('NOTIFICACIONES_EXPIRACION')) {
    define('NOTIFICACIONES_EXPIRACION', 30);
}
if (!defined('NOTIFICACIONES_MAXIMAS')) {
    define('NOTIFICACIONES_MAXIMAS', 100);
}
if (!defined('NOTIF_NUEVO_PASEO')) {
    define('NOTIF_NUEVO_PASEO', 'nuevo_paseo');
}
if (!defined('NOTIF_PASEO_CONFIRMADO')) {
    define('NOTIF_PASEO_CONFIRMADO', 'paseo_confirmado');
}
if (!defined('NOTIF_PASEO_INICIADO')) {
    define('NOTIF_PASEO_INICIADO', 'paseo_iniciado');
}
if (!defined('NOTIF_PASEO_COMPLETADO')) {
    define('NOTIF_PASEO_COMPLETADO', 'paseo_completado');
}
if (!defined('NOTIF_PASEO_CANCELADO')) {
    define('NOTIF_PASEO_CANCELADO', 'paseo_cancelado');
}
if (!defined('NOTIF_PAGO_RECIBIDO')) {
    define('NOTIF_PAGO_RECIBIDO', 'pago_recibido');
}
if (!defined('NOTIF_NUEVA_CALIFICACION')) {
    define('NOTIF_NUEVA_CALIFICACION', 'nueva_calificacion');
}

// Seguridad / login
if (!defined('MAX_LOGIN_ATTEMPTS')) {
    define('MAX_LOGIN_ATTEMPTS', 5);
}
if (!defined('LOGIN_LOCKOUT_TIME')) {
    define('LOGIN_LOCKOUT_TIME', 900);
}
if (!defined('PASSWORD_MIN_LENGTH')) {
    define('PASSWORD_MIN_LENGTH', 8);
}
if (!defined('PASSWORD_REQUIRE_SPECIAL')) {
    define('PASSWORD_REQUIRE_SPECIAL', true);
}
if (!defined('MAX_MASCOTAS_POR_USUARIO')) {
    define('MAX_MASCOTAS_POR_USUARIO', 10);
}
if (!defined('MAX_PASEOS_SIMULTANEOS')) {
    define('MAX_PASEOS_SIMULTANEOS', 3);
}
if (!defined('MAX_INTENTOS_LOGIN')) {
    define('MAX_INTENTOS_LOGIN', 5);
}
if (!defined('TIEMPO_BLOQUEO_LOGIN')) {
    define('TIEMPO_BLOQUEO_LOGIN', 900);
}

// API
if (!defined('API_RATE_LIMIT')) {
    define('API_RATE_LIMIT', 100);
}
if (!defined('API_TOKEN_LIFETIME')) {
    define('API_TOKEN_LIFETIME', 3600);
}
if (!defined('API_VERSION')) {
    define('API_VERSION', 'v1');
}
if (!defined('API_TOKEN_EXPIRY')) {
    define('API_TOKEN_EXPIRY', 3600);
}

// Email
if (!defined('SMTP_HOST')) {
    define('SMTP_HOST', 'smtp.gmail.com');
}
if (!defined('SMTP_PORT')) {
    define('SMTP_PORT', 587);
}
if (!defined('SMTP_USERNAME')) {
    define('SMTP_USERNAME', '');
}
if (!defined('SMTP_PASSWORD')) {
    define('SMTP_PASSWORD', '');
}
if (!defined('SMTP_FROM_EMAIL')) {
    define('SMTP_FROM_EMAIL', 'noreply@jaguata.com');
}
if (!defined('SMTP_FROM_NAME')) {
    define('SMTP_FROM_NAME', 'Jaguata');
}

// Localización
if (!defined('CURRENCY')) {
    define('CURRENCY', 'PYG');
}
if (!defined('CURRENCY_SYMBOL')) {
    define('CURRENCY_SYMBOL', '₲');
}
if (!defined('CURRENCY_DECIMALS')) {
    define('CURRENCY_DECIMALS', 0);
}
if (!defined('DEFAULT_LANGUAGE')) {
    define('DEFAULT_LANGUAGE', 'es');
}
if (!defined('SUPPORTED_LANGUAGES')) {
    define('SUPPORTED_LANGUAGES', ['es', 'en']);
}
if (!defined('LANG_DEFAULT')) {
    define('LANG_DEFAULT', 'es');
}
if (!defined('LANG_AVAILABLE')) {
    define('LANG_AVAILABLE', ['es', 'pt', 'en']);
}
if (!defined('TIMEZONE')) {
    define('TIMEZONE', 'America/Asuncion');
}
if (!defined('TIMEZONE_DEFAULT')) {
    define('TIMEZONE_DEFAULT', 'America/Asuncion');
}

// Logs / debug
if (!defined('LOG_LEVEL')) {
    define('LOG_LEVEL', 'INFO');
}
if (!defined('LOG_FILE')) {
    define('LOG_FILE', ROOT_PATH . '/logs/app.log');
}
if (!defined('LOG_MAX_SIZE')) {
    define('LOG_MAX_SIZE', 10 * 1024 * 1024);
}
if (!defined('LOG_MAX_FILES')) {
    define('LOG_MAX_FILES', 5);
}
if (!defined('LOG_LEVEL_DEBUG')) {
    define('LOG_LEVEL_DEBUG', 'debug');
}
if (!defined('LOG_LEVEL_INFO')) {
    define('LOG_LEVEL_INFO', 'info');
}
if (!defined('LOG_LEVEL_WARNING')) {
    define('LOG_LEVEL_WARNING', 'warning');
}
if (!defined('LOG_LEVEL_ERROR')) {
    define('LOG_LEVEL_ERROR', 'error');
}
if (!defined('LOG_LEVEL_CRITICAL')) {
    define('LOG_LEVEL_CRITICAL', 'critical');
}
if (!defined('DEBUG_MODE')) {
    define('DEBUG_MODE', true);
}
if (!defined('SHOW_ERRORS')) {
    define('SHOW_ERRORS', true);
}
if (!defined('LOG_QUERIES')) {
    define('LOG_QUERIES', false);
}

// Cache
if (!defined('CACHE_ENABLED')) {
    define('CACHE_ENABLED', true);
}
if (!defined('CACHE_LIFETIME')) {
    define('CACHE_LIFETIME', 3600);
}
if (!defined('CACHE_PATH')) {
    define('CACHE_PATH', ROOT_PATH . '/cache');
}
if (!defined('CACHE_TTL_SHORT')) {
    define('CACHE_TTL_SHORT', 300);
}
if (!defined('CACHE_TTL_MEDIUM')) {
    define('CACHE_TTL_MEDIUM', 3600);
}
if (!defined('CACHE_TTL_LONG')) {
    define('CACHE_TTL_LONG', 86400);
}

// Backups
if (!defined('BACKUP_ENABLED')) {
    define('BACKUP_ENABLED', true);
}
if (!defined('BACKUP_PATH')) {
    define('BACKUP_PATH', ROOT_PATH . '/backups');
}
if (!defined('BACKUP_RETENTION_DAYS')) {
    define('BACKUP_RETENTION_DAYS', 30);
}

// Reportes
if (!defined('REPORT_FORMATS')) {
    define('REPORT_FORMATS', ['pdf', 'excel', 'csv']);
}
if (!defined('REPORT_MAX_RECORDS')) {
    define('REPORT_MAX_RECORDS', 10000);
}

// Validación
if (!defined('VALIDATION_RULES')) {
    define('VALIDATION_RULES', [
        'email' => '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
        'telefono' => '/^[0-9]{4}-[0-9]{3}-[0-9]{3}$/',
        'password' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/'
    ]);
}
if (!defined('REGEX_EMAIL')) {
    define('REGEX_EMAIL', '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/');
}
if (!defined('REGEX_PHONE_PY')) {
    define('REGEX_PHONE_PY', '/^(\+595|0)(9[0-9]{8})$/');
}
if (!defined('REGEX_CEDULA_PY')) {
    define('REGEX_CEDULA_PY', '/^[0-9]{1,8}$/');
}
if (!defined('PHONE_MIN_LENGTH')) {
    define('PHONE_MIN_LENGTH', 9);
}
if (!defined('PHONE_MAX_LENGTH')) {
    define('PHONE_MAX_LENGTH', 15);
}

// Distancias / horarios
if (!defined('RADIO_BUSQUEDA_KM')) {
    define('RADIO_BUSQUEDA_KM', 10);
}
if (!defined('DISTANCIA_MINIMA_KM')) {
    define('DISTANCIA_MINIMA_KM', 1);
}
if (!defined('DISTANCIA_MAXIMA_KM')) {
    define('DISTANCIA_MAXIMA_KM', 50);
}
if (!defined('HORA_INICIO_SERVICIO')) {
    define('HORA_INICIO_SERVICIO', '06:00');
}
if (!defined('HORA_FIN_SERVICIO')) {
    define('HORA_FIN_SERVICIO', '22:00');
}
if (!defined('MINUTOS_ANTICIPACION')) {
    define('MINUTOS_ANTICIPACION', 60);
}

// Emergencia / soporte
if (!defined('TELEFONO_EMERGENCIA')) {
    define('TELEFONO_EMERGENCIA', '911');
}
if (!defined('TELEFONO_SOPORTE')) {
    define('TELEFONO_SOPORTE', '+595971234567');
}

// Mensajes
if (!defined('MESSAGES')) {
    define('MESSAGES', [
        'success' => [
            'usuario_creado' => 'Usuario creado exitosamente',
            'mascota_agregada' => 'Mascota agregada exitosamente',
            'paseo_Pendiente' => 'Paseo Pendiente exitosamente',
            'pago_procesado' => 'Pago procesado exitosamente',
            'perfil_actualizado' => 'Perfil actualizado exitosamente'
        ],
        'error' => [
            'usuario_no_encontrado' => 'Usuario no encontrado',
            'credenciales_incorrectas' => 'Credenciales incorrectas',
            'sesion_expirada' => 'Sesión expirada',
            'permisos_insuficientes' => 'Permisos insuficientes',
            'datos_invalidos' => 'Datos inválidos'
        ],
        'warning' => [
            'paseo_cancelado' => 'Paseo cancelado',
            'pago_pendiente' => 'Pago pendiente',
            'notificacion_expirada' => 'Notificación expirada'
        ]
    ]);
}
if (!defined('MSG_ERROR_GENERICO')) {
    define('MSG_ERROR_GENERICO', 'Ha ocurrido un error inesperado. Por favor, inténtelo más tarde.');
}
if (!defined('MSG_ERROR_VALIDACION')) {
    define('MSG_ERROR_VALIDACION', 'Los datos proporcionados no son válidos.');
}
if (!defined('MSG_ERROR_AUTENTICACION')) {
    define('MSG_ERROR_AUTENTICACION', 'Credenciales inválidas.');
}
if (!defined('MSG_ERROR_AUTORIZACION')) {
    define('MSG_ERROR_AUTORIZACION', 'No tienes permisos para realizar esta acción.');
}
if (!defined('MSG_ERROR_NO_ENCONTRADO')) {
    define('MSG_ERROR_NO_ENCONTRADO', 'El recurso solicitado no fue encontrado.');
}
if (!defined('MSG_EXITO_CREADO')) {
    define('MSG_EXITO_CREADO', 'Creado exitosamente.');
}
if (!defined('MSG_EXITO_ACTUALIZADO')) {
    define('MSG_EXITO_ACTUALIZADO', 'Actualizado exitosamente.');
}
if (!defined('MSG_EXITO_ELIMINADO')) {
    define('MSG_EXITO_ELIMINADO', 'Eliminado exitosamente.');
}
if (!defined('MSG_EXITO_ENVIADO')) {
    define('MSG_EXITO_ENVIADO', 'Enviado exitosamente.');
}

// Colores UI
if (!defined('COLORS')) {
    define('COLORS', [
        'primary' => '#2E7D32',
        'secondary' => '#4CAF50',
        'accent' => '#FFC107',
        'success' => '#4CAF50',
        'warning' => '#FF9800',
        'error' => '#F44336',
        'info' => '#2196F3',
        'light' => '#F5F5F5',
        'dark' => '#212121'
    ]);
}

// Iconos UI
if (!defined('ICONS')) {
    define('ICONS', [
        'mascota' => '🐕',
        'paseador' => '🚶‍♂️',
        'paseo' => '🚶‍♀️',
        'pago' => '💳',
        'notificacion' => '🔔',
        'calificacion' => '⭐',
        'puntos' => '🏆',
        'admin' => '👨‍💼'
    ]);
}

// Métodos HTTP
if (!defined('HTTP_GET')) {
    define('HTTP_GET', 'GET');
}
if (!defined('HTTP_POST')) {
    define('HTTP_POST', 'POST');
}
if (!defined('HTTP_PUT')) {
    define('HTTP_PUT', 'PUT');
}
if (!defined('HTTP_DELETE')) {
    define('HTTP_DELETE', 'DELETE');
}
if (!defined('HTTP_PATCH')) {
    define('HTTP_PATCH', 'PATCH');
}

// Códigos HTTP
if (!defined('HTTP_OK')) {
    define('HTTP_OK', 200);
}
if (!defined('HTTP_CREATED')) {
    define('HTTP_CREATED', 201);
}
if (!defined('HTTP_NO_CONTENT')) {
    define('HTTP_NO_CONTENT', 204);
}
if (!defined('HTTP_BAD_REQUEST')) {
    define('HTTP_BAD_REQUEST', 400);
}
if (!defined('HTTP_UNAUTHORIZED')) {
    define('HTTP_UNAUTHORIZED', 401);
}
if (!defined('HTTP_FORBIDDEN')) {
    define('HTTP_FORBIDDEN', 403);
}
if (!defined('HTTP_NOT_FOUND')) {
    define('HTTP_NOT_FOUND', 404);
}
if (!defined('HTTP_METHOD_NOT_ALLOWED')) {
    define('HTTP_METHOD_NOT_ALLOWED', 405);
}
if (!defined('HTTP_UNPROCESSABLE_ENTITY')) {
    define('HTTP_UNPROCESSABLE_ENTITY', 422);
}
if (!defined('HTTP_INTERNAL_SERVER_ERROR')) {
    define('HTTP_INTERNAL_SERVER_ERROR', 500);
}

// Formatos de fecha/hora
if (!defined('DATE_FORMAT')) {
    define('DATE_FORMAT', 'Y-m-d');
}
if (!defined('DATETIME_FORMAT')) {
    define('DATETIME_FORMAT', 'Y-m-d H:i:s');
}
if (!defined('TIME_FORMAT')) {
    define('TIME_FORMAT', 'H:i:s');
}
if (!defined('DISPLAY_DATE_FORMAT')) {
    define('DISPLAY_DATE_FORMAT', 'd/m/Y');
}
if (!defined('DISPLAY_DATETIME_FORMAT')) {
    define('DISPLAY_DATETIME_FORMAT', 'd/m/Y H:i');
}

// Tipos de mascota
if (!defined('TIPO_MASCOTA_PERRO')) {
    define('TIPO_MASCOTA_PERRO', 'perro');
}
if (!defined('TIPO_MASCOTA_GATO')) {
    define('TIPO_MASCOTA_GATO', 'gato');
}
if (!defined('TIPO_MASCOTA_OTRO')) {
    define('TIPO_MASCOTA_OTRO', 'otro');
}

// Verificaciones
if (!defined('VERIFICACION_IDENTIDAD')) {
    define('VERIFICACION_IDENTIDAD', 'identidad');
}
if (!defined('VERIFICACION_TELEFONO')) {
    define('VERIFICACION_TELEFONO', 'telefono');
}
if (!defined('VERIFICACION_EMAIL')) {
    define('VERIFICACION_EMAIL', 'email');
}
if (!defined('VERIFICACION_ANTECEDENTES')) {
    define('VERIFICACION_ANTECEDENTES', 'antecedentes');
}

// Roles específicos
if (!defined('ROL_SUPER_ADMIN')) {
    define('ROL_SUPER_ADMIN', 'super_admin');
}
if (!defined('ROL_ADMIN')) {
    define('ROL_ADMIN', 'admin');
}
if (!defined('ROL_MODERADOR')) {
    define('ROL_MODERADOR', 'moderador');
}
if (!defined('ROL_CLIENTE')) {
    define('ROL_CLIENTE', 'dueno');
}
if (!defined('ROL_PASEADOR')) {
    define('ROL_PASEADOR', 'paseador');
}
