<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/src/Config/AppConfig.php';
use Jaguata\Config\AppConfig;

AppConfig::init();
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = AppConfig::db();
    $dia = trim((string)($_GET['dia'] ?? ''));

    if ($dia === '') {
        echo json_encode(['ok' => false, 'error' => 'Falta dia']);
        exit;
    }

    $sql = "
      SELECT paseador_id, dia_semana, hora_inicio, hora_fin
      FROM disponibilidades_paseador
      WHERE activo = 1 AND dia_semana = :dia
      ORDER BY hora_inicio ASC
    ";
    $st = $pdo->prepare($sql);
    $st->execute([':dia' => $dia]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    echo json_encode(['ok' => true, 'data' => $rows]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
