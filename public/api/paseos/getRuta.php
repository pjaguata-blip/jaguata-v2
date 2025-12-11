<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 3) . '/src/Services/DatabaseService.php';
require_once dirname(__DIR__, 3) . '/src/Controllers/PaseoController.php';
require_once dirname(__DIR__, 3) . '/src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Services\DatabaseService;
use Jaguata\Controllers\PaseoController;
use Jaguata\Helpers\Session;

AppConfig::init();

header('Content-Type: application/json; charset=utf-8');

// Debe estar logueado
if (!Session::isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'ok'    => false,
        'error' => 'No autorizado.'
    ]);
    exit;
}

$rol       = Session::getUsuarioRol();
$usuarioId = (int)(Session::getUsuarioId() ?? 0);

// ID del paseo
$paseoId = isset($_GET['paseo_id']) ? (int)$_GET['paseo_id'] : 0;
if ($paseoId <= 0) {
    http_response_code(400);
    echo json_encode([
        'ok'    => false,
        'error' => 'ID de paseo inválido.'
    ]);
    exit;
}

try {
    $db        = DatabaseService::getInstance()->getConnection();
    $paseoCtrl = new PaseoController();

    // Obtenemos el paseo simple (con dueno_id, paseador_id, pickup_lat/lng, etc.)
    $paseo = $paseoCtrl->getById($paseoId);
    if (!$paseo) {
        http_response_code(404);
        echo json_encode([
            'ok'    => false,
            'error' => 'Paseo no encontrado.'
        ]);
        exit;
    }

    // Autorización según rol
    if ($rol === 'paseador') {
        if ((int)($paseo['paseador_id'] ?? 0) !== $usuarioId) {
            http_response_code(403);
            echo json_encode([
                'ok'    => false,
                'error' => 'No tienes permiso para ver esta ruta.'
            ]);
            exit;
        }
    } elseif ($rol === 'dueno') {
        if ((int)($paseo['dueno_id'] ?? 0) !== $usuarioId) {
            http_response_code(403);
            echo json_encode([
                'ok'    => false,
                'error' => 'No tienes permiso para ver esta ruta.'
            ]);
            exit;
        }
    } elseif ($rol !== 'admin') {
        // Otros roles no autorizados
        http_response_code(403);
        echo json_encode([
            'ok'    => false,
            'error' => 'Rol no autorizado.'
        ]);
        exit;
    }

    // Punto de recogida
    $pickup = null;
    if (!empty($paseo['pickup_lat']) && !empty($paseo['pickup_lng'])) {
        $pickup = [
            'lat' => (float)$paseo['pickup_lat'],
            'lng' => (float)$paseo['pickup_lng'],
        ];
    }

    // Ruta desde la tabla paseo_rutas
    $sql = "
        SELECT latitud, longitud
        FROM paseo_rutas
        WHERE paseo_id = :id
        ORDER BY creado_en ASC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $paseoId]);

    $puntos = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

    $ruta = array_map(static function (array $p): array {
        return [
            'lat' => (float)$p['latitud'],
            'lng' => (float)$p['longitud'],
        ];
    }, $puntos);

    echo json_encode([
        'ok'     => true,
        'pickup' => $pickup,
        'ruta'   => $ruta,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (\Throwable $e) {
    error_log('getRuta error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok'    => false,
        'error' => 'Error interno del servidor.'
    ]);
}
