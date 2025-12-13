<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Models/Usuario.php';
require_once dirname(__DIR__, 2) . '/src/Models/Calificacion.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Models\Usuario;
use Jaguata\Models\Calificacion;

AppConfig::init();

// 🔒 Solo admin
if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'admin') {
    header('Location: ' . BASE_URL . '/public/login.php?error=unauthorized');
    exit;
}

function h(?string $v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

// ID usuario
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    exit('<h3 style="color:red; text-align:center;">ID de usuario no válido</h3>');
}

$usuarioModel = new Usuario();
$usuario = $usuarioModel->find($id);

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

// ====== CALIFICACIONES / REPUTACIÓN ======
$rolUsuario = strtolower((string)($usuario['rol'] ?? ''));
$mostrarCalificaciones = in_array($rolUsuario, ['dueno', 'paseador'], true);

$promedioUsuario = null;
$totalOpiniones  = 0;
$opiniones       = [];

// ⚠️ Mapeo según tu sistema actual:
// paseador => tipo 'paseador'
// dueno    => tipo 'mascota' (así lo venías usando)
$tipoCalif = ($rolUsuario === 'paseador') ? 'paseador' : 'mascota';

if ($mostrarCalificaciones) {
    $calificacionModel = new Calificacion();
    $resumen = $calificacionModel->resumenPorRated((int)$usuario['usu_id'], $tipoCalif);
    $promedioUsuario = $resumen['promedio'] ?? null;
    $totalOpiniones  = (int)($resumen['total'] ?? 0);
    $opiniones = $calificacionModel->opinionesPorRated((int)$usuario['usu_id'], $tipoCalif, 5);
}

// ====== ESTADO ======
$estado = strtolower((string)($usuario['estado'] ?? 'pendiente'));
$badgeEstado = match ($estado) {
    'aprobado'   => 'estado-aprobado',
    'activo'     => 'estado-activo',
    'pendiente'  => 'estado-pendiente',
    'rechazado'  => 'estado-rechazado',
    'suspendido' => 'estado-suspendido',
    'inactivo'   => 'estado-inactivo',
    default      => 'estado-pendiente'
};
$estadoLabel = ucfirst($estado);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Perfil de Usuario - Admin | Jaguata</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">
</head>

<body>

    <?php include __DIR__ . '/../../src/Templates/SidebarAdmin.php'; ?>

    <button class="btn btn-outline-secondary d-md-none ms-2 mt-3" id="toggleSidebar">
        <i class="fas fa-bars"></i>
    </button>

    <main>
        <div class="py-4 container-fluid">

            <!-- HEADER -->
            <div class="header-box header-dashboard mb-4 d-flex flex-wrap justify-content-between align-items-center">
                <div>
                    <h1 class="fw-bold mb-1">
                        <i class="fas fa-user me-2"></i> Perfil de Usuario
                    </h1>
                    <p class="mb-0">
                        Visualizá los datos, documentos y reputación del usuario 👤
                    </p>
                </div>

                <div class="text-end mt-3 mt-md-0 d-flex gap-2 flex-wrap justify-content-end">
                    <a href="<?= BASE_URL; ?>/features/admin/Usuarios.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                    <a href="<?= BASE_URL; ?>/features/admin/editar_usuario.php?id=<?= (int)$usuario['usu_id']; ?>"
                        class="btn btn-light btn-sm">
                        <i class="fas fa-user-pen me-1"></i> Ver / Editar
                    </a>
                </div>
            </div>

            <div class="row g-3">

                <!-- IZQUIERDA -->
                <div class="col-lg-8">

                    <div class="section-card">
                        <div class="section-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <i class="fas fa-id-badge me-2"></i>
                                Usuario #<?= (int)$usuario['usu_id']; ?> — <?= h($usuario['nombre'] ?? ''); ?>
                            </div>

                            <span class="badge-estado <?= h($badgeEstado); ?>">
                                <?= h($estadoLabel); ?>
                            </span>
                        </div>

                        <div class="section-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <span class="text-muted small d-block">Nombre</span>
                                    <strong><?= h($usuario['nombre'] ?? ''); ?></strong>
                                </div>

                                <div class="col-md-6">
                                    <span class="text-muted small d-block">Email</span>
                                    <strong><?= h($usuario['email'] ?? ''); ?></strong>
                                </div>

                                <div class="col-md-6">
                                    <span class="text-muted small d-block">Rol</span>
                                    <span class="badge bg-info text-dark">
                                        <?= h(ucfirst($rolUsuario ?: 'dueno')); ?>
                                    </span>
                                </div>

                                <div class="col-md-6">
                                    <span class="text-muted small d-block">Registrado</span>
                                    <strong><?= h(substr((string)($usuario['created_at'] ?? ''), 0, 10)); ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- REPUTACIÓN -->
                    <?php if ($mostrarCalificaciones): ?>
                        <div class="section-card mt-3">
                            <div class="section-header">
                                <i class="fas fa-star-half-alt me-2"></i> Reputación del usuario
                            </div>

                            <div class="section-body">
                                <?php if ($promedioUsuario !== null && $totalOpiniones > 0): ?>
                                    <div class="d-flex flex-column flex-sm-row align-items-center justify-content-between mb-3">
                                        <div>
                                            <div class="display-6 mb-0">
                                                <?= number_format((float)$promedioUsuario, 1, ',', '.'); ?>
                                                <span class="fs-5">/ 5</span>
                                            </div>

                                            <div class="rating-stars fs-4">
                                                <?php
                                                $rounded = (int)round((float)$promedioUsuario);
                                                for ($i = 1; $i <= 5; $i++):
                                                    $cls = $i <= $rounded ? 'fas text-warning' : 'far text-muted';
                                                ?>
                                                    <i class="<?= $cls; ?> fa-star"></i>
                                                <?php endfor; ?>
                                            </div>

                                            <div class="small text-muted">
                                                <?= (int)$totalOpiniones; ?> opinión<?= $totalOpiniones === 1 ? '' : 'es'; ?>
                                            </div>
                                        </div>

                                        <div class="text-muted small mt-2 mt-sm-0 text-center text-sm-end">
                                            Evaluaciones recibidas como <strong><?= h(ucfirst($rolUsuario)); ?></strong>
                                        </div>
                                    </div>

                                    <div class="list-group list-group-flush">
                                        <?php foreach ($opiniones as $op): ?>
                                            <?php
                                            $c = (int)($op['calificacion'] ?? 0);
                                            $fecha = !empty($op['created_at']) ? substr((string)$op['created_at'], 0, 10) : '';
                                            $autor = $op['autor_nombre'] ?? 'Usuario';
                                            $autorEmail = $op['autor_email'] ?? '';
                                            ?>
                                            <div class="list-group-item px-0">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="fw-semibold">
                                                        <?= h($autor); ?>
                                                        <?php if ($autorEmail): ?>
                                                            <span class="text-muted small">(<?= h($autorEmail); ?>)</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <small class="text-muted"><?= h($fecha); ?></small>
                                                </div>

                                                <div class="rating-stars small mb-1">
                                                    <?php for ($i = 1; $i <= 5; $i++):
                                                        $cls = $i <= $c ? 'fas text-warning' : 'far text-muted';
                                                    ?>
                                                        <i class="<?= $cls; ?> fa-star"></i>
                                                    <?php endfor; ?>
                                                </div>

                                                <?php if (!empty($op['comentario'])): ?>
                                                    <div class="text-muted small">
                                                        “<?= h($op['comentario']); ?>”
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (!empty($op['paseo_id'])): ?>
                                                    <div class="text-muted small mt-1">
                                                        Paseo #<?= (int)$op['paseo_id']; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                <?php else: ?>
                                    <div class="alert alert-light text-center mb-0">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Este usuario todavía no tiene calificaciones registradas.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>

                <!-- DERECHA: DOCUMENTOS -->
                <div class="col-lg-4">
                    <div class="section-card">
                        <div class="section-header">
                            <i class="fas fa-file-shield me-2"></i> Documentos de verificación
                        </div>

                        <div class="section-body">
                            <?php if (!empty($documentos)): ?>
                                <p class="small text-muted mb-3">
                                    Archivos subidos durante el registro del usuario.
                                </p>

                                <div class="doc-grid">
                                    <?php foreach ($documentos as $doc): ?>
                                        <?php
                                        $label = h($doc['label']);
                                        $url   = h($doc['url']);
                                        $file  = h($doc['file']);
                                        $esImg = (bool)$doc['esImagen'];
                                        $ext   = strtoupper(pathinfo($file, PATHINFO_EXTENSION));
                                        ?>
                                        <div class="doc-item <?= $esImg ? 'doc-item-image' : 'doc-item-file' ?>">
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
                                                <a href="<?= $url; ?>" target="_blank" class="btn btn-ver-doc flex-grow-1">
                                                    <i class="fas fa-eye me-1"></i> Ver
                                                </a>
                                                <a href="<?= $url; ?>" download class="btn btn-outline-secondary btn-descargar-doc">
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
        document.getElementById('toggleSidebar')?.addEventListener('click', function() {
            document.getElementById('sidebar')?.classList.toggle('sidebar-open');
        });
    </script>
</body>

</html>