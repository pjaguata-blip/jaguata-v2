<?php
declare(strict_types=1);

ob_start();

set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) return false;
    throw new ErrorException($message, 0, $severity, $file, $line);
});

require_once dirname(__DIR__, 3) . '/vendor/autoload.php';
require_once __DIR__ . '/../../../src/Config/AppConfig.php';

use Jaguata\Config\AppConfig;

try {
    AppConfig::init();

    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

    $pdo = AppConfig::db();
    $dia = trim((string)($_GET['dia'] ?? ''));

    if ($dia === '') {
        ob_end_clean();
        echo json_encode(['ok' => false, 'error' => 'Falta dia'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $sql = "
      SELECT paseador_id, dia_semana, hora_inicio, hora_fin
      FROM disponibilidades_paseador
      WHERE activo = 1 AND dia_semana = :dia
      ORDER BY hora_inicio ASC
    ";
    $st = $pdo->prepare($sql);
    $st->execute(['dia' => $dia]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    ob_end_clean();
    echo json_encode(['ok' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} finally {
    restore_error_handler();
}
