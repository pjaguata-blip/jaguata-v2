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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="<?= ASSETS_URL; ?>/css/jaguata-theme.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background: #f6f9f7;
            font-family: "Poppins", sans-serif;
        }

        h1,
        h2,
        h3 {
            color: #3c6255;
            font-weight: 600;
        }

        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        }

        .card-header {
            background: linear-gradient(90deg, #3c6255, #20c997);
            color: #fff;
            font-weight: 600;
            border-radius: 16px 16px 0 0;
        }

        .btn-primary {
            background: #3c6255;
            border: none;
        }

        .btn-primary:hover {
            background: #2f4e45;
        }

        .btn-outline-secondary {
            border-color: #3c6255;
            color: #3c6255;
        }

        .btn-outline-secondary:hover {
            background: #3c6255;
            color: #fff;
        }

        .btn-warning {
            color: #fff;
            background: #f0ad4e;
            border: none;
        }

        .btn-warning:hover {
            background: #ec971f;
        }

        .btn-danger {
            background: #d9534f;
            border: none;
        }

        .btn-danger:hover {
            background: #c9302c;
        }

        .table thead th {
            background: #3c6255;
            color: #fff;
            font-weight: 600;
        }

        .table-hover tbody tr:hover {
            background: #f0fff8;
        }

        .alert {
            border-radius: 12px;
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../../src/Templates/Header.php'; ?>
    <?php include __DIR__ . '/../../src/Templates/Navbar.php'; ?>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1"><i class="fas fa-credit-card text-success me-2"></i>Mis Métodos de Pago</h1>
                <p class="text-muted mb-0">Gestioná tus tarjetas y cuentas bancarias registradas 🐾</p>
            </div>
            <a href="<?= BASE_URL ?>/features/dueno/Dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
        </div>

        <!-- Mensajes -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?= $_SESSION['success'];
                                                        unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-2"></i><?= $_SESSION['error'];
                                                                unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-wallet me-2"></i>Listado de Métodos de Pago</span>
                <a href="AgregarMetodoPago.php" class="btn btn-light btn-sm text-success fw-semibold">
                    <i class="fas fa-plus-circle me-1"></i>Agregar
                </a>
            </div>

            <div class="card-body">
                <?php if (empty($metodosPago)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-credit-card fa-3x text-muted mb-3"></i>
                        <h5 class="fw-semibold text-muted">No tenés métodos de pago registrados</h5>
                        <p class="text-muted mb-3">Agregá una tarjeta o cuenta para facilitar tus pagos.</p>
                        <a href="AgregarMetodoPago.php" class="btn btn-primary px-4">
                            <i class="fas fa-plus me-1"></i>Agregar Método
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-center">
                            <thead>
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
                                            <a href="EditarMetodoPago.php?id=<?= $m['metodo_id'] ?>" class="btn btn-sm btn-warning me-1">
                                                <i class="fas fa-edit"></i> Editar
                                            </a>
                                            <a href="EliminarMetodoPago.php?id=<?= $m['metodo_id'] ?>"
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('¿Seguro que querés eliminar este método de pago?')">
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
        </div>
    </div>

    <?php include __DIR__ . '/../../src/Templates/Footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>