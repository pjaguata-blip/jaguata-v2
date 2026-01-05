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

/* =========================
   Init + Auth
========================= */

AppConfig::init();

$auth = new AuthController();
$auth->checkRole('dueno');

/* =========================
   Helpers
========================= */
function h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/* =========================
   Datos del paseo
========================= */
$paseoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($paseoId <= 0) {
    exit('ID de paseo no válido.');
}

$paseoCtrl = new PaseoController();
$paseo     = $paseoCtrl->getById($paseoId);

if (!$paseo) {
    exit('No se encontró el paseo.');
}

/* =========================
   Rutas / navegación
========================= */
$rol          = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures = BASE_URL . "/features/{$rol}";
$panelUrl     = $baseFeatures . "/Dashboard.php";
$fallbackBack = $baseFeatures . "/MisPaseos.php";

// Back “real”: si venís desde otra pantalla (mismo host), usa referer
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$backUrl = $fallbackBack;
if ($referer) {
    $refHost = parse_url($referer, PHP_URL_HOST);
    $myHost  = $_SERVER['HTTP_HOST'] ?? '';
    if (!$refHost || $refHost === $myHost) {
        $backUrl = $referer;
    }
}

/* Para sidebar template (active) */
$currentFile   = basename($_SERVER['PHP_SELF']);
$inicioUrl     = $panelUrl;
$rolUsuario    = $rol;
$usuarioNombre = Session::getUsuarioNombre() ?? 'Dueño';

/* =========================
   Cancelación
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $motivo = trim($_POST['motivo'] ?? '');
    $resp   = $paseoCtrl->cancelarPaseo($paseoId, $motivo);

    if (!empty($resp['success'])) {
        $_SESSION['success'] = "El paseo fue cancelado correctamente 🐾";
        header("Location: {$fallbackBack}");
        exit;
    }
    $_SESSION['error'] = $resp['error'] ?? "No se pudo cancelar el paseo.";
}

/* =========================
   UI data (según tus campos reales)
========================= */
$fecha = !empty($paseo['inicio'])
    ? date('d/m/Y H:i', strtotime((string)$paseo['inicio']))
    : '—';

$paseador = h($paseo['paseador_nombre'] ?? ($paseo['nombre_paseador'] ?? 'No asignado'));

// Mascotas (tus alias reales en show())
$mascotas = [];
if (!empty($paseo['nombre_mascota']))   $mascotas[] = (string)$paseo['nombre_mascota'];
if (!empty($paseo['nombre_mascota_2'])) $mascotas[] = (string)$paseo['nombre_mascota_2'];

// Si llega por otros listados
if (!$mascotas && !empty($paseo['nombre_mascota'])) $mascotas[] = (string)$paseo['nombre_mascota'];
if (!$mascotas && !empty($paseo['mascota_nombre'])) $mascotas[] = (string)$paseo['mascota_nombre'];

$mascotasTxt = $mascotas ? implode(' • ', array_map('h', $mascotas)) : '—';

$montoBase = $paseo['precio_total'] ?? ($paseo['monto'] ?? 0);
$monto     = number_format((float)$montoBase, 0, ',', '.');

$duracion = h($paseo['duracion'] ?? '—');

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancelar Paseo - Jaguata</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <!-- ✅ CSS GLOBAL (tu tema) -->
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <style>
        .content-wrap {
            padding: 18px 18px 28px;
        }

        .header-cancelar {
            background: linear-gradient(90deg, #20c997, #3c6255);
        }

        .card-soft {
            border: 0;
            border-radius: 16px;
            box-shadow: 0 8px 22px rgba(0, 0, 0, .06);
        }

        /* Sidebar overlay (móvil) */
        .btn-sidebar-toggle {
            position: fixed;
            top: 14px;
            left: 14px;
            z-index: 1300;
        }

        @media (min-width: 992px) {
            .btn-sidebar-toggle {
                display: none;
            }
        }

        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-110%);
                transition: transform .25s ease;
                z-index: 1200;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .mobile-overlay {
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, .35);
                z-index: 1100;
                display: none;
            }

            .mobile-overlay.show {
                display: block;
            }
        }
    </style>
</head>

<body>

    <!-- Toggle solo móvil -->
    <button class="btn btn-light btn-sidebar-toggle shadow-sm" id="btnSidebarToggle" type="button" aria-label="Abrir menú">
        <i class="fas fa-bars"></i>
    </button>
    <div class="mobile-overlay" id="mobileOverlay"></div>

    <!-- ✅ Sidebar por include -->
    <?php
    $sidebarPath = __DIR__ . '/../../src/Templates/SidebarDueno.php';
    if (is_file($sidebarPath)) {
        require_once $sidebarPath;
    }
    ?>

    <main>
        <div class="content-wrap">

            <div class="header-box header-cancelar d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h1 class="fw-bold mb-1">
                        <i class="fas fa-ban me-2"></i>Cancelar Paseo
                    </h1>
                    <p class="mb-0">Podés cancelar el paseo antes de su inicio programado</p>
                </div>

                <a href="<?= h($backUrl) ?>" class="btn btn-light fw-semibold"
                    onclick="if (window.history.length > 1) { event.preventDefault(); window.history.back(); }">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
            </div>

            <!-- Flash messages -->
            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert alert-danger mt-3">
                    <?= h($_SESSION['error']);
                    unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success mt-3">
                    <?= h($_SESSION['success']);
                    unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <div class="container-fluid px-0 mt-3">
                <div class="row g-3">

                    <div class="col-12 col-xl-8">
                        <div class="card card-soft">
                            <div class="card-body p-4">
                                <h5 class="mb-3">
                                    <i class="fas fa-info-circle me-2 text-success"></i>Detalles del paseo
                                </h5>

                                <!-- ✅ ESTIRADO: Mascotas y Fecha a ancho completo -->
                                <div class="row g-3">

                                    <div class="col-12">
                                        <div class="p-3 bg-light rounded-3">
                                            <div class="text-muted small">Paseador</div>
                                            <div class="fw-semibold"><?= $paseador ?></div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="p-3 bg-light rounded-3">
                                            <div class="text-muted small">Mascota(s)</div>
                                            <div class="fw-semibold text-break"><?= $mascotasTxt ?></div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="p-3 bg-light rounded-3">
                                            <div class="text-muted small">Fecha</div>
                                            <div class="fw-semibold"><?= h($fecha) ?></div>
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <div class="p-3 bg-light rounded-3 h-100">
                                            <div class="text-muted small">Duración</div>
                                            <div class="fw-semibold"><?= $duracion ?> min</div>
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <div class="p-3 bg-light rounded-3 h-100">
                                            <div class="text-muted small">Monto</div>
                                            <div class="fw-semibold">₲ <?= $monto ?></div>
                                        </div>
                                    </div>

                                </div>

                                <hr class="my-4">

                                <form method="POST" id="formCancel">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">
                                            <i class="fas fa-comment-dots me-1 text-success"></i>
                                            Motivo de la cancelación
                                        </label>
                                        <textarea class="form-control" name="motivo" rows="4"
                                            placeholder="Contanos brevemente por qué querés cancelar..."></textarea>
                                    </div>

                                    <div class="d-flex flex-column flex-sm-row justify-content-end gap-2 mt-3">
                                        <a href="<?= h($backUrl) ?>" class="btn btn-outline-secondary px-4"
                                            onclick="if (window.history.length > 1) { event.preventDefault(); window.history.back(); }">
                                            <i class="fas fa-times me-1"></i> Volver
                                        </a>
                                        <button type="submit" class="btn btn-success px-4">
                                            <i class="fas fa-ban me-1"></i> Cancelar Paseo
                                        </button>
                                    </div>
                                </form>

                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-4">
                        <div class="card card-soft">
                            <div class="card-body p-4">
                                <h6 class="fw-bold mb-2">
                                    <i class="fas fa-triangle-exclamation me-2 text-warning"></i>Importante
                                </h6>
                                <p class="mb-2 text-muted">
                                    Esta acción no se puede deshacer. Si necesitás reagendar, cancelá y volvé a solicitar.
                                </p>
                                <div class="alert alert-light mb-0">
                                    <i class="fas fa-shield-dog me-1"></i>
                                    Jaguata recomienda cancelar con anticipación para no afectar reputación.
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <footer class="mt-4">
                    <small>© <?= date('Y') ?> Jaguata — Panel Dueño</small>
                </footer>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Sidebar móvil (funciona aunque el template no tenga id="sidebar")
        const btn = document.getElementById('btnSidebarToggle');
        const sidebar = document.querySelector('.sidebar'); // ✅ busca por clase
        const overlay = document.getElementById('mobileOverlay');

        function openSidebar() {
            if (sidebar) sidebar.classList.add('show');
            if (overlay) overlay.classList.add('show');
        }

        function closeSidebar() {
            if (sidebar) sidebar.classList.remove('show');
            if (overlay) overlay.classList.remove('show');
        }

        btn?.addEventListener('click', () => {
            if (!sidebar) return;
            sidebar.classList.contains('show') ? closeSidebar() : openSidebar();
        });
        overlay?.addEventListener('click', closeSidebar);

        // Confirmación SweetAlert
        const form = document.getElementById('formCancel');
        form?.addEventListener('submit', (e) => {
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