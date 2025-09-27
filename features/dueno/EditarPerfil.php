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

// Verificar autenticación SOLO para rol dueño
$authController = new AuthController();
$authController->checkRole('dueno');

// Obtener datos actuales
$usuarioModel = new Usuario();
$usuarioId    = Session::get('usuario_id');
$usuario      = $usuarioModel->getById($usuarioId);

if (!$usuario) {
    echo "Error: No se encontró el usuario.";
    exit;
}

$mensaje = '';
$error   = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre    = trim($_POST['nombre'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    if ($nombre === '' || $email === '') {
        $error = "El nombre y el email son obligatorios.";
    } else {
        $data = [
            'nombre'    => $nombre,
            'email'     => $email,
            'telefono'  => $telefono,
            'direccion' => $direccion
        ];

        if ($usuarioModel->updateUsuario($usuarioId, $data)) {
            $mensaje = "Perfil actualizado correctamente.";
            $usuario = $usuarioModel->getById($usuarioId);
        } else {
            $error = "Hubo un problema al actualizar el perfil.";
        }
    }
}

$titulo = "Editar Perfil (Dueño) - Jaguata";
?>

<?php include __DIR__ . '/../../src/Templates/header.php'; ?>

<div class="container mt-4">
    <h2 class="mb-3"><i class="fas fa-edit me-2"></i> Editar Perfil - Dueño</h2>

    <?php if ($mensaje): ?>
        <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label for="nombre" class="form-label">Nombre completo</label>
            <input type="text" class="form-control" id="nombre" name="nombre"
                value="<?= htmlspecialchars($usuario['nombre']) ?>" required>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Correo electrónico</label>
            <input type="email" class="form-control" id="email" name="email"
                value="<?= htmlspecialchars($usuario['email']) ?>" required>
        </div>

        <div class="mb-3">
            <label for="telefono" class="form-label">Teléfono</label>
            <input type="text" class="form-control" id="telefono" name="telefono"
                value="<?= htmlspecialchars($usuario['telefono'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label for="direccion" class="form-label">Dirección</label>
            <input type="text" class="form-control" id="direccion" name="direccion"
                value="<?= htmlspecialchars($usuario['direccion'] ?? '') ?>">
        </div>

        <button type="submit" class="btn btn-success">
            <i class="fas fa-save me-2"></i> Guardar Cambios
        </button>
        <a href="Perfil.php" class="btn btn-secondary ms-2">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </form>
</div>

<?php include __DIR__ . '/../../src/Templates/footer.php'; ?>