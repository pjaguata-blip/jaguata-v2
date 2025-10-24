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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resp = $mascotaCtrl->update($id);
    if (!empty($resp['success'])) {
        $_SESSION['success'] = "Mascota actualizada correctamente";
        header("Location: EditarMascota.php?id=" . $id);
        exit;
    } else {
        $_SESSION['error'] = $resp['error'] ?? "No se pudo actualizar la mascota.";
    }
}

function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$nombre = h($mascota['nombre'] ?? '');
$raza = h($mascota['raza'] ?? '');
$peso = $mascota['peso_kg'] ?? '';
$tamano = $mascota['tamano'] ?? '';
$edadMeses = (int)($mascota['edad_meses'] ?? 0);
$obs = h($mascota['observaciones'] ?? '');
$foto = $mascota['foto_url'] ?? '';
$razasDisponibles = ["Labrador Retriever", "Golden Retriever", "Pastor Alemán", "Beagle", "Bulldog", "Pug", "Chihuahua", "Doberman", "Cocker Spaniel", "Shih Tzu", "Border Collie"];
sort($razasDisponibles);

$rol = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures = BASE_URL . "/features/{$rol}";
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Mascota - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f7fa;
            font-family: "Poppins", sans-serif;
            margin: 0;
        }

        .layout {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            color: #f8f9fa;
            width: 240px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 1.5rem;
            box-shadow: 4px 0 12px rgba(0, 0, 0, .15);
            z-index: 1000;
            transition: transform .3s ease-in-out;
        }

        .sidebar .nav-link {
            color: #ddd;
            display: flex;
            align-items: center;
            padding: 10px 16px;
            border-radius: 8px;
            margin: 4px 8px;
            font-weight: 500;
        }

        .sidebar .nav-link i {
            width: 22px;
            margin-right: 10px;
        }

        .sidebar .nav-link:hover {
            background-color: #343454;
            color: #fff;
            transform: translateX(4px);
        }

        .sidebar .nav-link.active {
            background: #3c6255;
            color: #fff;
        }

        .menu-toggle {
            display: none;
            position: fixed;
            top: 16px;
            left: 16px;
            background: #1e1e2f;
            color: white;
            border: none;
            padding: 8px 10px;
            border-radius: 6px;
            z-index: 1100;
        }

        @media(max-width:768px) {
            .menu-toggle {
                display: block;
            }

            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }
        }

        main.content {
            flex-grow: 1;
            margin-left: 240px;
            padding: 2.5rem;
            width: calc(100% - 240px);
        }

        @media(max-width:768px) {
            main.content {
                margin-left: 0;
                width: 100%;
                padding: 1.5rem;
            }
        }

        .welcome-box {
            background: linear-gradient(90deg, #20c997, #3c6255);
            color: #fff;
            padding: 1.5rem 2rem;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 15px rgba(0, 0, 0, .08);
        }

        .card-premium {
            border: none;
            border-radius: 14px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, .05);
            background: #fff;
        }

        .card-premium .card-header {
            background: linear-gradient(90deg, #3c6255, #20c997);
            color: #fff;
            border-top-left-radius: 14px;
            border-top-right-radius: 14px;
            font-weight: 600;
        }

        .img-preview {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 12px;
            border: 3px solid #e0f2f1;
        }

        .btn-gradient {
            background: linear-gradient(90deg, #3c6255, #20c997);
            border: none;
            color: #fff;
            font-weight: 500;
        }

        .btn-gradient:hover {
            opacity: .9;
        }
    </style>
</head>

<body>
    <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>

    <div class="layout">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="text-center mb-4">
                <img src="../../assets/img/logo.png" alt="Jaguata" width="120" class="mb-3">
                <hr class="text-light">
            </div>
            <ul class="nav flex-column gap-1 px-2">
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/Dashboard.php"><i class="fas fa-home"></i> Inicio</a></li>
                <li><a class="nav-link active" href="<?= $baseFeatures; ?>/MisMascotas.php"><i class="fas fa-paw"></i> Mis Mascotas</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/SolicitarPaseo.php"><i class="fas fa-walking"></i> Paseos</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/MiPerfil.php"><i class="fas fa-user"></i> Perfil</a></li>
                <li><a class="nav-link text-danger" href="<?= BASE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a></li>
            </ul>
        </aside>

        <!-- Contenido -->
        <main class="content">
            <div class="welcome-box mb-4">
                <div>
                    <h4><i class="fas fa-paw me-2"></i>Editar Mascota</h4>
                    <p>Actualizá los datos de tu mascota 🐕‍🦺</p>
                </div>
                <a href="MisMascotas.php" class="btn btn-light text-success fw-semibold">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
            </div>

            <div class="card-premium">
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
                                <select class="form-select" name="raza">
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
                                <div class="btn-group w-100">
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
                                <div class="mt-2">
                                    <?php if ($foto): ?>
                                        <img src="<?= h($foto) ?>" class="img-preview" id="fotoActual">
                                    <?php endif; ?>
                                    <img src="" class="img-preview d-none" id="fotoPreview">
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Observaciones</label>
                                <textarea class="form-control" name="observaciones" rows="3"><?= $obs ?></textarea>
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-gradient px-4"><i class="fas fa-save me-1"></i> Guardar cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../../src/Templates/Footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const toggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        toggle.addEventListener('click', () => sidebar.classList.toggle('show'));

        document.getElementById('foto').addEventListener('change', e => {
            const f = e.target.files?.[0];
            if (!f) return;
            const p = document.getElementById('fotoPreview');
            p.src = URL.createObjectURL(f);
            p.classList.remove('d-none');
            document.getElementById('fotoActual')?.classList.add('d-none');
        });

        <?php if (!empty($_SESSION['success'])): ?>
            Swal.fire({
                icon: 'success',
                title: '¡Listo!',
                text: '<?= addslashes($_SESSION['success']) ?> 🐾',
                timer: 2500,
                showConfirmButton: false
            });
        <?php unset($_SESSION['success']);
        endif; ?>

        <?php if (!empty($_SESSION['error'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Ups...',
                text: '<?= addslashes($_SESSION['error']) ?>',
                confirmButtonText: 'Aceptar'
            });
        <?php unset($_SESSION['error']);
        endif; ?>
    </script>
</body>

</html>