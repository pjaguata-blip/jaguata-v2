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

// Obtener solicitudes (paseos en estado "solicitado")
$paseoController = new PaseoController();
$solicitudes = $paseoController->getSolicitudesPendientes($paseadorId);

$titulo = "Solicitudes de Paseos - Jaguata";
?>

<?php include __DIR__ . '/../../src/Templates/Header.php'; ?>
<?php include __DIR__ . '/../../src/Templates/Navbar.php'; ?>

<div class="container mt-4">
    <h2 class="mb-3"><i class="fas fa-envelope-open-text me-2"></i> Solicitudes de Paseos</h2>

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
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($solicitudes as $s): ?>
                                <tr>
                                    <td>
                                        <i class="fas fa-paw text-primary me-1"></i>
                                        <?= htmlspecialchars($s['nombre_mascota']) ?>
                                    </td>
                                    <td>
                                        <i class="fas fa-user text-secondary me-1"></i>
                                        <?= htmlspecialchars($s['nombre_dueno']) ?>
                                    </td>
                                    <td>
                                        <?= date('d/m/Y H:i', strtotime($s['inicio'])) ?>
                                    </td>
                                    <td><?= (int)$s['duracion'] ?> min</td>
                                    <td>₲<?= number_format($s['precio_total'], 0, ',', '.') ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="AceptarSolicitud.php?id=<?= $s['paseo_id'] ?>"
                                                class="btn btn-sm btn-success"
                                                onclick="return confirm('¿Aceptar esta solicitud?');">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <a href="RechazarSolicitud.php?id=<?= $s['paseo_id'] ?>"
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('¿Rechazar esta solicitud?');">
                                                <i class="fas fa-times"></i>
                                            </a>
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