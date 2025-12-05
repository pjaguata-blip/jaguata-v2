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

function h($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$paseoId = (int)($_GET['id'] ?? 0);
if ($paseoId <= 0) die('ID de paseo inválido.');

$paseoController = new PaseoController();
$paseo = $paseoController->show($paseoId);
if (!$paseo) die('No se encontró el paseo.');

if ((int)($paseo['paseador_id'] ?? 0) !== (int)(Session::getUsuarioId() ?? 0)) {
    die('No tienes permiso para ver este paseo.');
}

$estado = strtolower((string)($paseo['estado'] ?? ''));
$badge = match ($estado) {
    'confirmado' => 'warning',
    'en_curso'   => 'info',
    'completo'   => 'success',
    'cancelado'  => 'danger',
    default      => 'secondary'
};

$baseFeatures = BASE_URL . "/features/paseador";
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del Paseo - Jaguata</title>
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

        .btn-outline-danger {
            border-color: #c0392b;
            color: #c0392b;
        }

        .btn-outline-danger:hover {
            background-color: #c0392b;
            color: #fff;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="text-center mb-4">
                    <img src="<?= ASSETS_URL; ?>/uploads/perfiles/logojag.png" alt="Jaguata" width="50">
                    <hr class="text-light">
                </div>
                <ul class="nav flex-column gap-1 px-2">
                    <li><a class="nav-link" href="<?= $baseFeatures; ?>/Dashboard.php"><i class="fas fa-home me-2"></i>Inicio</a></li>
                    <li><a class="nav-link active" href="<?= $baseFeatures; ?>/MisPaseos.php"><i class="fas fa-list me-2"></i>Mis Paseos</a></li>
                    <li><a class="nav-link" href="<?= $baseFeatures; ?>/Disponibilidad.php"><i class="fas fa-calendar-check me-2"></i>Disponibilidad</a></li>
                    <li><a class="nav-link" href="<?= $baseFeatures; ?>/Perfil.php"><i class="fas fa-user me-2"></i>Mi Perfil</a></li>
                    <li><a class="nav-link text-danger" href="<?= BASE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar sesión</a></li>
                </ul>
            </div>

            <!-- Contenido -->

            <div class="page-header">
                <h2><i class="fas fa-walking me-2"></i> Detalle del Paseo</h2>
                <a href="MisPaseos.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
            </div>

            <div class="card-premium">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong><i class="fas fa-paw text-success me-2"></i>Mascota:</strong> <?= h($paseo['nombre_mascota'] ?? '—') ?></p>
                        <p><strong><i class="fas fa-user text-secondary me-2"></i>Dueño:</strong> <?= h($paseo['nombre_dueno'] ?? '—') ?></p>
                        <p><strong><i class="fas fa-calendar me-2"></i>Fecha:</strong> <?= isset($paseo['inicio']) ? date('d/m/Y H:i', strtotime($paseo['inicio'])) : '—' ?></p>
                        <p><strong><i class="fas fa-hourglass-half me-2"></i>Duración:</strong> <?= h($paseo['duracion'] ?? $paseo['duracion_min'] ?? '—') ?> min</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong><i class="fas fa-map-marker-alt me-2"></i>Dirección:</strong> <?= h($paseo['direccion'] ?? '—') ?></p>
                        <p><strong><i class="fas fa-dollar-sign me-2"></i>Precio Total:</strong> ₲<?= number_format((float)($paseo['precio_total'] ?? 0), 0, ',', '.') ?></p>
                        <p><strong><i class="fas fa-info-circle me-2"></i>Estado:</strong>
                            <span class="badge bg-<?= $badge ?>"><?= ucfirst($estado ?: '—') ?></span>
                        </p>
                    </div>
                </div>

                <hr>

                <div class="mt-3 d-flex gap-2">
                    <?php if ($estado === 'confirmado'): ?>
                        <a href="AccionPaseo.php?id=<?= (int)$paseo['paseo_id'] ?>&accion=iniciar"
                            class="btn btn-gradient"
                            onclick="return confirm('¿Iniciar este paseo?');">
                            <i class="fas fa-play me-1"></i> Iniciar Paseo
                        </a>
                        <a href="AccionPaseo.php?id=<?= (int)$paseo['paseo_id'] ?>&accion=cancelar"
                            class="btn btn-outline-danger"
                            onclick="return confirm('¿Cancelar este paseo?');">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </a>
                    <?php elseif ($estado === 'en_curso'): ?>
                        <a href="AccionPaseo.php?id=<?= (int)$paseo['paseo_id'] ?>&accion=completar"
                            class="btn btn-gradient"
                            onclick="return confirm('¿Marcar este paseo como completado?');">
                            <i class="fas fa-check me-1"></i> Completar Paseo
                        </a>
                        <a href="AccionPaseo.php?id=<?= (int)$paseo['paseo_id'] ?>&accion=cancelar"
                            class="btn btn-outline-danger"
                            onclick="return confirm('¿Cancelar este paseo en curso?');">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </a>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>