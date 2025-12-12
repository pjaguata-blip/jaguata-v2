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

$rolMenu      = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures = BASE_URL . "/features/{$rolMenu}";

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
    if ($estado === 'PENDIENTE') $pendientes++;
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

        /* Cards estiradas */
        .dash-card {
            background: #fff;
            border-radius: 18px;
            padding: 18px 20px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, .06);
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 6px;
            text-align: center;
        }

        .dash-card-icon {
            font-size: 2rem;
            margin-bottom: 6px;
        }

        .small-muted {
            font-size: .85rem;
            color: #666;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .2rem .6rem;
            border-radius: 999px;
            font-size: .85rem;
        }
    </style>
</head>

<body>

    <?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>

    <button class="btn btn-outline-secondary d-md-none ms-2 mt-3" id="toggleSidebar">
        <i class="fas fa-bars"></i>
    </button>

    <main class="main-content">
        <div class="py-2">

            <!-- Header -->
            <div class="header-box header-pagos mb-4 d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="fw-bold mb-1">
                        <i class="fas fa-receipt me-2"></i>Mis Pagos
                    </h1>
                    <p class="mb-0">Visualizá y descargá tus comprobantes de pago 🧾</p>
                </div>
            </div>

            <!-- Métricas (ESTIRADAS) -->
            <div class="row g-3 mb-4 align-items-stretch">
                <div class="col-md-4">
                    <div class="dash-card">
                        <i class="fas fa-wallet dash-card-icon text-success"></i>
                        <h4 class="mb-0">₲<?= money($totalPagado) ?></h4>
                        <div class="small-muted">Total pagado (CONFIRMADO)</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dash-card">
                        <i class="fas fa-file-invoice dash-card-icon text-primary"></i>
                        <h4 class="mb-0"><?= count($pagos) ?></h4>
                        <div class="small-muted">Pagos registrados</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dash-card">
                        <i class="fas fa-hourglass-half dash-card-icon text-warning"></i>
                        <h4 class="mb-0"><?= (int)$pendientes ?></h4>
                        <div class="small-muted">Pagos pendientes</div>
                    </div>
                </div>
            </div>

            <!-- Tabla -->
            <div class="section-card">
                <div class="section-header">
                    <i class="fas fa-list me-2"></i>Comprobantes de pago
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

                                        // Fecha pago real -> created_at
                                        $fechaPago = !empty($p['created_at'])
                                            ? date('d/m/Y H:i', strtotime((string)$p['created_at']))
                                            : '—';

                                        // Mascotas (1 o 2)
                                        $m1 = trim((string)($p['mascota_nombre_1'] ?? ''));
                                        $m2 = trim((string)($p['mascota_nombre_2'] ?? ''));
                                        $txtMascotas = $m1 !== '' ? $m1 : '—';
                                        if ($m2 !== '') $txtMascotas .= ' + ' . $m2;

                                        $pagoId = (int)($p['id'] ?? 0);
                                        $tieneComprobante = !empty($p['comprobante']);
                                    ?>
                                        <tr>
                                            <td><?= h((string)$pagoId) ?></td>
                                            <td><?= h($fechaPago) ?></td>
                                            <td class="fw-bold text-success">₲<?= money((float)($p['monto'] ?? 0)) ?></td>
                                            <td><?= h((string)($p['metodo'] ?? '—')) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $badge ?>"><?= h($estado) ?></span>
                                            </td>
                                            <td>#<?= h((string)($p['paseo_id'] ?? '—')) ?></td>
                                            <td><?= h($txtMascotas) ?></td>
                                            <td><?= h((string)($p['banco'] ?? '—')) ?></td>
                                            <td><?= h((string)($p['referencia'] ?? '—')) ?></td>
                                            <td><?= h((string)($p['observacion'] ?? '—')) ?></td>
                                            <td>
                                                <?php if ($tieneComprobante && $pagoId > 0): ?>
                                                    <div class="d-flex justify-content-center gap-2">
                                                        <a class="btn btn-sm btn-outline-primary"
                                                            target="_blank"
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
        document.getElementById('toggleSidebar')?.addEventListener('click', function() {
            document.getElementById('sidebar')?.classList.toggle('sidebar-open');
        });
    </script>

</body>

</html>