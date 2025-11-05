<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Config/AppConfig.php';
require_once __DIR__ . '/../Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;

// 🔹 Inicialización
AppConfig::init();

// 🔹 Datos del usuario y rol
$usuarioNombre = Session::getUsuarioNombre() ?? 'Usuario';
$rolUsuario = Session::getUsuarioRol() ?? 'dueno';
$baseFeatures = BASE_URL . "/features/{$rolUsuario}";
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $titulo ?? 'Panel - Jaguata'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= ASSETS_URL; ?>/css/jaguata-theme.css" rel="stylesheet">

    <style>
        body {
            background-color: #f5f7fa;
            font-family: "Poppins", sans-serif;
            overflow-x: hidden;
            margin: 0;
            padding: 0;
        }

        .layout {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            color: #f8f9fa;
            width: 240px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 1.5rem;
            box-shadow: 4px 0 12px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            transition: transform 0.3s ease-in-out;
        }

        .sidebar .nav-link {
            color: #ddd;
            display: flex;
            align-items: center;
            padding: 10px 16px;
            border-radius: 8px;
            margin: 4px 8px;
            transition: background 0.2s, transform 0.2s;
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

        /* Botón menú móvil */
        .menu-toggle {
            display: none;
            position: fixed;
            top: 16px;
            left: 16px;
            background-color: #1e1e2f;
            color: white;
            border: none;
            padding: 8px 10px;
            border-radius: 6px;
            z-index: 1100;
        }

        @media (max-width: 768px) {
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

        /* Contenido */
        main.content {
            flex-grow: 1;
            margin-left: 240px;
            background-color: #f5f7fa;
            padding: 2rem 2.5rem;
            transition: margin-left 0.3s ease;
            width: calc(100% - 240px);
        }

        @media (max-width: 768px) {
            main.content {
                margin-left: 0;
                padding: 1.5rem;
                width: 100%;
            }
        }

        /* Header / cabecera */
        .welcome-box {
            background: linear-gradient(90deg, #20c997, #3c6255);
            color: #fff;
            padding: 1.5rem 2rem;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .welcome-box h1 {
            font-weight: 600;
            margin-bottom: 4px;
        }

        .welcome-box p {
            font-size: 0.95rem;
            opacity: 0.9;
        }

        /* Tarjetas de estadísticas */
        .stat-card {
            background: #fff;
            border-radius: 12px;
            text-align: center;
            padding: 1.2rem 0.8rem;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
            border: 1px solid #e6e6e6;
        }

        .stat-card:hover {
            transform: translateY(-4px);
        }

        /* Tablas */
        .table thead {
            background: #3c6255;
            color: #fff;
        }

        .table-hover tbody tr:hover {
            background-color: #eef8f2;
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
                <li><a class="nav-link<?= strpos($_SERVER['PHP_SELF'], 'Dashboard') ? ' active' : '' ?>" href="<?= $baseFeatures; ?>/Dashboard.php"><i class="fas fa-home"></i> Inicio</a></li>
                <li><a class="nav-link<?= strpos($_SERVER['PHP_SELF'], 'MiPerfil') ? ' active' : '' ?>" href="<?= $baseFeatures; ?>/MiPerfil.php"><i class="fas fa-user"></i> Mi perfil</a></li>
                <li><a class="nav-link<?= strpos($_SERVER['PHP_SELF'], 'MisMascotas') ? ' active' : '' ?>" href="<?= $baseFeatures; ?>/MisMascotas.php"><i class="fas fa-paw"></i> Mis mascotas</a></li>
                <li><a class="nav-link<?= strpos($_SERVER['PHP_SELF'], 'SolicitarPaseo') ? ' active' : '' ?>" href="<?= $baseFeatures; ?>/SolicitarPaseo.php"><i class="fas fa-walking"></i> Reservar paseo</a></li>
                <li><a class="nav-link<?= strpos($_SERVER['PHP_SELF'], 'PaseosPendientes') ? ' active' : '' ?>" href="<?= $baseFeatures; ?>/PaseosPendientes.php"><i class="fas fa-hourglass-half"></i> Paseos pendientes</a></li>
                <li><a class="nav-link<?= strpos($_SERVER['PHP_SELF'], 'PaseosCompletados') ? ' active' : '' ?>" href="<?= $baseFeatures; ?>/PaseosCompletados.php"><i class="fas fa-check-circle"></i> Paseos completados</a></li>
                <li><a class="nav-link<?= strpos($_SERVER['PHP_SELF'], 'PaseosCancelados') ? ' active' : '' ?>" href="<?= $baseFeatures; ?>/PaseosCancelados.php"><i class="fas fa-times-circle"></i> Paseos cancelados</a></li>
                <li><a class="nav-link<?= strpos($_SERVER['PHP_SELF'], 'Notificaciones') ? ' active' : '' ?>" href="<?= $baseFeatures; ?>/Notificaciones.php"><i class="fas fa-bell"></i> Notificaciones</a></li>
                <li><a class="nav-link<?= strpos($_SERVER['PHP_SELF'], 'GastosTotales') ? ' active' : '' ?>" href="<?= $baseFeatures; ?>/GastosTotales.php"><i class="fas fa-wallet"></i> Mis gastos</a></li>
                <li><a class="nav-link text-danger" href="<?= BASE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a></li>
            </ul>
        </aside>

        <!-- Contenido -->
        <main class="content">
            <?php if (!isset($ocultarBienvenida) || !$ocultarBienvenida): ?>
                <div class="welcome-box mb-4">
                    <div>
                        <h1>¡Bienvenido/a a tu panel, <?= htmlspecialchars($usuarioNombre); ?>!</h1>
                        <p>Gestioná tus mascotas, paseos y notificaciones fácilmente 🐾</p>
                    </div>
                    <i class="fas fa-dog fa-3x opacity-75"></i>
                </div>
            <?php endif; ?>