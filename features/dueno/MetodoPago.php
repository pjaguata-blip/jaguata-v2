<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/MetodoPagoController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\MetodoPagoController;
use Jaguata\Helpers\Session;

AppConfig::init();

// Verificar rol dueño
$auth = new AuthController();
$auth->requireRole(['dueno']);

// Controlador
$controller = new MetodoPagoController();
$usuarioId = Session::get('usuario_id');
$metodos = $controller->getByUsuario($usuarioId);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Métodos de Pago</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <?php include __DIR__ . '/../../src/Templates/Header.php'; ?>
    <?php include __DIR__ . '/../../src/Templates/Navbar.php'; ?>

    <div class="container mt-4">
        <h2>Mis Métodos de Pago</h2>

        <?php if (empty($metodos)): ?>
            <div class="alert alert-info">No tienes métodos de pago registrados.</div>
        <?php else: ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Alias</th>
                        <th>Expiración</th>
                        <th>Predeterminado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($metodos as $m): ?>
                        <tr>
                            <td><?= htmlspecialchars($m['tipo']) ?></td>
                            <td><?= htmlspecialchars($m['alias']) ?></td>
                            <td><?= $m['expiracion'] ?: '-' ?></td>
                            <td><?= $m['is_default'] ? '✔' : '' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <a href="AgregarMetodoPago.php" class="btn btn-primary">Agregar Método de Pago</a>
        <td>
            <a href="EditarMetodoPago.php?id=<?= $m['metodo_id'] ?>" class="btn btn-sm btn-warning">Editar</a>
            <a href="EliminarMetodoPago.php?id=<?= $m['metodo_id'] ?>"
                class="btn btn-sm btn-danger"
                onclick="return confirm('¿Seguro que deseas eliminar este método de pago?')">
                Eliminar
            </a>
        </td>

    </div>

    <?php include __DIR__ . '/../../src/Templates/Footer.php'; ?>
</body>

</html>