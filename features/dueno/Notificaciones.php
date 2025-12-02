<?php

declare(strict_types=1);

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\NotificacionController;
use Jaguata\Helpers\Session;

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/AuthController.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/NotificacionController.php';

AppConfig::init();

/* 🔒 Auth solo dueño */
$auth = new AuthController();
$auth->checkRole('dueno');

$notiCtrl = new NotificacionController();

/* URL de esta página */
$selfUrl = BASE_URL . '/features/dueno/Notificaciones.php';

/* 🔧 Acciones POST */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'markRead' && isset($_POST['noti_id'])) {
        $notiId = (int)$_POST['noti_id'];
        if ($notiId > 0 && $notiCtrl->marcarLeidaForCurrentUser($notiId)) {
            Session::setSuccess('Notificación marcada como leída ✅');
        } else {
            Session::setError('No se pudo marcar la notificación como leída.');
        }
        header('Location: ' . $selfUrl);
        exit;
    }

    if ($action === 'markAllRead') {
        $cant = $notiCtrl->marcarTodasForCurrentUser();
        Session::setSuccess($cant . ' notificación(es) marcadas como leídas ✅');
        header('Location: ' . $selfUrl);
        exit;
    }
}

/* 📄 Filtros */
$q       = trim($_GET['q'] ?? '');
$leido   = $_GET['leido'] ?? '';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

$leidoParam = $leido === '' ? null : (int)$leido;

/* 🧩 Datos desde el controller (usuario + rol dueño) */
$data = $notiCtrl->listForCurrentUser($page, $perPage, $leidoParam, $q);

$notificaciones = $data['data'] ?? [];
$totalPages     = $data['totalPages'] ?? 1;

/* Mensajes flash */
$mensajeSuccess = Session::getSuccess();
$mensajeError   = Session::getError();

function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Notificaciones - Dueño | Jaguata</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSS Bootstrap + Tema Jaguata -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">
</head>

<body>

    <!-- Sidebar Dueño -->
    <?php include dirname(__DIR__, 2) . '/src/Templates/SidebarDueno.php'; ?>

    <main>
        <!-- HEADER -->
        <div class="header-box header-notificaciones mb-4">
            <div>
                <h1 class="fw-bold"><i class="fas fa-bell me-2"></i>Mis notificaciones</h1>
                <p>Enterate de novedades sobre tus paseos y tu cuenta 🐶</p>
            </div>
            <form method="post" class="m-0">
                <input type="hidden" name="action" value="markAllRead">
                <button type="submit" class="btn-enviar">
                    <i class="fas fa-check-double me-1"></i> Marcar todas como leídas
                </button>
            </form>
        </div>

        <!-- MENSAJES FLASH -->
        <?php if (!empty($mensajeSuccess)): ?>
            <div class="alert alert-success"><?= h($mensajeSuccess); ?></div>
        <?php endif; ?>

        <?php if (!empty($mensajeError)): ?>
            <div class="alert alert-danger"><?= h($mensajeError); ?></div>
        <?php endif; ?>

        <!-- FILTROS -->
        <div class="filtros mb-4">
            <form class="row g-3 align-items-end" method="get" action="">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Buscar</label>
                    <input type="text" name="q" value="<?= h($q); ?>" class="form-control"
                        placeholder="Buscar por título o mensaje...">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Estado</label>
                    <select name="leido" class="form-select">
                        <option value="">Todas</option>
                        <option value="0" <?= $leido === '0' ? 'selected' : ''; ?>>No leídas</option>
                        <option value="1" <?= $leido === '1' ? 'selected' : ''; ?>>Leídas</option>
                    </select>
                </div>
                <div class="col-md-3 d-grid">
                    <button class="btn-enviar">
                        <i class="fas fa-search me-1"></i> Aplicar filtros
                    </button>
                </div>
            </form>
        </div>

        <!-- BANDEJA -->
        <div class="section-card">
            <div class="section-header">
                <i class="fas fa-inbox me-2"></i>Bandeja de notificaciones
            </div>

            <?php if (empty($notificaciones)): ?>
                <div class="text-center text-muted py-4">
                    <i class="fas fa-bell-slash fa-2x mb-2"></i>
                    <p class="mb-0">No tenés notificaciones por ahora.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>Mensaje</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($notificaciones as $n): ?>
                                <?php
                                $notiId  = (int)($n['noti_id'] ?? 0);
                                $titulo  = $n['titulo'] ?? '';
                                $mensaje = $n['mensaje'] ?? '';
                                $fecha   = !empty($n['created_at'])
                                    ? date('d/m/Y H:i', strtotime((string)$n['created_at']))
                                    : '-';
                                $leida   = (int)($n['leido'] ?? 0) === 1;

                                $badgeClass = $leida ? 'estado-aprobado' : 'estado-pendiente';
                                $estadoText = $leida ? 'Leída' : 'No leída';
                                ?>
                                <tr>
                                    <td><?= h($titulo); ?></td>
                                    <td><?= h($mensaje); ?></td>
                                    <td><?= h($fecha); ?></td>
                                    <td>
                                        <span class="badge-estado <?= $badgeClass; ?>">
                                            <?= h($estadoText); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!$leida && $notiId > 0): ?>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="action" value="markRead">
                                                <input type="hidden" name="noti_id" value="<?= $notiId; ?>">
                                                <button type="submit" class="btn-accion btn-activar" title="Marcar como leída">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINACIÓN -->
                <?php if ($totalPages > 1): ?>
                    <nav class="mt-3">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link"
                                        href="?page=<?= $i; ?>&q=<?= urlencode($q); ?>&leido=<?= urlencode($leido); ?>">
                                        <?= $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <footer>
            <small>© <?= date('Y'); ?> Jaguata — Panel del Dueño</small>
        </footer>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>