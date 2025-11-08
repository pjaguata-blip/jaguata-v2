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

// Solo paseador
$authController = new AuthController();
$authController->checkRole('paseador');

// Datos del usuario
$usuarioModel = new Usuario();
$usuarioId = Session::get('usuario_id');
$usuario = $usuarioModel->getById($usuarioId);
if (!$usuario) {
    echo "Error: No se encontró el usuario.";
    exit;
}

$mensaje = '';
$error = '';

$DEPARTAMENTOS = ['Concepción', 'San Pedro', 'Cordillera', 'Guairá', 'Caaguazú', 'Caazapá', 'Itapúa', 'Misiones', 'Paraguarí', 'Alto Paraná', 'Central', 'Ñeembucú', 'Amambay', 'Canindeyú', 'Presidente Hayes', 'Boquerón', 'Alto Paraguay'];

$depActual = $usuario['departamento'] ?? '';
$fotoActual = $usuario['foto_perfil'] ?? ($usuario['perfil_foto'] ?? '');
$fechaNacActual = $usuario['fecha_nacimiento'] ?? '';

$zonasActuales = [];
if (!empty($usuario['zona'])) {
    $z = $usuario['zona'];
    $decoded = json_decode($z, true);
    $zonasActuales = json_last_error() === JSON_ERROR_NONE ? $decoded : explode(',', $z);
    $zonasActuales = array_values(array_filter(array_map('trim', $zonasActuales)));
}

// Guardar cambios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $departamento = trim($_POST['departamento'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $experiencia = trim($_POST['experiencia'] ?? '');
    $fechaNac = trim($_POST['fecha_nacimiento'] ?? '');
    $zonaJsonPost = trim($_POST['zona_json'] ?? '[]');
    $zonasTrabajo = json_decode($zonaJsonPost, true) ?? [];

    if ($nombre === '' || $email === '') {
        $error = "El nombre y el email son obligatorios.";
    } elseif ($departamento === '' || !in_array($departamento, $DEPARTAMENTOS, true)) {
        $error = "Seleccione un departamento válido.";
    } else {
        $rutaFotoNueva = null;
        if (!empty($_FILES['foto_perfil']['name'])) {
            $file = $_FILES['foto_perfil'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                $max = 2 * 1024 * 1024;
                if ($file['size'] > $max) {
                    $error = "La foto supera el tamaño máximo de 2MB.";
                } else {
                    $permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                    $mime = mime_content_type($file['tmp_name']);
                    if (!isset($permitidos[$mime])) {
                        $error = "Formato no permitido (usa JPG, PNG o WebP).";
                    } else {
                        $dirFs = realpath(__DIR__ . '/../../') . '/assets/uploads/perfiles';
                        if (!is_dir($dirFs)) @mkdir($dirFs, 0775, true);
                        $ext = $permitidos[$mime];
                        $nombreArchivo = 'paseador-' . $usuarioId . '-' . date('YmdHis') . '.' . $ext;
                        $destinoFs = $dirFs . '/' . $nombreArchivo;
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
            $data = [
                'nombre' => $nombre,
                'email' => $email,
                'telefono' => $telefono,
                'direccion' => $direccion,
                'experiencia' => $experiencia,
                'departamento' => $departamento,
                'fecha_nacimiento' => ($fechaNac ?: null),
                'zona' => json_encode($zonasTrabajo, JSON_UNESCAPED_UNICODE),
            ];
            if ($rutaFotoNueva) $data['foto_perfil'] = $rutaFotoNueva;

            if ($usuarioModel->update($usuarioId, $data)) {
                $mensaje = "Perfil actualizado correctamente.";
                $usuario = $usuarioModel->getById($usuarioId);
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
    <link href="<?= ASSETS_URL; ?>/css/jaguata-theme.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        html,
        body {
            margin: 0;
            background-color: #f6f9f7;
            font-family: "Poppins", sans-serif;
        }

        .layout {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            color: #fff;
            width: 240px;
            flex-shrink: 0;
            box-shadow: 4px 0 12px rgba(0, 0, 0, 0.15);
            padding-top: 1.5rem;
        }

        .sidebar .nav-link {
            color: #ddd;
            border-radius: 8px;
            padding: 10px 16px;
            margin: 4px 8px;
            display: flex;
            align-items: center;
            font-weight: 500;
            transition: all 0.2s;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: #3c6255;
            color: #fff;
            transform: translateX(4px);
        }

        main.content {
            flex-grow: 1;
            padding: 2.5rem;
            background-color: #f6f9f7;
        }

        .page-header {
            background: linear-gradient(90deg, #20c997, #3c6255);
            color: #fff;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .08);
        }

        footer {
            background-color: #3c6255;
            color: #fff;
            text-align: center;
            padding: 1.2rem 0;
            width: 100%;
            margin-top: 3rem;
        }

        img.rounded-circle {
            border: 4px solid #3c6255;
        }

        .btn-success {
            background: linear-gradient(90deg, #3c6255, #20c997);
            border: none;
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../../src/Templates/Navbar.php'; ?>

    <div class="layout">
        <?php include __DIR__ . '/../../src/Templates/SidebarPaseador.php'; ?>


        <!-- CONTENIDO -->
        <main class="content">
            <div class="page-header">
                <h2><i class="fas fa-edit me-2"></i> Editar Perfil - Paseador</h2>
                <div class="d-flex gap-2">
                    <a href="Perfil.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                    <a href="Dashboard.php" class="btn btn-light btn-sm text-success">
                        <i class="fas fa-home me-1"></i> Panel
                    </a>
                </div>
            </div>

            <?php if ($mensaje): ?><div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <!-- Columna izquierda -->
                    <div class="col-lg-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <?php
                                $src = $fotoActual ? (str_starts_with($fotoActual, 'http') ? $fotoActual : (BASE_URL . $fotoActual)) : (ASSETS_URL . '/images/user-placeholder.png');
                                ?>
                                <img id="previewFoto" src="<?= htmlspecialchars($src) ?>" class="rounded-circle mb-3" style="width:140px;height:140px;object-fit:cover;">
                                <div class="mb-3 text-start">
                                    <label for="foto_perfil" class="form-label">Foto de perfil</label>
                                    <input class="form-control" type="file" id="foto_perfil" name="foto_perfil" accept="image/jpeg,image/png,image/webp">
                                </div>
                                <div class="mb-3 text-start">
                                    <label for="nombre" class="form-label">Nombre completo</label>
                                    <input type="text" class="form-control" name="nombre" value="<?= htmlspecialchars($usuario['nombre']) ?>" required>
                                </div>
                                <div class="mb-3 text-start">
                                    <label for="email" class="form-label">Correo electrónico</label>
                                    <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" required>
                                </div>
                                <div class="mb-3 text-start">
                                    <label for="fecha_nacimiento" class="form-label">Fecha de nacimiento</label>
                                    <input type="date" class="form-control" name="fecha_nacimiento" value="<?= htmlspecialchars($fechaNacActual) ?>" max="<?= date('Y-m-d') ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Columna derecha -->
                    <div class="col-lg-8">
                        <div class="card mb-3">
                            <div class="card-header"><i class="fa-solid fa-map-location-dot me-2"></i> Dirección y zonas</div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="telefono" class="form-label">Teléfono</label>
                                        <input type="text" class="form-control" name="telefono" value="<?= htmlspecialchars($usuario['telefono'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="departamento" class="form-label">Departamento</label>
                                        <select class="form-select" name="departamento" required>
                                            <option value="">Seleccione…</option>
                                            <?php foreach ($DEPARTAMENTOS as $dep): ?>
                                                <option value="<?= htmlspecialchars($dep) ?>" <?= $depActual === $dep ? 'selected' : '' ?>><?= htmlspecialchars($dep) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label for="direccion" class="form-label">Referencia / Complemento</label>
                                        <input type="text" class="form-control" name="direccion" value="<?= htmlspecialchars($usuario['direccion'] ?? '') ?>">
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label for="experiencia" class="form-label">Experiencia</label>
                                        <textarea class="form-control" name="experiencia" rows="3"><?= htmlspecialchars($usuario['experiencia'] ?? '') ?></textarea>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <label class="form-label"><i class="fa-solid fa-map me-2"></i>Zonas de trabajo</label>
                                    <input type="text" class="form-control mb-2" id="nuevaZona" placeholder="Ejemplo: Central - San Lorenzo">
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="btnAgregarZona">
                                        <i class="fa-solid fa-plus me-1"></i> Agregar Zona
                                    </button>
                                    <div id="zonasSeleccionadas" class="mt-3"></div>
                                    <input type="hidden" name="zona_json" id="zona_json" value="<?= htmlspecialchars(json_encode($zonasActuales, JSON_UNESCAPED_UNICODE)) ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save me-2"></i> Guardar Cambios
                </button>
            </form>
        </main>
    </div>

    <footer>© <?= date('Y') ?> Jaguata — Todos los derechos reservados.</footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Previsualización de imagen
        document.getElementById('foto_perfil').addEventListener('change', e => {
            const file = e.target.files[0];
            if (file) document.getElementById('previewFoto').src = URL.createObjectURL(file);
        });

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

        btnAdd.addEventListener('click', () => {
            let zonas = [];
            try {
                zonas = JSON.parse(inputZonas.value) || [];
            } catch {
                zonas = [];
            }
            const z = nuevaZona.value.trim();
            if (!z) return alert('Ingrese una zona.');
            if (!zonas.includes(z)) zonas.push(z);
            inputZonas.value = JSON.stringify(zonas);
            nuevaZona.value = '';
            renderZonas();
        });

        renderZonas();
    </script>
</body>

</html>