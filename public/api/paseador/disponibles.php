<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/src/Config/AppConfig.php';

use Jaguata\Config\AppConfig;

AppConfig::init();
header('Content-Type: application/json; charset=utf-8');

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
    $lat      = isset($_GET['lat']) ? (float)$_GET['lat'] : 0.0;
    $lng      = isset($_GET['lng']) ? (float)$_GET['lng'] : 0.0;
    $inicio   = trim((string)($_GET['inicio'] ?? '')); // "YYYY-MM-DDTHH:MM"
    $duracion = (int)($_GET['duracion'] ?? 0);
    $radioKm  = isset($_GET['radio_km']) ? (float)$_GET['radio_km'] : 10.0;
    $limit    = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 30;

    if ($lat === 0.0 || $lng === 0.0 || $inicio === '' || $duracion <= 0) {
        echo json_encode(['ok' => false, 'error' => 'Faltan parámetros (lat, lng, inicio, duracion)']);
        exit;
    }

    // "2026-01-06T14:30" -> "2026-01-06 14:30:00"
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
                    COS(RADIANS(:lat)) * COS(RADIANS(w.latitud)) *
                    COS(RADIANS(w.longitud) - RADIANS(:lng)) +
                    SIN(RADIANS(:lat)) * SIN(RADIANS(w.latitud))
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
                    COS(RADIANS(:lat2)) * COS(RADIANS(w.latitud)) *
                    COS(RADIANS(w.longitud) - RADIANS(:lng2)) +
                    SIN(RADIANS(:lat2)) * SIN(RADIANS(w.latitud))
                )
          ) <= :radioKm

          /* ✅ NO permitir cruce con paseos existentes */
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

    $st = $pdo->prepare($sql);
    $st->execute([
        ':lat' => $lat, ':lng' => $lng,
        ':lat2' => $lat, ':lng2' => $lng,
        ':radioKm' => $radioKm,
        ':dia' => $dia,
        ':horaInicio' => $horaInicio,
        ':horaFin' => $horaFin,
        ':nuevo_inicio' => $inicioSql,
        ':nuevo_fin'    => $finSql,
    ]);

    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    echo json_encode([
        'ok' => true,
        'dia' => $dia,
        'inicio' => $inicioSql,
        'fin' => $finSql,
        'count' => count($rows),
        'data' => $rows
    ]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
