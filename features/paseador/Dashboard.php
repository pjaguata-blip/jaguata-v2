<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Controllers/NotificacionController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Controllers\NotificacionController;
use Jaguata\Helpers\Session;

// === Inicialización ===
AppConfig::init();
$authController = new AuthController();
$authController->checkRole('paseador');

// === Variables base ===
$rolMenu = Session::getUsuarioRol() ?: 'paseador';
$baseFeatures = BASE_URL . "/features/{$rolMenu}";
$paseadorId = Session::getUsuarioId();

// === Controladores ===
$paseoController = new PaseoController();
$notificacionController = new NotificacionController();

// === Datos reales ===
// Obtener paseos solo del paseador actual
$paseosAsignados = $paseoController->indexForPaseador($paseadorId);

// Notificaciones recientes
$notificaciones = $notificacionController->getRecientes($paseadorId) ?? [];

// === Estadísticas ===
$totalPaseos = count($paseosAsignados);
$paseosCompletados = array_filter($paseosAsignados, fn($p) => in_array(strtolower($p['estado'] ?? ''), ['completo', 'finalizado']));
$paseosPendientes = array_filter($paseosAsignados, fn($p) => in_array(strtolower($p['estado'] ?? ''), ['pendiente', 'confirmado']));
$paseosCancelados = array_filter($paseosAsignados, fn($p) => strtolower($p['estado'] ?? '') === 'cancelado');
$ingresosTotales = array_sum(array_map(fn($p) => (float)($p['precio_total'] ?? 0), $paseosCompletados));

// Paseos recientes (últimos 5)
usort($paseosAsignados, fn($a, $b) => strtotime($b['inicio'] ?? '') <=> strtotime($a['inicio'] ?? ''));
$paseosRecientes = array_slice($paseosAsignados, 0, 5);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Paseador - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f7fa;
            font-family: "Poppins", sans-serif;
            overflow-x: hidden;
        }

        .layout {
            display: flex;
            flex-wrap: nowrap;
            width: 100%;
            min-height: 100vh;
        }

        /* === Sidebar === */
        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            color: #f8f9fa;
            width: 240px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 1.5rem;
            box-shadow: 4px 0 12px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            transition: transform 0.3s ease-in-out;
        }

        .sidebar .nav-link {
            color: #ddd;
            display: flex;
            align-items: center;
            padding: 10px 16px;
            border-radius: 8px;
            margin: 4px 8px;
            transition: background 0.2s, transform 0.2s;
            font-weight: 500;
        }

        .sidebar .nav-link i {
            width: 22px;
            margin-right: 10px;
        }

        .sidebar .nav-link:hover {
            background-color: #343454;
            color: #fff;
            transform: translateX(4px);
        }

        .sidebar .nav-link.active {
            background: #3c6255;
            color: #fff;
        }

        /* === Botón móvil === */
        .menu-toggle {
            display: none;
            position: fixed;
            top: 16px;
            left: 16px;
            background-color: #1e1e2f;
            color: white;
            border: none;
            padding: 8px 10px;
            border-radius: 6px;
            z-index: 1100;
        }

        @media (max-width: 768px) {
            .menu-toggle {
                display: block;
            }

            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }
        }

        /* === Main === */
        main.content {
            flex-grow: 1;
            margin-left: 240px;
            background-color: #f5f7fa;
            padding: 2rem 2.5rem;
            width: calc(100% - 240px);
            transition: margin-left 0.3s ease;
        }

        @media (max-width: 768px) {
            main.content {
                margin-left: 0;
                padding: 1.5rem;
                width: 100%;
            }
        }

        /* === Header === */
        .welcome-box {
            background: linear-gradient(90deg, #20c997, #3c6255);
            color: #fff;
            padding: 1.5rem 2rem;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .stat-card {
            background: #fff;
            border-radius: 12px;
            text-align: center;
            padding: 1.2rem 0.8rem;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.05);
            border: 1px solid #e6e6e6;
        }

        .stat-icon {
            font-size: 1.8rem;
            margin-bottom: 0.4rem;
        }

        .stat-title {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .table thead {
            background: #3c6255;
            color: #fff;
        }

        .table-hover tbody tr:hover {
            background-color: #eef8f2;
        }

        footer {
            text-align: center;
            color: #777;
            font-size: 0.85rem;
            padding: 1rem 0;
            margin-top: 2rem;
        }

        .badge {
            display: inline-block;
            min-width: 90px;
            /* fuerza un ancho mínimo */
            text-align: center;
            font-size: 0.9rem;
            padding: 0.45em 0.75em;
            border-radius: 10px;
            font-weight: 500;
        }
    </style>
</head>

<body>
    <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>

    <div class="layout">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="text-center mb-4">
                <img src="<?= ASSETS_URL; ?>/uploads/perfiles/logojag.png" alt="Logo" width="50">
                <hr class="text-light">
            </div>

            <ul class="nav flex-column gap-1 px-2">
                <li><a class="nav-link active" href="<?= $baseFeatures; ?>/Dashboard.php"><i class="fas fa-home"></i> Inicio</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/Solicitudes.php"><i class="fas fa-envelope-open-text"></i> Solicitudes</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/MisPaseos.php"><i class="fas fa-list"></i> Mis Paseos</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/Disponibilidad.php"><i class="fas fa-calendar-check"></i> Disponibilidad</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/Pagos.php"><i class="fas fa-wallet"></i> Pagos</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/Estadisticas.php"><i class="fas fa-chart-line"></i> Estadísticas</a></li>
                <li><a class="nav-link" href="../mensajeria/chat.php"><i class="fas fa-comments"></i> Mensajería</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/Notificaciones.php"><i class="fas fa-bell"></i> Notificaciones</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/Perfil.php"><i class="fas fa-user"></i> Mi Perfil</a></li>
                <li><a class="nav-link text-danger" href="<?= BASE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a></li>
            </ul>
        </aside>

        <!-- Contenido -->
        <main class="content">
            <div class="welcome-box mb-4">
                <div>
                    <h1>¡Bienvenido/a, <?= htmlspecialchars(Session::getUsuarioNombre() ?? 'Paseador'); ?>!</h1>
                    <p>Gestioná tus paseos, disponibilidad y ganancias 🐾</p>
                </div>
                <i class="fas fa-dog fa-3x opacity-75"></i>
            </div>

            <!-- Estadísticas -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-card"><i class="fas fa-list stat-icon text-success"></i>
                        <h5><?= $totalPaseos ?></h5>
                        <p class="stat-title">Paseos asignados</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card"><i class="fas fa-check-circle stat-icon text-primary"></i>
                        <h5><?= count($paseosCompletados) ?></h5>
                        <p class="stat-title">Completados</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card"><i class="fas fa-hourglass-half stat-icon text-warning"></i>
                        <h5><?= count($paseosPendientes) ?></h5>
                        <p class="stat-title">Pendientes</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card"><i class="fas fa-wallet stat-icon text-info"></i>
                        <h5>₲<?= number_format($ingresosTotales, 0, ',', '.') ?></h5>
                        <p class="stat-title">Ingresos totales</p>
                    </div>
                </div>
            </div>

            <!-- Paseos recientes -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white fw-bold">Paseos recientes</div>
                        <div class="card-body">
                            <?php if (empty($paseosRecientes)): ?>
                                <p class="text-center text-muted mb-0">No hay paseos recientes.</p>
                            <?php else: ?>
                                <table class="table table-hover align-middle text-center">
                                    <thead>
                                        <tr>
                                            <th>Dueño</th>
                                            <th>Mascota</th>
                                            <th>Inicio</th>
                                            <th>Duración</th>
                                            <th>Precio</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($paseosRecientes as $p): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($p['nombre_dueno'] ?? '-') ?></td>
                                                <td><?= htmlspecialchars($p['nombre_mascota'] ?? '-') ?></td>
                                                <td><?= date('d/m/Y H:i', strtotime($p['inicio'] ?? '')) ?></td>
                                                <td><?= ($p['duracion'] ?? '-') ?> min</td>
                                                <td>₲<?= number_format((float)($p['precio_total'] ?? 0), 0, ',', '.') ?></td>
                                                <td><span class="badge bg-secondary"><?= ucfirst($p['estado'] ?? '-') ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Panel lateral -->
                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white fw-bold">Notificaciones</div>
                        <div class="card-body">
                            <?php if (empty($notificaciones)): ?>
                                <p class="text-center text-muted mb-0">No hay notificaciones recientes.</p>
                            <?php else: ?>
                                <?php foreach ($notificaciones as $n): ?>
                                    <div class="mb-3">
                                        <h6 class="fw-bold mb-1"><?= htmlspecialchars($n['titulo'] ?? '') ?></h6>
                                        <p class="mb-1"><?= htmlspecialchars($n['mensaje'] ?? '') ?></p>
                                        <small class="text-muted"><?= date('d/m/Y H:i', strtotime($n['created_at'] ?? '')) ?></small>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const toggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        toggle.addEventListener('click', () => sidebar.classList.toggle('show'));
    </script>
</body>

</html>