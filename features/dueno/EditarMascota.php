<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/MascotaController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\MascotaController;
use Jaguata\Helpers\Session;

// Init + auth
AppConfig::init();
$authController = new AuthController();
$authController->checkRole('dueno');

$mascotaController = new MascotaController();

// Back seguro
$rol = Session::getUsuarioRol() ?: 'dueno';
$defaultBack = BASE_URL . "/features/{$rol}/MisMascotas.php";
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$backUrl = (is_string($referer) && str_starts_with($referer, BASE_URL)) ? $referer : $defaultBack;

// Mascota ID
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['error'] = "ID inválido";
    header("Location: MisMascotas.php");
    exit;
}

// Obtener mascota
$mascota = $mascotaController->show($id);
if (isset($mascota['error'])) {
    $_SESSION['error'] = $mascota['error'];
    header("Location: MisMascotas.php");
    exit;
}

// POST (update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Asegúrate de que tu MascotaController->Update($id) acepte opcionalmente $_FILES['foto']
    // y que use $_POST['edad_meses'] (int) como fuente de verdad para la edad.
    $result = $mascotaController->Update($id);
    if (!empty($result['success'])) {
        $_SESSION['success'] = "Mascota actualizada correctamente";
        header("Location: MisMascotas.php");
        exit;
    } else {
        $_SESSION['error'] = $result['error'] ?? "No se pudo actualizar la mascota";
    }
}

// Helpers
function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

// Valores actuales
$nombre        = h($mascota['nombre'] ?? '');
$razaActual    = h($mascota['raza'] ?? '');
$pesoActual    = $mascota['peso_kg'] ?? '';
$tamanoActual  = $mascota['tamano'] ?? ''; // pequeno|mediano|grande
$edadMesesAct  = (int)($mascota['edad_meses'] ?? 0);
$obs           = h($mascota['observaciones'] ?? '');
$fotoUrlActual = $mascota['foto_url'] ?? ''; // si tu show() devuelve una URL/relativo de foto

// Edad sugerida (años/meses) a partir de edad_meses
$edadAniosSug = $edadMesesAct > 0 ? intdiv($edadMesesAct, 12) : '';
$edadMesesSug = $edadMesesAct > 0 ? ($edadMesesAct % 12) : '';

// Lista de razas (mismo set de Agregar)
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

// Sugerencia por raza → tamaño
$SUG_RAZA = [
    'Chihuahua' => 'pequeno',
    'Yorkshire Terrier' => 'pequeno',
    'Pomerania' => 'pequeno',
    'Caniche (Poodle)' => 'mediano',
    'Beagle' => 'mediano',
    'Cocker Spaniel' => 'mediano',
    'Bulldog' => 'mediano',
    'Bulldog Francés' => 'mediano',
    'Border Collie' => 'mediano',
    'Labrador Retriever' => 'grande',
    'Golden Retriever' => 'grande',
    'Pastor Alemán' => 'grande',
    'Rottweiler' => 'grande',
    'Doberman' => 'grande',
    'Siberian Husky' => 'grande',
    'Gran Danés' => 'grande',
    'San Bernardo' => 'grande',
    'Terranova' => 'grande'
];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Editar Mascota - Jaguata</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
    <style>
        .card {
            border-radius: 12px;
        }

        .form-section-title {
            font-size: .95rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        .img-preview {
            max-width: 160px;
            max-height: 160px;
            border-radius: 10px;
            object-fit: cover;
            border: 1px solid rgba(0, 0, 0, .1);
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../../src/Templates/Header.php'; ?>
    <?php include __DIR__ . '/../../src/Templates/Navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <main class="col-md-12 col-lg-12 px-md-4">

                <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <a href="<?= h($backUrl) ?>" class="btn btn-outline-secondary btn-sm"
                            onclick="event.preventDefault(); if (history.length>1){history.back();} else {window.location.href='<?= h($backUrl) ?>';}">
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </a>
                        <h1 class="h2 mb-0"><i class="fas fa-paw me-2"></i> Editar Mascota</h1>
                    </div>
                    <div>
                        <a href="MisMascotas.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-list-ul me-1"></i> Mis Mascotas</a>
                    </div>
                </div>

                <?php if (!empty($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-1"></i> <?= $_SESSION['success'];
                                                                    unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                    </div>
                <?php endif; ?>
                <?php if (!empty($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-triangle-exclamation me-1"></i> <?= $_SESSION['error'];
                                                                            unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                    </div>
                <?php endif; ?>

                <div class="card shadow">
                    <div class="card-header bg-white">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="form-section-title">Datos de la mascota</div>
                                <div class="fw-bold"><?= $nombre !== '' ? $nombre : 'Mascota' ?></div>
                            </div>
                            <span class="badge text-bg-light">ID: <?= (int)($mascota['mascota_id'] ?? $mascota['id'] ?? $id) ?></span>
                        </div>
                    </div>

                    <div class="card-body">
                        <form method="POST" id="formMascota" enctype="multipart/form-data" novalidate>
                            <div class="row g-3">
                                <!-- Nombre -->
                                <div class="col-md-6">
                                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-signature"></i></span>
                                        <input type="text" class="form-control" name="nombre" required maxlength="50" value="<?= $nombre ?>" placeholder="Ej: Rocky">
                                    </div>
                                </div>

                                <!-- Raza -->
                                <div class="col-md-6">
                                    <label class="form-label">Raza</label>
                                    <select class="form-select" id="raza" name="raza">
                                        <option value="">Seleccione una raza</option>
                                        <?php foreach ($razasDisponibles as $r): ?>
                                            <option value="<?= h($r) ?>" <?= ($razaActual === $r ? 'selected' : '') ?>><?= h($r) ?></option>
                                        <?php endforeach; ?>
                                        <option value="Otra" <?= ($razaActual === 'Otra' ? 'selected' : '') ?>>Otra</option>
                                    </select>
                                    <input type="text" class="form-control mt-2 d-none" id="raza_otra" name="raza_otra"
                                        placeholder="Especifique la raza" value="<?= ($razaActual !== '' && !in_array($razaActual, $razasDisponibles, true) && $razaActual !== 'Otra') ? $razaActual : '' ?>">
                                </div>

                                <!-- Peso -->
                                <div class="col-md-6">
                                    <label class="form-label">Peso (kg) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-weight-scale"></i></span>
                                        <input type="number" step="0.1" min="0" class="form-control" id="peso_kg" name="peso_kg" required
                                            value="<?= h($pesoActual !== '' ? (string)$pesoActual : '') ?>" placeholder="Ej: 12.5">
                                    </div>
                                    <div class="form-text">El tamaño se determina por rangos de peso.</div>
                                </div>

                                <!-- Tamaño (solo: pequeno | mediano | grande) -->
                                <div class="col-md-6">
                                    <label class="form-label d-flex align-items-center gap-2">
                                        Tamaño
                                        <span class="badge bg-secondary-subtle text-secondary-emphasis" id="tamano_sugerido_badge" style="display:none;">Sugerido por raza</span>
                                    </label>
                                    <div class="btn-group w-100" role="group" aria-label="Tamaño">
                                        <input type="radio" class="btn-check" name="tamano" id="tam_peq" value="pequeno" <?= $tamanoActual === 'pequeno' ? 'checked' : ''; ?>>
                                        <label class="btn btn-outline-success" for="tam_peq" data-range="0–10 kg"><i class="fa-solid fa-bone me-1"></i> Pequeño</label>

                                        <input type="radio" class="btn-check" name="tamano" id="tam_med" value="mediano" <?= $tamanoActual === 'mediano' ? 'checked' : ''; ?>>
                                        <label class="btn btn-outline-warning" for="tam_med" data-range="&gt;10–25 kg"><i class="fa-solid fa-dog me-1"></i> Mediano</label>

                                        <input type="radio" class="btn-check" name="tamano" id="tam_gra" value="grande" <?= $tamanoActual === 'grande' ? 'checked' : ''; ?>>
                                        <label class="btn btn-outline-danger" for="tam_gra" data-range="&gt;25 kg"><i class="fa-solid fa-shield-dog me-1"></i> Grande</label>
                                    </div>
                                    <div class="form-text" id="tamano_range_hint">Rango: —</div>
                                </div>

                                <!-- Edad (valor + unidad -> edad_meses) -->
                                <div class="col-md-6">
                                    <label class="form-label">Edad</label>
                                    <div class="input-group">
                                        <input type="number" min="0" step="1" class="form-control" id="edad_valor" name="edad_valor"
                                            placeholder="Ej: 8" value="<?= $edadAniosSug !== '' ? h((string)$edadAniosSug) : '' ?>">
                                        <select class="form-select" id="edad_unidad" name="edad_unidad">
                                            <?php
                                            // Si tenías meses < 12, muestra meses; si no, años por defecto
                                            $unidadSug = ($edadMesesAct > 0 && $edadMesesAct < 12) ? 'meses' : 'anios';
                                            ?>
                                            <option value="meses" <?= $unidadSug === 'meses' ? 'selected' : '' ?>>Meses</option>
                                            <option value="anios" <?= $unidadSug === 'anios' ? 'selected' : '' ?>>Años</option>
                                        </select>
                                    </div>
                                    <div class="form-text" id="edad_hint">—</div>
                                    <input type="hidden" id="edad_meses" name="edad_meses" value="<?= (int)$edadMesesAct ?>">
                                </div>

                                <!-- Observaciones -->
                                <div class="col-md-6">
                                    <label class="form-label">Observaciones</label>
                                    <textarea class="form-control" name="observaciones" rows="4" placeholder="Alergias, conducta, instrucciones…"><?= $obs ?></textarea>
                                </div>

                                <!-- Foto -->
                                <div class="col-md-6">
                                    <label class="form-label">Foto</label>
                                    <input type="file" class="form-control" name="foto" id="foto" accept="image/*">
                                    <div class="form-text">Formatos: JPG/PNG/WebP. Máx. 4 MB (ajusta en backend).</div>
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <div class="d-flex align-items-center gap-3">
                                        <?php if (!empty($fotoUrlActual)): ?>
                                            <img src="<?= h($fotoUrlActual) ?>" alt="Foto actual" class="img-preview" id="fotoActual">
                                        <?php endif; ?>
                                        <img src="" alt="Vista previa" class="img-preview d-none" id="fotoPreview">
                                    </div>
                                </div>

                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <a href="<?= h($backUrl) ?>" class="btn btn-outline-secondary"
                                    onclick="event.preventDefault(); if (history.length>1){history.back();} else {window.location.href='<?= h($backUrl) ?>';}">
                                    <i class="fas fa-arrow-left me-1"></i> Volver
                                </a>
                                <a href="MisMascotas.php" class="btn btn-outline-danger"><i class="fas fa-times me-1"></i> Cancelar</a>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Guardar cambios</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="mt-3 text-color #ffff small">
                    <i class="fas fa-info-circle me-1"></i> Este formulario mantiene los nombres de campos usados en tu backend/BD.
                </div>

            </main>
        </div>
    </div>

    <?php include __DIR__ . '/../../src/Templates/Footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function() {
            // --- Raza 'Otra' ---
            const selRaza = document.getElementById('raza');
            const razaOtra = document.getElementById('raza_otra');

            function toggleRazaOtra() {
                if (selRaza.value === 'Otra') {
                    razaOtra.classList.remove('d-none');
                    razaOtra.required = true;
                } else {
                    // Si el valor actual no está en la lista (era una “otra raza”), mantenlo visible hasta que el usuario cambie
                    if (selRaza.value === '' && razaOtra.value.trim() !== '' && !razaEstaEnLista(razaOtra.value)) {
                        razaOtra.classList.remove('d-none');
                        razaOtra.required = false;
                    } else {
                        razaOtra.classList.add('d-none');
                        razaOtra.required = false;
                    }
                }
            }

            function razaEstaEnLista(v) {
                const opts = Array.from(selRaza.options).map(o => o.value);
                return opts.includes(v);
            }

            // --- Peso ↔ Tamaño ---
            const pesoInput = document.getElementById('peso_kg');
            const radios = {
                pequeno: document.getElementById('tam_peq'),
                mediano: document.getElementById('tam_med'),
                grande: document.getElementById('tam_gra'),
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
                    max: Infinity,
                    maxInc: false
                }
            };

            const SUG_RAZA = <?php echo json_encode($SUG_RAZA, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

            function pesoEnRango(peso, clave) {
                const r = RANGOS[clave];
                if (!r) return false;
                const geMin = r.minInc ? (peso >= r.min) : (peso > r.min);
                const leMax = r.maxInc ? (peso <= r.max) : (peso < r.max);
                return geMin && leMax;
            }

            function elegirPorPeso() {
                const v = parseFloat(pesoInput.value);
                if (isNaN(v)) {
                    actualizarHint();
                    return;
                }
                if (pesoEnRango(v, 'pequeno')) radios.pequeno.checked = true;
                else if (pesoEnRango(v, 'mediano')) radios.mediano.checked = true;
                else radios.grande.checked = true;

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
                if (key === 'grande') {
                    txt += '> 25 kg';
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

            // --- Edad (valor+unidad → edad_meses) ---
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
                    edadMeses.value = v * 12;
                    edadHint.textContent = `${v} años (${v*12} meses)`;
                }
            }
            edadValor.addEventListener('input', actualizarEdad);
            edadUnidad.addEventListener('change', actualizarEdad);

            // --- Foto: preview ---
            const fotoInput = document.getElementById('foto');
            const preview = document.getElementById('fotoPreview');
            const fotoActual = document.getElementById('fotoActual');

            fotoInput.addEventListener('change', function() {
                const file = this.files && this.files[0];
                if (!file) {
                    preview.classList.add('d-none');
                    return;
                }
                const url = URL.createObjectURL(file);
                preview.src = url;
                preview.classList.remove('d-none');
                if (fotoActual) fotoActual.classList.add('d-none');
            });

            // --- Submit: si raza = 'Otra', mover texto ---
            const form = document.getElementById('formMascota');
            form.addEventListener('submit', function(e) {
                if (selRaza.value === 'Otra') {
                    const v = (razaOtra.value || '').trim();
                    if (!v.length) {
                        e.preventDefault();
                        razaOtra.classList.remove('d-none');
                        razaOtra.focus();
                        alert('Por favor, especificá la raza.');
                        return;
                    }
                    // Seteamos el select con el texto ingresado para que POST->raza lo reciba
                    let opt = Array.from(selRaza.options).find(o => o.value === v);
                    if (!opt) {
                        opt = document.createElement('option');
                        opt.value = v;
                        opt.textContent = v;
                        selRaza.add(opt);
                    }
                    selRaza.value = v;
                }
                // Normalizaciones ya están al día (edad_meses y tamano por radios/peso)
            });

            // --- INIT ---
            toggleRazaOtra();
            if (!document.querySelector('input[name="tamano"]:checked')) {
                // Si ya hay peso, deduce; si no, sugiere por raza
                if (pesoInput.value) elegirPorPeso();
                else sugerirTamanoPorRaza();
            } else {
                actualizarHint();
            }
            actualizarEdad();
        })();
    </script>
</body>

</html>