<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/UsuarioController.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/PaseoController.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/MascotaController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Controllers\UsuarioController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Controllers\MascotaController;

AppConfig::init();

// 🔒 Autenticación
if (!Session::isLoggedIn()) {
    header('Location: /jaguata/public/login.php');
    exit;
}
if (Session::getUsuarioRol() !== 'admin') {
    header('Location: /jaguata/public/login.php?error=unauthorized');
    exit;
}

// Controladores
$usuarioController = new UsuarioController();
$paseoController   = new PaseoController();
$mascotaController = new MascotaController();

// Datos
$usuarios = $usuarioController->index() ?: [];
$paseos   = $paseoController->index() ?: [];
$mascotas = $mascotaController->index() ?: [];

// Métricas
$totalUsuarios   = count($usuarios);
$totalPaseos     = count($paseos);
$totalMascotas   = count($mascotas);
$paseosActivos   = array_filter($paseos, fn($p) => in_array(strtolower($p['estado'] ?? ''), ['activo', 'pendiente']));
$paseosCompletos = array_filter($paseos, fn($p) => strtolower($p['estado'] ?? '') === 'completo');
$totalPagosPend  = count(array_filter($paseos, fn($p) => strtolower($p['estado_pago'] ?? '') === 'pendiente'));
$totalPagosReal  = count(array_filter($paseos, fn($p) => strtolower($p['estado_pago'] ?? '') === 'pagado'));

// Datos simulados
$totalServicios      = 4;
$totalNotificaciones = 5;
$totalRoles          = 3;
$totalMensajes       = 12;
$totalTickets        = 2; // soporte pendiente
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Jaguata</title>
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
            background-color: var(--gris-fondo);
            color: var(--gris-texto);
        }

        /* Sidebar */
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

        /* Main layout */
        main {
            margin-left: 250px;
            padding: 2rem;
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
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
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

        .stat-card i {
            font-size: 2rem;
            margin-bottom: .5rem;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        footer {
            text-align: center;
            padding: 1rem;
            color: #777;
            font-size: 0.9rem;
            margin-top: 3rem;
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="text-center mb-4">
            <img src="<?= ASSETS_URL; ?>/uploads/perfiles/logojag.png" alt="Logo" width="60">
            <h6 class="mt-2 fw-bold text-success">Jaguata Admin</h6>
            <hr class="text-light">
        </div>
        <ul class="nav flex-column">
            <li><a class="nav-link active" href="#"><i class="fas fa-home"></i>Inicio</a></li>
            <li><a class="nav-link" href="Usuarios.php"><i class="fas fa-users"></i>Usuarios</a></li>
            <li><a class="nav-link" href="Paseos.php"><i class="fas fa-dog"></i>Paseos</a></li>
            <li><a class="nav-link" href="../mensajeria/chat.php"><i class="fas fa-comments"></i>Mensajería</a></li>
            <li><a class="nav-link" href="Pagos.php"><i class="fas fa-wallet"></i>Pagos</a></li>
            <li><a class="nav-link" href="Servicios.php"><i class="fas fa-briefcase"></i>Servicios</a></li>
            <li><a class="nav-link" href="Notificaciones.php"><i class="fas fa-bell"></i>Notificaciones</a></li>
            <li><a class="nav-link" href="Soporte.php"><i class="fas fa-headset"></i>Soporte</a></li>
            <li><a class="nav-link" href="RolesPermisos.php"><i class="fas fa-user-lock"></i>Roles y Permisos</a></li>
            <li><a class="nav-link" href="Reportes.php"><i class="fas fa-chart-pie"></i>Reportes</a></li>
            <li><a class="nav-link" href="Configuracion.php"><i class="fas fa-cogs"></i>Configuración</a></li>
            <li><a class="nav-link" href="Auditoria.php"><i class="fas fa-shield-halved"></i>Auditoría</a></li>
            <li><a class="nav-link text-danger" href="/jaguata/public/logout.php"><i class="fas fa-sign-out-alt"></i>Salir</a></li>
        </ul>
    </aside>

    <!-- Contenido -->
    <main>
        <div class="welcome-box">
            <div>
                <h1 class="fw-bold">Panel de Administración</h1>
                <p>Bienvenido, <?= htmlspecialchars(Session::getUsuarioNombre() ?? 'Administrador'); ?> 👋</p>
            </div>
            <i class="fas fa-user-shield fa-3x opacity-75"></i>
        </div>

        <!-- Métricas -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card"><i class="fas fa-users text-primary"></i>
                    <h4><?= $totalUsuarios ?></h4>
                    <p>Usuarios</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card"><i class="fas fa-dog text-success"></i>
                    <h4><?= $totalMascotas ?></h4>
                    <p>Mascotas</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card"><i class="fas fa-walking text-warning"></i>
                    <h4><?= count($paseosActivos) ?></h4>
                    <p>Paseos activos</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card"><i class="fas fa-check-circle text-info"></i>
                    <h4><?= count($paseosCompletos) ?></h4>
                    <p>Paseos completados</p>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card"><i class="fas fa-wallet text-success"></i>
                    <h4><?= $totalPagosReal ?></h4>
                    <p>Pagos realizados</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card"><i class="fas fa-hourglass-half text-danger"></i>
                    <h4><?= $totalPagosPend ?></h4>
                    <p>Pagos pendientes</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card"><i class="fas fa-comments text-secondary"></i>
                    <h4><?= $totalMensajes ?></h4>
                    <p>Mensajes</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card"><i class="fas fa-headset text-primary"></i>
                    <h4><?= $totalTickets ?></h4>
                    <p>Soporte</p>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card"><i class="fas fa-bell text-warning"></i>
                    <h4><?= $totalNotificaciones ?></h4>
                    <p>Notificaciones</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card"><i class="fas fa-briefcase text-dark"></i>
                    <h4><?= $totalServicios ?></h4>
                    <p>Servicios activos</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card"><i class="fas fa-user-lock text-dark"></i>
                    <h4><?= $totalRoles ?></h4>
                    <p>Roles del sistema</p>
                </div>
            </div>
        </div>

        <!-- Gráfico -->
        <div class="card p-4 shadow-sm mt-4">
            <h5 class="mb-3 text-success fw-bold"><i class="fas fa-chart-line me-2"></i>Estadísticas Semanales</h5>
            <div class="chart-container"><canvas id="chartPaseos"></canvas></div>
        </div>

        <footer><small>© <?= date('Y') ?> Jaguata — Panel de Administración</small></footer>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('chartPaseos');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
                datasets: [{
                    label: 'Paseos por día',
                    data: [12, 19, 8, 17, 14, 10, 6],
                    backgroundColor: 'rgba(32,201,151,0.25)',
                    borderColor: '#3c6255',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
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