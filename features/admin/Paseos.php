<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/PaseoController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Controllers\PaseoController;

AppConfig::init();

if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'admin') {
    header('Location: ' . BASE_URL . '/public/login.php?error=unauthorized');
    exit;
}

$baseFeatures = BASE_URL . '/features/admin';

/* Datos */
$paseoController = new PaseoController();
$paseos          = $paseoController->index() ?: [];

function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Paseos - Jaguata</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <style>
        html, body { overflow-x: hidden; width: 100%; }
        .table-responsive { overflow-x: auto; }
        th, td { white-space: nowrap; }

        /* chip de estado tipo "pro" */
        .estado-chip{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-width:110px;
            gap:.35rem;
        }
        .estado-dot{
            width:10px;height:10px;border-radius:999px;display:inline-block;
        }
        .estado-dot.pendiente{ background:#f0ad4e; }
        .estado-dot.confirmado{ background:#0d6efd; }
        .estado-dot.en_curso{ background:#0dcaf0; }
        .estado-dot.finalizado{ background:#198754; }
        .estado-dot.cancelado{ background:#dc3545; }
    </style>
</head>

<body>

    <?php include __DIR__ . '/../../src/Templates/SidebarAdmin.php'; ?>

    <main>
        <div class="container-fluid px-3 px-md-2">
            <div class="header-box header-paseos mb-3">
                <div>
                    <h1 class="fw-bold mb-1">Paseos registrados</h1>
                    <p class="mb-0">Listado general de paseos activos, pendientes y completados 🐾</p>
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
                        <input type="text" id="searchInput" class="form-control" placeholder="Paseador o cliente...">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Estado</label>
                        <select id="filterEstado" class="form-select">
                            <option value="">Todos</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="confirmado">Confirmado</option>
                            <option value="en_curso">En curso</option>
                            <option value="finalizado">Finalizado</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Fecha desde</label>
                        <input type="date" id="filterDesde" class="form-control">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Fecha hasta</label>
                        <input type="date" id="filterHasta" class="form-control">
                    </div>
                </form>
            </div>

            <!-- EXPORT -->
            <div class="export-buttons mb-3">
                <a class="btn btn-excel" href="<?= BASE_URL; ?>/public/api/paseos/exportarPaseos.php">
                    <i class="fas fa-file-excel"></i> Excel
                </a>
            </div>

            <div class="section-card mb-3">
                <div class="section-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-walking me-2"></i>
                        <span>Listado de paseos</span>
                    </div>
                    <span class="badge bg-secondary"><?= count($paseos); ?> registro(s)</span>
                </div>

                <div class="section-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="tablaPaseos">
                            <thead>
                                <tr>
                                    <th class="text-center">ID</th>
                                    <th>Paseador</th>
                                    <th>Cliente</th>
                                    <th>Fecha</th>
                                    <th>Duración</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if (empty($paseos)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            No se encontraron paseos registrados.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($paseos as $p):

                                        $estadoRaw = strtolower((string)($p['estado'] ?? 'solicitado'));

                                        // UI: solicitado -> pendiente, completo -> finalizado
                                        if ($estadoRaw === 'solicitado') {
                                            $estadoUi = 'pendiente';
                                        } elseif ($estadoRaw === 'completo') {
                                            $estadoUi = 'finalizado';
                                        } else {
                                            $estadoUi = $estadoRaw;
                                        }

                                        $estadoData  = $estadoUi;
                                        $estadoLabel = match ($estadoUi) {
                                            'pendiente'  => 'Pendiente',
                                            'confirmado' => 'Confirmado',
                                            'en_curso'   => 'En curso',
                                            'finalizado' => 'Finalizado',
                                            'cancelado'  => 'Cancelado',
                                            default      => ucfirst($estadoUi),
                                        };

                                        $badgeEstado = match ($estadoUi) {
                                            'pendiente'  => 'estado-pendiente',
                                            'confirmado' => 'estado-activo',     // o creás estado-confirmado si querés
                                            'en_curso'   => 'estado-en-curso',   // si no existe, cae por CSS; igual dejamos
                                            'finalizado' => 'estado-aprobado',
                                            'cancelado'  => 'estado-rechazado',
                                            default      => 'estado-pendiente'
                                        };

                                        $inicio    = $p['inicio'] ?? null;
                                        $fechaShow = '-';
                                        $fechaData = '';

                                        if ($inicio) {
                                            $ts        = strtotime((string)$inicio);
                                            if ($ts !== false) {
                                                $fechaShow = date('d/m/Y H:i', $ts);
                                                $fechaData = date('Y-m-d', $ts);
                                            }
                                        }
                                    ?>
                                        <tr class="fade-in-row"
                                            data-estado="<?= h($estadoData); ?>"
                                            data-fecha="<?= h($fechaData); ?>">

                                            <td class="text-center">
                                                <strong>#<?= h((string)($p['paseo_id'] ?? '')); ?></strong>
                                            </td>

                                            <td><?= h($p['nombre_paseador'] ?? '-'); ?></td>
                                            <td><?= h($p['nombre_dueno'] ?? '-'); ?></td>
                                            <td><?= h($fechaShow); ?></td>
                                            <td><?= (int)($p['duracion'] ?? 0); ?> min</td>

                                            <td>
                                                <span class="badge-estado <?= h($badgeEstado); ?> estado-chip">
                                                    <span class="estado-dot <?= h($estadoData); ?>"></span>
                                                    <?= h($estadoLabel); ?>
                                                </span>
                                            </td>

                                            <td class="text-center">
                                                <a href="VerPaseo.php?id=<?= urlencode((string)($p['paseo_id'] ?? '')); ?>" class="btn-ver">
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
                        Tip: combiná búsqueda, estado y rango de fechas para encontrar paseos específicos.
                    </p>
                </div>
            </div>

            <footer class="mt-3">
                <small>© <?= date('Y') ?> Jaguata — Panel de Administración</small>
            </footer>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.querySelector('.sidebar');
            const btnToggle = document.getElementById('btnSidebarToggle');
            if (btnToggle && sidebar) btnToggle.addEventListener('click', () => sidebar.classList.toggle('show'));
        });

        const search = document.getElementById('searchInput');
        const estado = document.getElementById('filterEstado');
        const desde = document.getElementById('filterDesde');
        const hasta = document.getElementById('filterHasta');
        const rows = document.querySelectorAll('#tablaPaseos tbody tr[data-estado]');

        function aplicarFiltros() {
            const texto = (search.value || '').toLowerCase();
            const estadoVal = (estado.value || '').toLowerCase();
            const fDesde = desde.value ? new Date(desde.value) : null;
            const fHasta = hasta.value ? new Date(hasta.value) : null;

            rows.forEach(row => {
                const rowEstado = (row.dataset.estado || '').toLowerCase();
                const rowTexto = row.textContent.toLowerCase();
                const fechaStr = row.dataset.fecha || '';
                const fechaRow = fechaStr ? new Date(fechaStr) : null;

                const coincideTexto = !texto || rowTexto.includes(texto);
                const coincideEstado = !estadoVal || rowEstado === estadoVal;

                let coincideFecha = true;
                if (fDesde && fechaRow) coincideFecha = coincideFecha && (fechaRow >= fDesde);
                if (fHasta && fechaRow) coincideFecha = coincideFecha && (fechaRow <= fHasta);

                row.style.display = (coincideTexto && coincideEstado && coincideFecha) ? '' : 'none';
            });
        }

        [search, estado, desde, hasta].forEach(el => {
            if (!el) return;
            el.addEventListener('input', aplicarFiltros);
            el.addEventListener('change', aplicarFiltros);
        });
    </script>

</body>
</html>
