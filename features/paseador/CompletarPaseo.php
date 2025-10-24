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

$paseoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($paseoId <= 0) {
    exit('ID de paseo no válido.');
}

$paseoCtrl = new PaseoController();
$paseo = $paseoCtrl->getById($paseoId);

if (!$paseo) {
    exit('No se encontró el paseo.');
}

$rol = Session::getUsuarioRol() ?: 'paseador';
$baseFeatures = BASE_URL . "/features/{$rol}";

// === Acción: completar paseo ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comentario = trim($_POST['comentario'] ?? '');
    $resp = $paseoCtrl->completarPaseo($paseoId, $comentario);

    if (!empty($resp['success'])) {
        $_SESSION['success'] = "El paseo fue marcado como completado correctamente 🐾";
        header("Location: {$baseFeatures}/MisPaseos.php");
        exit;
    } else {
        $_SESSION['error'] = $resp['error'] ?? "No se pudo completar el paseo.";
    }
}

// Helper
function h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$fecha = !empty($paseo['fecha_inicio']) ? date('d/m/Y H:i', strtotime($paseo['fecha_inicio'])) : '—';
$mascota = h($paseo['mascota_nombre'] ?? '—');
$duracion = h($paseo['duracion'] ?? '—');
$monto = number_format((float)($paseo['monto'] ?? 0), 0, ',', '.');
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completar Paseo - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
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

        .btn-outline-secondary {
            border-color: #3c6255;
            color: #3c6255;
        }

        .btn-outline-secondary:hover {
            background-color: #3c6255;
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
                    <img src="../../assets/img/logo.png" alt="Jaguata" width="120" class="mb-3">
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

            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="page-header">
                    <h2><i class="fas fa-check-circle me-2"></i> Completar Paseo</h2>
                    <a href="<?= $baseFeatures; ?>/MisPaseos.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>

                <div class="card-premium">
                    <h5 class="mb-3"><i class="fas fa-info-circle me-2 text-success"></i> Detalles del paseo</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong><i class="fas fa-paw me-2 text-success"></i>Mascota:</strong> <?= $mascota ?></p>
                            <p><strong><i class="fas fa-calendar me-2 text-secondary"></i>Fecha:</strong> <?= $fecha ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong><i class="fas fa-hourglass-half me-2 text-secondary"></i>Duración:</strong> <?= $duracion ?> min</p>
                            <p><strong><i class="fas fa-dollar-sign me-2 text-secondary"></i>Monto:</strong> ₲ <?= $monto ?></p>
                        </div>
                    </div>
                    <hr>

                    <form method="POST" id="formCompletar">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-comment-dots me-1 text-success"></i> Comentario del paseo (opcional)
                            </label>
                            <textarea class="form-control" name="comentario" rows="3" placeholder="Ej: Todo salió bien, la mascota fue tranquila..."></textarea>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <a href="<?= $baseFeatures; ?>/MisPaseos.php" class="btn btn-outline-secondary px-4">
                                <i class="fas fa-times me-1"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-gradient px-4">
                                <i class="fas fa-check me-1"></i> Marcar como Completado
                            </button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const form = document.getElementById('formCompletar');
        form.addEventListener('submit', e => {
            e.preventDefault();
            Swal.fire({
                title: '¿Confirmás que el paseo fue completado?',
                text: 'El dueño será notificado del estado.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3c6255',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, marcar completado',
                cancelButtonText: 'No, volver'
            }).then((r) => {
                if (r.isConfirmed) form.submit();
            });
        });
    </script>
</body>

</html>