<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Helpers\Session;

AppConfig::init();

$auth = new AuthController();
$auth->checkRole('dueno');

/* ===== Helpers ===== */
function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function bindUsedParams(\PDOStatement $st, string $sql, array $params): void
{
    foreach ($params as $k => $v) {
        if (strpos($sql, $k) !== false) {
            $type = is_int($v)
                ? PDO::PARAM_INT
                : (is_null($v) ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $st->bindValue($k, $v, $type);
        }
    }
}

/**
 * ✅ Normaliza la foto del paseador para que SIEMPRE funcione:
 * - si viene URL completa -> se usa tal cual
 * - si viene ruta relativa (uploads/..., public/uploads/...) -> se antepone BASE_URL
 * - si viene vacía -> ''
 */
function resolveFotoUrl(?string $foto): string
{
    $foto = trim((string)$foto);
    if ($foto === '') return '';

    if (preg_match('~^https?://~i', $foto)) return $foto;

    $foto = ltrim(str_replace('\\', '/', $foto), '/');
    return BASE_URL . '/' . $foto;
}

/* ===== Filtros ===== */
$q        = trim($_GET['q'] ?? '');
$zona     = trim($_GET['zona'] ?? '');
$minRate  = $_GET['minRate'] ?? '';
$maxRate  = $_GET['maxRate'] ?? '';
$minPrice = $_GET['minPrice'] ?? '';
$maxPrice = $_GET['maxPrice'] ?? '';
$disp     = $_GET['disp'] ?? '';
$sort     = $_GET['sort'] ?? 'score';
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 12;
$offset   = ($page - 1) * $perPage;

/* ===== Normalización ===== */
$minRateF  = ($minRate === '' ? null : max(0, min(5, (float)$minRate)));
$maxRateF  = ($maxRate === '' ? null : max(0, min(5, (float)$maxRate)));
$minPriceF = ($minPrice === '' ? null : max(0, (float)$minPrice));
$maxPriceF = ($maxPrice === '' ? null : max(0, (float)$maxPrice));
$dispF     = ($disp === '' ? null : (int)$disp);

/* ===== WHERE ===== */
$where = [];
$args  = [];

/* Filtros sobre columnas reales (p.*) */
if ($q !== '') {
    $where[]    = '(p.nombre LIKE :q OR p.descripcion LIKE :q OR p.zona LIKE :q)';
    $args[':q'] = "%$q%";
}

if ($zona !== '') {
    $where[]       = 'p.zona = :zona';
    $args[':zona'] = $zona;
}

if ($minPriceF !== null) {
    $where[]           = 'p.precio_hora >= :minPrice';
    $args[':minPrice'] = $minPriceF;
}

if ($maxPriceF !== null) {
    $where[]           = 'p.precio_hora <= :maxPrice';
    $args[':maxPrice'] = $maxPriceF;
}

if ($dispF !== null) {
    $where[]       = 'p.disponible = :disp';
    $args[':disp'] = $dispF;
}

/* ✅ filtros por calificación (reputación real) - en SQL */
if ($minRateF !== null) {
    $where[]          = 'COALESCE(r.promedio,0) >= :minRate';
    $args[':minRate'] = $minRateF;
}
if ($maxRateF !== null) {
    $where[]          = 'COALESCE(r.promedio,0) <= :maxRate';
    $args[':maxRate'] = $maxRateF;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

/* ===== ORDER BY ===== */
$orderBy = match ($sort) {
    'precio_asc'  => 'p.precio_hora ASC',
    'precio_desc' => 'p.precio_hora DESC',
    'nombre'      => 'p.nombre ASC',
    'recientes'   => 'p.created_at DESC',
    default       => 'COALESCE(r.promedio,0) DESC, COALESCE(r.total,0) DESC, p.total_paseos DESC, p.created_at DESC'
};

/* ===== DB ===== */
$pdo = AppConfig::db();

/* ===== COUNT para paginación ===== */
$sqlCount = "
    SELECT COUNT(*)
    FROM paseadores p
    INNER JOIN usuarios u ON u.usu_id = p.paseador_id
    LEFT JOIN (
        SELECT rated_id,
               ROUND(AVG(calificacion), 1) AS promedio,
               COUNT(*) AS total
        FROM calificaciones
        WHERE tipo = 'paseador'
        GROUP BY rated_id
    ) r ON r.rated_id = p.paseador_id
    $whereSql
";

$stc = $pdo->prepare($sqlCount);
bindUsedParams($stc, $sqlCount, $args);
$stc->execute();
$total      = (int)$stc->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

/* ===== LISTADO ===== */
$sql = "
    SELECT 
        p.paseador_id AS id,
        p.nombre      AS nombre_paseador,
        p.zona,
        p.descripcion,
        p.foto_url,
        p.precio_hora,
        p.total_paseos,
        p.disponible,
        p.created_at,

        u.telefono,
        u.ciudad,
        u.barrio,

        COALESCE(r.promedio, 0) AS calificacion,
        COALESCE(r.total, 0)    AS total_calificaciones

    FROM paseadores p
    INNER JOIN usuarios u ON u.usu_id = p.paseador_id

    LEFT JOIN (
        SELECT rated_id,
               ROUND(AVG(calificacion), 1) AS promedio,
               COUNT(*) AS total
        FROM calificaciones
        WHERE tipo = 'paseador'
        GROUP BY rated_id
    ) r ON r.rated_id = p.paseador_id

    $whereSql
    ORDER BY $orderBy
    LIMIT :limit OFFSET :offset
";

$st = $pdo->prepare($sql);
bindUsedParams($st, $sql, $args);
$st->bindValue(':limit', $perPage, PDO::PARAM_INT);
$st->bindValue(':offset', $offset, PDO::PARAM_INT);
$st->execute();
$paseadores = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

/* Helper para querystring de paginación */
function qs(array $overrides = []): string
{
    $params = array_merge($_GET, $overrides);
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    return http_build_query($params);
}

/* Rutas base */
$rolMenu       = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures  = BASE_URL . "/features/{$rolMenu}";
$usuarioNombre = h(Session::getUsuarioNombre() ?? 'Dueño/a');

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar Paseadores - Jaguata</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <style>
        html,
        body {
            height: 100%;
        }

        body {
            background: var(--gris-fondo, #f4f6f9);
        }

        /* ✅ Si tu theme ya controla main, esto es solo “por si acaso” */
        main.main-content {
            margin-left: 260px;
            min-height: 100vh;
            padding: 24px;
        }

        @media (max-width: 768px) {
            main.main-content {
                margin-left: 0;
                padding: 16px;
            }
        }

        /* ✅ Cards paseadores (más pro) */
        .paseador-card {
            border: 0;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 12px 30px rgba(0, 0, 0, .06);
            background: #fff;
        }

        /* ✅ FOTO “ESTIRADA” (cover) */
        .paseador-cover {
            position: relative;
            width: 100%;
            height: 280px; /* 👈 más grande */
            background: #eef2f5;
            overflow: hidden;
        }

        @media (min-width: 992px) {
            .paseador-cover {
                height: 320px; /* 👈 aún más grande en desktop */
            }
        }

        .paseador-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
        }

        .paseador-cover .cover-fallback {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .paseador-cover .badge-disponible {
            position: absolute;
            top: 12px;
            right: 12px;
            border-radius: 999px;
            padding: .35rem .65rem;
            font-size: .80rem;
        }

        .paseador-title {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: start;
        }

        .paseador-title h5 {
            margin: 0;
            font-weight: 900;
        }

        .paseador-sub {
            color: #6c757d;
            font-size: .90rem;
            display: grid;
            gap: 4px;
        }

        .paseador-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
            margin-bottom: 10px;
        }

        .paseador-actions {
            display: flex;
            gap: 10px;
            margin-top: auto;
        }

        .paseador-actions .btn {
            flex: 1;
        }

        /* ✅ Modal: foto más grande */
        .mp-foto {
            width: 200px;
            height: 200px;
            object-fit: cover;
            object-position: center;
            background: #f4f6f9;
        }

        @media (max-width: 576px) {
            .mp-foto {
                width: 100%;
                height: 220px;
            }
        }
    </style>
</head>

<body>
    <!-- ✅ Sidebar Dueño (incluye topbar-mobile + backdrop + JS propio) -->
    <?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>

    <!-- Contenido -->
    <main class="main-content">
        <div class="py-2">

            <!-- Header -->
            <div class="header-box mb-2 d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="fw-bold mb-1">
                        <i class="fas fa-dog me-2"></i>Buscar Paseadores
                    </h1>
                    <p class="mb-0">Encontrá paseadores de confianza cerca tuyo, <?= $usuarioNombre; ?> 🐾</p>
                </div>

                <!-- ✅ VOLVER desktop -->
                <div class="d-none d-md-block">
                    <a href="<?= $baseFeatures; ?>/Dashboard.php" class="btn btn-outline-light btn-sm fw-semibold">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>
            </div>

            <!-- ✅ VOLVER mobile -->
            <div class="d-md-none mb-3">
                <a href="<?= $baseFeatures; ?>/Dashboard.php" class="btn btn-secondary btn-sm w-100">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
            </div>

            <!-- Filtros -->
            <div class="section-card mb-2">
                <div class="section-header">
                    <i class="fas fa-filter me-2"></i>Filtros de búsqueda
                </div>
                <div class="section-body">
                    <form class="row gy-3" method="get">
                        <div class="col-md-4">
                            <label class="form-label">Nombre / palabra clave</label>
                            <input type="text" name="q" class="form-control" value="<?= h($q) ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Zona</label>
                            <input type="text" name="zona" class="form-control" value="<?= h($zona) ?>">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Calif. mín.</label>
                            <input type="number" step="0.1" min="0" max="5" name="minRate" class="form-control" value="<?= h((string)$minRate) ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Precio máx. (Gs/h)</label>
                            <input type="number" min="0" name="maxPrice" class="form-control" value="<?= h((string)$maxPrice) ?>">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">¿Disponible?</label>
                            <select name="disp" class="form-select">
                                <option value="">Todos</option>
                                <option value="1" <?= $dispF === 1 ? 'selected' : '' ?>>Sí</option>
                                <option value="0" <?= $dispF === 0 ? 'selected' : '' ?>>No</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Ordenar por</label>
                            <select name="sort" class="form-select">
                                <option value="score" <?= $sort === 'score' ? 'selected' : '' ?>>Mejor calificados</option>
                                <option value="precio_asc" <?= $sort === 'precio_asc' ? 'selected' : '' ?>>Precio ascendente</option>
                                <option value="precio_desc" <?= $sort === 'precio_desc' ? 'selected' : '' ?>>Precio descendente</option>
                                <option value="recientes" <?= $sort === 'recientes' ? 'selected' : '' ?>>Más recientes</option>
                                <option value="nombre" <?= $sort === 'nombre' ? 'selected' : '' ?>>Nombre (A-Z)</option>
                            </select>
                        </div>

                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button class="btn btn-success w-100">
                                <i class="fas fa-search me-1"></i> Buscar
                            </button>
                            <a href="<?= $baseFeatures; ?>/BuscarPaseadores.php" class="btn btn-outline-secondary w-100">
                                Limpiar
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Resultados -->
            <?php if (empty($paseadores)): ?>
                <div class="section-card text-center">
                    <div class="section-body">
                        <div class="mb-3">
                            <i class="fas fa-dog fa-3x text-muted"></i>
                        </div>
                        <h5 class="text-muted mb-2">No se encontraron paseadores con esos filtros.</h5>
                        <p class="text-muted small mb-3">
                            Probá ampliando la zona, bajando el mínimo de calificación o aumentando el precio máximo.
                        </p>
                        <a href="<?= $baseFeatures; ?>/BuscarPaseadores.php" class="btn btn-success">
                            <i class="fas fa-undo me-1"></i> Restablecer filtros
                        </a>
                    </div>
                </div>
            <?php else: ?>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small">
                        Mostrando <?= count($paseadores) ?> de <?= $total ?> resultados
                    </span>
                </div>

                <div class="row g-4">
                    <?php foreach ($paseadores as $p): ?>
                        <?php
                        $foto = resolveFotoUrl((string)($p['foto_url'] ?? ''));
                        $disponible = (int)($p['disponible'] ?? 0) === 1;
                        $badgeClass = $disponible ? 'bg-primary' : 'bg-dark';

                        $id      = (int)($p['id'] ?? 0);
                        $nombre  = (string)($p['nombre_paseador'] ?? 'Paseador');
                        $zonaTxt = trim((string)($p['zona'] ?? ''));
                        $ciuTxt  = trim((string)($p['ciudad'] ?? ''));
                        $barTxt  = trim((string)($p['barrio'] ?? ''));
                        $ubiTxt  = trim($ciuTxt . ' ' . $barTxt);

                        $telefono = trim((string)($p['telefono'] ?? ''));
                        $precio   = (float)($p['precio_hora'] ?? 0);
                        $rate     = (float)($p['calificacion'] ?? 0);
                        $rateCnt  = (int)($p['total_calificaciones'] ?? 0);
                        $paseos   = (int)($p['total_paseos'] ?? 0);
                        ?>
                        <div class="col-sm-6 col-lg-4">
                            <div class="card paseador-card h-100">

                                <!-- ✅ FOTO ESTIRADA -->
                               <!-- FOTO COVER PASEADOR -->
<div class="profile-cover">
    <?php if ($foto !== ''): ?>
        <img
            src="<?= h($foto) ?>"
            alt="Foto de <?= h($nombre) ?>"
            loading="lazy"
            onerror="this.onerror=null; this.style.display='none'; this.parentElement.querySelector('.cover-fallback')?.classList.remove('d-none');">
        <div class="cover-fallback d-none">
            <i class="fas fa-user-circle fa-5x text-secondary"></i>
        </div>
    <?php else: ?>
        <div class="cover-fallback">
            <i class="fas fa-user-circle fa-5x text-secondary"></i>
        </div>
    <?php endif; ?>
</div>


                                <div class="card-body d-flex flex-column">
                                    <div class="paseador-title">
                                        <h5><?= h($nombre) ?></h5>
                                        <span class="badge bg-success">
                                            ⭐ <?= number_format($rate, 1) ?>
                                        </span>
                                    </div>

                                    <div class="paseador-sub mt-2">
                                        <div>
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?= h($ubiTxt !== '' ? $ubiTxt : 'Sin ubicación') ?>
                                        </div>
                                        <div>
                                            <i class="fas fa-location-dot me-1"></i>
                                            Zona: <?= h($zonaTxt !== '' ? $zonaTxt : 'Sin zona') ?>
                                        </div>
                                        <div>
                                            <i class="fas fa-phone me-1"></i>
                                            <?= h($telefono !== '' ? $telefono : 'Sin teléfono') ?>
                                        </div>
                                    </div>

                                    <div class="paseador-badges">
                                        <span class="badge bg-light text-dark">Opiniones: <?= $rateCnt ?></span>
                                        <span class="badge bg-secondary">₲<?= number_format($precio, 0, ',', '.') ?>/h</span>
                                        <span class="badge bg-light text-dark">Paseos: <?= $paseos ?></span>
                                    </div>

                                    <p class="text-muted small mb-0">
                                        <?= h(mb_strimwidth((string)($p['descripcion'] ?? ''), 0, 120, '…')) ?>
                                    </p>

                                    <div class="paseador-actions mt-3">
                                       
<a
  class="btn btn-outline-success"
  href="<?= $baseFeatures; ?>/VerPaseador.php?id=<?= (int)$id ?>"
>
  <i class="fas fa-user me-1"></i> Ver perfil
</a>

                                        <a href="<?= $baseFeatures; ?>/SolicitarPaseo.php?paseador_id=<?= (int)$id ?>"
                                            class="btn btn-gradient">
                                            <i class="fas fa-paw me-1"></i> Solicitar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Paginación -->
                <?php if ($totalPages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= qs(['page' => 1]) ?>">«</a>
                            </li>
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= qs(['page' => $page - 1]) ?>">‹</a>
                            </li>
                            <li class="page-item disabled">
                                <span class="page-link">Página <?= $page ?>/<?= $totalPages ?></span>
                            </li>
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= qs(['page' => $page + 1]) ?>">›</a>
                            </li>
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= qs(['page' => $totalPages]) ?>">»</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>

            <!-- ✅ MODAL PERFIL -->
            <div class="modal fade" id="modalPerfilPaseador" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content modal-jaguata">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-user me-2"></i> Perfil del Paseador
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>

                        <div class="modal-body">
                            <div class="d-flex gap-3 align-items-start flex-wrap">

                                <!-- ✅ FOTO GRANDE (y fallback) -->
                                <div style="min-width:200px;">
                                    <img id="mp-foto" alt="Foto" class="rounded-4 border mp-foto d-none">
                                    <div id="mp-foto-fallback" class="bg-light rounded-4 border d-flex align-items-center justify-content-center"
                                         style="width:200px;height:200px;">
                                        <i class="fas fa-user-circle fa-5x text-secondary"></i>
                                    </div>
                                </div>

                                <div class="flex-grow-1">
                                    <h4 id="mp-nombre" class="mb-1"></h4>

                                    <div class="text-muted small mb-2">
                                        <i class="fas fa-location-dot me-1"></i>
                                        <span id="mp-ubicacion"></span>
                                        <span class="mx-2">•</span>
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        Zona: <span id="mp-zona"></span>
                                    </div>

                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                        <span class="badge bg-success">⭐ <span id="mp-rate"></span></span>
                                        <span class="badge bg-light text-dark">Opiniones: <span id="mp-ratecount"></span></span>
                                        <span class="badge bg-secondary">₲<span id="mp-precio"></span>/h</span>
                                        <span class="badge bg-light text-dark">Paseos: <span id="mp-paseos"></span></span>
                                    </div>

                                    <div class="text-muted small">
                                        <i class="fas fa-phone me-1"></i> <span id="mp-telefono"></span>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <h6 class="fw-semibold mb-2"><i class="fas fa-circle-info me-2"></i>Descripción</h6>
                            <div class="mensaje-box" id="mp-desc"></div>
                        </div>

                        <div class="modal-footer">
                            <a id="mp-solicitar" href="#" class="btn btn-gradient">
                                <i class="fas fa-paw me-1"></i> Solicitar paseo
                            </a>
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="mt-4 text-center text-muted small">
                © <?= date('Y') ?> Jaguata — Panel del Dueño
            </footer>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- ✅ JS modal perfil -->
    <script>
        (function() {
            const modal = document.getElementById('modalPerfilPaseador');
            if (!modal) return;

            modal.addEventListener('show.bs.modal', function(event) {
                const btn = event.relatedTarget;
                if (!btn) return;

                const id   = btn.getAttribute('data-id') || '';
                const nom  = btn.getAttribute('data-nombre') || '';
                const zona = btn.getAttribute('data-zona') || '';
                const ciu  = btn.getAttribute('data-ciudad') || '';
                const bar  = btn.getAttribute('data-barrio') || '';
                const tel  = btn.getAttribute('data-telefono') || 'Sin teléfono';
                const des  = btn.getAttribute('data-desc') || 'Sin descripción.';
                const pre  = btn.getAttribute('data-precio') || '0';
                const rat  = btn.getAttribute('data-rate') || '0.0';
                const rc   = btn.getAttribute('data-ratecount') || '0';
                const pas  = btn.getAttribute('data-paseos') || '0';
                const foto = btn.getAttribute('data-foto') || '';

                document.getElementById('mp-nombre').textContent = nom;
                document.getElementById('mp-zona').textContent = zona || 'Sin zona';
                document.getElementById('mp-ubicacion').textContent = (ciu + ' ' + bar).trim() || 'Sin ubicación';
                document.getElementById('mp-telefono').textContent = tel;
                document.getElementById('mp-desc').textContent = des;
                document.getElementById('mp-precio').textContent = pre;
                document.getElementById('mp-rate').textContent = rat;
                document.getElementById('mp-ratecount').textContent = rc;
                document.getElementById('mp-paseos').textContent = pas;

                const img = document.getElementById('mp-foto');
                const fallback = document.getElementById('mp-foto-fallback');

                const ok = foto && foto.trim() !== '' && foto !== '0';

                if (ok) {
                    img.classList.remove('d-none');
                    fallback.classList.add('d-none');
                    img.src = foto;

                    img.onerror = () => {
                        img.onerror = null;
                        img.classList.add('d-none');
                        fallback.classList.remove('d-none');
                        img.removeAttribute('src');
                    };
                } else {
                    img.classList.add('d-none');
                    fallback.classList.remove('d-none');
                    img.removeAttribute('src');
                }

                const link = document.getElementById('mp-solicitar');
                link.href = "<?= $baseFeatures; ?>/SolicitarPaseo.php?paseador_id=" + encodeURIComponent(id);
            });
        })();
    </script>
</body>

</html>
