<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/MascotaController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\MascotaController;

AppConfig::init();
$auth = new AuthController();
$auth->checkRole('dueno');

$controller = new MascotaController();
$mascotas = $controller->index();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Mascotas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2>Mis Mascotas</h2>

    <!-- Mensajes -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <a href="AgregarMascota.php" class="btn btn-primary mb-3">Agregar Mascota</a>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Raza</th>
                <th>Tamaño</th>
                <th>Edad</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($mascotas as $m): ?>
            <tr>
                <td><?= htmlspecialchars($m['nombre']) ?></td>
                <td><?= htmlspecialchars($m['raza']) ?></td>
                <td><?= htmlspecialchars($m['tamano']) ?></td>
                <td><?= htmlspecialchars($m['edad']) ?></td>
                <td>
                    <a href="EditarMascota.php?id=<?= $m['mascota_id'] ?>" class="btn btn-sm btn-warning">Editar</a>
                    <a href="EliminarMascota.php?id=<?= $m['mascota_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro?')">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
