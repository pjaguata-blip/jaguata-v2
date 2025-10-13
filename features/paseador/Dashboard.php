<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;

// 🔹 Verificar autenticación con rol paseador
$authController = new AuthController();
$authController->checkRole('paseador');

$titulo = 'Dashboard Paseador - Jaguata';
?>

<?php include __DIR__ . '/../../src/Templates/Header.php'; ?>
<?php include __DIR__ . '/../../src/Templates/navbar.php'; ?>


<div class="container py-5">
    <h1 class="fw-bold text-success">Bienvenido Paseador</h1>
    <p class="text--color #ffff">Aquí podrás gestionar tus paseos, disponibilidad y perfil.</p>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow border-0 mb-4">
                <div class="card-body">
                    <h5 class="card-title">Mis Paseos Asignados</h5>
                    <p class="card-text">Verifica los paseos que te han asignado los dueños.</p>
                    <a href="MisPaseos.php" class="btn btn-success">
                        <i class="fas fa-walking me-1"></i> Ver Paseos
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow border-0 mb-4">
                <div class="card-body">
                    <h5 class="card-title">Mi Perfil de Paseador</h5>
                    <p class="card-text">Configura tu disponibilidad, experiencia y precios.</p>
                    <a href="Perfil.php" class="btn btn-outline-success">
                        <i class="fas fa-user-edit me-1"></i> Editar Perfil
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../../src/Templates/Navbar.php'; ?>
<?php include __DIR__ . '/../../src/Templates/Footer.php'; ?>