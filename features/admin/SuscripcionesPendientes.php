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

/* 🔒 solo admin */
$auth = new AuthController();
$auth->checkRole('admin');

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$subModel = new Suscripcion();

/* Procesar acciones */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $csrfPost = $_POST['csrf_token'] ?? '';
    if (!Validaciones::verificarCSRF($csrfPost)) {
        Session::setError('Token inválido. Recargá la página e intentá de nuevo.');
        header('Location: ' . BASE_URL . '/features/admin/SuscripcionesPendientes.php');
        exit;
    }

    $accion = $_POST['accion'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    if ($id <= 0) {
        Session::setError('ID inválido.');
        header('Location: ' . BASE_URL . '/features/admin/SuscripcionesPendientes.php');
        exit;
    }

    try {
        if ($accion === 'aprobar') {
            $subModel->aprobar($id);
            Session::setSuccess('Suscripción aprobada ✅ (activa por 30 días).');
        } elseif ($accion === 'rechazar') {
            $motivo = trim((string)($_POST['motivo'] ?? ''));
            if ($motivo === '') $motivo = 'Comprobante inválido o ilegible.';
            $subModel->rechazar($id, $motivo);
            Session::setSuccess('Suscripción rechazada ❌.');
        } else {
            Session::setError('Acción inválida.');
        }
    } catch (Throwable $e) {
        error_log('❌ Admin Suscripciones: ' . $e->getMessage());
        Session::setError('Ocurrió un error al procesar la solicitud.');
    }

    header('Location: ' . BASE_URL . '/features/admin/SuscripcionesPendientes.php');
    exit;
}

/* Listado pendientes */
$pendientes = $subModel->getPendientes(100);

$error   = Session::getError();
$success = Session::getSuccess();

/* Ruta pública del comprobante */
$comprobantesBaseUrl = BASE_URL . '/uploads/suscripciones/';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Suscripciones pendientes - Admin | Jaguata</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <style>
        html, body { overflow-x: hidden; width: 100%; }
        *, *::before, *::after { box-sizing: border-box; }

        :root{ --sidebar-w: 260px; }

        main.main-content{
            margin-left: var(--sidebar-w);
            width: calc(100% - var(--sidebar-w)); /* ✅ evita scroll horizontal */
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

        .mini{ font-size: .88rem; color: #6b7b83; }
    </style>
</head>

<body class="page-dashboard-admin">

    <?php include __DIR__ . '/../../src/Templates/SidebarAdmin.php'; ?>

    <main class="main-content">
        <div class="container-fluid p-0 py-2">

            <div class="header-box header-dashboard mb-3">
                <div>
                    <h1 class="h4 mb-1">Suscripciones pendientes 🧾</h1>
                    <p class="mb-0 text-white-50">Revisá comprobantes y aprobá o rechazá la suscripción de ₲50.000/mes.</p>
                </div>
                <i class="fas fa-file-invoice-dollar fa-3x opacity-75"></i>
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

            <div class="section-card">
                <div class="section-header">
                    <i class="fas fa-clock me-2"></i>Solicitudes en revisión
                </div>
                <div class="section-body">

                    <?php if (empty($pendientes)): ?>
                        <div class="alert alert-light border text-center mb-0">
                            <i class="fas fa-circle-info me-2"></i>No hay suscripciones pendientes.
                        </div>
                    <?php else: ?>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle text-center mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Paseador</th>
                                        <th>Email</th>
                                        <th>Método</th>
                                        <th>Referencia</th>
                                        <th>Monto</th>
                                        <th>Fecha</th>
                                        <th>Comprobante</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($pendientes as $s): ?>
                                    <?php
                                        $id = (int)($s['id'] ?? 0);
                                        $archivo = trim((string)($s['comprobante_path'] ?? ''));

                                        // ✅ FIX: construir link comprobante
                                        $linkComp = null;
                                        if ($archivo !== '') {
                                            $linkComp = $comprobantesBaseUrl . rawurlencode($archivo);
                                        }
                                    ?>
                                    <tr>
                                        <td>#<?= $id ?></td>
                                        <td><?= h($s['paseador_nombre'] ?? '-') ?></td>
                                        <td class="mini"><?= h($s['paseador_email'] ?? '-') ?></td>
                                        <td><?= h($s['metodo_pago'] ?? '-') ?></td>
                                        <td><?= h($s['referencia'] ?? '-') ?></td>
                                        <td><b>₲<?= number_format((int)($s['monto'] ?? 50000), 0, ',', '.') ?></b></td>
                                        <td class="mini">
                                            <?= !empty($s['created_at']) ? date('d/m/Y H:i', strtotime((string)$s['created_at'])) : '-' ?>
                                        </td>
                                        <td>
                                            <?php if ($linkComp): ?>
                                                <a class="btn btn-outline-primary btn-sm"
                                                   href="<?= h($linkComp) ?>" target="_blank" rel="noopener">
                                                    <i class="fas fa-eye me-1"></i>Ver
                                                </a>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Sin archivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap justify-content-center gap-2">

                                                <!-- Aprobar -->
                                                <form method="POST" class="m-0">
                                                    <input type="hidden" name="csrf_token" value="<?= h(Validaciones::generarCSRF()); ?>">
                                                    <input type="hidden" name="accion" value="aprobar">
                                                    <input type="hidden" name="id" value="<?= $id ?>">
                                                    <button type="submit" class="btn btn-success btn-sm">
                                                        <i class="fas fa-check me-1"></i>Aprobar
                                                    </button>
                                                </form>

                                                <!-- Rechazar (modal) -->
                                                <button type="button"
                                                    class="btn btn-danger btn-sm"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#rechazarModal"
                                                    data-id="<?= $id ?>">
                                                    <i class="fas fa-times me-1"></i>Rechazar
                                                </button>

                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                    <?php endif; ?>
                </div>
            </div>

            <footer class="mt-4 text-center text-muted small">
                © <?= date('Y'); ?> Jaguata — Admin Suscripciones
            </footer>

        </div>
    </main>

    <!-- Modal Rechazo -->
    <div class="modal fade" id="rechazarModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-ban me-2"></i>Rechazar suscripción</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= h(Validaciones::generarCSRF()); ?>">
                    <input type="hidden" name="accion" value="rechazar">
                    <input type="hidden" name="id" id="rechazarId" value="0">

                    <label class="form-label fw-semibold">Motivo</label>
                    <input type="text" name="motivo" class="form-control"
                        placeholder="Ej: comprobante ilegible / monto incorrecto / sin datos…"
                        required>
                    <div class="form-text">Este motivo se le mostrará al paseador.</div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times me-1"></i>Rechazar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const modal = document.getElementById('rechazarModal');
        modal?.addEventListener('show.bs.modal', (event) => {
            const button = event.relatedTarget;
            const id = button?.getAttribute('data-id') || '0';
            document.getElementById('rechazarId').value = id;
        });
    </script>
</body>
</html>
