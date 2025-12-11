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
function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function parseDate(?string $v): ?string
{
    return $v ? date('Y-m-d', strtotime($v)) : null;
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
$mascotaId  = $_GET['mascota_id'] ?? null;
$paseadorId = $_GET['paseador_id'] ?? null;
$metodo     = trim((string)($_GET['metodo'] ?? ''));
$estado     = trim((string)($_GET['estado'] ?? ''));
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
    'mascota_id'  => $mascotaId,
    'paseador_id' => $paseadorId,
    'metodo'      => $metodo,
    'estado'      => $estado,
];

/* Consulta de pagos */
$pagoController = new PagoController();
$rows           = $pagoController->listarGastosDueno($filters) ?? [];

/* Total: si se filtra por estado usamos ese, de lo contrario solo CONFIRMADO */
$total = 0.0;
foreach ($rows as $r) {
    if ($estado !== '') {
        $total += (float)$r['monto'];
    } elseif (strcasecmp((string)$r['estado'], 'CONFIRMADO') === 0) {
        $total += (float)$r['monto'];
    }
}

/* Export CSV */
if ($exportCsv) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="gastos_jaguata_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    // BOM UTF-8
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($out, ['ID Pago', 'Fecha pago', 'Monto (PYG)', 'Método', 'Estado', 'Mascota', 'Paseador', 'ID Paseo', 'Fecha paseo', 'Referencia', 'Observación']);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['id'],
            $r['fecha_pago'],
            $r['monto'],
            $r['metodo'],
            $r['estado'],
            $r['mascota'],
            $r['paseador'],
            $r['paseo_id'],
            $r['fecha_paseo'],
            $r['referencia'],
            $r['observacion']
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

    <!-- CSS global Jaguata -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <style>
        /* Que estire como los dashboards */
        html,
        body {
            height: 100%;
        }

        body {
            background: var(--gris-fondo, #f4f6f9);
        }

        main.main-content {
            margin-left: 260px;
            min-height: 100vh;
            padding: 24px;
        }

        @media (max-width: 768px) {
            main.main-content {
                margin-left: 0;
                padding: 16px;
            }
        }

        /* Tarjetas tipo dashboard */
        .dash-card {
            background: #ffffff;
            border-radius: 18px;
            padding: 18px 20px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.06);
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
            font-weight: 700;
            color: #222;
        }

        .dash-card-label {
            font-size: 0.9rem;
            color: #555;
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

<body>

    <!-- Sidebar dueño unificada -->
    <?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>

    <!-- Botón hamburguesa para mobile -->
    <button class="btn btn-outline-secondary d-md-none ms-2 mt-3" id="toggleSidebar">
        <i class="fas fa-bars"></i>
    </button>

    <main class="main-content">
        <div class="py-2">

            <!-- Header -->
            <div class="header-box header-pagos mb-4 d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="fw-bold mb-1">
                        <i class="fas fa-coins me-2"></i>Gastos Totales
                    </h1>
                    <p class="mb-0">Visualizá y exportá tus pagos realizados en Jaguata.</p>
                </div>
                <div class="text-end">
                    <a href="?<?= h(http_build_query(array_merge($_GET, ['export' => 'csv']))) ?>"
                        class="btn btn-outline-light fw-semibold">
                        <i class="fas fa-file-csv me-1"></i> Exportar CSV
                    </a>
                </div>
            </div>

            <!-- Métricas tipo dashboard -->
            <div class="row g-3 mb-4 text-center">
                <div class="col-md-4">
                    <div class="dash-card">
                        <i class="fas fa-wallet dash-card-icon icon-green"></i>
                        <div class="dash-card-value">₲<?= moneyPy($total) ?></div>
                        <div class="dash-card-label">Total gastado (PYG)</div>
                        <small class="text-muted d-block">
                            <?= $estado ? 'Según filtro de estado' : 'Solo pagos CONFIRMADOS' ?>
                        </small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dash-card">
                        <i class="fas fa-file-invoice-dollar dash-card-icon icon-blue"></i>
                        <div class="dash-card-value"><?= count($rows) ?></div>
                        <div class="dash-card-label">Registros encontrados</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dash-card">
                        <i class="fas fa-calendar-alt dash-card-icon icon-yellow"></i>
                        <div class="dash-card-value fs-6">
                            <?= h(($from ?? '—') . ' a ' . ($to ?? '—')) ?>
                        </div>
                        <div class="dash-card-label">Rango seleccionado</div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
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
                                <option value="<?= h((string)$mid) ?>" <?= ($mascotaId == $mid ? 'selected' : '') ?>>
                                    <?= h($m['nombre'] ?? '#' . $mid) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Paseador</label>
                        <select class="form-select" name="paseador_id">
                            <option value="">Todos</option>
                            <?php foreach ($paseadores as $p): ?>
                                <option value="<?= h((string)$p['id']) ?>" <?= ($paseadorId == $p['id'] ? 'selected' : '') ?>>
                                    <?= h($p['nombre'] ?? '#' . $p['id']) ?>
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
                            <option value="">Todos</option>
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

            <!-- Tabla -->
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
                                        <td colspan="11" class="text-muted py-4">
                                            No hay registros disponibles
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($rows as $r):
                                        $st    = strtoupper((string)($r['estado'] ?? ''));
                                        $badge = [
                                            'CONFIRMADO' => 'success',
                                            'PENDIENTE'  => 'warning',
                                            'RECHAZADO'  => 'danger',
                                        ][$st] ?? 'secondary';
                                    ?>
                                        <tr>
                                            <td><?= h((string)$r['id']) ?></td>
                                            <td><?= h($r['fecha_pago']) ?></td>
                                            <td class="fw-bold text-success">
                                                ₲<?= moneyPy((float)$r['monto']) ?>
                                            </td>
                                            <td><?= h($r['metodo']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $badge ?>">
                                                    <?= h($st) ?>
                                                </span>
                                            </td>
                                            <td><?= h($r['mascota']) ?></td>
                                            <td><?= h($r['paseador']) ?></td>
                                            <td><?= h((string)$r['paseo_id']) ?></td>
                                            <td><?= h($r['fecha_paseo']) ?></td>
                                            <td><?= h($r['referencia']) ?></td>
                                            <td><?= h($r['observacion']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <?php if (!empty($rows)): ?>
                                <tfoot>
                                    <tr class="table-light fw-bold">
                                        <td colspan="2">
                                            TOTAL (<?= h($estado ?: 'CONFIRMADO') ?>)
                                        </td>
                                        <td>
                                            ₲<?= moneyPy($total) ?>
                                        </td>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar en mobile
        document.getElementById('toggleSidebar')?.addEventListener('click', function() {
            document.getElementById('sidebar')?.classList.toggle('sidebar-open');
        });
    </script>
</body>

</html>