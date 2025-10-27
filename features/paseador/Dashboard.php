<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;

AppConfig::init();

// === Autenticación ===
$auth = new AuthController();
$auth->checkRole('paseador');

$rol = 'paseador';
$baseFeatures = BASE_URL . "/features/{$rol}";
$usuarioNombre = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Paseador');
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Paseador - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f7fa;
            font-family: "Poppins", sans-serif;
            margin: 0;
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
            transition: background 0.2s, transform 0.2s;
            font-weight: 500;
        }

        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
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

        /* ===== Main ===== */
        main {
            background-color: #f5f7fa;
            padding: 2rem;
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
        .card-premium {
            border: none;
            border-radius: 14px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, .05);
            background: #fff;
            padding: 1.5rem;
        }

        .card-premium h5 {
            color: #3c6255;
            font-weight: 600;
        }

        .card-premium p {
            color: #6c757d;
            font-size: 0.95rem;
        }

        /* ===== Buttons ===== */
        .btn-gradient {
            background: linear-gradient(90deg, #3c6255, #20c997);
            border: none;
            color: #fff;
            font-weight: 500;
        }

        .btn-gradient:hover {
            opacity: 0.9;
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
        @media (max-width:768px) {
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
            <!-- Botón de menú móvil -->
            <button class="btn btn-success d-md-none mb-3 mt-3 ms-3" type="button" data-bs-toggle="collapse" data-bs-target="#menuSidebar">
                <i class="fas fa-bars"></i> Menú
            </button>

            <!-- Sidebar -->
            <div class="collapse d-md-block col-md-3 col-lg-2 sidebar" id="menuSidebar">
                <div class="text-center mb-4">
                    <img src="<?= ASSETS_URL; ?>/uploads/perfiles/logojag.png" alt="Jaguata" width="50">
                    <hr class="text-light">
                </div>
                <ul class="nav flex-column gap-1 px-2">
                    <li><a class="nav-link active" href="<?= $baseFeatures; ?>/Dashboard.php"><i class="fas fa-home"></i> Inicio</a></li>
                    <li><a class="nav-link" href="<?= $baseFeatures; ?>/MisPaseos.php"><i class="fas fa-list"></i> Mis Paseos</a></li>
                    <li><a class="nav-link" href="<?= $baseFeatures; ?>/Disponibilidad.php"><i class="fas fa-calendar-check"></i> Disponibilidad</a></li>
                    <li><a class="nav-link" href="<?= $baseFeatures; ?>/Perfil.php"><i class="fas fa-user"></i> Mi Perfil</a></li>
                    <li><a class="nav-link" href="<?= $baseFeatures; ?>/Estadisticas.php"><i class="fas fa-chart-line"></i> Estadísticas</a></li>
                    <li><a class="nav-link" href="<?= $baseFeatures; ?>/Solicitudes.php"><i class="fas fa-comments"></i> Solicitudes</a></li>
                    <li><a class="nav-link text-danger" href="<?= BASE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a></li>
                </ul>
            </div>

            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="page-header">
                    <h2><i class="fas fa-paw me-2"></i> Bienvenido, <?= $usuarioNombre; ?> 🐾</h2>
                    <a href="<?= $baseFeatures; ?>/Perfil.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-user me-1"></i> Ver Perfil
                    </a>
                </div>

                <div class="row g-3">
                    <div class="col-md-6 col-xl-4">
                        <div class="card-premium text-center">
                            <i class="fas fa-dog fa-3x text-success mb-3"></i>
                            <h5>Mis Paseos Asignados</h5>
                            <p>Consultá los paseos que te asignaron los dueños y marcá su progreso.</p>
                            <a href="<?= $baseFeatures; ?>/MisPaseos.php" class="btn btn-gradient w-100"><i class="fas fa-walking me-1"></i> Ver Paseos</a>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-4">
                        <div class="card-premium text-center">
                            <i class="fas fa-calendar-alt fa-3x text-success mb-3"></i>
                            <h5>Mi Disponibilidad</h5>
                            <p>Definí los días y horarios en los que estás disponible para pasear.</p>
                            <a href="<?= $baseFeatures; ?>/Disponibilidad.php" class="btn btn-outline-success w-100"><i class="fas fa-clock me-1"></i> Gestionar Horarios</a>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-4">
                        <div class="card-premium text-center">
                            <i class="fas fa-user fa-3x text-success mb-3"></i>
                            <h5>Mi Perfil</h5>
                            <p>Actualizá tus datos personales, experiencia y zonas de trabajo.</p>
                            <a href="<?= $baseFeatures; ?>/Perfil.php" class="btn btn-outline-success w-100"><i class="fas fa-user-edit me-1"></i> Editar Perfil</a>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-4">
                        <div class="card-premium text-center">
                            <i class="fas fa-chart-line fa-3x text-success mb-3"></i>
                            <h5>Estadísticas</h5>
                            <p>Visualizá tus paseos completados, cancelados e ingresos generados.</p>
                            <a href="<?= $baseFeatures; ?>/Estadisticas.php" class="btn btn-outline-success w-100"><i class="fas fa-chart-bar me-1"></i> Ver Reporte</a>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-4">
                        <div class="card-premium text-center">
                            <i class="fas fa-comments fa-3x text-success mb-3"></i>
                            <h5>Mensajes y Solicitudes</h5>
                            <p>Respondé solicitudes y mensajes de los dueños que te contactaron.</p>
                            <a href="<?= $baseFeatures; ?>/Solicitudes.php" class="btn btn-outline-success w-100"><i class="fas fa-envelope me-1"></i> Ver Mensajes</a>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>