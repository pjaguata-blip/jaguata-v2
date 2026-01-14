<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/UsuarioController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Controllers\UsuarioController;

AppConfig::init();

// 🔒 Seguridad (solo admin)
if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'admin') {
    header('Location: ' . BASE_URL . '/public/login.php?error=unauthorized');
    exit;
}

// ID del usuario a editar
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$controller = new UsuarioController();
$usuario    = $controller->getById($id);
$error      = '';

if (!$usuario) {
    http_response_code(404);
    exit('<h3 style="color:red; text-align:center;">Usuario no encontrado</h3>');
}

// ====== DOCUMENTOS DEL USUARIO (verificaciones) ======
$baseVerifUrl = BASE_URL . '/uploads/verificaciones/';

$docsConfig = [
    'foto_cedula_frente'       => 'Cédula (frente)',
    'foto_cedula_dorso'        => 'Cédula (dorso)',
    'foto_selfie'              => 'Selfie con cédula',
    'certificado_antecedentes' => 'Certificado de antecedentes',
];

$documentos = [];

foreach ($docsConfig as $campo => $label) {
    $file = $usuario[$campo] ?? null;
    if ($file) {
        $ext      = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $esImagen = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
        $documentos[] = [
            'label'    => $label,
            'file'     => $file,
            'url'      => $baseVerifUrl . rawurlencode($file),
            'esImagen' => $esImagen,
        ];
    }
}

// 📨 Procesar POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $rol    = trim($_POST['rol'] ?? '');
    $estado = trim($_POST['estado'] ?? '');

    if ($nombre === '' || $email === '') {
        $error = 'Nombre y correo son obligatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Correo electrónico inválido.';
    } else {
        $ok = $controller->actualizarUsuario($id, [
            'nombre' => $nombre,
            'email'  => $email,
            'rol'    => $rol,
            'estado' => $estado
        ]);

        if ($ok) {
            header('Location: ' . BASE_URL . '/features/admin/Usuarios.php?actualizado=1');
            exit;
        } else {
            $error = "No se pudo actualizar el usuario.";
        }
    }
}

/* Helper de escape */
function h(?string $v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Editar Usuario - Jaguata</title>

    <!-- Bootstrap + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <!-- 🎨 Estilos globales Jaguata -->
    <link href="<?= ASSETS_URL; ?>/css/jaguata-theme.css" rel="stylesheet">
</head>

<body>

    <!-- Sidebar admin -->
    <?php include __DIR__ . '/../../src/Templates/SidebarAdmin.php'; ?>

    <!-- Botón hamburguesa para mobile -->
    <button class="btn btn-outline-secondary d-md-none ms-2 mt-3" id="toggleSidebar">
        <i class="fas fa-bars"></i>
    </button>

    <main>
        <div class="py-2 container-fluid">

            <!-- Header igual estilo que otros módulos admin -->
            <div class="header-box header-dashboard mb-2 d-flex flex-wrap justify-content-between align-items-center">
                <div>
                    <h1 class="fw-bold mb-1">
                        <i class="fas fa-user-gear me-2"></i>Editar Usuario
                    </h1>
                    <p class="mb-0">
                        Modificá los datos y revisá la documentación del usuario 🐾
                    </p>
                </div>
                <div class="text-end mt-3 mt-md-0">
                    <a href="<?= BASE_URL; ?>/features/admin/Usuarios.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Volver al listado
                    </a>
                </div>
            </div>

            <!-- Mensajes -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= h($error); ?></div>
            <?php endif; ?>

            <div class="row g-3">
                <!-- Columna principal: formulario -->
                <div class="col-lg-8">
                    <div class="section-card">
                        <div class="section-header">
                            <i class="fas fa-user me-2"></i>
                           Usuario #<?= $userId; ?> — <?= h($usuario['nombre'] ?? ''); ?>

                        </div>
                        <div class="section-body">
                            <form method="POST">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Nombre</label>
                                        <input
                                            type="text"
                                            name="nombre"
                                            class="form-control"
                                            value="<?= h($usuario['nombre'] ?? ''); ?>"
                                            required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Correo electrónico</label>
                                        <input
                                            type="email"
                                            name="email"
                                            class="form-control"
                                            value="<?= h($usuario['email'] ?? ''); ?>"
                                            required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Rol</label>
                                        <select name="rol" class="form-select">
                                            <option value="admin" <?= ($usuario['rol'] ?? '') === 'admin'    ? 'selected' : ''; ?>>Admin</option>
                                            <option value="paseador" <?= ($usuario['rol'] ?? '') === 'paseador' ? 'selected' : ''; ?>>Paseador</option>
                                            <option value="dueno" <?= ($usuario['rol'] ?? '') === 'dueno'    ? 'selected' : ''; ?>>Dueño</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Estado</label>
                                        <select name="estado" class="form-select">
                                            <option value="activo" <?= ($usuario['estado'] ?? '') === 'activo'     ? 'selected' : ''; ?>>Activo</option>
                                            <option value="pendiente" <?= ($usuario['estado'] ?? '') === 'pendiente'  ? 'selected' : ''; ?>>Pendiente</option>
                                            <option value="aprobado" <?= ($usuario['estado'] ?? '') === 'aprobado'   ? 'selected' : ''; ?>>Aprobado</option>
                                            <option value="rechazado" <?= ($usuario['estado'] ?? '') === 'rechazado'  ? 'selected' : ''; ?>>Rechazado</option>
                                            <option value="suspendido" <?= ($usuario['estado'] ?? '') === 'suspendido' ? 'selected' : ''; ?>>Suspendido</option>
                                            <option value="inactivo" <?= ($usuario['estado'] ?? '') === 'inactivo'   ? 'selected' : ''; ?>>Inactivo</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Botones -->
                                <div class="text-end mt-3 d-flex justify-content-end gap-2 flex-wrap">
                                    <a href="<?= BASE_URL; ?>/features/admin/Usuarios.php"
                                        class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i> Cancelar
                                    </a>
                                    <button type="submit" class="btn-guardar">
                                        <i class="fas fa-save me-1"></i> Guardar cambios
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Columna lateral: documentos -->
                <div class="col-lg-4">
                    <div class="section-card">
                        <div class="section-header">
                            <i class="fas fa-file-shield me-2"></i>Documentos de verificación
                        </div>
                        <div class="section-body">
                            <?php if (!empty($documentos)): ?>
                                <p class="small text-muted mb-3">
                                    Estos archivos fueron subidos en el registro del usuario.
                                </p>

                                <div class="doc-grid">
                                    <?php foreach ($documentos as $doc): ?>
                                        <?php
                                        $label = h($doc['label']);
                                        $url   = h($doc['url']);
                                        $file  = h($doc['file']);
                                        $esImg = $doc['esImagen'];
                                        $ext   = strtoupper(pathinfo($file, PATHINFO_EXTENSION));
                                        ?>
                                        <div class="doc-item <?= $esImg ? 'doc-item-image' : 'doc-item-file' ?>">
                                            <div class="doc-thumb-wrapper">
                                                <?php if ($esImg): ?>
                                                    <img src="<?= $url; ?>"
                                                        alt="<?= $label; ?>"
                                                        class="doc-thumb-img">
                                                    <span class="doc-label-pill">
                                                        <i class="fas fa-id-card-clip me-1"></i><?= $label; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <div class="doc-file-icon">
                                                        <i class="fas fa-file-pdf"></i>
                                                        <small><?= $ext; ?></small>
                                                    </div>
                                                    <span class="doc-label-pill">
                                                        <i class="fas fa-file-alt me-1"></i><?= $label; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>

                                            <div class="d-flex gap-2 mt-2">
                                                <a href="<?= $url; ?>" target="_blank"
                                                    class="btn btn-ver-doc flex-grow-1">
                                                    <i class="fas fa-eye me-1"></i> Ver
                                                </a>
                                                <a href="<?= $url; ?>" download
                                                    class="btn btn-outline-secondary btn-descargar-doc">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                            <?php else: ?>
                                <div class="alert alert-light text-center mb-0">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Este usuario no tiene documentos cargados.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div><!-- row -->

            <footer class="mt-4 text-center text-muted small">
                © <?= date('Y'); ?> Jaguata — Panel de Administración
            </footer>
        </div><!-- py-2 -->
    </main>

    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar en mobile (usa la clase .sidebar-open de tu CSS global)
        document.getElementById('toggleSidebar')?.addEventListener('click', function() {
            document.getElementById('sidebar')?.classList.toggle('sidebar-open');
        });
    </script>
</body>

</html>