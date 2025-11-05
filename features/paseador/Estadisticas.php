<?php

declare(strict_types=1);

$rootPath = realpath(__DIR__ . '/../../');
require_once $rootPath . '/vendor/autoload.php';
require_once $rootPath . '/src/Config/AppConfig.php';
require_once $rootPath . '/src/Controllers/AuthController.php';
require_once $rootPath . '/src/Controllers/PaseoController.php';
require_once $rootPath . '/src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Helpers\Session;

// ==== Inicialización ====
AppConfig::init();

$auth = new AuthController();
$auth->checkRole('paseador');

// ==== Datos del usuario logueado ====
$usuario = Session::get('usuario') ?? [];
$usuarioNombre = htmlspecialchars($usuario['nombre'] ?? 'Paseador');
$paseadorId = (int)($usuario['usu_id'] ?? Session::get('usuario_id') ?? 0);

// ==== Controlador ====
$paseoController = new PaseoController();
$paseos = $paseoController->indexForPaseador($paseadorId);

// ==== Si no hay paseos ====
if (empty($paseos)) {
    $paseos = [];
}

// ==== Estadísticas ====
$paseosCompletados = 0;
$paseosCancelados = 0;
$ingresosTotales = 0;
$calificaciones = [];
$porHora = [];
$porSemana = [];

foreach ($paseos as $p) {
    $estado = strtolower($p['estado'] ?? '');
    $fecha = isset($p['inicio']) ? new DateTime($p['inicio']) : null;
    $hora = $fecha ? $fecha->format('H') . "h" : '—';
    $semana = $fecha ? "Semana " . $fecha->format('W') : '—';

    // Contar por estado
    if ($estado === 'completo' || $estado === 'finalizado') {
        $paseosCompletados++;
        $ingresosTotales += (float)($p['precio_total'] ?? 0);
    } elseif ($estado === 'cancelado') {
        $paseosCancelados++;
    }

    // Agrupar por hora
    if ($hora !== '—') {
        $porHora[$hora] = ($porHora[$hora] ?? 0) + 1;
    }

    // Agrupar por semana
    if ($semana !== '—') {
        $porSemana[$semana] = ($porSemana[$semana] ?? 0) + (float)($p['precio_total'] ?? 0);
    }

    // Calificación (si existe)
    if (isset($p['calificacion']) && is_numeric($p['calificacion'])) {
        $calificaciones[] = (int)$p['calificacion'];
    }
}

// ==== Promedio de calificación ====
$promedioCalificacion = count($calificaciones) ? round(array_sum($calificaciones) / count($calificaciones), 1) : 0.0;

// ==== Distribución de calificaciones ====
$cantPorEstrella = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
foreach ($calificaciones as $c) {
    if (isset($cantPorEstrella[$c])) {
        $cantPorEstrella[$c]++;
    }
}

// ==== Preparar datos para gráficos ====
ksort($porHora);
ksort($porSemana);

$horas = array_keys($porHora);
$cantidadPorHora = array_values($porHora);

$estrellas = array_keys($cantPorEstrella);
$cantidadesEstrellas = array_values($cantPorEstrella);

$semanas = array_keys($porSemana);
$ingresosPorSemana = array_values($porSemana);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas - Paseador | Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f7fa;
            font-family: "Poppins", sans-serif;
        }

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
            font-weight: 500;
            transition: all 0.2s ease-in-out;
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

        .page-header {
            background: linear-gradient(90deg, #20c997, #3c6255);
            color: #fff;
            padding: 1.5rem 2rem;
            border-radius: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, .08);
            margin-bottom: 2rem;
        }

        .page-header h2 {
            font-weight: 600;
            margin: 0;
        }

        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
        }

        .card h5 {
            color: #3c6255;
            font-weight: 600;
        }

        .card h2,
        .card h3 {
            color: #20c997;
            font-weight: 700;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="text-center mb-4">
                    <img src="<?= ASSETS_URL; ?>/uploads/perfiles/logojag.png" alt="Jaguata" width="50">
                    <hr class="text-light">
                </div>
                <ul class="nav flex-column gap-1 px-2">
                    <li><a class="nav-link" href="Dashboard.php"><i class="fas fa-home"></i> Inicio</a></li>
                    <li><a class="nav-link" href="MisPaseos.php"><i class="fas fa-list"></i> Mis Paseos</a></li>
                    <li><a class="nav-link" href="Disponibilidad.php"><i class="fas fa-calendar-check"></i> Disponibilidad</a></li>
                    <li><a class="nav-link" href="Perfil.php"><i class="fas fa-user"></i> Mi Perfil</a></li>
                    <li><a class="nav-link active" href="Estadisticas.php"><i class="fas fa-chart-line"></i> Estadísticas</a></li>
                    <li><a class="nav-link text-danger" href="../../logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a></li>
                </ul>
            </div>

            <!-- Contenido principal -->

            <div class="page-header">
                <h2><i class="fas fa-chart-line me-2"></i> Estadísticas del Paseador</h2>
                <span class="fw-bold">🐾 <?= $usuarioNombre ?></span>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card text-center p-3">
                        <h5 class="text-muted">Paseos Completados</h5>
                        <h2><?= $paseosCompletados ?></h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center p-3">
                        <h5 class="text-muted">Paseos Cancelados</h5>
                        <h2 class="text-danger"><?= $paseosCancelados ?></h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center p-3">
                        <h5 class="text-muted">Ingresos Totales</h5>
                        <h3>Gs <?= number_format($ingresosTotales, 0, ',', '.') ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center p-3">
                        <h5 class="text-muted">Calificación Promedio</h5>
                        <h3 class="text-warning"><?= $promedioCalificacion ?> <i class="fa-solid fa-star"></i></h3>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card p-3">
                        <h5 class="text-success mb-3"><i class="fa-solid fa-clock me-1"></i> Paseos por hora</h5>
                        <canvas id="graficoHoras"></canvas>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card p-3">
                        <h5 class="text-success mb-3"><i class="fa-solid fa-star me-1"></i> Calificaciones</h5>
                        <canvas id="graficoCalificaciones"></canvas>
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="card p-3">
                        <h5 class="text-success mb-3"><i class="fa-solid fa-wallet me-1"></i> Ingresos por semana</h5>
                        <canvas id="graficoIngresos"></canvas>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const horas = <?= json_encode($horas) ?>;
        const cantidadPorHora = <?= json_encode($cantidadPorHora) ?>;
        const estrellas = <?= json_encode($estrellas) ?>;
        const cantPorEstrella = <?= json_encode($cantidadesEstrellas) ?>;
        const semanas = <?= json_encode($semanas) ?>;
        const ingresosPorSemana = <?= json_encode($ingresosPorSemana) ?>;

        new Chart(document.getElementById('graficoHoras'), {
            type: 'bar',
            data: {
                labels: horas,
                datasets: [{
                    label: 'Cantidad de Paseos',
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

        new Chart(document.getElementById('graficoIngresos'), {
            type: 'line',
            data: {
                labels: semanas,
                datasets: [{
                    label: 'Ingresos (Gs)',
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
    </script>
</body>

</html>