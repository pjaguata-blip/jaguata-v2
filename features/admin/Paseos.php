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

// 🔒 Solo admin
if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'admin') {
    header('Location: ' . BASE_URL . '/public/login.php?error=unauthorized');
    exit;
}

$paseoController = new PaseoController();
$paseos          = $paseoController->index() ?: [];
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
</head>

<body>

    <?php include __DIR__ . '/../../src/Templates/SidebarAdmin.php'; ?>

    <main>
        <div class="container-fluid px-3 px-md-2">

            <!-- HEADER -->
            <div class="header-box header-paseos">
                <div>
                    <h1 class="fw-bold mb-1">Paseos registrados</h1>
                    <p class="mb-0">Listado general de paseos activos, pendientes y completados 🐾</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <!-- Toggle sidebar en móvil -->
                    <button class="btn btn-light d-lg-none" id="btnSidebarToggle" type="button">
                        <i class="fas fa-bars"></i>
                    </button>
                    
                </div>
                 <a href="<?= $baseFeatures; ?>/Dashboard.php" class="btn btn-outline-light">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
            </div>

            <!-- FILTROS -->
            <div class="filtros mb-3">
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
                <a class="btn btn-excel"
                    href="<?= BASE_URL; ?>/public/api/paseos/exportarPaseos.php">
                    <i class="fas fa-file-excel"></i> Excel
                </a>
            </div>

            <!-- CARD + TABLA -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Listado de paseos</h5>
                    <span class="badge bg-secondary"><?= count($paseos); ?> registro(s)</span>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="tablaPaseos">
                            <thead>
                                <tr>
                                    <th>ID</th>
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
                                        // Estado tal cual viene de la BD (solicitado, confirmado, en_curso, completo, cancelado)
                                        $estadoRaw = strtolower($p['estado'] ?? 'solicitado');

                                        // 🔄 Normalizar a cómo queremos mostrar/filtrar en la UI
                                        if ($estadoRaw === 'solicitado') {
                                            $estadoUi = 'pendiente';
                                        } elseif ($estadoRaw === 'completo') {
                                            $estadoUi = 'finalizado';
                                        } else {
                                            $estadoUi = $estadoRaw;
                                        }

                                        $estadoData  = $estadoUi;          // lo que usa data-estado para filtros
                                        $estadoLabel = ucfirst($estadoUi); // texto visible en el badge

                                        $badge  = match ($estadoUi) {
                                            'pendiente'   => 'bg-warning text-dark',
                                            'confirmado'  => 'bg-primary',
                                            'en_curso'    => 'bg-info text-dark',
                                            'finalizado'  => 'bg-success',
                                            'cancelado'   => 'bg-danger',
                                            default       => 'bg-secondary'
                                        };

                                        // Fecha formateada y data-fecha para filtros
                                        $inicio    = $p['inicio'] ?? null;
                                        $fechaShow = '-';
                                        $fechaData = '';

                                        if ($inicio) {
                                            $ts        = strtotime($inicio);
                                            $fechaShow = date('d/m/Y H:i', $ts);
                                            $fechaData = date('Y-m-d', $ts);
                                        }
                                    ?>
                                        <tr class="fade-in-row"
                                            data-estado="<?= htmlspecialchars($estadoData, ENT_QUOTES, 'UTF-8'); ?>"
                                            data-fecha="<?= htmlspecialchars($fechaData, ENT_QUOTES, 'UTF-8'); ?>">

                                            <!-- ID uniforme: # + negrita centrado -->
                                            <td class="text-center">
                                                <strong>#<?= htmlspecialchars((string)$p['paseo_id']) ?></strong>
                                            </td>

                                            <td><?= htmlspecialchars($p['nombre_paseador'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($p['nombre_dueno'] ?? '-') ?></td>
                                            <td><?= $fechaShow; ?></td>
                                            <td><?= (int)($p['duracion'] ?? 0) ?> min</td>
                                            <td><span class="badge <?= $badge ?>"><?= $estadoLabel ?></span></td>
                                            <td class="text-center">
                                                <a href="VerPaseo.php?id=<?= urlencode((string)$p['paseo_id']); ?>" class="btn-ver">
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
        </div><!-- /.container-fluid -->
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

        // === FILTROS ===
        const search = document.getElementById('searchInput');
        const estado = document.getElementById('filterEstado');
        const desde = document.getElementById('filterDesde');
        const hasta = document.getElementById('filterHasta');
        // Solo filas reales de paseos
        const rows = document.querySelectorAll('#tablaPaseos tbody tr[data-estado]');

        function aplicarFiltros() {
            const texto = (search.value || '').toLowerCase();
            const estadoVal = (estado.value || '').toLowerCase();
            const fDesde = desde.value ? new Date(desde.value) : null;
            const fHasta = hasta.value ? new Date(hasta.value) : null;

            rows.forEach(row => {
                const rowEstado = (row.dataset.estado || '').toLowerCase();
                const rowTexto = row.textContent.toLowerCase();
                const fechaStr = row.dataset.fecha || ''; // YYYY-MM-DD
                const fechaRow = fechaStr ? new Date(fechaStr) : null;

                const coincideTexto = !texto || rowTexto.includes(texto);
                const coincideEstado = !estadoVal || rowEstado === estadoVal;

                let coincideFecha = true;
                if (fDesde && fechaRow) {
                    coincideFecha = coincideFecha && (fechaRow >= fDesde);
                }
                if (fHasta && fechaRow) {
                    coincideFecha = coincideFecha && (fechaRow <= fHasta);
                }

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