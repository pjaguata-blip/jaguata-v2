<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../../src/Controllers/MascotaController.php';
require_once __DIR__ . '/../../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\MascotaController;
use Jaguata\Helpers\Session;

AppConfig::init();

/* 🔒 Solo dueño */
$auth = new AuthController();
$auth->checkRole('dueno');

/* Helpers */
function h(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
function safeField(array $row, string $key): string
{
    if (!isset($row[$key])) return '';
    return str_replace(["\t", "\r", "\n"], ' ', (string)$row[$key]);
}
function edadAmigable($meses): string
{
    if ($meses === null || $meses === '') return '—';
    $m = (int)$meses;
    if ($m < 12) return $m . ' mes' . ($m === 1 ? '' : 'es');
    $a = intdiv($m, 12);
    $r = $m % 12;
    return $r ? "{$a} a {$r} m" : "{$a} año" . ($a === 1 ? '' : 's');
}
function etiquetaTamano(?string $t): string
{
    return match ($t) {
        'pequeno' => 'Pequeño',
        'mediano' => 'Mediano',
        'grande'  => 'Grande',
        'gigante' => 'Gigante',
        default   => '—',
    };
}

/* Contexto usuario */
$duenoId = (int)(Session::getUsuarioId() ?? 0);
if ($duenoId <= 0) {
    http_response_code(401);
    exit('No autenticado');
}

/* Filtros GET (mismos que tu pantalla) */
$q       = trim((string)($_GET['q'] ?? ''));
$raza    = trim((string)($_GET['raza'] ?? ''));
$tamanoF = trim((string)($_GET['tamano'] ?? ''));
$edadMin = (($_GET['edad_min'] ?? '') !== '') ? (int)$_GET['edad_min'] : null;
$edadMax = (($_GET['edad_max'] ?? '') !== '') ? (int)$_GET['edad_max'] : null;

/* Datos */
$mascotaController = new MascotaController();
$mascotas = $mascotaController->index() ?? [];

/* Aplicar filtros (igual lógica que tu pantalla) */
$mascotasFiltradas = array_values(array_filter(
    $mascotas,
    function ($m) use ($q, $raza, $tamanoF, $edadMin, $edadMax) {
        $ok = true;

        if ($q !== '') {
            $txt = strtolower(($m['nombre'] ?? '') . ' ' . ($m['raza'] ?? '') . ' ' . ($m['tamano'] ?? ''));
            $ok = $ok && str_contains($txt, strtolower($q));
        }
        if ($raza !== '')    $ok = $ok && (($m['raza'] ?? '') === $raza);
        if ($tamanoF !== '') $ok = $ok && (($m['tamano'] ?? '') === $tamanoF);

        $edadMeses = (int)($m['edad_meses'] ?? ($m['edad'] ?? 0));
        if ($edadMin !== null) $ok = $ok && $edadMeses >= $edadMin;
        if ($edadMax !== null) $ok = $ok && $edadMeses <= $edadMax;

        return $ok;
    }
));

/* Métricas */
$totalMascotas   = count($mascotas);
$totalFiltradas  = count($mascotasFiltradas);
$promedioEdadMes = 0;
if ($totalFiltradas > 0) {
    $sumEdad = 0;
    foreach ($mascotasFiltradas as $m) {
        $sumEdad += (int)($m['edad_meses'] ?? ($m['edad'] ?? 0));
    }
    $promedioEdadMes = (int)round($sumEdad / $totalFiltradas);
}

/* ✅ Forzar descarga Excel (HTML) */
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=reporte_mis_mascotas_jaguata_" . date('Ymd_His') . ".xls");
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

        .m-1 {
            background: #0d6efd12;
            color: #0d6efd;
        }

        .m-2 {
            background: #19875412;
            color: #198754;
        }

        .m-3 {
            background: #ffc10712;
            color: #856404;
        }

        .m-label {
            display: block;
            font-weight: 600;
            color: #4b5563;
            margin-top: 4px;
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

        .col-id {
            text-align: center;
            width: 70px;
        }

        .col-center {
            text-align: center;
        }
    </style>
</head>

<body>

    <table class="header-table">
        <tr>
            <td class="header-title">REPORTE DE MIS MASCOTAS – JAGUATA 🐶</td>
        </tr>
        <tr>
            <td class="header-date">Generado automáticamente el <?= date("d/m/Y H:i") ?></td>
        </tr>
    </table>

    <table class="metrics">
        <tr>
            <td class="m-1">
                <?= (int)$totalMascotas ?>
                <span class="m-label">Total registradas</span>
            </td>
            <td class="m-2">
                <?= (int)$totalFiltradas ?>
                <span class="m-label">Resultado(s) filtrados</span>
            </td>
            <td class="m-3">
                <?= h(edadAmigable($promedioEdadMes)) ?>
                <span class="m-label">Edad promedio (filtrado)</span>
            </td>
        </tr>
    </table>

    <div class="filters-box">
        <div>
            <b>Filtros:</b>
            Buscar: <?= h($q !== '' ? $q : '—') ?> |
            Raza: <?= h($raza !== '' ? $raza : 'Todas') ?> |
            Tamaño: <?= h($tamanoF !== '' ? etiquetaTamano($tamanoF) : 'Todos') ?> |
            Edad mín (meses): <?= h($edadMin !== null ? (string)$edadMin : '—') ?> |
            Edad máx (meses): <?= h($edadMax !== null ? (string)$edadMax : '—') ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Raza</th>
                <th>Tamaño</th>
                <th>Peso (kg)</th>
                <th>Edad</th>
                <th>Observaciones</th>
            </tr>
        </thead>

        <tbody>
            <?php if (empty($mascotasFiltradas)): ?>
                <tr>
                    <td colspan="7" style="text-align:center; color:#777;">Sin registros de mascotas</td>
                </tr>
            <?php else: ?>
                <?php
                $i = 0;
                foreach ($mascotasFiltradas as $m):
                    $i++;
                    $rowClass = ($i % 2 === 0) ? 'fila-par' : '';

                    $id   = (string)($m['mascota_id'] ?? $m['id'] ?? '');
                    $nom  = safeField($m, 'nombre');
                    $raz  = safeField($m, 'raza');
                    $tam  = (string)($m['tamano'] ?? '');
                    $peso = (string)($m['peso_kg'] ?? $m['peso'] ?? '');
                    $edad = (string)($m['edad_meses'] ?? $m['edad'] ?? '');
                    $obs  = safeField($m, 'observaciones');

                    $tamLabel = etiquetaTamano($tam !== '' ? $tam : null);
                ?>
                    <tr class="<?= $rowClass ?>">
                        <td class="col-id"><?= h($id) ?></td>
                        <td><?= h($nom !== '' ? $nom : '—') ?></td>
                        <td><?= h($raz !== '' ? $raz : '—') ?></td>
                        <td class="col-center"><?= h($tamLabel) ?></td>
                        <td class="col-center"><?= h($peso !== '' ? $peso : '—') ?></td>
                        <td class="col-center"><?= h(edadAmigable($edad)) ?></td>
                        <td><?= h($obs !== '' ? $obs : '—') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

</body>

</html>