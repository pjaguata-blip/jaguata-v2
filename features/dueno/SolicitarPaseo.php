<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Controllers/MascotaController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Controllers\MascotaController;

// Inicializar aplicación
AppConfig::init();

// Verificar autenticación
$authController = new AuthController();
$authController->checkRole('dueno');

// Obtener controladores
$paseoController = new PaseoController();
$mascotaController = new MascotaController();

// Obtener mascotas
$mascotas = $mascotaController->index();

// Verificar que tenga mascotas
if (empty($mascotas)) {
    $_SESSION['error'] = 'Debes tener al menos una mascota registrada para solicitar paseos';
    header('Location: AgregarMascota.php');
    exit;
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paseoController->store();
}

// Obtener paseadores disponibles
$paseadorModel = new \Jaguata\Models\Paseador();
$paseadores = $paseadorModel->getDisponibles();

// Obtener mascota preseleccionada
$mascotaPreseleccionada = (int)($_GET['mascota_id'] ?? 0);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar Paseo - Jaguata</title>
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
                        <li class="nav-item">
                            <a class="nav-link" href="Dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="MisMascotas.php">
                                <i class="fas fa-paw me-2"></i>
                                Mis Mascotas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="SolicitarPaseo.php">
                                <i class="fas fa-plus-circle me-2"></i>
                                Solicitar Paseo
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="MisPaseos.php">
                                <i class="fas fa-walking me-2"></i>
                                Mis Paseos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="MetodosPago.php">
                                <i class="fas fa-credit-card me-2"></i>
                                Métodos de Pago
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="MisPuntos.php">
                                <i class="fas fa-star me-2"></i>
                                Mis Puntos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="Perfil.php">
                                <i class="fas fa-user me-2"></i>
                                Mi Perfil
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Solicitar Paseo</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="MisPaseos.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>
                            Volver
                        </a>
                    </div>
                </div>

                <!-- Mensajes -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $_SESSION['success'];
                        unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $_SESSION['error'];
                        unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['errors'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <ul class="mb-0">
                            <?php foreach ($_SESSION['errors'] as $error): ?>
                                <li><?php echo $error; ?></li>
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
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-walking text-primary me-2"></i>
                                    Información del Paseo
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" data-validate>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="mascota_id" class="form-label">
                                                Mascota <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select" id="mascota_id" name="mascota_id" required>
                                                <option value="">Seleccionar mascota</option>
                                                <?php foreach ($mascotas as $mascota): ?>
                                                    <option value="<?php echo $mascota['mascota_id']; ?>"
                                                        <?php echo $mascota['mascota_id'] == $mascotaPreseleccionada ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($mascota['nombre']); ?>
                                                        (<?php echo ucfirst($mascota['tamano']); ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="form-text">Selecciona la mascota que quieres pasear</div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="paseador_id" class="form-label">
                                                Paseador <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select" id="paseador_id" name="paseador_id" required>
                                                <option value="">Seleccionar paseador</option>
                                                <?php foreach ($paseadores as $paseador): ?>
                                                    <option value="<?php echo $paseador['paseador_id']; ?>">
                                                        <?php echo htmlspecialchars($paseador['nombre']); ?>
                                                        - ₲<?php echo number_format($paseador['precio_hora'], 0, ',', '.'); ?>/hora
                                                        (⭐ <?php echo $paseador['calificacion']; ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="form-text">Selecciona un paseador disponible</div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="inicio" class="form-label">
                                                Fecha y Hora <span class="text-danger">*</span>
                                            </label>
                                            <input type="datetime-local" class="form-control" id="inicio" name="inicio"
                                                value="<?php echo htmlspecialchars($_POST['inicio'] ?? ''); ?>"
                                                required>
                                            <div class="form-text">Cuándo quieres que comience el paseo</div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="duracion" class="form-label">
                                                Duración <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select" id="duracion" name="duracion" required>
                                                <option value="">Seleccionar duración</option>
                                                <option value="15" <?php echo ($_POST['duracion'] ?? '') === '15' ? 'selected' : ''; ?>>
                                                    15 minutos
                                                </option>
                                                <option value="30" <?php echo ($_POST['duracion'] ?? '') === '30' ? 'selected' : ''; ?>>
                                                    30 minutos
                                                </option>
                                                <option value="45" <?php echo ($_POST['duracion'] ?? '') === '45' ? 'selected' : ''; ?>>
                                                    45 minutos
                                                </option>
                                                <option value="60" <?php echo ($_POST['duracion'] ?? '') === '60' ? 'selected' : ''; ?>>
                                                    1 hora
                                                </option>
                                                <option value="90" <?php echo ($_POST['duracion'] ?? '') === '90' ? 'selected' : ''; ?>>
                                                    1.5 horas
                                                </option>
                                                <option value="120" <?php echo ($_POST['duracion'] ?? '') === '120' ? 'selected' : ''; ?>>
                                                    2 horas
                                                </option>
                                            </select>
                                            <div class="form-text">Duración del paseo</div>
                                        </div>
                                    </div>

                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Información importante:</strong>
                                        <ul class="mb-0 mt-2">
                                            <li>El paseo debe solicitarse con al menos 2 horas de anticipación</li>
                                            <li>El paseador confirmará la solicitud antes del paseo</li>
                                            <li>El pago se procesará una vez que el paseo sea confirmado</li>
                                            <li>Puedes cancelar el paseo hasta 1 hora antes del inicio</li>
                                        </ul>
                                    </div>

                                    <div class="d-flex justify-content-between">
                                        <a href="MisPaseos.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-times me-1"></i>
                                            Cancelar
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane me-1"></i>
                                            Solicitar Paseo
                                        </button>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const now = new Date();
            now.setHours(now.getHours() + 2); // suma 2 horas

            // Formatear en local (YYYY-MM-DDTHH:MM)
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hour = String(now.getHours()).padStart(2, '0');
            const minute = String(now.getMinutes()).padStart(2, '0');

            const minDateTime = `${year}-${month}-${day}T${hour}:${minute}`;

            const inputInicio = document.getElementById('inicio');
            inputInicio.min = minDateTime; // fecha mínima
            inputInicio.value = minDateTime; // valor por defecto
        });
    </script>

</body>

</html>