<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Controllers/NotificacionController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';
require_once __DIR__ . '/../../src/Models/Suscripcion.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Controllers\NotificacionController;
use Jaguata\Helpers\Session;
use Jaguata\Models\Suscripcion;

AppConfig::init();

$authController = new AuthController();
$authController->checkRole('paseador');

if (Session::getUsuarioEstado() !== 'aprobado') {
    Session::setError('Tu cuenta aún no fue aprobada.');
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}

function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/* Rutas + sesión */
$rolMenu       = Session::getUsuarioRol() ?: 'paseador';
$baseFeatures  = BASE_URL . "/features/{$rolMenu}";
$paseadorId    = (int)(Session::getUsuarioId() ?? 0);
$usuarioNombre = Session::getUsuarioNombre() ?? 'Paseador';

/* Datos */
$paseoController        = new PaseoController();
$notificacionController = new NotificacionController();

$paseosAsignados = $paseadorId > 0 ? ($paseoController->indexForPaseador($paseadorId) ?: []) : [];
$notificaciones  = $paseadorId > 0 ? ($notificacionController->getRecientes($paseadorId) ?: []) : [];
$tieneProActiva = false;
$subEstado      = null;
$subFin         = null;
$subInicio      = null;
$subMonto       = 50000;

try {
    if ($paseadorId > 0) {
        $subModel = new Suscripcion();

        // opcional: marca vencidas automáticamente
        if (method_exists($subModel, 'marcarVencidas')) {
            $subModel->marcarVencidas();
        }

        $ultima = $subModel->getUltimaPorPaseador($paseadorId);

        if ($ultima) {
            $subEstado = strtolower((string)($ultima['estado'] ?? ''));
            $subInicio = $ultima['inicio'] ?? null;
            $subFin    = $ultima['fin'] ?? null;
            $subMonto  = (int)($ultima['monto'] ?? 50000);

            $tieneProActiva = ($subEstado === 'activa');
        }
    }
} catch (Throwable $e) {
    $tieneProActiva = false;
}

/* ===== Cards ===== */
$totalPaseos = count($paseosAsignados);

$paseosCompletadosArr = array_filter(
    $paseosAsignados,
    fn($p) => in_array(strtolower((string)($p['estado'] ?? '')), ['completo', 'finalizado', 'completado'], true)
);

$paseosPendientesArr = array_filter(
    $paseosAsignados,
    fn($p) => in_array(strtolower((string)($p['estado'] ?? '')), ['pendiente', 'solicitado', 'confirmado', 'en_curso'], true)
);

$paseosCanceladosArr = array_filter(
    $paseosAsignados,
    fn($p) => strtolower((string)($p['estado'] ?? '')) === 'cancelado'
);

$ingresosTotales = array_sum(array_map(fn($p) => (float)($p['precio_total'] ?? 0), $paseosCompletadosArr));

/* ===== Gráficos ===== */
$porHora        = [];
$porSemana      = [];
$calificaciones = [];

foreach ($paseosAsignados as $p) {
    $inicio = $p['inicio'] ?? null;

    if ($inicio) {
        $ts   = strtotime((string)$inicio);
        $hora = date('H:00', $ts);
        $porHora[$hora] = ($porHora[$hora] ?? 0) + 1;
    }

    $estado       = strtolower((string)($p['estado'] ?? ''));
    $esCompletado = in_array($estado, ['completo', 'finalizado', 'completado'], true);

    if ($inicio && $esCompletado) {
        $ts   = strtotime((string)$inicio);
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
usort(
    $paseosAsignados,
    fn($a, $b) => strtotime((string)($b['inicio'] ?? '1970-01-01')) <=> strtotime((string)($a['inicio'] ?? '1970-01-01'))
);
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
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        html, body { overflow-x: hidden; width: 100%; }

        /* charts: alto fijo responsive */
        .chart-box { position: relative; height: 320px; }
        .chart-box-lg { position: relative; height: 360px; }
        .chart-wrap { position: relative; height: 260px; }

        @media (max-width: 768px) {
            .chart-box { height: 260px; }
            .chart-box-lg { height: 300px; }
            .chart-wrap { height: 240px; }
        }
    </style>
</head>

<body>

    <?php include __DIR__ . '/../../src/Templates/SidebarPaseador.php'; ?>

    <main>
        <div class="container-fluid px-3 px-md-2">
            <div class="header-box header-dashboard mb-3">
                <div>
                    <h1 class="fw-bold mb-1">¡Hola, <?= h($usuarioNombre); ?>! 🐾</h1>
                    <p class="mb-0">Gestioná tus paseos, disponibilidad, ganancias y estadísticas desde un solo lugar.</p>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-dog fa-2x opacity-75 d-none d-md-inline"></i>
                </div>
            </div>
            <?php if (!$tieneProActiva): ?>
                <div class="alert alert-warning border d-flex align-items-start gap-3 mb-3">
                    <i class="fa-solid fa-triangle-exclamation mt-1"></i>
                    <div class="flex-grow-1">
                        <div class="fw-bold">Tu Suscripción PRO no está activa</div>

                        <?php if ($subEstado === 'pendiente'): ?>
                            <div class="small mt-1">
                                Estado: <span class="badge bg-warning text-dark">PENDIENTE</span>.
                                Subí tu comprobante para activar el plan (₲<?= number_format((float)$subMonto, 0, ',', '.'); ?> / mes).
                            </div>
                        <?php elseif ($subEstado === 'vencida'): ?>
                            <div class="small mt-1">
                                Estado: <span class="badge bg-secondary">VENCIDA</span>.
                                Renová tu plan para seguir aceptando paseos.
                            </div>
                        <?php elseif ($subEstado === 'rechazada'): ?>
                            <div class="small mt-1">
                                Estado: <span class="badge bg-danger">RECHAZADA</span>.
                                Subí un comprobante válido o corregí la referencia de pago.
                            </div>
                        <?php elseif ($subEstado === 'cancelada'): ?>
                            <div class="small mt-1">
                                Estado: <span class="badge bg-dark">CANCELADA</span>.
                                Podés volver a suscribirte cuando quieras.
                            </div>
                        <?php else: ?>
                            <div class="small mt-1">
                                Aún no registraste una suscripción PRO. Activala para tener paseos ilimitados.
                            </div>
                        <?php endif; ?>

                        <?php if ($subFin): ?>
                            <div class="small text-muted mt-1">
                                Vence: <?= date('d/m/Y H:i', strtotime((string)$subFin)); ?>
                            </div>
                        <?php endif; ?>

                        <div class="mt-2 d-flex flex-wrap gap-2">
                            <a href="<?= $baseFeatures; ?>/Suscripcion.php" class="btn btn-warning btn-sm fw-semibold">
                                <i class="fa-solid fa-crown me-1"></i> Gestionar suscripción
                            </a>
                            <a href="<?= $baseFeatures; ?>/Suscripcion.php#subir-comprobante" class="btn btn-outline-dark btn-sm">
                                <i class="fa-solid fa-upload me-1"></i> Subir comprobante
                            </a>
                        </div>

                        <div class="small text-muted mt-2">
                            * Mientras no esté activa, podés ver tu panel, pero luego vamos a bloquear “Aceptar paseo”.
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <div class="row g-3 mb-3">
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="stat-card">
                        <i class="fas fa-list text-success"></i>
                        <h4><?= (int)$totalPaseos ?></h4>
                        <p>Paseos asignados</p>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="stat-card">
                        <i class="fas fa-check-circle text-primary"></i>
                        <h4><?= (int)count($paseosCompletadosArr) ?></h4>
                        <p>Completados</p>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="stat-card">
                        <i class="fas fa-hourglass-half text-warning"></i>
                        <h4><?= (int)count($paseosPendientesArr) ?></h4>
                        <p>Pendientes / En curso</p>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="stat-card">
                        <i class="fas fa-wallet text-info"></i>
                        <h4>₲<?= number_format((float)$ingresosTotales, 0, ',', '.'); ?></h4>
                        <p>Ingresos totales</p>
                    </div>
                </div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-12 col-lg-6">
                    <div class="section-card h-100">
                        <div class="section-header d-flex align-items-center">
                            <i class="fas fa-clock me-2"></i>
                            <span>Paseos por hora</span>
                        </div>
                        <div class="section-body">
                            <div class="chart-box">
                                <canvas id="graficoHoras"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="section-card h-100">
                        <div class="section-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-star me-2"></i>
                                <span>Distribución de calificaciones</span>
                            </div>
                            <span class="badge bg-dark text-white">
                                Promedio: <?= number_format((float)$promedioCalificacion, 1, ',', '.'); ?> ⭐
                            </span>
                        </div>

                        <div class="section-body">
                            <?php if ($totalCalifs <= 0): ?>
                                <div class="alert alert-light border text-center mb-0">
                                    <i class="fas fa-circle-info me-2"></i>
                                    Aún no tenés calificaciones registradas.
                                </div>
                            <?php else: ?>
                                <div class="chart-wrap">
                                    <canvas id="graficoCalificaciones"></canvas>
                                </div>

                                <div class="mt-3 d-flex flex-wrap gap-2 justify-content-center">
                                    <?php foreach ([5, 4, 3, 2, 1] as $s): ?>
                                        <span class="badge bg-light text-dark border">
                                            <?= (int)$s ?> ⭐: <strong><?= (int)$cantPorEstrella[$s] ?></strong>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="section-card">
                        <div class="section-header d-flex align-items-center">
                            <i class="fas fa-wallet me-2"></i>
                            <span>Ingresos por semana</span>
                        </div>
                        <div class="section-body">
                            <div class="chart-box-lg">
                                <canvas id="graficoIngresos"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-12 col-lg-8">
                    <div class="section-card">
                        <div class="section-header d-flex align-items-center">
                            <i class="fas fa-paw me-2"></i>
                            <span>Paseos recientes</span>
                        </div>
                        <div class="section-body">
                            <?php if (empty($paseosRecientes)): ?>
                                <p class="text-center text-muted mb-0">No hay paseos recientes.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle text-center mb-0">
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
                                                <?php
                                                $estadoRaw = strtolower((string)($p['estado'] ?? ''));
                                                $badge = match ($estadoRaw) {
                                                    'confirmado' => 'bg-primary',
                                                    'en_curso'   => 'bg-info text-dark',
                                                    'pendiente', 'solicitado' => 'bg-warning text-dark',
                                                    'completo', 'finalizado', 'completado' => 'bg-success',
                                                    'cancelado' => 'bg-danger',
                                                    default     => 'bg-secondary',
                                                };
                                                ?>
                                                <tr>
                                                    <td><?= h($p['dueno_nombre'] ?? '-') ?></td>
                                                    <td><?= h($p['mascota_nombre'] ?? '-') ?></td>
                                                    <td><?= !empty($p['inicio']) ? date('d/m/Y H:i', strtotime((string)$p['inicio'])) : '-' ?></td>
                                                    <td><?= h((string)($p['duracion'] ?? $p['duracion_min'] ?? '-')) ?> min</td>
                                                    <td>₲<?= number_format((float)($p['precio_total'] ?? 0), 0, ',', '.'); ?></td>
                                                    <td><span class="badge <?= $badge ?>"><?= h(ucfirst($estadoRaw ?: '-')) ?></span></td>
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
                    <div class="section-card">
                        <div class="section-header d-flex align-items-center">
                            <i class="fas fa-bell me-2"></i>
                            <span>Notificaciones</span>
                        </div>
                        <div class="section-body">
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
                                            echo $fecha ? date('d/m/Y H:i', strtotime((string)$fecha)) : '';
                                            ?>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="mt-3">
                <small>© <?= date('Y'); ?> Jaguata — Panel Paseador</small>
            </footer>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.querySelector('.sidebar');
            const btnToggle = document.getElementById('btnSidebarToggle');

            if (btnToggle && sidebar) {
                btnToggle.addEventListener('click', () => {
                    sidebar.classList.toggle('show');
                });
            }
        });
        const horas = <?= json_encode($horas, JSON_UNESCAPED_UNICODE); ?>;
        const cantidadPorHora = <?= json_encode($cantidadPorHora, JSON_UNESCAPED_UNICODE); ?>;

        const estrellas = <?= json_encode($estrellas, JSON_UNESCAPED_UNICODE); ?>;
        const cantPorEstrella = <?= json_encode($cantidadesEstrellas, JSON_UNESCAPED_UNICODE); ?>;

        const semanas = <?= json_encode($semanas, JSON_UNESCAPED_UNICODE); ?>;
        const ingresosPorSemana = <?= json_encode($ingresosPorSemana, JSON_UNESCAPED_UNICODE); ?>;

        const canvasHoras = document.getElementById('graficoHoras');
        if (canvasHoras) {
            new Chart(canvasHoras, {
                type: 'bar',
                data: {
                    labels: horas,
                    datasets: [{
                        label: 'Cantidad de paseos',
                        data: cantidadPorHora,
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true } },
                    plugins: { legend: { display: false } }
                }
            });
        }

        const ctxCalif = document.getElementById('graficoCalificaciones');
        if (ctxCalif) {
            new Chart(ctxCalif, {
                type: 'doughnut',
                data: {
                    labels: estrellas.map(e => e + ' ⭐'),
                    datasets: [{ data: cantPorEstrella }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } }
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
                    scales: { y: { beginAtZero: true } },
                    plugins: { legend: { display: false } }
                }
            });
        }
    </script>

</body>
</html>
