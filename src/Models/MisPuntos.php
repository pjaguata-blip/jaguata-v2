<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\PuntoController;

AppConfig::init();

$controller = new PuntoController();
$data = $controller->misPuntos();

$total = $data['total'];
$historial = $data['historial'];

include __DIR__ . '/../../src/Templates/Header.php';
?>

<div class="container py-5">
    <h2 class="text-primary mb-4">
        <i class="fas fa-coins me-2"></i> Mis Puntos
    </h2>

    <div class="card shadow-sm mb-4">
        <div class="card-body text-center">
            <h3 class="fw-bold">Total acumulado</h3>
            <p class="display-4 text-success"><?php echo $total; ?> pts</p>
        </div>
    </div>

    <h4 class="mb-3">Historial de puntos</h4>
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="table-light">
                <tr>
                    <th>Fecha</th>
                    <th>Descripción</th>
                    <th>Puntos</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($historial) > 0): ?>
                    <?php foreach ($historial as $p): ?>
                        <tr>
                            <td><?php echo date("d/m/Y H:i", strtotime($p['fecha'])); ?></td>
                            <td><?php echo htmlspecialchars($p['descripcion']); ?></td>
                            <td class="<?php echo $p['puntos'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                <?php echo $p['puntos']; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="text-center text-muted">Aún no tienes puntos registrados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../src/Templates/Footer.php'; ?>