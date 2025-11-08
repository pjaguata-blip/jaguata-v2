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

/* 🔒 Auth rol dueño */
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
$nombre      = h($mascota['nombre'] ?? '');
$raza        = h($mascota['raza'] ?? '');
$peso        = $mascota['peso_kg'] ?? '';
$tamano      = $mascota['tamano'] ?? '';
$edadMeses   = (int)($mascota['edad_meses'] ?? 0);
$obs         = h($mascota['observaciones'] ?? '');
$foto        = $mascota['foto_url'] ?? '';

$razasDisponibles = ["Labrador Retriever", "Golden Retriever", "Pastor Alemán", "Beagle", "Bulldog", "Pug", "Chihuahua", "Doberman", "Cocker Spaniel", "Shih Tzu", "Border Collie"];
sort($razasDisponibles);

$rol          = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures = BASE_URL . "/features/{$rol}";
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Editar Mascota - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet" />
    <style>
        :root {
            --verde-jaguata: #3c6255;
            --verde-claro: #20c997;
            --gris-fondo: #f4f6f9;
            --gris-texto: #555;
            --blanco: #fff;
        }

        body {
            background: var(--gris-fondo);
            font-family: "Poppins", sans-serif;
            color: var(--gris-texto);
            margin: 0
        }

        /* Sidebar fija (250px) */
        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            color: var(--blanco);
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 1.5rem;
            box-shadow: 3px 0 10px rgba(0, 0, 0, .2);
            z-index: 1000
        }

        .sidebar .nav-link {
            color: #ccc;
            display: flex;
            align-items: center;
            gap: .8rem;
            padding: 12px 18px;
            border-radius: 8px;
            margin: 6px 10px;
            transition: .2s;
            font-size: .95rem
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: var(--verde-claro);
            color: #fff;
            transform: translateX(4px)
        }

        /* Main */
        main {
            margin-left: 250px;
            padding: 2rem
        }

        @media (max-width:768px) {
            main {
                margin-left: 0;
                padding: 1.25rem
            }
        }

        /* Header */
        .welcome-box {
            background: linear-gradient(90deg, var(--verde-claro), var(--verde-jaguata));
            color: #fff;
            padding: 1.6rem 2rem;
            border-radius: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .1)
        }

        /* Cards */
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .08)
        }

        .card-header {
            background: linear-gradient(90deg, var(--verde-jaguata), var(--verde-claro));
            color: #fff;
            font-weight: 600;
            border-top-left-radius: 16px;
            border-top-right-radius: 16px
        }

        .form-control,
        .form-select {
            border-radius: 10px
        }

        .img-preview {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 12px;
            border: 3px solid #e0f2f1
        }

        .btn-gradient {
            background: linear-gradient(90deg, var(--verde-jaguata), var(--verde-claro));
            border: none;
            color: #fff;
            font-weight: 600
        }

        .btn-gradient:hover {
            opacity: .92
        }

        .btn-outline-light {
            border-color: rgba(255, 255, 255, .6);
            color: #fff
        }

        .btn-outline-light:hover {
            background: #fff;
            color: #2b2b2b
        }

        footer {
            text-align: center;
            color: #777;
            font-size: .9rem;
            margin-top: 2rem
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>

    <!-- Contenido -->
    <main>
        <div class="welcome-box">
            <div>
                <h1 class="fw-bold"><i class="fas fa-paw me-2"></i> Editar Mascota</h1>
                <p>Actualizá los datos de tu mascota para mejorar su experiencia de paseo.</p>
            </div>
            <a href="<?= $baseFeatures; ?>/MisMascotas.php" class="btn btn-outline-light fw-semibold">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
        </div>

        <div class="card">
            <div class="card-header"><i class="fas fa-dog me-2"></i> Información de <?= $nombre ?: 'Mascota' ?></div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" id="formMascota">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nombre" value="<?= $nombre ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Raza</label>
                            <select class="form-select" name="raza" id="raza">
                                <option value="">Seleccione una raza</option>
                                <?php foreach ($razasDisponibles as $r): ?>
                                    <option value="<?= h($r) ?>" <?= $raza === $r ? 'selected' : '' ?>><?= h($r) ?></option>
                                <?php endforeach; ?>
                                <option value="Otra" <?= $raza === 'Otra' ? 'selected' : '' ?>>Otra</option>
                            </select>
                            <input type="text" class="form-control mt-2 d-none" id="raza_otra" name="raza_otra" placeholder="Especifique la raza">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Peso (kg)</label>
                            <input type="number" step="0.1" class="form-control" name="peso_kg" value="<?= h((string)$peso) ?>" placeholder="Ej: 12.5">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Tamaño</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="tamano" id="tam_peq" value="pequeno" <?= $tamano === 'pequeno' ? 'checked' : '' ?>>
                                <label class="btn btn-outline-success" for="tam_peq">Pequeño</label>

                                <input type="radio" class="btn-check" name="tamano" id="tam_med" value="mediano" <?= $tamano === 'mediano' ? 'checked' : '' ?>>
                                <label class="btn btn-outline-warning" for="tam_med">Mediano</label>

                                <input type="radio" class="btn-check" name="tamano" id="tam_gra" value="grande" <?= $tamano === 'grande' ? 'checked' : '' ?>>
                                <label class="btn btn-outline-danger" for="tam_gra">Grande</label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Edad (en meses)</label>
                            <input type="number" min="0" class="form-control" name="edad_meses" value="<?= (int)$edadMeses ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Foto</label>
                            <input type="file" class="form-control" id="foto" name="foto" accept="image/*">
                            <div class="mt-2 d-flex align-items-center gap-3">
                                <?php if ($foto): ?>
                                    <img src="<?= h($foto) ?>" class="img-preview" id="fotoActual" alt="Foto actual">
                                <?php endif; ?>
                                <img src="" class="img-preview d-none" id="fotoPreview" alt="Vista previa">
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Observaciones</label>
                            <textarea class="form-control" name="observaciones" rows="3"><?= $obs ?></textarea>
                        </div>
                    </div>

                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-gradient px-4">
                            <i class="fas fa-save me-1"></i> Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <footer><small>© <?= date('Y') ?> Jaguata — Panel del Dueño</small></footer>
    </main>

    <?php include __DIR__ . '/../../src/Templates/Footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Raza "Otra"
        const selRaza = document.getElementById('raza');
        const razaOtra = document.getElementById('raza_otra');
        const toggleOtra = () => razaOtra.classList.toggle('d-none', selRaza.value !== 'Otra');
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
            document.getElementById('fotoActual')?.classList.add('d-none');
        });

        // Alerts
        <?php if (!empty($_SESSION['success'])): ?>
            Swal.fire({
                icon: 'success',
                title: '¡Listo!',
                text: '<?= addslashes($_SESSION['success']) ?>',
                timer: 2200,
                showConfirmButton: false
            });
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if (!empty($_SESSION['error'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Ups...',
                text: '<?= addslashes($_SESSION['error']) ?>'
            });
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
    </script>
</body>

</html>