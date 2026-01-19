<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/AuthController.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/PagoController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PagoController;

AppConfig::init();

function h($v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

/* 🔒 Seguridad (igual que Usuarios.php) */
if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'admin') {
    header('Location: ' . BASE_URL . '/public/login.php?error=unauthorized');
    exit;
}

$auth = new AuthController();
$auth->checkRole('admin');

/* ✅ baseFeatures */
$baseFeatures = BASE_URL . '/features/admin';

/* ID del pago */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    exit('<h3 style="color:red; text-align:center; margin-top:2rem;">ID de pago no válido.</h3>');
}

/* Datos */
$controller = new PagoController();
$pago = $controller->detalleAdmin($id);

if (!$pago) {
    http_response_code(404);
    exit('<h3 style="color:red; text-align:center; margin-top:2rem;">No se encontró el pago solicitado.</h3>');
}

/* Mapear */
$usuario          = $pago['usuario'] ?? 'N/D';
$usuarioEmail     = $pago['usuario_email'] ?? 'N/D';
$usuarioTelefono  = $pago['usuario_telefono'] ?? 'N/D';

$monto    = (float)($pago['monto'] ?? 0);
$metodo   = (string)($pago['metodo'] ?? 'N/D');
$banco    = (string)($pago['banco'] ?? '');
$cuenta   = (string)($pago['cuenta'] ?? '');
$fechaRaw = $pago['fecha'] ?? null;
$fecha    = $fechaRaw ? date('d/m/Y H:i', strtotime((string)$fechaRaw)) : 'N/D';
$obs      = (string)($pago['observacion'] ?? '');

$estadoRaw = strtolower(trim((string)($pago['estado'] ?? 'pendiente')));
if (!in_array($estadoRaw, ['pendiente', 'pagado', 'cancelado'], true)) {
    $estadoRaw = 'pendiente';
}

$estadoLabel = match ($estadoRaw) {
    'pagado'    => 'Pagado',
    'pendiente' => 'Pendiente',
    'cancelado' => 'Cancelado',
    default     => ucfirst($estadoRaw),
};

/* ✅ MISMO sistema de badges que tu tabla */
$badgeEstado = match ($estadoRaw) {
    'pendiente' => 'estado-pendiente',
    'pagado'    => 'estado-aprobado',
    'cancelado' => 'estado-rechazado',
    default     => 'estado-pendiente'
};

/* Paseo */
$paseoId        = $pago['paseo_id'] ?? null;
$paseoInicioRaw = $pago['paseo_inicio'] ?? null;
$paseoInicio    = $paseoInicioRaw ? date('d/m/Y H:i', strtotime((string)$paseoInicioRaw)) : 'N/D';
$paseoDuracion  = $pago['paseo_duracion'] ?? null;
$paseoPrecio    = $pago['paseo_precio'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle Pago #<?= (int)$id; ?> - Jaguata</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <style>
        /* ✅ evita scroll horizontal (igual que Usuarios.php) */
        html, body { overflow-x: hidden; width: 100%; }

        /* ✅ chip de estado (igual Pagos.php/estilo sistema) */
        .estado-chip{
            display:inline-flex;
            align-items:center;
            gap:.35rem;
            justify-content:center;
            min-width:120px;
        }
        .estado-dot{
            width:10px;height:10px;border-radius:999px;display:inline-block;
        }
        .estado-dot.pendiente{ background:#f0ad4e; }
        .estado-dot.pagado{ background:#198754; }
        .estado-dot.cancelado{ background:#dc3545; }

        /* Mini “campo / valor” para que quede prolijo */
        .kv{ padding:.4rem 0; border-bottom:1px dashed rgba(255,255,255,.12); }
        .kv:last-child{ border-bottom:0; }
        .kv .k{ font-weight:600; opacity:.85; margin-bottom:.1rem; }
        .kv .v{ margin:0; }
        .kv .sub{ font-size:.85rem; opacity:.75; }
    </style>
</head>

<body>

<?php include __DIR__ . '/../../src/Templates/SidebarAdmin.php'; ?>

<main>
    <div class="container-fluid px-3 px-md-2">

        <!-- ✅ HEADER (MISMO estilo que Usuarios.php) -->
        <div class="header-box header-pagos mb-3">
            <div>
                <h1 class="fw-bold mb-1">Detalle de Pago</h1>
                <p class="mb-0">Pago #<?= (int)$id; ?> • Información completa del pago y del paseo 💸🐾</p>
            </div>

            

            <a href="<?= h($baseFeatures); ?>/Pagos.php" class="btn btn-outline-light">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
        </div>

        <div class="row g-3">

            <!-- ✅ DATOS DEL PAGO (section-card) -->
            <div class="col-lg-7">
                <div class="section-card mb-3">
                    <div class="section-header d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-receipt me-2"></i>
                            <span>Datos del pago</span>
                        </div>
                        <span class="badge bg-secondary">#<?= (int)$id; ?></span>
                    </div>

                    <div class="section-body">

                        <div class="kv">
                            <div class="k">Usuario</div>
                            <p class="v fw-semibold mb-0"><?= h($usuario); ?></p>
                            <div class="sub"><?= h($usuarioEmail); ?> • <?= h($usuarioTelefono); ?></div>
                        </div>

                        <div class="kv">
                            <div class="k">Monto abonado</div>
                            <p class="v fw-bold mb-0">₲<?= number_format($monto, 0, ',', '.'); ?></p>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <div class="kv">
                                    <div class="k">Método</div>
                                    <p class="v mb-0"><?= h(ucfirst($metodo)); ?></p>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="kv">
                                    <div class="k">Estado</div>
                                    <p class="v mb-0">
                                        <span class="badge-estado <?= h($badgeEstado); ?> estado-chip">
                                            <span class="estado-dot <?= h($estadoRaw); ?>"></span>
                                            <?= h($estadoLabel); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="kv">
                                    <div class="k">Banco</div>
                                    <p class="v mb-0"><?= h($banco !== '' ? $banco : '-'); ?></p>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="kv">
                                    <div class="k">Cuenta / Alias</div>
                                    <p class="v mb-0"><?= h($cuenta !== '' ? $cuenta : '-'); ?></p>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="kv">
                                    <div class="k">Fecha</div>
                                    <p class="v mb-0"><?= h($fecha); ?></p>
                                </div>
                            </div>
                        </div>

                        <?php if (trim($obs) !== ''): ?>
                            <div class="kv mt-2">
                                <div class="k">Observación</div>
                                <p class="v mb-0"><?= nl2br(h($obs)); ?></p>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

            <!-- ✅ PASEO ASOCIADO (section-card) -->
            <div class="col-lg-5">
                <div class="section-card mb-3">
                    <div class="section-header d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-dog me-2"></i>
                            <span>Paseo asociado</span>
                        </div>
                        <span class="badge bg-secondary">
                            <?= $paseoId ? '#'.(int)$paseoId : '—'; ?>
                        </span>
                    </div>

                    <div class="section-body">

                        <div class="kv">
                            <div class="k">Inicio del paseo</div>
                            <p class="v mb-0"><?= h($paseoInicio); ?></p>
                        </div>

                        <div class="kv">
                            <div class="k">Duración</div>
                            <p class="v mb-0"><?= $paseoDuracion !== null ? (int)$paseoDuracion . ' min' : 'N/D'; ?></p>
                        </div>

                        <div class="kv">
                            <div class="k">Precio del paseo</div>
                            <p class="v mb-0">
                                <?= $paseoPrecio !== null ? '₲' . number_format((float)$paseoPrecio, 0, ',', '.') : 'N/D'; ?>
                            </p>
                        </div>

                        <?php if ($paseoId): ?>
                            <a href="<?= BASE_URL; ?>/features/admin/VerPaseo.php?id=<?= (int)$paseoId; ?>" class="btn-ver mt-2">
                                <i class="fas fa-map-location-dot"></i> Ver detalle del paseo
                            </a>
                        <?php else: ?>
                            <small class="text-muted">Este pago no está asociado a un paseo válido.</small>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

        </div>

        <footer class="mt-3">
            <small>© <?= date('Y'); ?> Jaguata — Panel de Administración</small>
        </footer>

    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    /* ✅ Toggle sidebar en mobile (IGUAL a Usuarios.php) */
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.querySelector('.sidebar');
        const btnToggle = document.getElementById('btnSidebarToggle');

        if (btnToggle && sidebar) {
            btnToggle.addEventListener('click', () => {
                sidebar.classList.toggle('show');
            });
        }
    });
</script>

</body>
</html>
