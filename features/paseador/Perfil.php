<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Models/Usuario.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Models\Usuario;
use Jaguata\Helpers\Session;

// Inicializar configuración
AppConfig::init();

// Verificar autenticación SOLO para rol paseador
$authController = new AuthController();
$authController->checkRole('paseador');

// Obtener datos del usuario
$usuarioModel = new Usuario();
$usuarioId = Session::get('usuario_id');
$usuario    = $usuarioModel->getById($usuarioId);

if (!$usuario) {
    echo "Error: No se encontró el usuario.";
    exit;
}

$titulo = "Mi Perfil (Paseador) - Jaguata";
?>

<?php include __DIR__ . '/../../src/Templates/header.php'; ?>

<div class="container mt-4">
    <h2 class="mb-3"><i class="fas fa-user me-2"></i> Mi Perfil - Paseador</h2>
    <div class="card shadow-sm">
        <div class="card-body">
            <p><strong>Rol:</strong> Paseador</p>
            <p><strong>Nombre:</strong> <?= htmlspecialchars($usuario['nombre']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($usuario['email']) ?></p>
            <p><strong>Teléfono:</strong> <?= htmlspecialchars($usuario['telefono'] ?? 'No registrado') ?></p>
            <p><strong>Dirección:</strong> <?= htmlspecialchars($usuario['direccion'] ?? 'No registrada') ?></p>
            <p><strong>Experiencia:</strong> <?= htmlspecialchars($usuario['experiencia'] ?? 'No especificada') ?></p>
            <p><strong>Zona de trabajo:</strong> <?= htmlspecialchars($usuario['zona'] ?? 'No especificada') ?></p>

            <a href="Dashboard.php" class="btn btn-secondary mt-3">
                <i class="fas fa-arrow-left"></i> Volver al Dashboard
            </a>

            <a href="EditarPerfil.php" class="btn btn-primary mt-3">
                <i class="fas fa-edit"></i> Editar Perfil
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../src/Templates/footer.php'; ?>