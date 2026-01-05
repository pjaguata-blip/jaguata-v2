<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Controllers/NotificacionController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Controllers\NotificacionController;
use Jaguata\Helpers\Session;

AppConfig::init();

$authController = new AuthController();
$authController->checkRole('paseador');
/* 🔒 BLOQUEO POR ESTADO (MUY IMPORTANTE) */
if (Session::getUsuarioEstado() !== 'aprobado') {
    Session::setError('Tu cuenta aún no fue aprobada.');
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}

$rolMenu       = Session::getUsuarioRol() ?: 'paseador';
$baseFeatures  = BASE_URL . "/features/{$rolMenu}";
$paseadorId    = (int) (Session::getUsuarioId() ?? 0);
$usuarioNombre = Session::getUsuarioNombre() ?? 'Paseador';

$paseoController        = new PaseoController();
$notificacionController = new NotificacionController();

$paseosAsignados = $paseadorId > 0 ? $paseoController->indexForPaseador($paseadorId) : [];
$notificaciones = $paseadorId > 0 ? $notificacionController->getRecientes($paseadorId) : [];

/* ===== Cards ===== */
$totalPaseos = count($paseosAsignados);

$paseosCompletadosArr = array_filter(
    $paseosAsignados,
    fn($p) => in_array(strtolower($p['estado'] ?? ''), ['completo', 'finalizado', 'completado'], true)
);

$paseosPendientesArr = array_filter(
    $paseosAsignados,
    fn($p) => in_array(strtolower($p['estado'] ?? ''), ['pendiente', 'confirmado', 'en_curso'], true)
);

$paseosCanceladosArr = array_filter(
    $paseosAsignados,
    fn($p) => strtolower($p['estado'] ?? '') === 'cancelado'
);

$ingresosTotales = array_sum(array_map(fn($p) => (float)($p['precio_total'] ?? 0), $paseosCompletadosArr));

/* ===== Gráficos ===== */
$porHora = [];
$porSemana = [];
$calificaciones = [];

foreach ($paseosAsignados as $p) {
    $inicio = $p['inicio'] ?? null;

    if ($inicio) {
        $ts = strtotime($inicio);
        $hora = date('H:00', $ts);
        $porHora[$hora] = ($porHora[$hora] ?? 0) + 1;
    }

    $estado = strtolower((string)($p['estado'] ?? ''));
    $esCompletado = in_array($estado, ['completo', 'finalizado', 'completado'], true);

    if ($inicio && $esCompletado) {
        $ts   = strtotime($inicio);
        $anio = date('o', $ts);
        $sem  = date('W', $ts);
        $key  = "{$anio}-W{$sem}";
        $porSemana[$key] = ($porSemana[$key] ?? 0) + (float)($p['precio_total'] ?? 0);
    }

    $valor = $p['calificacion'] ?? $p['rating'] ?? $p['estrellas'] ?? null;
    if ($valor !== null && is_numeric($valor)) {
        $c = (int)$valor;
        if ($c >= 1 && $c <= 5) $calificaciones[] = $c;
    }
}

ksort($porHora);
ksort($porSemana);

$promedioCalificacion = count($calificaciones) ? round(array_sum($calificaciones) / count($calificaciones), 1) : 0.0;

$cantPorEstrella = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
foreach ($calificaciones as $c) $cantPorEstrella[$c]++;

$totalCalifs = array_sum($cantPorEstrella);

$horas               = array_keys($porHora);
$cantidadPorHora     = array_values($porHora);

$estrellas           = array_keys($cantPorEstrella);
$cantidadesEstrellas = array_values($cantPorEstrella);

$semanas           = array_keys($porSemana);
$ingresosPorSemana = array_values($porSemana);

/* Recientes */
usort($paseosAsignados, fn($a, $b) => strtotime($b['inicio'] ?? '1970-01-01') <=> strtotime($a['inicio'] ?? '1970-01-01'));
$paseosRecientes = array_slice($paseosAsignados, 0, 5);

function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Paseador - Jaguata</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= ASSETS_URL; ?>/css/jaguata-theme.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- ✅ SOLO estilos necesarios para responsive -->
    <style>
        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* overlay */
        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .45);
            z-index: 998;
        }

        .sidebar-backdrop.show {
            display: block;
        }

        /* topbar mobile */
        .topbar-mobile {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 56px;
            background: #1e1e2f;
            color: #fff;
            z-index: 999;
            padding: 0 .9rem;
            align-items: center;
            justify-content: space-between;
        }

        .topbar-mobile .btn {
            border: none;
            background: transparent;
            color: #fff;
            font-size: 1.6rem;
        }

        /* charts */
        .chart-box {
            position: relative;
            height: 320px;
        }

        .chart-box-lg {
            position: relative;
            height: 360px;
        }

        .chart-wrap {
            position: relative;
            height: 260px;
        }

        @media (max-width: 992px) {
            .topbar-mobile {
                display: flex;
            }

            main {
                margin-left: 0 !important;
                margin-top: 70px;
                padding: 1rem !important;
            }
        }

        @media (max-width: 768px) {
            .chart-box {
                height: 260px;
            }

            .chart-box-lg {
                height: 300px;
            }

            .chart-wrap {
                height: 240px;
            }
        }
    </style>
</head>

<body>

    <div class="layout">
        <?php include __DIR__ . '/../../src/Templates/SidebarPaseador.php'; ?>

        <main class="bg-light">
            <div class="container-fluid py-2">

                <!-- Header -->
                <div class="header-box header-dashboard mb-1">
                    <div>
                        <h1>¡Hola, <?= h($usuarioNombre); ?>! 🐾</h1>
                        <p>Gestioná tus paseos, disponibilidad, ganancias y estadísticas desde un solo lugar.</p>
                    </div>
                    <i class="fas fa-dog fa-3x opacity-75"></i>
                </div>

                <!-- Cards -->
                <div class="row g-3 mb-2">
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="stat-card">
                            <i class="fas fa-list text-success"></i>
                            <h4><?= $totalPaseos ?></h4>
                            <p>Paseos asignados</p>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="stat-card">
                            <i class="fas fa-check-circle text-primary"></i>
                            <h4><?= count($paseosCompletadosArr) ?></h4>
                            <p>Completados</p>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="stat-card">
                            <i class="fas fa-hourglass-half text-warning"></i>
                            <h4><?= count($paseosPendientesArr) ?></h4>
                            <p>Pendientes / En curso</p>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="stat-card">
                            <i class="fas fa-wallet text-info"></i>
                            <h4>₲<?= number_format($ingresosTotales, 0, ',', '.') ?></h4>
                            <p>Ingresos totales</p>
                        </div>
                    </div>
                </div>

                <!-- Gráficos -->
                <div class="row g-4 mb-4">
                    <div class="col-12 col-lg-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-success text-white fw-semibold">
                                <i class="fas fa-clock me-2"></i>Paseos por hora
                            </div>
                            <div class="card-body">
                                <div class="chart-box">
                                    <canvas id="graficoHoras"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-warning text-dark fw-semibold d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <span><i class="fas fa-star me-2"></i>Distribución de calificaciones</span>
                                <span class="badge bg-dark text-white">
                                    Promedio: <?= number_format((float)$promedioCalificacion, 1, ',', '.'); ?> ⭐
                                </span>
                            </div>

                            <div class="card-body d-flex flex-column">
                                <?php if ($totalCalifs <= 0): ?>
                                    <div class="alert alert-light border text-center mb-0">
                                        <i class="fas fa-circle-info me-2"></i>
                                        Aún no tenés calificaciones registradas.
                                    </div>
                                <?php else: ?>
                                    <div class="chart-wrap flex-grow-1">
                                        <canvas id="graficoCalificaciones"></canvas>
                                    </div>

                                    <div class="mt-3 d-flex flex-wrap gap-2 justify-content-center">
                                        <?php foreach ([5, 4, 3, 2, 1] as $s): ?>
                                            <span class="badge bg-light text-dark border">
                                                <?= $s ?> ⭐: <strong><?= (int)$cantPorEstrella[$s] ?></strong>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-info text-white fw-semibold">
                                <i class="fas fa-wallet me-2"></i>Ingresos por semana
                            </div>
                            <div class="card-body">
                                <div class="chart-box-lg">
                                    <canvas id="graficoIngresos"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla + Notificaciones -->
                <div class="row g-4">
                    <div class="col-12 col-lg-8">
                        <div class="card shadow-sm">
                            <div class="card-header bg-success text-white fw-semibold">
                                <i class="fas fa-paw me-2"></i>Paseos recientes
                            </div>
                            <div class="card-body">
                                <?php if (empty($paseosRecientes)): ?>
                                    <p class="text-center text-muted mb-0">No hay paseos recientes.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
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
                                                        <td><?= h($p['dueno_nombre'] ?? '-') ?></td>
                                                        <td><?= h($p['mascota_nombre'] ?? '-') ?></td>
                                                        <td><?= !empty($p['inicio']) ? date('d/m/Y H:i', strtotime($p['inicio'])) : '-' ?></td>
                                                        <td><?= h((string)($p['duracion'] ?? $p['duracion_min'] ?? '-')) ?> min</td>
                                                        <td>₲<?= number_format((float)($p['precio_total'] ?? 0), 0, ',', '.') ?></td>
                                                        <td><span class="badge bg-secondary"><?= ucfirst(strtolower($p['estado'] ?? '-')) ?></span></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-info text-white fw-semibold">
                                <i class="fas fa-bell me-2"></i>Notificaciones
                            </div>
                            <div class="card-body">
                                <?php if (empty($notificaciones)): ?>
                                    <p class="text-center text-muted mb-0">No hay notificaciones recientes.</p>
                                <?php else: ?>
                                    <?php foreach ($notificaciones as $n): ?>
                                        <div class="mb-3 border-bottom pb-2">
                                            <h6 class="fw-bold mb-1"><?= h($n['titulo'] ?? '') ?></h6>
                                            <p class="mb-1 small"><?= h($n['mensaje'] ?? '') ?></p>
                                            <small class="text-muted">
                                                <?php
                                                $fecha = $n['created_at'] ?? $n['fecha'] ?? null;
                                                echo $fecha ? date('d/m/Y H:i', strtotime($fecha)) : '';
                                                ?>
                                            </small>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <footer class="text-center text-muted small mt-4">
                    &copy; <?= date('Y'); ?> Jaguata. Todos los derechos reservados.
                </footer>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        /* ===== Sidebar responsive ===== */
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        const btn = document.getElementById('toggleSidebar');

        function openSidebar() {
            sidebar?.classList.add('sidebar-open');
            backdrop?.classList.add('show');
        }

        function closeSidebar() {
            sidebar?.classList.remove('sidebar-open');
            backdrop?.classList.remove('show');
        }
        btn?.addEventListener('click', () => {
            if (sidebar?.classList.contains('sidebar-open')) closeSidebar();
            else openSidebar();
        });
        backdrop?.addEventListener('click', closeSidebar);

        /* ===== Datos PHP ===== */
        const horas = <?= json_encode($horas); ?>;
        const cantidadPorHora = <?= json_encode($cantidadPorHora); ?>;

        const estrellas = <?= json_encode($estrellas); ?>;
        const cantPorEstrella = <?= json_encode($cantidadesEstrellas); ?>;

        const semanas = <?= json_encode($semanas); ?>;
        const ingresosPorSemana = <?= json_encode($ingresosPorSemana); ?>;

        /* ===== Charts (responsive real) ===== */
        const canvasHoras = document.getElementById('graficoHoras');
        if (canvasHoras) {
            new Chart(canvasHoras, {
                type: 'bar',
                data: {
                    labels: horas,
                    datasets: [{
                        label: 'Cantidad de paseos',
                        data: cantidadPorHora
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        const ctxCalif = document.getElementById('graficoCalificaciones');
        if (ctxCalif) {
            new Chart(ctxCalif, {
                type: 'doughnut',
                data: {
                    labels: estrellas.map(e => e + ' ⭐'),
                    datasets: [{
                        data: cantPorEstrella
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        const canvasIng = document.getElementById('graficoIngresos');
        if (canvasIng) {
            new Chart(canvasIng, {
                type: 'line',
                data: {
                    labels: semanas,
                    datasets: [{
                        label: 'Ingresos (₲)',
                        data: ingresosPorSemana,
                        fill: true,
                        tension: .3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    </script>

</body>

</html>