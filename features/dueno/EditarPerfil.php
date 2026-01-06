<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Models/Usuario.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Models\Usuario;
use Jaguata\Helpers\Session;

AppConfig::init();

/* 🔒 Auth (solo dueño) */
$auth = new AuthController();
$auth->checkRole('dueno');

/* ID usuario logueado */
$usuarioId = Session::getUsuarioId();
if (!$usuarioId) {
    http_response_code(401);
    exit('Sesión inválida');
}

$usuarioModel = new Usuario();

/* Traer datos actuales desde la BD */
$usuario = $usuarioModel->find((int)$usuarioId);
if (!$usuario) {
    http_response_code(404);
    exit('Usuario no encontrado.');
}

/* =========================
   CATÁLOGOS: Dep → Ciudad → Barrios
   ========================= */
$DEPARTAMENTOS = ['Asunción', 'Gran Asunción', 'Otra'];

/**
 * Estructura:
 *  UBICACIONES[Departamento][Ciudad] = [Barrios...]
 */
$UBICACIONES = [
    'Asunción' => [
        'Asunción' => [
            'Villa Morra',
            'Las Mercedes',
            'Recoleta',
            'Manorá',
            'Mburucuyá',
            'Madame Lynch',
            'Trinidad',
            'Sajonia',
            'Centro',
            'Otra'
        ],
    ],

    'Gran Asunción' => [
        'San Lorenzo' => ['Centro', 'Barcequillo', 'La Victoria', 'Otra'],
        'Luque'       => ['Centro', 'Itapuami', 'Ycuá Duré', 'Otra'],
        'Lambaré'     => ['Centro', 'Valle Apu’a', 'Otra'],
        'Fernando de la Mora' => ['Zona Norte', 'Zona Sur', 'Otra'],
        'Mariano Roque Alonso' => ['Centro', 'Otra'],
        'Ñemby'       => ['Centro', 'Cañadita', 'Otra'],
        'Villa Elisa' => ['Centro', 'Otra'],
        'Capiatá'     => ['Centro', 'Ruta 1', 'Otra'],
        'Limpio'      => ['Centro', 'Piquete Cue', 'Otra'],
        'Itauguá'     => ['Centro', 'Otra'],
        'Otra'        => ['Otra'],
    ],

    'Otra' => [
        'Otra' => ['Otra'],
    ],
];

/* =========================
   DATOS ACTUALES
   ========================= */
$nombre     = $usuario['nombre']       ?? '';
$email      = $usuario['email']        ?? '';
$telefono   = $usuario['telefono']     ?? '';

$fechaNacAct = $usuario['fecha_nacimiento'] ?? '';
if (!empty($fechaNacAct) && strlen((string)$fechaNacAct) > 10) {
    $fechaNacAct = substr((string)$fechaNacAct, 0, 10);
}

$depActual  = $usuario['departamento'] ?? '';
$ciudadAct  = $usuario['ciudad']       ?? '';

$calle      = $usuario['calle']        ?? '';
$zona       = $usuario['zona']         ?? '';
$direccion  = $usuario['direccion']    ?? '';

/* Barrio guardado en BD */
$barrioGuardado = $usuario['barrio'] ?? '';
$barrioSelect = $barrioGuardado;
$barrioTexto  = '';

$fotoActual = $usuario['foto_perfil'] ?? '';

$msg   = '';
$error = '';

/* =========================
   📨 PROCESAR POST
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre    = trim($_POST['nombre'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');

    $fechaNacAct = trim($_POST['fecha_nacimiento'] ?? '');

    $depActual = trim($_POST['departamento'] ?? '');
    $ciudadAct = trim($_POST['ciudad'] ?? '');

    $calle     = trim($_POST['calle'] ?? '');
    $zona      = trim($_POST['zona'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    // Barrio: select + texto
    $barrioSelect = trim($_POST['barrio_select'] ?? '');
    $barrioTexto  = trim($_POST['barrio_texto'] ?? '');

    // Si eligió "Otra", guardamos lo escrito
    $barrioFinal = ($barrioSelect === 'Otra') ? $barrioTexto : $barrioSelect;

    // Validaciones
    if ($nombre === '' || $email === '') {
        $error = 'Nombre y correo son obligatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Correo electrónico inválido.';
    } elseif ($fechaNacAct !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaNacAct)) {
        $error = 'Fecha de nacimiento inválida.';
    } elseif ($fechaNacAct !== '' && $fechaNacAct > date('Y-m-d')) {
        $error = 'La fecha de nacimiento no puede ser futura.';
    } else {
        $fotoNueva = null;

        /* 👇 Foto de perfil */
        if (!empty($_FILES['foto']['name']) && is_uploaded_file($_FILES['foto']['tmp_name'])) {
            $file   = $_FILES['foto'];
            $permit = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/jpg'  => 'jpg',
            ];

            $mime = @mime_content_type($file['tmp_name']) ?: ($file['type'] ?? '');

            if (isset($permit[$mime])) {
                $ext = $permit[$mime];

                $dir = __DIR__ . '/../../assets/uploads/perfiles';
                if (!is_dir($dir)) {
                    mkdir($dir, 0775, true);
                }

                $fname = 'u' . $usuarioId . '-' . date('YmdHis') . '.' . $ext;
                $dest  = $dir . '/' . $fname;

                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $fotoNueva = '/assets/uploads/perfiles/' . $fname;
                }
            }
        }

        /* Datos a actualizar */
        $data = [
            'nombre'           => $nombre,
            'email'            => $email,
            'telefono'         => $telefono,
            'fecha_nacimiento' => $fechaNacAct !== '' ? $fechaNacAct : null,

            'departamento'     => $depActual !== '' ? $depActual : null,
            'ciudad'           => $ciudadAct !== '' ? $ciudadAct : null,

            'barrio'           => $barrioFinal !== '' ? $barrioFinal : null,
            'calle'            => $calle !== '' ? $calle : null,
            'zona'             => $zona !== '' ? $zona : null,
            'direccion'        => $direccion !== '' ? $direccion : null,
        ];

        if ($fotoNueva) {
            $data['foto_perfil'] = $fotoNueva;
        }

        if ($usuarioModel->update((int)$usuarioId, $data)) {
            $msg = 'Perfil actualizado correctamente.';
            $usuario = $usuarioModel->find((int)$usuarioId);

            $fotoActual = $usuario['foto_perfil'] ?? $fotoActual;

            // refrescar fecha nac
            $fechaNacAct = $usuario['fecha_nacimiento'] ?? '';
            if (!empty($fechaNacAct) && strlen((string)$fechaNacAct) > 10) {
                $fechaNacAct = substr((string)$fechaNacAct, 0, 10);
            }

            // refrescar barrio
            $barrioGuardado = $usuario['barrio'] ?? '';
            $barrioSelect   = $barrioGuardado;
            $barrioTexto    = '';
        } else {
            $error = 'No se pudo guardar los cambios.';
        }
    }
}

/* =========================
   RUTAS BASE
   ========================= */
$rolMenu      = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures = BASE_URL . "/features/{$rolMenu}";

/* Helper */
function h(?string $v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

/* Foto preview */
if (!empty($fotoActual)) {
    $srcFoto = rtrim(BASE_URL, '/') . $fotoActual;
} else {
    $srcFoto = ASSETS_URL . '/images/user-placeholder.png';
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Editar Perfil - Jaguata</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <!-- ✅ CSS unificado -->
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">
</head>

<body>
    <!-- ✅ Sidebar unificado (incluye topbar + backdrop + JS toggle) -->
    <?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>

    <main>
        <div class="py-2">

            <div class="header-box header-dashboard mb-2">
                <div>
                    <h1 class="fw-bold mb-1">
                        <i class="fas fa-user-edit me-2"></i>Editar Perfil
                    </h1>
                    <p class="mb-0">
                        Actualizá tus datos personales y de contacto 🐾
                    </p>
                </div>
                <div class="text-end">
                    <a href="<?= $baseFeatures; ?>/MiPerfil.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>
            </div>

            <?php if ($msg): ?>
                <div class="alert alert-success"><?= h($msg); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= h($error); ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="row g-3">

                    <!-- Col izquierda -->
                    <div class="col-lg-4">
                        <div class="section-card text-center">
                            <div class="mb-3">
                                <img
                                    id="previewFoto"
                                    src="<?= h($srcFoto); ?>"
                                    alt="Foto de perfil"
                                    class="rounded-circle mb-3"
                                    style="width:150px; height:150px; object-fit:cover;">
                            </div>

                            <div class="text-start">
                                <div class="mb-3">
                                    <label class="form-label">Foto de perfil</label>
                                    <input type="file" class="form-control" name="foto" id="foto" accept="image/*">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Nombre completo</label>
                                    <input type="text" class="form-control" name="nombre" value="<?= h($nombre); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Correo electrónico</label>
                                    <input type="email" class="form-control" name="email" value="<?= h($email); ?>" required>
                                </div>

                                <div class="mb-3 text-start">
                                    <label class="form-label">Fecha de nacimiento</label>
                                    <input
                                        type="date"
                                        class="form-control"
                                        name="fecha_nacimiento"
                                        value="<?= h($fechaNacAct); ?>"
                                        max="<?= date('Y-m-d'); ?>">
                                </div>

                                <div class="mb-0">
                                    <label class="form-label">Teléfono</label>
                                    <input type="text" class="form-control" name="telefono" value="<?= h($telefono); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Col derecha -->
                    <div class="col-lg-8">
                        <div class="section-card">
                            <div class="section-header">
                                <i class="fas fa-map-marker-alt me-2"></i>Dirección
                            </div>

                            <div class="section-body">
                                <div class="row g-3">

                                    <!-- Departamento -->
                                    <div class="col-md-4">
                                        <label class="form-label">Departamento</label>
                                        <select class="form-select" name="departamento" id="departamento">
                                            <option value="">Seleccione…</option>
                                            <?php foreach ($DEPARTAMENTOS as $d): ?>
                                                <option value="<?= h($d); ?>" <?= $d === $depActual ? 'selected' : ''; ?>>
                                                    <?= h($d); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Ciudad -->
                                    <div class="col-md-4">
                                        <label class="form-label">Ciudad</label>
                                        <select class="form-select" name="ciudad" id="ciudad">
                                            <option value="">Seleccione…</option>
                                        </select>
                                    </div>

                                    <!-- Barrio (select) -->
                                    <div class="col-md-4">
                                        <label class="form-label">Barrio</label>
                                        <select class="form-select" name="barrio_select" id="barrio_select">
                                            <option value="">Seleccione…</option>
                                        </select>
                                    </div>

                                    <!-- Barrio (texto si eligió "Otra") -->
                                    <div class="col-12" id="barrio_texto_wrap" style="display:none;">
                                        <label class="form-label">Especificar barrio</label>
                                        <input type="text" class="form-control" name="barrio_texto" id="barrio_texto"
                                            placeholder="Escribí tu barrio...">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Calle</label>
                                        <input type="text" class="form-control" name="calle" value="<?= h($calle); ?>" placeholder="Ej: Av. Mariscal López">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Zona / Referencia</label>
                                        <input type="text" class="form-control" name="zona" value="<?= h($zona); ?>" placeholder="Ej: cerca del Shopping, portón negro, etc.">
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Dirección (completa)</label>
                                        <input type="text" class="form-control" name="direccion" value="<?= h($direccion); ?>" placeholder="Ej: Calle X c/ Calle Y, N° 123">
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="text-end mt-3">
                    <button type="submit" class="btn-guardar">
                        <i class="fas fa-save me-1"></i> Guardar cambios
                    </button>
                </div>
            </form>

            <footer class="mt-4 text-center text-muted small">
                © <?= date('Y'); ?> Jaguata — Panel del Dueño
            </footer>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Preview foto
        document.getElementById('foto')?.addEventListener('change', function(e) {
            const file = e.target.files?.[0];
            if (!file) return;
            const url = URL.createObjectURL(file);
            const img = document.getElementById('previewFoto');
            if (img) img.src = url;
        });

        // =========================
        // Dep → Ciudad → Barrio
        // =========================
        const UBICACIONES = <?= json_encode($UBICACIONES, JSON_UNESCAPED_UNICODE); ?>;

        const depEl = document.getElementById('departamento');
        const ciudadEl = document.getElementById('ciudad');
        const barrioSelEl = document.getElementById('barrio_select');
        const barrioTxtWrap = document.getElementById('barrio_texto_wrap');
        const barrioTxtEl = document.getElementById('barrio_texto');

        const depActual = <?= json_encode($depActual, JSON_UNESCAPED_UNICODE); ?>;
        const ciudadAct = <?= json_encode($ciudadAct, JSON_UNESCAPED_UNICODE); ?>;
        const barrioDB = <?= json_encode($barrioGuardado, JSON_UNESCAPED_UNICODE); ?>;

        function setOptions(select, items, selectedValue = '') {
            select.innerHTML = '<option value="">Seleccione…</option>';
            (items || []).forEach(v => {
                const opt = document.createElement('option');
                opt.value = v;
                opt.textContent = v;
                if (v === selectedValue) opt.selected = true;
                select.appendChild(opt);
            });
        }

        function loadCiudades(depto, selected = '') {
            if (!depto || !UBICACIONES[depto]) {
                setOptions(ciudadEl, []);
                setOptions(barrioSelEl, []);
                barrioTxtWrap.style.display = 'none';
                barrioTxtEl.value = '';
                return;
            }
            const ciudades = Object.keys(UBICACIONES[depto]);
            setOptions(ciudadEl, ciudades, selected);

            loadBarrios(depto, ciudadEl.value, barrioDB);
        }

        function loadBarrios(depto, ciudad, barrioSeleccionado = '') {
            if (!depto || !ciudad || !UBICACIONES[depto] || !UBICACIONES[depto][ciudad]) {
                setOptions(barrioSelEl, []);
                barrioTxtWrap.style.display = 'none';
                barrioTxtEl.value = '';
                return;
            }

            const barrios = UBICACIONES[depto][ciudad] || [];

            const existe = barrios.includes(barrioSeleccionado);
            const selected = existe ? barrioSeleccionado : (barrios.includes('Otra') ? 'Otra' : '');

            setOptions(barrioSelEl, barrios, selected);

            // Si barrio guardado NO existe en lista => lo dejamos en input
            if (!existe && barrioSeleccionado) {
                barrioTxtWrap.style.display = 'block';
                barrioTxtEl.value = barrioSeleccionado;
            } else {
                barrioTxtWrap.style.display = (barrioSelEl.value === 'Otra') ? 'block' : 'none';
                if (barrioSelEl.value !== 'Otra') barrioTxtEl.value = '';
            }
        }

        depEl?.addEventListener('change', () => {
            loadCiudades(depEl.value, '');
        });

        ciudadEl?.addEventListener('change', () => {
            loadBarrios(depEl.value, ciudadEl.value, '');
        });

        barrioSelEl?.addEventListener('change', () => {
            barrioTxtWrap.style.display = (barrioSelEl.value === 'Otra') ? 'block' : 'none';
            if (barrioSelEl.value !== 'Otra') barrioTxtEl.value = '';
        });

        // Inicializar selects con valores de BD
        if (depActual) {
            depEl.value = depActual;
            loadCiudades(depActual, ciudadAct || '');
        } else {
            setOptions(ciudadEl, []);
            setOptions(barrioSelEl, []);
        }
    </script>
</body>

</html>
