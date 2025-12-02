<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../../src/Controllers/UsuarioController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\UsuarioController;

AppConfig::init();

$controller = new UsuarioController();
$usuarios = $controller->obtenerDatosExportacion() ?? [];

// FORZAR DESCARGA COMO EXCEL
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=reporte_usuarios_jaguata_" . date('Ymd_His') . ".xls");
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

        .col-rol,
        .col-estado {
            text-align: center;
        }

        .estado-activo,
        .estado-aprobado {
            background: #19875433;
            color: #198754;
            font-weight: 600;
        }

        .estado-pendiente {
            background: #ffc10733;
            color: #856404;
            font-weight: 600;
        }

        .estado-rechazado,
        .estado-suspendido {
            background: #dc354533;
            color: #dc3545;
            font-weight: 600;
        }

        .estado-inactivo,
        .estado-cancelado {
            background: #adb5bd33;
            color: #6c757d;
            font-weight: 600;
        }
    </style>
</head>

<body>

    <table class="header-table">
        <tr>
            <td class="header-title">
                REPORTE DE USUARIOS – JAGUATA 👥
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
                <th>Nombre</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Estado</th>
                <th>Fecha creación</th>
                <th>Última actualización</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($usuarios)): ?>
                <tr>
                    <td colspan="7" style="text-align:center; color:#777;">Sin usuarios registrados</td>
                </tr>
                <?php else: $i = 0;
                foreach ($usuarios as $u):
                    $i++;

                    $rowClass = ($i % 2 === 0) ? 'fila-par' : '';

                    $id        = safeField($u, 'usu_id');
                    $nombre    = safeField($u, 'nombre');
                    $email     = safeField($u, 'email');
                    $rol       = safeField($u, 'rol');
                    $estado    = strtolower(safeField($u, 'estado'));
                    $creado    = safeField($u, 'created_at');
                    $actualiza = safeField($u, 'updated_at');

                    $estadoClass = 'estado-' . ($estado ?: 'pendiente');
                    $estadoLabel = ucfirst($estado ?: 'pendiente');
                ?>
                    <tr class="<?= $rowClass ?>">
                        <td class="col-id"><?= $id ?></td>
                        <td><?= $nombre ?></td>
                        <td><?= $email ?></td>
                        <td class="col-rol"><?= ucfirst($rol) ?></td>
                        <td class="col-estado <?= $estadoClass ?>"><?= $estadoLabel ?></td>
                        <td><?= $creado ?></td>
                        <td><?= $actualiza ?></td>
                    </tr>
            <?php endforeach;
            endif; ?>
        </tbody>
    </table>

</body>

</html>