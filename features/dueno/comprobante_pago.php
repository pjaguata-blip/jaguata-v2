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

$pagoId = isset($_GET['pago_id']) ? (int)$_GET['pago_id'] : 0;
if ($pagoId <= 0) {
    http_response_code(400);
    exit('Falta pago_id');
}

$svc = new PagoViewService();
$data = $svc->getPagoFull($pagoId);
if (!$data) {
    http_response_code(404);
    exit('Pago no encontrado');
}

// Seguridad: el comprobante del dueño solo si es el dueño del paseo
if ((int)$data['dueno_id'] !== (int)Session::getUsuarioId()) {
    http_response_code(403);
    exit('No autorizado');
}

$fechaPago  = $data['pagado_en'] ? date('d/m/Y H:i', strtotime($data['pagado_en'])) : '-';
$fechaPaseo = $data['inicio']    ? date('d/m/Y H:i', strtotime($data['inicio']))   : '-';
$duracion   = $data['duracion_min'] ? $data['duracion_min'] . ' min' : '—';
$montoGs    = number_format((float)$data['monto'], 0, ',', '.');
$metodo     = ucfirst($data['metodo']);
$aliasTxt   = $data['alias_transferencia'] ?: ($data['alias_cuenta'] ?: $data['cuenta_numero']);
$bancoTxt   = $data['banco_nombre'] ?: '—';
$estadoPago = ucfirst($data['estado_pago']);
$ref        = $data['referencia']; // si guardaste archivo o nro tx

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <title>Comprobante de pago</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="<?= ASSETS_URL; ?>/css/jaguata-theme.css" rel="stylesheet">

    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            .card {
                box-shadow: none !important;
                border: none !important;
            }
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../../src/Templates/Header.php'; ?>
    <?php include __DIR__ . '/../../src/Templates/Navbar.php'; ?>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Comprobante de pago</h1>
            <div class="no-print d-flex gap-2">
                <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">Imprimir</button>
                <!-- Opcional PDF con Dompdf -->
                <a class="btn btn-primary btn-sm" href="comprobante_pago_pdf.php?pago_id=<?= (int)$pagoId ?>">Descargar PDF</a>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header"><strong>Resumen del pago</strong></div>
            <div class="card-body">
                <div class="row g-2 small text-white p-3 rounded" style="background:#343a40">
                    <div class="col-md-6"><strong>Dueño:</strong> <?= htmlspecialchars($data['dueno_nombre']) ?></div>
                    <div class="col-md-6"><strong>Paseador:</strong> <?= htmlspecialchars($data['paseador_nombre']) ?></div>
                    <div class="col-md-6"><strong>Fecha del paseo:</strong> <?= htmlspecialchars($fechaPaseo) ?></div>
                    <div class="col-md-6"><strong>Duración:</strong> <?= htmlspecialchars($duracion) ?></div>
                    <div class="col-md-6"><strong>Método:</strong> <?= htmlspecialchars($metodo) ?></div>
                    <div class="col-md-6"><strong>Monto:</strong> ₲ <?= $montoGs ?></div>
                    <?php if ($data['metodo'] === 'transferencia'): ?>
                        <div class="col-md-6"><strong>Banco:</strong> <?= htmlspecialchars($bancoTxt) ?></div>
                        <div class="col-md-6"><strong>Alias/Cuenta:</strong> <?= htmlspecialchars($aliasTxt ?: '—') ?></div>
                    <?php endif; ?>
                    <div class="col-md-6"><strong>Estado del pago:</strong> <span class="badge bg-secondary"><?= htmlspecialchars($estadoPago) ?></span></div>
                    <div class="col-md-6"><strong>Fecha del pago:</strong> <?= htmlspecialchars($fechaPago) ?></div>
                    <?php if ($ref): ?>
                        <div class="col-12"><strong>Referencia:</strong> <?= htmlspecialchars($ref) ?></div>
                    <?php endif; ?>
                </div>
                <div class="mt-3 small text-muted">
                    Comprobante generado para el dueño <strong><?= htmlspecialchars($data['dueno_nombre']) ?></strong>.
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include __DIR__ . '/../../src/Templates/Footer.php'; ?>
</body>

</html>