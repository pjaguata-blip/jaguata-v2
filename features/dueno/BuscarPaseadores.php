<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseadorController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseadorController;

AppConfig::init();
$auth = new AuthController();
$auth->checkRole('dueno');

$controller = new PaseadorController();
$paseadores = $controller->buscar($_GET['q'] ?? ''); // Búsqueda opcional
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Buscar Paseadores - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">
</head>

<body>
    <!-- Navbar -->
    <?php include __DIR__ . '/../../src/Templates/Navbar.php'; ?>

    <div class="container mt-4">
        <h2 class="mb-4"><i class="fas fa-search me-2"></i>Buscar Paseadores</h2>

        <!-- Formulario de búsqueda -->
        <form class="row mb-4" method="get">
            <div class="col-md-8">
                <input type="text" name="q" class="form-control"
                    placeholder="Buscar por nombre o zona..."
                    value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-1"></i> Buscar
                </button>
            </div>
        </form>

        <!-- Resultados -->
        <div class="row">
            <?php if (empty($paseadores)): ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-user-times fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No se encontraron paseadores</p>
                </div>
            <?php else: ?>
                <?php foreach ($paseadores as $p): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title text-primary">
                                    <i class="fas fa-user me-2"></i><?= htmlspecialchars($p['nombre'] ?? 'Sin nombre') ?>
                                </h5>
                                <p class="mb-1">
                                    <i class="fas fa-map-marker-alt me-2 text-muted"></i>
                                    Zona: <?= htmlspecialchars($p['zona'] ?? 'No especificada') ?>
                                </p>
                                <p class="mb-1">
                                    <i class="fas fa-briefcase me-2 text-muted"></i>
                                    Experiencia: <?= htmlspecialchars($p['experiencia'] ?? 0) ?> años
                                </p>
                                <p class="mb-3">
                                    <i class="fas fa-dollar-sign me-2 text-muted"></i>
                                    Precio: ₲<?= number_format($p['precio_hora'] ?? 0, 0, ',', '.') ?>/hora
                                </p>

                                <a href="SolicitarPaseo.php?paseador=<?= $p['paseador_id'] ?>"
                                    class="btn btn-success mt-auto">
                                    <i class="fas fa-plus-circle me-1"></i> Solicitar Paseo
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <?php include __DIR__ . '/../../src/Templates/Footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>