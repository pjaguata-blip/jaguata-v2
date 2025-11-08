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

// Simulación temporal
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
    <link href="<?= ASSETS_URL; ?>/css/jaguata-theme.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --verde-jaguata: #3c6255;
            --verde-claro: #20c997;
            --gris-fondo: #f6f9f7;
            --blanco: #ffffff;
        }

        html,
        body {
            margin: 0;
            height: 100%;
            background: var(--gris-fondo);
            font-family: "Poppins", sans-serif;
        }

        /* === LAYOUT === */
        .layout {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        /* === SIDEBAR === */
        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            color: #f8f9fa;
            width: 240px;
            flex-shrink: 0;
            box-shadow: 4px 0 12px rgba(0, 0, 0, 0.15);
            padding-top: 1rem;
        }

        .sidebar .nav-link {
            color: #ccc;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 10px 16px;
            margin: 4px 8px;
            border-radius: 8px;
            transition: all .2s;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: var(--verde-claro);
            color: var(--blanco);
        }

        /* === CONTENIDO === */
        main.content {
            flex-grow: 1;
            padding: 2.5rem;
            background: var(--gris-fondo);
        }

        /* === CABECERA === */
        .page-header {
            background: linear-gradient(90deg, var(--verde-claro), var(--verde-jaguata));
            color: #fff;
            padding: 1.2rem 2rem;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .1);
        }

        .page-header h2 {
            margin: 0;
            font-weight: 600;
        }

        /* === TARJETA === */
        .card-premium {
            border: none;
            border-radius: 18px;
            background: var(--blanco);
            box-shadow: 0 8px 25px rgba(0, 0, 0, .05);
            padding: 2rem;
        }

        .day-row {
            display: grid;
            grid-template-columns: 130px 100px 1fr;
            align-items: center;
            border-bottom: 1px solid #eaeaea;
            padding: 0.8rem 0;
            gap: 1rem;
        }

        .day-name {
            font-weight: 600;
            font-size: 1rem;
            color: var(--verde-jaguata);
        }

        .form-switch .form-check-input {
            width: 3.2em;
            height: 1.5em;
        }

        .form-check-input:checked {
            background-color: var(--verde-claro);
            border-color: var(--verde-claro);
        }

        .time-group input[type="time"] {
            border-radius: 8px;
            border: 1px solid #d0d0d0;
            padding: 0.4rem 0.6rem;
            font-size: 0.9rem;
            width: 115px;
        }

        .time-group span {
            color: #888;
        }

        .time-group.disabled input {
            opacity: 0.4;
            pointer-events: none;
        }

        /* === BOTÓN === */
        .btn-gradient {
            background: linear-gradient(90deg, var(--verde-jaguata), var(--verde-claro));
            border: none;
            color: #fff;
            font-weight: 500;
            border-radius: 10px;
            transition: 0.3s;
        }

        .btn-gradient:hover {
            transform: translateY(-1px);
            opacity: 0.95;
        }

        /* === ALERTA flotante === */
        #alerta {
            position: fixed;
            bottom: 25px;
            right: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .2);
            display: none;
            font-size: 0.95rem;
        }

        /* === FOOTER === */
        footer {
            background-color: var(--verde-jaguata);
            color: #fff;
            text-align: center;
            padding: 1.2rem 0;
            width: 100%;
            margin-top: 3rem;
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../../src/Templates/Navbar.php'; ?>

    <div class="layout">
        <!-- === SIDEBAR === -->
        <?php include __DIR__ . '/../../src/Templates/SidebarPaseador.php'; ?>


        <!-- === CONTENIDO PRINCIPAL === -->
        <main class="content">
            <div class="page-header">
                <h2><i class="fas fa-calendar-check me-2"></i> Disponibilidad Semanal</h2>
                <a href="Dashboard.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
            </div>

            <div class="card-premium">
                <p class="text-muted mb-4">
                    Activá los días que estás disponible y definí tus horarios.
                    <br><small class="text-secondary">Podés copiar tus horarios de un día a otro fácilmente.</small>
                </p>

                <form id="formDisponibilidad">
                    <?php foreach ($diasSemana as $dia):
                        $dispo = $disponibilidadActual[$dia] ?? null;
                        $checked = $dispo ? 'checked' : '';
                        $inicio = $dispo['inicio'] ?? '';
                        $fin = $dispo['fin'] ?? '';
                    ?>
                        <div class="day-row">
                            <div class="day-name"><?= $dia ?></div>
                            <div class="form-switch">
                                <input type="checkbox" class="form-check-input toggle-dia" data-dia="<?= $dia ?>" <?= $checked ?>>
                            </div>
                            <div class="time-group <?= $checked ? '' : 'disabled' ?>">
                                <input type="time" class="hora-inicio" value="<?= $inicio ?>">
                                <span>–</span>
                                <input type="time" class="hora-fin" value="<?= $fin ?>">
                                <button type="button" class="copy-btn" title="Copiar horario a todos"><i class="fas fa-copy"></i></button>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-gradient px-4 py-2">
                            <i class="fas fa-save me-2"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <footer>© <?= date('Y') ?> Jaguata — Todos los derechos reservados.</footer>

    <div id="alerta" class="alert alert-success">
        <i class="fas fa-check-circle me-2"></i> Disponibilidad guardada correctamente.
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const alerta = document.getElementById('alerta');
        const form = document.getElementById('formDisponibilidad');

        document.querySelectorAll('.toggle-dia').forEach(toggle => {
            toggle.addEventListener('change', e => {
                const grupo = e.target.closest('.day-row').querySelector('.time-group');
                grupo.classList.toggle('disabled', !e.target.checked);
            });
        });

        document.querySelectorAll('.copy-btn').forEach(btn => {
            btn.addEventListener('click', e => {
                const row = e.target.closest('.day-row');
                const inicio = row.querySelector('.hora-inicio').value;
                const fin = row.querySelector('.hora-fin').value;

                if (!inicio || !fin) return alert("Completá los horarios antes de copiar.");

                document.querySelectorAll('.day-row').forEach(r => {
                    const activo = r.querySelector('.toggle-dia').checked;
                    if (activo) {
                        r.querySelector('.hora-inicio').value = inicio;
                        r.querySelector('.hora-fin').value = fin;
                    }
                });
            });
        });

        form.addEventListener('submit', e => {
            e.preventDefault();
            alerta.style.display = 'block';
            setTimeout(() => alerta.style.display = 'none', 2500);
        });
    </script>
</body>

</html>