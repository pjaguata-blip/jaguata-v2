<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../../src/Services/DatabaseService.php';
require_once __DIR__ . '/../../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Services\DatabaseService;
use Jaguata\Helpers\Session;

AppConfig::init();

// 🔒 Solo usuarios logueados
if (!Session::isLoggedIn()) {
    http_response_code(401);
    echo 'No autorizado';
    exit;
}

$pagoId = isset($_GET['pago_id']) ? (int)$_GET['pago_id'] : 0;
if ($pagoId <= 0) {
    http_response_code(400);
    echo 'ID de pago inválido';
    exit;
}

try {
    $db = DatabaseService::getInstance()->getConnection();

    // 🔹 Pago + paseo + dueño + paseador (para validar permisos)
    $sql = "
    SELECT 
        pg.id,
        pg.paseo_id,
        pg.comprobante,
        pg.usuario_id AS usuario_id_en_pago,
        p.paseador_id,
        m.dueno_id
    FROM pagos pg
    INNER JOIN paseos p   ON p.paseo_id = pg.paseo_id
    INNER JOIN mascotas m ON m.mascota_id = p.mascota_id
    WHERE pg.id = :id
    LIMIT 1
";


    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $pagoId]);
    $pago = $stmt->fetch(\PDO::FETCH_ASSOC);

    if (!$pago) {
        http_response_code(404);
        echo 'Pago no encontrado';
        exit;
    }

    // ✅ Validar permisos
    $usuarioId = (int)(Session::getUsuarioId() ?? 0);
    $rol       = (string)(Session::getUsuarioRol() ?? '');

    if ($rol === 'paseador' && (int)$pago['paseador_id'] !== $usuarioId) {
        http_response_code(403);
        echo 'No tienes permiso para ver este comprobante.';
        exit;
    }

    if ($rol === 'dueno' && (int)$pago['dueno_id'] !== $usuarioId) {
        http_response_code(403);
        echo 'No tienes permiso para ver este comprobante.';
        exit;
    }

    // 📁 Nombre / ruta guardada en BD
    $archivoDb = trim((string)($pago['comprobante'] ?? ''));
    if ($archivoDb === '') {
        http_response_code(404);
        echo 'Este pago no tiene comprobante adjunto.';
        exit;
    }

    // ✅ Normalizar a nombre de archivo
    // (si en BD viene con carpetas, nos quedamos con el basename)
    $nombreArchivo = basename(str_replace('\\', '/', $archivoDb));

    // ✅ Rutas físicas posibles (dependiendo de tu estructura real)
    $candidatos = [
        // 1) si tu subida está en: public/assets/uploads/comprobantes/
        __DIR__ . '/../../assets/uploads/comprobantes/' . $nombreArchivo,

        // 2) si tu subida está en: assets/uploads/comprobantes/ (fuera de public)
        dirname(__DIR__, 3) . '/assets/uploads/comprobantes/' . $nombreArchivo,

        // 3) por si guardaste en: public/uploads/comprobantes/
        dirname(__DIR__, 3) . '/public/uploads/comprobantes/' . $nombreArchivo,

        // 4) por si guardaste en: uploads/comprobantes/ (raíz)
        dirname(__DIR__, 3) . '/uploads/comprobantes/' . $nombreArchivo,
    ];

    $filePath = null;
    foreach ($candidatos as $path) {
        if (is_file($path)) {
            $filePath = $path;
            break;
        }
    }

    if (!$filePath) {
        http_response_code(404);
        echo 'Archivo de comprobante no encontrado.';
        exit;
    }

    // ✅ MIME
    $mime = function_exists('mime_content_type')
        ? (mime_content_type($filePath) ?: 'application/octet-stream')
        : 'application/octet-stream';

    // ✅ Headers para mostrar inline en el navegador
    header('Content-Type: ' . $mime);
    header('Content-Disposition: inline; filename="' . $nombreArchivo . '"');
    header('Content-Length: ' . filesize($filePath));
    header('X-Content-Type-Options: nosniff');

    readfile($filePath);
    exit;
} catch (\Throwable $e) {
    error_log('Error en comprobantePago.php: ' . $e->getMessage());
    http_response_code(500);
    echo 'Error interno al mostrar el comprobante.';
    exit;
}
