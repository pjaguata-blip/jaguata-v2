<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Controllers/PagoController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Controllers\PagoController;
use Jaguata\Helpers\Session;

AppConfig::init();

/* 🔒 Auth rol dueño */
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
    return date('Y-m-d', strtotime($v));
}
function moneyPy(float $v): string
{
    return number_format($v, 0, ',', '.');
}

/* Contexto usuario */
$duenoId = (int)(Session::getUsuarioId() ?? 0);
if ($duenoId <= 0) {
    http_response_code(401);
    exit('No autenticado');
}

$rolMenu      = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures = BASE_URL . "/features/{$rolMenu}";

/* Parámetros GET */
$from       = parseDate($_GET['from'] ?? null);
$to         = parseDate($_GET['to'] ?? null);
$mascotaId  = $_GET['mascota_id'] ?? '';
$paseadorId = $_GET['paseador_id'] ?? '';
$metodo     = strtoupper(trim((string)($_GET['metodo'] ?? ''))); // UI manda EFECTIVO/TRANSFERENCIA
$estado     = strtoupper(trim((string)($_GET['estado'] ?? ''))); // UI manda CONFIRMADO/PENDIENTE/RECHAZADO
$exportCsv  = (($_GET['export'] ?? '') === 'csv');

/* Datos para filtros */
$paseoController = new PaseoController();
$mascotas        = $paseoController->listarMascotasDeDueno($duenoId) ?? [];
$paseadores      = $paseoController->listarPaseadores() ?? [];

/* Filtros para consulta */
$filters = [
    'dueno_id'    => $duenoId,
    'from'        => $from,
    'to'          => $to,
    'mascota_id'  => ($mascotaId !== '' ? $mascotaId : null),
    'paseador_id' => ($paseadorId !== '' ? $paseadorId : null),
    'metodo'      => ($metodo !== '' ? $metodo : null),  // EFECTIVO/TRANSFERENCIA
    'estado'      => ($estado !== '' ? $estado : null),  // CONFIRMADO/PENDIENTE/RECHAZADO
];

/* Consulta de pagos */
$pagoController = new PagoController();
$rows           = $pagoController->listarGastosDueno($filters) ?? [];

/* ✅ Total: siempre suma lo que ya vino filtrado del SQL */
$total = 0.0;
foreach ($rows as $r) {
    $total += (float)($r['monto'] ?? 0);
}

/* Export CSV */
if ($exportCsv) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="gastos_jaguata_' . date('Ymd_His') . '.csv"');

    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

    fputcsv($out, ['ID Pago', 'Fecha pago', 'Monto (PYG)', 'Método', 'Estado', 'Mascota', 'Paseador', 'ID Paseo', 'Fecha paseo', 'Referencia', 'Observación']);

    foreach ($rows as $r) {
        fputcsv($out, [
            $r['id'] ?? '',
            $r['fecha_pago'] ?? '',
            $r['monto'] ?? '',
            $r['metodo'] ?? '',
            $r['estado'] ?? '',
            $r['mascota'] ?? '',
            $r['paseador'] ?? '',
            $r['paseo_id'] ?? '',
            $r['fecha_paseo'] ?? '',
            $r['referencia'] ?? '',
            $r['observacion'] ?? ''
        ]);
    }

    fputcsv($out, []);
    fputcsv($out, ['TOTAL', '', '₲' . moneyPy($total)]);
    fclose($out);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gastos Totales - Jaguata</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- ✅ Bootstrap y FA primero -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <!-- ✅ Tu tema al final (para que pise a Bootstrap y "estire" el estilo) -->
    <link href="<?= ASSETS_URL; ?>/css/jaguata-theme.css" rel="stylesheet">

    <style>
        /* (Opcional) podés mover esto al jaguata-theme.css luego */
        .dash-card {
            background: #fff;
            border-radius: 18px;
            padding: 18px 20px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, .06);
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 6px;
        }

        .dash-card-icon {
            font-size: 2rem;
            margin-bottom: 6px;
        }

        .dash-card-value {
            font-size: 1.4rem;
            font-weight: 800;
        }

        .icon-green {
            color: var(--verde-jaguata, #3c6255);
        }

        .icon-blue {
            color: #0d6efd;
        }

        .icon-yellow {
            color: #ffc107;
        }
    </style>
</head>

<body class="page-gastos">

    <!-- ✅ Botón hamburguesa mobile -->
    <button class="btn btn-outline-secondary d-md-none ms-2 mt-2" id="toggleSidebar">
        <i class="fas fa-bars"></i>
    </button>

    <div class="layout">
        <!-- ✅ Sidebar dueño unificado -->
        <?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>

        <main class="content bg-light">
            <div class="container-fluid py-1">

                <div class="header-box header-pagos mb-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h4 mb-1 fw-bold">
                            <i class="fas fa-coins me-2"></i> Gastos Totales
                        </h1>
                        <p class="mb-0 text-white-50">Visualizá y exportá tus pagos realizados en Jaguata.</p>
                    </div>
                    <a
                        href="<?= BASE_URL ?>/public/api/pagos/reporte_gastos_dueno.php?<?= h(http_build_query($_GET)) ?>"
                        class="btn btn-outline-light btn-sm fw-semibold">
                        <i class="fas fa-file-excel me-1"></i> Exportar Excel
                    </a>

                </div>

                <div class="row g-3 mb-4 text-center">
                    <div class="col-md-4">
                        <div class="dash-card">
                            <i class="fas fa-wallet dash-card-icon icon-green"></i>
                            <div class="dash-card-value">₲<?= moneyPy($total) ?></div>
                            <div class="text-muted small">
                                <?= $estado ? 'Según filtro de estado' : 'Por defecto: CONFIRMADOS' ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="dash-card">
                            <i class="fas fa-file-invoice-dollar dash-card-icon icon-blue"></i>
                            <div class="dash-card-value"><?= (int)count($rows) ?></div>
                            <div class="text-muted small">Registros encontrados</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="dash-card">
                            <i class="fas fa-calendar-alt dash-card-icon icon-yellow"></i>
                            <div class="dash-card-value fs-6">
                                <?= h(($from ?? '—') . ' a ' . ($to ?? '—')) ?>
                            </div>
                            <div class="text-muted small">Rango seleccionado</div>
                        </div>
                    </div>
                </div>

                <div class="filtros">
                    <form class="row g-3 align-items-end" method="get">
                        <div class="col-md-3">
                            <label class="form-label">Desde</label>
                            <input type="date" class="form-control" name="from" value="<?= h($from) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Hasta</label>
                            <input type="date" class="form-control" name="to" value="<?= h($to) ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Mascota</label>
                            <select class="form-select" name="mascota_id">
                                <option value="">Todas</option>
                                <?php foreach ($mascotas as $m): ?>
                                    <?php $mid = $m['id'] ?? $m['mascota_id'] ?? null; ?>
                                    <option value="<?= h((string)$mid) ?>" <?= ((string)$mascotaId === (string)$mid ? 'selected' : '') ?>>
                                        <?= h($m['nombre'] ?? ('#' . $mid)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Paseador</label>
                            <select class="form-select" name="paseador_id">
                                <option value="">Todos</option>
                                <?php foreach ($paseadores as $p): ?>
                                    <?php $pid = $p['id'] ?? $p['usu_id'] ?? null; ?>
                                    <option value="<?= h((string)$pid) ?>" <?= ((string)$paseadorId === (string)$pid ? 'selected' : '') ?>>
                                        <?= h($p['nombre'] ?? ('#' . $pid)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Método</label>
                            <select class="form-select" name="metodo">
                                <option value="">Todos</option>
                                <option value="EFECTIVO" <?= $metodo === 'EFECTIVO' ? 'selected' : '' ?>>EFECTIVO</option>
                                <option value="TRANSFERENCIA" <?= $metodo === 'TRANSFERENCIA' ? 'selected' : '' ?>>TRANSFERENCIA</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Estado</label>
                            <select class="form-select" name="estado">
                                <option value="">(Por defecto: Confirmados)</option>
                                <option value="PENDIENTE" <?= $estado === 'PENDIENTE'  ? 'selected' : '' ?>>PENDIENTE</option>
                                <option value="CONFIRMADO" <?= $estado === 'CONFIRMADO' ? 'selected' : '' ?>>CONFIRMADO</option>
                                <option value="RECHAZADO" <?= $estado === 'RECHAZADO'  ? 'selected' : '' ?>>RECHAZADO</option>
                            </select>
                        </div>

                        <div class="col-12 d-flex gap-2">
                            <button class="btn btn-accion btn-activar">
                                <i class="fas fa-search me-1"></i> Aplicar
                            </button>
                            <a href="<?= $baseFeatures; ?>/GastosTotales.php" class="btn btn-accion btn-desactivar">
                                <i class="fas fa-undo me-1"></i> Limpiar
                            </a>
                        </div>
                    </form>
                </div>

                <div class="section-card mt-4">
                    <div class="section-header">
                        <i class="fas fa-list me-2"></i>Detalle de Pagos
                    </div>

                    <div class="section-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle text-center mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Fecha pago</th>
                                        <th>Monto</th>
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
                                            <td colspan="11" class="text-muted py-4">No hay registros disponibles</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($rows as $r):
                                            $st = strtoupper((string)($r['estado'] ?? ''));
                                            $badge = [
                                                'CONFIRMADO' => 'success',
                                                'PENDIENTE'  => 'warning',
                                                'RECHAZADO'  => 'danger',
                                            ][$st] ?? 'secondary';
                                        ?>
                                            <tr>
                                                <td><?= h((string)($r['id'] ?? '')) ?></td>
                                                <td><?= h((string)($r['fecha_pago'] ?? '')) ?></td>
                                                <td class="fw-bold text-success">₲<?= moneyPy((float)($r['monto'] ?? 0)) ?></td>
                                                <td><?= h((string)($r['metodo'] ?? '')) ?></td>
                                                <td><span class="badge bg-<?= $badge ?>"><?= h($st) ?></span></td>
                                                <td><?= h((string)($r['mascota'] ?? '—')) ?></td>
                                                <td><?= h((string)($r['paseador'] ?? '—')) ?></td>
                                                <td><?= h((string)($r['paseo_id'] ?? '')) ?></td>
                                                <td><?= h((string)($r['fecha_paseo'] ?? '')) ?></td>
                                                <td><?= h((string)($r['referencia'] ?? '')) ?></td>
                                                <td><?= h((string)($r['observacion'] ?? '')) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>

                                <?php if (!empty($rows)): ?>
                                    <tfoot>
                                        <tr class="table-light fw-bold">
                                            <td colspan="2">TOTAL</td>
                                            <td>₲<?= moneyPy($total) ?></td>
                                            <td colspan="8"></td>
                                        </tr>
                                    </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>

                <footer class="mt-4 text-center text-muted small">
                    © <?= date('Y') ?> Jaguata — Panel del Dueño
                </footer>

            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('toggleSidebar')?.addEventListener('click', function() {
            document.getElementById('sidebar')?.classList.toggle('sidebar-open');
        });
    </script>
</body>

</html>