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
    $postData = $_POST;
    // Mantenemos el destino en la URL si ya estaba
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
        'dueno'    => 'Dueños',
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
</head>

<body>

    <?php include __DIR__ . '/../../src/Templates/SidebarAdmin.php'; ?>

    <main>
        <!-- HEADER -->
        <div class="header-box header-notificaciones mb-4">
            <div>
                <h1 class="fw-bold">Centro de Notificaciones</h1>
                <p>Envía avisos, recordatorios y promociones a los usuarios 🔔</p>
            </div>
            <i class="fas fa-bell fa-3x opacity-75"></i>
        </div>

        <!-- MENSAJES FLASH -->
        <?php if ($mensajeSuccess): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensajeSuccess); ?></div>
        <?php endif; ?>

        <?php if ($mensajeError): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($mensajeError); ?></div>
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

        <!-- FILTROS (buscador + estado) -->
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
                        <option value="enviado">Enviado</option>
                        <option value="pendiente">Pendiente</option>
                    </select>
                </div>
            </form>
        </div>

        <!-- NUEVA NOTIFICACIÓN -->
        <div class="section-card mb-4">
            <div class="section-header">
                <i class="fas fa-paper-plane me-2"></i>Enviar nueva notificación
            </div>
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
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Mensaje</label>
                    <textarea name="mensaje" class="form-control" rows="4"
                        placeholder="Escribe el contenido del aviso..." required></textarea>
                </div>

                <!-- Opcionales: tipo / prioridad / canal -->
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
                            <option value="email">Email</option>
                            <option value="push">Push</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn-enviar">
                    <i class="fas fa-paper-plane me-2"></i>Enviar
                </button>
            </form>
        </div>

        <!-- HISTORIAL DE NOTIFICACIONES -->
        <div class="section-card">
            <div class="section-header">
                <i class="fas fa-history me-2"></i>Historial de notificaciones
            </div>
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
                                $id             = (int)($n['id'] ?? 0);
                                $titulo         = $n['titulo'] ?? '';
                                $rolDestinoRaw  = $n['rol_destinatario'] ?? '';
                                $rolLabel       = labelDestino($rolDestinoRaw);
                                $fecha          = $n['fecha'] ?? '';
                                $estado         = strtolower($n['estado'] ?? 'pendiente');
                                $estadoLabel    = ucfirst($estado);
                                $mensaje        = $n['mensaje'] ?? '';

                                $badgeClass = $estado === 'enviado'
                                    ? 'badge-enviado'
                                    : 'badge-pendiente';
                                ?>
                                <tr data-estado="<?= htmlspecialchars($estado, ENT_QUOTES, 'UTF-8'); ?>">
                                    <td><?= $id > 0 ? $id : '-' ?></td>
                                    <td><?= htmlspecialchars($titulo); ?></td>
                                    <td><?= htmlspecialchars($rolLabel); ?></td>
                                    <td><?= htmlspecialchars($fecha); ?></td>
                                    <td>
                                        <span class="badge-estado <?= $badgeClass ?>">
                                            <?= htmlspecialchars($estadoLabel); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#verNotiModal"
                                            data-titulo="<?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8'); ?>"
                                            data-destinatario="<?= htmlspecialchars($rolLabel, ENT_QUOTES, 'UTF-8'); ?>"
                                            data-mensaje="<?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL DETALLE -->
        <div class="modal fade" id="verNotiModal" tabindex="-1"
            aria-labelledby="verNotiLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content modal-jaguata">
                    <div class="modal-header jaguata-modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-bell me-2"></i>Detalle de Notificación
                        </h5>
                        <button type="button" class="btn-close btn-close-white"
                            data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <p class="modal-label">Título</p>
                                <p class="modal-value" id="notiTitulo"></p>
                            </div>
                            <div class="col-md-6">
                                <p class="modal-label">Destinatario</p>
                                <p class="modal-value" id="notiDestinatario"></p>
                            </div>
                            <div class="col-12">
                                <p class="modal-label">Mensaje</p>
                                <p class="modal-value" id="notiMensaje"></p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer jaguata-modal-footer">
                        <button type="button" class="btn-ver" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <footer>
            <small>© <?= date('Y') ?> Jaguata — Panel de Administración</small>
        </footer>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // === Filtros dinámicos ===
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
            el.addEventListener('input', aplicarFiltros);
            el.addEventListener('change', aplicarFiltros);
        });

        // === Modal detalle ===
        const verNotiModal = document.getElementById('verNotiModal');
        if (verNotiModal) {
            verNotiModal.addEventListener('show.bs.modal', event => {
                const button = event.relatedTarget;
                if (!button) return;

                const titulo = button.getAttribute('data-titulo') || '';
                const destinatario = button.getAttribute('data-destinatario') || '';
                const mensaje = button.getAttribute('data-mensaje') || '';

                document.getElementById('notiTitulo').textContent = titulo;
                document.getElementById('notiDestinatario').textContent = destinatario;
                document.getElementById('notiMensaje').textContent = mensaje;
            });
        }
    </script>
</body>

</html>