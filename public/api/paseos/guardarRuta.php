<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 3) . '/src/Services/DatabaseService.php';
require_once dirname(__DIR__, 3) . '/src/Controllers/PaseoController.php';
require_once dirname(__DIR__, 3) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 3) . '/src/Controllers/AuthController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Services\DatabaseService;
use Jaguata\Controllers\PaseoController;
use Jaguata\Helpers\Session;
use Jaguata\Controllers\AuthController;

AppConfig::init();

header('Content-Type: application/json; charset=utf-8');

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

// 🔐 Solo paseador logueado
$auth = new AuthController();
$auth->checkRole('paseador'); // si falla, redirige; aquí asumimos que sigue

$paseadorId = (int)(Session::getUsuarioId() ?? 0);
if ($paseadorId <= 0) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit;
}

// Datos enviados
$paseoId = isset($_POST['paseo_id']) ? (int)$_POST['paseo_id'] : 0;
$lat     = isset($_POST['lat']) ? (float)$_POST['lat'] : 0;
$lng     = isset($_POST['lng']) ? (float)$_POST['lng'] : 0;

if ($paseoId <= 0 || !$lat || !$lng) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Datos inválidos']);
    exit;
}

// Validar que el paseo pertenece al paseador y que está en curso
$paseoCtrl = new PaseoController();
$paseo     = $paseoCtrl->getById($paseoId);

if (!$paseo) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Paseo no encontrado']);
    exit;
}

if ((int)($paseo['paseador_id'] ?? 0) !== $paseadorId) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'No tienes permiso sobre este paseo']);
    exit;
}

$estado = strtolower((string)($paseo['estado'] ?? ''));
if (!in_array($estado, ['en_curso', 'confirmado'], true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Solo se puede registrar ruta si el paseo está en curso/confirmado']);
    exit;
}

// Guardar punto en paseo_rutas
try {
    $db = DatabaseService::getInstance()->getConnection();

    $sql = "
        INSERT INTO paseo_rutas (paseo_id, latitud, longitud, creado_en)
        VALUES (:paseo_id, :lat, :lng, NOW())
    ";

    $stmt = $db->prepare($sql);
    $ok   = $stmt->execute([
        ':paseo_id' => $paseoId,
        ':lat'      => $lat,
        ':lng'      => $lng,
    ]);

    if ($ok) {
        echo json_encode(['ok' => true, 'mensaje' => 'Punto registrado']);
    } else {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'No se pudo registrar el punto']);
    }
} catch (\PDOException $e) {
    error_log('guardarRuta.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error interno del servidor']);
}
