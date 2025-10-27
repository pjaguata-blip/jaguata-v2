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

// ✅ Función de sanitización segura
function h(mixed $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

// === Init + auth ===
AppConfig::init();
$auth = new AuthController();
$auth->checkRole('dueno');


$paseoId = isset($_GET['paseo_id']) ? (int)$_GET['paseo_id'] : 0;
if ($paseoId <= 0) {
    exit('ID de paseo no válido.');
}

$paseoCtrl = new PaseoController();
$paseo = $paseoCtrl->getById($paseoId);
if (!$paseo) {
    exit('No se encontró el paseo.');
}

// Variables base
$rol = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures = BASE_URL . "/features/{$rol}";
$backUrl = $baseFeatures . "/MisPaseos.php";
$panelUrl = $baseFeatures . "/Dashboard.php";

// Datos
$fecha = !empty($paseo['fecha_inicio']) ? date('d/m/Y H:i', strtotime($paseo['fecha_inicio'])) : '—';
$estado = ucfirst($paseo['estado'] ?? 'Pendiente');
$monto = number_format((float)($paseo['monto'] ?? 0), 0, ',', '.');
$duracion = h($paseo['duracion'] ?? '—');
$observacion = nl2br(h($paseo['observacion'] ?? 'Sin observaciones.'));
$paseador = h($paseo['paseador_nombre'] ?? 'No asignado');
$mascota = h($paseo['mascota_nombre'] ?? '—');

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
            margin: 0;
        }

        .layout {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            color: #f8f9fa;
            width: 240px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 1.5rem;
            box-shadow: 4px 0 12px rgba(0, 0, 0, .15);
            z-index: 1000;
            transition: transform .3s ease-in-out;
        }

        .sidebar .nav-link {
            color: #ddd;
            display: flex;
            align-items: center;
            padding: 10px 16px;
            border-radius: 8px;
            margin: 4px 8px;
            font-weight: 500;
        }

        .sidebar .nav-link i {
            width: 22px;
            margin-right: 10px;
        }

        .sidebar .nav-link:hover {
            background-color: #343454;
            color: #fff;
            transform: translateX(4px);
        }

        .sidebar .nav-link.active {
            background: #3c6255;
            color: #fff;
        }

        .menu-toggle {
            display: none;
            position: fixed;
            top: 16px;
            left: 16px;
            background: #1e1e2f;
            color: white;
            border: none;
            padding: 8px 10px;
            border-radius: 6px;
            z-index: 1100;
        }

        @media(max-width:768px) {
            .menu-toggle {
                display: block;
            }

            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }
        }

        main.content {
            flex-grow: 1;
            margin-left: 240px;
            padding: 2.5rem;
            width: calc(100% - 240px);
        }

        @media(max-width:768px) {
            main.content {
                margin-left: 0;
                width: 100%;
                padding: 1.5rem;
            }
        }

        .welcome-box {
            background: linear-gradient(90deg, #20c997, #3c6255);
            color: #fff;
            padding: 1.5rem 2rem;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 15px rgba(0, 0, 0, .08);
        }

        .card-premium {
            border: none;
            border-radius: 14px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, .05);
            background: #fff;
        }

        .btn-gradient {
            background: linear-gradient(90deg, #3c6255, #20c997);
            border: none;
            color: #fff;
            font-weight: 500;
        }

        .btn-gradient:hover {
            opacity: .9;
        }
    </style>
</head>

<body>
    <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>

    <div class="layout">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="text-center mb-4">
                <img src="<?= ASSETS_URL; ?>/uploads/perfiles/logojag.png" alt="Jaguata" width="50">
                <hr class="text-light">
            </div>
            <ul class="nav flex-column gap-1 px-2">
                <li><a class="nav-link" href="<?= $panelUrl ?>"><i class="fas fa-home"></i> Inicio</a></li>
                <li><a class="nav-link active" href="#"><i class="fas fa-info-circle"></i> Detalle Paseo</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/MisPaseos.php"><i class="fas fa-list"></i> Mis Paseos</a></li>
                <li><a class="nav-link text-danger" href="<?= BASE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a></li>
            </ul>
        </aside>

        <!-- Contenido principal -->
        <main class="content">
            <div class="welcome-box mb-4">
                <div>
                    <h4>Detalles del Paseo</h4>
                    <p>Información completa sobre tu paseo 🐾</p>
                </div>
                <a href="<?= $backUrl ?>" class="btn btn-light text-success fw-semibold">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
            </div>

            <div class="card-premium p-4">
                <div class="row g-4 info-box">
                    <div class="col-md-6">
                        <h5><i class="fas fa-calendar-alt me-2 text-success"></i> Fecha del paseo</h5>
                        <p><?= $fecha ?></p>

                        <h5><i class="fas fa-user-tie me-2 text-success"></i> Paseador</h5>
                        <p><?= $paseador ?></p>

                        <h5><i class="fas fa-dog me-2 text-success"></i> Mascota</h5>
                        <p><?= $mascota ?></p>
                    </div>

                    <div class="col-md-6">
                        <h5><i class="fas fa-stopwatch me-2 text-success"></i> Duración</h5>
                        <p><?= (int)$paseo['duracion'] ?> minutos</p>

                        <h5><i class="fas fa-wallet me-2 text-success"></i> Monto</h5>
                        <p>₲ <?= $monto ?></p>

                        <h5><i class="fas fa-info-circle me-2 text-success"></i> Estado</h5>
                        <span class="badge 
                            <?= $estado === 'Completo' ? 'bg-success' : ($estado === 'Cancelado' ? 'bg-danger' : 'bg-warning text-dark') ?>">
                            <?= $estado ?>
                        </span>
                    </div>

                    <div class="col-12 mt-3">
                        <h5><i class="fas fa-comment-dots me-2 text-success"></i> Observaciones</h5>
                        <p class="border rounded p-2 bg-light"><?= $observacion ?></p>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="<?= $backUrl ?>" class="btn btn-outline-secondary px-4 py-2">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>

                    <?php if (in_array(strtolower($paseo['estado']), ['pendiente', 'confirmado'])): ?>
                        <a href="<?= $baseFeatures; ?>/CancelarPaseo.php?id=<?= $paseoId ?>" class="btn btn-outline-danger px-4 py-2">
                            <i class="fas fa-ban me-1"></i> Cancelar Paseo
                        </a>

                        <a href="<?= $baseFeatures; ?>/pago_paseo_dueno.php?paseo_id=<?= $paseoId ?>" class="btn btn-gradient px-4 py-2">
                            <i class="fas fa-wallet me-1"></i> Pagar Paseo
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const toggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        toggle.addEventListener('click', () => sidebar.classList.toggle('show'));
    </script>
</body>

</html>