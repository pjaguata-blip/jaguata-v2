<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/CalificacionController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\CalificacionController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Helpers\Session;

AppConfig::init();

$auth = new AuthController();
$auth->checkRole('dueno');

// --- Paseo ID ---
$paseoId = (int)($_GET['paseo_id'] ?? $_POST['paseo_id'] ?? 0);
if ($paseoId <= 0) {
    http_response_code(400);
    exit('Paseo no válido.');
}

// --- Controladores ---
$paseoCtrl = new PaseoController();
$paseo = $paseoCtrl->getById($paseoId);
if (!$paseo) {
    http_response_code(404);
    exit('Paseo no encontrado.');
}

$ctrl = new CalificacionController();
$msg = null;

// --- Procesamiento ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'paseo_id'     => (int)($_POST['paseo_id'] ?? 0),
        'rated_id'     => (int)($_POST['rated_id'] ?? 0),
        'rater_id'     => (int)Session::get('usuario_id'),
        'tipo'         => 'paseador',
        'calificacion' => (int)($_POST['calificacion'] ?? 0),
        'comentario'   => trim($_POST['comentario'] ?? '')
    ];
    $resp = $ctrl->calificarPaseador($data);
    if (!empty($resp['success'])) {
        $_SESSION['success'] = '¡Gracias por tu calificación!';
        header('Location: PaseosCompletados.php');
        exit;
    } else {
        $msg = $resp['error'] ?? 'No se pudo guardar la calificación.';
    }
}

// --- Datos ---
$paseadorNombre = htmlspecialchars($paseo['paseador_nombre'] ?? 'Paseador');
$paseadorId     = (int)($paseo['paseador_id'] ?? 0);
$fechaPaseo     = !empty($paseo['inicio']) ? date('d/m/Y H:i', strtotime($paseo['inicio'])) : '—';
$duracion       = isset($paseo['duracion']) ? $paseo['duracion'] . ' min' : '—';
$monto          = number_format((float)($paseo['precio_total'] ?? 0), 0, ',', '.');

$rolMenu = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures = BASE_URL . "/features/{$rolMenu}";
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calificar Paseador - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= ASSETS_URL; ?>/css/jaguata-theme.css" rel="stylesheet">
    <style>
        .rating-stars i {
            font-size: 1.8rem;
            color: #ccc;
            cursor: pointer;
            transition: color 0.2s ease-in-out;
        }

        .rating-stars i.selected,
        .rating-stars i:hover,
        .rating-stars i:hover~i {
            color: #ffc107;
        }

        .summary-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem 1.25rem;
            border: 1px solid #e3e3e3;
        }
    </style>
</head>

<body>
    <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>

    <div class="layout">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="text-center mb-4">
                < <img src="<?= ASSETS_URL; ?>/uploads/perfiles/logojag.png" alt="Jaguata" width="50">>
                    <hr class="text-light">
            </div>
            <ul class="nav flex-column gap-1 px-2">
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/Dashboard.php"><i class="fas fa-home"></i> Inicio</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/PaseosCompletados.php"><i class="fas fa-check-circle"></i> Paseos completados</a></li>
                <li><a class="nav-link active" href="#"><i class="fas fa-star"></i> Calificar paseador</a></li>
                <li><a class="nav-link text-danger" href="<?= BASE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a></li>
            </ul>
        </aside>

        <!-- Contenido principal -->
        <main class="content">
            <div class="section-header mb-4">
                <div><i class="fas fa-star"></i> Calificar Paseador</div>
            </div>

            <?php if ($msg): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($msg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header bg-success text-white fw-semibold">
                    <i class="fas fa-receipt me-2"></i>Resumen del paseo
                </div>
                <div class="card-body">
                    <div class="summary-box mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Paseador:</strong> <?= $paseadorNombre ?></p>
                                <p><strong>Fecha:</strong> <?= $fechaPaseo ?></p>
                                <p><strong>Duración:</strong> <?= $duracion ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Monto:</strong> ₲ <?= $monto ?></p>
                            </div>
                        </div>
                    </div>

                    <form method="post" id="ratingForm">
                        <input type="hidden" name="paseo_id" value="<?= $paseoId ?>">
                        <input type="hidden" name="rated_id" value="<?= $paseadorId ?>">

                        <div class="mb-3 text-center">
                            <label class="form-label fw-semibold mb-2">
                                <i class="fas fa-star text-warning me-1"></i>Tu calificación
                            </label>
                            <div class="rating-stars d-inline-flex gap-2" id="starRating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star" data-value="<?= $i ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" name="calificacion" id="ratingValue" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-comment-dots me-1"></i>Comentario (opcional)
                            </label>
                            <textarea class="form-control" name="comentario" rows="3"
                                placeholder="Contanos cómo fue tu experiencia con el paseador..."></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="PaseosCompletados.php" class="btn btn-outline-secondary px-4">
                                <i class="fas fa-arrow-left me-1"></i>Volver
                            </a>
                            <button type="submit" class="btn btn-success px-4">
                                <i class="fas fa-paper-plane me-2"></i>Enviar Calificación
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const toggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        toggle.addEventListener('click', () => sidebar.classList.toggle('show'));

        // Sistema de estrellas
        const stars = document.querySelectorAll('#starRating i');
        const ratingValue = document.getElementById('ratingValue');
        stars.forEach(star => {
            star.addEventListener('click', () => {
                const value = star.dataset.value;
                ratingValue.value = value;
                stars.forEach(s => s.classList.remove('selected'));
                for (let i = 0; i < value; i++) stars[i].classList.add('selected');
            });
        });
    </script>
</body>

</html>