<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';
require_once __DIR__ . '/../../src/Models/Suscripcion.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Helpers\Session;
use Jaguata\Models\Suscripcion;

AppConfig::init();

/* 🔒 Solo paseador */
$auth = new AuthController();
$auth->checkRole('paseador');

/* 🔒 BLOQUEO POR ESTADO (MUY IMPORTANTE) */
if (Session::getUsuarioEstado() !== 'aprobado') {
    Session::setError('Tu cuenta aún no fue aprobada.');
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}

function h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$paseoController = new PaseoController();
$paseadorId      = (int)(Session::getUsuarioId() ?? 0);
$paseos          = $paseadorId > 0 ? ($paseoController->indexForPaseador($paseadorId) ?: []) : [];

/* =========================
   ✅ SUSCRIPCIÓN PRO
   ========================= */
$tieneProActiva = false;
$subEstado = null;
$subFin    = null;

try {
    if ($paseadorId > 0) {
        $subModel = new Suscripcion();

        if (method_exists($subModel, 'marcarVencidas')) {
            $subModel->marcarVencidas();
        }

        $ultima = method_exists($subModel, 'getUltimaPorPaseador')
            ? $subModel->getUltimaPorPaseador($paseadorId)
            : null;

        if ($ultima) {
            $subEstado = strtolower(trim((string)($ultima['estado'] ?? '')));
            $subFin    = $ultima['fin'] ?? null;
            $tieneProActiva = ($subEstado === 'activa');
        }
    }
} catch (Throwable $e) {
    $tieneProActiva = false;
}

/* =========================
   FILTROS (PHP por estado)
   ========================= */
$estadosValidos = ['solicitado', 'confirmado', 'en_curso', 'completo', 'cancelado'];
$estadoFiltro   = strtolower(trim((string)($_GET['estado'] ?? '')));

if ($estadoFiltro !== '' && in_array($estadoFiltro, $estadosValidos, true)) {
    $paseos = array_values(array_filter(
        $paseos,
        fn($p) => strtolower((string)($p['estado'] ?? '')) === $estadoFiltro
    ));
}

$by = fn($s) => array_filter($paseos, fn($p) => strtolower(trim((string)($p['estado'] ?? ''))) === $s);

$totalPaseos       = count($paseos);
$paseosPendientes  = count($by('solicitado')) + count($by('confirmado'));
$paseosCompletados = count($by('completo'));
$paseosCancelados  = count($by('cancelado'));
$ingresosTotales   = array_reduce(
    $by('completo'),
    fn($a, $p) => $a + (float)($p['precio_total'] ?? 0),
    0
);

/* Para links base */
$rolMenu      = Session::getUsuarioRol() ?: 'paseador';
$baseFeatures = BASE_URL . "/features/{$rolMenu}";
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Paseos - Paseador | Jaguata</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <style>
        /* ✅ mini ajustes sin romper tu theme */
        html, body { overflow-x: hidden; width: 100%; }

        .acciones-wrap{
            display:flex;
            gap:.35rem;
            flex-wrap:wrap;
            justify-content:center;
            align-items:center;
        }

        @media (max-width: 992px){
            main{ margin-left:0 !important; }
        }
    </style>
</head>

<body>

    <?php include __DIR__ . '/../../src/Templates/SidebarPaseador.php'; ?>

    <main>
        <div class="container-fluid px-3 px-md-2">

            <!-- ✅ HEADER (igual estilo que Admin: botón toggle + volver al dashboard) -->
            <div class="header-box header-dashboard mb-3">
                <div>
                    <h1 class="fw-bold mb-1">
                        <i class="fas fa-walking me-2"></i> Mis paseos asignados
                    </h1>
                    <p class="mb-0">Revisá el historial de tus paseos, estados e ingresos.</p>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-light d-lg-none" id="btnSidebarToggle" type="button">
                        <i class="fas fa-bars"></i>
                    </button>

                    <a href="<?= $baseFeatures; ?>/Dashboard.php" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>
            </div>

            <!-- ✅ ALERTA SUSCRIPCIÓN -->
            <?php if (!$tieneProActiva): ?>
                <div class="alert alert-warning border d-flex align-items-start gap-3 mb-3">
                    <i class="fa-solid fa-triangle-exclamation mt-1"></i>
                    <div class="flex-grow-1">
                        <div class="fw-bold">Suscripción PRO requerida</div>
                        <div class="small mt-1">
                            Estado actual:
                            <span class="badge <?= $subEstado === 'pendiente' ? 'bg-warning text-dark' : 'bg-secondary' ?>">
                                <?= $subEstado ? strtoupper($subEstado) : 'SIN SUSCRIPCIÓN' ?>
                            </span>

                            <?php if ($subFin): ?>
                                <span class="text-muted ms-2">
                                    Vence: <?= date('d/m/Y H:i', strtotime((string)$subFin)); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="mt-2 d-flex flex-wrap gap-2">
                            <a href="<?= $baseFeatures; ?>/Suscripcion.php" class="btn btn-warning btn-sm fw-semibold">
                                <i class="fa-solid fa-crown me-1"></i> Activar / Renovar
                            </a>
                            <a href="<?= $baseFeatures; ?>/Suscripcion.php#subir-comprobante" class="btn btn-outline-dark btn-sm">
                                <i class="fa-solid fa-upload me-1"></i> Subir comprobante
                            </a>
                        </div>

                        <div class="small text-muted mt-2">
                            * Sin PRO activa no podrás confirmar/iniciar/completar paseos.
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ✅ Cards (stat-card del theme) -->
            <div class="row g-3 mb-3">
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="stat-card text-center">
                        <i class="fas fa-list text-success mb-1"></i>
                        <h4><?= (int)$totalPaseos ?></h4>
                        <p class="mb-0">Total de paseos</p>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="stat-card text-center">
                        <i class="fas fa-hourglass-half text-warning mb-1"></i>
                        <h4><?= (int)$paseosPendientes ?></h4>
                        <p class="mb-0">Pendientes / confirmados</p>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="stat-card text-center">
                        <i class="fas fa-check-circle text-primary mb-1"></i>
                        <h4><?= (int)$paseosCompletados ?></h4>
                        <p class="mb-0">Completados</p>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="stat-card text-center">
                        <i class="fas fa-wallet text-info mb-1"></i>
                        <h4>₲<?= number_format((float)$ingresosTotales, 0, ',', '.') ?></h4>
                        <p class="mb-0">Ingresos totales</p>
                    </div>
                </div>
            </div>

            <!-- ✅ FILTROS (mismo estilo filtros + JS client-side como admin) -->
            <div class="filtros mb-3">
                <form class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Buscar</label>
                        <input type="text" id="searchInput" class="form-control" placeholder="Mascota o dueño...">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Estado</label>
                        <select id="filterEstado" class="form-select">
                            <option value="">Todos</option>
                            <?php foreach ($estadosValidos as $v): ?>
                                <option value="<?= h($v) ?>" <?= $estadoFiltro === $v ? 'selected' : '' ?>>
                                    <?= h(ucfirst(str_replace('_', ' ', $v))) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Desde</label>
                        <input type="date" id="filterDesde" class="form-control">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Hasta</label>
                        <input type="date" id="filterHasta" class="form-control">
                    </div>
                </form>
            </div>

            <!-- ✅ EXPORT (igual a admin: export-buttons + btn-excel) -->
            <div class="export-buttons mb-3">
                <a class="btn btn-excel"
                   href="<?= BASE_URL; ?>/public/api/paseos/exportarPaseosPaseador.php">
                    <i class="fas fa-file-excel"></i> Excel
                </a>
            </div>

            <?php if (empty($paseos)): ?>
                <div class="card">
                    <div class="card-body text-center py-5 text-muted">
                        <i class="fas fa-dog fa-4x mb-3"></i>
                        <h5 class="mb-1">No tenés paseos asignados por el momento</h5>
                        <p class="mb-0 small">Cuando te asignen paseos, van a aparecer acá.</p>
                    </div>
                </div>
            <?php else: ?>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Lista de paseos</h5>
                        <span class="badge bg-secondary"><?= (int)count($paseos); ?> registro(s)</span>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="tablaPaseos">
                                <thead>
                                    <tr>
                                        <th>Mascota</th>
                                        <th>Dueño</th>
                                        <th>Fecha</th>
                                        <th>Duración</th>
                                        <th>Estado</th>
                                        <th>Pago</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>

                                <tbody>
                                <?php foreach ($paseos as $p):
                                    $estadoRaw = trim((string)($p['estado'] ?? ''));
                                    $estado    = strtolower($estadoRaw !== '' ? $estadoRaw : 'solicitado');
                                    $paseoId   = (int)($p['paseo_id'] ?? 0);

                                    $badgeClass = match ($estado) {
                                        'completo'   => 'bg-success',
                                        'cancelado'  => 'bg-danger',
                                        'en_curso'   => 'bg-info text-dark',
                                        'confirmado' => 'bg-primary',
                                        'solicitado' => 'bg-warning text-dark',
                                        default      => 'bg-secondary'
                                    };

                                    $esSolicitado = ($estado === 'solicitado');

                                    $nombre1 = (string)($p['mascota_nombre'] ?? '');
                                    $nombre2 = (string)($p['mascota2_nombre'] ?? '');
                                    $dueno   = (string)($p['dueno_nombre'] ?? '');

                                    $inicio = $p['inicio'] ?? null;
                                    $fechaShow = $inicio ? date('d/m/Y H:i', strtotime((string)$inicio)) : '—';
                                    $fechaData = $inicio ? date('Y-m-d', strtotime((string)$inicio)) : '';

                                    $estadoPago = strtolower(trim((string)($p['estado_pago'] ?? '')));
                                    $pagoLabel = match ($estadoPago) {
                                        'procesado', 'pagado' => 'Pagado',
                                        'pendiente' => 'Pendiente',
                                        'fallido'   => 'Fallido',
                                        default     => '—'
                                    };
                                    $pagoClass = match ($estadoPago) {
                                        'procesado', 'pagado' => 'text-success',
                                        'pendiente' => 'text-warning',
                                        'fallido'   => 'text-danger',
                                        default     => 'text-muted'
                                    };

                                    // Texto para buscar (igual patrón admin)
                                    $textoBusqueda = strtolower(
                                        $nombre1 . ' ' . $nombre2 . ' ' . $dueno . ' ' . $estado
                                    );
                                ?>
                                    <tr class="fade-in-row"
                                        data-texto="<?= h($textoBusqueda) ?>"
                                        data-estado="<?= h($estado) ?>"
                                        data-fecha="<?= h($fechaData) ?>">

                                        <td>
                                            <div class="fw-semibold"><?= h($nombre1) ?></div>
                                            <?php if ($nombre2 !== ''): ?>
                                                <div class="text-muted small">+ <?= h($nombre2) ?></div>
                                                <span class="badge bg-success mt-1">2 mascotas</span>
                                            <?php endif; ?>
                                        </td>

                                        <td><?= h($dueno) ?></td>

                                        <td><?= h($fechaShow) ?></td>

                                        <td><?= h($p['duracion'] ?? '') ?> min</td>

                                        <td>
                                            <span class="badge <?= $badgeClass ?>">
                                                <?= h(ucfirst(str_replace('_', ' ', $estado))) ?>
                                            </span>
                                        </td>

                                        <td><span class="<?= $pagoClass ?>"><?= h($pagoLabel) ?></span></td>

                                        <td class="text-center">
                                            <div class="acciones-wrap">
                                                <a href="VerPaseo.php?id=<?= $paseoId ?>"
                                                   class="btn-ver"
                                                   title="Ver detalle y ruta del paseo">
                                                    <i class="fas fa-route"></i> Ver
                                                </a>

                                                <?php if ($esSolicitado): ?>
                                                    <form action="AccionPaseo.php" method="post" class="d-inline">
                                                        <input type="hidden" name="id" value="<?= $paseoId ?>">
                                                        <input type="hidden" name="accion" value="confirmar">
                                                        <input type="hidden" name="redirect_to" value="MisPaseos.php">
                                                        <button type="submit"
                                                            class="btn btn-sm btn-outline-success <?= !$tieneProActiva ? 'disabled' : '' ?>"
                                                            <?= !$tieneProActiva ? 'disabled' : '' ?>
                                                            title="<?= !$tieneProActiva ? 'Requiere Suscripción PRO activa' : 'Confirmar solicitud' ?>">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>

                                                    <form action="AccionPaseo.php" method="post" class="d-inline">
                                                        <input type="hidden" name="id" value="<?= $paseoId ?>">
                                                        <input type="hidden" name="accion" value="cancelar">
                                                        <input type="hidden" name="redirect_to" value="MisPaseos.php">
                                                        <button type="submit"
                                                            class="btn btn-sm btn-outline-danger"
                                                            title="Cancelar solicitud"
                                                            onclick="return confirm('¿Cancelar este paseo?');">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>

                                                <?php elseif ($estado === 'confirmado'): ?>
                                                    <a href="AccionPaseo.php?id=<?= $paseoId ?>&accion=iniciar&redirect_to=MisPaseos.php"
                                                       class="btn btn-sm btn-outline-success <?= !$tieneProActiva ? 'disabled' : '' ?>"
                                                       <?= !$tieneProActiva ? 'aria-disabled="true" onclick="return false;"' : "onclick=\"return confirm('¿Iniciar este paseo?');\"" ?>
                                                       title="<?= !$tieneProActiva ? 'Requiere Suscripción PRO activa' : 'Iniciar paseo' ?>">
                                                        <i class="fas fa-play"></i>
                                                    </a>

                                                    <a href="AccionPaseo.php?id=<?= $paseoId ?>&accion=cancelar&redirect_to=MisPaseos.php"
                                                       class="btn btn-sm btn-outline-danger"
                                                       title="Cancelar paseo"
                                                       onclick="return confirm('¿Cancelar este paseo?');">
                                                        <i class="fas fa-times"></i>
                                                    </a>

                                                <?php elseif ($estado === 'en_curso'): ?>
                                                    <a href="AccionPaseo.php?id=<?= $paseoId ?>&accion=completar&redirect_to=MisPaseos.php"
                                                       class="btn btn-sm btn-outline-success <?= !$tieneProActiva ? 'disabled' : '' ?>"
                                                       <?= !$tieneProActiva ? 'aria-disabled="true" onclick="return false;"' : "onclick=\"return confirm('¿Marcar este paseo como completado?');\"" ?>
                                                       title="<?= !$tieneProActiva ? 'Requiere Suscripción PRO activa' : 'Completar paseo' ?>">
                                                        <i class="fas fa-check"></i>
                                                    </a>

                                                    <a href="AccionPaseo.php?id=<?= $paseoId ?>&accion=cancelar&redirect_to=MisPaseos.php"
                                                       class="btn btn-sm btn-outline-danger"
                                                       title="Cancelar paseo en curso"
                                                       onclick="return confirm('¿Cancelar este paseo?');">
                                                        <i class="fas fa-times"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <p class="text-muted small mt-2 mb-0">
                            Tip: combiná búsqueda + estado + rango de fechas para encontrar paseos específicos.
                        </p>
                    </div>
                </div>

            <?php endif; ?>

            <footer class="mt-3">
                <small>© <?= date('Y') ?> Jaguata — Panel Paseador</small>
            </footer>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // ✅ Toggle sidebar mobile (igual patrón admin)
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.querySelector('.sidebar');
            const btnToggle = document.getElementById('btnSidebarToggle');
            if (btnToggle && sidebar) btnToggle.addEventListener('click', () => sidebar.classList.toggle('show'));
        });

        // ✅ Filtros client-side (igual admin: texto + estado + fecha)
        const searchInput  = document.getElementById('searchInput');
        const filterEstado = document.getElementById('filterEstado');
        const filterDesde  = document.getElementById('filterDesde');
        const filterHasta  = document.getElementById('filterHasta');
        const rows         = document.querySelectorAll('#tablaPaseos tbody tr[data-texto]');

        function aplicarFiltros() {
            const txt = (searchInput?.value || '').toLowerCase();
            const est = (filterEstado?.value || '').toLowerCase();
            const fDesde = filterDesde?.value ? new Date(filterDesde.value) : null;
            const fHasta = filterHasta?.value ? new Date(filterHasta.value) : null;

            rows.forEach(row => {
                const rowTxt   = (row.dataset.texto || '').toLowerCase();
                const rowEst   = (row.dataset.estado || '').toLowerCase();
                const fechaStr = row.dataset.fecha || ''; // YYYY-MM-DD
                const fechaRow = fechaStr ? new Date(fechaStr) : null;

                const okTxt = !txt || rowTxt.includes(txt);
                const okEst = !est || rowEst === est;

                let okFecha = true;
                if (fDesde && fechaRow) okFecha = okFecha && (fechaRow >= fDesde);
                if (fHasta && fechaRow) okFecha = okFecha && (fechaRow <= fHasta);

                row.style.display = (okTxt && okEst && okFecha) ? '' : 'none';
            });
        }

        [searchInput, filterEstado, filterDesde, filterHasta].forEach(el => {
            if (!el) return;
            el.addEventListener('input', aplicarFiltros);
            el.addEventListener('change', aplicarFiltros);
        });

        // ✅ Si querés mantener filtro por GET (opcional): al cambiar estado, actualiza URL
        function aplicarFiltroEstadoURL() {
            const estado = filterEstado?.value || '';
            const url = new URL(window.location.href);
            if (estado) url.searchParams.set('estado', estado);
            else url.searchParams.delete('estado');
            window.location.href = url.toString();
        }
        // Si preferís URL, descomentá:
        // filterEstado?.addEventListener('change', aplicarFiltroEstadoURL);
    </script>

</body>
</html>
