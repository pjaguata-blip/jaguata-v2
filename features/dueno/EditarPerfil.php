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

/* Traer datos actuales desde la BD (BaseModel::find) */
$usuario = $usuarioModel->find((int)$usuarioId);
if (!$usuario) {
    http_response_code(404);
    exit('Usuario no encontrado.');
}

/* Catálogos (ejemplo, podés ampliar) */
$DEPARTAMENTOS = ['Central', 'Alto Paraná', 'Itapúa', 'Cordillera', 'Guairá', 'Caaguazú'];
$CIUDADES = [
    'Central'      => ['Asunción', 'San Lorenzo', 'Luque', 'Otra'],
    'Alto Paraná'  => ['Ciudad del Este', 'Minga Guazú', 'Otra'],
];

/* Datos actuales para el formulario */
$nombre     = $usuario['nombre']       ?? '';
$email      = $usuario['email']        ?? '';
$telefono   = $usuario['telefono']     ?? '';
$direccion  = $usuario['direccion']    ?? '';
$depActual  = $usuario['departamento'] ?? '';
$ciudadAct  = $usuario['ciudad']       ?? '';
$fotoActual = $usuario['foto_perfil']  ?? '';

$msg   = '';
$error = '';

/* 📨 Procesar POST (guardar cambios) */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre    = trim($_POST['nombre'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $depActual = trim($_POST['departamento'] ?? '');
    $ciudadAct = trim($_POST['ciudad'] ?? '');

    if ($nombre === '' || $email === '') {
        $error = 'Nombre y correo son obligatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Correo electrónico inválido.';
    } else {
        $fotoNueva = null;

        /* 👇 Manejo de foto de perfil (upload a /assets/uploads/perfiles) */
        if (!empty($_FILES['foto']['name']) && is_uploaded_file($_FILES['foto']['tmp_name'])) {
            $file   = $_FILES['foto'];
            $permit = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/jpg'  => 'jpg',
            ];

            $mime = @mime_content_type($file['tmp_name']) ?: $file['type'];

            if (isset($permit[$mime])) {
                $ext = $permit[$mime];

                // Ruta física en el proyecto: /jaguata/assets/uploads/perfiles
                $dir = __DIR__ . '/../../assets/uploads/perfiles';
                if (!is_dir($dir)) {
                    mkdir($dir, 0775, true);
                }

                $fname = 'u' . $usuarioId . '-' . date('YmdHis') . '.' . $ext;
                $dest  = $dir . '/' . $fname;

                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    // Ruta pública que se guarda en la BD
                    $fotoNueva = '/assets/uploads/perfiles/' . $fname;
                }
            }
        }

        /* Datos a actualizar en la BD (tabla usuarios) */
        $data = [
            'nombre'       => $nombre,
            'email'        => $email,
            'telefono'     => $telefono,
            'direccion'    => $direccion,
            'departamento' => $depActual,
            'ciudad'       => $ciudadAct,
        ];

        if ($fotoNueva) {
            $data['foto_perfil'] = $fotoNueva;
        }

        // ✅ Usamos BaseModel::update sobre el modelo Usuario
        if ($usuarioModel->update((int)$usuarioId, $data)) {
            $msg      = 'Perfil actualizado correctamente.';
            $usuario  = $usuarioModel->find((int)$usuarioId);
            $fotoActual = $usuario['foto_perfil'] ?? $fotoActual;
        } else {
            $error = 'No se pudo guardar los cambios.';
        }
    }
}

/* Rutas base para enlaces */
$rolMenu      = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures = BASE_URL . "/features/{$rolMenu}";

/* Helper de escape */
function h(?string $v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

/* Foto para el preview */
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

    <!-- Bootstrap + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <!-- 🎨 Estilos globales Jaguata (mismo CSS que todo el sistema) -->
    <link href="<?= ASSETS_URL; ?>/css/jaguata-theme.css" rel="stylesheet">
</head>

<body>
    <!-- Sidebar dueño unificado -->
    <?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>

    <!-- Botón hamburguesa para mobile (usa .sidebar-open del CSS) -->
    <button class="btn btn-outline-secondary d-md-none ms-2 mt-3" id="toggleSidebar">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Contenido -->
    <main>
        <div class="py-4">

            <!-- Header usando header-box + header-dashboard del CSS global -->
            <div class="header-box header-dashboard mb-4">
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

            <!-- Mensajes -->
            <?php if ($msg): ?>
                <div class="alert alert-success"><?= h($msg); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= h($error); ?></div>
            <?php endif; ?>

            <!-- Formulario -->
            <form method="POST" enctype="multipart/form-data">
                <div class="row g-3">
                    <!-- Col izquierda: foto + datos básicos -->
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

                                <div class="mb-0">
                                    <label class="form-label">Teléfono</label>
                                    <input type="text" class="form-control" name="telefono" value="<?= h($telefono); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Col derecha: dirección -->
                    <div class="col-lg-8">
                        <div class="section-card">
                            <div class="section-header">
                                <i class="fas fa-map-marker-alt me-2"></i>Dirección
                            </div>
                            <div class="section-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Departamento</label>
                                        <select class="form-select" name="departamento">
                                            <option value="">Seleccione…</option>
                                            <?php foreach ($DEPARTAMENTOS as $d): ?>
                                                <option value="<?= h($d); ?>" <?= $d === $depActual ? 'selected' : ''; ?>>
                                                    <?= h($d); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Ciudad</label>
                                        <select class="form-select" name="ciudad">
                                            <option value="">Seleccione…</option>
                                            <?php foreach (($CIUDADES[$depActual] ?? []) as $c): ?>
                                                <option value="<?= h($c); ?>" <?= $c === $ciudadAct ? 'selected' : ''; ?>>
                                                    <?= h($c); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Referencia / Dirección</label>
                                        <input type="text" class="form-control" name="direccion" value="<?= h($direccion); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botón guardar -->
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

    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar en mobile (usa la clase .sidebar-open de tu CSS global)
        document.getElementById('toggleSidebar')?.addEventListener('click', function() {
            document.getElementById('sidebar')?.classList.toggle('sidebar-open');
        });

        // Preview de la foto seleccionada
        document.getElementById('foto')?.addEventListener('change', function(e) {
            const file = e.target.files?.[0];
            if (!file) return;
            const url = URL.createObjectURL(file);
            const img = document.getElementById('previewFoto');
            if (img) img.src = url;
        });
    </script>
</body>

</html>