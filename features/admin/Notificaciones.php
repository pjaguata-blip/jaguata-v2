<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/AuthController.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/NotificacionController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\NotificacionController;

AppConfig::init();

// 🔒 Solo admin
$auth = new AuthController();
$auth->checkRole('admin');

// Filtro por destino (?destino=todos|admin|paseador|dueno)
$destino = $_GET['destino'] ?? 'todos';

$notificacionController = new NotificacionController();

// 📩 Alta de notificación desde el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postData  = $_POST;
    $resultado = $notificacionController->crearDesdeAdmin($postData);

    if ($resultado['success'] ?? false) {
        Session::setSuccess('Notificación creada correctamente.');
    } else {
        Session::setError($resultado['error'] ?? 'No se pudo crear la notificación.');
    }

    header('Location: ' . BASE_URL . '/features/admin/Notificaciones.php?destino=' . urlencode($destino));
    exit;
}

// 📋 Listado desde BD
$notificaciones = $notificacionController->indexAdmin($destino);
$sinDatos       = empty($notificaciones);

// Mensajes flash
$mensajeError   = Session::getError();
$mensajeSuccess = Session::getSuccess();

// Helper para mostrar texto humano de rol_destinatario
function labelDestino(string $rol): string
{
    $rol = strtolower($rol);
    return match ($rol) {
        'admin'    => 'Administradores',
        'paseador' => 'Paseadores',
        'dueno', 'dueño' => 'Dueños',
        'todos'    => 'Todos',
        default    => ucfirst($rol),
    };
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Notificaciones - Jaguata</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <style>
        /* ✅ Modal “estirado” y con estética Jaguata */
        .modal-jaguata .modal-content {
            border-radius: 18px;
            border: 0;
            overflow: hidden;
            box-shadow: 0 18px 45px rgba(0, 0, 0, .15);
        }

        .modal-jaguata .modal-header {
            background: var(--verde-jaguata, #3c6255);
            color: #fff;
            border: 0;
        }

        .modal-jaguata .modal-title {
            font-weight: 700;
        }

        .modal-jaguata .modal-body {
            background: #fff;
        }

        .modal-jaguata .meta-pill {
            display: inline-flex;
            gap: .5rem;
            align-items: center;
            padding: .35rem .65rem;
            border-radius: 999px;
            background: rgba(32, 201, 151, .12);
            color: var(--verde-jaguata, #3c6255);
            font-weight: 600;
            font-size: .85rem;
        }

        .modal-jaguata .mensaje-box {
            background: var(--gris-fondo, #f4f6f9);
            border-radius: 14px;
            padding: 14px 16px;
            white-space: pre-wrap;
            line-height: 1.5;
        }
    </style>
</head>

<body>

    <?php include __DIR__ . '/../../src/Templates/SidebarAdmin.php'; ?>

    <main>
        <div class="container-fluid px-3 px-md-2">

            <!-- HEADER -->
            <div class="header-box header-notificaciones mb-3">
                <div>
                    <h1 class="fw-bold mb-1">Centro de Notificaciones</h1>
                    <p class="mb-0">Envía avisos, recordatorios y promociones a los usuarios 🔔</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                   
                </div>
                <a href="<?= $baseFeatures; ?>/Dashboard.php" class="btn btn-outline-light">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
            </div>

            <!-- MENSAJES FLASH -->
            <?php if ($mensajeSuccess): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($mensajeSuccess); ?>
                </div>
            <?php endif; ?>

            <?php if ($mensajeError): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($mensajeError); ?>
                </div>
            <?php endif; ?>

            <!-- FILTRO POR DESTINO (chips) -->
            <div class="mb-3 d-flex flex-wrap gap-2">
                <?php
                $destinosUi = [
                    'todos'    => 'Todos',
                    'dueno'    => 'Dueños',
                    'paseador' => 'Paseadores',
                    'admin'    => 'Administradores',
                ];
                ?>
                <?php foreach ($destinosUi as $key => $label): ?>
                    <?php $active = ($destino === $key) ? 'btn-success' : 'btn-outline-secondary'; ?>
                    <a href="Notificaciones.php?destino=<?= urlencode($key); ?>"
                        class="btn btn-sm <?= $active; ?>">
                        <?= htmlspecialchars($label); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- FILTROS -->
            <div class="filtros mb-4">
                <form class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Buscar</label>
                        <input type="text" id="searchInput" class="form-control"
                            placeholder="Título o destinatario...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Estado</label>
                        <select id="filterEstado" class="form-select">
                            <option value="">Todos</option>
                            <!-- valores lógicos (igual que data-estado) -->
                            <option value="enviado">Enviado</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="fallida">Fallida</option>
                        </select>

                    </div>
                </form>
            </div>

            <!-- NUEVA NOTIFICACIÓN -->
            <div class="section-card mb-4">
                <div class="section-header d-flex align-items-center">
                    <i class="fas fa-paper-plane me-2"></i>
                    <span>Enviar nueva notificación</span>
                </div>
                <div class="section-body">
                    <form method="post" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Título</label>
                                <input type="text" name="titulo" class="form-control"
                                    placeholder="Ej. Mantenimiento programado" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Destinatario</label>
                                <select name="destinatario" class="form-select">
                                    <option value="todos">Todos</option>
                                    <option value="dueño">Dueños</option>
                                    <option value="paseador">Paseadores</option>
                                    <option value="admin">Administradores</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Mensaje</label>
                            <textarea name="mensaje" class="form-control" rows="4"
                                placeholder="Escribe el contenido del aviso..." required></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Tipo</label>
                                <select name="tipo" class="form-select">
                                    <option value="general">General</option>
                                    <option value="sistema">Sistema</option>
                                    <option value="promocion">Promoción</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Prioridad</label>
                                <select name="prioridad" class="form-select">
                                    <option value="baja">Baja</option>
                                    <option value="media" selected>Media</option>
                                    <option value="alta">Alta</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Canal</label>
                                <select name="canal" class="form-select">
                                    <option value="app" selected>App</option>

                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn-enviar">
                            <i class="fas fa-paper-plane me-2"></i>Enviar
                        </button>
                    </form>
                </div>
            </div>

            <!-- HISTORIAL -->
            <div class="section-card mb-3">
                <div class="section-header d-flex align-items-center">
                    <i class="fas fa-history me-2"></i>
                    <span>Historial de notificaciones</span>
                </div>
                <div class="section-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="tablaNotificaciones">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Título</th>
                                    <th>Destinatario</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($sinDatos): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            No hay notificaciones registradas.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($notificaciones as $n): ?>
                                        <?php
                                        $id            = (int)($n['id'] ?? 0);
                                        $titulo        = $n['titulo'] ?? '';
                                        $rolDestinoRaw = $n['rol_destinatario'] ?? '';
                                        $rolLabel      = labelDestino($rolDestinoRaw);
                                        $fecha         = $n['fecha'] ?? '';
                                        $mensaje       = $n['mensaje'] ?? '';

                                        // ✅ estado REAL desde BD
                                        $estadoRaw = strtolower(trim((string)($n['estado'] ?? 'pendiente')));

                                        // ✅ mapeo BD -> estado lógico
                                        $estado = match ($estadoRaw) {
                                            'enviada'   => 'enviado',
                                            'pendiente' => 'pendiente',
                                            'fallida'   => 'fallida',
                                            default     => 'pendiente',
                                        };

                                        // ✅ etiqueta visible
                                        $estadoLabel = match ($estado) {
                                            'enviado'   => 'Enviado',
                                            'pendiente' => 'Pendiente',
                                            'fallida'   => 'Fallida',
                                            default     => 'Pendiente',
                                        };

                                        // ✅ clase badge correcta (NO se pisa después)
                                        $badgeClass = match ($estado) {
                                            'enviado'   => 'badge-enviado',
                                            'pendiente' => 'badge-pendiente',
                                            'fallida'   => 'badge-fallida',
                                            default     => 'badge-pendiente',
                                        };
                                        ?>

                                        <tr class="fade-in-row"
                                            data-estado="<?= htmlspecialchars($estado, ENT_QUOTES, 'UTF-8'); ?>">

                                            <td class="text-center">
                                                <?= $id > 0 ? "<strong>#{$id}</strong>" : '-' ?>
                                            </td>

                                            <td><?= htmlspecialchars($titulo); ?></td>
                                            <td><?= htmlspecialchars($rolLabel); ?></td>
                                            <td><?= htmlspecialchars($fecha); ?></td>
                                            <td>
                                                <span class="badge-estado <?= $badgeClass ?>">
                                                    <?= htmlspecialchars($estadoLabel); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn-ver btn-sm"
                                                    type="button"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#verNotiModal"
                                                    data-titulo="<?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-destinatario="<?= htmlspecialchars($rolLabel, ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-mensaje="<?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="fas fa-eye"></i> Ver
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <p class="text-muted small mt-2 mb-0">
                        Tip: usá la búsqueda junto con el filtro de estado para encontrar avisos específicos.
                    </p>
                </div>
            </div>

            <footer class="mt-3">
                <small>© <?= date('Y') ?> Jaguata — Panel de Administración</small>
            </footer>
        </div>
    </main>

    <!-- ✅ MODAL VER NOTIFICACIÓN -->
    <div class="modal fade modal-jaguata" id="verNotiModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0">
                            <i class="fas fa-eye me-2"></i>
                            <span id="notiTitulo">Notificación</span>
                        </h5>
                        <div class="mt-2">
                            <span class="meta-pill">
                                <i class="fas fa-users"></i>
                                <span id="notiDestinatario">—</span>
                            </span>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <div class="mensaje-box" id="notiMensaje">—</div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Toggle sidebar en mobile
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.querySelector('.sidebar');
            const btnToggle = document.getElementById('btnSidebarToggle');

            if (btnToggle && sidebar) {
                btnToggle.addEventListener('click', () => {
                    sidebar.classList.toggle('show');
                });
            }
        });

        // Filtros dinámicos
        const searchInput = document.getElementById('searchInput');
        const filterEstado = document.getElementById('filterEstado');
        const rows = document.querySelectorAll('#tablaNotificaciones tbody tr');

        function aplicarFiltros() {
            const texto = (searchInput.value || '').toLowerCase();
            const estadoVal = (filterEstado.value || '').toLowerCase();

            rows.forEach(row => {
                const rowEstado = (row.dataset.estado || '').toLowerCase();
                const rowTexto = row.textContent.toLowerCase();

                const visible =
                    rowTexto.includes(texto) &&
                    (!estadoVal || rowEstado === estadoVal);

                row.style.display = visible ? '' : 'none';
            });
        }

        [searchInput, filterEstado].forEach(el => {
            if (!el) return;
            el.addEventListener('input', aplicarFiltros);
            el.addEventListener('change', aplicarFiltros);
        });

        // Modal detalle
        const verNotiModal = document.getElementById('verNotiModal');
        if (verNotiModal) {
            verNotiModal.addEventListener('show.bs.modal', event => {
                const button = event.relatedTarget;
                if (!button) return;

                const titulo = button.getAttribute('data-titulo') || '';
                const destinatario = button.getAttribute('data-destinatario') || '';
                const mensaje = button.getAttribute('data-mensaje') || '';

                const tEl = document.getElementById('notiTitulo');
                const dEl = document.getElementById('notiDestinatario');
                const mEl = document.getElementById('notiMensaje');

                if (tEl) tEl.textContent = titulo;
                if (dEl) dEl.textContent = destinatario;
                if (mEl) mEl.textContent = mensaje;
            });
        }
    </script>

</body>

</html>