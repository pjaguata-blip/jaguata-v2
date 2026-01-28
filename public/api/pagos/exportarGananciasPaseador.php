<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\PaseoController;
use Jaguata\Helpers\Session;

AppConfig::init();

if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'paseador') {
    http_response_code(401);
    exit('No autorizado');
}

$paseadorId = (int)(Session::getUsuarioId() ?? 0);
if ($paseadorId <= 0) {
    http_response_code(400);
    exit('Sesión inválida');
}

// Filtros opcionales
$fechaInicio = $_GET['fecha_inicio'] ?? '';
$fechaFin    = $_GET['fecha_fin'] ?? '';

$controller = new PaseoController();
$paseos     = $controller->indexForPaseador($paseadorId) ?? [];

// Filtrar solo paseos completados/finalizados dentro del rango
$paseosCompletados = array_filter($paseos, function ($p) use ($fechaInicio, $fechaFin) {
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
});

// FORZAR DESCARGA COMO EXCEL
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=ganancias_paseador_" . date('Ymd_His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// BOM UTF-8
echo "\xEF\xBB\xBF";

function safeField(array $row, string $key): string
{
    if (!isset($row[$key])) return '';
    return str_replace(["\t", "\r", "\n"], ' ', (string)$row[$key]);
}

$total = 0;
foreach ($paseosCompletados as $p) {
    $total += (float)($p['precio_total'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: "Poppins", Arial, sans-serif;
            font-size: 12px;
        }

        .header-table {
            margin-bottom: 15px;
            width: 100%;
        }

        .header-title {
            background: #3c6255;
            color: white;
            font-size: 18px;
            font-weight: 700;
            text-align: center;
            padding: 8px 0;
        }

        .header-date {
            background: #20c99733;
            color: #1e5247;
            font-size: 12px;
            text-align: center;
            padding: 5px 0;
            font-weight: 600;
        }

        .header-total {
            background: #e9f7ef;
            color: #1e5247;
            font-size: 13px;
            text-align: center;
            padding: 5px 0;
            font-weight: 600;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #dee2e6;
            padding: 6px 8px;
        }

        th {
            background-color: #3c6255;
            color: #ffffff;
            font-weight: 600;
            text-align: center;
        }

        tr.fila-par td {
            background-color: #f4f6f9;
        }

        .col-fecha {
            text-align: center;
            white-space: nowrap;
        }

        .col-monto {
            text-align: right;
            white-space: nowrap;
        }
    </style>
</head>

<body>

    <table class="header-table">
        <tr>
            <td class="header-title">
                REPORTE DE GANANCIAS – JAGUATA
            </td>
        </tr>
        <tr>
            <td class="header-date">
                Generado automáticamente el <?= date("d/m/Y H:i") ?>
                <?php if ($fechaInicio || $fechaFin): ?>
                    <br>Período:
                    <?= $fechaInicio ?: 'inicio' ?> &nbsp;–&nbsp; <?= $fechaFin ?: 'hoy' ?>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td class="header-total">
                Total de ganancias: ₲ <?= number_format($total, 0, ',', '.') ?>
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Mascota</th>
                <th>Dueño</th>
                <th>Fecha del paseo</th>
                <th>Duración (min)</th>
                <th>Estado paseo</th>
                <th>Monto (₲)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($paseosCompletados)): ?>
                <tr>
                    <td colspan="7" style="text-align:center; color:#777;">Sin paseos completados en el período</td>
                </tr>
                <?php else: $i = 0;
                foreach ($paseosCompletados as $p):
                    $i++;
                    $rowClass = ($i % 2 === 0) ? 'fila-par' : '';
                    $mascota  = $p['mascota_nombre'] ?? $p['nombre_mascota'] ?? '-';
                    $dueno    = $p['dueno_nombre']   ?? $p['dueno_email']   ?? '-';
                    $fecha    = $p['inicio'] ? date('d/m/Y H:i', strtotime($p['inicio'])) : '-';
                    $duracion = $p['duracion_min'] ?? $p['duracion'] ?? '';
                    $estado   = ucfirst(str_replace('_', ' ', strtolower($p['estado'] ?? '-')));
                    $monto    = (float)($p['precio_total'] ?? 0);
                ?>
                    <tr class="<?= $rowClass ?>">
                        <td style="text-align:center;"><?= $i ?></td>
                        <td><?= safeField(['m' => $mascota], 'm') ?></td>
                        <td><?= safeField(['d' => $dueno], 'd') ?></td>
                        <td class="col-fecha"><?= $fecha ?></td>
                        <td style="text-align:center;"><?= $duracion ?></td>
                        <td style="text-align:center;"><?= $estado ?></td>
                        <td class="col-monto"><?= number_format($monto, 0, ',', '.') ?></td>
                    </tr>
            <?php endforeach;
            endif; ?>
        </tbody>
    </table>

</body>

</html>