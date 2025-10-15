<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Models/Usuario.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Models\Usuario;
use Jaguata\Helpers\Session;

// 🔹 Inicializar aplicación y conexión
AppConfig::init();

// 🔹 Verificar usuario logueado (cualquier rol)
$auth = new AuthController();
$auth->checkAuth();

// 🔹 Obtener información del usuario actual
$usuarioId = (int) Session::get('usuario_id');
$rol       = Session::getUsuarioRol() ?? 'dueno';

$usuarioModel = new Usuario();
$usuario      = $usuarioModel->getById($usuarioId);

if (!$usuario) {
    http_response_code(404);
    exit('❌ Usuario no encontrado');
}

$puntos = (int)($usuario['puntos'] ?? 0);
$titulo = "Mis Puntos - Jaguata";
?>

<?php include __DIR__ . '/../../src/Templates/Header.php'; ?>
<?php include __DIR__ . '/../../src/Templates/Navbar.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="card shadow-lg border-0">
                <div class="card-body text-center p-5">
                    <h1 class="fw-bold text-success mb-3">
                        <i class="fas fa-star text-warning me-2"></i> Mis Puntos
                    </h1>

                    <p class="lead text-color #ffff mb-4">
                        Cada paseo completado te otorga puntos de recompensa 🐾
                    </p>

                    <div class="bg-light rounded-4 py-4 mb-4">
                        <h2 class="display-3 fw-bold text-primary mb-0">
                            <?= number_format($puntos, 0, ',', '.') ?>
                        </h2>
                        <small class="text-secondary">puntos acumulados</small>
                    </div>

                    <a href="<?= BASE_URL ?>/features/<?= $rol ?>/Dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i> Volver al Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../src/Templates/Footer.php'; ?>