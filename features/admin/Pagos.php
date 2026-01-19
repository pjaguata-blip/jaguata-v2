<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/AuthController.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/PagoController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PagoController;

AppConfig::init();

/* 🔒 Solo admin */
if (!Session::isLoggedIn()) {
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}
$auth = new AuthController();
$auth->checkRole('admin');

/* ✅ baseFeatures para botón volver */
$baseFeatures = BASE_URL . '/features/admin';

/* Datos */
$pagoController = new PagoController();
$pagos          = $pagoController->index() ?: [];

function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Pagos - Jaguata</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <style>
        /* ✅ evita scroll horizontal */
        html, body { overflow-x: hidden; width: 100%; }
        .table-responsive { overflow-x: auto; }
        th, td { white-space: nowrap; }

        /* ✅ chip de estado pro (igual Mascotas) */
        .estado-chip{
            display:inline-flex;
            align-items:center;
            gap:.35rem;
            justify-content:center;
            min-width:120px;
        }
        .estado-dot{
            width:10px;height:10px;border-radius:999px;display:inline-block;
        }
        .estado-dot.pendiente{ background:#f0ad4e; }
        .estado-dot.pagado{ background:#198754; }
        .estado-dot.cancelado{ background:#dc3545; }
    </style>
</head>

<body>

    <?php include __DIR__ . '/../../src/Templates/SidebarAdmin.php'; ?>

    <main>
        <div class="container-fluid px-3 px-md-2">

            <!-- ✅ HEADER (igual estilo) -->
            <div class="header-box header-paseos mb-3">
                <div>
                    <h1 class="fw-bold mb-1">Gestión de Pagos</h1>
                    <p class="mb-0">Pagos pendientes, pagados y detalles individuales 💸</p>
                </div>

                <div class="d-flex align-items-center gap-2">
                   
                </div>

                <a href="<?= $baseFeatures; ?>/Dashboard.php" class="btn btn-outline-light">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
            </div>

            <!-- FILTROS -->
            <div class="filtros mb-4">
                <form class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Buscar</label>
                        <input type="text" id="searchInput" class="form-control"
                            placeholder="Usuario, banco, cuenta...">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Estado</label>
                        <select id="filterEstado" class="form-select">
                            <option value="">Todos</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="pagado">Pagado</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Método de pago</label>
                        <select id="filterMetodo" class="form-select">
                            <option value="">Todos</option>
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="tarjeta">Tarjeta</option>
                            <option value="otros">Otros</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Rango de fecha</label>
                        <div class="d-flex gap-2">
                            <input type="date" id="filterDesde" class="form-control">
                            <input type="date" id="filterHasta" class="form-control">
                        </div>
                    </div>
                </form>
            </div>

            <!-- EXPORT -->
            <div class="export-buttons mb-3">
                <a class="btn btn-excel" href="<?= BASE_URL; ?>/public/api/pagos/exportPagos.php">
                    <i class="fas fa-file-excel"></i> Excel
                </a>
            </div>

            <!-- ✅ SECTION CARD (igual las otras) -->
            <div class="section-card mb-3">
                <div class="section-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-money-bill-wave me-2"></i>
                        <span>Listado de pagos</span>
                    </div>
                    <span class="badge bg-secondary"><?= count($pagos); ?> registro(s)</span>
                </div>

                <div class="section-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="tablaPagos">
                            <thead>
                                <tr>
                                    <th class="text-center">ID</th>
                                    <th>Usuario</th>
                                    <th>Monto</th>
                                    <th>Método</th>
                                    <th>Banco / Cuenta</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if (empty($pagos)): ?>
                                    <tr>
                                        <td colspan="8" class="text-muted text-center py-3">
                                            No se encontraron pagos registrados.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($pagos as $pago): ?>
                                        <?php
                                        $id         = (int)($pago['id'] ?? 0);
                                        $usuario    = (string)($pago['usuario'] ?? '-');
                                        $monto      = (float)($pago['monto'] ?? 0);
                                        $metodo     = (string)($pago['metodo'] ?? '-');
                                        $banco      = (string)($pago['banco'] ?? '');
                                        $cuenta     = (string)($pago['cuenta'] ?? '');
                                        $fecha      = (string)($pago['fecha'] ?? '');
                                        $fechaCorta = $fecha ? substr($fecha, 0, 10) : ''; // YYYY-MM-DD

                                        $estadoPago = strtolower(trim((string)($pago['estado'] ?? '')));
                                        if (!in_array($estadoPago, ['pendiente', 'pagado', 'cancelado'], true)) {
                                            $estadoPago = 'pendiente';
                                        }

                                        $estadoLabel = match ($estadoPago) {
                                            'pendiente' => 'Pendiente',
                                            'pagado'    => 'Pagado',
                                            'cancelado' => 'Cancelado',
                                            default     => ucfirst($estadoPago),
                                        };

                                        // ✅ badge-estado (como tu sistema)
                                        $badgeEstado = match ($estadoPago) {
                                            'pendiente' => 'estado-pendiente',
                                            'pagado'    => 'estado-aprobado',   // o estado-activo si preferís
                                            'cancelado' => 'estado-rechazado',
                                            default     => 'estado-pendiente'
                                        };

                                        $bancoCuenta = trim($banco . ' ' . $cuenta);
                                        ?>
                                        <tr class="fade-in-row"
                                            data-estado="<?= h($estadoPago); ?>"
                                            data-metodo="<?= h(strtolower($metodo)); ?>"
                                            data-fecha="<?= h($fechaCorta); ?>">

                                            <td class="text-center">
                                                <strong>#<?= (int)$id; ?></strong>
                                            </td>

                                            <td><?= h($usuario); ?></td>
                                            <td><?= number_format($monto, 0, ',', '.'); ?> Gs</td>
                                            <td><?= h($metodo); ?></td>
                                            <td><?= h($bancoCuenta !== '' ? $bancoCuenta : '-'); ?></td>
                                            <td><?= h($fecha !== '' ? $fecha : '-'); ?></td>

                                            <td>
                                                <span class="badge-estado <?= h($badgeEstado); ?> estado-chip">
                                                    <span class="estado-dot <?= h($estadoPago); ?>"></span>
                                                    <?= h($estadoLabel); ?>
                                                </span>
                                            </td>

                                            <td class="text-center">
                                                <a href="DetallePago.php?id=<?= (int)$id; ?>" class="btn-ver">
                                                    <i class="fas fa-eye"></i> Ver
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <p class="text-muted small mt-2 mb-0">
                        Tip: combiná búsqueda con filtros de estado, método y fechas para encontrar pagos específicos.
                    </p>
                </div>
            </div>

            <footer class="mt-3">
                <small>© <?= date('Y'); ?> Jaguata — Panel de Administración</small>
            </footer>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // ✅ Toggle sidebar en mobile (igual)
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.querySelector('.sidebar');
            const btnToggle = document.getElementById('btnSidebarToggle');
            if (btnToggle && sidebar) btnToggle.addEventListener('click', () => sidebar.classList.toggle('show'));
        });

        // ✅ Filtros pagos
        const searchInput  = document.getElementById('searchInput');
        const filterEstado = document.getElementById('filterEstado');
        const filterMetodo = document.getElementById('filterMetodo');
        const filterDesde  = document.getElementById('filterDesde');
        const filterHasta  = document.getElementById('filterHasta');
        const rows         = document.querySelectorAll('#tablaPagos tbody tr[data-estado]');

        function aplicarFiltros() {
            const texto     = (searchInput.value || '').toLowerCase();
            const estadoVal = (filterEstado.value || '').toLowerCase();
            const metodoVal = (filterMetodo.value || '').toLowerCase();
            const fDesde    = filterDesde.value ? new Date(filterDesde.value) : null;
            const fHasta    = filterHasta.value ? new Date(filterHasta.value) : null;

            rows.forEach(row => {
                const rowTexto  = row.textContent.toLowerCase();
                const rowEstado = (row.dataset.estado || '').toLowerCase();
                const rowMetodo = (row.dataset.metodo || '').toLowerCase();
                const fechaStr  = row.dataset.fecha || '';
                const fechaRow  = fechaStr ? new Date(fechaStr) : null;

                const coincideTexto  = !texto || rowTexto.includes(texto);
                const coincideEstado = !estadoVal || rowEstado === estadoVal;
                const coincideMetodo = !metodoVal || rowMetodo === metodoVal;

                let coincideFecha = true;
                if (fDesde && fechaRow) coincideFecha = coincideFecha && (fechaRow >= fDesde);
                if (fHasta && fechaRow) coincideFecha = coincideFecha && (fechaRow <= fHasta);

                row.style.display = (coincideTexto && coincideEstado && coincideMetodo && coincideFecha) ? '' : 'none';
            });
        }

        [searchInput, filterEstado, filterMetodo, filterDesde, filterHasta].forEach(el => {
            if (!el) return;
            el.addEventListener('input', aplicarFiltros);
            el.addEventListener('change', aplicarFiltros);
        });
    </script>

</body>
</html>
