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

/* POST -> crear mascota */
$mascotaController = new MascotaController();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mascotaController->store();
}

/* Datos UI */
$razasDisponibles = require __DIR__ . '/../../src/Data/Razas.php';
sort($razasDisponibles);

/* Estado de inputs */
$tamanoPost     = $_POST['tamano'] ?? '';
$pesoPost       = $_POST['peso_kg'] ?? '';
$edadValorPost  = $_POST['edad_valor'] ?? '';
$edadUnidadPost = $_POST['edad_unidad'] ?? 'meses';

$rolMenu       = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures  = BASE_URL . "/features/{$rolMenu}";
$usuarioNombre = htmlspecialchars(Session::getUsuarioNombre() ?? 'Dueño/a', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Agregar Mascota - Jaguata</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <style>
        main{
            margin-left: 260px;
            min-height: 100vh;
            padding: 24px;
        }
        @media (max-width: 768px){
            main{
                margin-left: 0 !important;
                width: 100% !important;
                margin-top: 0 !important;
                padding: calc(16px + var(--topbar-h)) 16px 16px !important; 
            }
        }
        .tamano-sugerido + label {
            outline: 3px solid rgba(32, 201, 151, .45);
            box-shadow: 0 0 0 4px rgba(32, 201, 151, .2);
            transform: translateY(-1px);
        }

        .hint-ok { color: #198754; }
        .hint-warn { color: #d39e00; }
        .hint-bad { color: #dc3545; }

        #btnTop {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            border: none;
            background: linear-gradient(135deg, #3c6255, #20c997);
            color: #fff;
            display: none;
            z-index: 999;
        }
    </style>
</head>

<body>

    <?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>

    <main>
        <div class="py-2">

            <div class="header-box header-mascotas mb-3 d-flex justify-content-between">
                <div>
                    <h1 class="fw-bold">
                        <i class="fas fa-dog me-2"></i>Agregar Mascota
                    </h1>
                    <p class="mb-0">Cargá los datos de tu mascota, <?= $usuarioNombre; ?> 🐾</p>
                </div>
                <a href="<?= $baseFeatures; ?>/MisMascotas.php" class="btn btn-outline-light">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <div class="section-card">
                <div class="section-header">
                    <i class="fas fa-info-circle me-2"></i>Información de la Mascota
                </div>

                <div class="section-body">
                    <form method="POST">

                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label">Nombre *</label>
                                <input type="text" class="form-control" name="nombre" required
                                       value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Raza</label>
                                <select class="form-select" id="raza" name="raza">
                                    <option value="">Seleccione</option>
                                    <?php foreach ($razasDisponibles as $r): ?>
                                        <option value="<?= htmlspecialchars($r) ?>" <?= (($_POST['raza'] ?? '') === $r ? 'selected' : '') ?>>
                                            <?= htmlspecialchars($r) ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="Otra" <?= (($_POST['raza'] ?? '') === 'Otra' ? 'selected' : '') ?>>Otra</option>
                                </select>

                                <input type="text" id="raza_otra" name="raza_otra"
                                       class="form-control mt-2 d-none"
                                       placeholder="Especifique la raza"
                                       value="<?= htmlspecialchars($_POST['raza_otra'] ?? '') ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Peso (kg) *</label>
                                <input type="number" step="0.1" min="0.3" max="120"
                                       id="peso_kg" name="peso_kg"
                                       class="form-control" required
                                       value="<?= htmlspecialchars($pesoPost) ?>">
                                <div class="form-text">
                                    Pequeño (0–7 kg) · Mediano (7.1–18) · Grande (18.1–35) · Gigante (+35)
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Tamaño</label>
                                <div class="btn-group w-100 flex-wrap">
                                    <?php
                                    $sizes = [
                                        'pequeno' => 'Pequeño',
                                        'mediano' => 'Mediano',
                                        'grande'  => 'Grande',
                                        'gigante' => 'Gigante'
                                    ];
                                    foreach ($sizes as $k => $lbl):
                                    ?>
                                        <input type="radio" class="btn-check"
                                               name="tamano" id="tam_<?= $k ?>"
                                               value="<?= $k ?>" <?= $tamanoPost === $k ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-success" for="tam_<?= $k ?>">
                                            <?= $lbl ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>

                                <small id="tamanoAutoText" class="d-block mt-2"></small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Edad</label>
                                <div class="input-group">
                                    <input type="number" class="form-control"
                                           name="edad_valor" min="0"
                                           value="<?= htmlspecialchars($edadValorPost) ?>">
                                    <select class="form-select" name="edad_unidad">
                                        <option value="meses" <?= $edadUnidadPost === 'meses' ? 'selected' : '' ?>>Meses</option>
                                        <option value="anios" <?= $edadUnidadPost === 'anios' ? 'selected' : '' ?>>Años</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Observaciones</label>
                                <textarea class="form-control" name="observaciones" rows="4"><?= htmlspecialchars($_POST['observaciones'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <button class="btn btn-gradient px-4">
                                <i class="fas fa-save me-1"></i> Guardar Mascota
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <footer class="mt-4 text-center text-muted small">
                © <?= date('Y') ?> Jaguata — Panel del Dueño
            </footer>
        </div>
    </main>

    <button id="btnTop"><i class="fas fa-arrow-up"></i></button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        /* ===== RAZA OTRA ===== */
        const selRaza = document.getElementById('raza');
        const razaOtra = document.getElementById('raza_otra');
        function toggleOtra(){
            razaOtra.classList.toggle('d-none', selRaza.value !== 'Otra');
        }
        selRaza.addEventListener('change', toggleOtra);
        toggleOtra();

        /* ===== AUTO TAMAÑO POR PESO ===== */
        const RANGOS = [
            { k: 'pequeno', min: 0,   max: 7,   label: 'Pequeño (0–7 kg)' },
            { k: 'mediano', min: 7.1, max: 18,  label: 'Mediano (7.1–18 kg)' },
            { k: 'grande',  min: 18.1,max: 35,  label: 'Grande (18.1–35 kg)' },
            { k: 'gigante', min: 35.1,max: null,label: 'Gigante (+35 kg)' }
        ];

        const peso = document.getElementById('peso_kg');
        const radios = {};
        RANGOS.forEach(r => radios[r.k] = document.getElementById('tam_' + r.k));
        const txt = document.getElementById('tamanoAutoText');

        function limpiar() {
            Object.values(radios).forEach(r => r.classList.remove('tamano-sugerido'));
        }

        function autoTamano() {
            limpiar();
            const p = parseFloat(peso.value);
            if (isNaN(p)) {
                txt.textContent = '';
                txt.className = '';
                return;
            }

            for (const r of RANGOS) {
                if (p >= r.min && (r.max === null || p <= r.max)) {
                    // si el usuario no eligió manualmente
                    if (!Object.values(radios).some(radio => radio.checked)) {
                        radios[r.k].checked = true;
                        radios[r.k].classList.add('tamano-sugerido');
                        txt.textContent = 'Tamaño sugerido: ' + r.label;
                        txt.className = 'hint-ok';
                    }
                    return;
                }
            }
        }

        peso.addEventListener('input', autoTamano);
        autoTamano();

        /* ===== VOLVER ARRIBA ===== */
        const btnTop = document.getElementById('btnTop');
        window.addEventListener('scroll', () => {
            btnTop.style.display = window.scrollY > 200 ? 'block' : 'none';
        });
        btnTop.onclick = () => window.scrollTo({ top: 0, behavior: 'smooth' });
    </script>

</body>
</html>
