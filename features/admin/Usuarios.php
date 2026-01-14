<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/UsuarioController.php';
require_once dirname(__DIR__, 2) . '/src/Models/Calificacion.php';
require_once dirname(__DIR__, 2) . '/src/Models/Suscripcion.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Controllers\UsuarioController;
use Jaguata\Models\Calificacion;
use Jaguata\Models\Suscripcion;

AppConfig::init();

/* 🔒 Seguridad */
if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'admin') {
    header('Location: ' . BASE_URL . '/public/login.php?error=unauthorized');
    exit;
}

function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/* ✅ baseFeatures */
$baseFeatures = BASE_URL . '/features/admin';

/* Datos */
$usuarioController = new UsuarioController();
$usuarios          = $usuarioController->index() ?: [];
$calificacionModel = new Calificacion();

/* ✅ SUSCRIPCIONES (1 query) */
$subModel = new Suscripcion();

$paseadorIds = [];
foreach ($usuarios as $u) {
    if (strtolower((string)($u['rol'] ?? '')) === 'paseador') {
        $paseadorIds[] = (int)($u['usu_id'] ?? 0);
    }
}

$subsMap = [];
try {
    if (!empty($paseadorIds) && method_exists($subModel, 'getEstadosActualesPorPaseadores')) {
        $subsMap = $subModel->getEstadosActualesPorPaseadores($paseadorIds);
    } else {
        foreach ($paseadorIds as $pid) {
            $ultima = $subModel->getUltimaPorPaseador((int)$pid);
            if ($ultima) {
                $subsMap[(int)$pid] = [
                    'estado' => strtolower((string)($ultima['estado'] ?? '')),
                    'inicio' => $ultima['inicio'] ?? null,
                    'fin'    => $ultima['fin'] ?? null,
                    'monto'  => (int)($ultima['monto'] ?? 0),
                    'plan'   => $ultima['plan'] ?? null,
                ];
            }
        }
    }
} catch (Throwable $e) {
    $subsMap = [];
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios - Jaguata</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <style>
        /* ✅ evita scroll horizontal */
        html, body { overflow-x: hidden; width: 100%; }
        .table-responsive { overflow-x: auto; }
        th, td { white-space: nowrap; }

        /* Badge Suscripción */
        .sub-mini { font-size: .85rem; }
        .sub-meta { font-size: .78rem; color:#6b7b83; }
    </style>
</head>

<body>

<?php include __DIR__ . '/../../src/Templates/SidebarAdmin.php'; ?>

<main>
    <div class="container-fluid px-3 px-md-2">

        <!-- ✅ HEADER (igual que Notificaciones) -->
        <div class="header-box header-usuarios mb-3">
            <div>
                <h1 class="fw-bold mb-1">Gestión de Usuarios</h1>
                <p class="mb-0">Administrá usuarios, roles, estados y suscripciones 👥</p>
            </div>

            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-light d-lg-none" id="btnSidebarToggle" type="button">
                    <i class="fas fa-bars"></i>
                </button>
            </div>

            <a href="<?= $baseFeatures; ?>/Dashboard.php" class="btn btn-outline-light">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
        </div>

        <!-- FILTROS -->
        <div class="filtros mb-4">
            <form class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Buscar</label>
                    <input id="searchInput" type="text" class="form-control" placeholder="Nombre o correo...">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Rol</label>
                    <select id="filterRol" class="form-select">
                        <option value="">Todos</option>
                        <option value="admin">Administrador</option>
                        <option value="paseador">Paseador</option>
                        <option value="dueno">Dueño</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Estado</label>
                    <select id="filterEstado" class="form-select">
                        <option value="">Todos</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="aprobado">Aprobado</option>
                        <option value="rechazado">Rechazado</option>
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                        <option value="suspendido">Suspendido</option>
                    </select>
                </div>
            </form>
        </div>

        <!-- EXPORT -->
        <div class="export-buttons mb-3">
            <a class="btn btn-excel" href="<?= BASE_URL; ?>/public/api/usuarios/exportUsuarios.php">
                <i class="fas fa-file-excel"></i> Excel
            </a>
        </div>

        <!-- TABLA -->
        <div class="section-card mb-3">
            <div class="section-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <i class="fas fa-users me-2"></i>
                    <span>Listado de usuarios</span>
                </div>
                <span class="badge bg-secondary"><?= count($usuarios); ?> registro(s)</span>
            </div>

            <div class="section-body">
                <div class="table-responsive">
                    <table class="table text-center align-middle table-hover" id="tablaUsuarios">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Reputación</th>
                                <th>Suscripción</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (empty($usuarios)): ?>
                                <tr>
                                    <td colspan="8" class="text-muted py-3">No se encontraron usuarios registrados.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($usuarios as $u): ?>
                                    <?php
                                    $estadoRaw   = $u['estado'] ?? 'pendiente';
                                    $estado      = strtolower(trim((string)$estadoRaw));
                                    $estadoTexto = ucfirst($estado);

                                    $badgeClass = match ($estado) {
                                        'aprobado'   => 'estado-aprobado',
                                        'activo'     => 'estado-activo',
                                        'pendiente'  => 'estado-pendiente',
                                        'rechazado'  => 'estado-rechazado',
                                        'suspendido' => 'estado-suspendido',
                                        'inactivo'   => 'estado-inactivo',
                                        'cancelado'  => 'estado-cancelado',
                                        default      => 'estado-pendiente'
                                    };

                                    $estadoIcon = match ($estado) {
                                        'aprobado'   => 'fa-circle-check',
                                        'activo'     => 'fa-bolt',
                                        'pendiente'  => 'fa-clock',
                                        'rechazado'  => 'fa-circle-xmark',
                                        'suspendido' => 'fa-user-slash',
                                        'inactivo'   => 'fa-circle-minus',
                                        'cancelado'  => 'fa-ban',
                                        default      => 'fa-clock'
                                    };

                                    $rolUsuario = strtolower((string)($u['rol'] ?? ''));

                                    // reputación
                                    $reputacionPromedio = null;
                                    $reputacionTotal    = 0;

                                    if ($rolUsuario === 'paseador') {
                                        $resumen            = $calificacionModel->resumenPorPaseador((int)$u['usu_id']);
                                        $reputacionPromedio = $resumen['promedio'] ?? null;
                                        $reputacionTotal    = (int)($resumen['total'] ?? 0);
                                    } elseif ($rolUsuario === 'dueno') {
                                        $resumen            = $calificacionModel->resumenPorDueno((int)$u['usu_id']);
                                        $reputacionPromedio = $resumen['promedio'] ?? null;
                                        $reputacionTotal    = (int)($resumen['total'] ?? 0);
                                    }

                                    // suscripción
                                    $uid = (int)($u['usu_id'] ?? 0);
                                    $sub = $subsMap[$uid] ?? null;
                                    $subEstado = $sub ? strtolower((string)($sub['estado'] ?? '')) : null;

                                    if ($subEstado === 'activa' && !empty($sub['fin'])) {
                                        $tsFin = strtotime((string)$sub['fin']);
                                        if ($tsFin !== false && $tsFin < time()) $subEstado = 'vencida';
                                    }

                                    $subBadgeClass = match ($subEstado) {
                                        'activa'    => 'bg-success',
                                        'pendiente' => 'bg-warning text-dark',
                                        'vencida'   => 'bg-secondary',
                                        'rechazada' => 'bg-danger',
                                        'cancelada' => 'bg-dark',
                                        default     => 'bg-light text-dark border',
                                    };

                                    $subBadgeText = match ($subEstado) {
                                        'activa'    => 'ACTIVA',
                                        'pendiente' => 'PEND.',
                                        'vencida'   => 'VENC.',
                                        'rechazada' => 'RECH.',
                                        'cancelada' => 'CANC.',
                                        default     => '—',
                                    };
                                    ?>
                                    <tr class="fade-in-row"
                                        data-id="<?= (int)$u['usu_id'] ?>"
                                        data-rol="<?= h($rolUsuario); ?>"
                                        data-estado="<?= h($estado); ?>">

                                        <td class="text-center"><strong>#<?= (int)$u['usu_id'] ?></strong></td>
                                        <td><?= h($u['nombre'] ?? '') ?></td>
                                        <td><?= h($u['email'] ?? '') ?></td>

                                        <td>
                                            <span class="badge bg-info text-dark">
                                                <?= h(ucfirst($u['rol'] ?? '')); ?>
                                            </span>
                                        </td>

                                        <!-- REPUTACIÓN -->
                                        <td>
                                            <?php if ($reputacionPromedio !== null && $reputacionTotal > 0): ?>
                                                <div class="d-flex flex-column align-items-center">
                                                    <div class="small fw-semibold">
                                                        <?= number_format((float)$reputacionPromedio, 1, ',', '.'); ?>/5
                                                    </div>

                                                    <div class="rating-stars">
                                                        <?php
                                                        $rounded = (int) round((float)$reputacionPromedio);
                                                        for ($i = 1; $i <= 5; $i++):
                                                            $cls = $i <= $rounded ? 'fas text-warning' : 'far text-muted';
                                                        ?>
                                                            <i class="<?= $cls ?> fa-star"></i>
                                                        <?php endfor; ?>
                                                    </div>

                                                    <div class="small text-muted">
                                                        <?= (int)$reputacionTotal ?> opinión<?= ((int)$reputacionTotal === 1) ? '' : 'es' ?>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted">Sin calificaciones</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- SUSCRIPCIÓN -->
                                        <td>
                                            <?php if ($rolUsuario !== 'paseador'): ?>
                                                <span class="text-muted">—</span>
                                            <?php else: ?>
                                                <span class="badge <?= $subBadgeClass ?> px-3 py-2 sub-mini">
                                                    <?= $subBadgeText ?>
                                                </span>

                                                <?php if ($subEstado === 'activa' && !empty($sub['fin'])): ?>
                                                    <div class="sub-meta mt-1">
                                                        Vence: <?= date('d/m/Y', strtotime((string)$sub['fin'])) ?>
                                                    </div>
                                                <?php elseif ($subEstado === 'pendiente'): ?>
                                                    <div class="sub-meta mt-1">
                                                        En revisión
                                                    </div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>

                                        <!-- ESTADO -->
                                        <td>
                                            <span class="badge-estado <?= h($badgeClass) ?> badge-estado-pill">
                                                <i class="fa-solid <?= h($estadoIcon) ?> me-1"></i>
                                                <?= h($estadoTexto) ?>
                                            </span>
                                        </td>

                                        <!-- ACCIONES -->
                                        <td>
                                            <button class="btn-accion btn-editar" type="button" data-id="<?= (int)$u['usu_id'] ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>

                                            <?php if (in_array($estado, ['activo', 'aprobado'], true)): ?>
                                                <button class="btn-accion btn-desactivar" type="button" data-id="<?= (int)$u['usu_id'] ?>">
                                                    <i class="fas fa-user-minus"></i>
                                                </button>
                                                <button class="btn-accion btn-suspender" type="button" data-id="<?= (int)$u['usu_id'] ?>">
                                                    <i class="fas fa-user-slash"></i>
                                                </button>
                                                <button class="btn-accion btn-rechazar" type="button" data-id="<?= (int)$u['usu_id'] ?>">
                                                    <i class="fas fa-times-circle"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn-accion btn-activar" type="button" data-id="<?= (int)$u['usu_id'] ?>">
                                                    <i class="fas fa-user-check"></i>
                                                </button>
                                                <button class="btn-accion btn-aprobar" type="button" data-id="<?= (int)$u['usu_id'] ?>">
                                                    <i class="fas fa-check-circle"></i>
                                                </button>
                                                <?php if ($estado !== 'rechazado'): ?>
                                                    <button class="btn-accion btn-rechazar" type="button" data-id="<?= (int)$u['usu_id'] ?>">
                                                        <i class="fas fa-times-circle"></i>
                                                    </button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <p class="text-muted small mt-2 mb-0">
                    Tip: usá la búsqueda combinada con filtros de rol y estado para encontrar usuarios específicos.
                </p>
            </div>
        </div>

        <footer class="mt-3">
            <small>© <?= date('Y') ?> Jaguata — Panel de Administración</small>
        </footer>

    </div>
</main>

<!-- Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    /* ✅ Toggle sidebar en mobile (IGUAL a Notificaciones) */
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.querySelector('.sidebar');
        const btnToggle = document.getElementById('btnSidebarToggle');

        if (btnToggle && sidebar) {
            btnToggle.addEventListener('click', () => {
                sidebar.classList.toggle('show');
            });
        }
    });

    /* Filtros */
    const searchInput = document.getElementById('searchInput');
    const filterRol = document.getElementById('filterRol');
    const filterEstado = document.getElementById('filterEstado');
    const rows = document.querySelectorAll('#tablaUsuarios tbody tr');

    function aplicarFiltros() {
        const texto = (searchInput.value || '').toLowerCase();
        const rol = (filterRol.value || '').toLowerCase();
        const estado = (filterEstado.value || '').toLowerCase();

        rows.forEach(row => {
            const t = row.textContent.toLowerCase();
            const r = (row.dataset.rol || '').toLowerCase();
            const e = (row.dataset.estado || '').toLowerCase();

            const show =
                (!texto || t.includes(texto)) &&
                (!rol || r === rol) &&
                (!estado || e === estado);

            row.style.display = show ? '' : 'none';
        });
    }

    if (searchInput && filterRol && filterEstado) {
        searchInput.addEventListener('input', aplicarFiltros);
        filterRol.addEventListener('change', aplicarFiltros);
        filterEstado.addEventListener('change', aplicarFiltros);
    }

    /* Acciones */
    document.addEventListener('DOMContentLoaded', () => {

        document.querySelectorAll('.btn-editar').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;
                window.location.href = '<?= BASE_URL; ?>/features/admin/PerfilUsuarioAdmin.php?id=' + id;
            });
        });

        const handleAction = async (id, accion, msg) => {
            if (msg && !confirm(msg)) return;

            const formData = new FormData();
            formData.append('id', id);
            formData.append('accion', accion);

            const res = await fetch('<?= BASE_URL; ?>/public/api/usuarios/accionesUsuarios.php', {
                method: 'POST',
                body: formData
            });

            const data = await res.json();
            alert(data.mensaje || 'Operación realizada');

            if (data.ok) location.reload();
        };

        document.querySelectorAll('.btn-suspender').forEach(btn =>
            btn.addEventListener('click', () => handleAction(btn.dataset.id, 'suspender', '¿Suspender este usuario?'))
        );
        document.querySelectorAll('.btn-activar').forEach(btn =>
            btn.addEventListener('click', () => handleAction(btn.dataset.id, 'activar', '¿Activar usuario?'))
        );
        document.querySelectorAll('.btn-aprobar').forEach(btn =>
            btn.addEventListener('click', () => handleAction(btn.dataset.id, 'aprobar', '¿Aprobar este usuario?'))
        );
        document.querySelectorAll('.btn-rechazar').forEach(btn =>
            btn.addEventListener('click', () => handleAction(btn.dataset.id, 'rechazar', '¿Rechazar usuario?'))
        );
        document.querySelectorAll('.btn-desactivar').forEach(btn =>
            btn.addEventListener('click', () => handleAction(btn.dataset.id, 'desactivar', '¿Desactivar usuario?'))
        );
    });
</script>

</body>
</html>
