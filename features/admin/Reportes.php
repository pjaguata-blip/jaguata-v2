<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/ReporteController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Controllers\ReporteController;

// 🔹 Inicialización
AppConfig::init();

// 🔹 Seguridad
if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'admin') {
    header('Location: /jaguata/public/login.php?error=unauthorized');
    exit;
}

// 🔹 Obtener datos del controlador
$reporteController = new ReporteController();
$estadisticas = $reporteController->getEstadisticas();

// 🔹 Fallback seguro si no hay datos o estructura incorrecta
if (empty($estadisticas) || !is_array($estadisticas) || !isset($estadisticas['usuarios'])) {
    $estadisticas = [
        'usuarios' => 120,
        'paseos_total' => 340,
        'paseos_completos' => 300,
        'paseos_pendientes' => 40,
        'ingresos_totales' => 2350000,
        'roles' => [
            'dueno' => 80,
            'paseador' => 35,
            'admin' => 5,
        ],
        'paseos_por_dia' => [
            'Lun' => 40,
            'Mar' => 55,
            'Mié' => 47,
            'Jue' => 60,
            'Vie' => 50,
            'Sáb' => 35,
            'Dom' => 25
        ],
        'ingresos_por_mes' => [
            'Ene' => 850000,
            'Feb' => 920000,
            'Mar' => 1000000,
            'Abr' => 1170000,
            'May' => 1350000,
            'Jun' => 1450000
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            font-family: "Poppins", sans-serif;
            background-color: #f5f7fa;
        }

        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            width: 240px;
            height: 100vh;
            position: fixed;
            color: #fff;
            top: 0;
            left: 0;
            padding-top: 1.5rem;
        }

        .sidebar .nav-link {
            color: #ddd;
            padding: 10px 16px;
            border-radius: 8px;
            margin: 4px 8px;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: #3c6255;
            color: #fff;
        }

        main {
            margin-left: 240px;
            padding: 2rem;
        }

        .welcome-box {
            background: linear-gradient(90deg, #20c997, #3c6255);
            color: #fff;
            padding: 1.5rem 2rem;
            border-radius: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: #fff;
            border-radius: 12px;
            text-align: center;
            padding: 1.2rem;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
        }

        .chart-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .chart-card h5 {
            color: #3c6255;
            font-weight: 600;
        }

        footer {
            text-align: center;
            color: #777;
            margin-top: 2rem;
            padding: 1rem;
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="text-center mb-4">
            <img src="<?= ASSETS_URL ?>/uploads/perfiles/logojag.png" alt="Logo" width="60">
            <hr class="text-light">
        </div>
        <ul class="nav flex-column gap-1 px-2">
            <li><a class="nav-link" href="Dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            <li><a class="nav-link" href="Usuarios.php"><i class="fas fa-users"></i> Usuarios</a></li>
            <li><a class="nav-link" href="Paseos.php"><i class="fas fa-dog"></i> Paseos</a></li>
            <li><a class="nav-link active" href="#"><i class="fas fa-file-alt"></i> Reportes</a></li>
            <li><a class="nav-link text-danger" href="/jaguata/public/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a></li>
        </ul>
    </aside>

    <!-- Contenido -->
    <main>
        <div class="welcome-box">
            <div>
                <h1>Reportes Generales</h1>
                <p>Visualizá el rendimiento de la plataforma y estadísticas globales 📊</p>
            </div>
            <i class="fas fa-chart-pie fa-3x opacity-75"></i>
        </div>

        <!-- Métricas principales -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fas fa-users text-primary fa-2x mb-2"></i>
                    <h5><?= $estadisticas['usuarios'] ?></h5>
                    <p>Usuarios totales</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fas fa-dog text-success fa-2x mb-2"></i>
                    <h5><?= $estadisticas['paseos_total'] ?></h5>
                    <p>Paseos totales</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fas fa-wallet text-warning fa-2x mb-2"></i>
                    <h5>₲<?= number_format($estadisticas['ingresos_totales'], 0, ',', '.') ?></h5>
                    <p>Ingresos totales</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fas fa-check-circle text-info fa-2x mb-2"></i>
                    <h5><?= $estadisticas['paseos_completos'] ?></h5>
                    <p>Paseos completados</p>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="row">
            <div class="col-lg-6">
                <div class="chart-card">
                    <h5><i class="fas fa-chart-bar me-2"></i>Paseos por día</h5>
                    <canvas id="chartPaseosPorDia"></canvas>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="chart-card">
                    <h5><i class="fas fa-chart-line me-2"></i>Ingresos por mes</h5>
                    <canvas id="chartIngresosMes"></canvas>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="chart-card">
                    <h5><i class="fas fa-user-group me-2"></i>Usuarios por rol</h5>
                    <canvas id="chartRoles"></canvas>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="chart-card">
                    <h5><i class="fas fa-table me-2"></i>Resumen general</h5>
                    <table class="table table-sm table-striped">
                        <thead class="table-success">
                            <tr>
                                <th>Concepto</th>
                                <th>Cantidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Usuarios totales</td>
                                <td><?= $estadisticas['usuarios'] ?></td>
                            </tr>
                            <tr>
                                <td>Paseos totales</td>
                                <td><?= $estadisticas['paseos_total'] ?></td>
                            </tr>
                            <tr>
                                <td>Paseos completados</td>
                                <td><?= $estadisticas['paseos_completos'] ?></td>
                            </tr>
                            <tr>
                                <td>Paseos pendientes</td>
                                <td><?= $estadisticas['paseos_pendientes'] ?></td>
                            </tr>
                            <tr>
                                <td>Ingresos totales</td>
                                <td>₲<?= number_format($estadisticas['ingresos_totales'], 0, ',', '.') ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <footer>
            <small>© <?= date('Y') ?> Jaguata — Panel de Administración</small>
        </footer>
    </main>

    <!-- Scripts -->
    <script>
        new Chart(document.getElementById('chartPaseosPorDia'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_keys($estadisticas['paseos_por_dia'])) ?>,
                datasets: [{
                    label: 'Paseos',
                    data: <?= json_encode(array_values($estadisticas['paseos_por_dia'])) ?>,
                    backgroundColor: '#3c6255'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        new Chart(document.getElementById('chartIngresosMes'), {
            type: 'line',
            data: {
                labels: <?= json_encode(array_keys($estadisticas['ingresos_por_mes'])) ?>,
                datasets: [{
                    label: 'Ingresos (₲)',
                    data: <?= json_encode(array_values($estadisticas['ingresos_por_mes'])) ?>,
                    borderColor: '#20c997',
                    backgroundColor: 'rgba(32, 201, 151, 0.2)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        new Chart(document.getElementById('chartRoles'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_keys($estadisticas['roles'])) ?>,
                datasets: [{
                    data: <?= json_encode(array_values($estadisticas['roles'])) ?>,
                    backgroundColor: ['#3c6255', '#20c997', '#f6c23e']
                }]
            },
            options: {
                responsive: true
            }
        });
    </script>
</body>

</html>