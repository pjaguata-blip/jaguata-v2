<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/MascotaController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\MascotaController;
use Jaguata\Helpers\Session;

AppConfig::init();
$auth = new AuthController();
$auth->checkRole('dueno');

/* Controlador y validaciones */
$mascotaCtrl = new MascotaController();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['error'] = "ID inválido";
    header("Location: MisMascotas.php");
    exit;
}

$mascota = $mascotaCtrl->show($id);
if (isset($mascota['error'])) {
    $_SESSION['error'] = $mascota['error'];
    header("Location: MisMascotas.php");
    exit;
}

/* Update */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resp = $mascotaCtrl->update($id);

    if (!empty($resp['success'])) {
        $_SESSION['success'] = "Mascota actualizada correctamente";
        header("Location: EditarMascota.php?id=" . $id);
        exit;
    }

    $_SESSION['error'] = $resp['error'] ?? "No se pudo actualizar la mascota.";
}

/* Helpers */
function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/* Datos UI */
$nombre    = h($mascota['nombre'] ?? '');
$raza      = h($mascota['raza'] ?? '');
$peso      = $mascota['peso_kg'] ?? '';
$tamano    = $mascota['tamano'] ?? '';
$edadMeses = (int)($mascota['edad_meses'] ?? 0);
$obs       = h($mascota['observaciones'] ?? '');
$foto      = $mascota['foto_url'] ?? '';

$razasDisponibles = [
    "Labrador Retriever",
    "Golden Retriever",
    "Pastor Alemán",
    "Beagle",
    "Bulldog",
    "Pug",
    "Chihuahua",
    "Doberman",
    "Cocker Spaniel",
    "Shih Tzu",
    "Border Collie"
];
sort($razasDisponibles);

$rol          = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures = BASE_URL . "/features/{$rol}";

$success = $_SESSION['success'] ?? null;
$error   = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Editar Mascota - Jaguata</title>

    <!-- CSS global (igual al Dashboard) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet" />
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet" />

    <style>
        html,
        body {
            height: 100%;
        }

        body {
            background: var(--gris-fondo, #f4f6f9);
        }
        main.main-content {
            margin-left: var(--sidebar-w);
            width: calc(100% - var(--sidebar-w));
            min-height: 100vh;
            padding: 24px;
            box-sizing: border-box;
        }
        @media (max-width: 768px) {
            main.main-content {
                margin-left: 0 !important;
                width: 100% !important;
                margin-top: 0 !important;
                padding: calc(16px + var(--topbar-h)) 16px 16px !important;
            }
        }
        .img-preview {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 14px;
            border: 3px solid rgba(32, 201, 151, 0.20);
        }
    </style>
</head>

<body class="page-no-topbar-margin">

    <!-- Sidebar Dueño unificado (igual al Dashboard) -->
    <?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>

    <main class="main-content">
        <div class="2">
            <div class="header-box header-mascotas mb-3 d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="fw-bold mb-1">
                        <i class="fas fa-paw me-2"></i>Editar Mascota
                    </h1>
                    <p class="mb-0">Actualizá los datos de tu mascota para mejorar su experiencia de paseo.</p>
                </div>

                <div class="d-flex gap-2">
                    <a href="<?= $baseFeatures; ?>/MisMascotas.php" class="btn btn-outline-light btn-sm fw-semibold">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>
            </div>
            <div class="section-card">
                <div class="section-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span><i class="fas fa-dog me-2"></i>Información de <?= $nombre ?: 'Mascota' ?></span>
                    <small class="text-muted">ID #<?= (int)$id; ?></small>
                </div>

                <div class="section-body">
                    <form method="POST" enctype="multipart/form-data" id="formMascota">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nombre" value="<?= $nombre ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Raza</label>
                                <select class="form-select" name="raza" id="raza">
                                    <option value="">Seleccione una raza</option>
                                    <?php foreach ($razasDisponibles as $r): ?>
                                        <option value="<?= h($r) ?>" <?= $raza === $r ? 'selected' : '' ?>>
                                            <?= h($r) ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="Otra" <?= $raza === 'Otra' ? 'selected' : '' ?>>Otra</option>
                                </select>

                                <input type="text"
                                    class="form-control mt-2 d-none"
                                    id="raza_otra"
                                    name="raza_otra"
                                    placeholder="Especifique la raza">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Peso (kg)</label>
                                <input type="number" step="0.1" class="form-control" name="peso_kg"
                                    value="<?= h((string)$peso) ?>" placeholder="Ej: 12.5">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Tamaño</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="tamano" id="tam_peq" value="pequeno" <?= $tamano === 'pequeno' ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-success" for="tam_peq">Pequeño</label>
                                    <input type="radio" class="btn-check" name="tamano" id="tam_med" value="mediano" <?= $tamano === 'mediano' ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-warning" for="tam_med">Mediano</label>
                                    <input type="radio" class="btn-check" name="tamano" id="tam_gra" value="grande" <?= $tamano === 'grande' ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-danger" for="tam_gra">Grande</label>
                                    <input type="radio" class="btn-check" name="tamano" id="tam_gig" value="gigante" <?= $tamano === 'gigante' ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-dark" for="tam_gig">Gigante</label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Edad (en meses)</label>
                                <input type="number" min="0" class="form-control" name="edad_meses" value="<?= (int)$edadMeses ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Foto</label>
                                <input type="file" class="form-control" id="foto" name="foto" accept="image/*">
                                <div class="mt-2 d-flex align-items-center gap-3 flex-wrap">
                                    <?php if (!empty($foto)): ?>
                                        <img src="<?= h($foto) ?>" class="img-preview" id="fotoActual" alt="Foto actual">
                                    <?php endif; ?>
                                    <img src="" class="img-preview d-none" id="fotoPreview" alt="Vista previa">
                                </div>
                                <small class="text-muted d-block mt-2">
                                    Tip: subí una imagen cuadrada para que se vea mejor en el perfil.
                                </small>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Observaciones</label>
                                <textarea class="form-control" name="observaciones" rows="3"><?= $obs ?></textarea>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="<?= $baseFeatures; ?>/MisMascotas.php" class="btn btn-outline-secondary">
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn-gradient px-4">
                                <i class="fas fa-save me-1"></i> Guardar cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </main>

    <!-- JS (igual al Dashboard + SweetAlert) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Raza "Otra"
        const selRaza = document.getElementById('raza');
        const razaOtra = document.getElementById('raza_otra');

        const toggleOtra = () => {
            const isOtra = selRaza.value === 'Otra';
            razaOtra.classList.toggle('d-none', !isOtra);
            if (!isOtra) razaOtra.value = '';
        };

        selRaza.addEventListener('change', toggleOtra);
        toggleOtra();

        // Preview foto
        const inputFoto = document.getElementById('foto');
        inputFoto.addEventListener('change', (e) => {
            const f = e.target.files?.[0];
            if (!f) return;

            const p = document.getElementById('fotoPreview');
            p.src = URL.createObjectURL(f);
            p.classList.remove('d-none');

            const actual = document.getElementById('fotoActual');
            if (actual) actual.classList.add('d-none');
        });

        // Alerts (flash)
        <?php if (!empty($success)): ?>
            Swal.fire({
                icon: 'success',
                title: '¡Listo!',
                text: '<?= addslashes((string)$success) ?>',
                timer: 2200,
                showConfirmButton: false
            });
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            Swal.fire({
                icon: 'error',
                title: 'Ups...',
                text: '<?= addslashes((string)$error) ?>'
            });
        <?php endif; ?>
    </script>
</body>

</html>
