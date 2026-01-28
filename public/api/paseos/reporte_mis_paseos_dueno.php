<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Helpers\Session;

AppConfig::init();
$auth = new AuthController();
$auth->checkRole('dueno');

/* Helpers */
$norm = static function ($s): string {
    return strtolower(trim((string)$s));
};

function safeField(array $row, string $key): string
{
    if (!isset($row[$key])) return '';
    return str_replace(["\t", "\r", "\n"], ' ', (string)$row[$key]);
}

function moneyPy(float $v): string
{
    return number_format($v, 0, ',', '.');
}

function fmtDateTime(?string $v): string
{
    $v = $v ? trim($v) : '';
    if ($v === '') return '—';
    $t = strtotime($v);
    if ($t === false) return '—';
    return date('d/m/Y H:i', $t);
}

function fmtEstado(string $estado): string
{
    $estado = strtolower(trim($estado));
    $estado = str_replace('_', ' ', $estado);
    return $estado === '' ? '—' : ucfirst($estado);
}

/* Contexto usuario */
$duenoId = (int)(Session::getUsuarioId() ?? 0);
if ($duenoId <= 0) {
    http_response_code(401);
    exit('No autenticado');
}

/* Datos */
$paseoCtrl = new PaseoController();
$all = $paseoCtrl->indexByDueno($duenoId) ?? [];

/* Estados válidos + filtro GET */
$estadosValidos = ['solicitado', 'confirmado', 'en_curso', 'completo', 'cancelado'];
$estadoFiltro = $norm($_GET['estado'] ?? '');

$paseos = $all;
if ($estadoFiltro !== '' && in_array($estadoFiltro, $estadosValidos, true)) {
    $paseos = array_values(array_filter(
        $paseos,
        fn($p) => $norm($p['estado'] ?? '') === $estadoFiltro
    ));
}

$total      = count($all);
$pendientes = array_filter($all, fn($p) => in_array($norm($p['estado'] ?? ''), ['solicitado', 'confirmado'], true));
$completos  = array_filter($all, fn($p) => $norm($p['estado'] ?? '') === 'completo');
$cancelados = array_filter($all, fn($p) => $norm($p['estado'] ?? '') === 'cancelado');
$gastoTotal = array_sum(array_map(fn($p) => (float)($p['precio_total'] ?? 0), $completos));

header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=reporte_mis_paseos_jaguata_" . date('Ymd_His') . ".xls");
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

        .metrics {
            margin: 10px 0 15px;
            width: 100%;
            border-collapse: collapse;
        }

        .metrics td {
            border: 1px solid #dee2e6;
            padding: 8px 10px;
            text-align: center;
            font-weight: 700;
        }

        .m-label {
            display: block;
            font-weight: 600;
            color: #4b5563;
            margin-top: 4px;
        }

        .m-total {
            background: #0d6efd12;
            color: #0d6efd;
        }

        .m-pend {
            background: #ffc10712;
            color: #856404;
        }

        .m-comp {
            background: #19875412;
            color: #198754;
        }

        .m-gasto {
            background: #dc354512;
            color: #dc3545;
        }

        .filters-box {
            margin: 0 0 12px;
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

        .col-fecha {
            text-align: center;
            white-space: nowrap;
        }

        .col-center {
            text-align: center;
        }

        .col-precio {
            text-align: right;
            white-space: nowrap;
            font-weight: 700;
            color: #1e5247;
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

        .badge-info {
            background: #0dcaf033;
            color: #0b7285;
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
    </style>
</head>

<body>

    <table class="header-table">
        <tr>
            <td class="header-title">REPORTE DE MIS PASEOS – JAGUATA 🐾</td>
        </tr>
        <tr>
            <td class="header-date">Generado automáticamente el <?= date("d/m/Y H:i") ?></td>
        </tr>
    </table>

    <table class="metrics">
        <tr>
            <td class="m-total">
                <?= (int)$total ?>
                <span class="m-label">Total de paseos</span>
            </td>
            <td class="m-pend">
                <?= (int)count($pendientes) ?>
                <span class="m-label">Solicitados/Confirmados</span>
            </td>
            <td class="m-comp">
                <?= (int)count($completos) ?>
                <span class="m-label">Completados</span>
            </td>
            <td class="m-gasto">
                ₲<?= moneyPy((float)$gastoTotal) ?>
                <span class="m-label">Gasto total (completos)</span>
            </td>
        </tr>
    </table>

    <div class="filters-box">
        <div>
            <b>Filtro estado:</b>
            <?= htmlspecialchars($estadoFiltro !== '' ? $estadoFiltro : 'Todos', ENT_QUOTES, 'UTF-8') ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Mascota</th>
                <th>Paseador</th>
                <th>Fecha</th>
                <th>Duración (min)</th>
                <th>Estado</th>
                <th>Precio (PYG)</th>
            </tr>
        </thead>

        <tbody>
            <?php if (empty($paseos)): ?>
                <tr>
                    <td colspan="6" style="text-align:center; color:#777;">Sin registros de paseos</td>
                </tr>
            <?php else: ?>
                <?php
                $i = 0;
                foreach ($paseos as $p):
                    $i++;
                    $rowClass = ($i % 2 === 0) ? 'fila-par' : '';

                    $estadoRaw = $norm($p['estado'] ?? '');
                    $estado    = $estadoRaw !== '' ? $estadoRaw : 'solicitado';

                    $badgeClass = match ($estado) {
                        'completo'   => 'badge-ok',
                        'cancelado'  => 'badge-bad',
                        'en_curso'   => 'badge-info',
                        'confirmado' => 'badge-other',
                        'solicitado' => 'badge-warn',
                        default      => 'badge-other',
                    };

                    $mascota  = safeField($p, 'nombre_mascota');
                    $paseador = safeField($p, 'nombre_paseador');
                    $inicio   = fmtDateTime((string)($p['inicio'] ?? ''));
                    $dur      = (int)($p['duracion'] ?? 0);
                    $precio   = '₲' . moneyPy((float)($p['precio_total'] ?? 0));
                ?>
                    <tr class="<?= $rowClass ?>">
                        <td><?= htmlspecialchars($mascota !== '' ? $mascota : '—', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($paseador !== '' ? $paseador : '—', ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="col-fecha"><?= htmlspecialchars($inicio, ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="col-center"><?= $dur ?></td>
                        <td class="<?= $badgeClass ?> col-center"><?= htmlspecialchars(fmtEstado($estado), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="col-precio"><?= htmlspecialchars($precio, ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

</body>

</html>