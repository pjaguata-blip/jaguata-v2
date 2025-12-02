<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../../src/Controllers/AuditoriaController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuditoriaController;

AppConfig::init();

$controller = new AuditoriaController();
$registros = $controller->obtenerDatosExportacion() ?? [];

// FORZAR DESCARGA COMO EXCEL
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=reporte_auditoria_jaguata_" . date('Ymd_His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// BOM UTF-8
echo "\xEF\xBB\xBF";

function safeField(array $row, string $key): string
{
    if (!isset($row[$key])) return '';
    return str_replace(["\t", "\r", "\n"], ' ', (string)$row[$key]);
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

        .col-id {
            text-align: center;
            width: 60px;
        }

        .col-fecha {
            text-align: center;
            white-space: nowrap;
        }

        .col-modulo {
            text-align: center;
        }

        .badge-accion-login {
            background: #19875433;
            color: #198754;
            font-weight: 600;
        }

        .badge-accion-update {
            background: #0d6efd33;
            color: #0d6efd;
            font-weight: 600;
        }

        .badge-accion-delete {
            background: #dc354533;
            color: #dc3545;
            font-weight: 600;
        }

        .badge-accion-other {
            background: #ffc10733;
            color: #856404;
            font-weight: 600;
        }
    </style>
</head>

<body>

    <table class="header-table">
        <tr>
            <td class="header-title">
                REPORTE DE AUDITORÍA – JAGUATA 🔐
            </td>
        </tr>
        <tr>
            <td class="header-date">
                Generado automáticamente el <?= date("d/m/Y H:i") ?>
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Acción</th>
                <th>Módulo</th>
                <th>Detalles</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($registros)): ?>
                <tr>
                    <td colspan="6" style="text-align:center; color:#777;">Sin registros de auditoría</td>
                </tr>
                <?php else: $i = 0;
                foreach ($registros as $r):
                    $i++;

                    $rowClass = ($i % 2 === 0) ? 'fila-par' : '';

                    $id      = safeField($r, 'id');
                    $usuario = safeField($r, 'usuario');
                    $accion  = safeField($r, 'accion');
                    $modulo  = safeField($r, 'modulo');
                    $detalles = safeField($r, 'detalles');
                    $fecha   = safeField($r, 'fecha');

                    $accionLower = strtolower($accion);
                    if (str_contains($accionLower, 'elimin')) {
                        $accionClass = 'badge-accion-delete';
                    } elseif (str_contains($accionLower, 'actualiz') || str_contains($accionLower, 'modif')) {
                        $accionClass = 'badge-accion-update';
                    } elseif (str_contains($accionLower, 'inicio') || str_contains($accionLower, 'login')) {
                        $accionClass = 'badge-accion-login';
                    } else {
                        $accionClass = 'badge-accion-other';
                    }
                ?>
                    <tr class="<?= $rowClass ?>">
                        <td class="col-id"><?= $id ?></td>
                        <td><?= $usuario ?></td>
                        <td class="<?= $accionClass ?>"><?= $accion ?></td>
                        <td class="col-modulo"><?= $modulo ?></td>
                        <td><?= $detalles ?></td>
                        <td class="col-fecha"><?= $fecha ?></td>
                    </tr>
            <?php endforeach;
            endif; ?>
        </tbody>
    </table>

</body>

</html>