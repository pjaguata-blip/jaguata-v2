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

// Solo paseador logueado
if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'paseador') {
    http_response_code(401);
    echo json_encode([
        'ok'    => false,
        'error' => 'No autorizado.'
    ]);
    exit;
}

$paseadorId = (int)(Session::getUsuarioId() ?? 0);

// Validar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'ok'    => false,
        'error' => 'Método no permitido.'
    ]);
    exit;
}

// Leer datos
$paseoId = isset($_POST['paseo_id']) ? (int)$_POST['paseo_id'] : 0;
$lat     = isset($_POST['lat']) ? (float)$_POST['lat'] : 0.0;
$lng     = isset($_POST['lng']) ? (float)$_POST['lng'] : 0.0;

if ($paseoId <= 0 || !$lat || !$lng) {
    http_response_code(400);
    echo json_encode([
        'ok'    => false,
        'error' => 'Datos incompletos.'
    ]);
    exit;
}

try {
    $db = DatabaseService::getInstance()->getConnection();
    $paseoCtrl = new PaseoController();

    // Verificar que el paseo exista
    $paseo = $paseoCtrl->getById($paseoId);
    if (!$paseo) {
        http_response_code(404);
        echo json_encode([
            'ok'    => false,
            'error' => 'Paseo no encontrado.'
        ]);
        exit;
    }

    // Verificar que el paseo pertenezca al paseador logueado
    if ((int)($paseo['paseador_id'] ?? 0) !== $paseadorId) {
        http_response_code(403);
        echo json_encode([
            'ok'    => false,
            'error' => 'No tienes permiso sobre este paseo.'
        ]);
        exit;
    }

    // Opcional: solo permitir registrar cuando está en_curso
    $estadoActual = strtolower($paseo['estado'] ?? '');
    if ($estadoActual !== 'en_curso') {
        // No es error fatal, pero podemos avisar
        // http_response_code(409);
        // echo json_encode(['ok' => false, 'error' => 'El paseo no está en curso.']);
        // exit;
    }

    // Insertar punto en paseo_rutas
    $sql = "
        INSERT INTO paseo_rutas (paseo_id, latitud, longitud, creado_en)
        VALUES (:paseo_id, :latitud, :longitud, NOW())
    ";

    $stmt = $db->prepare($sql);
    $ok = $stmt->execute([
        ':paseo_id' => $paseoId,
        ':latitud'  => $lat,
        ':longitud' => $lng
    ]);

    if (!$ok) {
        http_response_code(500);
        echo json_encode([
            'ok'    => false,
            'error' => 'No se pudo registrar la posición.'
        ]);
        exit;
    }

    echo json_encode([
        'ok'      => true,
        'mensaje' => 'Posición registrada.'
    ]);
} catch (\Throwable $e) {
    error_log('registrarPosicion error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok'    => false,
        'error' => 'Error interno del servidor.'
    ]);
}
