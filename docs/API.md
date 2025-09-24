# Documentación de API - Jaguata

Esta documentación describe los endpoints de la API REST de Jaguata.

## Base URL

```
http://localhost/jaguata/api
```

## Autenticación

La API utiliza autenticación basada en sesiones. Los usuarios deben estar logueados para acceder a la mayoría de endpoints.

### Headers Requeridos

```
Content-Type: application/json
X-CSRF-TOKEN: {token}
```

## Códigos de Estado HTTP

- `200` - OK
- `201` - Creado
- `400` - Bad Request
- `401` - No autorizado
- `403` - Prohibido
- `404` - No encontrado
- `422` - Error de validación
- `500` - Error interno del servidor

## Formato de Respuesta

### Respuesta Exitosa

```json
{
    "success": true,
    "data": {
        // Datos de respuesta
    },
    "message": "Operación exitosa"
}
```

### Respuesta de Error

```json
{
    "success": false,
    "error": "Mensaje de error",
    "code": "ERROR_CODE"
}
```

## Endpoints

### Autenticación

#### POST /auth/login
Iniciar sesión

**Body:**
```json
{
    "email": "usuario@email.com",
    "password": "contraseña"
}
```

**Respuesta:**
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "nombre": "Juan Pérez",
            "email": "usuario@email.com",
            "rol": "dueno"
        }
    }
}
```

#### POST /auth/logout
Cerrar sesión

**Respuesta:**
```json
{
    "success": true,
    "message": "Sesión cerrada exitosamente"
}
```

#### POST /auth/register
Registrar nuevo usuario

**Body:**
```json
{
    "nombre": "Juan Pérez",
    "email": "usuario@email.com",
    "password": "contraseña123",
    "telefono": "0981-123-456",
    "rol": "dueno"
}
```

### Usuarios

#### GET /users/profile
Obtener perfil del usuario actual

**Respuesta:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "nombre": "Juan Pérez",
        "email": "usuario@email.com",
        "telefono": "0981-123-456",
        "rol": "dueno",
        "perfil_foto": "foto.jpg",
        "created_at": "2024-01-01 00:00:00"
    }
}
```

#### PUT /users/profile
Actualizar perfil del usuario

**Body:**
```json
{
    "nombre": "Juan Carlos Pérez",
    "telefono": "0981-123-456"
}
```

### Mascotas

#### GET /mascotas
Obtener mascotas del usuario

**Respuesta:**
```json
{
    "success": true,
    "data": [
        {
            "mascota_id": 1,
            "nombre": "Max",
            "raza": "Golden Retriever",
            "tamano": "grande",
            "edad": 3,
            "observaciones": "Muy amigable"
        }
    ]
}
```

#### POST /mascotas
Crear nueva mascota

**Body:**
```json
{
    "nombre": "Luna",
    "raza": "Border Collie",
    "tamano": "mediano",
    "edad": 2,
    "observaciones": "Inteligente y activa"
}
```

#### PUT /mascotas/{id}
Actualizar mascota

#### DELETE /mascotas/{id}
Eliminar mascota

### Paseadores

#### GET /paseadores
Obtener paseadores disponibles

**Query Parameters:**
- `zona` - Filtrar por zona
- `disponible` - Solo disponibles (true/false)

**Respuesta:**
```json
{
    "success": true,
    "data": [
        {
            "paseador_id": 5,
            "nombre": "Roberto Silva",
            "experiencia": "3 años de experiencia",
            "zona": "Asunción Centro",
            "precio_hora": 8000,
            "calificacion": 4.8,
            "total_paseos": 45
        }
    ]
}
```

#### GET /paseadores/{id}
Obtener perfil de paseador

### Paseos

#### GET /paseos
Obtener paseos del usuario

**Query Parameters:**
- `estado` - Filtrar por estado
- `fecha` - Filtrar por fecha (YYYY-MM-DD)

#### POST /paseos
Solicitar nuevo paseo

**Body:**
```json
{
    "mascota_id": 1,
    "paseador_id": 5,
    "inicio": "2024-01-25 08:00:00",
    "duracion": 60
}
```

#### PUT /paseos/{id}/confirmar
Confirmar paseo (solo paseadores)

#### PUT /paseos/{id}/iniciar
Iniciar paseo (solo paseadores)

#### PUT /paseos/{id}/completar
Completar paseo (solo paseadores)

#### PUT /paseos/{id}/cancelar
Cancelar paseo

### Pagos

#### GET /pagos
Obtener pagos del usuario

#### POST /pagos
Procesar pago

**Body:**
```json
{
    "paseo_id": 1,
    "metodo_id": 1
}
```

#### GET /pagos/{id}
Obtener detalles de pago

### Notificaciones

#### GET /notificaciones
Obtener notificaciones del usuario

**Query Parameters:**
- `leido` - Filtrar por estado (true/false)
- `limite` - Limitar cantidad

#### PUT /notificaciones/{id}/leer
Marcar notificación como leída

#### PUT /notificaciones/leer-todas
Marcar todas las notificaciones como leídas

### Calificaciones

#### POST /calificaciones
Crear calificación

**Body:**
```json
{
    "paseo_id": 1,
    "calificacion": 5,
    "comentario": "Excelente servicio",
    "tipo": "paseador"
}
```

#### GET /calificaciones/paseador/{id}
Obtener calificaciones de paseador

### Reportes

#### GET /reportes/ganancias
Obtener reporte de ganancias (solo paseadores)

**Query Parameters:**
- `fecha_inicio` - Fecha de inicio (YYYY-MM-DD)
- `fecha_fin` - Fecha de fin (YYYY-MM-DD)

#### GET /reportes/estadisticas
Obtener estadísticas generales

## Ejemplos de Uso

### Solicitar un Paseo

```javascript
// 1. Buscar paseadores disponibles
const paseadores = await fetch('/api/paseadores?zona=Asunción Centro&disponible=true')
    .then(response => response.json());

// 2. Solicitar paseo
const paseo = await fetch('/api/paseos', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
    },
    body: JSON.stringify({
        mascota_id: 1,
        paseador_id: 5,
        inicio: '2024-01-25 08:00:00',
        duracion: 60
    })
}).then(response => response.json());
```

### Procesar un Pago

```javascript
const pago = await fetch('/api/pagos', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
    },
    body: JSON.stringify({
        paseo_id: 1,
        metodo_id: 1
    })
}).then(response => response.json());
```

### Obtener Notificaciones

```javascript
const notificaciones = await fetch('/api/notificaciones?leido=false&limite=10')
    .then(response => response.json());
```

## Rate Limiting

La API tiene un límite de 100 requests por hora por usuario. Los headers de respuesta incluyen:

```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1640995200
```

## Códigos de Error

| Código | Descripción |
|--------|-------------|
| `VALIDATION_ERROR` | Error de validación de datos |
| `UNAUTHORIZED` | Usuario no autenticado |
| `FORBIDDEN` | Usuario sin permisos |
| `NOT_FOUND` | Recurso no encontrado |
| `DUPLICATE_EMAIL` | Email ya registrado |
| `INVALID_CREDENTIALS` | Credenciales inválidas |
| `PAYMENT_FAILED` | Error en procesamiento de pago |
| `WALKER_UNAVAILABLE` | Paseador no disponible |
| `INVALID_WALK_TIME` | Hora de paseo inválida |

## Webhooks

### Eventos Disponibles

- `paseo.solicitado`
- `paseo.confirmado`
- `paseo.iniciado`
- `paseo.completado`
- `paseo.cancelado`
- `pago.procesado`
- `pago.fallido`
- `calificacion.creada`

### Configuración de Webhook

```json
{
    "url": "https://tu-servidor.com/webhook",
    "eventos": ["paseo.completado", "pago.procesado"],
    "secret": "tu_secret_key"
}
```

## SDKs

### JavaScript

```javascript
import { JaguataAPI } from '@jaguata/api-client';

const api = new JaguataAPI({
    baseURL: 'https://api.jaguata.com',
    apiKey: 'tu_api_key'
});

// Usar la API
const paseadores = await api.paseadores.list({ zona: 'Asunción Centro' });
```

### PHP

```php
use Jaguata\API\Client;

$client = new Client([
    'base_url' => 'https://api.jaguata.com',
    'api_key' => 'tu_api_key'
]);

$paseadores = $client->paseadores->list(['zona' => 'Asunción Centro']);
```

## Soporte

Para soporte técnico de la API:

- Email: api-support@jaguata.com
- Documentación: https://docs.jaguata.com
- GitHub: https://github.com/jaguata/api
