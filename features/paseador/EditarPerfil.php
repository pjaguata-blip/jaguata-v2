<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Models/Usuario.php';
require_once __DIR__ . '/../../src/Models/Paseador.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Models\Usuario;
use Jaguata\Models\Paseador;
use Jaguata\Helpers\Session;

AppConfig::init();

/* 🔒 Solo paseador */
$authController = new AuthController();
$authController->checkRole('paseador');

$usuarioModel = new Usuario();
$paseadorModel = new Paseador();

$usuarioId = (int)(Session::getUsuarioId() ?? 0);
if ($usuarioId <= 0) {
    echo "Error: Sesión inválida.";
    exit;
}

/* Datos actuales */
$usuario = $usuarioModel->find($usuarioId);
if (!$usuario) {
    echo "Error: No se encontró el usuario.";
    exit;
}

$paseadorRow = $paseadorModel->find($usuarioId) ?: [];
$precioHoraActual = (float)($paseadorRow['precio_hora'] ?? 0);

$mensaje = '';
$error   = '';

/* Departamentos limitados */
$DEPARTAMENTOS = ['Asunción', 'Central'];

/* Ciudades (Gran Asunción / Central) */
$CIUDADES = [
    'Asunción',
    'San Lorenzo',
    'Fernando de la Mora',
    'Luque',
    'Lambaré',
    'Mariano Roque Alonso',
    'Limpio',
    'Ñemby',
    'Villa Elisa',
    'Capiatá',
    'Itauguá',
    'Areguá',
    'Ypané',
    'Guarambaré',
    'Nueva Italia',
    'Villeta'
];

$depActual   = (string)($usuario['departamento'] ?? '');
$ciudadAct   = (string)($usuario['ciudad'] ?? '');
$barrioAct   = (string)($usuario['barrio'] ?? '');
$calleAct    = (string)($usuario['calle'] ?? '');
$telAct      = (string)($usuario['telefono'] ?? '');
$expAct      = (string)($usuario['experiencia'] ?? '');
$fotoActual  = (string)($usuario['foto_perfil'] ?? ($usuario['perfil_foto'] ?? ''));
$fechaNacAct = (string)($usuario['fecha_nacimiento'] ?? '');

$zonasActuales = [];
if (!empty($usuario['zona'])) {
    $z = (string)$usuario['zona'];
    $decoded = json_decode($z, true);
    $zonasActuales = json_last_error() === JSON_ERROR_NONE ? (array)$decoded : explode(',', $z);
    $zonasActuales = array_values(array_filter(array_map('trim', $zonasActuales)));
}

/* ===== Guardar ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre       = trim($_POST['nombre'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $telefono     = trim($_POST['telefono'] ?? '');
    $experiencia  = trim($_POST['experiencia'] ?? '');
    $fechaNac     = trim($_POST['fecha_nacimiento'] ?? '');

    $departamento = trim($_POST['departamento'] ?? '');
    $ciudad       = trim($_POST['ciudad'] ?? '');
    $barrio       = trim($_POST['barrio'] ?? '');
    $calle        = trim($_POST['calle'] ?? '');

    $precioHoraIn = trim($_POST['precio_hora'] ?? '');
    $precioHora   = is_numeric($precioHoraIn) ? (float)$precioHoraIn : null;

    $zonaJsonPost = trim($_POST['zona_json'] ?? '[]');
    $zonasTrabajo = json_decode($zonaJsonPost, true);
    if (!is_array($zonasTrabajo)) $zonasTrabajo = [];

    if ($nombre === '' || $email === '') {
        $error = "El nombre y el email son obligatorios.";
    } elseif (!in_array($departamento, $DEPARTAMENTOS, true)) {
        $error = "Seleccione un departamento válido (Asunción o Central).";
    } elseif ($ciudad === '' || !in_array($ciudad, $CIUDADES, true)) {
        $error = "Seleccione una ciudad válida (Gran Asunción / Central).";
    } elseif ($precioHora === null || $precioHora < 0) {
        $error = "Ingrese una tarifa válida (0 o mayor).";
    } else {

        /* 📷 Foto */
        $rutaFotoNueva = null;
        if (!empty($_FILES['foto_perfil']['name'])) {
            $file = $_FILES['foto_perfil'];

            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $max = 2 * 1024 * 1024;
                if (($file['size'] ?? 0) > $max) {
                    $error = "La foto supera el tamaño máximo de 2MB.";
                } else {
                    $permitidos = [
                        'image/jpeg' => 'jpg',
                        'image/png'  => 'png',
                        'image/webp' => 'webp'
                    ];
                    $mime = mime_content_type($file['tmp_name']);
                    if (!isset($permitidos[$mime])) {
                        $error = "Formato no permitido (usa JPG, PNG o WebP).";
                    } else {
                        $dirFs = realpath(__DIR__ . '/../../') . '/assets/uploads/perfiles';
                        if (!is_dir($dirFs)) @mkdir($dirFs, 0775, true);

                        $ext = $permitidos[$mime];
                        $nombreArchivo = 'paseador-' . $usuarioId . '-' . date('YmdHis') . '.' . $ext;
                        $destinoFs  = $dirFs . '/' . $nombreArchivo;
                        $destinoUrl = '/assets/uploads/perfiles/' . $nombreArchivo;

                        if (move_uploaded_file($file['tmp_name'], $destinoFs)) {
                            $rutaFotoNueva = $destinoUrl;
                        } else {
                            $error = "No se pudo guardar la imagen.";
                        }
                    }
                }
            }
        }

        if ($error === '') {

            /* 1) Actualiza USUARIOS (calle/barrio/ciudad/departamento etc.) */
            $dataUsuario = [
                'nombre'           => $nombre,
                'email'            => $email,
                'telefono'         => $telefono,
                'experiencia'      => $experiencia,
                'fecha_nacimiento' => ($fechaNac ?: null),
                'departamento'     => $departamento,
                'ciudad'           => $ciudad,
                'barrio'           => $barrio,
                'calle'            => $calle,
                'zona'             => json_encode($zonasTrabajo, JSON_UNESCAPED_UNICODE),
            ];

            if ($rutaFotoNueva) {
                $dataUsuario['foto_perfil'] = $rutaFotoNueva;
            }

            /* 2) Actualiza PASEADORES (tarifa) */
            $okPaseador = $paseadorModel->update($usuarioId, [
                'precio_hora' => $precioHora,
            ]);

            $okUsuario = $usuarioModel->update($usuarioId, $dataUsuario);

            if ($okUsuario && $okPaseador) {
                $mensaje = "Perfil actualizado correctamente.";

                $usuario = $usuarioModel->find($usuarioId) ?: $usuario;
                $paseadorRow = $paseadorModel->find($usuarioId) ?: $paseadorRow;
                $precioHoraActual = (float)($paseadorRow['precio_hora'] ?? $precioHora);

                $depActual   = (string)($usuario['departamento'] ?? '');
                $ciudadAct   = (string)($usuario['ciudad'] ?? '');
                $barrioAct   = (string)($usuario['barrio'] ?? '');
                $calleAct    = (string)($usuario['calle'] ?? '');
                $telAct      = (string)($usuario['telefono'] ?? '');
                $expAct      = (string)($usuario['experiencia'] ?? '');
                $fotoActual  = (string)($usuario['foto_perfil'] ?? ($usuario['perfil_foto'] ?? ''));
                $fechaNacAct = (string)($usuario['fecha_nacimiento'] ?? '');

                $z = (string)($usuario['zona'] ?? '[]');
                $decoded = json_decode($z, true);
                $zonasActuales = json_last_error() === JSON_ERROR_NONE ? (array)$decoded : explode(',', $z);
                $zonasActuales = array_values(array_filter(array_map('trim', $zonasActuales)));
            } else {
                $error = "Error al guardar los cambios (usuario o paseador).";
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - Paseador | Jaguata</title>

    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        .perfil-avatar {
            width: 140px;
            height: 140px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid var(--verde-jaguata);
            box-shadow: 0 0 10px rgba(0, 0, 0, .12);
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../../src/Templates/SidebarPaseador.php'; ?>

    <button class="btn btn-outline-secondary d-md-none ms-2 mt-3" id="toggleSidebar">
        <i class="fas fa-bars"></i>
    </button>

    <main>
        <div class="py-1">

            <div class="header-box header-dashboard mb-4 d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="fw-bold mb-1">
                        <i class="fas fa-edit me-2"></i>Editar Perfil — Paseador
                    </h1>
                    <p class="mb-0">Actualizá tus datos, dirección y tarifa del servicio 🐾</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="Perfil.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-user me-1"></i> Mi perfil
                    </a>
                    <a href="Dashboard.php" class="btn btn-light btn-sm text-success">
                        <i class="fas fa-home me-1"></i> Panel
                    </a>
                </div>
            </div>

            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="row g-3">

                    <div class="col-lg-4">
                        <div class="section-card text-center h-100">
                            <div class="section-body">
                                <?php
                                $src = $fotoActual
                                    ? (preg_match('#^https?://#i', (string)$fotoActual) ? $fotoActual : (BASE_URL . $fotoActual))
                                    : (ASSETS_URL . '/images/user-placeholder.png');
                                ?>
                                <img id="previewFoto" src="<?= htmlspecialchars($src) ?>" class="perfil-avatar mb-3" alt="Foto de perfil">

                                <div class="mb-3 text-start">
                                    <label class="form-label">Foto de perfil</label>
                                    <input class="form-control" type="file" id="foto_perfil" name="foto_perfil"
                                        accept="image/jpeg,image/png,image/webp">
                                </div>

                                <div class="mb-3 text-start">
                                    <label class="form-label">Nombre completo</label>
                                    <input type="text" class="form-control" name="nombre"
                                        value="<?= htmlspecialchars((string)($usuario['nombre'] ?? '')) ?>" required>
                                </div>

                                <div class="mb-3 text-start">
                                    <label class="form-label">Correo electrónico</label>
                                    <input type="email" class="form-control" name="email"
                                        value="<?= htmlspecialchars((string)($usuario['email'] ?? '')) ?>" required>
                                </div>

                                <div class="mb-3 text-start">
                                    <label class="form-label">Fecha de nacimiento</label>
                                    <input type="date" class="form-control" name="fecha_nacimiento"
                                        value="<?= htmlspecialchars($fechaNacAct) ?>" max="<?= date('Y-m-d') ?>">
                                </div>

                                <!-- ✅ TARIFA -->
                                <div class="mb-0 text-start">
                                    <label class="form-label">
                                        Tarifa por hora (₲)
                                    </label>
                                    <input type="number" class="form-control" name="precio_hora"
                                        min="0" step="1"
                                        value="<?= htmlspecialchars((string)$precioHoraActual) ?>"
                                        required>
                                    <small class="text-muted">Esto se guarda en <b>paseadores.precio_hora</b>.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="row g-3">

                            <!-- ✅ Dirección y contacto (Calle/Barrio/Ciudad/Departamento) -->
                            <div class="col-12">
                                <div class="section-card">
                                    <div class="section-header">
                                        <i class="fa-solid fa-map-location-dot me-2"></i> Dirección y contacto
                                    </div>
                                    <div class="section-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Teléfono</label>
                                                <input type="text" class="form-control" name="telefono"
                                                    value="<?= htmlspecialchars($telAct) ?>">
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label">Departamento</label>
                                                <select class="form-select" name="departamento" id="departamento" required>
                                                    <option value="">Seleccione…</option>
                                                    <?php foreach ($DEPARTAMENTOS as $dep): ?>
                                                        <option value="<?= htmlspecialchars($dep) ?>" <?= $depActual === $dep ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($dep) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label">Ciudad</label>
                                                <select class="form-select" name="ciudad" id="ciudad" required>
                                                    <option value="">Seleccione…</option>
                                                    <?php foreach ($CIUDADES as $c): ?>
                                                        <option value="<?= htmlspecialchars($c) ?>" <?= $ciudadAct === $c ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($c) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <small class="text-muted">Lista limitada a Gran Asunción / Central.</small>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label">Barrio</label>
                                                <input type="text" class="form-control" name="barrio"
                                                    value="<?= htmlspecialchars($barrioAct) ?>" placeholder="Ej: Villa Morra">
                                            </div>

                                            <div class="col-12">
                                                <label class="form-label">Calle</label>
                                                <input type="text" class="form-control" name="calle"
                                                    value="<?= htmlspecialchars($calleAct) ?>" placeholder="Ej: Av. España 123">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Experiencia -->
                            <div class="col-12">
                                <div class="section-card">
                                    <div class="section-header">
                                        <i class="fa-solid fa-briefcase me-2"></i> Experiencia
                                    </div>
                                    <div class="section-body">
                                        <textarea class="form-control" name="experiencia" rows="3"
                                            placeholder="Contanos tu experiencia paseando perros..."><?= htmlspecialchars($expAct) ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Zonas -->
                            <!-- ✅ Zonas de trabajo (SELECT Ciudad + Barrio) -->
                            <div class="col-12">
                                <div class="section-card">
                                    <div class="section-header">
                                        <i class="fa-solid fa-map me-2"></i> Zonas de trabajo
                                    </div>
                                    <div class="section-body">
                                        <p class="text-muted small mb-3">
                                            Elegí una <b>Ciudad</b> y un <b>Barrio</b>. Se guardan como <code>Ciudad - Barrio</code>.
                                        </p>

                                        <div class="row g-2 align-items-end">
                                            <div class="col-md-5">
                                                <label class="form-label">Ciudad</label>
                                                <select class="form-select" id="zonaCiudad">
                                                    <option value="">Seleccione…</option>
                                                    <?php foreach ($CIUDADES as $c): ?>
                                                        <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="col-md-5">
                                                <label class="form-label">Barrio</label>
                                                <select class="form-select" id="zonaBarrio" disabled>
                                                    <option value="">Seleccione una ciudad…</option>
                                                </select>
                                                <small class="text-muted">El barrio se habilita al elegir ciudad.</small>
                                            </div>

                                            <div class="col-md-2 d-grid">
                                                <button type="button" class="btn btn-outline-success" id="btnAgregarZonaSelect">
                                                    <i class="fa-solid fa-plus me-1"></i> Agregar
                                                </button>
                                            </div>
                                        </div>

                                        <div id="zonasSeleccionadas" class="mt-3"></div>

                                        <input type="hidden" name="zona_json" id="zona_json"
                                            value="<?= htmlspecialchars(json_encode($zonasActuales, JSON_UNESCAPED_UNICODE)) ?>">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-gradient px-4">
                        <i class="fas fa-save me-2"></i> Guardar cambios
                    </button>
                </div>
            </form>

            <footer class="mt-4 text-center text-muted small">
                © <?= date('Y') ?> Jaguata — Panel del Paseador
            </footer>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('toggleSidebar')?.addEventListener('click', function() {
            document.getElementById('sidebar')?.classList.toggle('sidebar-open');
        });

        // Preview foto
        document.getElementById('foto_perfil')?.addEventListener('change', e => {
            const file = e.target.files?.[0];
            if (file) document.getElementById('previewFoto').src = URL.createObjectURL(file);
        });

        // Auto depto según ciudad
        const ciudadSel = document.getElementById('ciudad');
        const depSel = document.getElementById('departamento');

        function autoDepartamento() {
            const c = ciudadSel?.value || '';
            if (!depSel) return;
            if (c === 'Asunción') depSel.value = 'Asunción';
            else if (c) depSel.value = 'Central';
        }
        ciudadSel?.addEventListener('change', autoDepartamento);
        autoDepartamento();

        // ===== ZONAS: ciudad + barrio (select dependiente) =====
        const inputZonas = document.getElementById('zona_json');
        const contZonas = document.getElementById('zonasSeleccionadas');

        const selCiudadZ = document.getElementById('zonaCiudad');
        const selBarrioZ = document.getElementById('zonaBarrio');
        const btnAddZ = document.getElementById('btnAgregarZonaSelect');

        // ⚠️ Lista base (podés ampliar)
        const BARRIOS_POR_CIUDAD = {
            "Asunción": [
                "Villa Morra", "Las Mercedes", "Recoleta", "Carmelitas",
                "Centro", "Trinidad", "Mburucuyá", "San Vicente", "Sajonia"
            ],
            "San Lorenzo": ["Barcequillo", "Universitario", "Centro", "San Miguel"],
            "Luque": ["Centro", "Maka'i", "Ykua Duré", "Marín Ka'aguy"],
            "Fernando de la Mora": ["Zona Norte", "Zona Sur", "Centro"],
            "Lambaré": ["Centro", "Valle Apu'a", "Santa Lucía"],
            "Mariano Roque Alonso": ["Centro", "Remansito"],
            "Limpio": ["Centro", "Piquete Cué", "San Francisco"],
            "Ñemby": ["Centro", "Cañadita", "Pa'i Ñu"],
            "Villa Elisa": ["Centro", "Sol de América", "Mbocayaty"],
            "Capiatá": ["Centro", "Ruta 1", "Ruta 2", "Toledo Cañada"],
            "Itauguá": ["Centro", "Ñu Guazú", "Hugua Po'i"],
            "Areguá": ["Centro", "Isla Valle", "Pindolo"],
            "Ypané": ["Centro", "Paso de Oro"],
            "Guarambaré": ["Centro", "Itapé Guazú"],
            "Nueva Italia": ["Centro"],
            "Villeta": ["Centro", "Tacuruty"]
        };

        function getZonas() {
            try {
                const z = JSON.parse(inputZonas.value);
                return Array.isArray(z) ? z : [];
            } catch {
                return [];
            }
        }

        function setZonas(zonas) {
            inputZonas.value = JSON.stringify(zonas);
        }

        function renderZonas() {
            const zonas = getZonas();
            contZonas.innerHTML = '';

            if (!zonas.length) {
                contZonas.innerHTML = '<span class="text-muted">Sin zonas agregadas.</span>';
                return;
            }

            zonas.forEach((z, i) => {
                const badge = document.createElement('span');
                badge.className = 'badge bg-success-subtle text-success-emphasis me-2 mb-2';
                badge.textContent = z;

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-sm btn-light ms-1';
                btn.innerHTML = '&times;';
                btn.onclick = () => {
                    const copy = getZonas();
                    copy.splice(i, 1);
                    setZonas(copy);
                    renderZonas();
                };

                const wrap = document.createElement('span');
                wrap.className = 'd-inline-flex align-items-center';
                wrap.appendChild(badge);
                wrap.appendChild(btn);

                contZonas.appendChild(wrap);
            });
        }

        function cargarBarrios(ciudad) {
            selBarrioZ.innerHTML = '';
            const barrios = BARRIOS_POR_CIUDAD[ciudad] || [];

            if (!ciudad) {
                selBarrioZ.disabled = true;
                selBarrioZ.innerHTML = '<option value="">Seleccione una ciudad…</option>';
                return;
            }

            selBarrioZ.disabled = false;
            selBarrioZ.innerHTML = '<option value="">Seleccione…</option>';

            barrios.forEach(b => {
                const opt = document.createElement('option');
                opt.value = b;
                opt.textContent = b;
                selBarrioZ.appendChild(opt);
            });

            // Si no hay barrios cargados, igual dejá seleccionar "Otro"
            if (!barrios.length) {
                const opt = document.createElement('option');
                opt.value = 'Otro';
                opt.textContent = 'Otro';
                selBarrioZ.appendChild(opt);
            }
        }

        selCiudadZ?.addEventListener('change', () => {
            cargarBarrios(selCiudadZ.value);
        });

        btnAddZ?.addEventListener('click', () => {
            const ciudad = (selCiudadZ.value || '').trim();
            const barrio = (selBarrioZ.value || '').trim();

            if (!ciudad) return alert('Seleccione una ciudad.');
            if (!barrio) return alert('Seleccione un barrio.');

            const nueva = `${ciudad} - ${barrio}`;
            const zonas = getZonas();

            if (!zonas.includes(nueva)) zonas.push(nueva);

            setZonas(zonas);
            renderZonas();

            // opcional: reset barrio
            selBarrioZ.value = '';
        });

        renderZonas();
        cargarBarrios(selCiudadZ?.value || '');
    </script>
</body>

</html>