<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Models/Usuario.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Models\Usuario;
use Jaguata\Helpers\Session;

AppConfig::init();

// Verificar login (acepta cualquier usuario logueado)
$authController = new AuthController();
$authController->checkAuth();

$usuarioModel = new Usuario();
$usuarioId    = Session::get('usuario_id');
$usuario      = $usuarioModel->getById($usuarioId);

if (!$usuario) {
    echo "Error: Usuario no encontrado";
    exit;
}

$puntos = $usuario['puntos'] ?? 0;

$titulo = "Mis Puntos - Jaguata";
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title><?= $titulo ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <?php include __DIR__ . '/../../src/Templates/Header.php'; ?>
    <?php include __DIR__ . '/../../src/Templates/Navbar.php'; ?>

    <div class="container mt-4">
        <h2><i class="fas fa-star text-warning me-2"></i> Mis Puntos</h2>
        <div class="card shadow mt-3">
            <div class="card-body text-center">
                <h3>Tienes</h3>
                <p class="display-4 text-primary"><?= htmlspecialchars($puntos) ?> ⭐</p>
                <p class="text-muted">Obtienes puntos cada vez que completas un paseo.</p>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../../src/Templates/Footer.php'; ?>
</body>

</html>