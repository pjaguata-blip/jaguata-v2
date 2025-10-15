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

// --- Inicialización y seguridad ---
AppConfig::init();
$auth = new AuthController();
$auth->checkRole('dueno');

// --- Paseo ID seguro ---
$paseoId = 0;
if (isset($_GET['paseo_id'])) {
    $paseoId = (int)$_GET['paseo_id'];
} elseif (isset($_POST['paseo_id'])) {
    $paseoId = (int)$_POST['paseo_id'];
}
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

// --- Procesamiento del formulario ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'paseo_id'    => (int)($_POST['paseo_id'] ?? 0),
        'rated_id'    => (int)($_POST['rated_id'] ?? 0),
        'rater_id'    => (int)Session::get('usuario_id'),
        'tipo'        => 'paseador',
        'calificacion' => (int)($_POST['calificacion'] ?? 0),
        'comentario'  => trim($_POST['comentario'] ?? '')
    ];
    $resp = $ctrl->calificarPaseador($data);
    if (!empty($resp['success'])) {
        $_SESSION['success'] = '¡Gracias por tu calificación!';
        header('Location: MisPaseos.php');
        exit;
    } else {
        $msg = $resp['error'] ?? 'No se pudo guardar la calificación.';
    }
}

// --- Datos de vista ---
$paseadorNombre = htmlspecialchars($paseo['paseador_nombre'] ?? 'Paseador');
$paseadorId     = (int)($paseo['paseador_id'] ?? 0);
$fechaPaseo     = !empty($paseo['inicio']) ? date('d/m/Y H:i', strtotime($paseo['inicio'])) : '—';
$duracion       = isset($paseo['duracion']) ? $paseo['duracion'] . ' min' : '—';
$monto          = number_format((float)($paseo['precio_total'] ?? 0), 0, ',', '.');
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Calificar Paseador - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <link href="../../assets/css/style.css" rel="stylesheet" />
</head>

<body>
    <?php include __DIR__ . '/../../src/Templates/Header.php'; ?>
    <?php include __DIR__ . '/../../src/Templates/Navbar.php'; ?>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-7 col-md-8">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h1 class="h4 mb-0">
                        <i class="fas fa-star text-warning me-2"></i> Calificar Paseador
                    </h1>
                    <a href="MisPaseos.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>

                <?php if ($msg): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($msg) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-dark text-white">
                        <strong>Resumen del Paseo</strong>
                    </div>
                    <div class="card-body small text-white rounded"
                        style="background:#343a40">
                        <div class="row g-2">
                            <div class="col-md-6"><strong>Paseador:</strong> <?= $paseadorNombre ?></div>
                            <div class="col-md-6"><strong>Fecha:</strong> <?= $fechaPaseo ?></div>
                            <div class="col-md-6"><strong>Duración:</strong> <?= htmlspecialchars($duracion) ?></div>
                            <div class="col-md-6"><strong>Monto:</strong> ₲ <?= $monto ?></div>
                        </div>
                    </div>

                    <form method="post" class="card-body">
                        <input type="hidden" name="paseo_id" value="<?= $paseoId ?>" />
                        <input type="hidden" name="rated_id" value="<?= $paseadorId ?>" />

                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-star text-warning me-1"></i>
                                Puntuación
                            </label>
                            <select class="form-select" name="calificacion" required>
                                <option value="">Seleccionar...</option>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?= $i ?>">⭐ <?= $i ?> <?= $i === 1 ? 'estrella' : 'estrellas' ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-comment-dots me-1"></i>
                                Comentario (opcional)
                            </label>
                            <textarea class="form-control" name="comentario" rows="3"
                                placeholder="Contanos cómo fue la experiencia con tu paseador..."></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-1"></i> Enviar Calificación
                            </button>
                            <a href="MisPaseos.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../../src/Templates/Footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>