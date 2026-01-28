<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../../src/Controllers/PagoController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\PagoController;

AppConfig::init();

$controller = new PagoController();
$pagos = $controller->obtenerDatosExportacion() ?? [];

/* FORZAR DESCARGA COMO EXCEL */
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=reporte_pagos_jaguata_" . date('Ymd_His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

/* BOM UTF-8 */
echo "\xEF\xBB\xBF";

function safeField(array $row, string $key): string
{
    if (!isset($row[$key]) || $row[$key] === null) return '';
    return str_replace(["\t", "\r", "\n"], ' ', (string)$row[$key]);
}

/**
 * ✅ Devuelve monto como NÚMERO PURO (sin puntos, sin Gs).
 * Ej:
 *  "45.000" -> "45000"
 *  "45000"  -> "45000"
 *  ""       -> "0"
 */
function montoNumero(string $valor): string
{
    $valor = trim($valor);
    if ($valor === '' || $valor === '0') return '0';
    return preg_replace('/[^0-9]/', '', $valor);
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
            font-size: 20px;
            font-weight: 700;
            text-align: center;
            padding: 10px 0;
        }

        .header-date {
            background: #20c99733;
            color: #1e5247;
            font-size: 13px;
            text-align: center;
            padding: 6px 0;
            font-weight: 600;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th, td {
            border: 1px solid #dee2e6;
            padding: 6px 8px;
            vertical-align: middle;
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

        .col-center { text-align: center; }
        .col-right  { text-align: right; }

        /* Estados pago (opcional, solo visual) */
        .pago-pendiente { background:#ffc10733; color:#856404; font-weight:700; }
        .pago-confirmado_por_dueno,
        .pago-confirmado_por_admin,
        .pago-procesado { background:#0d6efd33; color:#0d6efd; font-weight:700; }
        .pago-pagado { background:#19875433; color:#198754; font-weight:700; }
        .pago-rechazado,
        .pago-cancelado { background:#dc354533; color:#dc3545; font-weight:700; }

    </style>
</head>

<body>

<table class="header-table">
    <tr>
        <td class="header-title">REPORTE DE PAGOS – JAGUATA 💳</td>
    </tr>
    <tr>
        <td class="header-date">Generado automáticamente el <?= date("d/m/Y H:i") ?></td>
    </tr>
</table>

<table>
    <thead>
        <tr>
            <th>ID Pago</th>
            <th>ID Paseo</th>
            <th>Dueño</th>
            <th>Paseador</th>
            <th>Mascotas</th>
            <th>Cant.</th>
            <th>Monto</th>
            <th>Estado Pago</th>
            <th>Comprobante</th>
            <th>Inicio Paseo</th>
            <th>Creado</th>
            <th>Actualizado</th>
        </tr>
    </thead>

    <tbody>
    <?php if (empty($pagos)): ?>
        <tr>
            <td colspan="12" style="text-align:center; color:#777;">Sin pagos registrados</td>
        </tr>
    <?php else:
        $i = 0;
        foreach ($pagos as $p):
            $i++;
            $rowClass = ($i % 2 === 0) ? 'fila-par' : '';

            $estado = strtolower(safeField($p, 'estado_pago'));
            $estadoClass = $estado ? ('pago-' . $estado) : 'pago-pendiente';
            $estadoLabel = $estado ? ucfirst(str_replace('_', ' ', $estado)) : 'Pendiente';

            $montoRaw = safeField($p, 'monto');
            $montoOut = montoNumero($montoRaw); // ✅ número puro
    ?>
        <tr class="<?= $rowClass ?>">
            <td class="col-center"><?= safeField($p, 'pago_id') ?></td>
            <td class="col-center"><?= safeField($p, 'paseo_id') ?></td>
            <td><?= safeField($p, 'dueno_nombre') ?></td>
            <td><?= safeField($p, 'paseador_nombre') ?></td>
            <td><?= safeField($p, 'mascota_nombre') ?></td>
            <td class="col-center"><?= safeField($p, 'cantidad_mascotas') ?></td>

            <!-- ✅ MONTO SIN FORMATO -->
            <td class="col-right"><?= $montoOut ?></td>

            <td class="col-center <?= $estadoClass ?>"><?= $estadoLabel ?></td>
            <td><?= safeField($p, 'comprobante') ?: '—' ?></td>
            <td><?= safeField($p, 'inicio_paseo') ?: '—' ?></td>
            <td><?= safeField($p, 'created_at') ?: '—' ?></td>
            <td><?= safeField($p, 'updated_at') ?: '—' ?></td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>

</body>
</html>
