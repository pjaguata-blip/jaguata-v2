<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Controllers/PagoController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Controllers\PagoController;
use Jaguata\Helpers\Session;

AppConfig::init();

/* 🔒 Auth dueño */
$auth = new AuthController();
$auth->checkRole('dueno');

/* Helper */
function h(mixed $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/* Contexto usuario */
$duenoId = (int)(Session::getUsuarioId() ?? 0);
if ($duenoId <= 0) {
    http_response_code(401);
    exit('No autenticado');
}

$rolMenu      = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures = BASE_URL . "/features/{$rolMenu}";

/* Paseo a pagar */
$paseoId = (int)($_GET['paseo_id'] ?? $_POST['paseo_id'] ?? 0);
if ($paseoId <= 0) {
    $_SESSION['error'] = 'Paseo no válido para pago.';
    header("Location: {$baseFeatures}/MisPaseos.php");
    exit;
}

$paseoCtrl = new PaseoController();
$pagoCtrl  = new PagoController();

/* Detalle del paseo para mostrar y calcular monto */
$detalle = $paseoCtrl->getDetalleParaPago($paseoId);
if (!$detalle) {
    http_response_code(404);
    exit('Paseo no encontrado');
}

/* Datos base para la vista */
$paseadorNombre = h($detalle['nombre_paseador'] ?? 'No asignado');
$paseadorBanco  = $detalle['paseador_banco'] ?? '';
$paseadorAlias  = $detalle['paseador_alias'] ?? '';
$paseadorCuenta = $detalle['paseador_cuenta'] ?? '';
$fecha          = !empty($detalle['inicio'])
    ? date('d/m/Y H:i', strtotime($detalle['inicio']))
    : '—';
$duracionMin    = (int)($detalle['duracion_min'] ?? 0);
$montoNum       = (float)($detalle['precio_total'] ?? 0.0);
$montoFormato   = number_format($montoNum, 0, ',', '.');

$success = $_SESSION['success'] ?? '';
$error   = $_SESSION['error']   ?? '';
unset($_SESSION['success'], $_SESSION['error']);

/* ===========================
   POST: registrar pago
   =========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $metodo = $_POST['metodo'] ?? 'efectivo';
    $metodo = $metodo === 'transferencia' ? 'transferencia' : 'efectivo';

    $banco       = trim((string)($_POST['banco'] ?? ''));
    $cuentaAlias = trim((string)($_POST['cuenta'] ?? ''));
    $referencia  = trim((string)($_POST['referencia'] ?? ''));
    $observacion = trim((string)($_POST['observacion'] ?? ''));

    $rutaComprobante = null;

    /* 📎 Manejo de archivo de comprobante (solo transferencia) */
    if ($metodo === 'transferencia' && !empty($_FILES['comprobante']['name'])) {
        $file = $_FILES['comprobante'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $permitidos = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'application/pdf' => 'pdf',
            ];

            $mime = mime_content_type($file['tmp_name']);
            if (!isset($permitidos[$mime])) {
                $error = 'Formato de comprobante no permitido. Solo JPG, PNG o PDF.';
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $error = 'El comprobante no puede superar los 5MB.';
            } else {
                $ext = $permitidos[$mime];
                $dir = __DIR__ . '/../../assets/uploads/comprobantes';
                if (!is_dir($dir)) {
                    mkdir($dir, 0775, true);
                }
                $nombreArchivo = 'pago_' . $paseoId . '_' . date('YmdHis') . '.' . $ext;
                $rutaFisica    = $dir . '/' . $nombreArchivo;
                if (move_uploaded_file($file['tmp_name'], $rutaFisica)) {
                    // Ruta accesible desde el navegador
                    $rutaComprobante = '/assets/uploads/comprobantes/' . $nombreArchivo;
                } else {
                    $error = 'No se pudo guardar el comprobante.';
                }
            }
        } elseif ($file['error'] !== UPLOAD_ERR_NO_FILE) {
            $error = 'Error al subir el comprobante.';
        }
    }

    if ($error === '') {
        /* 📦 Armamos datos para PagoController::crearPagoDueno */
        $data = [
            'paseo_id'    => $paseoId,
            'usuario_id'  => $duenoId,
            'metodo'      => $metodo,
            'monto'       => $montoNum,
            'banco'       => $banco ?: $paseadorBanco,
            'cuenta'      => $cuentaAlias ?: $paseadorCuenta,
            'alias'       => $cuentaAlias ?: $paseadorAlias,
            'comprobante' => $rutaComprobante,
            'referencia'  => $referencia ?: null,
            'observacion' => $observacion ?: null,
        ];

        $resultado = $pagoCtrl->crearPagoDueno($data);

        if (!empty($resultado['error'])) {
            $error = $resultado['error'];
        } else {
            $_SESSION['success'] = 'Pago registrado correctamente.';
            // Podés redirigir a GastosTotales, MisPaseos o dashboard
            header("Location: {$baseFeatures}/GastosTotales.php");
            exit;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagar paseo - Jaguata</title>

    <!-- CSS global Jaguata -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">
</head>

<body>

    <!-- Sidebar dueño unificado -->
    <?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>

    <!-- Botón hamburguesa (mismo comportamiento que otras pantallas) -->
    <button class="btn btn-outline-secondary d-md-none ms-2 mt-3" type="button" data-toggle="sidebar">
  <i class="fas fa-bars"></i>
</button>


    <main class="main-content bg-light">

        <div class="container-fluid py-2">

            <!-- HEADER pagos -->
            <div class="header-box header-pagos mb-4">
                <div>
                    <h1 class="fw-bold mb-1">
                        <i class="fas fa-money-bill-wave me-2"></i>Pago del paseo
                    </h1>
                    <p class="mb-0">Confirmá y registrá tu pago de manera rápida y segura 🐾</p>
                </div>
                <div class="text-end">
                    <a href="<?= $baseFeatures; ?>/MisPaseos.php" class="btn btn-outline-light fw-semibold">
                        <i class="fas fa-arrow-left me-1"></i> Volver a Mis Paseos
                    </a>
                </div>
            </div>

            <!-- Alertas -->
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm">
                    <i class="fas fa-check-circle me-2"></i><?= h($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show shadow-sm">
                    <i class="fas fa-exclamation-triangle me-2"></i><?= h($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Contenido principal: resumen + formulario -->
            <div class="row g-4">
                <!-- Resumen del paseo -->
                <div class="col-lg-5">
                    <div class="section-card h-100">
                        <div class="section-header">
                            <i class="fas fa-receipt me-2"></i> Resumen del paseo
                        </div>
                        <div class="section-body">
                            <dl class="row mb-0">
                                <dt class="col-5">Paseador:</dt>
                                <dd class="col-7"><?= $paseadorNombre; ?></dd>

                                <dt class="col-5">Fecha:</dt>
                                <dd class="col-7"><?= h($fecha); ?></dd>

                                <dt class="col-5">Duración:</dt>
                                <dd class="col-7"><?= $duracionMin; ?> min</dd>

                                <dt class="col-5">Monto:</dt>
                                <dd class="col-7 fw-bold text-success">₲ <?= $montoFormato; ?></dd>

                                <dt class="col-5">Banco paseador:</dt>
                                <dd class="col-7"><?= h($paseadorBanco ?: '—'); ?></dd>

                                <dt class="col-5">Alias/Cuenta paseador:</dt>
                                <dd class="col-7"><?= h($paseadorAlias ?: $paseadorCuenta ?: '—'); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Formulario de pago -->
                <div class="col-lg-7">
                    <div class="section-card h-100">
                        <div class="section-header">
                            <i class="fas fa-credit-card me-2"></i> Registrar pago
                        </div>
                        <div class="section-body">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="paseo_id" value="<?= $paseoId; ?>">

                                <!-- Método -->
                                <div class="mb-3">
                                    <label class="form-label">Método de pago</label>
                                    <div class="d-flex flex-wrap gap-4 mt-1">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="metodo" id="m1" value="efectivo" checked>
                                            <label class="form-check-label" for="m1">Efectivo</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="metodo" id="m2" value="transferencia">
                                            <label class="form-check-label" for="m2">Transferencia</label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Datos transferencia -->
                                <div id="transferenciaFields" class="border rounded p-3 bg-light mb-4 d-none">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Banco desde el que pagás</label>
                                            <input type="text"
                                                class="form-control"
                                                name="banco"
                                                placeholder="Ej: Banco X">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Cuenta o alias usado</label>
                                            <input type="text"
                                                class="form-control"
                                                name="cuenta"
                                                placeholder="Alias o nro de cuenta">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Referencia del pago (N° operación)</label>
                                            <input type="text"
                                                class="form-control"
                                                name="referencia"
                                                placeholder="Ej: Nro de transacción">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Comprobante (JPG/PNG/PDF, máx 5MB)</label>
                                            <input type="file"
                                                class="form-control"
                                                name="comprobante"
                                                accept=".jpg,.jpeg,.png,.pdf">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Observación (opcional)</label>
                                            <textarea class="form-control"
                                                name="observacion"
                                                rows="2"
                                                placeholder="Ej: Transferí desde mi cuenta de ahorro..."></textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- Botones -->
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="<?= $baseFeatures; ?>/MisPaseos.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i> Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-guardar">
                                        <i class="fas fa-check me-1"></i> Confirmar pago
                                    </button>
                                </div>
                            </form>
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
        // Sidebar responsive (igual que otras pantallas)
        document.getElementById('toggleSidebar')?.addEventListener('click', function() {
            document.getElementById('sidebar')?.classList.toggle('sidebar-open');
        });

        // Mostrar/ocultar campos de transferencia
        const m1 = document.getElementById('m1');
        const m2 = document.getElementById('m2');
        const box = document.getElementById('transferenciaFields');

        function toggleTransferencia() {
            if (!box) return;
            box.classList.toggle('d-none', !m2.checked);
        }

        m1?.addEventListener('change', toggleTransferencia);
        m2?.addEventListener('change', toggleTransferencia);
    </script>
</body>

</html>