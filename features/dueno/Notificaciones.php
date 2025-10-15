<?php

declare(strict_types=1);

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\NotificacionController;
use Jaguata\Helpers\Session;

// ✅ Cargar el autoload ANTES de los controladores (agregado)
require_once __DIR__ . '/../../vendor/autoload.php';

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/NotificacionController.php';
require_once __DIR__ . '/../../vendor/autoload.php'; // ← tu línea original, se mantiene

AppConfig::init();

// Autenticación
$auth = new AuthController();
$auth->checkRole('dueno');

// Controlador
$notiCtrl = new NotificacionController();

// Acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'markRead' && isset($_POST['noti_id'])) {
        $notiCtrl->markRead($_POST);
        header('Location: Notificaciones.php');
        exit;
    }
    if ($action === 'markAllRead') {
        $notiCtrl->markAllRead();
        header('Location: Notificaciones.php');
        exit;
    }
}

// Datos
$q       = $_GET['q'] ?? '';
$leido   = $_GET['leido'] ?? '';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

$data = $notiCtrl->index(['q' => $q, 'leido' => $leido, 'page' => $page, 'perPage' => $perPage]);
$notificaciones = $data['data'];
$totalPages = $data['totalPages'];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Notificaciones - Jaguata</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
    <style>
        .noti-card.unread {
            border-left: 4px solid #0d6efd;
            background: #f8fbff;
        }

        .noti-card.read {
            border-left: 4px solid #adb5bd;
        }

        .text-truncate-2 {
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            overflow: hidden;
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
                    <ul class="nav flex-column gap-1">
                        <!-- Mi Perfil -->
                        <li class="nav-item">
                            <button class="nav-link d-flex align-items-center w-100 text-start"
                                data-bs-toggle="collapse" data-bs-target="#menuPerfil" aria-expanded="false">
                                <i class="fas fa-user me-2"></i>
                                <span class="flex-grow-1">Mi Perfil</span>
                                <i class="fas fa-chevron-right ms-2 chevron"></i>
                            </button>
                            <ul class="collapse ps-4 nav flex-column" id="menuPerfil">
                                <li class="nav-item">
                                    <a class="nav-link" href="<?= $baseFeatures; ?>/MiPerfil.php">
                                        <i class="fas fa-id-card me-2"></i> Ver Perfil
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?= $baseFeatures; ?>/EditarPerfil.php">
                                        <i class="fas fa-user-edit me-2 text-warning"></i> Editar Perfil
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?= $baseFeatures; ?>/GastosTotales.php">
                                        <i class="fas fa-coins me-2 text-success"></i> Gastos Totales
                                    </a>
                                </li>
                            </ul>
                        </li>




                        <!-- Mascotas -->
                        <li class="nav-item">
                            <button class="nav-link d-flex align-items-center w-100 text-start"
                                data-bs-toggle="collapse" data-bs-target="#menuMascotas" aria-expanded="false">
                                <i class="fas fa-paw me-2"></i>
                                <span class="flex-grow-1">Mascotas</span>
                                <i class="fas fa-chevron-right ms-2 chevron"></i>
                            </button>
                            <ul class="collapse ps-4 nav flex-column" id="menuMascotas">
                                <li class="nav-item">
                                    <a class="nav-link" href="MisMascotas.php">
                                        <i class="fas fa-list-ul me-2"></i> Mis Mascotas
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="AgregarMascota.php">
                                        <i class="fas fa-plus-circle me-2"></i> Agregar Mascota
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?= $firstMascotaId ? '' : 'disabled' ?>"
                                        href="<?= $firstMascotaId ? 'PerfilMascota.php?id=' . (int)$firstMascotaId : '#' ?>">
                                        <i class="fas fa-id-badge me-2"></i> Perfil de mi Mascota
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Paseos -->
                        <li class="nav-item">
                            <button class="nav-link d-flex align-items-center w-100 text-start"
                                data-bs-toggle="collapse" data-bs-target="#menuPaseos" aria-expanded="false">
                                <i class="fas fa-walking me-2"></i>
                                <span class="flex-grow-1">Paseos</span>
                                <i class="fas fa-chevron-right ms-2 chevron"></i>
                            </button>
                            <ul class="collapse ps-4 nav flex-column" id="menuPaseos">
                                <li class="nav-item">
                                    <a class="nav-link" href="BuscarPaseadores.php">
                                        <i class="fas fa-search me-2"></i> Buscar Paseadores
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link d-flex align-items-center w-100 text-start"
                                        data-bs-toggle="collapse" data-bs-target="#menuMisPaseos" aria-expanded="false">
                                        <i class="fas fa-calendar-check me-2"></i>
                                        <span class="flex-grow-1">Mis Paseos</span>
                                        <i class="fas fa-chevron-right ms-2 chevron"></i>
                                    </button>
                                    <ul class="collapse ps-4 nav flex-column" id="menuMisPaseos">
                                        <li class="nav-item"><a class="nav-link" href="PaseosCompletados.php"><i class="fas fa-check-circle me-2"></i> Completados</a></li>
                                        <li class="nav-item"><a class="nav-link" href="PaseosPendientes.php"><i class="fas fa-hourglass-half me-2"></i> Pendientes</a></li>
                                        <li class="nav-item"><a class="nav-link" href="PaseosCancelados.php"><i class="fas fa-times-circle me-2"></i> Cancelados</a></li>
                                    </ul>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="SolicitarPaseo.php">
                                        <i class="fas fa-plus-circle me-2"></i> Solicitar Nuevo Paseo
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Pagos -->
                        <li class="nav-item">
                            <button class="nav-link d-flex align-items-center w-100 text-start"
                                data-bs-toggle="collapse" data-bs-target="#menuPagos" aria-expanded="false">
                                <i class="fas fa-credit-card me-2"></i>
                                <span class="flex-grow-1">Pagos</span>
                                <i class="fas fa-chevron-right ms-2 chevron"></i>
                            </button>
                            <ul class="collapse ps-4 nav flex-column" id="menuPagos">
                                <li class="nav-item">
                                    <!-- Enviar a Pendientes (allí hay botón Pagar con paseo_id) -->
                                    <a class="nav-link" href="PaseosPendientes.php">
                                        <i class="fas fa-wallet me-2"></i> Pagar paseo
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Notificaciones -->
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center" href="Notificaciones.php">
                                <i class="fas fa-bell me-2"></i>
                                <span>Notificaciones</span>
                            </a>
                        </li>

                        <!-- Configuración (solo Editar Perfil y Cerrar Sesión) -->
                        <li class="nav-item">
                            <button class="nav-link d-flex align-items-center w-100 text-start"
                                data-bs-toggle="collapse" data-bs-target="#menuConfig" aria-expanded="false">
                                <i class="fas fa-gear me-2"></i>
                                <span class="flex-grow-1">Configuración</span>
                                <i class="fas fa-chevron-right ms-2 chevron"></i>
                            </button>
                            <ul class="collapse ps-4 nav flex-column" id="menuConfig">
                                <li class="nav-item">
                                    <a class="nav-link" href="<?= $baseFeatures; ?>/EditarPerfil.php">
                                        <i class="fas fa-user-cog me-2"></i> Editar Perfil
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-danger" href="<?= BASE_URL; ?>/logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión
                                    </a>
                                </li>
                            </ul>
                        </li>

                    </ul>
                </div>
            </div>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Notificaciones</h1>
                    <form method="post" class="m-0">
                        <input type="hidden" name="action" value="markAllRead">
                        <button type="submit" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-check-double me-1"></i> Marcar todas como leídas
                        </button>
                    </form>
                </div>

                <form class="row g-2 mb-3" method="get">
                    <div class="col-md-4">
                        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" class="form-control" placeholder="Buscar...">
                    </div>
                    <div class="col-md-3">
                        <select name="leido" class="form-select">
                            <option value="">Todas</option>
                            <option value="0" <?= $leido === '0' ? 'selected' : '' ?>>No leídas</option>
                            <option value="1" <?= $leido === '1' ? 'selected' : '' ?>>Leídas</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i> Filtrar</button>
                    </div>
                </form>

                <?php if (empty($notificaciones)): ?>
                    <div class="alert alert-info text-center py-4">
                        <i class="fas fa-bell fa-2x mb-2 text-muted"></i>
                        <p class="mb-0">No tienes notificaciones disponibles.</p>
                    </div>
                <?php else: ?>
                    <div class="vstack gap-3 mb-4">
                        <?php foreach ($notificaciones as $n): ?>
                            <?php
                            $isRead  = (int)$n['leido'] === 1;
                            $titulo  = htmlspecialchars($n['titulo'] ?? '');
                            $msg     = htmlspecialchars($n['mensaje'] ?? '');
                            $fecha   = $n['created_at'] ? date('d/m/Y H:i', strtotime($n['created_at'])) : '';
                            ?>
                            <div class="card shadow-sm noti-card <?= $isRead ? 'read' : 'unread' ?>">
                                <div class="card-body">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div class="flex-grow-1">
                                            <h5 class="card-title mb-1">
                                                <i class="fas fa-bell text-primary me-1"></i><?= $titulo ?>
                                            </h5>
                                            <p class="mb-2 text-truncate-2"><?= $msg ?></p>
                                            <div class="text-muted small">
                                                <i class="far fa-clock me-1"></i><?= $fecha ?>
                                            </div>
                                        </div>
                                        <?php if (!$isRead): ?>
                                            <form method="post" class="ms-3">
                                                <input type="hidden" name="action" value="markRead">
                                                <input type="hidden" name="noti_id" value="<?= (int)$n['noti_id'] ?>">
                                                <button class="btn btn-sm btn-outline-primary"><i class="fas fa-check"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>