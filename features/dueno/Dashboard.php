<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/AuthController.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/MascotaController.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/PaseoController.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/NotificacionController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\MascotaController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Controllers\NotificacionController;

AppConfig::init();

/* 🔒 Autenticación y rol */
$auth = new AuthController();
$auth->checkRole('dueno');

/* Controladores */
$mascotaController      = new MascotaController();
$paseoController        = new PaseoController();
$notificacionController = new NotificacionController();

/* Datos */
$mascotas       = $mascotaController->index() ?: [];
$allPaseos      = $paseoController->index() ?: [];
$notificaciones = $notificacionController->getRecientes() ?: [];

/* Filtrado de paseos del dueño por sus mascotas */
$extractMascotaId = fn($p) => $p['mascota_id'] ?? $p['id_mascota'] ?? null;
$idsMascotas      = array_map(fn($m) => (int)($m['mascota_id'] ?? $m['id'] ?? 0), $mascotas);
$paseos           = array_values(array_filter($allPaseos, fn($p) => in_array((int)($extractMascotaId($p) ?? 0), $idsMascotas, true)));

/* Estadísticas */
$totalMascotas       = count($mascotas);
$paseosPendientes    = array_filter($paseos, fn($p) => in_array(strtolower($p['estado'] ?? ''), ['pendiente', 'confirmado'], true));
$paseosCompletados   = array_filter($paseos, fn($p) => strtolower($p['estado'] ?? '') === 'completo');
$gastosTotales       = array_sum(array_map(fn($p) => (float)($p['precio_total'] ?? 0), $paseosCompletados));
$paseosRecientes     = array_slice($paseos, 0, 5);
$mascotasRecientes   = array_slice($mascotas, 0, 3);

/* Rutas base */
$baseFeatures = BASE_URL . "/features/dueno";
$nombreUsuario = htmlspecialchars(Session::getUsuarioNombre() ?? 'Dueño/a');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Panel del Dueño - Jaguata</title>
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
            font-family: "Poppins", sans-serif;
            background: var(--gris-fondo);
            color: var(--gris-texto)
        }

        /* Sidebar (mismo estilo que admin) */
        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            color: var(--blanco);
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 1.5rem;
            box-shadow: 3px 0 10px rgba(0, 0, 0, .2)
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
            padding: 1.8rem 2rem;
            border-radius: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .1)
        }

        .stat-card {
            background: var(--blanco);
            border-radius: 14px;
            text-align: center;
            padding: 1.5rem 1rem;
            box-shadow: 0 3px 15px rgba(0, 0, 0, .08);
            transition: transform .2s
        }

        .stat-card:hover {
            transform: translateY(-5px)
        }

        .stat-card i {
            font-size: 2rem;
            margin-bottom: .5rem
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, .07)
        }

        .card-header {
            border-top-left-radius: 10px;
            border-top-right-radius: 10px
        }

        .table thead {
            background: var(--verde-jaguata);
            color: #fff
        }

        footer {
            text-align: center;
            padding: 1rem;
            color: #777;
            font-size: .9rem;
            margin-top: 3rem
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>

    <main>
        <!-- Encabezado -->
        <div class="welcome-box">
            <div>
                <h1 class="fw-bold">Panel del Dueño</h1>
                <p>Bienvenido, <?= $nombreUsuario; ?> 🐾</p>
            </div>
            <i class="fas fa-dog fa-3x opacity-75"></i>
        </div>

        <!-- Métricas -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fas fa-paw text-primary"></i>
                    <h4><?= $totalMascotas ?></h4>
                    <p>Mascotas</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fas fa-check-circle text-success"></i>
                    <h4><?= count($paseosCompletados) ?></h4>
                    <p>Paseos completados</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fas fa-hourglass-half text-warning"></i>
                    <h4><?= count($paseosPendientes) ?></h4>
                    <p>Paseos pendientes</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fas fa-wallet text-info"></i>
                    <h4>₲<?= number_format($gastosTotales, 0, ',', '.') ?></h4>
                    <p>Gasto total</p>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <!-- Paseos recientes -->
            <div class="col-lg-8">
                <div class="card mb-3">
                    <div class="card-header bg-success text-white fw-bold">
                        <i class="fas fa-walking me-2"></i>Paseos recientes
                    </div>
                    <div class="card-body">
                        <?php if (empty($paseosRecientes)): ?>
                            <p class="text-center text-muted mb-0">No hay paseos recientes.</p>
                        <?php else: ?>
                            <div class="table-responsive">
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
                                                <td><?= !empty($p['inicio']) ? date('d/m/Y H:i', strtotime($p['inicio'])) : '-' ?></td>
                                                <td><?= htmlspecialchars((string)($p['duracion'] ?? '-')) ?> min</td>
                                                <td>₲<?= number_format((float)($p['precio_total'] ?? 0), 0, ',', '.') ?></td>
                                                <td><span class="badge bg-secondary"><?= ucfirst($p['estado'] ?? '-') ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Panel lateral -->
            <div class="col-lg-4">
                <!-- Mis Mascotas -->
                <div class="card mb-3">
                    <div class="card-header text-white fw-bold" style="background:#0d6efd;">
                        <i class="fas fa-paw me-2"></i>Mis Mascotas
                    </div>
                    <div class="card-body">
                        <?php if (empty($mascotasRecientes)): ?>
                            <p class="text-center text-muted mb-0">No tienes mascotas registradas.</p>
                        <?php else: ?>
                            <?php foreach ($mascotasRecientes as $m): ?>
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-bone text-primary me-2"></i>
                                    <span><?= htmlspecialchars($m['nombre'] ?? '-') ?></span>
                                </div>
                            <?php endforeach; ?>
                            <div class="text-center mt-3">
                                <a href="<?= $baseFeatures; ?>/MisMascotas.php" class="btn btn-outline-primary btn-sm">Ver todas</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Notificaciones -->
                <div class="card">
                    <div class="card-header text-white fw-bold" style="background:#0dcaf0;">
                        <i class="fas fa-bell me-2"></i>Notificaciones
                    </div>
                    <div class="card-body">
                        <?php if (empty($notificaciones)): ?>
                            <p class="text-center text-muted mb-0">No tienes notificaciones.</p>
                        <?php else: ?>
                            <?php foreach ($notificaciones as $n): ?>
                                <div class="mb-3">
                                    <h6 class="fw-bold mb-1"><?= htmlspecialchars($n['titulo'] ?? '') ?></h6>
                                    <p class="mb-1"><?= htmlspecialchars($n['mensaje'] ?? '') ?></p>
                                    <small class="text-muted"><?= !empty($n['created_at']) ? date('d/m/Y H:i', strtotime($n['created_at'])) : '' ?></small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <footer><small>© <?= date('Y') ?> Jaguata — Panel del Dueño</small></footer>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>