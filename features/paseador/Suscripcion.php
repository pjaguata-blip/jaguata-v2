<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/AuthController.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Validaciones.php';
require_once dirname(__DIR__, 2) . '/src/Models/Suscripcion.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Controllers\AuthController;
use Jaguata\Helpers\Validaciones;
use Jaguata\Models\Suscripcion;

AppConfig::init();

/* 🔒 solo paseador */
$auth = new AuthController();
$auth->checkRole('paseador');

/* 🔒 bloqueo por estado */
if (Session::getUsuarioEstado() !== 'aprobado') {
    Session::setError('Tu cuenta aún no fue aprobada.');
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$paseadorId    = (int)(Session::getUsuarioId() ?? 0);
$usuarioNombre = Session::getUsuarioNombre() ?? 'Paseador';

$subModel = new Suscripcion();

/* (opcional) actualizar vencidas en cada visita */
try {
    $subModel->marcarVencidas();
} catch (\Throwable $e) {
    // si la tabla no existe todavía, no rompemos la pantalla
}

/* Carpeta uploads */
$uploadDir = dirname(__DIR__, 2) . '/uploads/suscripciones/';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
}

/* Estado actual */
$ultima = $paseadorId > 0 ? $subModel->getUltimaPorPaseador($paseadorId) : null;

/* Helpers archivo */
function validarComprobante(array &$errores, string $campo='comprobante', int $maxBytes=5242880): void
{
    if (empty($_FILES[$campo]['name'])) {
        $errores[] = 'Debes subir el comprobante.';
        return;
    }
    if (!empty($_FILES[$campo]['error']) && $_FILES[$campo]['error'] !== UPLOAD_ERR_OK) {
        $errores[] = 'Error al subir el comprobante.';
        return;
    }
    if (!empty($_FILES[$campo]['size']) && (int)$_FILES[$campo]['size'] > $maxBytes) {
        $errores[] = 'El comprobante supera 5MB.';
        return;
    }

    $ext = strtolower(pathinfo((string)$_FILES[$campo]['name'], PATHINFO_EXTENSION));
    $allowedExt = ['jpg','jpeg','png','pdf'];
    if (!in_array($ext, $allowedExt, true)) {
        $errores[] = 'Formato inválido (solo JPG, PNG o PDF).';
        return;
    }

    $tmp = $_FILES[$campo]['tmp_name'] ?? '';
    if ($tmp && is_file($tmp)) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($tmp);

        $allowedMime = ['image/jpeg','image/png','application/pdf'];
        if (!in_array($mime, $allowedMime, true)) {
            $errores[] = 'Tipo de archivo no permitido.';
            return;
        }
    }
}

function subirComprobante(string $uploadDir, string $campo='comprobante'): ?string
{
    if (empty($_FILES[$campo]['name'])) return null;

    $ext = strtolower(pathinfo((string)$_FILES[$campo]['name'], PATHINFO_EXTENSION));
    $filename = uniqid('sub_', true) . '.' . $ext;

    $dest = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file((string)($_FILES[$campo]['tmp_name'] ?? ''), $dest)) {
        return null;
    }
    return $filename;
}

/* Procesar POST */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $csrfPost = $_POST['csrf_token'] ?? '';
    if (!Validaciones::verificarCSRF($csrfPost)) {
        Session::setError('Token inválido. Recargá la página e intentá de nuevo.');
        header('Location: ' . BASE_URL . '/features/paseador/Suscripcion.php');
        exit;
    }

    $errores = [];

    // evitar duplicados
    $ultima = $subModel->getUltimaPorPaseador($paseadorId);
    $estadoUlt = strtolower((string)($ultima['estado'] ?? ''));
    $finUlt    = $ultima['fin'] ?? null;
    $activaVigente = ($estadoUlt === 'activa' && (!$finUlt || strtotime((string)$finUlt) >= time()));

    if ($activaVigente) {
        $errores[] = 'Ya tenés una suscripción activa vigente.';
    }
    if ($estadoUlt === 'pendiente') {
        $errores[] = 'Ya tenés una suscripción pendiente de aprobación.';
    }

    // ✅ SOLO TRANSFERENCIA
    $metodo     = 'transferencia';
    $referencia = trim((string)($_POST['referencia'] ?? ''));
    $nota       = trim((string)($_POST['nota'] ?? ''));

    validarComprobante($errores, 'comprobante');

    if (empty($errores)) {
        $archivo = subirComprobante($uploadDir, 'comprobante');
        if (!$archivo) {
            $errores[] = 'No se pudo guardar el comprobante.';
        } else {
            $subModel->crearSolicitud([
                'paseador_id'      => $paseadorId,
                'plan'             => 'pro',
                'monto'            => 50000,
                'comprobante_path' => $archivo,
                'metodo_pago'      => $metodo, // transferencia
                'referencia'       => $referencia !== '' ? $referencia : null,
                'nota'             => $nota !== '' ? $nota : null,
            ]);

            Session::setSuccess('¡Listo! Enviamos tu comprobante. Un administrador lo revisará 🐾');
            header('Location: ' . BASE_URL . '/features/paseador/Suscripcion.php');
            exit;
        }
    }

    if (!empty($errores)) {
        Session::setError(implode(' | ', $errores));
        header('Location: ' . BASE_URL . '/features/paseador/Suscripcion.php');
        exit;
    }
}

$error   = Session::getError();
$success = Session::getSuccess();

/* refrescar estado */
$ultima = $subModel->getUltimaPorPaseador($paseadorId);

/* badges */
$estado = strtolower((string)($ultima['estado'] ?? 'sin'));
$badgeClass = match ($estado) {
    'activa'     => 'bg-success',
    'pendiente'  => 'bg-warning text-dark',
    'vencida'    => 'bg-secondary',
    'rechazada'  => 'bg-danger',
    'cancelada'  => 'bg-dark',
    default      => 'bg-light text-dark border',
};

/* ✅ Datos reales de transferencia */
$BANCO_TRANSFER = 'Ueno Bank';
$ALIAS_TRANSFER = '6112910';
$TITULAR_TRANSF = 'Jaguata';
$MONTO_TRANSF   = 50000;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Suscripción - Paseador | Jaguata</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <style>
        /* ✅ anti scroll horizontal */
        html, body { overflow-x: hidden; width: 100%; }
        *, *::before, *::after { box-sizing: border-box; }

        :root{ --sidebar-w: 260px; }

        /* ✅ MISMO LAYOUT QUE DASHBOARD (sin scroll abajo) */
        main.main-content{
            margin-left: var(--sidebar-w);
            width: calc(100% - var(--sidebar-w)); /* ✅ clave */
            min-height: 100vh;
            padding: 24px;
            overflow-x: hidden;
        }
        @media (max-width: 992px){
            main.main-content{
                margin-left: 0 !important;
                width: 100% !important;
                padding: calc(16px + var(--topbar-h)) 16px 16px !important;
            }
        }

        .plan-box{
            border: 2px dashed rgba(60,98,85,.25);
            border-radius: 16px;
            padding: 16px;
            background: #fff;
        }
        .price{
            font-size: 2rem;
            font-weight: 900;
            color: var(--verde-jaguata, #3c6255);
            line-height: 1;
        }
        .hint{ font-size:.9rem; color:#6b7b83; }
        .doc-note{ font-size:.9rem; color:#5b6a72; }

        /* ✅ “Card” de transferencia más prolija */
        .transfer-card{
            border: 1px solid rgba(0,0,0,.08);
            border-radius: 16px;
            background: #fff;
            overflow: hidden;
        }
        .transfer-head{
            background: rgba(60,98,85,.10);
            border-bottom: 1px solid rgba(0,0,0,.06);
            padding: 12px 14px;
            font-weight: 800;
            color: var(--verde-jaguata, #3c6255);
            display:flex;
            align-items:center;
            gap:10px;
        }
        .transfer-body{ padding: 14px; }
        .transfer-row{
            display:flex;
            justify-content:space-between;
            gap:12px;
            padding: 8px 0;
            border-bottom: 1px dashed rgba(0,0,0,.10);
            flex-wrap: wrap;
        }
        .transfer-row:last-child{ border-bottom: 0; }
        .k{ color:#6b7b83; font-size:.9rem; }
        .v{ font-weight: 800; }
        .copy-hint{
            font-size:.85rem;
            color:#6b7b83;
            margin-top: 8px;
        }
    </style>
</head>

<body class="page-dashboard-paseador">

    <?php include __DIR__ . '/../../src/Templates/SidebarPaseador.php'; ?>

    <main class="main-content">
        <div class="container-fluid p-0 py-2">

            <div class="header-box header-dashboard mb-3">
                <div>
                    <h1 class="h4 mb-1">Suscripción PRO 🐾</h1>
                    <p class="mb-0 text-white-50">
                        Pagá ₲<?= number_format($MONTO_TRANSF, 0, ',', '.'); ?>/mes y accedé a paseos ilimitados como paseador.
                    </p>
                </div>
                <i class="fas fa-crown fa-3x opacity-75"></i>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?= h($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?= h($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-3">

                <!-- Estado actual -->
                <div class="col-lg-5">
                    <div class="section-card h-100">
                        <div class="section-header">
                            <i class="fas fa-id-card me-2"></i>Tu estado
                        </div>
                        <div class="section-body">

                            <?php if (!$ultima): ?>
                                <p class="text-muted mb-2">Aún no registraste ninguna suscripción.</p>
                                <span class="badge bg-light text-dark border">Sin suscripción</span>
                            <?php else: ?>
                                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                                    <span class="badge <?= $badgeClass ?> px-3 py-2">
                                        <?= strtoupper($estado) ?>
                                    </span>
                                    <span class="text-muted small">
                                        Plan: <?= h($ultima['plan'] ?? 'pro') ?>
                                        · Monto: ₲<?= number_format((int)($ultima['monto'] ?? 50000), 0, ',', '.') ?>
                                    </span>
                                </div>

                                <div class="small text-muted">
                                    <?php if (!empty($ultima['inicio'])): ?>
                                        Inicio: <b><?= date('d/m/Y H:i', strtotime((string)$ultima['inicio'])) ?></b><br>
                                    <?php endif; ?>
                                    <?php if (!empty($ultima['fin'])): ?>
                                        Vence: <b><?= date('d/m/Y H:i', strtotime((string)$ultima['fin'])) ?></b><br>
                                    <?php endif; ?>
                                    <?php if (!empty($ultima['referencia'])): ?>
                                        Referencia: <b><?= h($ultima['referencia']) ?></b><br>
                                    <?php endif; ?>
                                </div>

                                <?php if ($estado === 'rechazada' && !empty($ultima['nota'])): ?>
                                    <div class="alert alert-warning border mt-3 mb-0">
                                        <i class="fas fa-triangle-exclamation me-2"></i>
                                        <b>Motivo:</b> <?= h($ultima['nota']) ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <hr>

                            <div class="plan-box">
                                <div class="d-flex align-items-end justify-content-between flex-wrap gap-2">
                                    <div>
                                        <div class="hint">Plan PRO</div>
                                        <div class="price">₲<?= number_format($MONTO_TRANSF, 0, ',', '.'); ?></div>
                                        <div class="hint">por mes</div>
                                    </div>
                                    <div class="text-end">
                                        <div class="doc-note">
                                            <i class="fas fa-infinity me-1"></i>
                                            Paseos ilimitados
                                        </div>
                                        <div class="doc-note">
                                            <i class="fas fa-shield-dog me-1"></i>
                                            Validación por admin
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3 small text-muted">
                                    * Luego de pagar por <b>transferencia</b>, subí tu comprobante. La activación depende de la verificación del admin.
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Formulario -->
                <div class="col-lg-7">
                    <div class="section-card">
                        <div class="section-header">
                            <i class="fas fa-file-arrow-up me-2"></i>Enviar comprobante (Transferencia)
                        </div>
                        <div class="section-body">

                            <?php
                            $bloquear = false;
                            if ($ultima) {
                                $estadoUlt = strtolower((string)($ultima['estado'] ?? ''));
                                $finUlt    = $ultima['fin'] ?? null;
                                $activaVigente = ($estadoUlt === 'activa' && (!$finUlt || strtotime((string)$finUlt) >= time()));
                                if ($activaVigente || $estadoUlt === 'pendiente') $bloquear = true;
                            }
                            ?>

                            <?php if ($bloquear): ?>
                                <div class="alert alert-info border mb-0">
                                    <i class="fas fa-circle-info me-2"></i>
                                    <?= ($estado === 'pendiente')
                                        ? 'Tu solicitud está en revisión. No necesitás enviar otro comprobante.'
                                        : 'Tu suscripción está activa. Podrás renovar cuando venza.'; ?>
                                </div>
                            <?php else: ?>

                                <!-- ✅ DATOS REALES DE TRANSFERENCIA (UENO + ALIAS) -->
                                <div class="transfer-card mb-3">
                                    <div class="transfer-head">
                                        <i class="fas fa-building-columns"></i>
                                        Datos para transferir
                                    </div>

                                    <div class="transfer-body">
                                        <div class="transfer-row">
                                            <div class="k">Banco/Entidad</div>
                                            <div class="v"><?= h($BANCO_TRANSFER); ?></div>
                                        </div>

                                        <div class="transfer-row">
                                            <div class="k">Alias</div>
                                            <div class="v"><?= h($ALIAS_TRANSFER); ?></div>
                                        </div>

                                        <div class="transfer-row">
                                            <div class="k">Titular</div>
                                            <div class="v"><?= h($TITULAR_TRANSF); ?></div>
                                        </div>

                                        <div class="transfer-row">
                                            <div class="k">Monto</div>
                                            <div class="v">₲<?= number_format($MONTO_TRANSF, 0, ',', '.'); ?></div>
                                        </div>

                                        <div class="copy-hint">
                                            <i class="fas fa-circle-info me-1"></i>
                                            * El método es únicamente transferencia. Luego subí el comprobante (PDF/JPG/PNG, máx. 5MB).
                                        </div>
                                    </div>
                                </div>

                                <form method="POST" enctype="multipart/form-data" autocomplete="off">
                                    <input type="hidden" name="csrf_token" value="<?= h(Validaciones::generarCSRF()); ?>">

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Método de pago</label>
                                            <input type="text" class="form-control" value="Transferencia" disabled>
                                            <input type="hidden" name="metodo_pago" value="transferencia">
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Referencia (opcional)</label>
                                            <input type="text" name="referencia" class="form-control" placeholder="N° transacción / referencia">
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Comprobante (PDF/JPG/PNG)</label>
                                            <input type="file" name="comprobante" class="form-control" accept=".pdf,image/*" required>
                                            <div class="form-text">Máximo 5MB.</div>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Nota (opcional)</label>
                                            <input type="text" name="nota" class="form-control" placeholder="Ej: pagué hoy a las 10:30">
                                        </div>
                                    </div>

                                    <div class="d-grid mt-3">
                                        <button class="btn btn-success">
                                            <i class="fas fa-paper-plane me-2"></i>Enviar comprobante
                                        </button>
                                    </div>
                                </form>

                            <?php endif; ?>

                        </div>
                    </div>
                </div>

            </div>

            <footer class="mt-4 text-center text-muted small">
                © <?= date('Y'); ?> Jaguata — Suscripción Paseador
            </footer>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Toggle sidebar (mobile) - por si usás el botón en la topbar
        document.querySelectorAll('[data-toggle="sidebar"]').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('sidebar')?.classList.toggle('sidebar-open');
                document.getElementById('sidebarBackdrop')?.classList.toggle('show');
                document.body.style.overflow = document.getElementById('sidebar')?.classList.contains('sidebar-open') ? 'hidden' : '';
            });
        });

        document.getElementById('sidebarBackdrop')?.addEventListener('click', function(){
            document.getElementById('sidebar')?.classList.remove('sidebar-open');
            this.classList.remove('show');
            document.body.style.overflow = '';
        });
    </script>

</body>
</html>
