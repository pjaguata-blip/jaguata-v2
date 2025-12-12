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

/* 🔒 Auth */
$auth = new AuthController();
$auth->checkRole('dueno');

/* POST -> crear mascota */
$mascotaController = new MascotaController();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mascotaController->store();   // 👈 en minúsculas
}

/* Datos UI */
// Cargar la lista completa de razas desde src/Data/Razas.php
$razasDisponibles = require __DIR__ . '/../../src/Data/Razas.php';
sort($razasDisponibles); // orden alfabético

/* Estado de inputs (persistencia tras error) */
$tamanoPost      = $_POST['tamano']       ?? '';
$pesoPost        = $_POST['peso_kg']      ?? '';
$edadValorPost   = $_POST['edad_valor']   ?? '';
$edadUnidadPost  = $_POST['edad_unidad']  ?? 'meses';

$rolMenu       = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures  = BASE_URL . "/features/{$rolMenu}";
$usuarioNombre = htmlspecialchars(Session::getUsuarioNombre() ?? 'Dueño/a', ENT_QUOTES, 'UTF-8');

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Agregar Mascota - Jaguata</title>

    <!-- CSS global -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <style>
        /* Volver arriba solo para esta página */
        #btnTop {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 45px;
            height: 45px;
            border: none;
            border-radius: 50%;
            background: linear-gradient(135deg, #3c6255, #20c997);
            color: #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .25);
            cursor: pointer;
            display: none;
            z-index: 1000;
            transition: transform .2s, opacity .2s;
        }

        #btnTop:hover {
            transform: scale(1.1);
            opacity: .9;
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>

    <!-- Contenido -->
    <main>
        <div class="py-4"><!-- mismo padding que otras pantallas -->

            <!-- Header -->
            <div class="header-box header-mascotas mb-1 d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="fw-bold mb-1">
                        <i class="fas fa-dog me-2"></i>Agregar Mascota
                    </h1>
                    <p class="mb-0">
                        Cargá los datos básicos para gestionar paseos y fichas médicas, <?= $usuarioNombre; ?>.
                    </p>
                </div>
                <a href="<?= $baseFeatures; ?>/MisMascotas.php" class="btn btn-outline-light fw-semibold">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
            </div>

            <!-- Mensajes de estado -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm">
                    <i class="fas fa-check-circle me-2"></i><?= $_SESSION['success']; ?>
                    <?php unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show shadow-sm">
                    <i class="fas fa-exclamation-triangle me-2"></i><?= $_SESSION['error']; ?>
                    <?php unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Formulario: ahora como section-card -->
            <div class="section-card">
                <div class="section-header">
                    <i class="fas fa-info-circle me-2"></i>Información de la Mascota
                </div>
                <div class="section-body">
                    <form method="POST" novalidate>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre *</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    name="nombre"
                                    required
                                    value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Raza</label>
                                <select class="form-select" id="raza" name="raza">
                                    <option value="">Seleccione una raza</option>

                                    <?php foreach ($razasDisponibles as $r): ?>
                                        <option value="<?= htmlspecialchars($r) ?>"
                                            <?= (($_POST['raza'] ?? '') === $r ? 'selected' : '') ?>>
                                            <?= htmlspecialchars($r) ?>
                                        </option>
                                    <?php endforeach; ?>

                                    <!-- Opción para escribir una raza personalizada -->
                                    <option value="Otra" <?= (($_POST['raza'] ?? '') === 'Otra' ? 'selected' : '') ?>>
                                        Otra
                                    </option>
                                </select>

                                <!-- Campo que aparece cuando el usuario elige "Otra" -->
                                <input
                                    type="text"
                                    class="form-control mt-2 d-none"
                                    id="raza_otra"
                                    name="raza_otra"
                                    placeholder="Especifique la raza"
                                    value="<?= htmlspecialchars($_POST['raza_otra'] ?? '') ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Peso (kg) *</label>
                                <input
                                    type="number"
                                    step="0.1"
                                    min="0"
                                    class="form-control"
                                    id="peso_kg"
                                    name="peso_kg"
                                    required
                                    value="<?= htmlspecialchars($pesoPost) ?>">
                                <div class="form-text">
                                    Podés elegir tamaño manualmente o dejar que se calcule por peso.
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Tamaño</label>
                                <div class="btn-group w-100 flex-wrap" role="group" aria-label="Seleccionar tamaño">
                                    <input
                                        type="radio"
                                        class="btn-check"
                                        name="tamano"
                                        id="tam_peq"
                                        value="pequeno"
                                        <?= $tamanoPost === 'pequeno' ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-success" for="tam_peq">Pequeño</label>

                                    <input
                                        type="radio"
                                        class="btn-check"
                                        name="tamano"
                                        id="tam_med"
                                        value="mediano"
                                        <?= $tamanoPost === 'mediano' ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-warning" for="tam_med">Mediano</label>

                                    <input
                                        type="radio"
                                        class="btn-check"
                                        name="tamano"
                                        id="tam_gra"
                                        value="grande"
                                        <?= $tamanoPost === 'grande' ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-danger" for="tam_gra">Grande</label>

                                    <input
                                        type="radio"
                                        class="btn-check"
                                        name="tamano"
                                        id="tam_gig"
                                        value="gigante"
                                        <?= $tamanoPost === 'gigante' ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-dark" for="tam_gig">Gigante</label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Edad</label>
                                <div class="input-group">
                                    <input
                                        type="number"
                                        min="0"
                                        step="1"
                                        class="form-control"
                                        id="edad_valor"
                                        name="edad_valor"
                                        placeholder="Ej: 8"
                                        value="<?= htmlspecialchars($edadValorPost) ?>">
                                    <select class="form-select" id="edad_unidad" name="edad_unidad">
                                        <option value="meses" <?= $edadUnidadPost === 'meses' ? 'selected' : '' ?>>
                                            Meses
                                        </option>
                                        <option value="anios" <?= $edadUnidadPost === 'anios' ? 'selected' : '' ?>>
                                            Años
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Observaciones</label>
                                <textarea
                                    class="form-control"
                                    name="observaciones"
                                    rows="4"><?= htmlspecialchars($_POST['observaciones'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-gradient px-4">
                                <i class="fas fa-save me-1"></i> Guardar Mascota
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <footer class="mt- text-center text-muted small">
                © <?= date('Y') ?> Jaguata — Panel del Dueño
            </footer>
        </div>
    </main>

    <!-- Volver arriba -->
    <button id="btnTop" title="Volver arriba">
        <i class="fas fa-arrow-up"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mostrar input raza_otra si corresponde
        const selRaza = document.getElementById('raza');
        const razaOtra = document.getElementById('raza_otra');

        function toggleRazaOtra() {
            razaOtra.classList.toggle('d-none', selRaza.value !== 'Otra');
        }
        selRaza.addEventListener('change', toggleRazaOtra);
        toggleRazaOtra();

        // Autocalcular tamaño por peso si no hay selección manual
        const peso = document.getElementById('peso_kg');
        const radios = {
            pequeno: document.getElementById('tam_peq'),
            mediano: document.getElementById('tam_med'),
            grande: document.getElementById('tam_gra'),
            gigante: document.getElementById('tam_gig')
        };

        function autoTamano() {
            const algunoMarcado = Object.values(radios).some(r => r.checked);
            if (algunoMarcado) return; // respetar selección manual

            const p = parseFloat(peso.value || '0');
            let sel = null;
            if (p <= 7) sel = radios.pequeno;
            else if (p <= 18) sel = radios.mediano;
            else if (p <= 35) sel = radios.grande;
            else if (p > 35) sel = radios.gigante;
            if (sel) sel.checked = true;
        }
        peso.addEventListener('input', autoTamano);

        // Botón volver arriba
        const btnTop = document.getElementById('btnTop');
        window.addEventListener('scroll', () => {
            btnTop.style.display = window.scrollY > 200 ? 'block' : 'none';
        });
        btnTop.addEventListener('click', () => window.scrollTo({
            top: 0,
            behavior: 'smooth'
        }));
    </script>
</body>

</html>