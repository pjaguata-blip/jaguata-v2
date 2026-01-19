<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PagoController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PagoController;
use Jaguata\Helpers\Session;

AppConfig::init();

/* 🔒 Auth dueño */
(new AuthController())->checkRole('dueno');

/* Helpers */
function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function money(float $v): string
{
    return number_format($v, 0, ',', '.');
}

/* Contexto */
$duenoId = (int)(Session::getUsuarioId() ?? 0);
if ($duenoId <= 0) {
    http_response_code(401);
    exit('No autorizado');
}

$rolMenu       = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures  = BASE_URL . "/features/{$rolMenu}";
$usuarioNombre = h(Session::getUsuarioNombre() ?? 'Dueño');

/* Datos */
$pagoCtrl = new PagoController();
$pagos    = $pagoCtrl->listarPagosDueno($duenoId) ?? [];

/* Métricas */
$totalPagado = 0.0;
$pendientes  = 0;

foreach ($pagos as $p) {
    $estado = strtoupper(trim((string)($p['estado'] ?? 'PENDIENTE')));

    if ($estado === 'CONFIRMADO') {
        $totalPagado += (float)($p['monto'] ?? 0);
    }
    if ($estado === 'PENDIENTE') {
        $pendientes++;
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Mis Pagos - Jaguata</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <style>
        html, body { height: 100%; overflow-x: hidden; }
        body { background: var(--gris-fondo, #f4f6f9); }

        /* ✅ Igual a dashboards */
        main.main-content{
            margin-left: 260px;
            min-height: 100vh;
            padding: 24px;
            width: calc(100% - 260px);
        }
        @media (max-width: 768px){
            main.main-content{
                margin-left: 0 !important;
                width: 100% !important;
                margin-top: 0 !important;
                padding: calc(16px + var(--topbar-h)) 16px 16px !important;
            }
        }

        /* Botones comprobante compactos */
        .btns-comp{
            display:flex;
            gap:.35rem;
            justify-content:center;
            flex-wrap:wrap;
        }

        /* Inputs filtros */
        .filtros .form-control, .filtros .form-select{
            border-radius: 12px;
        }
    </style>
</head>

<body class="page-mis-pagos">

<?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>

<main class="main-content">
    <div class="py-2">

        <!-- Header -->
        <div class="header-box header-pagos mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h1 class="fw-bold mb-1">
                    <i class="fas fa-receipt me-2"></i> Mis Pagos
                </h1>
                <p class="mb-0">
                    Hola, <?= $usuarioNombre; ?>. Visualizá y descargá tus comprobantes 🧾
                </p>
            </div>

            <div class="d-flex gap-2">
                <a href="<?= $baseFeatures; ?>/Dashboard.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
            </div>
        </div>

        <!-- Métricas (igual estilo stat-card) -->
        <div class="row g-3 mb-3 align-items-stretch">
            <div class="col-12 col-md-4">
                <div class="stat-card text-center h-100">
                    <i class="fas fa-wallet text-success mb-1"></i>
                    <h4>₲<?= money($totalPagado) ?></h4>
                    <p class="mb-0">Total pagado (CONFIRMADO)</p>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="stat-card text-center h-100">
                    <i class="fas fa-file-invoice text-primary mb-1"></i>
                    <h4><?= (int)count($pagos) ?></h4>
                    <p class="mb-0">Pagos registrados</p>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="stat-card text-center h-100">
                    <i class="fas fa-hourglass-half text-warning mb-1"></i>
                    <h4><?= (int)$pendientes ?></h4>
                    <p class="mb-0">Pagos pendientes</p>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filtros mb-3">
            <form class="row g-3 align-items-end" onsubmit="return false;">
                <div class="col-md-5">
                    <label class="form-label fw-semibold">Buscar</label>
                    <input type="text" id="searchInput" class="form-control" placeholder="Buscar por banco, referencia, mascota, paseo...">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Estado</label>
                    <select id="filterEstado" class="form-select">
                        <option value="">Todos</option>
                        <option value="CONFIRMADO">CONFIRMADO</option>
                        <option value="PENDIENTE">PENDIENTE</option>
                        <option value="RECHAZADO">RECHAZADO</option>
                        <option value="CANCELADO">CANCELADO</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Tip</label>
                    <div class="text-muted small">
                        Escribí en “Buscar” y/o filtrá por estado para encontrar rápido.
                    </div>
                </div>
            </form>
        </div>

        <!-- Tabla -->
        <div class="section-card">
            <div class="section-header">
                <i class="fas fa-list me-2"></i> Comprobantes de pago
            </div>

            <div class="section-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle text-center mb-0" id="tablaPagos">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Fecha pago</th>
                                <th>Monto</th>
                                <th>Método</th>
                                <th>Estado</th>
                                <th>Paseo</th>
                                <th>Mascotas</th>
                                <th>Banco</th>
                                <th>Referencia</th>
                                <th>Obs.</th>
                                <th>Comprobante</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (empty($pagos)): ?>
                                <tr>
                                    <td colspan="11" class="text-muted py-4">No tenés pagos registrados.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pagos as $p):
                                    $estado = strtoupper(trim((string)($p['estado'] ?? 'PENDIENTE')));
                                    $badge = [
                                        'CONFIRMADO' => 'success',
                                        'PENDIENTE'  => 'warning',
                                        'RECHAZADO'  => 'danger',
                                        'CANCELADO'  => 'danger'
                                    ][$estado] ?? 'secondary';

                                    $fechaPago = !empty($p['created_at'])
                                        ? date('d/m/Y H:i', strtotime((string)$p['created_at']))
                                        : '—';

                                    $m1 = trim((string)($p['mascota_nombre_1'] ?? ''));
                                    $m2 = trim((string)($p['mascota_nombre_2'] ?? ''));
                                    $txtMascotas = $m1 !== '' ? $m1 : '—';
                                    if ($m2 !== '') $txtMascotas .= ' + ' . $m2;

                                    $pagoId = (int)($p['id'] ?? 0);
                                    $tieneComprobante = !empty($p['comprobante']);

                                    $banco = (string)($p['banco'] ?? '—');
                                    $ref   = (string)($p['referencia'] ?? '—');
                                    $obs   = (string)($p['observacion'] ?? '—');

                                    $textoBusqueda = strtolower(
                                        $estado . ' ' .
                                        ($p['metodo'] ?? '') . ' ' .
                                        $banco . ' ' . $ref . ' ' . $obs . ' ' .
                                        ($p['paseo_id'] ?? '') . ' ' .
                                        $txtMascotas
                                    );
                                ?>
                                    <tr data-texto="<?= h($textoBusqueda) ?>" data-estado="<?= h($estado) ?>">
                                        <td><?= h((string)$pagoId) ?></td>
                                        <td><?= h($fechaPago) ?></td>
                                        <td class="fw-bold text-success">₲<?= money((float)($p['monto'] ?? 0)) ?></td>
                                        <td><?= h((string)($p['metodo'] ?? '—')) ?></td>
                                        <td><span class="badge bg-<?= $badge ?>"><?= h($estado) ?></span></td>
                                        <td>#<?= h((string)($p['paseo_id'] ?? '—')) ?></td>
                                        <td><?= h($txtMascotas) ?></td>
                                        <td><?= h($banco) ?></td>
                                        <td><?= h($ref) ?></td>
                                        <td><?= h($obs) ?></td>

                                        <td>
                                            <?php if ($tieneComprobante && $pagoId > 0): ?>
                                                <div class="btns-comp">
                                                    <a class="btn btn-sm btn-outline-primary"
                                                       target="_blank" rel="noopener"
                                                       href="<?= BASE_URL; ?>/public/api/pagos/comprobantePago.php?pago_id=<?= (int)$pagoId ?>">
                                                        <i class="fas fa-eye"></i> Ver
                                                    </a>

                                                    <a class="btn btn-sm btn-outline-secondary"
                                                       download
                                                       href="<?= BASE_URL; ?>/public/api/pagos/comprobantePago.php?pago_id=<?= (int)$pagoId ?>">
                                                        <i class="fas fa-download"></i>
                                                    </a>

                                                    <a class="btn btn-sm btn-success"
                                                       href="<?= $baseFeatures; ?>/comprobante_pago.php?pago_id=<?= (int)$pagoId ?>"
                                                       title="Ver comprobante bonito">
                                                        <i class="fas fa-receipt"></i>
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>

                        <?php if (!empty($pagos)): ?>
                            <tfoot>
                                <tr class="table-light fw-bold">
                                    <td colspan="2">TOTAL (CONFIRMADO)</td>
                                    <td class="text-success">₲<?= money($totalPagado) ?></td>
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
    // ✅ Filtro client-side (igual patrón admin/paseador)
    const searchInput  = document.getElementById('searchInput');
    const filterEstado = document.getElementById('filterEstado');
    const rows         = document.querySelectorAll('#tablaPagos tbody tr[data-texto]');

    function aplicarFiltros(){
        const txt = (searchInput?.value || '').toLowerCase().trim();
        const est = (filterEstado?.value || '').toUpperCase().trim();

        rows.forEach(row => {
            const rowTxt = (row.dataset.texto || '').toLowerCase();
            const rowEst = (row.dataset.estado || '').toUpperCase();

            const okTxt = !txt || rowTxt.includes(txt);
            const okEst = !est || rowEst === est;

            row.style.display = (okTxt && okEst) ? '' : 'none';
        });
    }

    [searchInput, filterEstado].forEach(el => {
        if (!el) return;
        el.addEventListener('input', aplicarFiltros);
        el.addEventListener('change', aplicarFiltros);
    });
</script>

</body>
</html>
