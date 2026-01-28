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

// Solo paseador
$auth = new AuthController();
$auth->checkRole('paseador');

function h($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$paseoId = (int)($_GET['id'] ?? 0);
if ($paseoId <= 0) {
    die('ID de paseo inválido.');
}

$paseoController = new PaseoController();
$paseo           = $paseoController->show($paseoId);
if (!$paseo) {
    die('No se encontró el paseo.');
}

// Validar que el paseo pertenezca al paseador logueado
if ((int)($paseo['paseador_id'] ?? 0) !== (int)(Session::getUsuarioId() ?? 0)) {
    die('No tienes permiso para ver este paseo.');
}

$estado = strtolower((string)($paseo['estado'] ?? ''));
$badge  = match ($estado) {
    'confirmado' => 'warning',
    'en_curso'   => 'info',
    'completo'   => 'success',
    'cancelado'  => 'danger',
    default      => 'secondary',
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
    <link rel="stylesheet" href="<?= BASE_URL; ?>/assets/css/jaguata-theme.css">
</head>

<body class="bg-jaguata">
    <div class="d-flex min-vh-100">
        <!-- Sidebar paseador (reutilizado) -->
        <?php include __DIR__ . '/sidebarpaseador.php'; ?>

        <!-- Contenido principal -->
        <main class="flex-grow-1 p-4">
            <div class="container-fluid">

                <!-- Header / migas -->
                <div class="page-header-jaguata d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0">
                        <i class="fas fa-walking me-2"></i> Detalle del Paseo
                    </h2>
                    <a href="<?= $baseFeatures; ?>/MisPaseos.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>

                <!-- Mensajes flash -->
                <?php if (!empty($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= h($_SESSION['success']);
                        unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= h($_SESSION['error']);
                        unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Card principal -->
                <div class="card card-jaguata shadow-sm">
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="mb-2">
                                    <strong><i class="fas fa-paw text-success me-2"></i>Mascota:</strong>
                                    <?= h($paseo['nombre_mascota'] ?? '—') ?>
                                </p>
                                <p class="mb-2">
                                    <strong><i class="fas fa-user text-secondary me-2"></i>Dueño:</strong>
                                    <?= h($paseo['nombre_dueno'] ?? '—') ?>
                                </p>
                                <p class="mb-2">
                                    <strong><i class="fas fa-calendar me-2"></i>Fecha:</strong>
                                    <?= isset($paseo['inicio']) ? date('d/m/Y H:i', strtotime($paseo['inicio'])) : '—' ?>
                                </p>
                                <p class="mb-2">
                                    <strong><i class="fas fa-hourglass-half me-2"></i>Duración:</strong>
                                    <?= h($paseo['duracion'] ?? $paseo['duracion_min'] ?? '—') ?> min
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2">
                                    <strong><i class="fas fa-map-marker-alt me-2"></i>Dirección:</strong>
                                    <?= h($paseo['direccion'] ?? '—') ?>
                                </p>
                                <p class="mb-2">
                                    <strong><i class="fas fa-dollar-sign me-2"></i>Precio Total:</strong>
                                    ₲<?= number_format((float)($paseo['precio_total'] ?? 0), 0, ',', '.') ?>
                                </p>
                                <p class="mb-2">
                                    <strong><i class="fas fa-info-circle me-2"></i>Estado:</strong>
                                    <span class="badge bg-<?= $badge ?>"><?= ucfirst($estado ?: '—') ?></span>
                                </p>
                            </div>
                        </div>

                        <hr>

                        <!-- Botones de acción -->
                        <div class="mt-3 d-flex flex-wrap gap-2">
                            <?php if ($estado === 'confirmado'): ?>
                                <a href="AccionPaseo.php?id=<?= (int)$paseo['paseo_id'] ?>&accion=iniciar"
                                    class="btn btn-jaguata-primary"
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
                                    class="btn btn-jaguata-primary"
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
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>