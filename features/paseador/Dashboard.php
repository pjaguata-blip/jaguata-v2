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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --verde-jaguata: #3c6255;
            --verde-claro: #20c997;
            --gris-fondo: #f4f6f9;
            --gris-texto: #555;
            --blanco: #fff;
        }

        body {
            font-family: "Poppins", sans-serif;
            background-color: var(--gris-fondo);
            color: var(--gris-texto);
            overflow-x: hidden;
        }

        .layout {
            display: flex;
            flex-wrap: nowrap;
            width: 100%;
            min-height: 100vh;
        }

        /* ===== SIDEBAR (MISMO QUE ADMIN) ===== */
        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            color: var(--blanco);
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 1.5rem;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            transition: transform 0.3s ease-in-out;
        }

        .sidebar .nav-link {
            color: #ccc;
            display: flex;
            align-items: center;
            gap: .8rem;
            padding: 12px 18px;
            border-radius: 8px;
            margin: 6px 10px;
            transition: all .2s ease;
            font-size: 0.95rem;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: var(--verde-claro);
            color: var(--blanco);
            transform: translateX(4px);
        }

        /* ===== BOTÓN MÓVIL ===== */
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

        /* ===== MAIN (MISMO QUE ADMIN) ===== */
        main.content {
            flex-grow: 1;
            margin-left: 250px;
            padding: 2rem;
            width: calc(100% - 250px);
            transition: margin-left 0.3s ease;
        }

        @media (max-width: 768px) {
            main.content {
                margin-left: 0;
                padding: 1.5rem;
                width: 100%;
            }
        }

        /* ===== HEADER / WELCOME BOX ===== */
        .welcome-box {
            background: linear-gradient(90deg, var(--verde-claro), var(--verde-jaguata));
            color: var(--blanco);
            padding: 1.8rem 2rem;
            border-radius: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .welcome-box h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: .25rem;
        }

        .welcome-box p {
            margin: 0;
            font-size: 0.95rem;
        }

        /* ===== STAT CARDS (IGUAL QUE ADMIN) ===== */
        .stat-card {
            background: var(--blanco);
            border-radius: 14px;
            text-align: center;
            padding: 1.5rem 1rem;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card i {
            font-size: 2rem;
            margin-bottom: .5rem;
        }

        .stat-card h4 {
            font-size: 1.6rem;
            font-weight: 600;
            margin-bottom: .25rem;
        }

        .stat-card p {
            margin: 0;
            font-size: 0.9rem;
            color: #777;
        }

        /* ===== TABLAS / CARDS ===== */
        .table thead {
            background: var(--verde-jaguata);
            color: var(--blanco);
        }

        .table-hover tbody tr:hover {
            background-color: #eef8f2;
        }

        .badge {
            display: inline-block;
            min-width: 90px;
            text-align: center;
            font-size: 0.9rem;
            padding: 0.45em 0.75em;
            border-radius: 10px;
            font-weight: 500;
        }

        .card-header {
            font-weight: 600;
            font-size: 1rem;
        }

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
        <?php include __DIR__ . '/../../src/Templates/SidebarPaseador.php'; ?>

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
                    <div class="stat-card">
                        <i class="fas fa-list text-success"></i>
                        <h4><?= $totalPaseos ?></h4>
                        <p>Paseos asignados</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="fas fa-check-circle text-primary"></i>
                        <h4><?= count($paseosCompletados) ?></h4>
                        <p>Completados</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="fas fa-hourglass-half text-warning"></i>
                        <h4><?= count($paseosPendientes) ?></h4>
                        <p>Pendientes</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="fas fa-wallet text-info"></i>
                        <h4>₲<?= number_format($ingresosTotales, 0, ',', '.') ?></h4>
                        <p>Ingresos totales</p>
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