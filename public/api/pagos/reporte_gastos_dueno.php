<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../../src/Controllers/PagoController.php';
require_once __DIR__ . '/../../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PagoController;
use Jaguata\Helpers\Session;

AppConfig::init();

/* 🔒 Solo dueño */
$auth = new AuthController();
$auth->checkRole('dueno');

/* Helpers */
function parseDate(?string $v): ?string
{
    $v = $v ? trim($v) : '';
    if ($v === '') return null;
    return date('Y-m-d', strtotime($v));
}
function moneyPy(float $v): string
{
    return number_format($v, 0, ',', '.');
}
function safeField(array $row, string $key): string
{
    if (!isset($row[$key])) return '';
    return str_replace(["\t", "\r", "\n"], ' ', (string)$row[$key]);
}

/* Contexto usuario */
$duenoId = (int)(Session::getUsuarioId() ?? 0);
if ($duenoId <= 0) {
    http_response_code(401);
    exit('No autenticado');
}

/* Parámetros GET (mismos que tu pantalla) */
$from       = parseDate($_GET['from'] ?? null);
$to         = parseDate($_GET['to'] ?? null);
$mascotaId  = trim((string)($_GET['mascota_id'] ?? ''));
$paseadorId = trim((string)($_GET['paseador_id'] ?? ''));
$metodo     = strtoupper(trim((string)($_GET['metodo'] ?? ''))); // EFECTIVO/TRANSFERENCIA
$estado     = strtoupper(trim((string)($_GET['estado'] ?? ''))); // CONFIRMADO/PENDIENTE/RECHAZADO

$filters = [
    'dueno_id'    => $duenoId,
    'from'        => $from,
    'to'          => $to,
    'mascota_id'  => ($mascotaId !== '' ? $mascotaId : null),
    'paseador_id' => ($paseadorId !== '' ? $paseadorId : null),
    'metodo'      => ($metodo !== '' ? $metodo : null),
    'estado'      => ($estado !== '' ? $estado : null),
];

/* Datos */
$pagoController = new PagoController();
$rows = $pagoController->listarGastosDueno($filters) ?? [];

/* Total */
$total = 0.0;
foreach ($rows as $r) {
    $total += (float)($r['monto'] ?? 0);
}

/* ✅ Forzar descarga como "Excel" (HTML) */
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=reporte_gastos_dueno_jaguata_" . date('Ymd_His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

/* BOM UTF-8 */
echo "\xEF\xBB\xBF";

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

        .filters-box {
            margin: 10px 0 15px;
            padding: 8px 10px;
            border: 1px solid #dee2e6;
            background: #f8fafb;
            border-radius: 8px;
        }

        .filters-box b {
            color: #1e5247;
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
            font-weight: 700;
            text-align: center;
        }

        tr.fila-par td {
            background-color: #f4f6f9;
        }

        .col-id {
            text-align: center;
            width: 70px;
        }

        .col-fecha {
            text-align: center;
            white-space: nowrap;
        }

        .col-monto {
            text-align: right;
            white-space: nowrap;
            font-weight: 700;
            color: #198754;
        }

        .col-center {
            text-align: center;
        }

        .badge-ok {
            background: #19875433;
            color: #198754;
            font-weight: 700;
        }

        .badge-warn {
            background: #ffc10733;
            color: #856404;
            font-weight: 700;
        }

        .badge-bad {
            background: #dc354533;
            color: #dc3545;
            font-weight: 700;
        }

        .badge-other {
            background: #6c757d33;
            color: #495057;
            font-weight: 700;
        }

        tfoot td {
            background: #eef2f4;
            font-weight: 800;
        }

        .total-label {
            text-align: right;
        }

        .total-monto {
            text-align: right;
            color: #1e5247;
            font-size: 13px;
        }
    </style>
</head>

<body>

    <table class="header-table">
        <tr>
            <td class="header-title">REPORTE DE GASTOS TOTALES – JAGUATA 💰</td>
        </tr>
        <tr>
            <td class="header-date">Generado automáticamente el <?= date("d/m/Y H:i") ?></td>
        </tr>
    </table>

    <div class="filters-box">
        <div><b>Filtros:</b>
            Desde: <?= htmlspecialchars($from ?? '—', ENT_QUOTES, 'UTF-8') ?> |
            Hasta: <?= htmlspecialchars($to ?? '—', ENT_QUOTES, 'UTF-8') ?> |
            Mascota ID: <?= htmlspecialchars($mascotaId !== '' ? $mascotaId : 'Todas', ENT_QUOTES, 'UTF-8') ?> |
            Paseador ID: <?= htmlspecialchars($paseadorId !== '' ? $paseadorId : 'Todos', ENT_QUOTES, 'UTF-8') ?> |
            Método: <?= htmlspecialchars($metodo !== '' ? $metodo : 'Todos', ENT_QUOTES, 'UTF-8') ?> |
            Estado: <?= htmlspecialchars($estado !== '' ? $estado : 'Por defecto (Confirmados)', ENT_QUOTES, 'UTF-8') ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID Pago</th>
                <th>Fecha pago</th>
                <th>Monto (PYG)</th>
                <th>Método</th>
                <th>Estado</th>
                <th>Mascota</th>
                <th>Paseador</th>
                <th>ID Paseo</th>
                <th>Fecha paseo</th>
                <th>Referencia</th>
                <th>Observación</th>
            </tr>
        </thead>

        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="11" style="text-align:center; color:#777;">Sin registros de gastos</td>
                </tr>
            <?php else: ?>
                <?php
                $i = 0;
                foreach ($rows as $r):
                    $i++;
                    $rowClass = ($i % 2 === 0) ? 'fila-par' : '';

                    $id          = safeField($r, 'id');
                    $fecha_pago  = safeField($r, 'fecha_pago');
                    $montoRaw    = (float)($r['monto'] ?? 0);
                    $monto       = '₲' . moneyPy($montoRaw);
                    $met         = safeField($r, 'metodo');
                    $est         = strtoupper(safeField($r, 'estado'));
                    $masc        = safeField($r, 'mascota');
                    $pas         = safeField($r, 'paseador');
                    $paseo_id    = safeField($r, 'paseo_id');
                    $fecha_paseo = safeField($r, 'fecha_paseo');
                    $ref         = safeField($r, 'referencia');
                    $obs         = safeField($r, 'observacion');

                    $badgeClass = match ($est) {
                        'CONFIRMADO' => 'badge-ok',
                        'PENDIENTE'  => 'badge-warn',
                        'RECHAZADO'  => 'badge-bad',
                        default      => 'badge-other',
                    };
                ?>
                    <tr class="<?= $rowClass ?>">
                        <td class="col-id"><?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="col-fecha"><?= htmlspecialchars($fecha_pago, ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="col-monto"><?= htmlspecialchars($monto, ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="col-center"><?= htmlspecialchars($met, ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="<?= $badgeClass ?> col-center"><?= htmlspecialchars($est, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($masc !== '' ? $masc : '—', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($pas !== '' ? $pas : '—', ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="col-center"><?= htmlspecialchars($paseo_id, ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="col-fecha"><?= htmlspecialchars($fecha_paseo, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($ref, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($obs, ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>

        <tfoot>
            <tr>
                <td colspan="2" class="total-label">TOTAL</td>
                <td class="total-monto"><?= '₲' . moneyPy($total) ?></td>
                <td colspan="8"></td>
            </tr>
        </tfoot>
    </table>

</body>

</html>