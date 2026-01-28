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

function diaSemanaEs(DateTime $dt): string
{
    $n = (int)$dt->format('N'); // 1..7
    return match ($n) {
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
        7 => 'Domingo',
        default => 'Lunes'
    };
}

try {
    AppConfig::init();

    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

    $lat      = isset($_GET['lat']) ? (float)$_GET['lat'] : 0.0;
    $lng      = isset($_GET['lng']) ? (float)$_GET['lng'] : 0.0;
    $inicio   = trim((string)($_GET['inicio'] ?? '')); // "YYYY-MM-DDTHH:MM"
    $duracion = (int)($_GET['duracion'] ?? 0);
    $radioKm  = isset($_GET['radio_km']) ? (float)$_GET['radio_km'] : 10.0;
    $limit    = isset($_GET['limit']) ? (int)$_GET['limit'] : 30;
    $limit    = max(1, min(100, $limit)); 

    if ($lat === 0.0 || $lng === 0.0 || $inicio === '' || $duracion <= 0) {
        ob_end_clean();
        echo json_encode(['ok' => false, 'error' => 'Faltan parámetros (lat, lng, inicio, duracion)'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $inicioSql = str_replace('T', ' ', $inicio) . ':00';
    $inicioObj = new DateTime($inicioSql);
    $finObj    = (clone $inicioObj)->modify("+{$duracion} minutes");
    $finSql    = $finObj->format('Y-m-d H:i:s');

    $dia        = diaSemanaEs($inicioObj);
    $horaInicio = $inicioObj->format('H:i:s');
    $horaFin    = $finObj->format('H:i:s');

    $pdo = AppConfig::db();

    $sql = "
        SELECT
            w.paseador_id,
            w.nombre,
            w.zona,
            w.precio_hora,
            w.calificacion,
            w.total_paseos,
            w.foto_url,
            w.latitud,
            w.longitud,
            (
                6371 * ACOS(
                    COS(RADIANS(:ulatA1)) * COS(RADIANS(w.latitud)) *
                    COS(RADIANS(w.longitud) - RADIANS(:ulngA1)) +
                    SIN(RADIANS(:ulatA2)) * SIN(RADIANS(w.latitud))
                )
            ) AS distancia_km
        FROM paseadores w
        INNER JOIN disponibilidades_paseador d
            ON d.paseador_id = w.paseador_id
           AND d.activo = 1
           AND d.dia_semana = :dia
           AND d.hora_inicio <= :horaInicio
           AND d.hora_fin >= :horaFin
        WHERE w.disponible = 1
          AND w.latitud IS NOT NULL AND w.longitud IS NOT NULL
          AND (
                6371 * ACOS(
                    COS(RADIANS(:ulatB1)) * COS(RADIANS(w.latitud)) *
                    COS(RADIANS(w.longitud) - RADIANS(:ulngB1)) +
                    SIN(RADIANS(:ulatB2)) * SIN(RADIANS(w.latitud))
                )
          ) <= :radioKm

          AND NOT EXISTS (
            SELECT 1
            FROM paseos p
            WHERE p.paseador_id = w.paseador_id
              AND p.estado IN ('solicitado','confirmado','en_curso')
              AND p.inicio < :nuevo_fin
              AND DATE_ADD(p.inicio, INTERVAL p.duracion MINUTE) > :nuevo_inicio
          )

        ORDER BY distancia_km ASC
        LIMIT {$limit}
    ";

    $params = [
        'ulatA1' => $lat,
        'ulatA2' => $lat,
        'ulngA1' => $lng,

        'ulatB1' => $lat,
        'ulatB2' => $lat,
        'ulngB1' => $lng,

        'radioKm' => $radioKm,
        'dia' => $dia,
        'horaInicio' => $horaInicio,
        'horaFin' => $horaFin,
        'nuevo_inicio' => $inicioSql,
        'nuevo_fin'    => $finSql,
    ];

    $st = $pdo->prepare($sql);
    $st->execute($params);

    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    ob_end_clean();
    echo json_encode([
        'ok' => true,
        'dia' => $dia,
        'inicio' => $inicioSql,
        'fin' => $finSql,
        'count' => count($rows),
        'data' => $rows
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} finally {
    restore_error_handler();
}
