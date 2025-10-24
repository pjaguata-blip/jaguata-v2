<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;

// === Init + Auth ===
AppConfig::init();
$auth = new AuthController();
$auth->checkRole('paseador');

$rol = 'paseador';
$baseFeatures = BASE_URL . "/features/{$rol}";

// Días de la semana
$diasSemana = [
    'Lunes',
    'Martes',
    'Miércoles',
    'Jueves',
    'Viernes',
    'Sábado',
    'Domingo'
];

// Simulación: carga previa (luego podés traer de BD)
$disponibilidadActual = [
    'Lunes' => ['inicio' => '08:00', 'fin' => '12:00'],
    'Martes' => ['inicio' => '08:00', 'fin' => '12:00'],
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
        body {
            background: #f5f7fa;
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

        /* Main */
        main {
            padding: 2rem;
        }

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
            margin: 0;
        }

        .card-premium {
            border: none;
            border-radius: 14px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, .05);
            background: #fff;
            padding: 1.5rem;
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

        .form-check-input:checked {
            background-color: #3c6255;
            border-color: #3c6255;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="text-center mb-4">
                    <img src="../../assets/img/logo.png" alt="Jaguata" width="120" class="mb-3">
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

            <!-- Contenido -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">

                <div class="page-header">
                    <h2><i class="fas fa-calendar-check me-2"></i> Disponibilidad Semanal</h2>
                    <a href="Dashboard.php" class="btn btn-outline-light btn-sm"><i class="fas fa-arrow-left me-1"></i> Volver</a>
                </div>

                <div class="card-premium mb-4">
                    <h5 class="fw-semibold mb-3"><i class="fas fa-clock text-success me-2"></i> Configurá tus horarios</h5>
                    <p class="text-muted mb-4">Seleccioná los días en los que estás disponible y especificá tus horarios de inicio y fin. Los dueños solo podrán solicitarte paseos dentro de estos rangos.</p>

                    <form id="formDisponibilidad">
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Día</th>
                                        <th>Disponible</th>
                                        <th>Hora de inicio</th>
                                        <th>Hora de fin</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($diasSemana as $dia):
                                        $dispo = $disponibilidadActual[$dia] ?? null;
                                        $checked = $dispo ? 'checked' : '';
                                        $inicio = $dispo['inicio'] ?? '';
                                        $fin = $dispo['fin'] ?? '';
                                    ?>
                                        <tr>
                                            <td class="fw-semibold"><?= $dia ?></td>
                                            <td>
                                                <input type="checkbox" class="form-check-input" name="dias[]" value="<?= $dia ?>" <?= $checked ?>>
                                            </td>
                                            <td><input type="time" class="form-control" name="inicio[<?= $dia ?>]" value="<?= $inicio ?>"></td>
                                            <td><input type="time" class="form-control" name="fin[<?= $dia ?>]" value="<?= $fin ?>"></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-gradient">
                                <i class="fas fa-save me-2"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>

                <div id="alerta" class="alert d-none"></div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('formDisponibilidad').addEventListener('submit', function(e) {
            e.preventDefault();
            const alerta = document.getElementById('alerta');
            alerta.className = 'alert alert-success';
            alerta.textContent = 'Disponibilidad guardada correctamente.';
            alerta.classList.remove('d-none');
            setTimeout(() => alerta.classList.add('d-none'), 3000);
        });
    </script>
</body>

</html>