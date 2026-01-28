<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

/**
 * ✅ ROOT robusto: sube carpetas hasta encontrar /src/Config/AppConfig.php
 * Este archivo está en /public/api/pagos/ => sube hasta /jaguata
 */
$dir = __DIR__;
$ROOT = null;

for ($i = 0; $i < 10; $i++) {
    if (file_exists($dir . '/src/Config/AppConfig.php')) {
        $ROOT = realpath($dir);
        break;
    }
    $parent = dirname($dir);
    if ($parent === $dir) break;
    $dir = $parent;
}

if (!$ROOT) {
    http_response_code(500);
    die('❌ No se encontró el ROOT del proyecto (src/Config/AppConfig.php).');
}

require_once $ROOT . '/src/Config/AppConfig.php';
require_once $ROOT . '/src/Controllers/AuthController.php';
require_once $ROOT . '/src/Controllers/PagoController.php';
require_once $ROOT . '/src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PagoController;
use Jaguata\Helpers\Session;

AppConfig::init();

$auth = new AuthController();
$auth->checkRole('dueno');

/* Helpers */
function h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function parseDate(?string $v): ?string
{
    $v = $v ? trim($v) : '';
    if ($v === '') return null;
    $t = strtotime($v);
    if ($t === false) return null;
    return date('Y-m-d', $t);
}
function moneyPy(float $v): string
{
    return number_format($v, 0, ',', '.');
}
function safeField(array $row, string $key): string
{
    if (!isset($row[$key]) || $row[$key] === null) return '';
    return str_replace(["\t", "\r", "\n"], ' ', (string)$row[$key]);
}

/* Contexto usuario */
$duenoId = (int)(Session::getUsuarioId() ?? 0);
if ($duenoId <= 0) {
    http_response_code(401);
    exit('No autenticado');
}
$usuarioNombre = h(Session::getUsuarioNombre() ?? 'Dueño');

/* Parámetros GET */
$from       = parseDate($_GET['from'] ?? null);
$to         = parseDate($_GET['to'] ?? null);
$mascotaId  = trim((string)($_GET['mascota_id'] ?? ''));
$paseadorId = trim((string)($_GET['paseador_id'] ?? ''));
$metodo     = strtoupper(trim((string)($_GET['metodo'] ?? '')));
$estado     = strtoupper(trim((string)($_GET['estado'] ?? '')));

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

/* Headers Excel */
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=reporte_gastos_dueno_jaguata_" . date('Ymd_His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

/* BOM UTF-8 */
echo "\xEF\xBB\xBF";

/* ✅ Etiquetas lindas para filtros */
$fMascota  = ($mascotaId !== '' ? "ID $mascotaId" : 'Todas');
$fPaseador = ($paseadorId !== '' ? "ID $paseadorId" : 'Todos');
$fMetodo   = ($metodo !== '' ? $metodo : 'Todos');
$fEstado   = ($estado !== '' ? $estado : 'Por defecto (Confirmados)');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        /* ====== Jaguata Report Theme ====== */
        body{
            font-family:"Poppins", Arial, sans-serif;
            font-size:12px;
            color:#111827;
            margin:0;
            padding:0;
        }

        .wrap{
            padding:12px 12px 18px;
        }

        /* Header */
        .hero{
            width:100%;
            border-collapse:collapse;
            margin-bottom:12px;
        }
        .hero-title{
            background:#3c6255;
            color:#ffffff;
            font-size:20px;
            font-weight:800;
            letter-spacing:.2px;
            text-align:center;
            padding:12px 10px;
            border-radius:10px 10px 0 0;
        }
        .hero-sub{
            background:#20c99733;
            color:#1e5247;
            font-size:13px;
            font-weight:700;
            text-align:center;
            padding:8px 10px;
            border-radius:0 0 10px 10px;
        }

        /* Badges */
        .pill{
            display:inline-block;
            padding:3px 8px;
            border-radius:999px;
            font-weight:800;
            font-size:11px;
            white-space:nowrap;
        }
        .pill-ok{ background:#19875422; color:#198754; }
        .pill-warn{ background:#ffc10722; color:#856404; }
        .pill-bad{ background:#dc354522; color:#dc3545; }
        .pill-other{ background:#6c757d22; color:#495057; }

        /* Info cards */
        .meta{
            width:100%;
            border-collapse:collapse;
            margin:10px 0 12px;
        }
        .meta td{
            border:1px solid #e5e7eb;
            padding:10px 10px;
            vertical-align:top;
        }
        .meta .box-title{
            font-weight:900;
            color:#1e5247;
            margin-bottom:4px;
        }
        .meta .box-val{
            font-weight:700;
            color:#111827;
        }
        .meta .soft{
            background:#f8fafb;
            border-radius:10px;
        }

        /* Table */
        table.report{
            border-collapse:collapse;
            width:100%;
        }
        .report th, .report td{
            border:1px solid #dee2e6;
            padding:6px 8px;
            vertical-align:top;
        }
        .report th{
            background:#3c6255;
            color:#ffffff;
            font-weight:800;
            text-align:center;
            white-space:nowrap;
        }
        .report tr:nth-child(even) td{
            background:#f4f6f9;
        }

        /* Cols */
        .c-id{ text-align:center; width:70px; }
        .c-fecha{ text-align:center; white-space:nowrap; }
        .c-monto{ text-align:right; white-space:nowrap; font-weight:900; color:#198754; }
        .c-center{ text-align:center; }

        /* Footer total */
        tfoot td{
            background:#eef2f4;
            font-weight:900;
        }
        .total-label{
            text-align:right;
        }
        .total-monto{
            text-align:right;
            color:#1e5247;
            font-size:13px;
        }

        /* Small */
        .muted{ color:#6b7280; font-weight:600; }
    </style>
</head>
<body>
<div class="wrap">

    <!-- Header lindo -->
    <table class="hero">
        <tr><td class="hero-title">REPORTE DE GASTOS TOTALES — JAGUATA 💰</td></tr>
        <tr><td class="hero-sub">Generado el <?= date("d/m/Y H:i") ?> — Usuario: <?= $usuarioNombre ?></td></tr>
    </table>

    <!-- Meta / filtros + resumen -->
    <table class="meta">
        <tr>
            <td class="soft" style="width:70%;">
                <div class="box-title">Filtros aplicados</div>
                <div class="box-val">
                    <span class="muted">Desde:</span> <?= h($from ?? '—') ?> &nbsp; | &nbsp;
                    <span class="muted">Hasta:</span> <?= h($to ?? '—') ?> &nbsp; | &nbsp;
                    <span class="muted">Mascota:</span> <?= h($fMascota) ?> &nbsp; | &nbsp;
                    <span class="muted">Paseador:</span> <?= h($fPaseador) ?> &nbsp; | &nbsp;
                    <span class="muted">Método:</span> <?= h($fMetodo) ?> &nbsp; | &nbsp;
                    <span class="muted">Estado:</span> <?= h($fEstado) ?>
                </div>
            </td>
            <td class="soft" style="width:30%; text-align:center;">
                <div class="box-title">Resumen</div>
                <div class="box-val" style="font-size:18px; color:#1e5247;">₲<?= moneyPy($total) ?></div>
                <div class="muted">Pagos: <?= (int)count($rows) ?></div>
            </td>
        </tr>
    </table>

    <!-- Tabla -->
    <table class="report">
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
                <td colspan="11" style="text-align:center; color:#777; padding:14px 8px;">
                    Sin registros de gastos con los filtros aplicados.
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($rows as $r):
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

                $pillClass = match ($est) {
                    'CONFIRMADO' => 'pill pill-ok',
                    'PENDIENTE'  => 'pill pill-warn',
                    'RECHAZADO'  => 'pill pill-bad',
                    default      => 'pill pill-other',
                };
            ?>
                <tr>
                    <td class="c-id"><?= h($id) ?></td>
                    <td class="c-fecha"><?= h($fecha_pago) ?></td>
                    <td class="c-monto"><?= h($monto) ?></td>
                    <td class="c-center"><?= h($met) ?></td>
                    <td class="c-center"><span class="<?= $pillClass ?>"><?= h($est) ?></span></td>
                    <td><?= h($masc !== '' ? $masc : '—') ?></td>
                    <td><?= h($pas !== '' ? $pas : '—') ?></td>
                    <td class="c-center"><?= h($paseo_id) ?></td>
                    <td class="c-fecha"><?= h($fecha_paseo) ?></td>
                    <td><?= h($ref) ?></td>
                    <td><?= h($obs) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>

        <tfoot>
        <tr>
            <td colspan="2" class="total-label">TOTAL</td>
            <td class="total-monto">₲<?= moneyPy($total) ?></td>
            <td colspan="8"></td>
        </tr>
        </tfoot>
    </table>

</div>
</body>
</html>
