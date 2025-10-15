<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Helpers\Session;

// Inicializar aplicación
AppConfig::init();

// Verificar autenticación SOLO para paseador
$authController = new AuthController();
$authController->checkRole('paseador');

// Obtener ID del paseador en sesión
$paseadorId = Session::get('usuario_id');

// Obtener solicitudes (paseos en estado "Pendiente")
$paseoController = new PaseoController();
$solicitudes = $paseoController->getSolicitudesPendientes((int)$paseadorId);

function h($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$titulo = "Solicitudes de Paseos - Jaguata";
?>

<?php include __DIR__ . '/../../src/Templates/Header.php'; ?>
<?php include __DIR__ . '/../../src/Templates/Navbar.php'; ?>

<div class="container mt-4">
    <h2 class="mb-3"><i class="fas fa-envelope-open-text me-2"></i> Solicitudes de Paseos</h2>

    <!-- Flash messages -->
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-1"></i> <?= $_SESSION['success'];
                                                        unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-triangle-exclamation me-1"></i> <?= $_SESSION['error'];
                                                                unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($solicitudes)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>No tienes solicitudes pendientes en este momento.
        </div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Mascota</th>
                                <th>Dueño</th>
                                <th>Fecha</th>
                                <th>Duración</th>
                                <th>Precio</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($solicitudes as $s): ?>
                                <?php
                                $paseoId = (int)($s['paseo_id'] ?? 0);
                                $duracion = $s['duracion'] ?? ($s['duracion_min'] ?? 0);
                                ?>
                                <tr>
                                    <td>
                                        <i class="fas fa-paw text-primary me-1"></i>
                                        <?= h($s['nombre_mascota'] ?? '-') ?>
                                    </td>
                                    <td>
                                        <i class="fas fa-user text-secondary me-1"></i>
                                        <?= h($s['nombre_dueno'] ?? '-') ?>
                                    </td>
                                    <td><?= isset($s['inicio']) ? date('d/m/Y H:i', strtotime($s['inicio'])) : '—' ?></td>
                                    <td><?= (int)$duracion ?> min</td>
                                    <td>₲<?= number_format((float)($s['precio_total'] ?? 0), 0, ',', '.') ?></td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <!-- Aceptar (confirmar) -->
                                            <form action="AccionPaseo.php" method="post" class="d-inline">
                                                <input type="hidden" name="id" value="<?= $paseoId ?>">
                                                <input type="hidden" name="accion" value="confirmar">
                                                <!-- volver a esta misma pantalla luego -->
                                                <input type="hidden" name="redirect_to" value="Solicitudes.php">
                                                <button type="submit"
                                                    class="btn btn-sm btn-success"
                                                    onclick="return confirm('¿Aceptar esta solicitud?');"
                                                    title="Aceptar">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>

                                            <!-- Rechazar (cancelar) -->
                                            <form action="AccionPaseo.php" method="post" class="d-inline ms-1">
                                                <input type="hidden" name="id" value="<?= $paseoId ?>">
                                                <input type="hidden" name="accion" value="cancelar">
                                                <input type="hidden" name="redirect_to" value="Solicitudes.php">
                                                <button type="submit"
                                                    class="btn btn-sm btn-danger"
                                                    onclick="return confirm('¿Rechazar esta solicitud?');"
                                                    title="Rechazar">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../src/Templates/Footer.php'; ?>