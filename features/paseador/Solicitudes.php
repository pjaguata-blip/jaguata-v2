<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Helpers\Session;

// Inicializar aplicación
AppConfig::init();

// Verificar autenticación SOLO para paseador
$authController = new AuthController();
$authController->checkRole('paseador');

// Obtener ID del paseador en sesión
$paseadorId = Session::get('usuario_id');

// Obtener solicitudes (paseos en estado "Pendiente")
$paseoController = new PaseoController();
$solicitudes = $paseoController->getSolicitudesPendientes((int)$paseadorId);

function h($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$baseFeatures = BASE_URL . "/features/paseador";
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitudes de Paseos - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f7fa;
            font-family: "Poppins", sans-serif;
        }

        /* Sidebar */
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
            transition: background 0.2s, transform 0.2s;
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

        /* Main */
        main {
            padding: 2rem;
        }

        .page-header {
            background: linear-gradient(90deg, #20c997, #3c6255);
            color: #fff;
            padding: 1.2rem 1.5rem;
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

        .card-premium {
            border: none;
            border-radius: 14px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, .05);
            background: #fff;
            padding: 1.5rem;
        }

        .btn-gradient {
            background: linear-gradient(90deg, #3c6255, #20c997);
            border: none;
            color: #fff;
            font-weight: 500;
        }

        .btn-gradient:hover {
            opacity: 0.9;
        }

        .table thead {
            background-color: #f0f3f7;
        }

        .btn-success {
            background-color: #3c6255;
            border: none;
        }

        .btn-success:hover {
            background-color: #2e4d44;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="text-center mb-4">
                    <img src="../../assets/img/logo.png" alt="Jaguata" width="120" class="mb-3">
                    <hr class="text-light">
                </div>
                <ul class="nav flex-column gap-1 px-2">
                    <li><a class="nav-link" href="<?= $baseFeatures; ?>/Dashboard.php"><i class="fas fa-home me-2"></i>Inicio</a></li>
                    <li><a class="nav-link" href="<?= $baseFeatures; ?>/MisPaseos.php"><i class="fas fa-list me-2"></i>Mis Paseos</a></li>
                    <li><a class="nav-link" href="<?= $baseFeatures; ?>/Disponibilidad.php"><i class="fas fa-calendar-check me-2"></i>Disponibilidad</a></li>
                    <li><a class="nav-link active" href="#"><i class="fas fa-envelope-open-text me-2"></i>Solicitudes</a></li>
                    <li><a class="nav-link" href="<?= $baseFeatures; ?>/Perfil.php"><i class="fas fa-user me-2"></i>Mi Perfil</a></li>
                    <li><a class="nav-link text-danger" href="<?= BASE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar sesión</a></li>
                </ul>
            </div>

            <!-- Contenido -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">

                <div class="page-header">
                    <h2><i class="fas fa-envelope-open-text me-2"></i> Solicitudes de Paseos</h2>
                    <a href="Dashboard.php" class="btn btn-outline-light btn-sm"><i class="fas fa-arrow-left me-1"></i> Volver</a>
                </div>

                <!-- Flash messages -->
                <?php if (!empty($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-1"></i> <?= $_SESSION['success'];
                                                                    unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-triangle-exclamation me-1"></i> <?= $_SESSION['error'];
                                                                            unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (empty($solicitudes)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>No tienes solicitudes pendientes en este momento.
                    </div>
                <?php else: ?>
                    <div class="card-premium">
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Mascota</th>
                                        <th>Dueño</th>
                                        <th>Fecha</th>
                                        <th>Duración</th>
                                        <th>Precio</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($solicitudes as $s):
                                        $paseoId = (int)($s['paseo_id'] ?? 0);
                                        $duracion = $s['duracion'] ?? ($s['duracion_min'] ?? 0);
                                    ?>
                                        <tr>
                                            <td><i class="fas fa-paw text-success me-1"></i><?= h($s['nombre_mascota'] ?? '-') ?></td>
                                            <td><i class="fas fa-user text-secondary me-1"></i><?= h($s['nombre_dueno'] ?? '-') ?></td>
                                            <td><?= isset($s['inicio']) ? date('d/m/Y H:i', strtotime($s['inicio'])) : '—' ?></td>
                                            <td><?= (int)$duracion ?> min</td>
                                            <td>₲<?= number_format((float)($s['precio_total'] ?? 0), 0, ',', '.') ?></td>
                                            <td class="text-end">
                                                <form action="AccionPaseo.php" method="post" class="d-inline">
                                                    <input type="hidden" name="id" value="<?= $paseoId ?>">
                                                    <input type="hidden" name="accion" value="confirmar">
                                                    <input type="hidden" name="redirect_to" value="Solicitudes.php">
                                                    <button type="submit" class="btn btn-sm btn-success"
                                                        onclick="return confirm('¿Aceptar esta solicitud?');" title="Aceptar">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                <form action="AccionPaseo.php" method="post" class="d-inline ms-1">
                                                    <input type="hidden" name="id" value="<?= $paseoId ?>">
                                                    <input type="hidden" name="accion" value="cancelar">
                                                    <input type="hidden" name="redirect_to" value="Solicitudes.php">
                                                    <button type="submit" class="btn btn-sm btn-danger"
                                                        onclick="return confirm('¿Rechazar esta solicitud?');" title="Rechazar">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>