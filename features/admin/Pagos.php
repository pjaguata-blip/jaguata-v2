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

// 🔒 Solo admin
if (!Session::isLoggedIn()) {
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}
$auth = new AuthController();
$auth->checkRole('admin');

// Cargamos TODOS los pagos
$pagoController = new PagoController();
$pagos = $pagoController->index() ?: [];
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
</head>

<body>

    <?php include __DIR__ . '/../../src/Templates/SidebarAdmin.php'; ?>

    <main>
        <div class="container-fluid px-3 px-md-4">

            <!-- Encabezado -->
            <div class="header-box header-paseos">
                <div>
                    <h1 class="fw-bold mb-1">Gestión de Pagos</h1>
                    <p class="mb-0">Pagos pendientes, pagados y detalles individuales 💸</p>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <!-- Botón toggle sidebar SOLO en móvil -->
                    <button class="btn btn-light d-lg-none" id="btnSidebarToggle" type="button">
                        <i class="fas fa-bars"></i>
                    </button>
                    <i class="fas fa-wallet fa-3x opacity-75 d-none d-lg-block"></i>
                </div>
            </div>

            <!-- Filtros -->
            <div class="filtros mb-3">
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

            <!-- Botón export -->
            <div class="export-buttons">
                <a class="btn btn-excel"
                    href="<?= BASE_URL; ?>/public/api/pagos/exportPagos.php">
                    <i class="fas fa-file-excel"></i> Excel
                </a>
            </div>

            <!-- Tabla principal -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Listado de pagos</h5>
                    <span class="badge bg-secondary"><?= count($pagos); ?> registro(s)</span>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="tablaPagos">
                            <thead>
                                <tr>
                                    <th>#</th>
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
                                <?php foreach ($pagos as $pago): ?>
                                    <?php
                                    $id          = (int)($pago['id'] ?? 0);
                                    $usuario     = $pago['usuario'] ?? '-';
                                    $monto       = (float)($pago['monto'] ?? 0);
                                    $metodo      = $pago['metodo'] ?? '-';
                                    $banco       = $pago['banco'] ?? '';
                                    $cuenta      = $pago['cuenta'] ?? '';
                                    $fecha       = $pago['fecha'] ?? '';
                                    $fechaCorta  = substr($fecha, 0, 10); // YYYY-MM-DD
                                    $estadoRaw   = strtolower((string)($pago['estado'] ?? ''));
                                    $estadoPago  = $estadoRaw ?: 'nd';
                                    $estadoLabel = ucfirst($estadoPago);

                                    $badgeClass = match ($estadoPago) {
                                        'pendiente' => 'bg-warning text-dark',
                                        'pagado'    => 'bg-success',
                                        'cancelado' => 'bg-danger',
                                        default     => 'bg-secondary'
                                    };
                                    ?>
                                    <tr data-estado="<?= htmlspecialchars($estadoPago); ?>"
                                        data-metodo="<?= htmlspecialchars(strtolower($metodo)); ?>"
                                        data-fecha="<?= htmlspecialchars($fechaCorta); ?>">
                                        <td><?= $id; ?></td>
                                        <td><?= htmlspecialchars($usuario); ?></td>
                                        <td><?= number_format($monto, 0, ',', '.'); ?> Gs</td>
                                        <td><?= htmlspecialchars($metodo); ?></td>
                                        <td><?= htmlspecialchars(trim("$banco $cuenta") ?: '-'); ?></td>
                                        <td><?= htmlspecialchars($fecha); ?></td>
                                        <td>
                                            <span class="badge <?= $badgeClass ?>"><?= $estadoLabel ?></span>
                                        </td>


                                        <td class="text-center">
                                            <a href="DetallePago.php?id=<?= $id; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i> Ver
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                            </tbody>
                        </table>
                    </div>

                    <p class="text-muted small mt-2">
                        Tip: combiná la búsqueda con los filtros de estado, método y fechas para encontrar pagos específicos.
                    </p>
                </div>
            </div>

            <footer>
                <small>© <?= date('Y'); ?> Jaguata — Panel de Administración</small>
            </footer>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // === Toggle sidebar en mobile ===
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.querySelector('.sidebar');
            const btnToggle = document.getElementById('btnSidebarToggle');

            if (btnToggle && sidebar) {
                btnToggle.addEventListener('click', () => {
                    sidebar.classList.toggle('show');
                });
            }
        });

        // === Filtros pagos ===
        const searchInput = document.getElementById('searchInput');
        const filterEstado = document.getElementById('filterEstado');
        const filterMetodo = document.getElementById('filterMetodo');
        const filterDesde = document.getElementById('filterDesde');
        const filterHasta = document.getElementById('filterHasta');
        const rows = document.querySelectorAll('#tablaPagos tbody tr');

        function aplicarFiltros() {
            const texto = (searchInput.value || '').toLowerCase();
            const estadoVal = (filterEstado.value || '').toLowerCase();
            const metodoVal = (filterMetodo.value || '').toLowerCase();
            const fDesde = filterDesde.value ? new Date(filterDesde.value) : null;
            const fHasta = filterHasta.value ? new Date(filterHasta.value) : null;

            rows.forEach(row => {
                if (!row.dataset) return;

                const rowTexto = row.textContent.toLowerCase();
                const rowEstado = (row.dataset.estado || '').toLowerCase();
                const rowMetodo = (row.dataset.metodo || '').toLowerCase();
                const fechaStr = row.dataset.fecha || '';
                const fechaRow = fechaStr ? new Date(fechaStr) : null;

                const coincideTexto = rowTexto.includes(texto);
                const coincideEstado = !estadoVal || rowEstado === estadoVal;
                const coincideMetodo = !metodoVal || rowMetodo === metodoVal;

                let coincideFecha = true;
                if (fDesde && fechaRow) coincideFecha = coincideFecha && (fechaRow >= fDesde);
                if (fHasta && fechaRow) coincideFecha = coincideFecha && (fechaRow <= fHasta);

                row.style.display = (coincideTexto && coincideEstado && coincideMetodo && coincideFecha) ? '' : 'none';
            });
        }

        [searchInput, filterEstado, filterMetodo, filterDesde, filterHasta].forEach(el => {
            el.addEventListener('input', aplicarFiltros);
            el.addEventListener('change', aplicarFiltros);
        });
    </script>
</body>

</html>