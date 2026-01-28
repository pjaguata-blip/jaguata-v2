<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/AuthController.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/UsuarioController.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Validaciones.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\UsuarioController;
use Jaguata\Helpers\Validaciones;

AppConfig::init();

$auth = new AuthController();
$auth->checkRole('admin');

function h(?string $v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

/* ID usuario */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    exit('<h3 style="color:red; text-align:center;">ID de usuario no válido</h3>');
}

$controller = new UsuarioController();
$usuario    = $controller->getById($id);

if (!$usuario) {
    http_response_code(404);
    exit('<h3 style="color:red; text-align:center;">Usuario no encontrado</h3>');
}

$rolActual = strtolower(trim((string)(
    $usuario['rol']
    ?? $usuario['usu_rol']
    ?? $usuario['rol_usuario']
    ?? $usuario['tipo']
    ?? ''
)));
if ($rolActual === '') $rolActual = 'dueno';

$estadoActual = strtolower(trim((string)($usuario['estado'] ?? 'pendiente')));
if ($estadoActual === '') $estadoActual = 'pendiente';

/* ====== DOCUMENTOS (mismo criterio que Perfil) ====== */
function urlDoc(?string $valorBD): ?string
{
    $valorBD = trim((string)$valorBD);
    if ($valorBD === '') return null;

    $valorBD = str_replace('\\', '/', $valorBD);
    $valorBD = ltrim($valorBD, '/');

    if (str_starts_with($valorBD, 'assets/')) {
        return rtrim(BASE_URL, '/') . '/public/' . $valorBD;
    }
    if (str_starts_with($valorBD, 'public/')) {
        return rtrim(BASE_URL, '/') . '/' . $valorBD;
    }
    return rtrim(BASE_URL, '/') . '/public/assets/uploads/documentos/' . $valorBD;
}

$docsConfig = [
    'foto_cedula_frente'       => 'Cédula (frente)',
    'foto_cedula_dorso'        => 'Cédula (dorso)',
    'foto_selfie'              => 'Selfie con cédula',
    'certificado_antecedentes' => 'Certificado de antecedentes',
];

$documentos = [];
foreach ($docsConfig as $campo => $label) {
    $file = $usuario[$campo] ?? null;
    $url  = urlDoc($file);

    if ($file && $url) {
        $ext      = strtolower(pathinfo((string)$file, PATHINFO_EXTENSION));
        $esImagen = in_array($ext, ['jpg','jpeg','png','gif','webp'], true);

        $documentos[] = [
            'label'    => $label,
            'file'     => (string)$file,
            'url'      => $url,
            'esImagen' => $esImagen,
        ];
    }
}

/* ====== POST: guardar ====== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $csrfPost = $_POST['csrf_token'] ?? '';
    if (!Validaciones::verificarCSRF($csrfPost)) {
        Session::setError('Token inválido. Recargá la página e intentá de nuevo.');
        header('Location: ' . BASE_URL . "/features/admin/editar_usuario.php?id={$id}");
        exit;
    }

    $nombre = trim((string)($_POST['nombre'] ?? ''));
    $email  = trim((string)($_POST['email'] ?? ''));
    $rol    = strtolower(trim((string)($_POST['rol'] ?? $rolActual)));
    $estado = strtolower(trim((string)($_POST['estado'] ?? $estadoActual)));

    if ($nombre === '' || $email === '') {
        Session::setError('Nombre y correo son obligatorios.');
        header('Location: ' . BASE_URL . "/features/admin/editar_usuario.php?id={$id}");
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        Session::setError('Correo electrónico inválido.');
        header('Location: ' . BASE_URL . "/features/admin/editar_usuario.php?id={$id}");
        exit;
    }

    if (!in_array($rol, ['admin','paseador','dueno'], true)) {
        $rol = $rolActual;
    }
    if (!in_array($estado, ['activo','pendiente','aprobado','rechazado','suspendido','inactivo'], true)) {
        $estado = $estadoActual;
    }

    try {
        $ok = $controller->actualizarUsuario($id, [
            'nombre' => $nombre,
            'email'  => $email,
            'rol'    => $rol,
            'estado' => $estado
        ]);

        if ($ok) {
            Session::setSuccess('Usuario actualizado correctamente ✅');
            header('Location: ' . BASE_URL . "/features/admin/PerfilUsuarioAdmin.php?id={$id}");
            exit;
        }

        Session::setError('No se pudo actualizar el usuario.');
        header('Location: ' . BASE_URL . "/features/admin/editar_usuario.php?id={$id}");
        exit;

    } catch (Throwable $e) {
        error_log('❌ editar_usuario: ' . $e->getMessage());
        Session::setError('Ocurrió un error al guardar los cambios.');
        header('Location: ' . BASE_URL . "/features/admin/editar_usuario.php?id={$id}");
        exit;
    }
}

$error   = Session::getError();
$success = Session::getSuccess();

/* IDs tolerantes */
$usuarioIdLabel = (int)($usuario['usu_id'] ?? $usuario['id'] ?? $id);

/* BaseFeatures (para volver al dashboard si querés) */
$baseFeatures = BASE_URL . '/features/admin';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Editar Usuario - Admin | Jaguata</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <style>
        html, body { overflow-x: hidden; width: 100%; }
        *, *::before, *::after { box-sizing: border-box; }

        :root{ --sidebar-w: 260px; }

        main.main-content{
            margin-left: var(--sidebar-w);
            width: calc(100% - var(--sidebar-w));
            min-height: 100vh;
            padding: 24px;
            overflow-x: hidden;
        }
        @media (max-width: 992px){
            main.main-content{
                margin-left: 0 !important;
                width: 100% !important;
                padding: calc(16px + var(--topbar-h)) 16px 16px !important;
            }
        }

        .mini{ font-size:.88rem; color:#6b7b83; }

        .doc-grid{ display:grid; grid-template-columns: 1fr; gap: 12px; }
        @media(min-width: 576px){ .doc-grid{ grid-template-columns: 1fr 1fr; } }

        .doc-item{
            background:#fff;
            border:1px solid rgba(0,0,0,.06);
            border-radius: 16px;
            padding: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,.04);
        }
        .doc-thumb-wrapper{
            position: relative;
            border-radius: 14px;
            overflow: hidden;
            background:#f4f6f9;
            min-height: 140px;
            display:flex;
            align-items:center;
            justify-content:center;
        }
        .doc-thumb-img{
            width: 100%;
            height: 180px;
            object-fit: cover;
            display:block;
        }
        .doc-label-pill{
            position:absolute;
            left:10px;
            bottom:10px;
            background: rgba(60,98,85,.92);
            color:#fff;
            font-size:.78rem;
            padding: 6px 10px;
            border-radius: 999px;
            display:flex;
            align-items:center;
            gap:6px;
        }
        .doc-file-icon{ text-align:center; color:#3c6255; }
        .doc-file-icon i{ font-size: 42px; }
        .doc-file-icon small{ display:block; margin-top:6px; font-weight:700; opacity:.8; }

        .btn-ver-doc{
            background:#3c6255;
            color:#fff;
            border: none;
        }
        .btn-ver-doc:hover{ opacity:.92; color:#fff; }
    </style>
</head>

<body class="page-dashboard-admin">

<?php include __DIR__ . '/../../src/Templates/SidebarAdmin.php'; ?>

<main class="main-content">
    <div class="container-fluid p-0 py-2">

        <div class="header-box header-dashboard mb-3 d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h1 class="fw-bold mb-1">
                    <i class="fas fa-user-gear me-2"></i> Editar Usuario
                </h1>
                <p class="mb-0 text-white-50">Modificá los datos y revisá la documentación del usuario 🐾</p>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="<?= BASE_URL; ?>/features/admin/Usuarios.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
                <a href="<?= BASE_URL; ?>/features/admin/PerfilUsuarioAdmin.php?id=<?= $usuarioIdLabel; ?>" class="btn btn-light btn-sm">
                    <i class="fas fa-id-badge me-1"></i> Perfil
                </a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i><?= h($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?= h($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-3">

            <div class="col-lg-8">
                <div class="section-card">
                    <div class="section-header">
                        <i class="fas fa-user me-2"></i>
                        Usuario #<?= $usuarioIdLabel; ?> — <?= h($usuario['nombre'] ?? ''); ?>
                    </div>

                    <div class="section-body">
                        <form method="POST" class="m-0">
                            <input type="hidden" name="csrf_token" value="<?= h(Validaciones::generarCSRF()); ?>">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Nombre</label>
                                    <input type="text" name="nombre" class="form-control"
                                           value="<?= h($usuario['nombre'] ?? ''); ?>" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Correo electrónico</label>
                                    <input type="email" name="email" class="form-control"
                                           value="<?= h($usuario['email'] ?? ''); ?>" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Rol</label>
                                    <select name="rol" class="form-select">
                                        <option value="admin"    <?= $rolActual === 'admin'    ? 'selected' : ''; ?>>Admin</option>
                                        <option value="paseador" <?= $rolActual === 'paseador' ? 'selected' : ''; ?>>Paseador</option>
                                        <option value="dueno"    <?= $rolActual === 'dueno'    ? 'selected' : ''; ?>>Dueño</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Estado</label>
                                    <select name="estado" class="form-select">
                                        <option value="activo"     <?= $estadoActual === 'activo'     ? 'selected' : ''; ?>>Activo</option>
                                        <option value="pendiente"  <?= $estadoActual === 'pendiente'  ? 'selected' : ''; ?>>Pendiente</option>
                                        <option value="aprobado"   <?= $estadoActual === 'aprobado'   ? 'selected' : ''; ?>>Aprobado</option>
                                        <option value="rechazado"  <?= $estadoActual === 'rechazado'  ? 'selected' : ''; ?>>Rechazado</option>
                                        <option value="suspendido" <?= $estadoActual === 'suspendido' ? 'selected' : ''; ?>>Suspendido</option>
                                        <option value="inactivo"   <?= $estadoActual === 'inactivo'   ? 'selected' : ''; ?>>Inactivo</option>
                                    </select>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2 flex-wrap mt-3">
                                <a href="<?= BASE_URL; ?>/features/admin/Usuarios.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save me-1"></i> Guardar cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="section-card">
                    <div class="section-header">
                        <i class="fas fa-file-shield me-2"></i> Documentos de verificación
                    </div>

                    <div class="section-body">
                        <?php if (!empty($documentos)): ?>
                            <p class="small text-muted mb-3">Archivos subidos durante el registro del usuario.</p>

                            <div class="doc-grid">
                                <?php foreach ($documentos as $doc): ?>
                                    <?php
                                        $label = h($doc['label']);
                                        $url   = h($doc['url']);
                                        $file  = h($doc['file']);
                                        $esImg = (bool)$doc['esImagen'];
                                        $ext   = strtoupper(pathinfo($file, PATHINFO_EXTENSION));
                                    ?>
                                    <div class="doc-item">
                                        <div class="doc-thumb-wrapper">
                                            <?php if ($esImg): ?>
                                                <img src="<?= $url; ?>" alt="<?= $label; ?>" class="doc-thumb-img">
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
                                            <a href="<?= $url; ?>" target="_blank" class="btn btn-ver-doc flex-grow-1" rel="noopener">
                                                <i class="fas fa-eye me-1"></i> Ver
                                            </a>
                                            <a href="<?= $url; ?>" download class="btn btn-outline-secondary">
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

        </div>

        <footer class="mt-4 text-center text-muted small">
            © <?= date('Y'); ?> Jaguata — Panel de Administración
        </footer>

    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('.sidebar');
    const btnToggle = document.getElementById('btnSidebarToggle');
    if (btnToggle && sidebar) btnToggle.addEventListener('click', () => sidebar.classList.toggle('show'));
});
</script>
</body>
</html>
