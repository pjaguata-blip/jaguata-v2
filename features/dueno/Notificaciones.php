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

$auth = new AuthController();
$auth->checkRole('dueno');

$notiCtrl = new NotificacionController();

// ==== Acciones POST ====
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

// ==== Datos ====
$q       = $_GET['q'] ?? '';
$leido   = $_GET['leido'] ?? '';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

$data = $notiCtrl->index(['q' => $q, 'leido' => $leido, 'page' => $page, 'perPage' => $perPage]);
$notificaciones = $data['data'];
$totalPages = $data['totalPages'];

$rolMenu = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures = BASE_URL . "/features/{$rolMenu}";
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Notificaciones - Jaguata</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f7fa;
            font-family: "Poppins", sans-serif;
        }

        /* Sidebar */
        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            color: #f8f9fa;
            min-height: 100vh;
            padding-top: 1rem;
            box-shadow: 4px 0 12px rgba(0, 0, 0, 0.15);
        }

        .sidebar .nav-link {
            color: #ddd;
            border-radius: 8px;
            padding: 10px 16px;
            margin: 4px 8px;
            display: flex;
            align-items: center;
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .sidebar .nav-link:hover {
            background-color: #343454;
            color: #fff;
            transform: translateX(4px);
        }

        .sidebar .nav-link.active {
            background-color: #3c6255;
            color: #fff;
        }

        main {
            background-color: #f5f7fa;
            padding: 2rem;
        }

        .page-header {
            background: linear-gradient(90deg, #20c997, #3c6255);
            color: #fff;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-weight: 600;
            margin: 0;
        }

        .btn-light {
            background-color: #fff;
            color: #3c6255;
            border: none;
        }

        .btn-light:hover {
            background-color: #3c6255;
            color: #fff;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .noti-card {
            transition: all .3s ease;
            border-left: 5px solid #20c997;
        }

        .noti-card.unread {
            background: #f0fff8;
        }

        .noti-card.read {
            background: #fff;
            border-left-color: #adb5bd;
        }

        .noti-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 22px rgba(0, 0, 0, .08);
        }

        .text-truncate-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .btn-outline-success {
            color: #3c6255;
            border-color: #3c6255;
        }

        .btn-outline-success:hover {
            background-color: #3c6255;
            color: #fff;
        }

        .btn-outline-secondary {
            color: #20c997;
            border-color: #20c997;
        }

        .btn-outline-secondary:hover {
            background-color: #20c997;
            color: #fff;
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../../src/Templates/Header.php'; ?>
    <?php include __DIR__ . '/../../src/Templates/Navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li><a class="nav-link" href="<?= $baseFeatures; ?>/Dashboard.php"><i class="fas fa-home me-2"></i> Inicio</a></li>
                        <li><a class="nav-link" href="<?= $baseFeatures; ?>/MisMascotas.php"><i class="fas fa-paw me-2"></i> Mis Mascotas</a></li>
                        <li><a class="nav-link" href="<?= $baseFeatures; ?>/PaseosPendientes.php"><i class="fas fa-walking me-2"></i> Paseos</a></li>
                        <li><a class="nav-link active" href="#"><i class="fas fa-bell me-2"></i> Notificaciones</a></li>
                        <li><a class="nav-link text-danger" href="<?= BASE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Cerrar sesión</a></li>
                    </ul>
                </div>
            </div>

            <!-- Main -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="page-header">
                    <h1><i class="fas fa-bell me-2"></i> Notificaciones</h1>
                    <form method="post" class="m-0">
                        <input type="hidden" name="action" value="markAllRead">
                        <button type="submit" class="btn btn-light btn-sm">
                            <i class="fas fa-check-double me-1"></i> Marcar todas
                        </button>
                    </form>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <form class="row g-3" method="get">
                            <div class="col-md-5">
                                <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" class="form-control" placeholder="Buscar notificación...">
                            </div>
                            <div class="col-md-3">
                                <select name="leido" class="form-select">
                                    <option value="">Todas</option>
                                    <option value="0" <?= $leido === '0' ? 'selected' : '' ?>>No leídas</option>
                                    <option value="1" <?= $leido === '1' ? 'selected' : '' ?>>Leídas</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-outline-success w-100"><i class="fas fa-filter me-1"></i> Filtrar</button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if (empty($notificaciones)): ?>
                    <div class="alert alert-info text-center py-5 shadow-sm rounded-4">
                        <i class="fas fa-bell-slash fa-3x mb-3 text-muted"></i>
                        <h5 class="fw-semibold">No tienes notificaciones</h5>
                        <p class="text-muted mb-0">Te avisaremos cuando haya novedades 🐾</p>
                    </div>
                <?php else: ?>
                    <div class="vstack gap-3 mb-4">
                        <?php foreach ($notificaciones as $n):
                            $isRead = (int)$n['leido'] === 1;
                            $titulo = htmlspecialchars($n['titulo'] ?? '');
                            $msg = htmlspecialchars($n['mensaje'] ?? '');
                            $fecha = $n['created_at'] ? date('d/m/Y H:i', strtotime($n['created_at'])) : '';
                        ?>
                            <div class="card noti-card <?= $isRead ? 'read' : 'unread' ?> border-0">
                                <div class="card-body d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h5 class="card-title mb-1">
                                            <i class="fas fa-bell text-success me-2"></i><?= $titulo ?>
                                        </h5>
                                        <p class="text-muted text-truncate-2 mb-2"><?= $msg ?></p>
                                        <small class="text-muted"><i class="far fa-clock me-1"></i><?= $fecha ?></small>
                                    </div>
                                    <?php if (!$isRead): ?>
                                        <form method="post" class="ms-3">
                                            <input type="hidden" name="action" value="markRead">
                                            <input type="hidden" name="noti_id" value="<?= (int)$n['noti_id'] ?>">
                                            <button class="btn btn-sm btn-outline-success rounded-circle shadow-sm"><i class="fas fa-check"></i></button>
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
            </main>
        </div>
    </div>

    <?php include __DIR__ . '/../../src/Templates/Footer.php'; ?>
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
        <?php unset($_SESSION['success']);
        endif; ?>
    </script>
</body>

</html>