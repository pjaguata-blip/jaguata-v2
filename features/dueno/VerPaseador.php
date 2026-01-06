<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Helpers\Session;

AppConfig::init();

/* 🔒 Auth */
$auth = new AuthController();
$auth->checkRole('dueno');

/* 🔒 (Recomendado: igual a tu Dashboard) */
if (Session::getUsuarioEstado() !== 'aprobado') {
    Session::setError('Tu cuenta aún no fue aprobada.');
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}

/* Helpers */
function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function resolveFotoUrl(?string $foto): string
{
    $foto = trim((string)$foto);
    if ($foto === '') return '';
    if (preg_match('~^https?://~i', $foto)) return $foto;

    $foto = ltrim(str_replace('\\', '/', $foto), '/');
    return BASE_URL . '/' . $foto;
}

function renderStars(float $rate): string
{
    $rate  = max(0, min(5, $rate));
    $full  = (int)floor($rate);
    $half  = (($rate - $full) >= 0.5) ? 1 : 0;
    $empty = 5 - $full - $half;

    $html = '';
    for ($i = 0; $i < $full; $i++) $html .= '<i class="fas fa-star text-warning"></i>';
    if ($half) $html .= '<i class="fas fa-star-half-stroke text-warning"></i>';
    for ($i = 0; $i < $empty; $i++) $html .= '<i class="far fa-star text-warning"></i>';
    return $html;
}

function normPhone(string $tel): string
{
    // deja solo dígitos
    $digits = preg_replace('/\D+/', '', $tel) ?? '';
    return $digits ?: '';
}

function whatsappUrl(?string $tel, string $nombre = ''): string
{
    $digits = normPhone((string)$tel);
    if ($digits === '') return '';

    // Paraguay: si viene con 0 al inicio (ej 0984...), lo pasamos a 595984...
    if (str_starts_with($digits, '0')) {
        $digits = '595' . substr($digits, 1);
    } elseif (!str_starts_with($digits, '595') && strlen($digits) <= 10) {
        // si no tiene prefijo y parece local, asumimos PY
        $digits = '595' . $digits;
    }

    $msg = "Hola! Vi tu perfil en Jaguata y me gustaría consultar por un paseo 😊🐾";
    if ($nombre !== '') {
        $msg = "Hola {$nombre}! Vi tu perfil en Jaguata y me gustaría consultar por un paseo 😊🐾";
    }

    return "https://wa.me/{$digits}?text=" . rawurlencode($msg);
}

/* ID paseador */
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['error'] = 'ID de paseador inválido.';
    header('Location: ' . BASE_URL . '/features/dueno/BuscarPaseadores.php');
    exit;
}

/* Rutas UI */
$baseFeatures = BASE_URL . "/features/dueno";
$volverUrl    = $baseFeatures . "/BuscarPaseadores.php";
$solicitarUrl = $baseFeatures . "/SolicitarPaseo.php?paseador_id=" . $id;

/* DB */
$pdo = AppConfig::db();

/* ✅ Paseador + rating real */
$sql = "
    SELECT 
        p.*,
        COALESCE(r.promedio, 0) AS calificacion_promedio,
        COALESCE(r.total, 0)    AS calificacion_total
    FROM paseadores p
    LEFT JOIN (
        SELECT rated_id,
               ROUND(AVG(calificacion), 1) AS promedio,
               COUNT(*) AS total
        FROM calificaciones
        WHERE tipo = 'paseador'
        GROUP BY rated_id
    ) r ON r.rated_id = p.paseador_id
    WHERE p.paseador_id = :id
    LIMIT 1
";
$st = $pdo->prepare($sql);
$st->bindValue(':id', $id, PDO::PARAM_INT);
$st->execute();
$u = $st->fetch(PDO::FETCH_ASSOC);

/* UI data */
$nombre  = (string)($u['nombre'] ?? 'Paseador');
$zona    = (string)($u['zona'] ?? '');
$ciudad  = (string)($u['ciudad'] ?? '');
$barrio  = (string)($u['barrio'] ?? '');
$tel     = (string)($u['telefono'] ?? '');
$desc    = (string)($u['descripcion'] ?? '');
$precio  = (float)($u['precio_hora'] ?? 0);
$paseos  = (int)($u['total_paseos'] ?? 0);

$rate    = (float)($u['calificacion_promedio'] ?? 0);
$rateCnt = (int)($u['calificacion_total'] ?? 0);

$foto    = resolveFotoUrl((string)($u['foto_url'] ?? ''));
$waLink  = whatsappUrl($tel, $nombre);

/* ✅ Reseñas (últimas 10)
   Ajustá nombres de columnas si tu tabla difiere:
   - calificaciones: calificacion, comentario, created_at, rater_id (o usuario_id)
   - usuarios: usu_id, nombre
*/
$reseñas = [];
if ($u) {
    try {
        $sqlReviews = "
            SELECT 
                c.calificacion,
                c.comentario,
                c.created_at,
                u.nombre AS autor
            FROM calificaciones c
            LEFT JOIN usuarios u 
                ON u.usu_id = c.rater_id
            WHERE c.tipo = 'paseador'
              AND c.rated_id = :id
            ORDER BY c.created_at DESC
            LIMIT 10
        ";
        $rv = $pdo->prepare($sqlReviews);
        $rv->bindValue(':id', $id, PDO::PARAM_INT);
        $rv->execute();
        $reseñas = $rv->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        // Si tu esquema no coincide, no rompemos la pantalla
        $reseñas = [];
    }
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Detalle Paseador - Jaguata</title>

    <!-- CSS global -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <style>
        html, body { height: 100%; }
        body { background: var(--gris-fondo, #f4f6f9); }

        /* ✅ Layout IGUAL al Dashboard */
        main.main-content{
            margin-left: 260px;
            min-height: 100vh;
            padding: 24px;
        }
        @media (max-width: 768px){
            main.main-content{
                margin-left: 0;
                margin-top: 0 !important;
                width: 100% !important;
                padding: calc(16px + var(--topbar-h)) 16px 16px !important;
            }
        }

        /* ✅ HERO cover horizontal */
        .walker-hero{
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 12px 30px rgba(0,0,0,.06);
            position: relative;
            min-height: 190px;
            background:
              linear-gradient(90deg, rgba(25,193,146,.95) 0%, rgba(6,75,43,.92) 100%);
        }
        .walker-hero.has-photo{
            background:
              linear-gradient(90deg, rgba(25,193,146,.80) 0%, rgba(6,75,43,.80) 100%),
              var(--hero-photo);
            background-size: cover;
            background-position: center;
        }
        .walker-hero-inner{
            padding: 18px 20px;
            color:#fff;
            display:flex;
            gap:14px;
            align-items:flex-end;
            justify-content: space-between;
            min-height: 190px;
        }

        .walker-avatar{
            width: 92px;
            height: 92px;
            border-radius: 20px;
            object-fit: cover;
            border: 2px solid rgba(255,255,255,.75);
            background: rgba(255,255,255,.15);
        }
        .walker-avatar-fallback{
            width: 92px;
            height: 92px;
            border-radius: 20px;
            display:flex;
            align-items:center;
            justify-content:center;
            border: 2px solid rgba(255,255,255,.35);
            background: rgba(255,255,255,.10);
        }

        /* chips */
        .chip{
            display:inline-flex;
            gap:.45rem;
            align-items:center;
            padding:.45rem .75rem;
            border-radius:999px;
            border:1px solid rgba(15,23,42,.10);
            background:#fff;
            font-size:.90rem;
            color:#0f172a;
        }
        .chip i{ opacity:.85; }

        .desc-box{
            background: var(--gris-fondo, #f4f6f9);
            border: 1px solid rgba(60,98,85,.12);
            border-radius: 14px;
            padding: 14px;
            white-space: pre-wrap;
            line-height: 1.55;
            color:#1f2a28;
        }

        .review-item{
            border:1px solid rgba(15,23,42,.08);
            border-radius: 14px;
            padding: 12px 14px;
            background:#fff;
        }
    </style>
</head>

<body class="page-dashboard-dueno">

    <!-- Sidebar Dueño unificado -->
    <?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>

    <main class="main-content">
        <div class="py-0">

            <!-- Header -->
            <div class="header-box header-paseos mb-3 d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="fw-bold mb-1">
                        <i class="fas fa-user-check me-2"></i>Detalle del Paseador
                    </h1>
                    <p class="mb-0">Revisá perfil, calificación, precio y zona antes de solicitar 🐾</p>
                </div>

                <div class="d-flex gap-2 align-items-center">
                    <a href="<?= h($volverUrl) ?>" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>
            </div>

            <?php if (!$u): ?>
                <div class="alert alert-warning shadow-sm">
                    <i class="fas fa-triangle-exclamation me-2"></i>Paseador no encontrado.
                </div>
            <?php else: ?>

                <!-- HERO -->
                <?php
                $heroClass = $foto !== '' ? 'walker-hero has-photo' : 'walker-hero';
                $heroStyle = $foto !== '' ? "style=\"--hero-photo:url('" . h($foto) . "');\"" : '';
                ?>
                <div class="<?= $heroClass; ?> mb-3" <?= $heroStyle; ?>>
                    <div class="walker-hero-inner flex-wrap">
                        <div class="d-flex gap-3 align-items-end flex-wrap">
                            <?php if ($foto !== ''): ?>
                                <img
                                    src="<?= h($foto) ?>"
                                    alt="Foto de <?= h($nombre) ?>"
                                    class="walker-avatar"
                                    loading="lazy"
                                    onerror="this.onerror=null; this.style.display='none'; this.parentElement.querySelector('.walker-avatar-fallback')?.classList.remove('d-none');">
                                <div class="walker-avatar-fallback d-none">
                                    <i class="fas fa-user-circle fa-2x text-white-50"></i>
                                </div>
                            <?php else: ?>
                                <div class="walker-avatar-fallback">
                                    <i class="fas fa-user-circle fa-2x text-white-50"></i>
                                </div>
                            <?php endif; ?>

                            <div>
                                <h3 class="mb-1 fw-bold"><?= h($nombre); ?></h3>
                                <div class="opacity-75 mb-2">
                                    <i class="fas fa-location-dot me-1"></i>
                                    <?= h($zona !== '' ? $zona : 'Sin zona'); ?>
                                </div>

                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="chip">
                                        <span class="me-1"><?= renderStars($rate); ?></span>
                                        <strong><?= number_format($rate, 1); ?></strong>
                                        <span class="text-muted">(<?= (int)$rateCnt; ?>)</span>
                                    </span>

                                    <span class="chip">
                                        <i class="fas fa-money-bill-wave"></i>
                                        ₲<?= number_format($precio, 0, ',', '.'); ?>/hora
                                    </span>

                                    <span class="chip">
                                        <i class="fas fa-walking"></i>
                                        Paseos: <?= (int)$paseos; ?>
                                    </span>

                                    <?php if (trim($ciudad . $barrio) !== ''): ?>
                                        <span class="chip">
                                            <i class="fas fa-city"></i>
                                            <?= h(trim($ciudad . ' ' . $barrio)); ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($tel !== ''): ?>
                                        <span class="chip">
                                            <i class="fas fa-phone"></i>
                                            <?= h($tel); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 align-items-center mt-2">
                            <?php if ($waLink !== ''): ?>
                                <a class="btn btn-light btn-sm fw-semibold"
                                   href="<?= h($waLink) ?>"
                                   target="_blank" rel="noopener">
                                    <i class="fab fa-whatsapp me-1"></i> WhatsApp
                                </a>
                            <?php endif; ?>

                            <a class="btn btn-light btn-sm fw-semibold" href="<?= h($solicitarUrl) ?>">
                                <i class="fas fa-calendar-plus me-1"></i> Solicitar paseo
                            </a>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <!-- Sobre mí -->
                    <div class="col-lg-8">
                        <div class="section-card mb-3">
                            <div class="section-header">
                                <i class="fas fa-circle-info me-2"></i>Sobre mí
                            </div>
                            <div class="section-body">
                                <?php if (trim($desc) !== ''): ?>
                                    <div class="desc-box"><?= h($desc); ?></div>
                                <?php else: ?>
                                    <div class="desc-box text-muted">Este paseador aún no cargó una descripción.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- ✅ Reseñas -->
                        <div class="section-card">
                            <div class="section-header">
                                <i class="fas fa-comments me-2"></i>Opiniones de dueños
                            </div>
                            <div class="section-body">
                                <?php if (empty($reseñas)): ?>
                                    <p class="text-center text-muted mb-0">Todavía no hay reseñas para este paseador.</p>
                                <?php else: ?>
                                    <div class="d-flex flex-column gap-2">
                                        <?php foreach ($reseñas as $r): ?>
                                            <?php
                                            $rRate = (float)($r['calificacion'] ?? 0);
                                            $autor = (string)($r['autor'] ?? 'Dueño/a');
                                            $com   = (string)($r['comentario'] ?? '');
                                            $fec   = (string)($r['created_at'] ?? '');
                                            ?>
                                            <div class="review-item">
                                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                                    <div class="fw-semibold">
                                                        <i class="fas fa-user me-1 text-muted"></i><?= h($autor); ?>
                                                    </div>
                                                    <div class="small">
                                                        <?= renderStars($rRate); ?>
                                                        <span class="ms-1 fw-semibold"><?= number_format($rRate, 1); ?></span>
                                                    </div>
                                                </div>

                                                <?php if ($com !== ''): ?>
                                                    <div class="mt-2"><?= h($com); ?></div>
                                                <?php else: ?>
                                                    <div class="mt-2 text-muted">Sin comentario.</div>
                                                <?php endif; ?>

                                                <?php if ($fec !== ''): ?>
                                                    <div class="mt-2 small text-muted">
                                                        <i class="far fa-clock me-1"></i><?= date('d/m/Y H:i', strtotime($fec)); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="col-lg-4">
                        <div class="section-card">
                            <div class="section-header">
                                <i class="fas fa-bolt me-2"></i>Acciones
                            </div>
                            <div class="section-body">
                                <div class="d-grid gap-2">
                                    <a class="btn btn-enviar" href="<?= h($solicitarUrl) ?>">
                                        <i class="fas fa-calendar-check me-1"></i> Solicitar paseo
                                    </a>

                                    <?php if ($waLink !== ''): ?>
                                        <a class="btn btn-outline-success" href="<?= h($waLink) ?>" target="_blank" rel="noopener">
                                            <i class="fab fa-whatsapp me-1"></i> Hablar por WhatsApp
                                        </a>
                                    <?php endif; ?>

                                    <a class="btn btn-secondary" href="<?= h($volverUrl) ?>">
                                        <i class="fas fa-arrow-left me-1"></i> Volver a buscar
                                    </a>
                                </div>

                                <hr>

                                <div class="small text-muted">
                                    <div class="mb-1"><i class="fas fa-shield-dog me-2"></i>Elegí paseadores verificados.</div>
                                    <div><i class="fas fa-paw me-2"></i>Solicitá paseos según tu zona y horario.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php endif; ?>

            <footer class="mt-4 text-center text-muted small">
                © <?= date('Y') ?> Jaguata — Panel del Dueño
            </footer>

        </div>
    </main>

    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
