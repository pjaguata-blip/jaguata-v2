<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';
require_once __DIR__ . '/../../src/Services/PagoViewService.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Helpers\Session;
use Jaguata\Services\PagoViewService;

AppConfig::init();
(new AuthController())->checkRole('dueno');

/* Helpers */
function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/* ID pago */
$pagoId = isset($_GET['pago_id']) ? (int)$_GET['pago_id'] : 0;
if ($pagoId <= 0) {
    http_response_code(400);
    exit('Falta pago_id');
}

/* Servicio */
$svc  = new PagoViewService();
$data = $svc->getPagoFull($pagoId);
if (!$data) {
    http_response_code(404);
    exit('Pago no encontrado');
}

/* Seguridad: solo el dueño del paseo puede ver */
if ((int)($data['dueno_id'] ?? 0) !== (int)(Session::getUsuarioId() ?? 0)) {
    http_response_code(403);
    exit('No autorizado');
}

/* Formateos */
$fechaPago  = !empty($data['pagado_en']) ? date('d/m/Y H:i', strtotime((string)$data['pagado_en'])) : '—';
$fechaPaseo = !empty($data['inicio'])    ? date('d/m/Y H:i', strtotime((string)$data['inicio']))    : '—';

$durMin  = (int)($data['duracion_min'] ?? $data['duracion'] ?? 0);
$duracion = $durMin > 0 ? ($durMin . ' min') : '—';

$montoGs  = number_format((float)($data['monto'] ?? 0), 0, ',', '.');
$metodoDb = strtolower(trim((string)($data['metodo'] ?? '')));
$metodo   = $metodoDb ? ucfirst($metodoDb) : '—';

$bancoTxt = (string)($data['banco_nombre'] ?? $data['banco'] ?? '');
$bancoTxt = $bancoTxt !== '' ? $bancoTxt : '—';

$aliasTxt = (string)($data['alias_transferencia'] ?? $data['alias_cuenta'] ?? $data['alias'] ?? '');
$cuenta   = (string)($data['cuenta_numero'] ?? $data['cuenta'] ?? '');
$aliasCuenta = $aliasTxt !== '' ? $aliasTxt : ($cuenta !== '' ? $cuenta : '—');

$estadoPagoDb = strtolower(trim((string)($data['estado_pago'] ?? $data['estado'] ?? 'pendiente')));
$estadoPago   = $estadoPagoDb !== '' ? ucfirst($estadoPagoDb) : 'Pendiente';

$ref = trim((string)($data['referencia'] ?? ''));

/* Mascotas (soporta 1 o 2) */
$mas1 = trim((string)(
    $data['mascota_nombre_1']
    ?? $data['nombre_mascota_1']
    ?? $data['mascota']
    ?? $data['nombre_mascota']
    ?? ''
));
$mas2 = trim((string)(
    $data['mascota_nombre_2']
    ?? $data['nombre_mascota_2']
    ?? ''
));

$mascotasTxt = $mas1 !== '' ? $mas1 : '—';
if ($mas2 !== '') {
    $mascotasTxt .= ' + ' . $mas2 . ' (2 mascotas)';
}

/* Comprobante (archivo) */
$comprobante = trim((string)($data['comprobante'] ?? '')); // viene de pagos.comprobante
// Ajustá esta ruta si tus comprobantes están en otra carpeta:
$comprobanteUrl = $comprobante !== '' ? (BASE_URL . '/uploads/comprobantes/' . rawurlencode($comprobante)) : '';

/* UI */
$rolMenu      = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures = BASE_URL . "/features/{$rolMenu}";
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <title>Comprobante de pago - Jaguata</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <!-- Bootstrap + FA -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />

    <!-- Theme global -->
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <style>
        html,
        body {
            height: 100%;
        }

        body {
            background: var(--gris-fondo, #f4f6f9);
        }

        main.main-content {
            margin-left: 260px;
            min-height: 100vh;
            padding: 24px;
        }

        @media (max-width: 768px) {
            main.main-content {
                margin-left: 0;
                padding: 16px;
            }
        }

        .btn-volver {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .45rem .9rem;
            border-radius: 999px;
            border: 1px solid rgba(0, 0, 0, .15);
            font-size: .9rem;
            text-decoration: none;
            color: #333;
            background: #fff;
        }

        .btn-volver:hover {
            background: #f1f1f1;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .25rem .6rem;
            border-radius: 999px;
            font-size: .85rem;
            border: 1px solid rgba(255, 255, 255, .25);
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: #fff !important;
            }

            .card {
                box-shadow: none !important;
            }

            main.main-content {
                margin: 0 !important;
                padding: 0 !important;
            }
        }
    </style>
</head>

<body>

    <!-- Sidebar dueño -->
    <?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>

   

    <main class="main-content">
        <div class="py-2">

            <!-- Header -->
            <div class="header-box header-finanzas mb-3 d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="fw-bold mb-1">
                        <i class="fas fa-receipt me-2"></i> Comprobante de pago
                    </h1>
                    <p class="mb-0">
                        Comprobante generado para el dueño <strong><?= h($data['dueno_nombre'] ?? ''); ?></strong>.
                    </p>
                </div>

                <div class="no-print d-flex gap-2 align-items-center">
                    <a href="<?= $baseFeatures; ?>/Dashboard.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>

                    <button class="btn btn-outline-light btn-sm" onclick="window.print()">
                        <i class="fas fa-print me-1"></i> Imprimir
                    </button>

                    <a class="btn btn-light btn-sm"
                        href="<?= $baseFeatures; ?>/comprobante_pago_pdf.php?pago_id=<?= (int)$pagoId ?>">
                        <i class="fas fa-file-pdf me-1 text-danger"></i> Descargar PDF
                    </a>
                </div>
            </div>

            <!-- Volver a lista -->
            <div class="mb-3 no-print">
                <a href="<?= $baseFeatures; ?>/Pago.php" class="btn-volver">
                    <i class="fas fa-arrow-left"></i> Volver a pagos
                </a>
            </div>

            <!-- Card principal -->
            <div class="card jag-card shadow-sm mb-4">
                <div class="card-header bg-success text-white fw-semibold d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-wallet me-2"></i> Resumen del pago
                    </div>

                    <?php
                    $badge = match ($estadoPagoDb) {
                        'confirmado', 'procesado' => 'bg-success',
                        'pendiente'              => 'bg-warning text-dark',
                        'rechazado', 'cancelado' => 'bg-danger',
                        default                  => 'bg-secondary'
                    };
                    ?>
                    <span class="pill <?= $badge ?>">
                        <i class="fas fa-circle-check"></i> <?= h($estadoPago); ?>
                    </span>
                </div>

                <div class="card-body">

                    <div class="row g-3">
                        <div class="col-lg-6">
                            <div class="p-3 rounded text-white" style="background:#343a40;">
                                <div class="row g-2 small">

                                    <div class="col-md-6">
                                        <strong>Dueño:</strong> <?= h($data['dueno_nombre'] ?? '—'); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Paseador:</strong> <?= h($data['paseador_nombre'] ?? '—'); ?>
                                    </div>

                                    <div class="col-md-6">
                                        <strong>Mascotas:</strong> <?= h($mascotasTxt); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>ID Paseo:</strong> #<?= (int)($data['paseo_id'] ?? 0); ?>
                                    </div>

                                    <div class="col-md-6">
                                        <strong>Fecha del paseo:</strong> <?= h($fechaPaseo); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Duración:</strong> <?= h($duracion); ?>
                                    </div>

                                    <div class="col-md-6">
                                        <strong>Método:</strong> <?= h($metodo); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Monto:</strong> ₲ <?= $montoGs; ?>
                                    </div>

                                    <?php if ($metodoDb === 'transferencia'): ?>
                                        <div class="col-md-6">
                                            <strong>Banco:</strong> <?= h($bancoTxt); ?>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Alias/Cuenta:</strong> <?= h($aliasCuenta); ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="col-md-6">
                                        <strong>Fecha del pago:</strong> <?= h($fechaPago); ?>
                                    </div>

                                    <?php if ($ref !== ''): ?>
                                        <div class="col-12">
                                            <strong>Referencia:</strong> <?= h($ref); ?>
                                        </div>
                                    <?php endif; ?>

                                </div>
                            </div>

                            <div class="mt-3 small text-muted">
                                Este comprobante corresponde al pago del paseo asociado al
                                <strong>ID #<?= (int)($data['paseo_id'] ?? 0); ?></strong> dentro del sistema Jaguata.
                            </div>
                        </div>

                        <!-- Comprobante (archivo) -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-light fw-semibold">
                                    <i class="fas fa-paperclip me-2"></i> Comprobante adjunto
                                </div>
                                <div class="card-body">

                                    <?php if ($comprobanteUrl === ''): ?>
                                        <div class="text-center text-muted py-4">
                                            <i class="fas fa-file-circle-xmark fa-2x mb-2"></i>
                                            <div>No hay comprobante cargado.</div>
                                        </div>
                                    <?php else: ?>
                                        <div class="d-flex flex-wrap gap-2 mb-3 no-print">
                                            <a class="btn btn-outline-primary btn-sm" href="<?= h($comprobanteUrl) ?>" target="_blank">
                                                <i class="fas fa-eye me-1"></i> Ver
                                            </a>
                                            <a class="btn btn-outline-success btn-sm" href="<?= h($comprobanteUrl) ?>" download>
                                                <i class="fas fa-download me-1"></i> Descargar
                                            </a>
                                        </div>

                                        <?php
                                        $ext = strtolower(pathinfo($comprobante, PATHINFO_EXTENSION));
                                        $isImg = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
                                        $isPdf = ($ext === 'pdf');
                                        ?>

                                        <?php if ($isImg): ?>
                                            <img src="<?= h($comprobanteUrl) ?>"
                                                class="img-fluid rounded border"
                                                alt="Comprobante">
                                        <?php elseif ($isPdf): ?>
                                            <div class="ratio ratio-4x3">
                                                <iframe src="<?= h($comprobanteUrl) ?>" title="Comprobante PDF"></iframe>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-info mb-0">
                                                Archivo: <strong><?= h($comprobante) ?></strong><br>
                                                No se puede previsualizar este tipo de archivo, pero podés descargarlo.
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <footer class="mt-4 text-center text-muted small">
                © <?= date('Y'); ?> Jaguata — Panel del Dueño
            </footer>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Toggle sidebar en mobile
        document.getElementById('toggleSidebar')?.addEventListener('click', function() {
            document.getElementById('sidebar')?.classList.toggle('sidebar-open');
        });
    </script>
</body>

</html>