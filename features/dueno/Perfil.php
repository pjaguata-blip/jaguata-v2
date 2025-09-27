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

// Verificar autenticación (acepta cualquier rol logueado)
$authController = new AuthController();
$authController->checkAuth(); // valida login sin forzar rol único

// Obtener datos del usuario
$usuarioModel = new Usuario();
$usuarioId = Session::get('usuario_id');
$rol        = Session::get('rol');
$usuario    = $usuarioModel->getById($usuarioId);

if (!$usuario) {
    echo "Error: No se encontró el usuario.";
    exit;
}

$titulo = "Mi Perfil - Jaguata";
?>

<?php include __DIR__ . '/../../src/Templates/header.php'; ?>

<div class="container mt-4">
    <h2 class="mb-3"><i class="fas fa-user me-2"></i> Mi Perfil</h2>
    <div class="card shadow-sm">
        <div class="card-body">
            <p><strong>Rol:</strong> <?= htmlspecialchars(ucfirst($rol)) ?></p>
            <p><strong>Nombre:</strong> <?= htmlspecialchars($usuario['nombre']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($usuario['email']) ?></p>
            <p><strong>Teléfono:</strong> <?= htmlspecialchars($usuario['telefono'] ?? 'No registrado') ?></p>
            <p><strong>Dirección:</strong> <?= htmlspecialchars($usuario['direccion'] ?? 'No registrada') ?></p>

            <?php if ($rol === 'paseador'): ?>
                <p><strong>Experiencia:</strong> <?= htmlspecialchars($usuario['experiencia'] ?? 'No especificada') ?></p>
                <p><strong>Zona de trabajo:</strong> <?= htmlspecialchars($usuario['zona'] ?? 'No especificada') ?></p>
            <?php endif; ?>

            <a href="EditarPerfil.php" class="btn btn-primary mt-3">
                <i class="fas fa-edit"></i> Editar Perfil
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../src/Templates/footer.php'; ?>