<?php
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
    $mascotaController->Store();
}

/* Datos UI */
$razasDisponibles = [
    "Labrador Retriever",
    "Golden Retriever",
    "Pastor Alemán",
    "Bulldog",
    "Caniche (Poodle)",
    "Beagle",
    "Rottweiler",
    "Yorkshire Terrier",
    "Boxer",
    "Siberian Husky",
    "Doberman",
    "Shih Tzu",
    "Chihuahua",
    "Gran Danés",
    "Pomerania",
    "Border Collie",
    "Bulldog Francés",
    "Cocker Spaniel",
    "Pug",
    "Bichón Frisé",
    "San Bernardo",
    "Terranova",
    "Shiba Inu",
    "Pastor Belga Malinois",
    "Cane Corso"
];
sort($razasDisponibles);

/* Estado de inputs (persistencia tras error) */
$tamanoPost      = $_POST['tamano']       ?? '';
$pesoPost        = $_POST['peso_kg']      ?? '';
$edadValorPost   = $_POST['edad_valor']   ?? '';
$edadUnidadPost  = $_POST['edad_unidad']  ?? 'meses';

$rolMenu      = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures = BASE_URL . "/features/{$rolMenu}";
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Agregar Mascota - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --verde-jaguata: #3c6255;
            --verde-claro: #20c997;
            --gris-fondo: #f4f6f9;
            --gris-texto: #555;
            --blanco: #fff;
        }

        body {
            background-color: var(--gris-fondo);
            font-family: "Poppins", sans-serif;
            color: var(--gris-texto);
            margin: 0
        }

        /* Sidebar (fija, 250px) */
        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            color: var(--blanco);
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 1.5rem;
            box-shadow: 3px 0 10px rgba(0, 0, 0, .2);
            z-index: 1000
        }

        .sidebar .nav-link {
            color: #ccc;
            display: flex;
            align-items: center;
            gap: .8rem;
            padding: 12px 18px;
            border-radius: 8px;
            margin: 6px 10px;
            transition: .2s;
            font-size: .95rem
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: var(--verde-claro);
            color: var(--blanco);
            transform: translateX(4px)
        }

        /* Main */
        main {
            margin-left: 250px;
            padding: 2rem
        }

        @media (max-width:768px) {
            main {
                margin-left: 0;
                padding: 1.25rem
            }
        }

        .welcome-box {
            background: linear-gradient(90deg, var(--verde-claro), var(--verde-jaguata));
            color: var(--blanco);
            padding: 1.6rem 2rem;
            border-radius: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .1)
        }

        .card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, .06)
        }

        .card-header {
            background: var(--verde-jaguata);
            color: #fff;
            font-weight: 600;
            border-top-left-radius: 14px;
            border-top-right-radius: 14px
        }

        .btn-gradient {
            background: linear-gradient(90deg, var(--verde-jaguata), var(--verde-claro));
            border: none;
            color: #fff;
            font-weight: 500
        }

        .btn-gradient:hover {
            opacity: .9
        }

        .form-control,
        .form-select {
            border-radius: 8px
        }

        footer {
            text-align: center;
            color: #777;
            font-size: .85rem;
            padding: 1rem 0;
            margin-top: 2rem
        }

        /* Volver arriba */
        #btnTop {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 45px;
            height: 45px;
            border: none;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--verde-jaguata), var(--verde-claro));
            color: #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .25);
            cursor: pointer;
            display: none;
            z-index: 1000;
            transition: transform .2s, opacity .2s
        }

        #btnTop:hover {
            transform: scale(1.1);
            opacity: .9
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>

    <!-- Contenido -->
    <main>
        <!-- Header -->
        <div class="welcome-box">
            <div>
                <h1 class="fw-bold"><i class="fas fa-dog me-2"></i>Agregar Mascota</h1>
                <p>Cargá los datos básicos para gestionar paseos y fichas médicas.</p>
            </div>
            <a href="<?= $baseFeatures; ?>/MisMascotas.php" class="btn btn-outline-light fw-semibold">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
        </div>

        <!-- Mensajes de estado -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm">
                <i class="fas fa-check-circle me-2"></i><?= $_SESSION['success'];
                                                        unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm">
                <i class="fas fa-exclamation-triangle me-2"></i><?= $_SESSION['error'];
                                                                unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Formulario -->
        <div class="card">
            <div class="card-header"><i class="fas fa-info-circle me-2"></i>Información de la Mascota</div>
            <div class="card-body">
                <form method="POST" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre *</label>
                            <input type="text" class="form-control" name="nombre" required value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Raza</label>
                            <select class="form-select" id="raza" name="raza">
                                <option value="">Seleccione una raza</option>
                                <?php foreach ($razasDisponibles as $r): ?>
                                    <option value="<?= htmlspecialchars($r) ?>" <?= (($_POST['raza'] ?? '') === $r ? 'selected' : '') ?>><?= htmlspecialchars($r) ?></option>
                                <?php endforeach; ?>
                                <option value="Otra" <?= (($_POST['raza'] ?? '') === 'Otra' ? 'selected' : '') ?>>Otra</option>
                            </select>
                            <input type="text" class="form-control mt-2 d-none" id="raza_otra" name="raza_otra"
                                placeholder="Especifique la raza" value="<?= htmlspecialchars($_POST['raza_otra'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Peso (kg) *</label>
                            <input type="number" step="0.1" min="0" class="form-control" id="peso_kg" name="peso_kg"
                                required value="<?= htmlspecialchars($pesoPost) ?>">
                            <div class="form-text">Podés elegir tamaño manualmente o dejar que se calcule por peso.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Tamaño</label>
                            <div class="btn-group w-100 flex-wrap" role="group" aria-label="Seleccionar tamaño">
                                <input type="radio" class="btn-check" name="tamano" id="tam_peq" value="pequeno" <?= $tamanoPost === 'pequeno' ? 'checked' : '' ?>>
                                <label class="btn btn-outline-success" for="tam_peq">Pequeño</label>

                                <input type="radio" class="btn-check" name="tamano" id="tam_med" value="mediano" <?= $tamanoPost === 'mediano' ? 'checked' : '' ?>>
                                <label class="btn btn-outline-warning" for="tam_med">Mediano</label>

                                <input type="radio" class="btn-check" name="tamano" id="tam_gra" value="grande" <?= $tamanoPost === 'grande' ? 'checked' : '' ?>>
                                <label class="btn btn-outline-danger" for="tam_gra">Grande</label>

                                <input type="radio" class="btn-check" name="tamano" id="tam_gig" value="gigante" <?= $tamanoPost === 'gigante' ? 'checked' : '' ?>>
                                <label class="btn btn-outline-dark" for="tam_gig">Gigante</label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Edad</label>
                            <div class="input-group">
                                <input type="number" min="0" step="1" class="form-control" id="edad_valor" name="edad_valor"
                                    placeholder="Ej: 8" value="<?= htmlspecialchars($edadValorPost) ?>">
                                <select class="form-select" id="edad_unidad" name="edad_unidad">
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
                        <button type="submit" class="btn btn-gradient px-4">
                            <i class="fas fa-save me-1"></i> Guardar Mascota
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <footer><small>© <?= date('Y') ?> Jaguata — Panel del Dueño</small></footer>
    </main>

    <!-- Volver arriba -->
    <button id="btnTop" title="Volver arriba"><i class="fas fa-arrow-up"></i></button>

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