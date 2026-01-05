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
$paseo = $paseoCtrl->show($paseoId); // trae joins

if (!$paseo) {
    exit('No se encontró el paseo.');
}

// Validamos que sea del paseador logueado
if ((int)($paseo['paseador_id'] ?? 0) !== (int)(Session::getUsuarioId() ?? 0)) {
    exit('No tienes permiso para completar este paseo.');
}

$rol          = Session::getUsuarioRol() ?: 'paseador';
$baseFeatures = BASE_URL . "/features/{$rol}";

// === Acción: completar paseo ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comentario = trim($_POST['comentario'] ?? '');
    $resp       = $paseoCtrl->completarPaseo($paseoId, $comentario);

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

$fecha    = !empty($paseo['inicio']) ? date('d/m/Y H:i', strtotime($paseo['inicio'])) : '—';
$mascota  = h($paseo['mascota_nombre'] ?? '—');
$duracion = h($paseo['duracion'] ?? $paseo['duracion_min'] ?? '—');
$monto    = number_format((float)($paseo['precio_total'] ?? 0), 0, ',', '.');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completar Paseo - Paseador | Jaguata</title>

    <!-- CSS global Jaguata -->
    <link href="<?= ASSETS_URL; ?>/css/jaguata-theme.css" rel="stylesheet">

    <!-- Bootstrap + FontAwesome + SweetAlert -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
</head>

<body>
    <!-- Botón hamburguesa mobile -->
    <button class="btn btn-outline-secondary d-md-none ms-2 mt-2" id="toggleSidebar">
        <i class="fas fa-bars"></i>
    </button>

    <div class="layout">
        <!-- Sidebar unificado -->
        <?php include __DIR__ . '/../../src/Templates/SidebarPaseador.php'; ?>

        <!-- Contenido principal -->
        <main class="content bg-light">
            <div class="container-fluid py-2">

                <div class="header-box header-dashboard mb-2">
                    <div>
                        <h1 class="h4 mb-1">
                            <i class="fas fa-check-circle me-2"></i> Completar paseo
                        </h1>
                        <p class="mb-0 text-white-50">
                            Confirmá que el paseo se completó correctamente y dejá un comentario opcional.
                        </p>
                    </div>
                    <a href="<?= $baseFeatures; ?>/MisPaseos.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>

                <?php if (!empty($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-triangle-exclamation me-1"></i> <?= h($_SESSION['error']); ?>
                        <?php unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card jag-card shadow-sm">
                    <div class="card-body">
                        <h5 class="mb-3">
                            <i class="fas fa-info-circle me-2 text-success"></i>
                            Detalles del paseo
                        </h5>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="mb-1">
                                    <strong><i class="fas fa-paw me-2 text-success"></i>Mascota:</strong> <?= $mascota ?>
                                </p>
                                <p class="mb-1">
                                    <strong><i class="fas fa-calendar me-2 text-secondary"></i>Fecha:</strong> <?= $fecha ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1">
                                    <strong><i class="fas fa-hourglass-half me-2 text-secondary"></i>Duración:</strong>
                                    <?= $duracion ?> min
                                </p>
                                <p class="mb-1">
                                    <strong><i class="fas fa-dollar-sign me-2 text-secondary"></i>Monto:</strong>
                                    ₲ <?= $monto ?>
                                </p>
                            </div>
                        </div>

                        <hr>

                        <form method="POST" id="formCompletar">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-comment-dots me-1 text-success"></i>
                                    Comentario del paseo (opcional)
                                </label>
                                <textarea class="form-control" name="comentario" rows="3"
                                    placeholder="Ej: Todo salió bien, la mascota fue tranquila..."></textarea>
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-3">
                                <a href="<?= $baseFeatures; ?>/MisPaseos.php" class="btn btn-outline-secondary px-4">
                                    <i class="fas fa-times me-1"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-success px-4">
                                    <i class="fas fa-check me-1"></i> Marcar como completado
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <footer class="text-center text-muted small mt-4">
                    &copy; <?= date('Y'); ?> Jaguata — Panel de Paseador
                </footer>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Toggle sidebar
        document.getElementById('toggleSidebar')?.addEventListener('click', function() {
            document.getElementById('sidebar')?.classList.toggle('sidebar-open');
        });

        const form = document.getElementById('formCompletar');
        if (form) {
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
        }
    </script>
</body>

</html>