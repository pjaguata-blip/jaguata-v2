<?php

declare(strict_types=1);

// ==== Bootstrap general ====
$rootPath = realpath(__DIR__ . '/../../');
require_once $rootPath . '/vendor/autoload.php';
require_once $rootPath . '/src/Config/AppConfig.php';
require_once $rootPath . '/src/Controllers/AuthController.php';
require_once $rootPath . '/src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Helpers\Session;

// ==== Inicialización ====
AppConfig::init();

$auth = new AuthController();
$auth->checkRole('paseador');

// ==== Datos del usuario logueado ====
$usuario = Session::get('usuario') ?? [];
$usuarioNombre = htmlspecialchars($usuario['nombre'] ?? 'Paseador');

// === Datos de ejemplo (sustituí luego por datos reales de la BD) ===
$paseosCompletados = 42;
$paseosCancelados  = 6;
$ingresosTotales   = 1250000; // Gs
$promedioCalificacion = 4.6;

$horas = ['06h', '08h', '10h', '12h', '14h', '16h', '18h'];
$cantidadPorHora = [3, 8, 10, 5, 7, 6, 3];

$estrellas = [5, 4, 3, 2, 1];
$cantPorEstrella = [25, 10, 4, 2, 1];

$semanas = ['Semana 1', 'Semana 2', 'Semana 3', 'Semana 4'];
$ingresosPorSemana = [250000, 310000, 280000, 410000];
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

        /* ===== Sidebar ===== */
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

        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }

        /* ===== Header ===== */
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

        /* ===== Cards ===== */
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
            transition: all 0.2s ease;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
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

        /* ===== Buttons ===== */
        .btn-gradient {
            background: linear-gradient(90deg, #3c6255, #20c997);
            border: none;
            color: #fff;
            font-weight: 500;
        }

        .btn-outline-success {
            border-color: #3c6255;
            color: #3c6255;
        }

        .btn-outline-success:hover {
            background-color: #3c6255;
            color: #fff;
        }

        /* ===== Responsive ===== */
        @media (max-width: 768px) {
            main {
                padding: 1rem;
            }

            .sidebar {
                min-height: auto;
                position: relative;
            }
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
                    <li><a class="nav-link" href="Solicitudes.php"><i class="fas fa-comments"></i> Solicitudes</a></li>
                    <li><a class="nav-link text-danger" href="../../logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a></li>
                </ul>
            </div>

            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="page-header">
                    <h2><i class="fas fa-chart-line me-2"></i> Estadísticas del Paseador</h2>
                    <span class="fw-bold">🐾 <?= $usuarioNombre ?></span>
                </div>

                <!-- RESUMEN GENERAL -->
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
                            <h3 class="text-warning"><?= number_format($promedioCalificacion, 1) ?> <i class="fa-solid fa-star"></i></h3>
                        </div>
                    </div>
                </div>

                <!-- GRÁFICOS -->
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="card p-3">
                            <h5 class="text-success mb-3"><i class="fa-solid fa-clock me-1"></i> Paseos por franja horaria</h5>
                            <canvas id="graficoHoras"></canvas>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card p-3">
                            <h5 class="text-success mb-3"><i class="fa-solid fa-star me-1"></i> Distribución de calificaciones</h5>
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
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        const horas = <?= json_encode($horas) ?>;
        const cantidadPorHora = <?= json_encode($cantidadPorHora) ?>;
        const estrellas = <?= json_encode($estrellas) ?>;
        const cantPorEstrella = <?= json_encode($cantPorEstrella) ?>;
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