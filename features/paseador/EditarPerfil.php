<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Models/Usuario.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Models\Usuario;
use Jaguata\Helpers\Session;

AppConfig::init();

$authController = new AuthController();
$authController->checkRole('paseador');

$usuarioModel = new Usuario();
$usuarioId    = Session::get('usuario_id');
$usuario      = $usuarioModel->getById($usuarioId);
if (!$usuario) {
    echo "Error: No se encontró el usuario.";
    exit;
}

$mensaje = '';
$error   = '';

// ===== Catálogos =====
$DEPARTAMENTOS = [
    'Concepción',
    'San Pedro',
    'Cordillera',
    'Guairá',
    'Caaguazú',
    'Caazapá',
    'Itapúa',
    'Misiones',
    'Paraguarí',
    'Alto Paraná',
    'Central',
    'Ñeembucú',
    'Amambay',
    'Canindeyú',
    'Presidente Hayes',
    'Boquerón',
    'Alto Paraguay'
];

$CIUDADES = [
    'Central'      => ['Asunción', 'San Lorenzo', 'Luque', 'Lambaré', 'Capiatá', 'Fernando de la Mora', 'Ñemby', 'Mariano R. Alonso', 'Villa Elisa', 'Limpio', 'Otra'],
    'Alto Paraná'  => ['Ciudad del Este', 'Presidente Franco', 'Hernandarias', 'Minga Guazú', 'Otra'],
    'Itapúa'       => ['Encarnación', 'Hohenau', 'Bella Vista', 'Natalio', 'Fram', 'Otra'],
];

$BARRIOS = [
    'Central' => [
        'Asunción'    => ['Recoleta', 'Las Mercedes', 'Villa Morra', 'San Vicente', 'San Roque', 'Otra'],
        'San Lorenzo' => ['San Miguel', 'Barcequillo', 'Reducto', 'Otra'],
        'Luque'       => ['Luque Centro', 'Mora Cué', 'Maramburé', 'Otra'],
    ],
    'Alto Paraná' => [
        'Ciudad del Este' => ['Ciudad Nueva', 'Boquerón', 'San Isidro', 'Otra'],
    ],
    'Itapúa' => [
        'Encarnación' => ['San Isidro', 'Pacú Cuá', 'Chaipé', 'Otra'],
    ],
];

$CALLES = [
    'Central' => [
        'Asunción' => [
            'Recoleta' => ['Av. Mariscal López', 'Lillo', 'Cruz del Defensor', 'Otra'],
        ],
        'San Lorenzo' => [
            'San Miguel' => ['Avelino Martínez', 'Tte. Ettiene', 'Otra'],
        ],
    ],
    'Alto Paraná' => [
        'Ciudad del Este' => [
            'Ciudad Nueva' => ['Av. Julio César Riquelme', 'Curupayty', 'Otra'],
        ],
    ],
    'Itapúa' => [
        'Encarnación' => [
            'San Isidro' => ['Av. Artigas', 'Ruta 1', 'Perú', 'Otra'],
        ],
    ],
];

// Valores actuales
$depActual      = $usuario['departamento'] ?? '';
$ciudadActual   = $usuario['ciudad'] ?? '';
$barrioActual   = $usuario['barrio'] ?? '';
$calleActual    = $usuario['calle'] ?? '';
$fotoActual     = $usuario['foto_perfil'] ?? ($usuario['perfil_foto'] ?? '');
$fechaNacActual = $usuario['fecha_nacimiento'] ?? '';

// Zonas actuales (JSON o CSV)
$zonasActuales = [];
if (!empty($usuario['zona'])) {
    $z = $usuario['zona'];
    $decoded = json_decode($z, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $zonasActuales = array_values(array_filter(array_map('trim', $decoded)));
    } else {
        $zonasActuales = array_values(array_filter(array_map('trim', explode(',', $z))));
    }
}

// ===== Guardar =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre       = trim($_POST['nombre'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $telefono     = trim($_POST['telefono'] ?? '');
    $departamento = trim($_POST['departamento'] ?? '');
    $ciudadSel    = trim($_POST['ciudad'] ?? '');
    $ciudadOtra   = trim($_POST['ciudad_otra'] ?? '');
    $barrioSel    = trim($_POST['barrio'] ?? '');
    $barrioOtra   = trim($_POST['barrio_otra'] ?? '');
    $calleSel     = trim($_POST['calle'] ?? '');
    $calleOtra    = trim($_POST['calle_otra'] ?? '');
    $direccion    = trim($_POST['direccion'] ?? '');
    $experiencia  = trim($_POST['experiencia'] ?? '');
    $fechaNac     = trim($_POST['fecha_nacimiento'] ?? '');
    $zonaJsonPost = trim($_POST['zona_json'] ?? '[]');

    $ciudad = ($ciudadSel === 'Otra') ? $ciudadOtra : $ciudadSel;
    $barrio = ($barrioSel === 'Otra') ? $barrioOtra : $barrioSel;
    $calle  = ($calleSel  === 'Otra') ? $calleOtra  : $calleSel;

    $zonasTrabajo = json_decode($zonaJsonPost, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($zonasTrabajo)) $zonasTrabajo = [];
    $zonasTrabajo = array_values(array_unique(array_filter(array_map('trim', $zonasTrabajo))));

    if ($nombre === '' || $email === '') {
        $error = "El nombre y el email son obligatorios.";
    } elseif ($departamento === '' || !in_array($departamento, $DEPARTAMENTOS, true)) {
        $error = "Seleccione un departamento válido.";
    } elseif ($ciudad === '') {
        $error = "Seleccione o escriba una ciudad.";
    } else {
        // Foto (opcional)
        $rutaFotoNueva = null;
        if (!empty($_FILES['foto_perfil']['name'])) {
            $file = $_FILES['foto_perfil'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                $max = 2 * 1024 * 1024;
                if ($file['size'] > $max) {
                    $error = "La foto supera el tamaño máximo de 2MB.";
                } else {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime  = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);

                    $permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                    if (!isset($permitidos[$mime])) {
                        $error = "Formato de imagen no permitido. Usa JPG, PNG o WebP.";
                    } else {
                        $dirFs = realpath(__DIR__ . '/../../') . '/assets/uploads/perfiles';
                        if (!is_dir($dirFs)) {
                            @mkdir($dirFs, 0775, true);
                        }
                        $ext  = $permitidos[$mime];
                        $slug = preg_replace('/[^a-z0-9\-]+/i', '-', strtolower(pathinfo($file['name'], PATHINFO_FILENAME)));
                        $nombreArchivo = $slug . '-' . date('YmdHis') . '-' . $usuarioId . '.' . $ext;

                        $destinoFs  = $dirFs . '/' . $nombreArchivo;
                        $destinoUrl = '/assets/uploads/perfiles/' . $nombreArchivo;

                        if (move_uploaded_file($file['tmp_name'], $destinoFs)) {
                            $rutaFotoNueva = $destinoUrl;
                        } else {
                            $error = "No se pudo guardar la foto en el servidor.";
                        }
                    }
                }
            } elseif ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                $error = "Error al subir la imagen (código: {$file['error']}).";
            }
        }

        if ($error === '') {
            $data = [
                'nombre'           => $nombre,
                'email'            => $email,
                'telefono'         => $telefono,
                'direccion'        => $direccion,
                'experiencia'      => $experiencia,
                'departamento'     => $departamento,
                'ciudad'           => $ciudad,
                'barrio'           => $barrio,
                'calle'            => $calle,
                'fecha_nacimiento' => ($fechaNac !== '' ? $fechaNac : null),
                'zona'             => json_encode($zonasTrabajo, JSON_UNESCAPED_UNICODE),
            ];
            if ($rutaFotoNueva) {
                $data['foto_perfil'] = $rutaFotoNueva;
            }

            if ($usuarioModel->update($usuarioId, $data)) {
                $mensaje        = "Perfil actualizado correctamente.";
                $usuario        = $usuarioModel->getById($usuarioId);
                $depActual      = $usuario['departamento'] ?? '';
                $ciudadActual   = $usuario['ciudad'] ?? '';
                $barrioActual   = $usuario['barrio'] ?? '';
                $calleActual    = $usuario['calle'] ?? '';
                $fotoActual     = $usuario['foto_perfil'] ?? ($usuario['perfil_foto'] ?? '');
                $fechaNacActual = $usuario['fecha_nacimiento'] ?? '';
                $zonasActuales  = json_decode($usuario['zona'] ?? '[]', true) ?? [];
            } else {
                $error = "Hubo un problema al actualizar el perfil.";
            }
        }
    }
}

$titulo = "Editar Perfil (Paseador) - Jaguata";
?>

<?php include __DIR__ . '/../../src/Templates/header.php'; ?>

<div class="container mt-4">
    <h2 class="mb-3 d-flex align-items-center">
        <i class="fas fa-edit me-2"></i> Editar Perfil - Paseador
    </h2>

    <?php if ($mensaje): ?><div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-danger"><?= htmlspecialchars($error)   ?></div><?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data">
        <div class="row">
            <!-- Foto + datos básicos -->
            <div class="col-lg-4 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <?php
                            $src = $fotoActual ? (str_starts_with($fotoActual, 'http') ? $fotoActual : (BASE_URL . $fotoActual)) : (ASSETS_URL . '/images/user-placeholder.png');
                            ?>
                            <img id="previewFoto" src="<?= htmlspecialchars($src) ?>" alt="Foto de perfil"
                                class="rounded-circle" style="width: 140px; height: 140px; object-fit: cover;">
                        </div>
                        <div class="mb-3 text-start">
                            <label for="foto_perfil" class="form-label">Foto de perfil</label>
                            <input class="form-control" type="file" id="foto_perfil" name="foto_perfil"
                                accept="image/jpeg,image/png,image/webp">
                            <div class="form-text">JPG, PNG o WebP. Máx: 2 MB.</div>
                        </div>

                        <div class="mb-3 text-start">
                            <label for="nombre" class="form-label">Nombre completo</label>
                            <input type="text" class="form-control" id="nombre" name="nombre"
                                value="<?= htmlspecialchars($usuario['nombre']) ?>" required>
                        </div>

                        <div class="mb-3 text-start">
                            <label for="email" class="form-label">Correo electrónico</label>
                            <input type="email" class="form-control" id="email" name="email"
                                value="<?= htmlspecialchars($usuario['email']) ?>" required>
                        </div>

                        <div class="mb-3 text-start">
                            <label for="fecha_nacimiento" class="form-label">Fecha de nacimiento</label>
                            <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento"
                                value="<?= htmlspecialchars($fechaNacActual) ?>"
                                max="<?= date('Y-m-d') ?>">
                            <div class="form-text">Usaremos esta fecha sólo para tu perfil.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dirección + zonas -->
            <div class="col-lg-8">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="telefono" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" id="telefono" name="telefono"
                            value="<?= htmlspecialchars($usuario['telefono'] ?? '') ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="departamento" class="form-label">Departamento</label>
                        <select class="form-select" id="departamento" name="departamento" required>
                            <option value="">Seleccione…</option>
                            <?php foreach ($DEPARTAMENTOS as $dep): ?>
                                <option value="<?= htmlspecialchars($dep) ?>" <?= $depActual === $dep ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dep) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Ciudad -->
                    <div class="col-md-6 mb-3">
                        <label for="ciudad" class="form-label">Ciudad</label>
                        <select class="form-select" id="ciudad" name="ciudad" required></select>
                        <input type="text" class="form-control mt-2 d-none" id="ciudad_otra" name="ciudad_otra"
                            placeholder="Escriba su ciudad" value="<?= htmlspecialchars($ciudadActual) ?>">
                    </div>

                    <!-- Barrio -->
                    <div class="col-md-6 mb-3">
                        <label for="barrio" class="form-label">Barrio</label>
                        <select class="form-select" id="barrio" name="barrio"></select>
                        <input type="text" class="form-control mt-2 d-none" id="barrio_otra" name="barrio_otra"
                            placeholder="Escriba su barrio" value="<?= htmlspecialchars($barrioActual) ?>">
                    </div>

                    <!-- Calle -->
                    <div class="col-md-6 mb-3">
                        <label for="calle" class="form-label">Calle</label>
                        <select class="form-select" id="calle" name="calle"></select>
                        <input type="text" class="form-control mt-2 d-none" id="calle_otra" name="calle_otra"
                            placeholder="Escriba su calle" value="<?= htmlspecialchars($calleActual) ?>">
                    </div>

                    <div class="col-12 mb-3">
                        <label for="direccion" class="form-label">Referencia/Complemento</label>
                        <input type="text" class="form-control" id="direccion" name="direccion"
                            placeholder="Piso, número de casa, entre calles, etc."
                            value="<?= htmlspecialchars($usuario['direccion'] ?? '') ?>">
                    </div>

                    <!-- Zonas -->
                    <div class="col-12">
                        <div class="card mb-3">
                            <div class="card-header">
                                <strong><i class="fa-solid fa-map-location-dot me-2"></i> Zonas de trabajo</strong>
                                <small class="text-muted d-block">Seleccioná una o varias ciudades donde trabajás.</small>
                            </div>
                            <div class="card-body">
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-4">
                                        <label class="form-label" for="dep_zona">Departamento</label>
                                        <select class="form-select" id="dep_zona">
                                            <option value="">Seleccione…</option>
                                            <?php foreach ($DEPARTAMENTOS as $dep): ?>
                                                <option value="<?= htmlspecialchars($dep) ?>"><?= htmlspecialchars($dep) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label" for="ciu_zona">Ciudad</label>
                                        <select class="form-select" id="ciu_zona" disabled>
                                            <option value="">Seleccione un departamento</option>
                                        </select>
                                        <input type="text" class="form-control mt-2 d-none" id="ciu_zona_otra" placeholder="Escriba la ciudad">
                                    </div>
                                    <div class="col-md-3">
                                        <button type="button" class="btn btn-outline-primary w-100" id="btnAgregarZona">
                                            <i class="fa-solid fa-plus me-1"></i> Agregar
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-3" id="zonasSeleccionadas"></div>
                                <input type="hidden" id="zona_json" name="zona_json" value="<?= htmlspecialchars(json_encode($zonasActuales, JSON_UNESCAPED_UNICODE)) ?>">
                            </div>
                        </div>
                    </div>

                    <div class="col-12 mb-3">
                        <label for="experiencia" class="form-label">Experiencia</label>
                        <textarea class="form-control" id="experiencia" name="experiencia"><?= htmlspecialchars($usuario['experiencia'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-success">
            <i class="fas fa-save me-2"></i> Guardar Cambios
        </button>
        <a href="Perfil.php" class="btn btn-secondary ms-2">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </form>
</div>

<?php include __DIR__ . '/../../src/Templates/footer.php'; ?>

<script>
    (function() {
        // Preview foto
        const inpFoto = document.getElementById('foto_perfil');
        const imgPrev = document.getElementById('previewFoto');
        if (inpFoto) {
            inpFoto.addEventListener('change', (e) => {
                const file = e.target.files?.[0];
                if (!file) return;
                const url = URL.createObjectURL(file);
                imgPrev.src = url;
            });
        }

        // Catálogos PHP → JS
        const CIUDADES = <?= json_encode($CIUDADES, JSON_UNESCAPED_UNICODE) ?>;
        const BARRIOS = <?= json_encode($BARRIOS,  JSON_UNESCAPED_UNICODE) ?>;
        const CALLES = <?= json_encode($CALLES,   JSON_UNESCAPED_UNICODE) ?>;

        // Selects dirección
        const depSelect = document.getElementById('departamento');
        const ciudadSelect = document.getElementById('ciudad');
        const ciudadOtra = document.getElementById('ciudad_otra');

        const barrioSelect = document.getElementById('barrio');
        const barrioOtra = document.getElementById('barrio_otra');

        const calleSelect = document.getElementById('calle');
        const calleOtra = document.getElementById('calle_otra');

        const depActual = <?= json_encode($depActual,    JSON_UNESCAPED_UNICODE) ?>;
        const ciudadActual = <?= json_encode($ciudadActual, JSON_UNESCAPED_UNICODE) ?>;
        const barrioActual = <?= json_encode($barrioActual, JSON_UNESCAPED_UNICODE) ?>;
        const calleActual = <?= json_encode($calleActual,  JSON_UNESCAPED_UNICODE) ?>;

        function setSelectOptions(select, items, placeholder) {
            select.innerHTML = '';
            // 👇 CAMBIO: si no hay items, dejamos al menos "Otra"
            if (!items || items.length === 0) {
                items = ['Otra']; // 👈 CAMBIO
            }
            // Nunca deshabilitar para permitir elegir "Otra"
            select.disabled = false; // 👈 CAMBIO

            // Si queremos un placeholder visual, podemos anteponerlo opcionalmente
            // pero no hace falta; con "Otra" alcanza para permitir la carga libre
            items.forEach(v => {
                const o = document.createElement('option');
                o.value = v;
                o.textContent = v;
                select.appendChild(o);
            });
        }

        function poblarCiudades(dep) {
            // 👇 CAMBIO: si el dep no está en catálogo, ofrecer "Otra"
            const items = (dep && CIUDADES[dep]) ? CIUDADES[dep] : ['Otra']; // 👈 CAMBIO
            setSelectOptions(ciudadSelect, items, 'Seleccione un departamento primero');
            ciudadOtra.classList.add('d-none');
            ciudadOtra.required = false;

            setSelectOptions(barrioSelect, [], 'Seleccione una ciudad primero');
            barrioOtra.classList.add('d-none');
            barrioOtra.required = false;
            setSelectOptions(calleSelect, [], 'Seleccione un barrio primero');
            calleOtra.classList.add('d-none');
            calleOtra.required = false;

            // Preseleccionar si hay dato guardado
            if (ciudadActual) {
                const existe = items.includes(ciudadActual);
                if (existe) {
                    ciudadSelect.value = ciudadActual;
                    poblarBarrios(dep, ciudadActual);
                } else if (items.includes('Otra')) {
                    ciudadSelect.value = 'Otra';
                    ciudadOtra.classList.remove('d-none');
                    ciudadOtra.required = true;
                    ciudadOtra.value = ciudadActual;
                }
            }
        }

        function poblarBarrios(dep, ciudad) {
            let items = [];
            if (dep && ciudad && BARRIOS[dep] && BARRIOS[dep][ciudad]) {
                items = [...BARRIOS[dep][ciudad]];
            } else {
                items = ['Otra']; // 👈 ya hacías fallback a Otra
            }
            setSelectOptions(barrioSelect, items, 'Seleccione una ciudad primero');
            barrioOtra.classList.add('d-none');
            barrioOtra.required = false;

            setSelectOptions(calleSelect, [], 'Seleccione un barrio primero');
            calleOtra.classList.add('d-none');
            calleOtra.required = false;

            if (barrioActual) {
                if (items.includes(barrioActual)) {
                    barrioSelect.value = barrioActual;
                    poblarCalles(dep, ciudad, barrioActual);
                } else if (items.includes('Otra')) {
                    barrioSelect.value = 'Otra';
                    barrioOtra.classList.remove('d-none');
                    barrioOtra.required = true;
                    barrioOtra.value = barrioActual;
                }
            }
        }

        function poblarCalles(dep, ciudad, barrio) {
            let items = [];
            if (dep && ciudad && barrio && CALLES[dep] && CALLES[dep][ciudad] && CALLES[dep][ciudad][barrio]) {
                items = [...CALLES[dep][ciudad][barrio]];
            } else {
                items = ['Otra'];
            }
            setSelectOptions(calleSelect, items, 'Seleccione un barrio primero');
            calleOtra.classList.add('d-none');
            calleOtra.required = false;

            if (calleActual) {
                if (items.includes(calleActual)) {
                    calleSelect.value = calleActual;
                } else if (items.includes('Otra')) {
                    calleSelect.value = 'Otra';
                    calleOtra.classList.remove('d-none');
                    calleOtra.required = true;
                    calleOtra.value = calleActual;
                }
            }
        }

        depSelect.addEventListener('change', () => poblarCiudades(depSelect.value));

        ciudadSelect.addEventListener('change', () => {
            const dep = depSelect.value;
            const c = ciudadSelect.value;
            if (c === 'Otra') {
                ciudadOtra.classList.remove('d-none');
                ciudadOtra.required = true;

                setSelectOptions(barrioSelect, ['Otra']);
                barrioSelect.value = 'Otra';
                barrioOtra.classList.remove('d-none');
                barrioOtra.required = true;

                setSelectOptions(calleSelect, ['Otra']);
                calleSelect.value = 'Otra';
                calleOtra.classList.remove('d-none');
                calleOtra.required = true;
            } else {
                ciudadOtra.classList.add('d-none');
                ciudadOtra.required = false;
                poblarBarrios(dep, c);
            }
        });

        barrioSelect.addEventListener('change', () => {
            const dep = depSelect.value;
            const c = ciudadSelect.value;
            const b = barrioSelect.value;
            if (b === 'Otra') {
                barrioOtra.classList.remove('d-none');
                barrioOtra.required = true;

                setSelectOptions(calleSelect, ['Otra']);
                calleSelect.value = 'Otra';
                calleOtra.classList.remove('d-none');
                calleOtra.required = true;
            } else {
                barrioOtra.classList.add('d-none');
                barrioOtra.required = false;
                poblarCalles(dep, c, b);
            }
        });

        calleSelect.addEventListener('change', () => {
            if (calleSelect.value === 'Otra') {
                calleOtra.classList.remove('d-none');
                calleOtra.required = true;
            } else {
                calleOtra.classList.add('d-none');
                calleOtra.required = false;
            }
        });

        // Inicializar con valores actuales
        if (depActual) depSelect.value = depActual;
        poblarCiudades(depSelect.value);

        // ===== Zonas de trabajo =====
        const depZona = document.getElementById('dep_zona');
        const ciuZona = document.getElementById('ciu_zona');
        const ciuZonaOtra = document.getElementById('ciu_zona_otra');
        const btnAgregarZona = document.getElementById('btnAgregarZona');
        const contZonas = document.getElementById('zonasSeleccionadas');
        const inputZonas = document.getElementById('zona_json');

        function renderZonas() {
            let zonas = [];
            try {
                zonas = JSON.parse(inputZonas.value) || [];
            } catch (e) {
                zonas = [];
            }
            contZonas.innerHTML = '';
            if (zonas.length === 0) {
                contZonas.innerHTML = '<span class="text-muted">Sin zonas agregadas.</span>';
                return;
            }
            zonas.forEach((z, idx) => {
                const badge = document.createElement('span');
                badge.className = 'badge bg-primary me-2 mb-2';
                badge.textContent = z;

                const close = document.createElement('button');
                close.type = 'button';
                close.className = 'btn btn-sm btn-light ms-2';
                close.innerHTML = '&times;';
                close.onclick = () => {
                    zonas.splice(idx, 1);
                    inputZonas.value = JSON.stringify(zonas);
                    renderZonas();
                };

                const wrapper = document.createElement('span');
                wrapper.className = 'd-inline-flex align-items-center';
                wrapper.appendChild(badge);
                wrapper.appendChild(close);
                contZonas.appendChild(wrapper);
            });
        }

        function poblarCiudadesZona(dep) {
            ciuZona.innerHTML = '';
            ciuZonaOtra.classList.add('d-none');
            ciuZonaOtra.value = '';
            if (!dep || !CIUDADES[dep]) {
                ciuZona.disabled = false; // 👈 CAMBIO: no deshabilitar
                ciuZona.innerHTML = '<option value="Otra">Otra</option>'; // 👈 CAMBIO
                return;
            }
            ciuZona.disabled = false;
            CIUDADES[dep].forEach(c => {
                const o = document.createElement('option');
                o.value = c;
                o.textContent = c;
                ciuZona.appendChild(o);
            });
            if (!CIUDADES[dep].includes('Otra')) {
                const o = document.createElement('option');
                o.value = 'Otra';
                o.textContent = 'Otra';
                ciuZona.appendChild(o);
            }
        }

        depZona.addEventListener('change', () => poblarCiudadesZona(depZona.value));

        ciuZona.addEventListener('change', () => {
            if (ciuZona.value === 'Otra') {
                ciuZonaOtra.classList.remove('d-none');
                ciuZonaOtra.focus();
            } else {
                ciuZonaOtra.classList.add('d-none');
                ciuZonaOtra.value = '';
            }
        });

        btnAgregarZona.addEventListener('click', () => {
            let zonas = [];
            try {
                zonas = JSON.parse(inputZonas.value) || [];
            } catch (e) {
                zonas = [];
            }

            const d = depZona.value;
            if (!d) return alert('Seleccione un departamento.');

            if (ciuZona.disabled) return alert('Seleccione una ciudad.');
            let c = ciuZona.value || '';
            if (!c) return alert('Seleccione una ciudad.');
            if (c === 'Otra') {
                c = (ciuZonaOtra.value || '').trim();
                if (!c) return alert('Escriba la ciudad.');
            }
            const label = d + ' - ' + c;
            if (!zonas.includes(label)) {
                zonas.push(label);
                inputZonas.value = JSON.stringify(zonas);
                renderZonas();
            }
        });

        renderZonas();
    })();
</script>