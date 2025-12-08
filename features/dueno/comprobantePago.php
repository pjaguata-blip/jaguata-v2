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

/* 🔒 Solo dueño */
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

/* Servicio de vista de pago */
$svc  = new PagoViewService();
$data = $svc->getPagoFull($pagoId);
if (!$data) {
    http_response_code(404);
    exit('Pago no encontrado');
}

/* Seguridad: solo el dueño del paseo puede ver su comprobante */
if ((int)$data['dueno_id'] !== (int)Session::getUsuarioId()) {
    http_response_code(403);
    exit('No autorizado');
}

/* Formateos */
$fechaPago  = $data['pagado_en'] ? date('d/m/Y H:i', strtotime($data['pagado_en'])) : '-';
$fechaPaseo = $data['inicio']    ? date('d/m/Y H:i', strtotime($data['inicio']))   : '-';
$duracion   = $data['duracion_min'] ? $data['duracion_min'] . ' min' : '—';
$montoGs    = number_format((float)$data['monto'], 0, ',', '.');
$metodo     = ucfirst($data['metodo'] ?? '');
$aliasTxt   = $data['alias_transferencia'] ?: ($data['alias_cuenta'] ?: $data['cuenta_numero']);
$bancoTxt   = $data['banco_nombre'] ?: '—';
$estadoPago = ucfirst($data['estado_pago'] ?? '');
$ref        = $data['referencia'] ?? '';

/* Rutas / contexto UI */
$rolMenu       = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures  = BASE_URL . "/features/{$rolMenu}";
$usuarioNombre = h(Session::getUsuarioNombre() ?? 'Dueño/a');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <title>Comprobante de pago - Jaguata</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <!-- CSS global -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: #fff !important;
            }

            .card {
                box-shadow: none !important;
                border: none !important;
            }

            main {
                margin: 0 !important;
                padding: 0 !important;
            }
        }
    </style>
</head>

<body>
    <!-- Sidebar dueño -->
    <?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>

    <main class="bg-light">
        <div class="container-fluid py-4">

            <!-- Header -->
            <div class="header-box header-finanzas mb-4">
                <div>
                    <h1 class="fw-bold mb-1">
                        <i class="fas fa-receipt me-2"></i>Comprobante de pago
                    </h1>
                    <p class="mb-0">
                        Comprobante generado para el dueño <strong><?= h($data['dueno_nombre']); ?></strong>.
                    </p>
                </div>
                <div class="no-print d-flex gap-2">
                    <div class="d-none d-md-block">
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

                <!-- Contenido del comprobante -->
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white fw-semibold">
                        <i class="fas fa-wallet me-2"></i>Resumen del pago
                    </div>
                    <div class="card-body">
                        <div class="row g-2 small text-white p-3 rounded" style="background:#343a40;">
                            <div class="col-md-6">
                                <strong>Dueño:</strong> <?= h($data['dueno_nombre']); ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Paseador:</strong> <?= h($data['paseador_nombre']); ?>
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

                            <?php if (($data['metodo'] ?? '') === 'transferencia'): ?>
                                <div class="col-md-6">
                                    <strong>Banco:</strong> <?= h($bancoTxt); ?>
                                </div>
                                <div class="col-md-6">
                                    <strong>Alias/Cuenta:</strong> <?= h($aliasTxt ?: '—'); ?>
                                </div>
                            <?php endif; ?>

                            <div class="col-md-6">
                                <strong>Estado del pago:</strong>
                                <span class="badge bg-secondary"><?= h($estadoPago); ?></span>
                            </div>
                            <div class="col-md-6">
                                <strong>Fecha del pago:</strong> <?= h($fechaPago); ?>
                            </div>

                            <?php if ($ref): ?>
                                <div class="col-12">
                                    <strong>Referencia:</strong> <?= h($ref); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mt-3 small text-muted">
                            Este comprobante corresponde al pago del paseo asociado al ID de paseo
                            <strong>#<?= (int)$data['paseo_id']; ?></strong> dentro del sistema Jaguata.
                        </div>
                    </div>
                </div>

                <footer class="mt-4 text-center text-muted small">
                    © <?= date('Y'); ?> Jaguata — Panel del Dueño
                </footer>
            </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>