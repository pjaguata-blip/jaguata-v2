<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Helpers\Session;

AppConfig::init();
$auth = new AuthController();
$auth->checkRole('paseador');

/* Base */
$rolMenu      = Session::getUsuarioRol() ?: 'paseador';
$baseFeatures = BASE_URL . "/features/{$rolMenu}";
$paseadorId   = (int)(Session::getUsuarioId() ?? 0);

$paseoController = new PaseoController();

/* Paseos del paseador */
$paseos = $paseadorId > 0 ? ($paseoController->indexForPaseador($paseadorId) ?: []) : [];

/* Helper */
function h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

/* ====== Filtro por fecha (ganancias) ====== */
$fechaInicio = $_GET['fecha_inicio'] ?? '';
$fechaFin    = $_GET['fecha_fin'] ?? '';

$paseosCompletados = array_filter($paseos, function ($p) use ($fechaInicio, $fechaFin) {
    $estado = strtolower((string)($p['estado'] ?? ''));
    $fecha  = !empty($p['inicio']) ? date('Y-m-d', strtotime((string)$p['inicio'])) : null;

    if (!$fecha || !in_array($estado, ['completo', 'finalizado', 'completado'], true)) return false;
    if ($fechaInicio && $fecha < $fechaInicio) return false;
    if ($fechaFin && $fecha > $fechaFin) return false;
    return true;
});

$gananciasTotales = array_sum(array_map(fn($p) => (float)($p['precio_total'] ?? 0), $paseosCompletados));

/* ====== Construir array de pagos ====== */
$pagos = [];
foreach ($paseos as $p) {
    $estadoPagoRaw = strtolower((string)($p['estado_pago'] ?? ''));
    $estadoPago = in_array($estadoPagoRaw, ['procesado', 'pagado'], true) ? 'pagado' : 'pendiente';

    // ⚠️ depende de que indexForPaseador() traiga pago_id
    $pagoId = (isset($p['pago_id']) && (int)$p['pago_id'] > 0) ? (int)$p['pago_id'] : null;

    $pagos[] = [
        'id'       => (int)($p['paseo_id'] ?? 0),
        'pago_id'  => $pagoId,
        'paseo'    => 'Paseo de ' . ($p['mascota_nombre'] ?? $p['nombre_mascota'] ?? 'Mascota'),
        'monto'    => (float)($p['precio_total'] ?? 0),
        'fecha'    => $p['inicio'] ?? null,
        'estado'   => $estadoPago,
    ];
}

$totalPagado    = array_sum(array_map(fn($x) => $x['estado'] === 'pagado' ? (float)$x['monto'] : 0, $pagos));
$totalPendiente = array_sum(array_map(fn($x) => $x['estado'] === 'pendiente' ? (float)$x['monto'] : 0, $pagos));
$totalPaseos    = count($pagos);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagos y Ganancias - Paseador | Jaguata</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <!-- ✅ mismo theme que el resto -->
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <style>
        html, body { height:100%; overflow-x:hidden; }
        body { background: var(--gris-fondo, #f4f6f9); }

        /* ✅ Layout igual Dashboard */
        main.main-content{
            margin-left: var(--sidebar-w);
            width: calc(100% - var(--sidebar-w));
            min-height: 100vh;
            padding: 24px;
            box-sizing: border-box;
        }
        @media (max-width: 768px){
            main.main-content{
                margin-left: 0 !important;
                width: 100% !important;
                margin-top: 0 !important;
                padding: calc(16px + var(--topbar-h)) 16px 16px !important;
            }
        }

        .ganancias-total-box{
            border: 1px solid rgba(15,23,42,.12);
            background: rgba(60,98,85,.06);
            border-radius: 14px;
            padding: 14px 16px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap: 12px;
        }
        .ganancias-total-label{ font-weight:700; color:#1f2937; }
        .ganancias-total-value{ font-size: 1.35rem; font-weight: 900; color: var(--verde-jaguata, #3c6255); }

        .pill{
            display:inline-flex;
            align-items:center;
            gap:.4rem;
            padding:.25rem .65rem;
            border-radius:999px;
            font-size:.78rem;
            background: rgba(60, 98, 85, .10);
            color: var(--verde-jaguata, #3c6255);
            border: 1px solid rgba(60, 98, 85, .18);
            font-weight:700;
            white-space: nowrap;
        }
    </style>
</head>

<body>
<?php include __DIR__ . '/../../src/Templates/SidebarPaseador.php'; ?>

<main class="main-content">
    <div class="py-2">

        <!-- Header -->
        <div class="header-box header-dashboard mb-2 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="fw-bold mb-1">
                    <i class="fas fa-wallet me-2"></i> Pagos y ganancias
                </h1>
                <p class="mb-0 text-white-50">
                    Visualizá tus ingresos, pagos pendientes y exportá tus ganancias.
                </p>
            </div>
            <a href="<?= $baseFeatures; ?>/Dashboard.php" class="btn btn-outline-light btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
        </div>

        <!-- Totales -->
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="stat-card text-center">
                    <i class="fas fa-check-circle text-success mb-2"></i>
                    <h4>₲<?= number_format((float)$totalPagado, 0, ',', '.') ?></h4>
                    <p class="mb-0">Total recibido</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card text-center">
                    <i class="fas fa-hourglass-half text-warning mb-2"></i>
                    <h4>₲<?= number_format((float)$totalPendiente, 0, ',', '.') ?></h4>
                    <p class="mb-0">Pendiente de cobro</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card text-center">
                    <i class="fas fa-list text-info mb-2"></i>
                    <h4><?= (int)$totalPaseos ?></h4>
                    <p class="mb-0">Total de paseos</p>
                </div>
            </div>
        </div>

        <!-- ✅ MIS GANANCIAS -->
        <div class="section-card mb-3">
            <div class="section-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-coins"></i>
                    <span>Mis ganancias</span>
                </div>

                <?php if (!empty($paseosCompletados)): ?>
                    <a href="<?= BASE_URL ?>/public/api/pagos/exportarGananciasPaseador.php?fecha_inicio=<?= urlencode((string)$fechaInicio) ?>&fecha_fin=<?= urlencode((string)$fechaFin) ?>"
                       class="btn btn-light btn-sm fw-semibold">
                        <i class="fas fa-file-excel me-1 text-success"></i> Exportar Excel
                    </a>
                <?php endif; ?>
            </div>

            <div class="section-body">

                <form method="get" class="row g-3 align-items-end mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Desde</label>
                        <input type="date" name="fecha_inicio" class="form-control" value="<?= h($fechaInicio) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Hasta</label>
                        <input type="date" name="fecha_fin" class="form-control" value="<?= h($fechaFin) ?>">
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-success flex-grow-1">
                            <i class="fas fa-filter me-1"></i> Filtrar
                        </button>
                        <a href="Pagos.php" class="btn btn-outline-secondary">
                            Quitar
                        </a>
                    </div>
                </form>

                <div class="ganancias-total-box mb-3">
                    <div class="ganancias-total-label">Ganancias en el período seleccionado</div>
                    <div class="ganancias-total-value">
                        ₲<?= number_format((float)$gananciasTotales, 0, ',', '.') ?>
                    </div>
                </div>

                <?php if (empty($paseosCompletados)): ?>
                    <div class="alert alert-info text-center mb-0">
                        No hay paseos completados en el período seleccionado.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-center">
                            <thead class="table-light">
                                <tr>
                                    <th>Mascota</th>
                                    <th>Fecha</th>
                                    <th>Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($paseosCompletados as $pp): ?>
                                    <tr>
                                        <td><?= h($pp['mascota_nombre'] ?? $pp['nombre_mascota'] ?? '-') ?></td>
                                        <td><?= !empty($pp['inicio']) ? date('d/m/Y H:i', strtotime((string)$pp['inicio'])) : '—' ?></td>
                                        <td class="fw-semibold text-success">
                                            ₲<?= number_format((float)($pp['precio_total'] ?? 0), 0, ',', '.') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- ✅ HISTORIAL DE PAGOS (section-card) -->
        <div class="section-card">
            <div class="section-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-list"></i>
                    <span>Historial de pagos</span>
                </div>
                <span class="pill"><i class="fas fa-receipt"></i> <?= (int)count($pagos) ?> registros</span>
            </div>

            <div class="section-body">
                <?php if (empty($pagos)): ?>
                    <div class="alert alert-light border text-center mb-0 text-muted">
                        No hay registros de pagos aún.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-center">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Paseo</th>
                                    <th>Monto</th>
                                    <th>Fecha</th>
                                    <th>Estado del pago</th>
                                    <th>Comprobante</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pagos as $row): ?>
                                    <?php
                                    $badgeColor = $row['estado'] === 'pagado'
                                        ? 'bg-success'
                                        : 'bg-warning text-dark';

                                    $tieneComprobante = ($row['estado'] === 'pagado') && !empty($row['pago_id']);
                                    ?>
                                    <tr>
                                        <td>#<?= (int)$row['id'] ?></td>
                                        <td><?= h($row['paseo']) ?></td>
                                        <td>₲<?= number_format((float)$row['monto'], 0, ',', '.') ?></td>
                                        <td><?= !empty($row['fecha']) ? date('d/m/Y', strtotime((string)$row['fecha'])) : '—' ?></td>
                                        <td>
                                            <span class="badge <?= $badgeColor ?>">
                                                <?= ucfirst((string)$row['estado']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($tieneComprobante): ?>
                                                <a href="<?= BASE_URL; ?>/public/api/pagos/comprobantePago.php?pago_id=<?= (int)$row['pago_id'] ?>"
                                                   class="btn btn-sm btn-outline-primary"
                                                   target="_blank" rel="noopener">
                                                    <i class="fas fa-file-pdf me-1"></i> Ver
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted small">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <footer class="mt-4 text-center text-muted small">
            © <?= date('Y'); ?> Jaguata — Panel de Paseador
        </footer>

    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
