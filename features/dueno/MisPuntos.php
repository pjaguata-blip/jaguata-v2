<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Models/Usuario.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Models\Usuario;
use Jaguata\Helpers\Session;

// Inicializar
AppConfig::init();
$auth = new AuthController();
$auth->checkAuth();

// Datos del usuario
$usuarioId = (int) Session::get('usuario_id');
$rol = Session::getUsuarioRol() ?? 'dueno';

$usuarioModel = new Usuario();
$usuario = $usuarioModel->getById($usuarioId);
if (!$usuario) {
    http_response_code(404);
    exit('❌ Usuario no encontrado');
}

$puntos = (int)($usuario['puntos'] ?? 0);
$baseFeatures = BASE_URL . "/features/{$rol}";
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mis Puntos - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f7fa;
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
            transition: all .2s ease;
            font-weight: 500;
        }

        .sidebar .nav-link:hover {
            background: #343454;
            color: #fff;
            transform: translateX(4px);
        }

        .sidebar .nav-link.active {
            background: #3c6255;
            color: #fff;
        }

        main {
            background: #f5f7fa;
            padding: 2rem;
        }

        .page-header {
            background: linear-gradient(90deg, #20c997, #3c6255);
            color: #fff;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-weight: 600;
            margin: 0;
        }

        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .btn-outline-secondary {
            border-color: #20c997;
            color: #20c997;
        }

        .btn-outline-secondary:hover {
            background: #20c997;
            color: #fff;
        }

        .text-primary {
            color: #3c6255 !important;
        }

        .text-success {
            color: #20c997 !important;
        }

        .bg-gradient-success {
            background: linear-gradient(90deg, #20c997, #3c6255);
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../../src/Templates/Header.php'; ?>
    <?php include __DIR__ . '/../../src/Templates/Navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li><a class="nav-link" href="<?= $baseFeatures; ?>/Dashboard.php"><i class="fas fa-home me-2"></i>Inicio</a></li>
                        <li><a class="nav-link active" href="#"><i class="fas fa-star me-2"></i>Mis Puntos</a></li>
                        <li><a class="nav-link" href="<?= $baseFeatures; ?>/MisMascotas.php"><i class="fas fa-paw me-2"></i>Mis Mascotas</a></li>
                        <li><a class="nav-link" href="<?= $baseFeatures; ?>/PaseosPendientes.php"><i class="fas fa-walking me-2"></i>Paseos</a></li>
                        <li><a class="nav-link" href="<?= $baseFeatures; ?>/GastosTotales.php"><i class="fas fa-wallet me-2"></i>Gastos</a></li>
                        <li><a class="nav-link text-danger" href="<?= BASE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Salir</a></li>
                    </ul>
                </div>
            </div>

            <!-- Main -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="page-header">
                    <h1><i class="fas fa-star me-2"></i>Mis Puntos</h1>
                    <a href="<?= $baseFeatures; ?>/Dashboard.php" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>

                <div class="row justify-content-center">
                    <div class="col-lg-6 col-md-8">
                        <div class="card text-center p-4 shadow-lg">
                            <div class="card-body">
                                <h2 class="fw-bold text-success mb-3">
                                    <i class="fas fa-medal text-warning me-2"></i> ¡Tus Recompensas!
                                </h2>
                                <p class="text-muted mb-4">
                                    Cada paseo completado te otorga puntos 🐶 ¡Seguí sumando para desbloquear beneficios!
                                </p>

                                <div class="bg-light rounded-4 py-4 mb-4 border">
                                    <h2 class="display-3 fw-bold text-primary mb-0">
                                        <?= number_format($puntos, 0, ',', '.') ?>
                                    </h2>
                                    <small class="text-secondary">puntos acumulados</small>
                                </div>

                                <a href="<?= $baseFeatures; ?>/Dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i> Volver al Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include __DIR__ . '/../../src/Templates/Footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>