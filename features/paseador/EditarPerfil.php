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

/* 🔒 Solo paseador */
$authController = new AuthController();
$authController->checkRole('paseador');

/* 📌 Datos del usuario logueado */
$usuarioModel = new Usuario();
$usuarioId    = Session::getUsuarioId();

if (!$usuarioId) {
    echo "Error: Sesión inválida.";
    exit;
}

$usuario = $usuarioModel->find((int)$usuarioId);

if (!$usuario) {
    echo "Error: No se encontró el usuario.";
    exit;
}

$mensaje = '';
$error   = '';

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

$depActual      = $usuario['departamento']      ?? '';
$fotoActual     = $usuario['foto_perfil']       ?? ($usuario['perfil_foto'] ?? '');
$fechaNacActual = $usuario['fecha_nacimiento']  ?? '';

$zonasActuales = [];
if (!empty($usuario['zona'])) {
    $z       = $usuario['zona'];
    $decoded = json_decode($z, true);
    $zonasActuales = json_last_error() === JSON_ERROR_NONE ? $decoded : explode(',', $z);
    $zonasActuales = array_values(array_filter(array_map('trim', $zonasActuales)));
}

/* ===== Guardar cambios ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre       = trim($_POST['nombre']          ?? '');
    $email        = trim($_POST['email']           ?? '');
    $departamento = trim($_POST['departamento']    ?? '');
    $telefono     = trim($_POST['telefono']        ?? '');
    $direccion    = trim($_POST['direccion']       ?? '');
    $experiencia  = trim($_POST['experiencia']     ?? '');
    $fechaNac     = trim($_POST['fecha_nacimiento'] ?? '');
    $zonaJsonPost = trim($_POST['zona_json']       ?? '[]');
    $zonasTrabajo = json_decode($zonaJsonPost, true) ?? [];

    if ($nombre === '' || $email === '') {
        $error = "El nombre y el email son obligatorios.";
    } elseif ($departamento === '' || !in_array($departamento, $DEPARTAMENTOS, true)) {
        $error = "Seleccione un departamento válido.";
    } else {
        /* 📷 Manejo de foto */
        $rutaFotoNueva = null;
        if (!empty($_FILES['foto_perfil']['name'])) {
            $file = $_FILES['foto_perfil'];

            if ($file['error'] === UPLOAD_ERR_OK) {
                $max = 2 * 1024 * 1024; // 2MB
                if ($file['size'] > $max) {
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
                        if (!is_dir($dirFs)) {
                            @mkdir($dirFs, 0775, true);
                        }
                        $ext           = $permitidos[$mime];
                        $nombreArchivo = 'paseador-' . $usuarioId . '-' . date('YmdHis') . '.' . $ext;
                        $destinoFs     = $dirFs . '/' . $nombreArchivo;
                        $destinoUrl    = '/assets/uploads/perfiles/' . $nombreArchivo;

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
            $data = [
                'nombre'           => $nombre,
                'email'            => $email,
                'telefono'         => $telefono,
                'direccion'        => $direccion,
                'experiencia'      => $experiencia,
                'departamento'     => $departamento,
                'fecha_nacimiento' => ($fechaNac ?: null),
                'zona'             => json_encode($zonasTrabajo, JSON_UNESCAPED_UNICODE),
            ];

            if ($rutaFotoNueva) {
                $data['foto_perfil'] = $rutaFotoNueva;
            }

            if ($usuarioModel->update((int)$usuarioId, $data)) {
                $mensaje = "Perfil actualizado correctamente.";

                // refrescar datos
                $usuario       = $usuarioModel->find((int)$usuarioId);
                $depActual     = $usuario['departamento']     ?? '';
                $fotoActual    = $usuario['foto_perfil']      ?? ($usuario['perfil_foto'] ?? '');
                $fechaNacActual = $usuario['fecha_nacimiento'] ?? '';
                $z             = $usuario['zona'] ?? '[]';
                $decoded       = json_decode($z, true);
                $zonasActuales = json_last_error() === JSON_ERROR_NONE ? $decoded : explode(',', $z);
                $zonasActuales = array_values(array_filter(array_map('trim', $zonasActuales)));
            } else {
                $error = "Error al guardar los cambios.";
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

    <!-- 🎨 CSS global Jaguata -->
    <link href="<?= ASSETS_URL; ?>/css/jaguata-theme.css" rel="stylesheet">
    <!-- Bootstrap + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        /* Solo pequeños ajustes encima del tema global */

        .perfil-avatar {
            width: 140px;
            height: 140px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid var(--verde-jaguata);
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.12);
        }
    </style>
</head>

<body>

    <!-- Sidebar paseador -->
    <?php include __DIR__ . '/../../src/Templates/SidebarPaseador.php'; ?>

    <!-- Botón hamburguesa mobile -->
    <button class="btn btn-outline-secondary d-md-none ms-2 mt-3" id="toggleSidebar">
        <i class="fas fa-bars"></i>
    </button>

    <!-- CONTENIDO PRINCIPAL (mismo estilo que MiPerfil dueño/paseador) -->
    <main>
        <div class="py-4">

            <!-- HEADER -->
            <div class="header-box header-dashboard mb-4 d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="fw-bold mb-1">
                        <i class="fas fa-edit me-2"></i>Editar Perfil — Paseador
                    </h1>
                    <p class="mb-0">
                        Actualizá tus datos, foto, zonas de trabajo y experiencia en Jaguata 🐾
                    </p>
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

            <!-- ALERTAS -->
            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- FORMULARIO -->
            <form method="POST" enctype="multipart/form-data">
                <div class="row g-3">
                    <!-- Columna izquierda: foto + básicos -->
                    <div class="col-lg-4">
                        <div class="section-card text-center h-100">
                            <div class="section-body">
                                <?php
                                $src = $fotoActual
                                    ? (preg_match('#^https?://#i', (string)$fotoActual)
                                        ? $fotoActual
                                        : (BASE_URL . $fotoActual))
                                    : (ASSETS_URL . '/images/user-placeholder.png');
                                ?>
                                <img id="previewFoto" src="<?= htmlspecialchars($src) ?>"
                                    class="perfil-avatar mb-3" alt="Foto de perfil">

                                <div class="mb-3 text-start">
                                    <label for="foto_perfil" class="form-label">Foto de perfil</label>
                                    <input class="form-control" type="file" id="foto_perfil" name="foto_perfil"
                                        accept="image/jpeg,image/png,image/webp">
                                </div>

                                <div class="mb-3 text-start">
                                    <label for="nombre" class="form-label">Nombre completo</label>
                                    <input type="text" class="form-control" name="nombre"
                                        value="<?= htmlspecialchars($usuario['nombre']) ?>" required>
                                </div>

                                <div class="mb-3 text-start">
                                    <label for="email" class="form-label">Correo electrónico</label>
                                    <input type="email" class="form-control" name="email"
                                        value="<?= htmlspecialchars($usuario['email']) ?>" required>
                                </div>

                                <div class="mb-0 text-start">
                                    <label for="fecha_nacimiento" class="form-label">Fecha de nacimiento</label>
                                    <input type="date" class="form-control" name="fecha_nacimiento"
                                        value="<?= htmlspecialchars($fechaNacActual) ?>"
                                        max="<?= date('Y-m-d') ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Columna derecha: contacto + dirección + zonas + experiencia -->
                    <div class="col-lg-8">
                        <div class="row g-3">
                            <!-- Datos de contacto / dirección -->
                            <div class="col-12">
                                <div class="section-card">
                                    <div class="section-header">
                                        <i class="fa-solid fa-map-location-dot me-2"></i> Dirección y contacto
                                    </div>
                                    <div class="section-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="telefono" class="form-label">Teléfono</label>
                                                <input type="text" class="form-control" name="telefono"
                                                    value="<?= htmlspecialchars($usuario['telefono'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="departamento" class="form-label">Departamento</label>
                                                <select class="form-select" name="departamento" required>
                                                    <option value="">Seleccione…</option>
                                                    <?php foreach ($DEPARTAMENTOS as $dep): ?>
                                                        <option value="<?= htmlspecialchars($dep) ?>"
                                                            <?= $depActual === $dep ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($dep) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <label for="direccion" class="form-label">Referencia / Complemento</label>
                                                <input type="text" class="form-control" name="direccion"
                                                    value="<?= htmlspecialchars($usuario['direccion'] ?? '') ?>">
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
                                            placeholder="Contanos tu experiencia paseando perros..."><?= htmlspecialchars($usuario['experiencia'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Zonas de trabajo -->
                            <div class="col-12">
                                <div class="section-card">
                                    <div class="section-header">
                                        <i class="fa-solid fa-map me-2"></i> Zonas de trabajo
                                    </div>
                                    <div class="section-body">
                                        <div class="mb-2">
                                            <input type="text" class="form-control mb-2" id="nuevaZona"
                                                placeholder="Ejemplo: Central - San Lorenzo">
                                            <button type="button" class="btn btn-outline-success btn-sm" id="btnAgregarZona">
                                                <i class="fa-solid fa-plus me-1"></i> Agregar zona
                                            </button>
                                        </div>

                                        <div id="zonasSeleccionadas" class="mt-2"></div>

                                        <input type="hidden" name="zona_json" id="zona_json"
                                            value="<?= htmlspecialchars(json_encode($zonasActuales, JSON_UNESCAPED_UNICODE)) ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BOTÓN GUARDAR -->
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
        // Toggle sidebar en mobile
        document.getElementById('toggleSidebar')?.addEventListener('click', function() {
            document.getElementById('sidebar')?.classList.toggle('sidebar-open');
        });

        // Previsualización de imagen
        const inputFoto = document.getElementById('foto_perfil');
        if (inputFoto) {
            inputFoto.addEventListener('change', e => {
                const file = e.target.files[0];
                if (file) {
                    document.getElementById('previewFoto').src = URL.createObjectURL(file);
                }
            });
        }

        // Manejo de zonas
        const inputZonas = document.getElementById('zona_json');
        const contZonas = document.getElementById('zonasSeleccionadas');
        const nuevaZona = document.getElementById('nuevaZona');
        const btnAdd = document.getElementById('btnAgregarZona');

        function renderZonas() {
            let zonas = [];
            try {
                zonas = JSON.parse(inputZonas.value) || [];
            } catch {
                zonas = [];
            }
            contZonas.innerHTML = '';
            if (zonas.length === 0) {
                contZonas.innerHTML = '<span class="text-muted">Sin zonas agregadas.</span>';
                return;
            }
            zonas.forEach((z, i) => {
                const span = document.createElement('span');
                span.className = 'badge bg-success-subtle text-success-emphasis me-2 mb-2';
                span.textContent = z;

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-sm btn-light ms-1';
                btn.innerHTML = '&times;';
                btn.onclick = () => {
                    zonas.splice(i, 1);
                    inputZonas.value = JSON.stringify(zonas);
                    renderZonas();
                };

                const wrapper = document.createElement('span');
                wrapper.className = 'd-inline-flex align-items-center';
                wrapper.appendChild(span);
                wrapper.appendChild(btn);
                contZonas.appendChild(wrapper);
            });
        }

        if (btnAdd) {
            btnAdd.addEventListener('click', () => {
                let zonas = [];
                try {
                    zonas = JSON.parse(inputZonas.value) || [];
                } catch {
                    zonas = [];
                }
                const z = (nuevaZona.value || '').trim();
                if (!z) {
                    alert('Ingrese una zona.');
                    return;
                }
                if (!zonas.includes(z)) {
                    zonas.push(z);
                }
                inputZonas.value = JSON.stringify(zonas);
                nuevaZona.value = '';
                renderZonas();
            });
        }

        renderZonas();
    </script>
</body>

</html>