<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/ReporteController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Controllers\ReporteController;

AppConfig::init();

if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'admin') {
    header('Location: /jaguata/public/login.php?error=unauthorized');
    exit;
}

$reporteController = new ReporteController();
$estadisticas = $reporteController->getEstadisticas();
if (!isset($estadisticas['usuarios'])) {
    $estadisticas = ['usuarios' => 0, 'paseos_total' => 0];
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --verde-jaguata: #3c6255;
            --verde-claro: #20c997;
            --gris-fondo: #f5f7fa;
            --blanco: #fff;
        }

        body {
            background-color: var(--gris-fondo);
            font-family: "Poppins", sans-serif;
            color: #333;
        }

        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            width: 250px;
            height: 100vh;
            position: fixed;
            color: #fff;
            top: 0;
            left: 0;
            padding-top: 1.5rem;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.25);
        }

        .sidebar .nav-link {
            color: #ccc;
            padding: 10px 16px;
            border-radius: 8px;
            margin: 4px 10px;
            display: flex;
            align-items: center;
            gap: .7rem;
            transition: all .2s ease;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: var(--verde-claro);
            color: #fff;
            transform: translateX(4px);
        }

        main {
            margin-left: 250px;
            padding: 2rem;
        }

        .welcome-box {
            background: linear-gradient(90deg, var(--verde-claro), var(--verde-jaguata));
            color: #fff;
            padding: 1.8rem 2rem;
            border-radius: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            animation: fadeIn .7s ease;
        }

        .filtros {
            background: var(--blanco);
            border-radius: 14px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            padding: 1rem 1.5rem;
            margin: 1.5rem 0;
        }

        .filtros label {
            font-weight: 600;
            color: #444;
        }

        .stat-card {
            background: var(--blanco);
            border-radius: 12px;
            text-align: center;
            padding: 1.2rem;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .chart-card {
            background: var(--blanco);
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            animation: fadeIn .7s ease;
        }

        .chart-card h5 {
            color: var(--verde-jaguata);
            font-weight: 600;
        }

        footer {
            text-align: center;
            color: #777;
            margin-top: 2rem;
            padding: 1rem;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../../src/Templates/SidebarAdmin.php'; ?>

    <!-- Contenido -->
    <main>
        <div class="welcome-box">
            <div>
                <h1 class="fw-bold">Reportes Generales</h1>
                <p>Visualizá el rendimiento y estadísticas globales 📊</p>
            </div>
            <i class="fas fa-chart-pie fa-3x opacity-75"></i>
        </div>

        <!-- Filtros -->
        <div class="filtros">
            <form class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Buscar</label>
                    <input type="text" id="searchInput" class="form-control" placeholder="Escribí: usuarios, paseos, ingresos...">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Mostrar gráfico de</label>
                    <select id="filterGrafico" class="form-select">
                        <option value="todos">Todos</option>
                        <option value="dia">Paseos por día</option>
                        <option value="mes">Ingresos por mes</option>
                        <option value="roles">Usuarios por rol</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha desde</label>
                    <input type="date" class="form-control">
                </div>
            </form>
        </div>

        <!-- Métricas -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card"><i class="fas fa-users text-primary fa-2x mb-2"></i>
                    <h5><?= $estadisticas['usuarios'] ?></h5>
                    <p>Usuarios totales</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card"><i class="fas fa-dog text-success fa-2x mb-2"></i>
                    <h5><?= $estadisticas['paseos_total'] ?></h5>
                    <p>Paseos totales</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card"><i class="fas fa-wallet text-warning fa-2x mb-2"></i>
                    <h5>₲<?= number_format($estadisticas['ingresos_totales'], 0, ',', '.') ?></h5>
                    <p>Ingresos totales</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card"><i class="fas fa-check-circle text-info fa-2x mb-2"></i>
                    <h5><?= $estadisticas['paseos_completos'] ?></h5>
                    <p>Paseos completados</p>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="row" id="chartGroup">
            <div class="col-lg-6 chart-item" data-type="dia">
                <div class="chart-card">
                    <h5><i class="fas fa-chart-bar me-2"></i>Paseos por día</h5>
                    <canvas id="chartPaseosPorDia"></canvas>
                </div>
            </div>
            <div class="col-lg-6 chart-item" data-type="mes">
                <div class="chart-card">
                    <h5><i class="fas fa-chart-line me-2"></i>Ingresos por mes</h5>
                    <canvas id="chartIngresosMes"></canvas>
                </div>
            </div>
            <div class="col-lg-6 chart-item" data-type="roles">
                <div class="chart-card">
                    <h5><i class="fas fa-user-group me-2"></i>Usuarios por rol</h5>
                    <canvas id="chartRoles"></canvas>
                </div>
            </div>
            <div class="col-lg-6 chart-item" data-type="resumen">
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

        <footer><small>© <?= date('Y') ?> Jaguata — Panel de Administración</small></footer>
    </main>

    <script>
        /* === Gráficos Chart.js === */
        new Chart(document.getElementById('chartPaseosPorDia'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_keys($estadisticas['paseos_por_dia'])) ?>,
                datasets: [{
                    label: 'Paseos',
                    data: <?= json_encode(array_values($estadisticas['paseos_por_dia'])) ?>,
                    backgroundColor: '#3c6255',
                    borderRadius: 5
                }]
            },
            options: {
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
                    backgroundColor: 'rgba(32,201,151,0.25)',
                    tension: .3,
                    fill: true
                }]
            },
            options: {
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
            }
        });

        /* === Filtros dinámicos === */
        const searchInput = document.getElementById('searchInput');
        const filterGrafico = document.getElementById('filterGrafico');
        const chartItems = document.querySelectorAll('.chart-item');

        filterGrafico.addEventListener('change', () => {
            const val = filterGrafico.value;
            chartItems.forEach(c => {
                c.style.display = (val === 'todos' || c.dataset.type === val) ? '' : 'none';
            });
        });

        searchInput.addEventListener('input', () => {
            const text = searchInput.value.toLowerCase();
            chartItems.forEach(c => {
                const visible = c.textContent.toLowerCase().includes(text);
                c.style.display = visible ? '' : 'none';
            });
        });
    </script>
</body>

</html>