<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/PagoController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Controllers\PagoController;

AppConfig::init();

// 🔒 Solo admin
if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'admin') {
    header('Location: ' . BASE_URL . '/public/login.php?error=unauthorized');
    exit;
}

// ID del pago
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die('<h3 style="color:red; text-align:center; margin-top:2rem;">ID de pago no válido.</h3>');
}

// Cargar detalle desde el controller
$controller = new PagoController();
$pago = $controller->detalleAdmin($id);

if (!$pago) {
    die('<h3 style="color:red; text-align:center; margin-top:2rem;">No se encontró el pago solicitado.</h3>');
}

// Mapear datos
$usuario          = $pago['usuario'] ?? 'N/D';
$usuarioEmail     = $pago['usuario_email'] ?? 'N/D';
$usuarioTelefono  = $pago['usuario_telefono'] ?? 'N/D';

$monto    = (float)($pago['monto'] ?? 0);
$metodo   = $pago['metodo'] ?? 'N/D';
$banco    = $pago['banco'] ?? '';
$cuenta   = $pago['cuenta'] ?? '';
$fechaRaw = $pago['fecha'] ?? null;
$fecha    = $fechaRaw ? date('d/m/Y H:i', strtotime($fechaRaw)) : 'N/D';
$obs      = $pago['observacion'] ?? '';

$estadoRaw = strtolower((string)($pago['estado'] ?? 'pendiente'));

$estadoLabel = match ($estadoRaw) {
    'pagado'    => 'Pagado',
    'pendiente' => 'Pendiente',
    'cancelado' => 'Cancelado',
    default     => ucfirst($estadoRaw),
};

$badgeClass = match ($estadoRaw) {
    'pagado'    => 'bg-success',
    'pendiente' => 'bg-warning text-dark',
    'cancelado' => 'bg-danger',
    default     => 'bg-secondary',
};

$paseoId        = $pago['paseo_id'] ?? null;
$paseoInicioRaw = $pago['paseo_inicio'] ?? null;
$paseoInicio    = $paseoInicioRaw ? date('d/m/Y H:i', strtotime($paseoInicioRaw)) : 'N/D';
$paseoDuracion  = $pago['paseo_duracion'] ?? null;
$paseoPrecio    = $pago['paseo_precio'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Pago #<?= htmlspecialchars((string)$id); ?> - Jaguata</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <!-- Tema Jaguata -->
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">
</head>

<body>

    <?php include __DIR__ . '/../../src/Templates/SidebarAdmin.php'; ?>

    <!-- Topbar mobile (si la usás) -->
    <!-- <div class="topbar-admin">...</div> -->

    <main>
        <div class="container-fluid px-3 px-md-4">

            <!-- HEADER BONITO -->
            <div class="header-box header-pagos">
                <div>
                    <h1 class="fw-bold mb-1">
                        <i class="fas fa-wallet me-2"></i>
                        Pago #<?= htmlspecialchars((string)$id); ?>
                    </h1>
                    <p class="mb-0">Detalle completo del pago y paseo asociado 💸🐾</p>
                </div>
                <i class="fas fa-file-invoice-dollar fa-3x opacity-75"></i>
            </div>

            <!-- Botón volver -->
            <div class="mb-3">
                <a href="Pagos.php" class="btn-volver">
                    <i class="fas fa-arrow-left"></i> Volver al listado
                </a>
            </div>

            <div class="row g-3">

                <!-- COLUMNA IZQUIERDA: Datos del pago -->
                <div class="col-lg-7">
                    <div class="card mb-4">
                        <div class="card-header d-flex align-items-center gap-2">
                            <i class="fas fa-receipt"></i>
                            Datos del pago
                        </div>
                        <div class="card-body">
                            <div class="row g-3">

                                <div class="col-md-6">
                                    <p class="modal-label">Usuario</p>
                                    <p class="modal-value mb-1">
                                        <?= htmlspecialchars($usuario); ?>
                                    </p>
                                    <small class="text-muted d-block">
                                        <?= htmlspecialchars($usuarioEmail); ?> •
                                        <?= htmlspecialchars($usuarioTelefono); ?>
                                    </small>
                                </div>

                                <div class="col-md-6">
                                    <p class="modal-label">Monto abonado</p>
                                    <p class="modal-value">
                                        ₲<?= number_format($monto, 0, ',', '.'); ?>
                                    </p>
                                </div>

                                <div class="col-md-6">
                                    <p class="modal-label">Método de pago</p>
                                    <p class="modal-value">
                                        <?= htmlspecialchars(ucfirst($metodo)); ?>
                                    </p>
                                </div>

                                <div class="col-md-6">
                                    <p class="modal-label">Estado del pago</p>
                                    <p class="m-0">
                                        <span class="badge-modal badge <?= $badgeClass; ?>">
                                            <?= htmlspecialchars($estadoLabel ?: 'N/D'); ?>
                                        </span>
                                    </p>
                                </div>

                                <div class="col-md-6">
                                    <p class="modal-label">Banco</p>
                                    <p class="modal-value">
                                        <?= htmlspecialchars($banco ?: '-'); ?>
                                    </p>
                                </div>

                                <div class="col-md-6">
                                    <p class="modal-label">Cuenta / Alias</p>
                                    <p class="modal-value">
                                        <?= htmlspecialchars($cuenta ?: '-'); ?>
                                    </p>
                                </div>

                                <div class="col-md-6">
                                    <p class="modal-label">Fecha de registro</p>
                                    <p class="modal-value">
                                        <?= htmlspecialchars($fecha); ?>
                                    </p>
                                </div>

                                <?php if (trim((string)$obs) !== ''): ?>
                                    <div class="col-12">
                                        <p class="modal-label">Observación</p>
                                        <p class="modal-value">
                                            <?= nl2br(htmlspecialchars($obs)); ?>
                                        </p>
                                    </div>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- COLUMNA DERECHA: Datos del paseo asociado -->
                <div class="col-lg-5">
                    <div class="card mb-4">
                        <div class="card-header d-flex align-items-center gap-2">
                            <i class="fas fa-dog"></i>
                            Paseo asociado
                        </div>
                        <div class="card-body">

                            <div class="mb-3">
                                <p class="modal-label">ID del paseo</p>
                                <p class="modal-value">
                                    <?= htmlspecialchars((string)($paseoId ?? '-')); ?>
                                </p>
                            </div>

                            <div class="mb-3">
                                <p class="modal-label">Inicio del paseo</p>
                                <p class="modal-value">
                                    <?= htmlspecialchars($paseoInicio); ?>
                                </p>
                            </div>

                            <div class="mb-3">
                                <p class="modal-label">Duración</p>
                                <p class="modal-value">
                                    <?= $paseoDuracion !== null ? (int)$paseoDuracion . ' min' : 'N/D'; ?>
                                </p>
                            </div>

                            <div class="mb-3">
                                <p class="modal-label">Precio del paseo</p>
                                <p class="modal-value">
                                    <?= $paseoPrecio !== null
                                        ? '₲' . number_format((float)$paseoPrecio, 0, ',', '.')
                                        : 'N/D'; ?>
                                </p>
                            </div>

                            <?php if ($paseoId): ?>
                                <a href="VerPaseo.php?id=<?= (int)$paseoId; ?>" class="btn-ver mt-2">
                                    <i class="fas fa-map-location-dot"></i> Ver detalle del paseo
                                </a>
                            <?php else: ?>
                                <small class="text-muted">
                                    Este pago no está asociado a un paseo válido.
                                </small>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>

            </div>

            <footer>
                <small>© <?= date('Y'); ?> Jaguata — Panel de Administración</small>
            </footer>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>