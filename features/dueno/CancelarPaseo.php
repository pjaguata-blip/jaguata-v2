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

/* 🔒 Auth dueño */
$auth = new AuthController();
$auth->checkRole('dueno');

/* Base */
$rol          = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures = BASE_URL . "/features/{$rol}";
$backUrl      = $baseFeatures . "/MisPaseos.php";

/* ID */
$paseoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($paseoId <= 0) {
    exit('ID de paseo no válido.');
}

/* Datos (con JOINs) */
$paseoCtrl = new PaseoController();
$paseo     = $paseoCtrl->show($paseoId);

if (!$paseo) {
    exit('No se encontró el paseo.');
}

/* ✅ Seguridad: validar que el paseo sea del dueño logueado */
$duenoSesionId = (int)(Session::getUsuarioId() ?? 0);
$duenoPaseoId  = (int)($paseo['dueno_id'] ?? 0);

if ($duenoSesionId <= 0 || $duenoPaseoId !== $duenoSesionId) {
    exit('No autorizado.');
}

function h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/* ✅ Campos correctos segun PaseoController::show() */
$fecha    = !empty($paseo['inicio']) ? date('d/m/Y H:i', strtotime((string)$paseo['inicio'])) : '—';
$duracion = (int)($paseo['duracion'] ?? 0);
$monto    = number_format((float)($paseo['precio_total'] ?? 0), 0, ',', '.');

$paseador = h($paseo['paseador_nombre'] ?? '—');   // ✅ show() trae paseador_nombre
$mascota  = h($paseo['nombre_mascota'] ?? '—');    // ✅ show() trae nombre_mascota

/* Cancelación */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $motivo = trim((string)($_POST['motivo'] ?? ''));
    $resp   = $paseoCtrl->cancelarPaseo($paseoId, $motivo);

    if (!empty($resp['success'])) {
        $_SESSION['success'] = "El paseo fue cancelado correctamente 🐾";
        header("Location: {$backUrl}");
        exit;
    }

    $_SESSION['error'] = $resp['error'] ?? "No se pudo cancelar el paseo.";
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancelar Paseo - Jaguata</title>

    <!-- Tema + libs -->
    <link href="<?= ASSETS_URL; ?>/css/jaguata-theme.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <style>
        .card-premium {
            border: 0;
            border-radius: 18px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, .06);
            background: #fff;
        }

        .btn-gradient {
            background: linear-gradient(90deg, var(--verde-jaguata, #3c6255), #20c997);
            border: none;
            color: #fff;
            font-weight: 600;
        }

        .btn-gradient:hover {
            opacity: .92;
        }

        .detail-line {
            display: flex;
            gap: 10px;
            align-items: baseline;
            margin-bottom: 8px;
        }

        .detail-line i {
            width: 18px;
        }
    </style>
</head>

<body>

    <!-- Botón hamburguesa -->
    <button class="btn btn-outline-secondary d-md-none ms-2 mt-2" id="toggleSidebar">
        <i class="fas fa-bars"></i>
    </button>

    <div class="layout">
        <?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>

        <main class="content bg-light">
            <div class="container-fluid py-1">

                <div class="header-box header-paseos mb-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h4 mb-1">
                            <i class="fas fa-ban me-2"></i> Cancelar Paseo
                        </h1>
                        <p class="mb-0 text-white-50">
                            Podés cancelar el paseo antes de su inicio programado.
                        </p>
                    </div>
                    <a href="<?= $backUrl ?>" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>

                <?php if (!empty($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-triangle-exclamation me-2"></i><?= h($_SESSION['error']); ?>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <div class="card-premium p-4">
                    <h5 class="mb-3">
                        <i class="fas fa-circle-info me-2 text-success"></i>Detalles del paseo
                    </h5>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="detail-line">
                                <i class="fas fa-user text-secondary"></i>
                                <div><strong>Paseador:</strong> <?= $paseador ?></div>
                            </div>

                            <div class="detail-line">
                                <i class="fas fa-paw text-success"></i>
                                <div><strong>Mascota:</strong> <?= $mascota ?></div>
                            </div>

                            <div class="detail-line">
                                <i class="fas fa-calendar text-primary"></i>
                                <div><strong>Fecha:</strong> <?= h($fecha) ?></div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-line">
                                <i class="fas fa-clock text-warning"></i>
                                <div><strong>Duración:</strong> <?= (int)$duracion ?> min</div>
                            </div>

                            <div class="detail-line">
                                <i class="fas fa-money-bill-wave text-success"></i>
                                <div><strong>Monto:</strong> ₲ <?= h($monto) ?></div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-3">

                    <form method="POST" id="formCancel">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-comment-dots me-1 text-success"></i>Motivo de la cancelación
                            </label>
                            <textarea class="form-control" name="motivo" rows="3"
                                placeholder="Contanos brevemente por qué querés cancelar..."></textarea>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <a href="<?= $backUrl ?>" class="btn btn-outline-secondary px-4">
                                <i class="fas fa-times me-1"></i> Volver
                            </a>
                            <button type="submit" class="btn btn-gradient px-4">
                                <i class="fas fa-ban me-1"></i> Cancelar Paseo
                            </button>
                        </div>
                    </form>
                </div>

                <footer class="text-center text-muted small mt-4">
                    &copy; <?= date('Y') ?> Jaguata — Panel del Dueño
                </footer>

            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.getElementById('toggleSidebar')?.addEventListener('click', function() {
            document.getElementById('sidebar')?.classList.toggle('sidebar-open');
        });

        const form = document.getElementById('formCancel');
        form.addEventListener('submit', e => {
            e.preventDefault();
            Swal.fire({
                title: '¿Confirmás la cancelación?',
                text: 'Esta acción no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3c6255',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, cancelar',
                cancelButtonText: 'No, volver'
            }).then((r) => {
                if (r.isConfirmed) form.submit();
            });
        });
    </script>
</body>

</html>