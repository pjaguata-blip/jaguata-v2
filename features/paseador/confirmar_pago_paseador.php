<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PagoController.php';
require_once __DIR__ . '/../../src/Controllers/NotificacionController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PagoController;
use Jaguata\Controllers\NotificacionController;
use Jaguata\Helpers\Session;

AppConfig::init();

$auth = new AuthController();
$auth->checkRole('paseador');

// Parámetros
$pagoId  = isset($_GET['pago_id']) ? (int)$_GET['pago_id'] : 0;
$paseoId = isset($_GET['paseo_id']) ? (int)$_GET['paseo_id'] : 0;

// CSRF
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];

$duenoNombre = "María López";
$fecha       = date('d/m/Y');
$monto       = 50000;
$metodo      = "Transferencia"; // si querés, traé del modelo pago real
$estado      = "Pendiente";
$flash       = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('CSRF inválido');
    }
    $accion = $_POST['accion'] ?? '';
    $obs    = trim($_POST['observacion'] ?? '');

    $pagoCtrl = new PagoController();
    $notif    = new NotificacionController();

    if ($accion === 'confirmar') {
        $resp = $pagoCtrl->confirmarPago($pagoId, $obs);
        if (!empty($resp['success'])) {
            $estado = 'Confirmado';
            // Notificar al dueño
            $notif->notificarPagoConfirmadoADueno(/* duenoId */1, $paseoId);
            $flash = '✅ Pago confirmado correctamente.';
        } else {
            $flash = $resp['error'] ?? 'No se pudo confirmar el pago.';
        }
    } elseif ($accion === 'problema') {
        $resp = $pagoCtrl->observarPago($pagoId, $obs);
        if (!empty($resp['success'])) {
            $estado = 'Revisar';
            $notif->notificarPagoObservadoADueno(/* duenoId */1, $paseoId, $obs);
            $flash = '⚠️ Se reportó un problema con el pago. Hemos notificado al dueño.';
        } else {
            $flash = $resp['error'] ?? 'No se pudo actualizar el pago.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <title>Confirmar pago recibido</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="<?= ASSETS_URL; ?>/css/jaguata-theme.css" rel="stylesheet">

</head>

<body>
    <?php include __DIR__ . '/../../src/Templates/Header.php'; ?>
    <?php include __DIR__ . '/../../src/Templates/Navbar.php'; ?>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-7 col-md-8">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h1 class="h4 mb-0">Confirmar pago recibido</h1>
                    <a href="Dashboard.php" class="btn btn-outline-secondary btn-sm">Volver</a>
                </div>

                <?php if ($flash): ?>
                    <div class="alert alert-info"><?= htmlspecialchars($flash) ?></div>
                <?php endif; ?>

                <div class="card shadow-sm">
                    <div class="card-header"><strong>Resumen</strong></div>
                    <div class="card-body">
                        <div class="row g-2 small text-muted">
                            <div class="col-6"><strong>Dueño:</strong> <?= htmlspecialchars($duenoNombre) ?></div>
                            <div class="col-6"><strong>Fecha:</strong> <?= htmlspecialchars($fecha) ?></div>
                            <div class="col-6"><strong>Monto:</strong> ₲ <?= number_format($monto, 0, ',', '.') ?></div>
                            <div class="col-6"><strong>Método:</strong> <?= htmlspecialchars($metodo) ?></div>
                            <div class="col-12">
                                <span class="badge bg-<?= $estado === 'Confirmado' ? 'success' : ($estado === 'Revisar' ? 'warning text-dark' : 'secondary') ?>">
                                    Estado: <?= htmlspecialchars($estado) ?>
                                </span>
                            </div>
                        </div>

                        <hr>

                        <form method="POST" class="mt-3">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>" />

                            <div class="mb-3">
                                <label class="form-label">Observaciones</label>
                                <textarea class="form-control" name="observacion" rows="3" placeholder="Ej.: Importe coincide con el comprobante..."></textarea>
                            </div>

                            <div class="d-flex gap-2">
                                <button class="btn btn-success" name="accion" value="confirmar">✅ Confirmar recepción</button>
                                <button class="btn btn-warning" name="accion" value="problema">⚠️ Reportar problema</button>
                            </div>
                        </form>

                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://kit.fontawesome.com/a2e0e6ad59.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include __DIR__ . '/../../src/Templates/Footer.php'; ?>
</body>

</html>