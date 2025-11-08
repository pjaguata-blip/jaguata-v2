<?php

declare(strict_types=1);

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\NotificacionController;
use Jaguata\Helpers\Session;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/NotificacionController.php';

AppConfig::init();

/* 🔒 Auth */
$auth = new AuthController();
$auth->checkRole('paseador');

$notiCtrl = new NotificacionController();

/* 🔧 Acciones POST */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'markRead' && isset($_POST['noti_id'])) {
        $notiCtrl->markRead($_POST);
        $_SESSION['success'] = 'Notificación marcada como leída ✅';
        header('Location: Notificaciones.php');
        exit;
    }
    if ($action === 'markAllRead') {
        $notiCtrl->markAllRead();
        $_SESSION['success'] = 'Todas las notificaciones fueron marcadas como leídas ✅';
        header('Location: Notificaciones.php');
        exit;
    }
}

/* 📄 Datos */
$q       = $_GET['q'] ?? '';
$leido   = $_GET['leido'] ?? '';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

$data = $notiCtrl->index([
    'q' => $q,
    'leido' => $leido,
    'page' => $page,
    'perPage' => $perPage
]) ?? [];

$notificaciones = $data['data'] ?? [];
$totalPages     = $data['totalPages'] ?? 1;

$rolMenu      = Session::getUsuarioRol() ?: 'paseador';
$baseFeatures = BASE_URL . "/features/{$rolMenu}";

function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Notificaciones - Paseador | Jaguata</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --verde-jaguata: #3c6255;
            --verde-claro: #20c997;
            --gris-fondo: #f4f6f9;
            --gris-texto: #555;
            --blanco: #fff;
        }

        body {
            background-color: var(--gris-fondo);
            font-family: "Poppins", sans-serif;
            color: var(--gris-texto);
            margin: 0
        }

        /* Sidebar unificada */
        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            color: var(--blanco);
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 1.5rem;
            box-shadow: 3px 0 10px rgba(0, 0, 0, .2);
            z-index: 1000
        }

        .sidebar .nav-link {
            color: #ccc;
            display: flex;
            align-items: center;
            gap: .8rem;
            padding: 12px 18px;
            border-radius: 8px;
            margin: 6px 10px;
            transition: .2s;
            font-size: .95rem
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: var(--verde-claro);
            color: var(--blanco);
            transform: translateX(4px)
        }

        /* Main */
        main {
            margin-left: 250px;
            padding: 2rem
        }

        .welcome-box {
            background: linear-gradient(90deg, var(--verde-claro), var(--verde-jaguata));
            color: var(--blanco);
            padding: 1.6rem 2rem;
            border-radius: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .1)
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .08)
        }

        .card-header {
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
            background: var(--verde-jaguata);
            color: #fff;
            font-weight: 600
        }

        .noti-card {
            transition: .3s;
            border-left: 5px solid var(--verde-claro)
        }

        .noti-card.unread {
            background: #f0fff8
        }

        .noti-card.read {
            background: #fff;
            border-left-color: #adb5bd
        }

        .noti-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 22px rgba(0, 0, 0, .08)
        }

        .text-truncate-2 {
            -webkit-line-clamp: 2;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            overflow: hidden
        }

        .btn-gradient {
            background: linear-gradient(90deg, var(--verde-jaguata), var(--verde-claro));
            border: none;
            color: #fff;
            font-weight: 500
        }

        .btn-gradient:hover {
            opacity: .92
        }

        .btn-outline-success {
            color: var(--verde-jaguata);
            border-color: var(--verde-jaguata)
        }

        .btn-outline-success:hover {
            background: var(--verde-jaguata);
            color: #fff
        }

        .btn-outline-secondary {
            color: var(--verde-claro);
            border-color: var(--verde-claro)
        }

        .btn-outline-secondary:hover {
            background: var(--verde-claro);
            color: #fff
        }

        footer {
            text-align: center;
            padding: 1rem;
            color: #777;
            font-size: .9rem;
            margin-top: 2rem
        }

        @media (max-width:768px) {
            main {
                margin-left: 0;
                padding: 1.25rem
            }
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <?php include __DIR__ . '/../../src/Templates/SidebarPaseador.php'; ?>

    <!-- Contenido -->
    <main>
        <!-- Header -->
        <div class="welcome-box">
            <div>
                <h1 class="fw-bold"><i class="fas fa-bell me-2"></i>Notificaciones</h1>
                <p>Revisá tus avisos del sistema y marcá como leídas las que ya viste.</p>
            </div>
            <form method="post" class="m-0">
                <input type="hidden" name="action" value="markAllRead">
                <button type="submit" class="btn btn-outline-light fw-semibold">
                    <i class="fas fa-check-double me-1"></i> Marcar todas
                </button>
            </form>
        </div>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-filter me-2"></i>Filtros</div>
            <div class="card-body">
                <form class="row g-3" method="get">
                    <div class="col-md-6">
                        <input type="text" name="q" value="<?= h($q) ?>" class="form-control" placeholder="Buscar notificación...">
                    </div>
                    <div class="col-md-3">
                        <select name="leido" class="form-select">
                            <option value="">Todas</option>
                            <option value="0" <?= $leido === '0' ? 'selected' : ''; ?>>No leídas</option>
                            <option value="1" <?= $leido === '1' ? 'selected' : ''; ?>>Leídas</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-grid">
                        <button class="btn btn-gradient"><i class="fas fa-search me-1"></i> Filtrar</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista -->
        <?php if (empty($notificaciones)): ?>
            <div class="alert alert-info text-center py-5 shadow-sm rounded-4">
                <i class="fas fa-bell-slash fa-3x mb-3 text-muted"></i>
                <h5 class="fw-semibold">No tenés notificaciones</h5>
                <p class="text-muted mb-0">Te avisaremos cuando haya novedades 🐾</p>
            </div>
        <?php else: ?>
            <div class="vstack gap-3 mb-4">
                <?php foreach ($notificaciones as $n):
                    $isRead = (int)($n['leido'] ?? 0) === 1;
                    $titulo = h($n['titulo'] ?? '');
                    $msg    = h($n['mensaje'] ?? '');
                    $fecha  = !empty($n['created_at']) ? date('d/m/Y H:i', strtotime((string)$n['created_at'])) : '';
                ?>
                    <div class="card noti-card <?= $isRead ? 'read' : 'unread' ?> border-0">
                        <div class="card-body d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1 pe-3">
                                <h5 class="card-title mb-1">
                                    <i class="fas fa-bell text-success me-2"></i><?= $titulo ?>
                                </h5>
                                <p class="text-muted text-truncate-2 mb-2"><?= $msg ?></p>
                                <small class="text-muted"><i class="far fa-clock me-1"></i><?= h($fecha) ?></small>
                            </div>
                            <?php if (!$isRead): ?>
                                <form method="post" class="ms-3">
                                    <input type="hidden" name="action" value="markRead">
                                    <input type="hidden" name="noti_id" value="<?= (int)($n['noti_id'] ?? 0) ?>">
                                    <button class="btn btn-sm btn-outline-success rounded-circle shadow-sm" title="Marcar como leída">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPages > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&q=<?= urlencode($q) ?>&leido=<?= urlencode($leido) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>

        <footer><small>© <?= date('Y') ?> Jaguata — Panel del Paseador</small></footer>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        <?php if (isset($_SESSION['success'])): ?>
            Swal.fire({
                icon: 'success',
                title: '¡Hecho!',
                text: '<?= addslashes($_SESSION['success']) ?>',
                showConfirmButton: false,
                timer: 2500,
                background: '#f6f9f7'
            });
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
    </script>
</body>

</html>