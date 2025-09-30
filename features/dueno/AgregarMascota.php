<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/MascotaController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\MascotaController;

// Inicializar configuración
AppConfig::init();

// Verificar autenticación
$authController = new AuthController();
$authController->checkRole('dueno');

// Controlador de Mascotas
$mascotaController = new MascotaController();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mascotaController->Store(); // usa el método de MascotaController
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Mascota - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
</head>

<body>
    <?php include __DIR__ . '/../../src/Templates/Header.php'; ?>
    <?php include __DIR__ . '/../../src/Templates/Navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item"><a class="nav-link" href="Dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link active" href="MisMascotas.php"><i class="fas fa-paw me-2"></i> Mis Mascotas</a></li>
                        <li class="nav-item"><a class="nav-link" href="SolicitarPaseo.php"><i class="fas fa-plus-circle me-2"></i> Solicitar Paseo</a></li>
                        <li class="nav-item"><a class="nav-link" href="MisPaseos.php"><i class="fas fa-walking me-2"></i> Mis Paseos</a></li>
                        <li class="nav-item"><a class="nav-link" href="MetodosPago.php"><i class="fas fa-credit-card me-2"></i> Métodos de Pago</a></li>
                        <li class="nav-item"><a class="nav-link" href="MisPuntos.php"><i class="fas fa-star me-2"></i> Mis Puntos</a></li>
                        <li class="nav-item"><a class="nav-link" href="Perfil.php"><i class="fas fa-user me-2"></i> Mi Perfil</a></li>
                    </ul>
                </div>
            </div>

            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Agregar Mascota</h1>
                    <a href="MisMascotas.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>

                <!-- Mensajes -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= $_SESSION['success'];
                        unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= $_SESSION['error'];
                        unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['errors'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <ul class="mb-0">
                            <?php foreach ($_SESSION['errors'] as $error): ?>
                                <li><?= $error; ?></li>
                            <?php endforeach;
                            unset($_SESSION['errors']); ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Formulario -->
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="card shadow">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="fas fa-paw text-primary me-2"></i> Información de la Mascota</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="nombre" name="nombre" required
                                                value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="raza" class="form-label">Raza</label>
                                            <input type="text" class="form-control" id="raza" name="raza"
                                                value="<?= htmlspecialchars($_POST['raza'] ?? '') ?>">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="tamano" class="form-label">Tamaño</label>
                                            <select class="form-select" id="tamano" name="tamano" required>
                                                <option value="">Seleccionar tamaño</option>
                                                <?php foreach (TAMANOS_MASCOTA as $key => $info): ?>
                                                    <option value="<?= $key ?>" <?= ($_POST['tamano'] ?? '') === $key ? 'selected' : '' ?>>
                                                        <?= $info['label'] ?> (<?= $info['rango'] ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>

                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="edad" class="form-label">Edad</label>
                                            <input type="number" class="form-control" id="edad" name="edad" min="0" max="30"
                                                value="<?= htmlspecialchars($_POST['edad'] ?? '') ?>">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="observaciones" class="form-label">Observaciones</label>
                                        <textarea class="form-control" id="observaciones" name="observaciones" rows="4"><?= htmlspecialchars($_POST['observaciones'] ?? '') ?></textarea>
                                    </div>

                                    <div class="d-flex justify-content-between">
                                        <a href="MisMascotas.php" class="btn btn-outline-secondary"><i class="fas fa-times me-1"></i> Cancelar</a>
                                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Guardar Mascota</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include __DIR__ . '/../../src/Templates/Footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/main.js"></script>
</body>

</html>