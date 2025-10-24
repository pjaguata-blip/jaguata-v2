<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/MascotaController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Controllers/NotificacionController.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\MascotaController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Controllers\NotificacionController;
use Jaguata\Helpers\Session;

AppConfig::init();

// Verificar autenticación
$authController = new AuthController();
$authController->checkRole('dueno');

// Variables base
$rolMenu = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures = BASE_URL . "/features/{$rolMenu}";

// Controladores
$mascotaController = new MascotaController();
$paseoController = new PaseoController();
$notificacionController = new NotificacionController();

// Datos
$mascotas = $mascotaController->index();
$allPaseos = $paseoController->index();
$notificaciones = $notificacionController->getRecientes();

// Filtrado de paseos
$extractMascotaId = fn($p) => $p['mascota_id'] ?? $p['id_mascota'] ?? null;
$idsMascotas = array_map(fn($m) => (int)($m['mascota_id'] ?? $m['id'] ?? 0), $mascotas);
$paseos = array_filter($allPaseos, fn($p) => in_array((int)($extractMascotaId($p) ?? 0), $idsMascotas, true));

// Estadísticas
$totalMascotas = count($mascotas);
$paseosPendientes = array_filter($paseos, fn($p) => in_array(strtolower($p['estado'] ?? ''), ['pendiente', 'confirmado']));
$paseosCompletados = array_filter($paseos, fn($p) => strtolower($p['estado'] ?? '') === 'completo');
$gastosTotales = array_sum(array_map(fn($p) => (float)($p['precio_total'] ?? 0), $paseosCompletados));
$paseosRecientes = array_slice($paseos, 0, 5);
$mascotasRecientes = array_slice($mascotas, 0, 3);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= ASSETS_URL; ?>/css/jaguata-theme.css" rel="stylesheet">

    <style>
        /* === Layout general === */
        body {
            background-color: #f5f7fa;
            font-family: "Poppins", sans-serif;
            overflow-x: hidden;
            margin: 0;
            padding: 0;
        }

        .layout {
            display: flex;
            flex-wrap: nowrap;
            width: 100%;
            min-height: 100vh;
            margin: 0;
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

        /* === Botón de menú móvil === */
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

        /* === Contenido principal === */
        main.content {
            flex-grow: 1;
            margin-left: 240px;
            background-color: #f5f7fa;
            padding: 2rem 2.5rem;
            transition: margin-left 0.3s ease;
            width: calc(100% - 240px);
        }

        @media (max-width: 768px) {
            main.content {
                margin-left: 0;
                padding: 1.5rem;
                width: 100%;
            }
        }

        /* === Cabecera de bienvenida === */
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

        .welcome-box h4 {
            font-weight: 600;
            font-size: 1.25rem;
            margin-bottom: 4px;
        }

        .welcome-box p {
            font-size: 0.95rem;
            opacity: 0.9;
        }

        /* === Tarjetas de estadísticas === */
        .stat-card {
            background: #fff;
            border-radius: 12px;
            text-align: center;
            padding: 1.2rem 0.8rem;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
            border: 1px solid #e6e6e6;
        }

        .stat-card:hover {
            transform: translateY(-4px);
        }

        .stat-icon {
            font-size: 1.8rem;
            margin-bottom: 0.4rem;
        }

        .stat-title {
            color: #6c757d;
            font-size: 0.9rem;
        }

        /* === Secciones de contenido === */
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.07);
        }

        .card-header {
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }

        /* === Tabla === */
        .table {
            font-size: 0.9rem;
        }

        .table thead {
            background: #3c6255;
            color: #fff;
        }

        .table-hover tbody tr:hover {
            background-color: #eef8f2;
        }

        /* === Panel lateral (mascotas / notificaciones) === */
        .side-panel .card-header {
            background-color: #3c6255;
        }

        .side-panel .card-header.bg-info {
            background-color: #0dcaf0 !important;
        }

        /* === Footer (opcional) === */
        footer {
            text-align: center;
            color: #777;
            font-size: 0.85rem;
            padding: 1rem 0;
            margin-top: 2rem;
        }
    </style>


</head>

<body>
    <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>

    <div class="layout">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="text-center mb-4">
                <img src="../../assets/img/logo.png" alt="Jaguata" width="120" class="mb-3">
                <hr class="text-light">
            </div>

            <ul class="nav flex-column gap-1 px-2">
                <li><a class="nav-link active" href="<?= $baseFeatures; ?>/Dashboard.php"><i class="fas fa-home"></i> Inicio</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/MiPerfil.php"><i class="fas fa-user"></i> Mi perfil</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/MisMascotas.php"><i class="fas fa-paw"></i> Mis mascotas</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/SolicitarPaseo.php"><i class="fas fa-walking"></i> Reservar paseo</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/PaseosPendientes.php"><i class="fas fa-hourglass-half"></i> Paseos pendientes</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/PaseosCompletados.php"><i class="fas fa-check-circle"></i> Paseos completados</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/PaseosCancelados.php"><i class="fas fa-times-circle"></i> Paseos cancelados</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/Notificaciones.php"><i class="fas fa-bell"></i> Notificaciones</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/GastosTotales.php"><i class="fas fa-wallet"></i> Mis gastos</a></li>
                <li><a class="nav-link text-danger" href="<?= BASE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a></li>
            </ul>
        </aside>

        <!-- Contenido -->
        <main class="content">
            <div class="welcome-box mb-4">
                <div>
                    <h4>¡Bienvenido/a a tu panel, <?= htmlspecialchars(Session::getUsuarioNombre() ?? 'Dueño/a'); ?>!</h4>
                    <p>Gestioná tus mascotas, paseos y notificaciones fácilmente 🐾</p>
                </div>
                <i class="fas fa-dog fa-3x opacity-75"></i>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-card"><i class="fas fa-paw stat-icon text-primary"></i>
                        <h5><?= $totalMascotas ?></h5>
                        <p class="stat-title">Mascotas</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card"><i class="fas fa-check-circle stat-icon text-success"></i>
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
                        <h5>₲<?= number_format($gastosTotales, 0, ',', '.') ?></h5>
                        <p class="stat-title">Gastos</p>
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
                                            <th>Mascota</th>
                                            <th>Paseador</th>
                                            <th>Inicio</th>
                                            <th>Duración</th>
                                            <th>Precio</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($paseosRecientes as $p): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($p['nombre_mascota'] ?? '-') ?></td>
                                                <td><?= htmlspecialchars($p['nombre_paseador'] ?? '-') ?></td>
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

                <div class="col-lg-4 side-panel">
                    <!-- Mis Mascotas -->
                    <div class="card mb-4">
                        <div class="card-header text-white fw-bold bg-primary">Mis Mascotas</div>
                        <div class="card-body">
                            <?php if (empty($mascotasRecientes)): ?>
                                <p class="text-center text-muted">No tienes mascotas registradas.</p>
                            <?php else: ?>
                                <?php foreach ($mascotasRecientes as $m): ?>
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-paw text-primary me-2"></i>
                                        <span><?= htmlspecialchars($m['nombre'] ?? '-') ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <div class="text-center mt-3">
                                <a href="<?= $baseFeatures; ?>/MisMascotas.php" class="btn btn-outline-primary btn-sm">Ver todas</a>
                            </div>
                        </div>
                    </div>

                    <!-- Notificaciones -->
                    <div class="card">
                        <div class="card-header text-white fw-bold bg-info">Notificaciones</div>
                        <div class="card-body">
                            <?php if (empty($notificaciones)): ?>
                                <p class="text-center text-muted mb-0">No tienes notificaciones.</p>
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