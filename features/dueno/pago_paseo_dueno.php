<?php

/**
 * features/dueno/pago_paseo_dueno.php
 * - Dueño registra el pago de un paseo (efectivo o transferencia)
 * - Guarda: banco, cuenta, comprobante (archivo), alias, referencia, observacion
 * - Notifica al paseador
 */

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PagoController.php';
require_once __DIR__ . '/../../src/Controllers/NotificacionController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PagoController;
use Jaguata\Controllers\NotificacionController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Helpers\Session;

AppConfig::init();

// --- Lectura robusta de paseo_id ---
$paseoId = 0;
if (($v = filter_input(INPUT_GET, 'paseo_id', FILTER_VALIDATE_INT)) !== false && $v !== null) $paseoId = $v;
if ($paseoId <= 0 && ($v = filter_input(INPUT_POST, 'paseo_id', FILTER_VALIDATE_INT)) !== false && $v !== null) $paseoId = $v;
if ($paseoId <= 0 && !empty($_SERVER['REQUEST_URI']) && preg_match('~/pago_paseo_dueno\.php/(\d+)~', $_SERVER['REQUEST_URI'], $m)) $paseoId = (int)$m[1];

// --- Autenticación/rol ---
$auth = new AuthController();
$auth->checkRole('dueno');

// Si no vino paseo_id, mostramos un selector simple para que el dueño elija
if ($paseoId <= 0) {
    $duenoId = (int)Session::getUsuarioId();
    $paseoCtrlTmp = new PaseoController();
    $misPaseos = $paseoCtrlTmp->indexByDueno($duenoId);

    include __DIR__ . '/../../src/Templates/Header.php';
    include __DIR__ . '/../../src/Templates/Navbar.php'; ?>
    <div class="container py-4">
        <h1 class="h4 mb-3">Elegí el paseo a pagar</h1>
        <?php if (empty($misPaseos)): ?>
            <div class="alert alert-info">No hay paseos para pagar.</div>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($misPaseos as $p):
                    $pid   = (int)($p['paseo_id'] ?? 0);
                    $masc  = htmlspecialchars($p['nombre_mascota'] ?? '');
                    $fec   = !empty($p['inicio']) ? date('d/m/Y H:i', strtotime($p['inicio'])) : '-';
                    $monto = number_format((float)($p['precio_total'] ?? 0), 0, ',', '.');
                    $est   = htmlspecialchars($p['estado'] ?? '');
                    if ($pid > 0): ?>
                        <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="?paseo_id=<?= $pid ?>">
                            <div><strong><?= $masc ?></strong> • <?= $fec ?> • <span class="text-muted"><?= $est ?></span></div>
                            <span>₲ <?= $monto ?> <i class="fas fa-chevron-right ms-2"></i></span>
                        </a>
                <?php endif;
                endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php
    include __DIR__ . '/../../src/Templates/Footer.php';
    exit;
}

// --- Traer datos del paseo para la UI ---
$paseoCtrl = new PaseoController();
$detalle = $paseoCtrl->getDetalleParaPago($paseoId);
if (!$detalle) {
    http_response_code(404);
    exit('Paseo no encontrado');
}

// --- Validar pertenencia del dueño ---
$duenoId = (int)Session::getUsuarioId();
if ((int)$detalle['dueno_id'] !== $duenoId) {
    http_response_code(403);
    exit('No autorizado para pagar este paseo');
}

// --- Mapear a variables de vista ---
$paseadorId      = (int)$detalle['paseador_id'];
$paseadorNombre  = (string)$detalle['nombre_paseador'];
$paseadorBanco   = (string)($detalle['paseador_banco'] ?? '');
$paseadorAlias   = (string)($detalle['paseador_alias'] ?? '');
$paseadorCuenta  = (string)($detalle['paseador_cuenta'] ?? '');
$fecha           = !empty($detalle['inicio']) ? date('d/m/Y H:i', strtotime((string)$detalle['inicio'])) : date('d/m/Y');
$duracion        = isset($detalle['duracion_min']) ? ($detalle['duracion_min'] . ' min') : '—';
$monto           = (float)($detalle['precio_total'] ?? 0.0);

// --- CSRF ---
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf  = $_SESSION['csrf'];
$flash = null;

// --- POST: crear pago ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('CSRF inválido');
    }

    $metodo = $_POST['metodo'] ?? 'efectivo'; // 'efectivo' | 'transferencia'
    $banco  = trim($_POST['banco'] ?? '');
    $cuenta = trim($_POST['cuenta'] ?? '');
    $obs    = trim($_POST['observacion'] ?? '');
    $comprobanteNombre = null;

    if ($metodo === 'transferencia') {
        // Validación y manejo de archivo
        if (!empty($_FILES['comprobante']['name'])) {
            $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
            $max     = 5 * 1024 * 1024; // 5MB
            $ext     = strtolower(pathinfo($_FILES['comprobante']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed, true)) {
                $flash = 'Tipo de archivo no permitido. Use JPG, PNG o PDF.';
            } elseif (($_FILES['comprobante']['size'] ?? 0) > $max) {
                $flash = 'El archivo excede 5MB.';
            } elseif (is_uploaded_file($_FILES['comprobante']['tmp_name'])) {
                $dir = __DIR__ . '/../../storage/comprobantes';
                if (!is_dir($dir)) @mkdir($dir, 0775, true);
                $fname = 'comp_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($_FILES['comprobante']['tmp_name'], $dir . '/' . $fname)) {
                    $comprobanteNombre = $fname;
                } else {
                    $flash = 'No se pudo guardar el comprobante.';
                }
            } else {
                $flash = 'Carga de archivo inválida.';
            }
        } else {
            $flash = 'Adjunte un comprobante para transferencia.';
        }
    }

    if ($flash === null) {
        // Construir alias/referencia (human-readable y corta)
        $aliasPreferido = $paseadorAlias ?: $cuenta;
        $refPartes = [];
        if ($cuenta !== '')                           $refPartes[] = $cuenta;
        if ($paseadorCuenta !== '' && $cuenta === '') $refPartes[] = $paseadorCuenta;
        if ($comprobanteNombre)                       $refPartes[] = "comp:$comprobanteNombre";
        if ($banco !== '')                            $refPartes[] = "banco:$banco";
        $referencia = $refPartes ? implode(' | ', $refPartes) : null;

        // Crear pago
        $pagoCtrl = new PagoController();
        $resp = $pagoCtrl->crearPagoDueno([
            'paseo_id'    => $paseoId,
            'usuario_id'  => $paseadorId,                         // receptor (paseador)
            'metodo'      => $metodo,                             // 'efectivo'|'transferencia'
            'banco'       => $banco ?: ($paseadorBanco ?: null),
            'cuenta'      => $cuenta ?: ($paseadorCuenta ?: null),
            'comprobante' => $comprobanteNombre ?: null,
            'alias'       => $aliasPreferido ?: null,
            'referencia'  => $referencia,
            'monto'       => number_format($monto, 2, '.', ''),   // DECIMAL(10,2)
            'observacion' => $obs ?: null,
        ]);

        if (!empty($resp['success'])) {
            // Notificación al paseador
            $notif = new NotificacionController();
            $backupPost = $_POST;

            if ($metodo === 'efectivo') {
                $_POST = [
                    'usu_id'   => $paseadorId,
                    'tipo'     => 'pago',
                    'titulo'   => 'Pago en efectivo',
                    'mensaje'  => 'El dueño confirmó un pago en efectivo del paseo.',
                    'paseo_id' => $paseoId
                ];
            } else {
                $_POST = [
                    'usu_id'   => $paseadorId,
                    'tipo'     => 'pago',
                    'titulo'   => 'Transferencia pendiente',
                    'mensaje'  => 'Hay un pago por transferencia pendiente de tu confirmación.',
                    'paseo_id' => $paseoId
                ];
            }
            $notif->crear();
            $_POST = $backupPost;

            // Redirigir al flujo del paseador para confirmar
            header('Location: ../paseador/confirmar_pago_paseador.php?paseo_id=' . (int)$paseoId . '&pago_id=' . (int)$resp['id']);
            exit;
        } else {
            $flash = $resp['error'] ?? 'No se pudo registrar el pago.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <title>Pagar paseo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="../../assets/css/style.css" rel="stylesheet" />
</head>

<body>
    <?php include __DIR__ . '/../../src/Templates/Header.php'; ?>
    <?php include __DIR__ . '/../../src/Templates/Navbar.php'; ?>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-7 col-md-8">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h1 class="h4 mb-0">Pago del paseo</h1>
                    <a href="Dashboard.php" class="btn btn-outline-secondary btn-sm">Volver</a>
                </div>

                <?php if ($flash): ?>
                    <div class="alert alert-warning"><?= htmlspecialchars($flash) ?></div>
                <?php endif; ?>

                <div class="card shadow-sm">
                    <div class="card-header"><strong>Resumen</strong></div>
                    <div class="card-body">
                        <div class="row g-2 small text-white p-3 rounded"
                            style="background:#343a40"
                            id="resumen"
                            data-banco="<?= htmlspecialchars($paseadorBanco) ?>"
                            data-alias="<?= htmlspecialchars($paseadorAlias) ?>"
                            data-cuenta="<?= htmlspecialchars($paseadorCuenta) ?>">
                            <div class="col-6"><strong>Paseador:</strong> <?= htmlspecialchars($paseadorNombre) ?></div>
                            <div class="col-6"><strong>Fecha:</strong> <?= htmlspecialchars($fecha) ?></div>
                            <div class="col-6"><strong>Duración:</strong> <?= htmlspecialchars($duracion) ?></div>
                            <div class="col-6"><strong>Monto:</strong> ₲ <?= number_format($monto, 0, ',', '.') ?></div>
                        </div>

                        <hr>

                        <!-- Mantener paseo_id en action y como hidden -->
                        <form method="POST" enctype="multipart/form-data" class="mt-3" action="?paseo_id=<?= (int)$paseoId ?>">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>" />
                            <input type="hidden" name="paseo_id" value="<?= (int)$paseoId ?>" />

                            <div class="mb-2">
                                <label class="form-label mb-1">Método de pago</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="metodo" value="efectivo" id="m1" checked>
                                    <label class="form-check-label" for="m1">Efectivo</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="metodo" value="transferencia" id="m2">
                                    <label class="form-check-label" for="m2">Transferencia</label>
                                </div>
                            </div>

                            <div id="transferenciaFields" class="border rounded p-3 mb-3 d-none">
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">Banco</label>
                                        <input type="text" class="form-control" name="banco" id="inputBanco" placeholder="Banco...">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">Nro. de cuenta o alias</label>
                                        <input type="text" class="form-control" name="cuenta" id="inputCuenta" placeholder="Cuenta o alias...">
                                        <div class="form-text">Se autocompleta con los datos del paseador (si existen).</div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Comprobante (JPG/PNG/PDF, máx 5MB)</label>
                                        <input type="file" class="form-control" name="comprobante" accept=".jpg,.jpeg,.png,.pdf">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Observación (opcional)</label>
                                        <textarea class="form-control" name="observacion" rows="2" placeholder="Ej: Transferí desde mi cuenta de ahorros..."></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-check me-1"></i> Confirmar pago
                                </button>
                                <a class="btn btn-outline-secondary" href="Dashboard.php">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        const m1 = document.getElementById('m1');
        const m2 = document.getElementById('m2');
        const box = document.getElementById('transferenciaFields');
        const resumen = document.getElementById('resumen');
        const inputBanco = document.getElementById('inputBanco');
        const inputCuenta = document.getElementById('inputCuenta');

        function toggle() {
            const isTrans = m2.checked;
            box.classList.toggle('d-none', !isTrans);
            if (isTrans) {
                const banco = resumen.getAttribute('data-banco') || '';
                const alias = resumen.getAttribute('data-alias') || '';
                const cuenta = resumen.getAttribute('data-cuenta') || '';
                inputBanco.value = banco;
                inputCuenta.value = alias || cuenta;
            }
        }
        [m1, m2].forEach(r => r.addEventListener('change', toggle));
        toggle();
    </script>
    <script src="https://kit.fontawesome.com/a2e0e6ad59.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <?php include __DIR__ . '/../../src/Templates/Footer.php'; ?>
</body>

</html>