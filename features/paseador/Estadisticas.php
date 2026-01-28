<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Helpers\Session;
AppConfig::init();
$auth = new AuthController();
$auth->checkRole('paseador');

$paseadorId    = (int)(Session::getUsuarioId() ?? 0);
$usuarioNombre = Session::getUsuarioNombre() ?? 'Paseador';

if ($paseadorId <= 0) {
    header('Location: ' . BASE_URL . '/public/login.php?error=unauthorized');
    exit;
}
$paseoController = new PaseoController();
$paseos          = $paseoController->indexForPaseador($paseadorId);
if (empty($paseos)) {
    $paseos = [];
}
$paseosCompletados = 0;
$paseosCancelados  = 0;
$ingresosTotales   = 0;
$calificaciones    = [];
$porHora           = [];
$porSemana         = [];

foreach ($paseos as $p) {
    $estado = strtolower($p['estado'] ?? '');
    $fecha  = isset($p['inicio']) ? new DateTime($p['inicio']) : null;
    $hora   = $fecha ? $fecha->format('H') . "h" : '—';
    $semana = $fecha ? "Semana " . $fecha->format('W') : '—';

    if ($estado === 'completo' || $estado === 'finalizado') {
        $paseosCompletados++;
        $ingresosTotales += (float)($p['precio_total'] ?? 0);
    } elseif ($estado === 'cancelado') {
        $paseosCancelados++;
    }

    if ($hora !== '—') {
        $porHora[$hora] = ($porHora[$hora] ?? 0) + 1;
    }
    if ($semana !== '—') {
        $porSemana[$semana] = ($porSemana[$semana] ?? 0) + (float)($p['precio_total'] ?? 0);
    }
    if (isset($p['calificacion']) && is_numeric($p['calificacion'])) {
        $calificaciones[] = (int)$p['calificacion'];
    }
}

$promedioCalificacion = count($calificaciones)
    ? round(array_sum($calificaciones) / count($calificaciones), 1)
    : 0.0;

$cantPorEstrella = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
foreach ($calificaciones as $c) {
    if (isset($cantPorEstrella[$c])) {
        $cantPorEstrella[$c]++;
    }
}

ksort($porHora);
ksort($porSemana);

$horas               = array_keys($porHora);
$cantidadPorHora     = array_values($porHora);
$estrellas           = array_keys($cantPorEstrella);
$cantidadesEstrellas = array_values($cantPorEstrella);
$semanas             = array_keys($porSemana);
$ingresosPorSemana   = array_values($porSemana);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas - Paseador | Jaguata</title>

    <!-- Bootstrap + FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <!-- (Opcional) tu tema global -->
    <link href="<?= ASSETS_URL; ?>/css/jaguata-theme.css" rel="stylesheet">

    <style>
        :root {
            --verde-jaguata: #3c6255;
            --verde-claro: #20c997;
            --gris-fondo: #f4f6f9;
            --gris-texto: #555;
            --blanco: #fff;
        }

        html,
        body {
            margin: 0;
            height: 100%;
            font-family: "Poppins", sans-serif;
            background-color: var(--gris-fondo);
            color: var(--gris-texto);
        }

        .layout {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        /* === SIDEBAR (mismo estilo que Dashboard Paseador) === */
        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            color: var(--blanco);
            width: 250px;
            flex-shrink: 0;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.2);
            padding-top: 1.5rem;
        }

        main.content {
            flex-grow: 1;
            padding: 2rem;
            background-color: var(--gris-fondo);
        }

        .page-header {
            background: linear-gradient(90deg, var(--verde-claro), var(--verde-jaguata));
            color: #fff;
            padding: 1.8rem 2rem;
            border-radius: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .1);
        }

        .page-header h2 {
            margin: 0;
            font-size: 1.6rem;
            font-weight: 600;
        }

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

        .stat-card h2,
        .stat-card h3 {
            margin: 0.25rem 0 0;
            font-weight: 600;
        }

        .stat-card h6 {
            margin: 0;
            font-size: 0.9rem;
            color: #777;
        }

        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
        }

        .card h6 {
            font-weight: 600;
        }

        footer {
            background-color: var(--verde-jaguata);
            color: #fff;
            text-align: center;
            padding: 1.2rem 0;
            width: 100%;
            margin-top: 3rem;
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../../src/Templates/Navbar.php'; ?>

    <div class="layout">
        <?php include __DIR__ . '/../../src/Templates/SidebarPaseador.php'; ?>

        <!-- CONTENIDO PRINCIPAL -->
        <main class="content">
            <div class="page-header">
                <h2><i class="fas fa-chart-line me-2"></i>Estadísticas del Paseador</h2>
                <a href="Dashboard.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <h6>Paseos completados</h6>
                        <h2 class="text-success"><?= $paseosCompletados ?></h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h6>Paseos cancelados</h6>
                        <h2 class="text-danger"><?= $paseosCancelados ?></h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h6>Ingresos totales</h6>
                        <h3>₲ <?= number_format($ingresosTotales, 0, ',', '.') ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h6>Calificación promedio</h6>
                        <h3 class="text-warning"><?= $promedioCalificacion ?> <i class="fa-solid fa-star"></i></h3>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card p-3">
                        <h6 class="text-success mb-3">
                            <i class="fa-solid fa-clock me-1"></i>Paseos por hora
                        </h6>
                        <canvas id="graficoHoras"></canvas>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card p-3">
                        <h6 class="text-success mb-3">
                            <i class="fa-solid fa-star me-1"></i>Calificaciones
                        </h6>
                        <canvas id="graficoCalificaciones"></canvas>
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="card p-3">
                        <h6 class="text-success mb-3">
                            <i class="fa-solid fa-wallet me-1"></i>Ingresos por semana
                        </h6>
                        <canvas id="graficoIngresos"></canvas>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <footer>© <?= date('Y') ?> Jaguata — Todos los derechos reservados.</footer>

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
    </script>
</body>

</html>