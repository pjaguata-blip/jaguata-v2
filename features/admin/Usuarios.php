<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/UsuarioController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Controllers\UsuarioController;

AppConfig::init();

// 🔒 Seguridad
if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'admin') {
    header('Location: ' . BASE_URL . '/public/login.php?error=unauthorized');
    exit;
}

// Controlador
$usuarioController = new UsuarioController();
$usuarios = $usuarioController->index() ?: [];
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

        <!-- HEADER -->
        <div class="header-box header-usuarios">
            <div>
                <h1 class="fw-bold">Gestión de Usuarios</h1>
                <p class="mb-0">Administrá usuarios, roles y estados 👥</p>
            </div>
            <i class="fas fa-user-gear fa-3x opacity-75"></i>
        </div>

        <!-- FILTROS -->
        <div class="filtros">
            <form class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Buscar</label>
                    <input id="searchInput" type="text" class="form-control" placeholder="Nombre o correo...">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Rol</label>
                    <select id="filterRol" class="form-select">
                        <option value="">Todos</option>
                        <option value="admin">Administrador</option>
                        <option value="paseador">Paseador</option>
                        <option value="dueno">Dueño</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select id="filterEstado" class="form-select">
                        <option value="">Todos</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="aprobado">Aprobado</option>
                        <option value="rechazado">Rechazado</option>
                        <option value="cancelado">Cancelado</option>
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                        <option value="suspendido">Suspendido</option>
                    </select>
                </div>
            </form>
        </div>
        <!-- EXPORT -->
        <div class="export-buttons">
            <button class="btn btn-excel"
                onclick="window.location.href='<?= BASE_URL; ?>/public/api/usuarios/exportUsuarios.php'">
                <i class="fas fa-file-excel"></i> Excel
            </button>
        </div>
        <!-- TABLA -->
        <div class="table-responsive">
            <table class="table text-center align-middle" id="tablaUsuarios">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                        <?php
                        $estadoRaw = $u['estado'] ?? 'pendiente';
                        $estado = strtolower(trim($estadoRaw));

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

                        $estadoTexto = ucfirst($estado);
                        ?>
                        <tr data-id="<?= (int)$u['usu_id'] ?>"
                            data-rol="<?= strtolower($u['rol']) ?>"
                            data-estado="<?= $estado ?>">

                            <td><?= (int)$u['usu_id'] ?></td>
                            <td><?= htmlspecialchars($u['nombre']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>

                            <td>
                                <span class="badge bg-info text-dark">
                                    <?= ucfirst($u['rol']) ?>
                                </span>
                            </td>

                            <td>
                                <span class="badge-estado <?= $badgeClass ?>">
                                    <?= htmlspecialchars($estadoTexto) ?>
                                </span>
                            </td>

                            <td>
                                <!-- Editar -->
                                <button class="btn-accion btn-editar" data-id="<?= (int)$u['usu_id'] ?>">
                                    <i class="fas fa-edit"></i>
                                </button>

                                <?php if (in_array($estado, ['activo', 'aprobado'], true)): ?>
                                    <button class="btn-accion btn-desactivar" data-id="<?= (int)$u['usu_id'] ?>">
                                        <i class="fas fa-user-minus"></i>
                                    </button>
                                    <button class="btn-accion btn-suspender" data-id="<?= (int)$u['usu_id'] ?>">
                                        <i class="fas fa-user-slash"></i>
                                    </button>
                                    <button class="btn-accion btn-rechazar" data-id="<?= (int)$u['usu_id'] ?>">
                                        <i class="fas fa-times-circle"></i>
                                    </button>
                                <?php else: ?>
                                    <button class="btn-accion btn-activar" data-id="<?= (int)$u['usu_id'] ?>">
                                        <i class="fas fa-user-check"></i>
                                    </button>
                                    <button class="btn-accion btn-aprobar" data-id="<?= (int)$u['usu_id'] ?>">
                                        <i class="fas fa-check-circle"></i>
                                    </button>
                                    <?php if ($estado !== 'rechazado'): ?>
                                        <button class="btn-accion btn-rechazar" data-id="<?= (int)$u['usu_id'] ?>">
                                            <i class="fas fa-times-circle"></i>
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>


                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <footer>
            © <?= date('Y') ?> Jaguata — Panel de Administración
        </footer>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // FILTROS
        const searchInput = document.getElementById('searchInput');
        const filterRol = document.getElementById('filterRol');
        const filterEstado = document.getElementById('filterEstado');
        const rows = document.querySelectorAll('#tablaUsuarios tbody tr');

        function aplicarFiltros() {
            const texto = searchInput.value.toLowerCase();
            const rol = filterRol.value.toLowerCase();
            const estado = filterEstado.value.toLowerCase();

            rows.forEach(row => {
                const t = row.textContent.toLowerCase();
                const r = row.dataset.rol;
                const e = row.dataset.estado;

                const show =
                    (texto === '' || t.includes(texto)) &&
                    (rol === '' || r === rol) &&
                    (estado === '' || e === estado);

                row.style.display = show ? '' : 'none';
            });
        }

        searchInput.addEventListener('input', aplicarFiltros);
        filterRol.addEventListener('change', aplicarFiltros);
        filterEstado.addEventListener('change', aplicarFiltros);

        // ACCIONES
        document.addEventListener('DOMContentLoaded', () => {

            // Editar
            document.querySelectorAll('.btn-editar').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.dataset.id;
                    window.location.href = '<?= BASE_URL; ?>/features/admin/editar_usuario.php?id=' + id;
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
                alert(data.mensaje);
                if (data.ok) location.reload();
            };



            document.querySelectorAll('.btn-suspender').forEach(btn => {
                btn.addEventListener('click', () => {
                    handleAction(btn.dataset.id, 'suspender', '¿Suspender este usuario?');
                });
            });

            document.querySelectorAll('.btn-activar').forEach(btn => {
                btn.addEventListener('click', () => {
                    handleAction(btn.dataset.id, 'activar', '¿Activar usuario?');
                });
            });

            document.querySelectorAll('.btn-aprobar').forEach(btn => {
                btn.addEventListener('click', () => {
                    handleAction(btn.dataset.id, 'aprobar', '¿Aprobar este usuario?');
                });
            });

            document.querySelectorAll('.btn-rechazar').forEach(btn => {
                btn.addEventListener('click', () => {
                    handleAction(btn.dataset.id, 'rechazar', '¿Rechazar usuario?');
                });
            });

            document.querySelectorAll('.btn-desactivar').forEach(btn => {
                btn.addEventListener('click', () => {
                    handleAction(btn.dataset.id, 'desactivar', '¿Desactivar usuario?');
                });
            });
        });
    </script>
</body>

</html>