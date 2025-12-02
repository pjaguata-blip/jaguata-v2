<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Helpers\Session;

// === Inicialización y seguridad ===
AppConfig::init();
$authController = new AuthController();
$authController->checkRole('paseador');

// === Variables base ===
$rolMenu      = Session::getUsuarioRol() ?: 'paseador';
$baseFeatures = BASE_URL . "/features/{$rolMenu}";
$paseadorId   = Session::getUsuarioId();

// === Controlador ===
$paseoController = new PaseoController();

// === Datos reales ===
// Obtener solo solicitudes en estado "pendiente" para este paseador
$solicitudes = $paseoController->getSolicitudesPendientes((int)$paseadorId) ?? [];

// Helper para escapar
function h($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitudes de Paseos - Jaguata</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

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
            margin: 0;
        }

        .layout {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* === Sidebar (mismo estilo que Dashboard/Admin) === */
        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            color: var(--blanco);
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 1.5rem;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.2);
            z-index: 1000;
        }

        .sidebar .nav-link {
            color: #ccc;
            display: flex;
            align-items: center;
            gap: .8rem;
            padding: 12px 18px;
            border-radius: 8px;
            margin: 6px 10px;
            transition: all .2s ease;
            font-size: 0.95rem;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: var(--verde-claro);
            color: var(--blanco);
            transform: translateX(4px);
        }

        /* === Contenido === */
        main.content {
            flex-grow: 1;
            margin-left: 250px;
            padding: 2rem;
        }

        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            main.content {
                margin-left: 0;
                padding: 1.5rem;
            }

            .menu-toggle {
                display: block;
            }
        }

        .menu-toggle {
            display: none;
            position: fixed;
            top: 16px;
            left: 16px;
            background-color: #1e1e2f;
            color: #fff;
            border: none;
            padding: 8px 10px;
            border-radius: 6px;
            z-index: 1100;
        }

        .page-header {
            background: linear-gradient(90deg, var(--verde-claro), var(--verde-jaguata));
            color: #fff;
            padding: 1.8rem 2rem;
            border-radius: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .1);
            margin-bottom: 2rem;
        }

        .page-header h2 {
            margin: 0;
            font-size: 1.6rem;
            font-weight: 600;
        }

        .card-premium {
            border: none;
            border-radius: 14px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, .05);
            background: #fff;
            padding: 1.5rem;
        }

        .table thead {
            background-color: #f0f3f7;
        }

        .btn-success {
            background-color: #3c6255;
            border: none;
        }

        .btn-success:hover {
            background-color: #2e4d44;
        }
    </style>
</head>

<body>
    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="layout">
        <?php include __DIR__ . '/../../src/Templates/SidebarPaseador.php'; ?>

        <!-- Contenido -->
        <main class="content">
            <div class="page-header">
                <h2><i class="fas fa-envelope-open-text me-2"></i>Solicitudes de Paseos</h2>
                <a href="Dashboard.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
            </div>

            <!-- Mensajes de estado -->
            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-1"></i> <?= h($_SESSION['success']); ?>
                    <?php unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-triangle-exclamation me-1"></i> <?= h($_SESSION['error']); ?>
                    <?php unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (empty($solicitudes)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>No tienes solicitudes pendientes en este momento.
                </div>
            <?php else: ?>
                <div class="card-premium">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Mascota</th>
                                    <th>Dueño</th>
                                    <th>Fecha</th>
                                    <th>Duración</th>
                                    <th>Precio</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($solicitudes as $s):
                                    $paseoId  = (int)($s['paseo_id'] ?? 0);
                                    $duracion = $s['duracion'] ?? ($s['duracion_min'] ?? 0);
                                ?>
                                    <tr>
                                        <td><i class="fas fa-paw text-success me-1"></i><?= h($s['nombre_mascota'] ?? '-') ?></td>
                                        <td><i class="fas fa-user text-secondary me-1"></i><?= h($s['nombre_dueno'] ?? '-') ?></td>
                                        <td><?= isset($s['inicio']) ? date('d/m/Y H:i', strtotime($s['inicio'])) : '—' ?></td>
                                        <td><?= (int)$duracion ?> min</td>
                                        <td>₲<?= number_format((float)($s['precio_total'] ?? 0), 0, ',', '.') ?></td>
                                        <td class="text-end">
                                            <form action="AccionPaseo.php" method="post" class="d-inline">
                                                <input type="hidden" name="id" value="<?= $paseoId ?>">
                                                <input type="hidden" name="accion" value="confirmar">
                                                <input type="hidden" name="redirect_to" value="Solicitudes.php">
                                                <button type="submit" class="btn btn-sm btn-success"
                                                    onclick="return confirm('¿Aceptar esta solicitud?');" title="Aceptar">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            <form action="AccionPaseo.php" method="post" class="d-inline ms-1">
                                                <input type="hidden" name="id" value="<?= $paseoId ?>">
                                                <input type="hidden" name="accion" value="cancelar">
                                                <input type="hidden" name="redirect_to" value="Solicitudes.php">
                                                <button type="submit" class="btn btn-sm btn-danger"
                                                    onclick="return confirm('¿Rechazar esta solicitud?');" title="Rechazar">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const toggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar'); // definido en SidebarPaseador.php

        if (toggle && sidebar) {
            toggle.addEventListener('click', () => {
                sidebar.classList.toggle('show');
            });
        }
    </script>
</body>

</html>