<?php

declare(strict_types=1);

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

function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function edadAmigable(?int $meses): string
{
    if ($meses === null || $meses < 0) return '—';
    if ($meses < 12) return $meses . ' mes' . ($meses === 1 ? '' : 'es');
    $a = intdiv($meses, 12);
    $m = $meses % 12;
    return $m ? "{$a} a {$m} m" : "{$a} años";
}

$mascotaCtrl = new MascotaController();
$mascotas = $mascotaCtrl->index(); // mascotas del dueño

if (count($mascotas) === 1) {
    $mid = (int)($mascotas[0]['mascota_id'] ?? $mascotas[0]['id'] ?? 0);
    if ($mid > 0) {
        header("Location: PerfilMascota.php?id={$mid}");
        exit;
    }
}

$rol = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures = BASE_URL . "/features/{$rol}";
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Mascotas - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
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

        .card-premium img {
            border-top-left-radius: 14px;
            border-top-right-radius: 14px;
            object-fit: cover;
            width: 100%;
            height: 180px;
        }

        .card-premium .card-body {
            padding: 1rem;
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

        .badge-raz {
            background: #e7f3ef;
            color: #3c6255;
            font-weight: 500;
        }
    </style>
</head>

<body>
    <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>

    <div class="layout">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="text-center mb-4">
                <img src="<?= ASSETS_URL; ?>/uploads/perfiles/logojag.png" alt="Jaguata" width="50">
                <hr class="text-light">
            </div>
            <ul class="nav flex-column gap-1 px-2">
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/Dashboard.php"><i class="fas fa-home"></i> Inicio</a></li>
                <li><a class="nav-link active" href="#"><i class="fas fa-paw"></i> Mis Mascotas</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/SolicitarPaseo.php"><i class="fas fa-walking"></i> Paseos</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/MiPerfil.php"><i class="fas fa-user"></i> Perfil</a></li>
                <li><a class="nav-link text-danger" href="<?= BASE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a></li>
            </ul>
        </aside>

        <!-- Contenido principal -->
        <main class="content">
            <div class="welcome-box mb-4">
                <div>
                    <h4>Mis Mascotas</h4>
                    <p>Gestioná y accedé a los perfiles de tus mascotas 🐾</p>
                </div>
                <a href="AgregarMascota.php" class="btn btn-light text-success fw-semibold">
                    <i class="fas fa-plus me-1"></i> Nueva Mascota
                </a>
            </div>

            <?php if (empty($mascotas)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-dog fa-4x mb-2"></i>
                    <p class="mb-3">Aún no registraste mascotas.</p>
                    <a href="AgregarMascota.php" class="btn btn-gradient btn-sm">Agregar Mascota</a>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($mascotas as $m):
                        $id = (int)($m['mascota_id'] ?? $m['id'] ?? 0);
                        $nom = h($m['nombre'] ?? 'Mascota');
                        $raz = $m['raza'] ?? null;
                        $tam = ucfirst((string)($m['tamano'] ?? '—'));
                        $edm = isset($m['edad_meses']) ? (int)$m['edad_meses'] : (isset($m['edad']) ? (int)$m['edad'] : null);
                        $foto = $m['foto_url'] ?? '';
                    ?>
                        <div class="col-sm-6 col-lg-4 col-xl-3">
                            <div class="card-premium h-100">
                                <img src="<?= $foto ? h($foto) : 'https://via.placeholder.com/400x200.png?text=Mascota' ?>" alt="Foto de <?= $nom ?>">
                                <div class="card-body">
                                    <h5 class="mb-1"><?= $nom ?></h5>
                                    <div class="mb-2">
                                        <?php if ($raz): ?><span class="badge badge-raz me-1"><?= h($raz) ?></span><?php endif; ?>
                                        <span class="badge bg-light text-dark"><?= h($tam) ?></span>
                                    </div>
                                    <div class="text-muted small mb-3">Edad: <?= edadAmigable($edm) ?></div>
                                    <div class="d-grid gap-2">
                                        <a href="PerfilMascota.php?id=<?= $id ?>" class="btn btn-gradient btn-sm"><i class="fas fa-id-card me-1"></i> Ver Perfil</a>
                                        <a href="EditarMascota.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-pen-to-square me-1"></i> Editar</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const toggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        toggle.addEventListener('click', () => sidebar.classList.toggle('show'));
    </script>
</body>

</html>