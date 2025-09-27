<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Helpers\Session;

// Inicializar app
AppConfig::init();

// Verificar que el usuario sea paseador
$authController = new AuthController();
$authController->checkRole('paseador');

// Obtener el paseo
$paseoId = (int)($_GET['id'] ?? 0);
if ($paseoId <= 0) {
    die("ID de paseo inválido.");
}

$paseoController = new PaseoController();
$paseo = $paseoController->show($paseoId);

if (!$paseo) {
    die("No se encontró el paseo.");
}

// Validar que el paseo pertenezca al paseador logueado
if ($paseo['paseador_id'] != Session::get('usuario_id')) {
    die("No tienes permiso para ver este paseo.");
}

$titulo = "Detalle del Paseo - Jaguata";
?>

<?php include __DIR__ . '/../../src/Templates/Header.php'; ?>
<?php include __DIR__ . '/../../src/Templates/Navbar.php'; ?>

<div class="container mt-4">
    <h2 class="mb-3">
        <i class="fas fa-walking me-2"></i> Detalle del Paseo
    </h2>

    <div class="card shadow-sm">
        <div class="card-body">
            <p><strong>Mascota:</strong> <?= htmlspecialchars($paseo['nombre_mascota'] ?? 'No especificada') ?></p>
            <p><strong>Dueño:</strong> <?= htmlspecialchars($paseo['nombre_dueno'] ?? 'No especificado') ?></p>
            <p><strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($paseo['inicio'])) ?></p>
            <p><strong>Duración:</strong> <?= htmlspecialchars($paseo['duracion']) ?> min</p>
            <p><strong>Precio Total:</strong> ₲<?= number_format($paseo['precio_total'], 0, ',', '.') ?></p>
            <p><strong>Estado:</strong>
                <span class="badge bg-<?php
                                        echo match ($paseo['estado']) {
                                            'confirmado' => 'warning',
                                            'en_curso'   => 'info',
                                            'completado' => 'success',
                                            'cancelado'  => 'danger',
                                            default      => 'secondary'
                                        };
                                        ?>">
                    <?= ucfirst($paseo['estado']) ?>
                </span>
            </p>

            <div class="mt-3">
                <?php if ($paseo['estado'] === 'confirmado'): ?>
                    <a href="IniciarPaseo.php?id=<?= $paseo['paseo_id'] ?>"
                        class="btn btn-primary">
                        <i class="fas fa-play me-1"></i> Iniciar Paseo
                    </a>
                <?php elseif ($paseo['estado'] === 'en_curso'): ?>
                    <a href="CompletarPaseo.php?id=<?= $paseo['paseo_id'] ?>"
                        class="btn btn-success">
                        <i class="fas fa-check me-1"></i> Completar Paseo
                    </a>
                <?php endif; ?>

                <a href="MisPaseos.php" class="btn btn-secondary ms-2">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../src/Templates/Footer.php'; ?>