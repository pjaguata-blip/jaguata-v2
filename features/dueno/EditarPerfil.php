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

// Solo dueño
$authController = new AuthController();
$authController->checkRole('dueno');

$usuarioModel = new Usuario();
$usuarioId    = Session::get('usuario_id');
$usuario      = $usuarioModel->getById($usuarioId);

if (!$usuario) {
    http_response_code(404);
    echo "Error: No se encontró el usuario.";
    exit;
}

$mensaje = '';
$error   = '';

/* =======================
   Catálogos Paraguay
   ======================= */
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
    'Cordillera'   => ['Caacupé', 'Atyrá', 'Altos', 'Otra'],
    'Paraguarí'    => ['Paraguarí', 'Carapeguá', 'Ybycuí', 'Otra'],
    'San Pedro'    => ['San Pedro de Ycuamandiyú', 'Santa Rosa del Aguaray', 'Lima', 'Otra'],
    'Concepción'   => ['Concepción', 'Horqueta', 'Belén', 'Otra'],
    'Guairá'       => ['Villarrica', 'Independencia', 'Otra'],
    'Caaguazú'     => ['Coronel Oviedo', 'Caaguazú', 'Otra'],
    'Caazapá'      => ['Caazapá', 'San Juan Nepomuceno', 'Otra'],
    'Misiones'     => ['San Juan Bautista', 'Ayolas', 'Otra'],
    'Ñeembucú'     => ['Pilar', 'Otra'],
    'Amambay'      => ['Pedro Juan Caballero', 'Zanja Pytá', 'Otra'],
    'Canindeyú'    => ['Salto del Guairá', 'Katueté', 'Otra'],
    'Presidente Hayes' => ['Villa Hayes', 'Pozo Colorado', 'Otra'],
    'Boquerón'     => ['Filadelfia', 'Loma Plata', 'Neuland', 'Otra'],
    'Alto Paraguay' => ['Fuerte Olimpo', 'Bahía Negra', 'Otra'],
];

$BARRIOS = [
    'Central' => [
        'Asunción'     => ['Recoleta', 'Las Mercedes', 'Villa Morra', 'San Vicente', 'San Roque', 'Otra'],
        'San Lorenzo'  => ['San Miguel', 'Barcequillo', 'Reducto', 'Otra'],
        'Luque'        => ['Luque Centro', 'Mora Cué', 'Maramburé', 'Otra'],
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
            'Villa Morra' => ['España', 'Charles de Gaulle', 'Santa Teresa', 'Otra'],
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

/* =======================
   Valores actuales
   ======================= */
$nombre     = $usuario['nombre'] ?? '';
$email      = $usuario['email'] ?? '';
$telefono   = $usuario['telefono'] ?? '';
$direccion  = $usuario['direccion'] ?? '';
$depActual  = $usuario['departamento'] ?? '';
$ciudadAct  = $usuario['ciudad'] ?? '';
$barrioAct  = $usuario['barrio'] ?? '';
$calleAct   = $usuario['calle'] ?? '';
$fechaNac   = $usuario['fecha_nacimiento'] ?? '';
$fotoActual = $usuario['foto_perfil'] ?? ($usuario['perfil_foto'] ?? '');

/* =======================
   POST: Guardar cambios
   ======================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre     = trim($_POST['nombre'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $telefono   = trim($_POST['telefono'] ?? '');
    $direccion  = trim($_POST['direccion'] ?? '');

    $departamento = trim($_POST['departamento'] ?? '');
    $ciudadSel    = trim($_POST['ciudad'] ?? '');
    $ciudadOtra   = trim($_POST['ciudad_otra'] ?? '');
    $barrioSel    = trim($_POST['barrio'] ?? '');
    $barrioOtra   = trim($_POST['barrio_otra'] ?? '');
    $calleSel     = trim($_POST['calle'] ?? '');
    $calleOtra    = trim($_POST['calle_otra'] ?? '');
    $fechaNac     = trim($_POST['fecha_nacimiento'] ?? '');

    // Resolver ciudad/barrio/calle
    $ciudad = ($ciudadSel === 'Otra') ? $ciudadOtra : $ciudadSel;
    $barrio = ($barrioSel === 'Otra') ? $barrioOtra : $barrioSel;
    $calle  = ($calleSel  === 'Otra') ? $calleOtra  : $calleSel;

    // Validaciones básicas
    if ($nombre === '' || $email === '') {
        $error = "El nombre y el email son obligatorios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "El email no es válido.";
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
                    $error = "La foto supera 2MB.";
                } else {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime  = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);
                    $permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                    if (!isset($permitidos[$mime])) {
                        $error = "Formato de imagen no permitido (JPG/PNG/WebP).";
                    } else {
                        $dirFs = realpath(__DIR__ . '/../../') . '/assets/uploads/perfiles';
                        if (!is_dir($dirFs)) {
                            @mkdir($dirFs, 0775, true);
                        }
                        $ext   = $permitidos[$mime];
                        $slug  = preg_replace('/[^a-z0-9\-]+/i', '-', strtolower(pathinfo($file['name'], PATHINFO_FILENAME)));
                        $fname = 'u' . $usuarioId . '-' . date('YmdHis') . '.' . $ext;
                        $destFs  = $dirFs . '/' . $fname;
                        $destUrl = '/assets/uploads/perfiles/' . $fname;

                        if (move_uploaded_file($file['tmp_name'], $destFs)) {
                            $rutaFotoNueva = $destUrl;
                        } else {
                            $error = "No se pudo guardar la foto.";
                        }
                    }
                }
            } elseif ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                $error = "Error al subir la imagen (código: " . $file['error'] . ").";
            }
        }

        if ($error === '') {
            $data = [
                'nombre'           => $nombre,
                'email'            => $email,
                'telefono'         => $telefono,
                'direccion'        => $direccion,
                'departamento'     => $departamento,
                'ciudad'           => $ciudad,
                'barrio'           => $barrio,
                'calle'            => $calle,
                'fecha_nacimiento' => ($fechaNac !== '' ? $fechaNac : null),
            ];
            if ($rutaFotoNueva) {
                $data['foto_perfil'] = $rutaFotoNueva;
            }

            if ($usuarioModel->updateUsuario((int)$usuarioId, $data)) {
                $mensaje = "Perfil actualizado correctamente.";
                $usuario = $usuarioModel->getById($usuarioId);

                // refrescar actuales
                $depActual  = $usuario['departamento'] ?? '';
                $ciudadAct  = $usuario['ciudad'] ?? '';
                $barrioAct  = $usuario['barrio'] ?? '';
                $calleAct   = $usuario['calle'] ?? '';
                $fechaNac   = $usuario['fecha_nacimiento'] ?? '';
                $fotoActual = $usuario['foto_perfil'] ?? ($usuario['perfil_foto'] ?? '');
            } else {
                $error = "Hubo un problema al actualizar el perfil.";
            }
        }
    }
}

$titulo = "Editar Perfil (Dueño) - Jaguata";
$inicioUrl = AppConfig::getBaseUrl();
$panelUrl  = AppConfig::getBaseUrl() . '/features/dueno/Dashboard.php';
?>

<?php include __DIR__ . '/../../src/Templates/header.php'; ?>

<div class="container py-4">
    <div class="d-flex justify-content-end gap-2 mb-3">
        <a href="<?= htmlspecialchars($inicioUrl) ?>" class="btn btn-outline-secondary">
            <i class="fa-solid fa-house me-1"></i> Inicio
        </a>
        <a href="<?= htmlspecialchars($panelUrl) ?>" class="btn btn-outline-primary">
            <i class="fa-solid fa-gauge-high me-1"></i> Panel
        </a>
    </div>

    <h2 class="mb-3 d-flex align-items-center">
        <i class="fas fa-edit me-2"></i> Editar Perfil - Dueño
    </h2>

    <?php if ($mensaje): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($mensaje) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data">
        <div class="row">
            <!-- Columna izquierda: foto + básicos -->
            <div class="col-lg-4 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <?php
                        $src = $fotoActual
                            ? (str_starts_with($fotoActual, 'http') ? $fotoActual : (BASE_URL . $fotoActual))
                            : (ASSETS_URL . '/images/user-placeholder.png');
                        ?>
                        <div class="mb-3">
                            <img id="previewFoto" src="<?= htmlspecialchars($src) ?>" alt="Foto de perfil"
                                class="rounded-circle" style="width:140px;height:140px;object-fit:cover;">
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
                                value="<?= htmlspecialchars($nombre) ?>" required>
                        </div>

                        <div class="mb-3 text-start">
                            <label for="email" class="form-label">Correo electrónico</label>
                            <input type="email" class="form-control" id="email" name="email"
                                value="<?= htmlspecialchars($email) ?>" required>
                        </div>

                        <div class="mb-3 text-start">
                            <label for="fecha_nacimiento" class="form-label">Fecha de nacimiento</label>
                            <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento"
                                value="<?= htmlspecialchars($fechaNac) ?>" max="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna derecha: contacto + dirección -->
            <div class="col-lg-8">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="telefono" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" id="telefono" name="telefono"
                            value="<?= htmlspecialchars($telefono) ?>" placeholder="0981-123-456">
                    </div>

                    <!-- Departamento -->
                    <div class="col-md-6 mb-3">
                        <label for="departamento" class="form-label">Departamento</label>
                        <select class="form-select" id="departamento" name="departamento" required>
                            <option value="">Seleccione…</option>
                            <?php foreach ($DEPARTAMENTOS as $dep): ?>
                                <option value="<?= htmlspecialchars($dep) ?>" <?= $dep === $depActual ? 'selected' : '' ?>>
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
                            placeholder="Escriba su ciudad" value="<?= htmlspecialchars($ciudadAct) ?>">
                    </div>

                    <!-- Barrio -->
                    <div class="col-md-6 mb-3">
                        <label for="barrio" class="form-label">Barrio</label>
                        <select class="form-select" id="barrio" name="barrio"></select>
                        <input type="text" class="form-control mt-2 d-none" id="barrio_otra" name="barrio_otra"
                            placeholder="Escriba su barrio" value="<?= htmlspecialchars($barrioAct) ?>">
                    </div>

                    <!-- Calle -->
                    <div class="col-md-6 mb-3">
                        <label for="calle" class="form-label">Calle</label>
                        <select class="form-select" id="calle" name="calle"></select>
                        <input type="text" class="form-control mt-2 d-none" id="calle_otra" name="calle_otra"
                            placeholder="Escriba su calle" value="<?= htmlspecialchars($calleAct) ?>">
                    </div>

                    <!-- Referencia -->
                    <div class="col-md-6 mb-3">
                        <label for="direccion" class="form-label">Referencia/Complemento</label>
                        <input type="text" class="form-control" id="direccion" name="direccion"
                            placeholder="Piso, número de casa, entre calles, etc."
                            value="<?= htmlspecialchars($direccion) ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-2">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save me-2"></i> Guardar Cambios
            </button>
            <a href="Perfil.php" class="btn btn-secondary ms-2">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../src/Templates/footer.php'; ?>

<script>
    (function() {
        // Preview foto
        const inp = document.getElementById('foto_perfil');
        const img = document.getElementById('previewFoto');
        if (inp) {
            inp.addEventListener('change', e => {
                const f = e.target.files?.[0];
                if (!f) return;
                img.src = URL.createObjectURL(f);
            });
        }

        // Catálogos desde PHP
        const CIUDADES = <?= json_encode($CIUDADES, JSON_UNESCAPED_UNICODE) ?>;
        const BARRIOS = <?= json_encode($BARRIOS,  JSON_UNESCAPED_UNICODE) ?>;
        const CALLES = <?= json_encode($CALLES,   JSON_UNESCAPED_UNICODE) ?>;

        const depSel = document.getElementById('departamento');
        const ciuSel = document.getElementById('ciudad');
        const ciuOtra = document.getElementById('ciudad_otra');

        const barSel = document.getElementById('barrio');
        const barOtra = document.getElementById('barrio_otra');

        const calSel = document.getElementById('calle');
        const calOtra = document.getElementById('calle_otra');

        const depActual = <?= json_encode($depActual, JSON_UNESCAPED_UNICODE) ?>;
        const ciudadAct = <?= json_encode($ciudadAct, JSON_UNESCAPED_UNICODE) ?>;
        const barrioAct = <?= json_encode($barrioAct, JSON_UNESCAPED_UNICODE) ?>;
        const calleAct = <?= json_encode($calleAct,  JSON_UNESCAPED_UNICODE) ?>;

        function setOptions(sel, arr, placeholder) {
            sel.innerHTML = '';
            if (!arr || arr.length === 0) {
                sel.disabled = true;
                const o = document.createElement('option');
                o.value = '';
                o.textContent = placeholder || 'Sin datos';
                sel.appendChild(o);
                return;
            }
            sel.disabled = false;
            arr.forEach(v => {
                const o = document.createElement('option');
                o.value = v;
                o.textContent = v;
                sel.appendChild(o);
            });
        }

        function poblarCiudades(dep) {
            const items = (dep && CIUDADES[dep]) ? CIUDADES[dep] : [];
            setOptions(ciuSel, items, 'Seleccione un departamento');
            ciuOtra.classList.add('d-none');
            ciuOtra.required = false;

            setOptions(barSel, [], 'Seleccione ciudad');
            barOtra.classList.add('d-none');
            barOtra.required = false;

            setOptions(calSel, [], 'Seleccione barrio');
            calOtra.classList.add('d-none');
            calOtra.required = false;

            if (items.length && ciudadAct) {
                if (items.includes(ciudadAct)) {
                    ciuSel.value = ciudadAct;
                    poblarBarrios(dep, ciudadAct);
                } else if (items.includes('Otra')) {
                    ciuSel.value = 'Otra';
                    ciuOtra.classList.remove('d-none');
                    ciuOtra.required = true;
                    ciuOtra.value = ciudadAct;
                    // cuando es "Otra" dejamos barrio/calle también en Otra
                    setOptions(barSel, ['Otra']);
                    barSel.value = 'Otra';
                    barOtra.classList.remove('d-none');
                    barOtra.required = true;
                    barOtra.value = barrioAct;
                    setOptions(calSel, ['Otra']);
                    calSel.value = 'Otra';
                    calOtra.classList.remove('d-none');
                    calOtra.required = true;
                    calOtra.value = calleAct;
                }
            }
        }

        function poblarBarrios(dep, ciudad) {
            let items = [];
            if (dep && ciudad && BARRIOS[dep] && BARRIOS[dep][ciudad]) {
                items = [...BARRIOS[dep][ciudad]];
            } else {
                items = ['Otra'];
            }
            setOptions(barSel, items, 'Seleccione ciudad');
            barOtra.classList.add('d-none');
            barOtra.required = false;

            setOptions(calSel, [], 'Seleccione barrio');
            calOtra.classList.add('d-none');
            calOtra.required = false;

            if (barrioAct) {
                if (items.includes(barrioAct)) {
                    barSel.value = barrioAct;
                    poblarCalles(dep, ciudad, barrioAct);
                } else if (items.includes('Otra')) {
                    barSel.value = 'Otra';
                    barOtra.classList.remove('d-none');
                    barOtra.required = true;
                    barOtra.value = barrioAct;

                    setOptions(calSel, ['Otra']);
                    calSel.value = 'Otra';
                    calOtra.classList.remove('d-none');
                    calOtra.required = true;
                    calOtra.value = calleAct;
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
            setOptions(calSel, items, 'Seleccione barrio');
            calOtra.classList.add('d-none');
            calOtra.required = false;

            if (calleAct) {
                if (items.includes(calleAct)) {
                    calSel.value = calleAct;
                } else if (items.includes('Otra')) {
                    calSel.value = 'Otra';
                    calOtra.classList.remove('d-none');
                    calOtra.required = true;
                    calOtra.value = calleAct;
                }
            }
        }

        depSel.addEventListener('change', () => poblarCiudades(depSel.value));
        ciuSel.addEventListener('change', () => {
            const dep = depSel.value;
            const c = ciuSel.value;
            if (c === 'Otra') {
                ciuOtra.classList.remove('d-none');
                ciuOtra.required = true;
                setOptions(barSel, ['Otra']);
                barSel.value = 'Otra';
                barOtra.classList.remove('d-none');
                barOtra.required = true;
                setOptions(calSel, ['Otra']);
                calSel.value = 'Otra';
                calOtra.classList.remove('d-none');
                calOtra.required = true;
            } else {
                ciuOtra.classList.add('d-none');
                ciuOtra.required = false;
                poblarBarrios(dep, c);
            }
        });
        barSel.addEventListener('change', () => {
            const dep = depSel.value,
                c = ciuSel.value,
                b = barSel.value;
            if (b === 'Otra') {
                barOtra.classList.remove('d-none');
                barOtra.required = true;
                setOptions(calSel, ['Otra']);
                calSel.value = 'Otra';
                calOtra.classList.remove('d-none');
                calOtra.required = true;
            } else {
                barOtra.classList.add('d-none');
                barOtra.required = false;
                poblarCalles(dep, c, b);
            }
        });
        calSel.addEventListener('change', () => {
            if (calSel.value === 'Otra') {
                calOtra.classList.remove('d-none');
                calOtra.required = true;
            } else {
                calOtra.classList.add('d-none');
                calOtra.required = false;
            }
        });

        // Init
        if (depActual) depSel.value = depActual;
        poblarCiudades(depSel.value);
    })();
</script>