<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Helpers\Session;

AppConfig::init();
$auth = new AuthController();
$auth->checkRole('paseador');

function h($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$paseoId = (int)($_GET['id'] ?? 0);
if ($paseoId <= 0) {
    die('ID de paseo inválido.');
}

$paseoController = new PaseoController();
$paseo = $paseoController->show($paseoId);
if (!$paseo) {
    die('No se encontró el paseo.');
}

if ((int)($paseo['paseador_id'] ?? 0) !== (int)Session::get('usuario_id')) {
    die('No tienes permiso para ver este paseo.');
}

$estado = strtolower((string)($paseo['estado'] ?? ''));
$badge = match ($estado) {
    'confirmado' => 'warning',
    'en_curso'   => 'info',
    'completo'   => 'success',
    'cancelado'  => 'danger',
    default      => 'secondary'
};

include __DIR__ . '/../../src/Templates/Header.php';
include __DIR__ . '/../../src/Templates/Navbar.php';
?>
<div class="container mt-4">
    <h2 class="mb-3">
        <i class="fas fa-walking me-2"></i> Detalle del Paseo
    </h2>

    <div class="card shadow-sm">
        <div class="card-body">
            <p><strong>Mascota:</strong> <?= h($paseo['nombre_mascota'] ?? '—') ?></p>
            <p><strong>Dueño:</strong> <?= h($paseo['nombre_dueno'] ?? '—') ?></p>
            <p><strong>Fecha:</strong> <?= isset($paseo['inicio']) ? date('d/m/Y H:i', strtotime($paseo['inicio'])) : '—' ?></p>
            <p><strong>Duración:</strong> <?= h($paseo['duracion'] ?? $paseo['duracion_min'] ?? '—') ?> min</p>
            <p><strong>Precio Total:</strong> ₲<?= number_format((float)($paseo['precio_total'] ?? 0), 0, ',', '.') ?></p>
            <p><strong>Estado:</strong>
                <span class="badge bg-<?= $badge ?>">
                    <?= ucfirst($estado ?: '—') ?>
                </span>
            </p>

            <div class="mt-3 d-flex gap-2">
                <?php if ($estado === 'confirmado'): ?>
                    <a href="IniciarPaseo.php?id=<?= (int)$paseo['paseo_id'] ?>" class="btn btn-primary"
                        onclick="return confirm('¿Iniciar este paseo?');">
                        <i class="fas fa-play me-1"></i> Iniciar Paseo
                    </a>
                    <a href="CancelarPaseo.php?id=<?= (int)$paseo['paseo_id'] ?>" class="btn btn-outline-danger"
                        onclick="return confirm('¿Cancelar este paseo?');">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </a>
                <?php elseif ($estado === 'en_curso'): ?>
                    <a href="CompletarPaseo.php?id=<?= (int)$paseo['paseo_id'] ?>" class="btn btn-success"
                        onclick="return confirm('¿Marcar este paseo como completo?');">
                        <i class="fas fa-check me-1"></i> Completar Paseo
                    </a>
                    <a href="CancelarPaseo.php?id=<?= (int)$paseo['paseo_id'] ?>" class="btn btn-outline-danger"
                        onclick="return confirm('¿Cancelar este paseo en curso?');">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </a>
                <?php endif; ?>

                <a href="MisPaseos.php" class="btn btn-secondary ms-auto">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../../src/Templates/Footer.php'; ?>