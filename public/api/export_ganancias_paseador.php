<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/PaseoController.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\PaseoController;
use Jaguata\Helpers\Session;

AppConfig::init();

/* 🔒 Solo paseador */
if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'paseador') {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$paseadorId = (int)(Session::getUsuarioId() ?? 0);
if ($paseadorId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Sesión inválida']);
    exit;
}

/* Helpers */
function safeField(array $row, string $key): string
{
    if (!isset($row[$key])) return '';
    return str_replace(["\t", "\r", "\n"], ' ', (string)$row[$key]);
}

function moneyPY(float $v): string
{
    return number_format($v, 0, ',', '.');
}

/* Filtros opcionales (GET) */
$fechaInicio = $_GET['fecha_inicio'] ?? '';
$fechaFin    = $_GET['fecha_fin'] ?? '';
$formato     = strtolower(trim($_GET['formato'] ?? 'csv')); // csv | json

$controller = new PaseoController();
$paseos     = $controller->indexForPaseador($paseadorId) ?? [];

/* Filtrar solo paseos completados/finalizados dentro del rango */
$paseosCompletados = array_values(array_filter($paseos, function ($p) use ($fechaInicio, $fechaFin) {
    $estado = strtolower($p['estado'] ?? '');
    $fecha  = isset($p['inicio']) ? date('Y-m-d', strtotime($p['inicio'])) : null;

    if (!$fecha || !in_array($estado, ['completo', 'finalizado'], true)) {
        return false;
    }
    if ($fechaInicio && $fecha < $fechaInicio) {
        return false;
    }
    if ($fechaFin && $fecha > $fechaFin) {
        return false;
    }
    return true;
}));

/* Calcular total de ganancias */
$total = 0.0;
foreach ($paseosCompletados as $p) {
    $total += (float)($p['precio_total'] ?? 0);
}

/* ============================
   FORMATO JSON
   ============================ */
if ($formato === 'json') {
    header('Content-Type: application/json; charset=UTF-8');

    $data = [];
    $i    = 0;
    foreach ($paseosCompletados as $p) {
        $i++;
        $mascota  = $p['mascota_nombre'] ?? $p['nombre_mascota'] ?? '-';
        $dueno    = $p['dueno_nombre']   ?? $p['dueno_email']   ?? '-';
        $fecha    = $p['inicio'] ? date('Y-m-d H:i', strtotime($p['inicio'])) : null;
        $duracion = $p['duracion_min'] ?? $p['duracion'] ?? null;
        $estado   = ucfirst(str_replace('_', ' ', strtolower($p['estado'] ?? '-')));
        $monto    = (float)($p['precio_total'] ?? 0);

        $data[] = [
            'nro'            => $i,
            'mascota'        => $mascota,
            'dueno'          => $dueno,
            'fecha_paseo'    => $fecha,
            'duracion_min'   => $duracion,
            'estado'         => $estado,
            'monto'          => $monto,
        ];
    }

    echo json_encode([
        'paseador_id' => $paseadorId,
        'filtros' => [
            'fecha_inicio' => $fechaInicio,
            'fecha_fin'    => $fechaFin,
            'formato'      => 'json',
        ],
        'total_ganancias' => $total,
        'total_ganancias_fmt' => '₲ ' . moneyPY($total),
        'cantidad_paseos' => count($paseosCompletados),
        'data'            => $data,
    ]);
    exit;
}

/* ============================
   FORMATO CSV (por defecto)
   ============================ */
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="ganancias_paseador_' . date('Ymd_His') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');
// BOM UTF-8
fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

/* Cabecera "bonita" del reporte en CSV */
fputcsv($out, ['REPORTE DE GANANCIAS – JAGUATA']);
$periodoTexto = 'Sin filtro de fechas';
if ($fechaInicio || $fechaFin) {
    $periodoTexto = ($fechaInicio ?: 'inicio') . ' - ' . ($fechaFin ?: 'hoy');
}
fputcsv($out, ['Generado el', date('d/m/Y H:i')]);
fputcsv($out, ['Período', $periodoTexto]);
fputcsv($out, ['Total de ganancias', '₲ ' . moneyPY($total)]);
fputcsv($out, []); // línea en blanco

/* Encabezado de tabla */
fputcsv($out, [
    '#',
    'Mascota',
    'Dueño',
    'Fecha del paseo',
    'Duración (min)',
    'Estado paseo',
    'Monto (₲)'
]);

/* Detalle */
$i = 0;
foreach ($paseosCompletados as $p) {
    $i++;
    $mascota  = $p['mascota_nombre'] ?? $p['nombre_mascota'] ?? '-';
    $dueno    = $p['dueno_nombre']   ?? $p['dueno_email']   ?? '-';
    $fecha    = $p['inicio'] ? date('d/m/Y H:i', strtotime($p['inicio'])) : '-';
    $duracion = $p['duracion_min'] ?? $p['duracion'] ?? '';
    $estado   = ucfirst(str_replace('_', ' ', strtolower($p['estado'] ?? '-')));
    $monto    = (float)($p['precio_total'] ?? 0);

    fputcsv($out, [
        $i,
        safeField(['m' => $mascota], 'm'),
        safeField(['d' => $dueno], 'd'),
        $fecha,
        $duracion,
        $estado,
        moneyPY($monto),
    ]);
}

fclose($out);
exit;
