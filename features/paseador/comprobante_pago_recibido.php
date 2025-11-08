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
(new AuthController())->checkRole('paseador');

// --- Helpers seguros (evitan TypeError) ---
$h = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$ucfirst = static fn($v) => $v !== null && $v !== '' ? ucfirst((string)$v) : '—';
$fmtFecha = static function ($v): string {
    if (!$v) return '—';
    $ts = is_numeric($v) ? (int)$v : strtotime((string)$v);
    return $ts ? date('d/m/Y H:i', $ts) : '—';
};
$fmtGs = static fn($v): string => number_format((float)($v ?? 0), 0, ',', '.');

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

// Seguridad: el comprobante solo si el usuario logueado es el paseador del paseo
if ((int)($data['paseador_id'] ?? 0) !== (int)Session::getUsuarioId()) {
    http_response_code(403);
    exit('No autorizado');
}

// --- Normalización segura de campos ---
$fechaPago   = $fmtFecha($data['pagado_en'] ?? null);
$fechaPaseo  = $fmtFecha($data['inicio'] ?? null);
$duracion    = isset($data['duracion_min']) ? ((int)$data['duracion_min']) . ' min' : '—';
$montoGs     = $fmtGs($data['monto'] ?? null);
$metodo      = $ucfirst($data['metodo'] ?? null);
$estadoPago  = $ucfirst($data['estado_pago'] ?? null);
$ref         = $data['referencia'] ?? '';
$bancoTxt    = $data['banco_nombre'] ?? '—';
$aliasTxt    = $data['alias_transferencia']
    ?? ($data['alias_cuenta'] ?? ($data['cuenta_numero'] ?? ''));

// bandera transferencia (sin Notice)
$esTransfer  = isset($data['metodo']) && $data['metodo'] === 'transferencia';

// Nombres (con fallback)
$paseadorNombre = $data['paseador_nombre'] ?? 'Paseador';
$duenoNombre    = $data['dueno_nombre'] ?? 'Dueño';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <title>Comprobante de pago recibido</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="<?= ASSETS_URL; ?>/css/jaguata-theme.css" rel="stylesheet" />
    <style>
        body {
            font-family: "Poppins", sans-serif;
            background: #f5f7fa;
        }

        .page-header {
            background: linear-gradient(90deg, #20c997, #3c6255);
            color: #fff;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
        }

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

    <div class="container-fluid">
        <div class="row flex-nowrap">
            <!-- Sidebar Paseador unificado -->
            <?php include __DIR__ . '/../../src/Templates/SidebarPaseador.php'; ?>

            <!-- Contenido -->
            <main class="col py-3">
                <div class="page-header">
                    <h1 class="h5 mb-0">
                        <i class="fas fa-receipt me-2"></i> Comprobante de pago recibido
                    </h1>
                    <div class="no-print d-flex gap-2">
                        <button class="btn btn-outline-light btn-sm" onclick="window.print()">
                            <i class="fas fa-print me-1"></i> Imprimir
                        </button>
                        <a class="btn btn-light btn-sm" href="comprobante_pago_recibido_pdf.php?pago_id=<?= (int)$pagoId ?>">
                            <i class="fas fa-file-pdf me-1"></i> PDF
                        </a>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header"><strong>Detalle del pago</strong></div>
                    <div class="card-body">
                        <div class="row g-2 small text-white p-3 rounded" style="background:#343a40">
                            <div class="col-md-6"><strong>Paseador:</strong> <?= $h($paseadorNombre) ?></div>
                            <div class="col-md-6"><strong>Dueño:</strong> <?= $h($duenoNombre) ?></div>

                            <div class="col-md-6"><strong>Fecha del paseo:</strong> <?= $h($fechaPaseo) ?></div>
                            <div class="col-md-6"><strong>Duración:</strong> <?= $h($duracion) ?></div>

                            <div class="col-md-6"><strong>Método:</strong> <?= $h($metodo) ?></div>
                            <div class="col-md-6"><strong>Monto:</strong> ₲ <?= $montoGs ?></div>

                            <?php if ($esTransfer): ?>
                                <div class="col-md-6"><strong>Banco:</strong> <?= $h($bancoTxt) ?></div>
                                <div class="col-md-6"><strong>Alias/Cuenta:</strong> <?= $h($aliasTxt ?: '—') ?></div>
                            <?php endif; ?>

                            <div class="col-md-6">
                                <strong>Estado del pago:</strong>
                                <span class="badge bg-secondary"><?= $h($estadoPago) ?></span>
                            </div>
                            <div class="col-md-6"><strong>Fecha del registro:</strong> <?= $h($fechaPago) ?></div>

                            <?php if ($ref): ?>
                                <div class="col-12"><strong>Referencia:</strong> <?= $h($ref) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mt-3 small text-muted">
                            Comprobante generado para el paseador <strong><?= $h($paseadorNombre) ?></strong>.
                        </div>
                    </div>
                </div>

                <footer class="text-center text-muted mt-4">
                    © <?= date('Y') ?> Jaguata — Paseador
                </footer>
            </main>
        </div>
    </div>

    <!-- JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <?php include __DIR__ . '/../../src/Templates/Footer.php'; ?>
</body>

</html>