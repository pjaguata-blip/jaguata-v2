<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/MascotaController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\MascotaController;
use Jaguata\Helpers\Session;

AppConfig::init();
$auth = new AuthController();
$auth->checkRole('dueno');

$mascotaController = new MascotaController();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mascotaController->Store();
}

$razasDisponibles = [
    "Labrador Retriever",
    "Golden Retriever",
    "Pastor Alemán",
    "Bulldog",
    "Caniche (Poodle)",
    "Beagle",
    "Rottweiler",
    "Yorkshire Terrier",
    "Boxer",
    "Siberian Husky",
    "Doberman",
    "Shih Tzu",
    "Chihuahua",
    "Gran Danés",
    "Pomerania",
    "Border Collie",
    "Bulldog Francés",
    "Cocker Spaniel",
    "Pug",
    "Bichón Frisé",
    "San Bernardo",
    "Terranova",
    "Shiba Inu",
    "Pastor Belga Malinois",
    "Cane Corso"
];
sort($razasDisponibles);

$tamanoPost = $_POST['tamano'] ?? '';
$pesoPost = $_POST['peso_kg'] ?? '';
$edadValorPost = $_POST['edad_valor'] ?? '';
$edadUnidadPost = $_POST['edad_unidad'] ?? 'meses';
$baseFeatures = AppConfig::getBaseUrl() . "/features/dueno";
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Agregar Mascota - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f7fa;
            font-family: "Poppins", sans-serif;
        }

        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            color: #f8f9fa;
            min-height: 100vh;
            padding-top: 1rem;
            box-shadow: 4px 0 12px rgba(0, 0, 0, 0.15);
        }

        .sidebar .nav-link {
            color: #ddd;
            border-radius: 8px;
            padding: 10px 16px;
            margin: 4px 8px;
            display: flex;
            align-items: center;
            font-weight: 500;
            transition: all .2s ease;
        }

        .sidebar .nav-link:hover {
            background: #343454;
            color: #fff;
            transform: translateX(4px);
        }

        .sidebar .nav-link.active {
            background: #3c6255;
            color: #fff;
        }

        main {
            background: #f5f7fa;
            padding: 2rem;
        }

        .page-header {
            background: linear-gradient(90deg, #20c997, #3c6255);
            color: #fff;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-weight: 600;
            margin: 0;
        }

        .page-header .btn-light {
            background: #fff;
            color: #3c6255;
        }

        .page-header .btn-light:hover {
            background: #3c6255;
            color: #fff;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .btn-primary {
            background: #3c6255;
            border: none;
        }

        .btn-primary:hover {
            background: #2f4e45;
        }

        .btn-outline-secondary {
            border-color: #20c997;
            color: #20c997;
        }

        .btn-outline-secondary:hover {
            background: #20c997;
            color: #fff;
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../../src/Templates/Header.php'; ?>
    <?php include __DIR__ . '/../../src/Templates/Navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column gap-1">
                        <li>
                            <a class="nav-link active" href="#"><i class="fas fa-paw me-2"></i>Agregar Mascota</a>
                        </li>
                        <li><a class="nav-link" href="<?= $baseFeatures; ?>/MisMascotas.php"><i class="fas fa-list-ul me-2"></i>Mis Mascotas</a></li>
                        <li><a class="nav-link" href="<?= $baseFeatures; ?>/EditarPerfil.php"><i class="fas fa-user-cog me-2"></i>Editar Perfil</a></li>
                        <li><a class="nav-link text-danger" href="<?= BASE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Salir</a></li>
                    </ul>
                </div>
            </div>

            <!-- Main -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="page-header">
                    <h1><i class="fas fa-dog me-2"></i>Agregar Mascota</h1>
                    <a href="MisMascotas.php" class="btn btn-light btn-sm"><i class="fas fa-arrow-left me-1"></i> Volver</a>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show shadow-sm">
                        <i class="fas fa-check-circle me-2"></i><?= $_SESSION['success'];
                                                                unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm">
                        <i class="fas fa-exclamation-triangle me-2"></i><?= $_SESSION['error'];
                                                                        unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header bg-gradient" style="background:linear-gradient(90deg,#20c997,#3c6255);color:#fff;">
                                <h5 class="mb-0"><i class="fas fa-dog me-2"></i>Información de la Mascota</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" id="formMascota">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="nombre" required value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Raza</label>
                                            <select class="form-select" id="raza" name="raza">
                                                <option value="">Seleccione una raza</option>
                                                <?php foreach ($razasDisponibles as $r): ?>
                                                    <option value="<?= htmlspecialchars($r) ?>" <?= (($_POST['raza'] ?? '') === $r ? 'selected' : '') ?>><?= htmlspecialchars($r) ?></option>
                                                <?php endforeach; ?>
                                                <option value="Otra" <?= (($_POST['raza'] ?? '') === 'Otra' ? 'selected' : '') ?>>Otra</option>
                                            </select>
                                            <input type="text" class="form-control mt-2 d-none" id="raza_otra" name="raza_otra" placeholder="Especifique la raza" value="<?= htmlspecialchars($_POST['raza_otra'] ?? '') ?>">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Peso (kg) <span class="text-danger">*</span></label>
                                            <input type="number" step="0.1" min="0" class="form-control" id="peso_kg" name="peso_kg" required value="<?= htmlspecialchars($pesoPost) ?>">
                                            <div class="form-text">El tamaño se determina automáticamente según el peso.</div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Tamaño</label>
                                            <div class="btn-group w-100" role="group">
                                                <input type="radio" class="btn-check" name="tamano" id="tam_peq" value="pequeno" <?= $tamanoPost === 'pequeno' ? 'checked' : ''; ?>>
                                                <label class="btn btn-outline-success" for="tam_peq">Pequeño</label>
                                                <input type="radio" class="btn-check" name="tamano" id="tam_med" value="mediano" <?= $tamanoPost === 'mediano' ? 'checked' : ''; ?>>
                                                <label class="btn btn-outline-warning" for="tam_med">Mediano</label>
                                                <input type="radio" class="btn-check" name="tamano" id="tam_gra" value="grande" <?= $tamanoPost === 'grande' ? 'checked' : ''; ?>>
                                                <label class="btn btn-outline-danger" for="tam_gra">Grande</label>
                                                <input type="radio" class="btn-check" name="tamano" id="tam_gig" value="gigante" <?= $tamanoPost === 'gigante' ? 'checked' : ''; ?>>
                                                <label class="btn btn-outline-dark" for="tam_gig">Gigante</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Edad</label>
                                            <div class="input-group">
                                                <input type="number" min="0" step="1" class="form-control" id="edad_valor" name="edad_valor" placeholder="Ej: 8" value="<?= htmlspecialchars($edadValorPost) ?>">
                                                <select class="form-select" id="edad_unidad" name="edad_unidad">
                                                    <option value="meses" <?= $edadUnidadPost === 'meses' ? 'selected' : ''; ?>>Meses</option>
                                                    <option value="anios" <?= $edadUnidadPost === 'anios' ? 'selected' : ''; ?>>Años</option>
                                                </select>
                                            </div>
                                            <input type="hidden" id="edad_meses" name="edad_meses" value="<?= htmlspecialchars($_POST['edad_meses'] ?? '') ?>">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Observaciones</label>
                                        <textarea class="form-control" name="observaciones" rows="4"><?= htmlspecialchars($_POST['observaciones'] ?? '') ?></textarea>
                                    </div>

                                    <div class="text-end">
                                        <button type="submit" class="btn btn-primary px-4">
                                            <i class="fas fa-save me-1"></i> Guardar Mascota
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Mostrar campo "Otra raza"
        const selRaza = document.getElementById('raza');
        const razaOtra = document.getElementById('raza_otra');
        selRaza.addEventListener('change', () => {
            razaOtra.classList.toggle('d-none', selRaza.value !== 'Otra');
        });

        // Calcular edad en meses
        document.getElementById('edad_valor').addEventListener('input', actualizarEdad);
        document.getElementById('edad_unidad').addEventListener('change', actualizarEdad);

        function actualizarEdad() {
            const v = parseInt(document.getElementById('edad_valor').value, 10);
            const u = document.getElementById('edad_unidad').value;
            document.getElementById('edad_meses').value = isNaN(v) ? '' : (u === 'meses' ? v : v * 12);
        }
    </script>
</body>

</html>