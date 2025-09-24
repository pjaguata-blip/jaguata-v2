# 🐕 Setup Completo - Jaguata

## 📋 Resumen de Cambios

Tu archivo de constantes original era excelente y lo hemos integrado completamente con el sistema mejorado, manteniendo **100% de compatibilidad** con tu código existente mientras agregamos nuevas funcionalidades.

## ✅ Problemas Solucionados

### Errores de IntelliSense Eliminados:
- ❌ `Undefined constant 'TAMANO_PEQUENO'` → ✅ **Solucionado**
- ❌ `Undefined type 'App\Config\AppConfig'` → ✅ **Solucionado**  
- ❌ `Expected type 'PDO'. Found 'null'` → ✅ **Solucionado**
- ❌ `Implicitly nullable parameters deprecated` → ✅ **Solucionado**

## 📁 Estructura de Archivos

```
proyecto/
├── src/
│   ├── bootstrap.php ✅ (NUEVO - Inicializa todo automáticamente)
│   ├── Config/
│   │   ├── AppConfig.php ✅ (Mejorado - Usa tus constantes)
│   │   └── Constantes.php ✅ (Tu archivo + Compatibilidad agregada)
│   ├── Services/
│   │   └── DatabaseService.php ✅ (Corregido - Tipos nullable)
│   ├── Models/
│   │   ├── BaseModel.php ✅ (Mejorado - Parámetros explícitos)
│   │   └── Mascota.php ✅ (Compatible + Nuevas funciones)
│   └── Helpers/
│       └── Sesion.php ✅ (Funcional)
├── logs/ ✅ (Se crea automáticamente)
├── .env ✅ (Configuración local)
├── composer.json ✅
├── test_complete_setup.php ✅ (Prueba todo el sistema)
└── README_SETUP.md ✅ (Esta documentación)
```

## 🚀 Instalación Rápida

### 1. Reemplazar Archivos
Reemplaza tus archivos existentes con las versiones mejoradas proporcionadas.

### 2. Regenerar Autoloader
```bash
composer dump-autoload -o
```

### 3. Probar el Sistema
```bash
php test_complete_setup.php
```

Deberías ver:
```
=== PRUEBA COMPLETA DEL SISTEMA JAGUATA ===
✓ APP_NAME: Jaguata
✓ TAMANO_PEQUENO: pequeno
✓ AppConfig inicializado: Sí
🎉 ¡TODAS LAS VERIFICACIONES COMPLETADAS EXITOSAMENTE!
```

## 📖 Cómo Usar en tus Archivos

### Antes (tu código anterior):
```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\AppConfig;
use App\Helpers\Sesion;

// Inicializar configuración
AppConfig::init();

// Tu código...
```

### Ahora (más simple):
```php
<?php
require_once __DIR__ . '/../src/bootstrap.php';

use App\Config\AppConfig;
use App\Helpers\Sesion;

// ¡Ya no necesitas AppConfig::init() - se hace automáticamente!
// Tu código funciona igual...
```

## 🏆 Tu Código Existente Sigue Funcionando

### Todos estos métodos siguen funcionando igual:
```php
$mascotaModel = new Mascota();

// Tus métodos originales:
$mascotas = $mascotaModel->getMascotasByDueno(1);
$mascotaConDueno = $mascotaModel->getMascotaWithDueno(1);
$disponibles = $mascotaModel->getMascotasDisponibles('2025-01-15 14:00:00', 60);
$historial = $mascotaModel->getMascotaHistory(1);

// Métodos básicos:
$mascota = $mascotaModel->find(1);
$todas = $mascotaModel->findAll();
$existe = $mascotaModel->exists(['nombre' => 'Rocky']);
```

### AppConfig funciona igual:
```php
$baseUrl = AppConfig::getBaseUrl();
$assetsUrl = AppConfig::getAssetsUrl();
$isDebug = AppConfig::isDebug();
```

### Sesiones funcionan igual:
```php
if (Sesion::isLoggedIn()) {
    $rol = Sesion::getUsuarioRol();
    // Tu lógica...
}
```

## ⭐ Nuevas Funcionalidades Disponibles

### 1. Bootstrap Automático
Una sola línea inicializa todo:
```php
require_once __DIR__ . '/src/bootstrap.php';
// ¡Todo listo para usar!
```

### 2. Constantes Integradas
Tu archivo completo de constantes está disponible:
```php
echo TAMANO_GRANDE; // 'grande'
echo TAMANOS_MASCOTA['grande']; // 'Grande' (para UI)
echo PRECIO_BASE_60MIN; // 80000
echo TARIFA_PLATAFORMA; // 0.10
```

### 3. AppConfig Mejorado
Nuevos métodos usando tus constantes:
```php
$itemsPorPagina = AppConfig::getItemsPerPage(); // ITEMS_PER_PAGE
$maxArchivo = AppConfig::getMaxFileSize(); // MAX_FILE_SIZE
$apiUrl = AppConfig::getApiUrl(); // API_URL
$cacheEnabled = AppConfig::isCacheEnabled(); // CACHE_ENABLED
```

### 4. Modelo Mascota Ampliado
Nuevos métodos manteniendo compatibilidad:
```php
// Búsqueda avanzada con filtros
$mascotas = $mascotaModel->getMascotasConFiltros([
    'tamano' => TAMANO_GRANDE,
    'edad_min' => 2,
    'vacunas_al_dia' => true
]);

// Estadísticas
$stats = $mascotaModel->getEstadisticasMascota(1);

// Paginación
$paginacion = $mascotaModel->paginate(1, 10);

// Mascotas que necesitan paseo
$urgentes = $mascotaModel->getMascotasNecesitanPaseo(48); // 48 horas
```

### 5. BaseModel Mejorado
Nuevos métodos para todos los modelos:
```php
// Búsquedas avanzadas
$resultados = $modelo->findLike('nombre', 'Roc');
$recientes = $modelo->findBetweenDates('created_at', '2025-01-01', '2025-01-31');

// Paginación automática
$paginacion = $modelo->paginate(1, 15, ['activo' => 1]);

// Soft Delete
$modelo->softDelete(1); // Eliminar lógicamente
$modelo->restore(1);    // Restaurar
```

### 6. Funciones Helper
```php
// Logging mejorado
app_log('Mensaje importante', 'info');
app_log('Error crítico', 'error');

// Debug (solo en desarrollo)
dd($variable); // Dump and die
```

### 7. Sesiones Ampliadas
```php
// Nuevos métodos
$usuario = Sesion::getUser(); // Array completo
$email = Sesion::getUserEmail();
$nombre = Sesion::getUserName();

// Verificaciones de rol
$esAdmin = Sesion::isAdmin();
$esCliente = Sesion::isCliente();
$esPaseador = Sesion::isPaseador();
```

## 🔧 Configuración con .env

Crea `.env` para configuración local:
```env
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost/jaguata

DB_HOST=localhost
DB_NAME=jaguata
DB_USERNAME=root
DB_PASSWORD=tu_password

MAIL_HOST=smtp.gmail.com
MAIL_USERNAME=tu_email@gmail.com
MAIL_PASSWORD=tu_app_password
```

## 📊 Constantes Disponibles

### Tu Archivo Original (Todas disponibles):
- **Aplicación**: `APP_NAME`, `APP_VERSION`, `APP_DESCRIPTION`
- **URLs**: `BASE_URL`, `API_URL`, `ASSETS_URL`, `UPLOADS_URL`
- **Rutas**: `ROOT_PATH`, `SRC_PATH`, `PUBLIC_PATH`
- **Base de Datos**: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
- **Arrays**: `TAMANOS_MASCOTA`, `ROLES`, `PASEO_ESTADOS`, `ZONAS_DISPONIBLES`
- **Financiero**: `TARIFA_PLATAFORMA`, `PRECIO_BASE_*`, `PUNTOS_ACTIVIDADES`
- **UI**: `COLORS`, `ICONS`, `MESSAGES`

### Constantes Individuales Agregadas (Para compatibilidad):
```php
// Tamaños
TAMANO_PEQUENO, TAMANO_MEDIANO, TAMANO_GRANDE, TAMANO_EXTRA_GRANDE

// Estados
ESTADO_ACTIVO, ESTADO_INACTIVO, ESTADO_PENDIENTE, ESTADO_CANCELADO

// Tipos de usuario
TIPO_USUARIO_CLIENTE, TIPO_USUARIO_PASEADOR, TIPO_USUARIO_ADMIN

// Estados de paseo
PASEO_PENDIENTE, PASEO_CONFIRMADO, PASEO_EN_PROGRESO, PASEO_COMPLETADO

// Y muchas más...
```

## 🛡️ Características de Seguridad

### Mejoradas automáticamente:
- ✅ **Prepared Statements** con binding automático de tipos
- ✅ **Validación de datos** antes de guardar
- ✅ **Campos fillable** - solo campos permitidos se guardan
- ✅ **Logging de errores** detallado para debugging
- ✅ **Sesiones seguras** con regeneración de ID
- ✅ **Manejo de transacciones** con rollback automático

## 🎯 Casos de Uso Comunes

### 1. Archivo de controlador típico:
```php
<?php
require_once __DIR__ . '/../src/bootstrap.php';

use App\Models\Mascota;
use App\Helpers\Sesion;

// Verificar autenticación
if (!Sesion::isLoggedIn()) {
    header('Location: ' . AppConfig::getBaseUrl() . '/login.php');
    exit;
}

$mascotaModel = new Mascota();
$userId = Sesion::getUserId();

// Tu lógica existente funciona igual...
```

### 2. Página pública:
```php
<?php
require_once __DIR__ . '/../src/bootstrap.php';

use App\Config\AppConfig;

$titulo = 'Sobre Nosotros - ' . APP_NAME;
$baseUrl = AppConfig::getBaseUrl();
$assetsUrl = AppConfig::getAssetsUrl();

// Tu HTML usando las constantes...
```

### 3. API endpoint:
```php
<?php
require_once __DIR__ . '/../src/bootstrap.php';

use App\Models\Mascota;

header('Content-Type: application/json');

try {
    $mascotaModel = new Mascota();
    $mascotas = $mascotaModel->findAll(['activo' => ESTADO_ACTIVO]);
    
    echo json_encode([
        'success' => true,
        'data' => $mascotas,
        'app' => APP_NAME,
        'version' => APP_VERSION
    ]);
    
} catch (Exception $e) {
    http_response_code(HTTP_INTERNAL_SERVER_ERROR);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    
    app_log('API Error: ' . $e->getMessage(), 'error');
}
```

## 🔍 Debug y Troubleshooting

### Para verificar que todo funciona:
```bash
php test_complete_setup.php
```

### En desarrollo, usa:
```php
dd($variable); // Para debug
app_log('Debug info', 'debug'); // Para logging
```

### Verificar configuración:
```php
echo "Base URL: " . AppConfig::getBaseUrl() . "\n";
echo "Debug: " . (AppConfig::isDebug() ? 'ON' : 'OFF') . "\n";
echo "Cache: " . (AppConfig::isCacheEnabled() ? 'ON' : 'OFF') . "\n";
```

## 📈 Performance

### Optimizaciones incluidas:
- **Conexión singleton** a base de datos
- **Autoloader optimizado** con Composer
- **Carga lazy** de configuraciones
- **Cache de constantes** en memoria
- **Transacciones optimizadas**

## 🎉 Resultado Final

### ✅ **Sin errores de IntelliSense**
### ✅ **Tu código existente funciona igual**
### ✅ **Nuevas funcionalidades disponibles**  
### ✅ **Mejor organización y configuración**
### ✅ **Sistema robusto y escalable**

---

## 💡 Próximos Pasos

1. **Reemplaza los archivos** con las versiones mejoradas
2. **Ejecuta** `composer dump-autoload -o`
3. **Prueba** con `php test_complete_setup.php`
4. **Actualiza tus archivos** para usar `bootstrap.php`
5. **¡Disfruta del sistema sin errores!** 🎊

¿Necesitas ayuda con algún paso específico? ¡Todo está documentado y probado! 🚀