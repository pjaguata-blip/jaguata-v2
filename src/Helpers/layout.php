<?php
include __DIR__ . '/../../src/Templates/layout.php';


require_once __DIR__ . '/../Config/AppConfig.php';
require_once __DIR__ . '/../Helpers/Session.php';

use Jaguata\Helpers\Session;

// Inicializa entorno si no se llamó antes
if (!defined('BASE_URL')) {
    \Jaguata\Config\AppConfig::init();
}

$rol = $rol ?? Session::get('rol') ?? 'paseador';
$baseFeatures = BASE_URL . "/features/{$rol}";
$titulo = $titulo ?? 'Jaguata';

// Clase activa en menú
function activeMenu(string $name): string
{
    return str_contains($_SERVER['SCRIPT_NAME'], $name) ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo) ?></title>
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
            min-height: 100vh;
        }

        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            color: #f8f9fa;
            width: 240px;
            position: fixed;
            height: 100vh;
            top: 0;
            left: 0;
            padding-top: 1.5rem;
            box-shadow: 4px 0 12px rgba(0, 0, 0, .15);
            transition: transform .3s ease-in-out;
            z-index: 1000;
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

        .sidebar .nav-link.active {
            background: #3c6255;
            color: #fff;
        }

        .sidebar .nav-link:hover {
            background-color: #343454;
            color: #fff;
            transform: translateX(4px);
        }

        .sidebar .nav-link i {
            width: 22px;
            margin-right: 10px;
        }

        main.content {
            flex-grow: 1;
            margin-left: 240px;
            padding: 2.5rem;
        }

        @media(max-width:768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            main.content {
                margin-left: 0;
                width: 100%;
                padding: 1.5rem;
            }
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
        }
    </style>
</head>

<body>
    <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
    <div class="layout">
        <aside class="sidebar" id="sidebar">
            <div class="text-center mb-4">
                <img src="<?= ASSETS_URL; ?>/uploads/perfiles/logojag.png" alt="Jaguata" width="100">
                <hr class="text-light">
            </div>
            <ul class="nav flex-column gap-1 px-2">
                <li><a class="nav-link <?= activeMenu('Dashboard.php') ?>" href="<?= $baseFeatures ?>/Dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a class="nav-link <?= activeMenu('MisPaseos.php') ?>" href="<?= $baseFeatures ?>/MisPaseos.php"><i class="fas fa-list"></i> Mis Paseos</a></li>
                <li><a class="nav-link <?= activeMenu('Disponibilidad.php') ?>" href="<?= $baseFeatures ?>/Disponibilidad.php"><i class="fas fa-calendar-check"></i> Disponibilidad</a></li>
                <li><a class="nav-link <?= activeMenu('Perfil.php') ?>" href="<?= $baseFeatures ?>/Perfil.php"><i class="fas fa-user"></i> Mi Perfil</a></li>
                <li><a class="nav-link text-danger" href="<?= BASE_URL ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a></li>
            </ul>
        </aside>

        <main class="content">
            <?= $content ?? '' ?>
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