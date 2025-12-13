<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/UsuarioController.php';
require_once dirname(__DIR__, 2) . '/src/Models/Calificacion.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Controllers\UsuarioController;
use Jaguata\Models\Calificacion;

AppConfig::init();

// 🔒 Seguridad
if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'admin') {
    header('Location: ' . BASE_URL . '/public/login.php?error=unauthorized');
    exit;
}

function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

// Datos
$usuarioController = new UsuarioController();
$usuarios          = $usuarioController->index() ?: [];

$calificacionModel = new Calificacion();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Jaguata</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">
</head>

<body>

    <?php include __DIR__ . '/../../src/Templates/SidebarAdmin.php'; ?>

    <main>
        <div class="container-fluid px-3 px-md-4">

            <!-- HEADER -->
            <div class="header-box header-usuarios">
                <div>
                    <h1 class="fw-bold mb-1">Gestión de Usuarios</h1>
                    <p class="mb-0">Administrá usuarios, roles y estados 👥</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-light d-lg-none" id="btnSidebarToggle" type="button">
                        <i class="fas fa-bars"></i>
                    </button>
                    <i class="fas fa-user-gear fa-3x opacity-75 d-none d-lg-block"></i>
                </div>
            </div>

            <!-- FILTROS -->
            <div class="filtros mb-3">
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
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Listado de usuarios</h5>
                    <span class="badge bg-secondary"><?= count($usuarios); ?> registro(s)</span>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table text-center align-middle table-hover" id="tablaUsuarios">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Rol</th>
                                    <th>Reputación</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if (empty($usuarios)): ?>
                                    <tr>
                                        <td colspan="7" class="text-muted py-3">No se encontraron usuarios registrados.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($usuarios as $u): ?>
                                        <?php
                                        $estadoRaw = $u['estado'] ?? 'pendiente';
                                        $estado    = strtolower(trim((string)$estadoRaw));
                                        $estadoTexto = ucfirst($estado);

                                        // badge + icono pro
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

                                        // reputación
                                        $rolUsuario         = strtolower($u['rol'] ?? '');
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

                                                        <button
                                                            type="button"
                                                            class="btn btn-sm btn-opiniones mt-2 btn-opiniones-js"
                                                            data-usuario="<?= (int)$u['usu_id'] ?>"
                                                            data-tipo="<?= ($rolUsuario === 'paseador') ? 'paseador' : 'mascota' ?>"
                                                            data-nombre="<?= h($u['nombre'] ?? 'Usuario') ?>">
                                                            <i class="fas fa-comment-dots me-1"></i> Ver opiniones
                                                        </button>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-muted">Sin calificaciones</span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- ESTADO (mejorado) -->
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

    <!-- ✅ MODAL OPINIONES -->
    <div class="modal fade modal-jaguata" id="modalOpiniones" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">

                <div class="modal-header">
                    <div>
                        <div class="modal-title">
                            <i class="fas fa-comment-dots me-2"></i>
                            Opiniones — <span id="opinionesNombre">Usuario</span>
                        </div>
                        <small class="opacity-75">Comentarios de calificaciones</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <div id="opinionesLoading" class="text-center py-4 d-none">
                        <div class="spinner-border" role="status"></div>
                        <div class="text-muted mt-2">Cargando opiniones...</div>
                    </div>

                    <div id="opinionesEmpty" class="alert alert-info border-0 rounded-4 d-none mb-0"
                        style="background:#eaf5f0; color:#245a45;">
                        Sin opiniones con comentario.
                    </div>

                    <div id="opinionesList" class="opi-grid d-none"></div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        /* ========= Sidebar toggle (mobile) ========= */
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.querySelector('.sidebar');
            const btnToggle = document.getElementById('btnSidebarToggle');
            if (btnToggle && sidebar) {
                btnToggle.addEventListener('click', () => sidebar.classList.toggle('show'));
            }
        });

        /* ========= Filtros ========= */
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

        /* ========= Acciones + Opiniones ========= */
        document.addEventListener('DOMContentLoaded', () => {

            // Editar
            document.querySelectorAll('.btn-editar').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.dataset.id;
                    window.location.href = '<?= BASE_URL; ?>/features/admin/PerfilUsuarioAdmin.php?id=' + id;
                });
            });

            // acciones
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

            // opiniones
            const modalEl = document.getElementById('modalOpiniones');
            const modal = modalEl ? new bootstrap.Modal(modalEl) : null;

            const elNombre = document.getElementById('opinionesNombre');
            const elLoading = document.getElementById('opinionesLoading');
            const elEmpty = document.getElementById('opinionesEmpty');
            const elList = document.getElementById('opinionesList');

            const setState = (state) => {
                elLoading.classList.toggle('d-none', state !== 'loading');
                elEmpty.classList.toggle('d-none', state !== 'empty');
                elList.classList.toggle('d-none', state !== 'list');
            };

            const esc = (s) => (s || '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            document.querySelectorAll('.btn-opiniones-js').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const usuarioId = btn.dataset.usuario;
                    const tipo = btn.dataset.tipo || 'paseador';
                    const nombre = btn.dataset.nombre || 'Usuario';

                    elNombre.textContent = nombre;
                    if (!modal) return;

                    modal.show();
                    setState('loading');
                    elList.innerHTML = '';

                    try {
                        const url = `<?= BASE_URL; ?>/public/api/calificaciones/opiniones.php?usuario_id=${encodeURIComponent(usuarioId)}&tipo=${encodeURIComponent(tipo)}&limite=30`;
                        const res = await fetch(url);
                        const data = await res.json();

                        if (!data.ok) {
                            setState('empty');
                            elEmpty.textContent = data.mensaje || 'No se pudo cargar.';
                            return;
                        }

                        const opiniones = data.opiniones || [];
                        if (!opiniones.length) {
                            setState('empty');
                            return;
                        }

                        const html = opiniones.map(o => {
                            const cal = Math.max(0, Math.min(5, parseInt(o.calificacion || 0, 10)));
                            const estrellas = '⭐'.repeat(cal);
                            const fecha = o.created_at ? new Date(o.created_at).toLocaleString('es-PY') : '';
                            const quien = o.rater_nombre ?
                                `${o.rater_nombre}${o.rater_email ? ' (' + o.rater_email + ')' : ''}` :
                                '—';

                            return `
            <div class="opi-card">
              <div class="opi-top">
                <div>
                  <div class="opi-who">${esc(quien)}</div>
                  <div class="opi-meta">${esc(fecha)} • Paseo #${esc(String(o.paseo_id || '—'))}</div>
                </div>
                <div class="opi-stars text-warning">${esc(estrellas)}</div>
              </div>
              <div class="opi-comment">${esc(o.comentario || '')}</div>
            </div>
          `;
                        }).join('');

                        elList.innerHTML = html;
                        setState('list');

                    } catch (e) {
                        console.error(e);
                        setState('empty');
                        elEmpty.textContent = 'Error inesperado al cargar opiniones.';
                    }
                });
            });
        });
    </script>

</body>

</html>