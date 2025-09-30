<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/MetodoPagoController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\MetodoPagoController;

AppConfig::init();
$auth = new AuthController();
$auth->checkRole('dueno');

// Controlador
$controller = new MetodoPagoController();
$metodosPago = $controller->index(); // obtiene todos los métodos del usuario logueado
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Métodos de Pago - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">
</head>

<body>
    <!-- Navbar -->
    <?php include __DIR__ . '/../../src/Templates/Navbar.php'; ?>

    <div class="container mt-4">
        <h2 class="mb-4"><i class="fas fa-credit-card me-2"></i>Mis Métodos de Pago</h2>

        <!-- Mensajes -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'];
                                                unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'];
                                            unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <a href="AgregarMetodoPago.php" class="btn btn-primary mb-3">
            <i class="fas fa-plus me-1"></i>Agregar Método de Pago
        </a>

        <?php if (empty($metodosPago)): ?>
            <div class="text-center py-5">
                <i class="fas fa-credit-card fa-3x text-muted mb-3"></i>
                <p class="text-muted">No tienes métodos de pago registrados</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Tipo</th>
                            <th>Entidad</th>
                            <th>Número</th>
                            <th>Titular</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($metodosPago as $m): ?>
                            <tr>
                                <td><i class="fas fa-<?= $m['tipo'] === 'tarjeta' ? 'credit-card' : 'university' ?> me-1"></i> <?= ucfirst($m['tipo']); ?></td>
                                <td><?= htmlspecialchars($m['entidad']); ?></td>
                                <td>**** <?= substr($m['numero'], -4); ?></td>
                                <td><?= htmlspecialchars($m['titular']); ?></td>
                                <td>
                                    <a href="EditarMetodoPago.php?id=<?= $m['metodo_id'] ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                    <a href="EliminarMetodoPago.php?id=<?= $m['metodo_id'] ?>"
                                        class="btn btn-sm btn-danger"
                                        onclick="return confirm('¿Seguro que quieres eliminar este método de pago?')">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <?php include __DIR__ . '/../../src/Templates/Footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>