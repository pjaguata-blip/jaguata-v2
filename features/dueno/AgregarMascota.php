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
$usuarioNombre = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Dueño');
$baseFeatures = AppConfig::getBaseUrl() . "/features/dueno";
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Mascota - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            background-color: #f5f7fa;
            font-family: "Poppins", sans-serif;
        }

        /* Sidebar */
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
            transition: all 0.2s ease-in-out;
            font-weight: 500;
        }

        .sidebar .nav-link:hover {
            background-color: #343454;
            color: #fff;
            transform: translateX(4px);
        }

        .sidebar .nav-link.active {
            background-color: #3c6255;
            color: #fff;
        }

        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }

        /* Header */
        .page-header {
            background: linear-gradient(90deg, #20c997, #3c6255);
            color: #fff;
            padding: 1.5rem 2rem;
            border-radius: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .page-header h2 {
            font-weight: 600;
            font-size: 1.6rem;
            letter-spacing: 0.2px;
            margin: 0;
        }

        .page-header .btn-light {
            color: #3c6255;
            background-color: #fff;
            font-weight: 500;
        }

        .page-header .btn-light:hover {
            background-color: #3c6255;
            color: #fff;
        }

        /* Cards */
        .card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
        }

        .card-header {
            background-color: #3c6255;
            color: #fff;
            font-weight: 600;
            border-top-left-radius: 14px;
            border-top-right-radius: 14px;
        }

        .btn-gradient {
            background: linear-gradient(90deg, #3c6255, #20c997);
            border: none;
            color: #fff;
            font-weight: 500;
        }

        .btn-gradient:hover {
            opacity: 0.9;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
        }

        /* Tamaño buttons */
        .btn-group label {
            flex: 1;
            font-weight: 500;
        }

        footer {
            text-align: center;
            color: #777;
            font-size: 0.85rem;
            padding: 1rem 0;
            margin-top: 2rem;
        }

        /* Botón volver arriba */
        #btnTop {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 45px;
            height: 45px;
            border: none;
            border-radius: 50%;
            background: linear-gradient(135deg, #3c6255, #20c997);
            color: #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
            cursor: pointer;
            display: none;
            z-index: 1000;
            transition: transform 0.2s ease, opacity 0.2s ease;
        }

        #btnTop:hover {
            transform: scale(1.1);
            opacity: 0.9;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="text-center mb-4">
                    <img src="<?= ASSETS_URL; ?>/uploads/perfiles/logojag.png" alt="Jaguata" width="50 class=" mb-3">
                    <hr class="text-light">
                </div>
                <ul class="nav flex-column gap-1 px-2">
                    <li><a class="nav-link" href="<?= $baseFeatures; ?>/Dashboard.php"><i class="fas fa-home"></i> Inicio</a></li>
                    <li><a class="nav-link" href="<?= $baseFeatures; ?>/MisMascotas.php"><i class="fas fa-paw"></i> Mis Mascotas</a></li>
                    <li><a class="nav-link active" href="<?= $baseFeatures; ?>/AgregarMascota.php"><i class="fas fa-plus-circle"></i> Agregar Mascota</a></li>
                    <li><a class="nav-link" href="<?= $baseFeatures; ?>/EditarPerfil.php"><i class="fas fa-user-cog"></i> Editar Perfil</a></li>
                    <li><a class="nav-link text-danger" href="<?= BASE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a></li>
                </ul>
            </div>

            <!-- Main -->

            <div class="page-header">
                <h2><i class="fas fa-dog me-2"></i> Agregar Mascota</h2>
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

            <div class="card">
                <div class="card-header"><i class="fas fa-info-circle me-2"></i> Información de la Mascota</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre *</label>
                                <input type="text" class="form-control" name="nombre" required value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
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

                            <div class="col-md-6">
                                <label class="form-label">Peso (kg) *</label>
                                <input type="number" step="0.1" min="0" class="form-control" id="peso_kg" name="peso_kg" required value="<?= htmlspecialchars($pesoPost) ?>">
                                <div class="form-text">El tamaño se determina automáticamente según el peso.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Tamaño</label>
                                <div class="btn-group w-100 flex-wrap" role="group">
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

                            <div class="col-md-6">
                                <label class="form-label">Edad</label>
                                <div class="input-group">
                                    <input type="number" min="0" step="1" class="form-control" id="edad_valor" name="edad_valor" placeholder="Ej: 8" value="<?= htmlspecialchars($edadValorPost) ?>">
                                    <select class="form-select" id="edad_unidad" name="edad_unidad">
                                        <option value="meses" <?= $edadUnidadPost === 'meses' ? 'selected' : ''; ?>>Meses</option>
                                        <option value="anios" <?= $edadUnidadPost === 'anios' ? 'selected' : ''; ?>>Años</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Observaciones</label>
                                <textarea class="form-control" name="observaciones" rows="4"><?= htmlspecialchars($_POST['observaciones'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-gradient px-4">
                                <i class="fas fa-save me-1"></i> Guardar Mascota
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <footer>© <?= date('Y') ?> Jaguata — Todos los derechos reservados.</footer>

        </div>
    </div>

    <button id="btnTop" title="Volver arriba"><i class="fas fa-arrow-up"></i></button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const selRaza = document.getElementById('raza');
        const razaOtra = document.getElementById('raza_otra');
        selRaza.addEventListener('change', () => {
            razaOtra.classList.toggle('d-none', selRaza.value !== 'Otra');
        });

        const btnTop = document.getElementById("btnTop");
        window.addEventListener("scroll", () => {
            btnTop.style.display = window.scrollY > 200 ? "block" : "none";
        });
        btnTop.addEventListener("click", () => window.scrollTo({
            top: 0,
            behavior: "smooth"
        }));
    </script>
</body>

</html>