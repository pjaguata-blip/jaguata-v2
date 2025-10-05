<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/MascotaController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\MascotaController;

// Inicializar configuración
AppConfig::init();

// Verificar autenticación
$authController = new AuthController();
$authController->checkRole('dueno');

// Controlador de Mascotas
$mascotaController = new MascotaController();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // TIP: en Store() podés usar $_POST['edad_meses'] como fuente de verdad.
    $mascotaController->Store();
}

// Lista de razas (puedes mover a BD si querés)
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
    "Dachshund (Salchicha)",
    "Siberian Husky",
    "Doberman",
    "Shih Tzu",
    "Chihuahua",
    "Gran Danés",
    "Pomerania",
    "Pastor Australiano",
    "Border Collie",
    "Bulldog Francés",
    "Cocker Spaniel",
    "Boston Terrier",
    "Maltés",
    "Pug",
    "Bichón Frisé",
    "Akita",
    "Boyero de Berna",
    "Collie",
    "Dálmata",
    "Springer Spaniel Inglés",
    "Galgo",
    "Habanero (Havanese)",
    "Setter Irlandés",
    "Lhasa Apso",
    "Terranova",
    "Papillón",
    "San Bernardo",
    "Samoyedo",
    "Terrier Escocés",
    "Shetland Sheepdog",
    "Weimaraner",
    "West Highland White Terrier",
    "Whippet",
    "American Staffordshire Terrier",
    "Cane Corso",
    "Pastor Belga Malinois",
    "Shiba Inu",
    "Basenji",
    "Bloodhound",
    "Bull Terrier",
    "Chow Chow",
    "Alaskan Malamute",
    "Shar-Pei",
    "Pointer",
    "Braco Húngaro (Vizsla)",
    "Ridgeback de Rodesia",
    "Bretón (Epagneul Breton)",
    "Schnauzer Miniatura",
    "Schnauzer Estándar",
    "Schnauzer Gigante",
    "Galgo Italiano",
    "Gran Pirineo",
    "Perro de Agua Portugués",
    "Boyero Australiano",
    "Jack Russell Terrier",
    "Cavalier King Charles Spaniel",
    "Pekinés",
    "Caniche Miniatura",
    "Caniche Estándar",
    "Setter Inglés",
    "American Eskimo Dog",
    "Keeshond",
    "Elkhound Noruego",
    "Airedale Terrier",
    "Fox Terrier",
    "Corgi Galés (Pembroke)",
    "Corgi Galés (Cardigan)",
    "Pinscher Miniatura",
    "Spitz Alemán",
    "Husky de Alaska (Alaskan Husky)"
];
sort($razasDisponibles);

$tamanoPost = $_POST['tamano'] ?? '';
$pesoPost   = $_POST['peso_kg'] ?? '';
$edadValorPost  = $_POST['edad_valor'] ?? '';
$edadUnidadPost = $_POST['edad_unidad'] ?? 'meses'; // default meses
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Mascota - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
</head>

<body>
    <?php include __DIR__ . '/../../src/Templates/Header.php'; ?>
    <?php include __DIR__ . '/../../src/Templates/Navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item"><a class="nav-link" href="Dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link active" href="MisMascotas.php"><i class="fas fa-paw me-2"></i> Mis Mascotas</a></li>
                        <li class="nav-item"><a class="nav-link" href="SolicitarPaseo.php"><i class="fas fa-plus-circle me-2"></i> Solicitar Paseo</a></li>
                        <li class="nav-item"><a class="nav-link" href="MisPaseos.php"><i class="fas fa-walking me-2"></i> Mis Paseos</a></li>
                        <li class="nav-item"><a class="nav-link" href="MetodosPago.php"><i class="fas fa-credit-card me-2"></i> Métodos de Pago</a></li>
                        <li class="nav-item"><a class="nav-link" href="MisPuntos.php"><i class="fas fa-star me-2"></i> Mis Puntos</a></li>
                        <li class="nav-item"><a class="nav-link" href="Perfil.php"><i class="fas fa-user me-2"></i> Mi Perfil</a></li>
                    </ul>
                </div>
            </div>

            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Agregar Mascota</h1>
                    <a href="MisMascotas.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>

                <!-- Mensajes -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= $_SESSION['success'];
                        unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= $_SESSION['error'];
                        unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['errors'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <ul class="mb-0">
                            <?php foreach ($_SESSION['errors'] as $error): ?>
                                <li><?= $error; ?></li>
                            <?php endforeach;
                            unset($_SESSION['errors']); ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Formulario -->
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="card shadow">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="fas fa-paw text-primary me-2"></i> Información de la Mascota</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" id="formMascota">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="nombre" name="nombre" required
                                                value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
                                        </div>

                                        <!-- RAZA -->
                                        <div class="col-md-6 mb-3">
                                            <label for="raza" class="form-label">Raza</label>
                                            <select class="form-select" id="raza" name="raza">
                                                <option value="">Seleccione una raza</option>
                                                <?php foreach ($razasDisponibles as $r): ?>
                                                    <option value="<?= htmlspecialchars($r) ?>" <?= (($_POST['raza'] ?? '') === $r ? 'selected' : '') ?>>
                                                        <?= htmlspecialchars($r) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                                <option value="Otra" <?= (($_POST['raza'] ?? '') === 'Otra' ? 'selected' : '') ?>>Otra</option>
                                            </select>
                                            <input type="text" class="form-control mt-2 d-none" id="raza_otra" name="raza_otra"
                                                placeholder="Especifique la raza"
                                                value="<?= htmlspecialchars($_POST['raza_otra'] ?? '') ?>">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <!-- PESO EN KG -->
                                        <div class="col-md-6 mb-3">
                                            <label for="peso_kg" class="form-label">Peso (kg) <span class="text-danger">*</span></label>
                                            <input type="number" step="0.1" min="0" class="form-control" id="peso_kg" name="peso_kg" required
                                                placeholder="Ej: 12.5"
                                                value="<?= htmlspecialchars($pesoPost) ?>">
                                            <div class="form-text">
                                                El tamaño se determina por rangos de peso.
                                            </div>
                                        </div>

                                        <!-- TAMAÑO: segmentado -->
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label d-flex align-items-center gap-2">
                                                Tamaño
                                                <span class="badge bg-secondary-subtle text-secondary-emphasis" id="tamano_sugerido_badge" style="display:none;">
                                                    Sugerido por raza
                                                </span>
                                            </label>

                                            <div class="btn-group w-100" role="group" aria-label="Tamaño">
                                                <input type="radio" class="btn-check" name="tamano" id="tam_peq" value="pequeno" <?= $tamanoPost === 'pequeno' ? 'checked' : ''; ?>>
                                                <label class="btn btn-outline-success" for="tam_peq" data-range="0–10 kg">
                                                    <i class="fa-solid fa-bone me-1"></i> Pequeño
                                                </label>

                                                <input type="radio" class="btn-check" name="tamano" id="tam_med" value="mediano" <?= $tamanoPost === 'mediano' ? 'checked' : ''; ?>>
                                                <label class="btn btn-outline-warning" for="tam_med" data-range="&gt;10–25 kg">
                                                    <i class="fa-solid fa-dog me-1"></i> Mediano
                                                </label>

                                                <input type="radio" class="btn-check" name="tamano" id="tam_gra" value="grande" <?= $tamanoPost === 'grande' ? 'checked' : ''; ?>>
                                                <label class="btn btn-outline-danger" for="tam_gra" data-range="&gt;25–45 kg">
                                                    <i class="fa-solid fa-shield-dog me-1"></i> Grande
                                                </label>

                                                <input type="radio" class="btn-check" name="tamano" id="tam_gig" value="gigante" <?= $tamanoPost === 'gigante' ? 'checked' : ''; ?>>
                                                <label class="btn btn-outline-dark" for="tam_gig" data-range="&gt;45 kg">
                                                    <i class="fa-solid fa-paw me-1"></i> Gigante
                                                </label>
                                            </div>
                                            <div class="form-text" id="tamano_range_hint">Rango: —</div>
                                        </div>
                                    </div>

                                    <!-- EDAD: valor + unidad + hidden normalizado -->
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Edad</label>
                                            <div class="input-group">
                                                <input type="number" min="0" step="1" class="form-control" id="edad_valor" name="edad_valor"
                                                    placeholder="Ej: 8" value="<?= htmlspecialchars($edadValorPost) ?>">
                                                <select class="form-select" id="edad_unidad" name="edad_unidad">
                                                    <option value="meses" <?= $edadUnidadPost === 'meses' ? 'selected' : ''; ?>>Meses</option>
                                                    <option value="anios" <?= $edadUnidadPost === 'anios' ? 'selected' : ''; ?>>Años</option>
                                                </select>
                                            </div>
                                            <div class="form-text" id="edad_hint">—</div>
                                            <input type="hidden" id="edad_meses" name="edad_meses" value="<?= htmlspecialchars($_POST['edad_meses'] ?? '') ?>">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="observaciones" class="form-label">Observaciones</label>
                                        <textarea class="form-control" id="observaciones" name="observaciones" rows="4"><?= htmlspecialchars($_POST['observaciones'] ?? '') ?></textarea>
                                    </div>

                                    <div class="d-flex justify-content-between">
                                        <a href="MisMascotas.php" class="btn btn-outline-secondary"><i class="fas fa-times me-1"></i> Cancelar</a>
                                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Guardar Mascota</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include __DIR__ . '/../../src/Templates/Footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/main.js"></script>

    <script>
        (function() {
            // --------- RAZA OTRA ---------
            const selRaza = document.getElementById('raza');
            const razaOtra = document.getElementById('raza_otra');

            function toggleRazaOtra() {
                if (selRaza.value === 'Otra') {
                    razaOtra.classList.remove('d-none');
                    razaOtra.required = true;
                } else {
                    razaOtra.classList.add('d-none');
                    razaOtra.required = false;
                }
            }

            // --------- PESO ↔ TAMAÑO ---------
            const pesoInput = document.getElementById('peso_kg');
            const radios = {
                pequeno: document.getElementById('tam_peq'),
                mediano: document.getElementById('tam_med'),
                grande: document.getElementById('tam_gra'),
                gigante: document.getElementById('tam_gig')
            };
            const rangeHint = document.getElementById('tamano_range_hint');
            const badgeSugerido = document.getElementById('tamano_sugerido_badge');

            const RANGOS = {
                pequeno: {
                    min: 0,
                    minInc: true,
                    max: 10,
                    maxInc: true
                },
                mediano: {
                    min: 10,
                    minInc: false,
                    max: 25,
                    maxInc: true
                },
                grande: {
                    min: 25,
                    minInc: false,
                    max: 45,
                    maxInc: true
                },
                gigante: {
                    min: 45,
                    minInc: false,
                    max: Infinity,
                    maxInc: false
                }
            };

            const SUG_RAZA = {
                'Chihuahua': 'pequeno',
                'Yorkshire Terrier': 'pequeno',
                'Pomerania': 'pequeno',
                'Caniche (Poodle)': 'mediano',
                'Beagle': 'mediano',
                'Cocker Spaniel': 'mediano',
                'Bulldog': 'mediano',
                'Bulldog Francés': 'mediano',
                'Border Collie': 'mediano',
                'Labrador Retriever': 'grande',
                'Golden Retriever': 'grande',
                'Pastor Alemán': 'grande',
                'Rottweiler': 'grande',
                'Doberman': 'grande',
                'Siberian Husky': 'grande',
                'Gran Danés': 'gigante',
                'San Bernardo': 'gigante',
                'Terranova': 'gigante'
            };

            function pesoEnRango(peso, def) {
                const r = RANGOS[def];
                if (!r) return false;
                const geMin = r.minInc ? (peso >= r.min) : (peso > r.min);
                const leMax = r.maxInc ? (peso <= r.max) : (peso < r.max);
                return geMin && leMax;
            }

            function elegirPorPeso() {
                const v = parseFloat(pesoInput.value);
                if (isNaN(v)) return;

                if (pesoEnRango(v, 'pequeno')) radios.pequeno.checked = true;
                else if (pesoEnRango(v, 'mediano')) radios.mediano.checked = true;
                else if (pesoEnRango(v, 'grande')) radios.grande.checked = true;
                else radios.gigante.checked = true;

                actualizarHint();
                badgeSugerido.style.display = 'none';
            }

            function actualizarHint() {
                const seleccionado = document.querySelector('input[name="tamano"]:checked');
                if (!seleccionado) {
                    rangeHint.textContent = 'Rango: —';
                    return;
                }
                const key = seleccionado.value;
                const r = RANGOS[key];
                let txt = 'Rango: ';
                if (key === 'gigante') {
                    txt += '> 45 kg';
                } else {
                    const minSymbol = r.minInc ? '≥' : '>';
                    const maxSymbol = r.maxInc ? '≤' : '<';
                    txt += `${minSymbol} ${r.min} kg y ${maxSymbol} ${r.max} kg`;
                }
                rangeHint.textContent = txt;

                const v = parseFloat(pesoInput.value);
                if (!isNaN(v)) {
                    if (!pesoEnRango(v, key)) rangeHint.classList.add('text-danger');
                    else rangeHint.classList.remove('text-danger');
                } else {
                    rangeHint.classList.remove('text-danger');
                }
            }

            function sugerirTamanoPorRaza() {
                const raza = selRaza.value;
                const sug = SUG_RAZA[raza];
                const algunChecked = !!document.querySelector('input[name="tamano"]:checked');

                if (sug && !algunChecked && !pesoInput.value) {
                    radios[sug].checked = true;
                    badgeSugerido.style.display = 'inline-block';
                    actualizarHint();
                } else {
                    badgeSugerido.style.display = 'none';
                }
            }

            Object.values(radios).forEach(r => r.addEventListener('change', actualizarHint));
            selRaza.addEventListener('change', () => {
                toggleRazaOtra();
                sugerirTamanoPorRaza();
            });
            pesoInput.addEventListener('input', elegirPorPeso);

            // --------- EDAD (valor + unidad -> meses hidden) ---------
            const edadValor = document.getElementById('edad_valor');
            const edadUnidad = document.getElementById('edad_unidad');
            const edadMeses = document.getElementById('edad_meses');
            const edadHint = document.getElementById('edad_hint');

            function actualizarEdad() {
                const v = parseInt(edadValor.value, 10);
                if (isNaN(v) || v < 0) {
                    edadMeses.value = '';
                    edadHint.textContent = '—';
                    return;
                }
                if (edadUnidad.value === 'meses') {
                    edadMeses.value = v;
                    const anios = (v / 12).toFixed(1);
                    edadHint.textContent = `${v} meses (${anios} años aprox.)`;
                } else {
                    // años
                    edadMeses.value = v * 12;
                    edadHint.textContent = `${v} años (${v*12} meses)`;
                }
            }

            edadValor.addEventListener('input', actualizarEdad);
            edadUnidad.addEventListener('change', actualizarEdad);

            // --------- INIT ---------
            toggleRazaOtra();
            sugerirTamanoPorRaza();
            if (pesoInput.value) {
                elegirPorPeso();
            } else {
                actualizarHint();
            }
            actualizarEdad();
        })();
    </script>
</body>

</html>