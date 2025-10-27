<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;

AppConfig::init();
$auth = new AuthController();
$auth->checkRole('paseador');

$rol = 'paseador';
$baseFeatures = BASE_URL . "/features/{$rol}";

$diasSemana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

// Simulado: en producción, esto viene de la BD
$disponibilidadActual = [
    'Lunes' => ['inicio' => '08:00', 'fin' => '12:00'],
    'Martes' => ['inicio' => '09:00', 'fin' => '13:00'],
];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disponibilidad - Paseador | Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        /* === FUENTE Y COLORES BASE === */
        body {
            background: #f5f7fa;
            font-family: "Poppins", sans-serif;
            color: #333;
        }

        /* === SIDEBAR === */
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
            transition: background 0.2s, transform 0.2s;
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

        /* === CABECERA === */
        .page-header {
            background: linear-gradient(90deg, #20c997, #3c6255);
            color: #fff;
            padding: 1.2rem 1.5rem;
            border-radius: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, .08);
            margin-bottom: 2rem;
        }

        .page-header h2 {
            font-weight: 600;
            font-size: 1.3rem;
            margin: 0;
        }

        /* === TARJETA === */
        .card-premium {
            border: none;
            border-radius: 14px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, .05);
            background: #fff;
            padding: 1.5rem 2rem;
        }

        /* === FORMULARIO === */
        .day-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #eee;
            padding: 0.8rem 0;
        }

        .day-row:last-child {
            border-bottom: none;
        }

        .day-name {
            font-weight: 600;
            width: 110px;
        }

        .form-switch .form-check-input {
            width: 3em;
            height: 1.4em;
        }

        .form-check-input:checked {
            background-color: #3c6255;
            border-color: #3c6255;
        }

        .hour-input {
            border-radius: 8px;
            border: 1px solid #ced4da;
            padding: 0.45rem 0.6rem;
            width: 110px;
            font-size: 0.9rem;
        }

        .time-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* === BOTÓN === */
        .btn-gradient {
            background: linear-gradient(90deg, #3c6255, #20c997);
            border: none;
            color: #fff;
            font-weight: 500;
            border-radius: 10px;
            transition: 0.3s;
        }

        .btn-gradient:hover {
            opacity: 0.9;
        }

        /* === ALERTA === */
        #alerta {
            position: fixed;
            bottom: 20px;
            right: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .15);
            display: none;
            font-size: 0.95rem;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- === SIDEBAR === -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="text-center mb-4">
                    <img src="<?= ASSETS_URL; ?>/uploads/perfiles/logojag.png" alt="Jaguata" width="50">
                    <hr class="text-light">
                </div>
                <ul class="nav flex-column gap-1 px-2">
                    <li><a class="nav-link" href="<?= $baseFeatures; ?>/Dashboard.php"><i class="fas fa-home me-2"></i>Inicio</a></li>
                    <li><a class="nav-link" href="<?= $baseFeatures; ?>/MisPaseos.php"><i class="fas fa-list me-2"></i>Mis Paseos</a></li>
                    <li><a class="nav-link active" href="#"><i class="fas fa-calendar-check me-2"></i>Disponibilidad</a></li>
                    <li><a class="nav-link" href="<?= $baseFeatures; ?>/Perfil.php"><i class="fas fa-user me-2"></i>Mi Perfil</a></li>
                    <li><a class="nav-link text-danger" href="<?= BASE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar sesión</a></li>
                </ul>
            </div>

            <!-- === CONTENIDO PRINCIPAL === -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="page-header">
                    <h2><i class="fas fa-calendar-check me-2"></i> Disponibilidad Semanal</h2>
                    <a href="Dashboard.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>

                <div class="card-premium">
                    <p class="text-muted mb-4">Activá los días que estás disponible y ajustá tus horarios.</p>

                    <form id="formDisponibilidad">
                        <?php foreach ($diasSemana as $dia):
                            $dispo = $disponibilidadActual[$dia] ?? null;
                            $checked = $dispo ? 'checked' : '';
                            $inicio = $dispo['inicio'] ?? '';
                            $fin = $dispo['fin'] ?? '';
                        ?>
                            <div class="day-row">
                                <span class="day-name"><?= $dia ?></span>
                                <div class="form-switch">
                                    <input type="checkbox" class="form-check-input" name="dias[]" id="<?= $dia ?>" value="<?= $dia ?>" <?= $checked ?>>
                                </div>
                                <div class="time-group">
                                    <input type="time" name="inicio[<?= $dia ?>]" value="<?= $inicio ?>" class="hour-input">
                                    <span>–</span>
                                    <input type="time" name="fin[<?= $dia ?>]" value="<?= $fin ?>" class="hour-input">
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-gradient px-4 py-2">
                                <i class="fas fa-save me-2"></i> Guardar cambios
                            </button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <div id="alerta" class="alert alert-success">
        <i class="fas fa-check-circle me-2"></i> Disponibilidad guardada correctamente.
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('formDisponibilidad').addEventListener('submit', e => {
            e.preventDefault();
            const alerta = document.getElementById('alerta');
            alerta.style.display = 'block';
            setTimeout(() => alerta.style.display = 'none', 2500);
        });
    </script>
</body>

</html>