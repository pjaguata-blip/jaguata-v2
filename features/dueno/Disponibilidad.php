<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseadorController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseadorController;
use Jaguata\Helpers\Session;

// === Init + auth ===
AppConfig::init();
$auth = new AuthController();
$auth->checkRole('paseador');

$paseadorCtrl = new PaseadorController();
$paseadorId = (int)Session::get('usuario_id');

// ✅ Si hay envío de formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dias = $_POST['dias'] ?? [];
    $horaInicio = $_POST['hora_inicio'] ?? '';
    $horaFin = $_POST['hora_fin'] ?? '';
    $resp = $paseadorCtrl->actualizarDisponibilidad($paseadorId, $dias, $horaInicio, $horaFin);

    if (!empty($resp['success'])) {
        $_SESSION['success'] = 'Disponibilidad actualizada correctamente 🐾';
        header("Location: Disponibilidad.php");
        exit;
    } else {
        $_SESSION['error'] = $resp['error'] ?? 'No se pudo actualizar la disponibilidad.';
    }
}

// ✅ Datos actuales del paseador
$disponibilidad = $paseadorCtrl->obtenerDisponibilidad($paseadorId);
$diasActivos = $disponibilidad['dias'] ?? [];
$inicio = $disponibilidad['hora_inicio'] ?? '08:00';
$fin = $disponibilidad['hora_fin'] ?? '18:00';

$rol = 'paseador';
$baseFeatures = BASE_URL . "/features/{$rol}";

// Helper seguro
function h(mixed $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disponibilidad - Jaguata</title>
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

        .btn-gradient {
            background: linear-gradient(90deg, #3c6255, #20c997);
            border: none;
            color: #fff;
            font-weight: 500;
        }

        .btn-gradient:hover {
            opacity: .9;
        }

        .form-check-label {
            font-weight: 500;
            color: #333;
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
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/Dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a class="nav-link active" href="#"><i class="fas fa-calendar-check"></i> Disponibilidad</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/MisPaseos.php"><i class="fas fa-list"></i> Mis Paseos</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/Perfil.php"><i class="fas fa-user"></i> Perfil</a></li>
                <li><a class="nav-link text-danger" href="<?= BASE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a></li>
            </ul>
        </aside>

        <!-- Contenido -->
        <main class="content">
            <div class="welcome-box mb-4">
                <div>
                    <h4>Mi Disponibilidad</h4>
                    <p>Definí los días y horarios en los que podés ofrecer paseos 🐾</p>
                </div>
                <i class="fas fa-calendar-alt fa-3x opacity-75"></i>
            </div>

            <div class="card-premium p-4">
                <form method="POST">
                    <div class="mb-4">
                        <h5 class="fw-semibold"><i class="fas fa-calendar-days me-2 text-success"></i>Días disponibles</h5>
                        <div class="row">
                            <?php
                            $diasSemana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
                            foreach ($diasSemana as $d): ?>
                                <div class="col-6 col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="dias[]" value="<?= h($d) ?>"
                                            id="dia_<?= h($d) ?>" <?= in_array($d, $diasActivos) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="dia_<?= h($d) ?>"><?= h($d) ?></label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="fas fa-clock me-2 text-success"></i>Hora de inicio</label>
                            <input type="time" name="hora_inicio" class="form-control" value="<?= h($inicio) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="fas fa-clock me-2 text-success"></i>Hora de finalización</label>
                            <input type="time" name="hora_fin" class="form-control" value="<?= h($fin) ?>" required>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-gradient px-4 py-2">
                            <i class="fas fa-save me-1"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const toggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        toggle.addEventListener('click', () => sidebar.classList.toggle('show'));

        <?php if (!empty($_SESSION['success'])): ?>
            Swal.fire({
                icon: 'success',
                title: '¡Listo!',
                text: '<?= addslashes($_SESSION['success']) ?>',
                showConfirmButton: false,
                timer: 2500,
                background: '#f6f9f7'
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