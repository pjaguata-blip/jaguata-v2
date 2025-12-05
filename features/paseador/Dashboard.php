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

// === Inicialización ===
AppConfig::init();
$authController = new AuthController();
$authController->checkRole('paseador');

// === Variables base ===
$rolMenu       = Session::getUsuarioRol() ?: 'paseador';
$baseFeatures  = BASE_URL . "/features/{$rolMenu}";
$paseadorId    = (int) (Session::getUsuarioId() ?? 0);
$usuarioNombre = Session::getUsuarioNombre() ?? 'Paseador';

// === Controladores ===
$paseoController        = new PaseoController();
$notificacionController = new NotificacionController();

// === Datos reales ===
// Paseos asignados solo al paseador actual
$paseosAsignados = $paseadorId > 0
    ? $paseoController->indexForPaseador($paseadorId)
    : [];

// Notificaciones recientes
$notificaciones = $paseadorId > 0
    ? $notificacionController->getRecientes($paseadorId)
    : [];

// === Estadísticas básicas (cards) ===
$totalPaseos = count($paseosAsignados);

$paseosCompletadosArr = array_filter(
    $paseosAsignados,
    fn($p) => in_array(strtolower($p['estado'] ?? ''), ['completo', 'finalizado', 'completado'])
);

$paseosPendientesArr = array_filter(
    $paseosAsignados,
    fn($p) => in_array(strtolower($p['estado'] ?? ''), ['pendiente', 'confirmado', 'en_curso'])
);

$paseosCanceladosArr = array_filter(
    $paseosAsignados,
    fn($p) => strtolower($p['estado'] ?? '') === 'cancelado'
);

$ingresosTotales = array_sum(
    array_map(fn($p) => (float)($p['precio_total'] ?? 0), $paseosCompletadosArr)
);

// === Datos para GRÁFICOS (estadísticas avanzadas) ===
$porHora        = [];
$porSemana      = [];
$calificaciones = [];

foreach ($paseosAsignados as $p) {
    $fecha = isset($p['inicio']) ? new DateTime($p['inicio']) : null;

    if ($fecha) {
        $hora   = $fecha->format('H') . "h";
        $semana = "Semana " . $fecha->format('W');

        // Conteo de paseos por hora
        $porHora[$hora] = ($porHora[$hora] ?? 0) + 1;

        // Ingresos acumulados por semana
        $porSemana[$semana] = ($porSemana[$semana] ?? 0) + (float)($p['precio_total'] ?? 0);
    }

    // Calificaciones (si existen en tu consulta)
    if (isset($p['calificacion']) && is_numeric($p['calificacion'])) {
        $calificaciones[] = (int)$p['calificacion'];
    }
}

// Promedio de calificación
$promedioCalificacion = count($calificaciones)
    ? round(array_sum($calificaciones) / count($calificaciones), 1)
    : 0.0;

// Distribución por estrellas
$cantPorEstrella = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
foreach ($calificaciones as $c) {
    if (isset($cantPorEstrella[$c])) {
        $cantPorEstrella[$c]++;
    }
}

// Ordenamos por clave
ksort($porHora);
ksort($porSemana);

// Arrays para pasar a JS (Chart.js)
$horas               = array_keys($porHora);
$cantidadPorHora     = array_values($porHora);
$estrellas           = array_keys($cantPorEstrella);
$cantidadesEstrellas = array_values($cantPorEstrella);
$semanas             = array_keys($porSemana);
$ingresosPorSemana   = array_values($porSemana);

// Paseos recientes (últimos 5, ordenados por inicio)
usort(
    $paseosAsignados,
    fn($a, $b) => strtotime($b['inicio'] ?? '1970-01-01') <=> strtotime($a['inicio'] ?? '1970-01-01')
);
$paseosRecientes = array_slice($paseosAsignados, 0, 5);

// Helper
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

    <!-- CSS global -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= ASSETS_URL; ?>/css/jaguata-theme.css" rel="stylesheet">

    <!-- Chart.js para los gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <!-- Sidebar Paseador -->
    <?php include __DIR__ . '/../../src/Templates/SidebarPaseador.php'; ?>

    <!-- Contenido principal -->
    <main class="bg-light">
        <div class="container-fluid py-4">

            <!-- Header -->
            <div class="header-box header-dashboard mb-4">
                <div>
                    <h1>¡Hola, <?= h($usuarioNombre); ?>! 🐾</h1>
                    <p>Gestioná tus paseos, disponibilidad, ganancias y estadísticas desde un solo lugar.</p>
                </div>
                <i class="fas fa-dog fa-3x opacity-75"></i>
            </div>

            <!-- Estadísticas (cards) -->
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
                        <h4><?= count($paseosCompletadosArr) ?></h4>
                        <p>Completados</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="fas fa-hourglass-half text-warning"></i>
                        <h4><?= count($paseosPendientesArr) ?></h4>
                        <p>Pendientes / En curso</p>
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

            <!-- BLOQUE DE ESTADÍSTICAS AVANZADAS (gráficos) -->
            <div class="row g-4 mb-4">
                <!-- Paseos por hora -->
                <div class="col-lg-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-success text-white fw-semibold">
                            <i class="fas fa-clock me-2"></i>Paseos por hora
                        </div>
                        <div class="card-body">
                            <canvas id="graficoHoras"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Calificaciones -->
                <div class="col-lg-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-warning text-dark fw-semibold">
                            <i class="fas fa-star me-2"></i>Distribución de calificaciones
                        </div>
                        <div class="card-body">
                            <canvas id="graficoCalificaciones"></canvas>
                            <p class="mt-3 mb-0 text-muted">
                                Promedio: <strong><?= $promedioCalificacion; ?></strong> ⭐
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Ingresos por semana -->
                <div class="col-lg-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-info text-white fw-semibold">
                            <i class="fas fa-wallet me-2"></i>Ingresos por semana
                        </div>
                        <div class="card-body">
                            <canvas id="graficoIngresos"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenido principal (paseos + notificaciones) -->
            <div class="row">
                <!-- Paseos recientes -->
                <div class="col-lg-8">
                    <div class="card shadow-sm mb-4">
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
                                                    <td>
                                                        <?php
                                                        $inicio = $p['inicio'] ?? null;
                                                        echo $inicio
                                                            ? date('d/m/Y H:i', strtotime($inicio))
                                                            : '-';
                                                        ?>
                                                    </td>
                                                    <td><?= h((string)($p['duracion'] ?? '-')) ?> min</td>
                                                    <td>₲<?= number_format((float)($p['precio_total'] ?? 0), 0, ',', '.') ?></td>
                                                    <td>
                                                        <span class="badge bg-secondary">
                                                            <?= ucfirst(strtolower($p['estado'] ?? '-')) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Panel lateral: Notificaciones -->
                <div class="col-lg-4">
                    <div class="card shadow-sm mb-4">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // === Datos enviados desde PHP ===
        const horas = <?= json_encode($horas); ?>;
        const cantidadPorHora = <?= json_encode($cantidadPorHora); ?>;
        const estrellas = <?= json_encode($estrellas); ?>;
        const cantPorEstrella = <?= json_encode($cantidadesEstrellas); ?>;
        const semanas = <?= json_encode($semanas); ?>;
        const ingresosPorSemana = <?= json_encode($ingresosPorSemana); ?>;

        // Gráfico: Paseos por hora
        if (document.getElementById('graficoHoras')) {
            new Chart(document.getElementById('graficoHoras'), {
                type: 'bar',
                data: {
                    labels: horas,
                    datasets: [{
                        label: 'Cantidad de paseos',
                        data: cantidadPorHora,
                        backgroundColor: '#20c997'
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // Gráfico: Distribución de calificaciones
        if (document.getElementById('graficoCalificaciones')) {
            new Chart(document.getElementById('graficoCalificaciones'), {
                type: 'pie',
                data: {
                    labels: estrellas.map(e => e + ' ⭐'),
                    datasets: [{
                        data: cantPorEstrella,
                        backgroundColor: ['#FFD700', '#FFC107', '#FF9800', '#FF7043', '#E0E0E0']
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        // Gráfico: Ingresos por semana
        if (document.getElementById('graficoIngresos')) {
            new Chart(document.getElementById('graficoIngresos'), {
                type: 'line',
                data: {
                    labels: semanas,
                    datasets: [{
                        label: 'Ingresos (₲)',
                        data: ingresosPorSemana,
                        fill: true,
                        borderColor: '#3c6255',
                        backgroundColor: 'rgba(60, 98, 85, 0.1)',
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
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