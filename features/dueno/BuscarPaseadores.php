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


function resolveFotoUrl(?string $foto): string
{
    $foto = trim((string)$foto);
    if ($foto === '') return '';

    if (preg_match('~^https?://~i', $foto)) return $foto;

    $fotoNorm = str_replace('\\', '/', $foto);
    $fotoNorm = ltrim($fotoNorm, '/');

    if (
        str_starts_with($fotoNorm, 'assets/') ||
        str_starts_with($fotoNorm, 'public/') ||
        str_starts_with($fotoNorm, 'uploads/')
    ) {
        return BASE_URL . '/' . $fotoNorm;
    }

    return BASE_URL . '/assets/uploads/perfiles/' . $fotoNorm;
}
function zonaToText(?string $raw): string
{
    $raw = trim((string)$raw);
    if ($raw === '') return 'Sin zona';

    // Normaliza espacios
    $raw = preg_replace('/\s+/', ' ', $raw);

    // Intenta decodificar varias veces (doble/triple encode)
    $try = $raw;

    for ($i = 0; $i < 8; $i++) {
        $decoded = json_decode($try, true);

        if (json_last_error() === JSON_ERROR_NONE) {

            // Caso: quedó como string (doble encode)
            if (is_string($decoded)) {
                $try = $decoded;
                continue;
            }

            // Caso: array
            if (is_array($decoded)) {
                $onlyStrings = true;
                foreach ($decoded as $x) {
                    if (!is_string($x)) { $onlyStrings = false; break; }
                }

                if ($onlyStrings) {
                    $joined = implode('', $decoded);
                    $joined = stripslashes($joined);
                    $joined = trim($joined);

                    // Si el join parece JSON, lo volvemos a intentar
                    if ($joined !== '' && (str_starts_with($joined, '[') || str_starts_with($joined, '{') || str_starts_with($joined, '"'))) {
                        $try = $joined;
                        continue;
                    }
                }

                // Aplanar + limpiar
                $flat = [];
                $stack = [$decoded];

                while ($stack) {
                    $item = array_pop($stack);

                    if (is_array($item)) {
                        foreach ($item as $v) $stack[] = $v;
                        continue;
                    }

                    $v = trim((string)$item);
                    $v = stripslashes($v);
                    $v = preg_replace('/\s+/', ' ', $v);

                    // Limpieza de basura típica
                    $v = str_replace(['\\"', '"', '[', ']', '\\\\'], ['', '', '', '', '\\'], $v);
                    $v = trim($v, " \t\n\r\0\x0B,");
                    if ($v !== '') $flat[] = $v;
                }

                $flat = array_values(array_unique($flat));
                if (!empty($flat)) return implode(', ', $flat);

                return 'Sin zona';
            }

            break;
        }

        // No era JSON: sacamos escapes y probamos otra vez
        $try = stripslashes($try);
        $try = trim($try, "\"'");
    }

    // Fallback final: limpiar y mostrar “legible”
    $clean = stripslashes($raw);
    $clean = preg_replace('/\s+/', ' ', $clean);
    $clean = str_replace(['\\"', '"', '[', ']'], ['', '', '', ''], $clean);
    $clean = trim($clean, " ,");

    return $clean !== '' ? $clean : 'Sin zona';
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

/* ===== WHERE dinámico (se concatena con AND) ===== */
$where = [];
$args  = [];

/* Filtro texto */
if ($q !== '') {
    $where[] = '(u.nombre LIKE :q
            OR COALESCE(p.descripcion,u.descripcion,"") LIKE :q
            OR COALESCE(p.zona,u.zona,"") LIKE :q
            OR COALESCE(u.ciudad,"") LIKE :q
            OR COALESCE(u.barrio,"") LIKE :q)';
    $args[':q'] = "%$q%";
}

/* Zona */
if ($zona !== '') {
    $where[]       = 'COALESCE(p.zona,u.zona,"") = :zona';
    $args[':zona'] = $zona;
}

/* Precio min/max */
if ($minPriceF !== null) {
    $where[]           = 'COALESCE(p.precio_hora,0) >= :minPrice';
    $args[':minPrice'] = $minPriceF;
}
if ($maxPriceF !== null) {
    $where[]           = 'COALESCE(p.precio_hora,0) <= :maxPrice';
    $args[':maxPrice'] = $maxPriceF;
}

/* Disponible */
if ($dispF !== null) {
    $where[]       = 'COALESCE(p.disponible,1) = :disp';
    $args[':disp'] = $dispF;
}

/* Rating */
if ($minRateF !== null) {
    $where[]          = 'COALESCE(r.promedio,0) >= :minRate';
    $args[':minRate'] = $minRateF;
}
if ($maxRateF !== null) {
    $where[]          = 'COALESCE(r.promedio,0) <= :maxRate';
    $args[':maxRate'] = $maxRateF;
}

$whereSql = $where ? (' AND ' . implode(' AND ', $where)) : '';

/* ===== ORDER BY (compatible con LEFT JOIN) ===== */
$orderBy = match ($sort) {
    'precio_asc'  => 'COALESCE(p.precio_hora,0) ASC',
    'precio_desc' => 'COALESCE(p.precio_hora,0) DESC',
    'nombre'      => 'u.nombre ASC',
    'recientes'   => 'COALESCE(p.created_at, u.created_at) DESC',
    default       => 'COALESCE(r.promedio,0) DESC, COALESCE(r.total,0) DESC, COALESCE(p.total_paseos,0) DESC, COALESCE(p.created_at, u.created_at) DESC'
};

/* ===== DB ===== */
$pdo = AppConfig::db();

/* ===== COUNT ===== */
$sqlCount = "
    SELECT COUNT(*)
    FROM usuarios u
    LEFT JOIN paseadores p ON p.paseador_id = u.usu_id
    LEFT JOIN (
        SELECT rated_id,
               ROUND(AVG(calificacion), 1) AS promedio,
               COUNT(*) AS total
        FROM calificaciones
        WHERE tipo = 'paseador'
        GROUP BY rated_id
    ) r ON r.rated_id = u.usu_id
    WHERE u.rol = 'paseador' AND u.estado = 'aprobado'
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
        u.usu_id AS id,
        u.nombre AS nombre_paseador,

        COALESCE(p.zona, u.zona, '') AS zona,
        COALESCE(p.descripcion, u.descripcion, '') AS descripcion,

        COALESCE(p.foto_url, u.foto_perfil, '') AS foto_url,

        COALESCE(p.precio_hora, 0) AS precio_hora,
        COALESCE(p.total_paseos, 0) AS total_paseos,
        COALESCE(p.disponible, 1) AS disponible,

        u.telefono,
        u.ciudad,
        u.barrio,

        COALESCE(r.promedio, 0) AS calificacion,
        COALESCE(r.total, 0)    AS total_calificaciones

    FROM usuarios u
    LEFT JOIN paseadores p ON p.paseador_id = u.usu_id
    LEFT JOIN (
        SELECT rated_id,
               ROUND(AVG(calificacion), 1) AS promedio,
               COUNT(*) AS total
        FROM calificaciones
        WHERE tipo = 'paseador'
        GROUP BY rated_id
    ) r ON r.rated_id = u.usu_id

    WHERE u.rol = 'paseador' AND u.estado = 'aprobado'
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

/* Helper querystring */
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

$DEFAULT_AVATAR = BASE_URL . '/public/assets/images/user-default.png';

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
        html, body { height: 100%; }
        body { background: var(--gris-fondo, #f4f6f9); }

        main.main-content {
            margin-left: 260px;
            min-height: 100vh;
            padding: 24px;
        }
        @media (max-width: 768px) {
            main.main-content { margin-left: 0; padding: 16px; }
        }

        .paseador-card{
            border: 0;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 12px 30px rgba(0,0,0,.06);
            background: #fff;
        }

        .paseador-cover{
            position: relative;
            width: 100%;
            height: 280px;
            background: #eef2f5;
            overflow: hidden;
        }
        @media (min-width: 992px) {
            .paseador-cover { height: 320px; }
        }

        .paseador-cover img{
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
        }

        .paseador-cover .badge-disponible{
            position: absolute;
            top: 12px;
            right: 12px;
            border-radius: 999px;
            padding: .35rem .65rem;
            font-size: .80rem;
        }

        .paseador-title{
            display:flex;
            justify-content:space-between;
            gap:10px;
            align-items:start;
        }
        .paseador-title h5{ margin:0; font-weight:900; }

        .paseador-sub{
            color:#6c757d;
            font-size:.90rem;
            display:grid;
            gap:4px;
        }

        .paseador-badges{
            display:flex;
            flex-wrap:wrap;
            gap:8px;
            margin-top:10px;
            margin-bottom:10px;
        }

        .paseador-actions{
            display:flex;
            gap:10px;
            margin-top:auto;
        }
        .paseador-actions .btn{ flex:1; }

        .btn-avatar{
            display:inline-flex;
            align-items:center;
            gap:10px;
            padding:6px 12px 6px 6px;
            border-radius:999px;
            font-weight:600;
        }
        .btn-avatar .avatar{
            width:34px;
            height:34px;
            border-radius:50%;
            overflow:hidden;
            flex-shrink:0;
            border:2px solid #3c6255;
            background:#fff;
        }
        .btn-avatar .avatar img{
            width:100%;
            height:100%;
            object-fit:cover;
        }
    </style>
</head>

<body>
<?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>

<main class="main-content">
    <div class="py-2">

        <div class="header-box mb-2 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="fw-bold mb-1">
                    <i class="fas fa-dog me-2"></i>Buscar Paseadores
                </h1>
                <p class="mb-0">Encontrá paseadores de confianza cerca tuyo, <?= $usuarioNombre; ?> 🐾</p>
            </div>

            <div class="d-none d-md-block">
                <a href="<?= $baseFeatures; ?>/Dashboard.php" class="btn btn-outline-light btn-sm fw-semibold">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
            </div>
        </div>

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
                    Mostrando <?= count($paseadores) ?> de <?= (int)$total ?> resultados
                </span>
            </div>

            <div class="row g-4">
                <?php foreach ($paseadores as $p): ?>
                    <?php
                    $id         = (int)($p['id'] ?? 0);
                    $nombre     = (string)($p['nombre_paseador'] ?? 'Paseador');
                    $zonaTxt    = trim((string)($p['zona'] ?? ''));
                    $ciuTxt     = trim((string)($p['ciudad'] ?? ''));
                    $barTxt     = trim((string)($p['barrio'] ?? ''));
                    $ubiTxt     = trim($ciuTxt . ' ' . $barTxt);

                    $telefono   = trim((string)($p['telefono'] ?? ''));
                    $precio     = (float)($p['precio_hora'] ?? 0);
                    $rate       = (float)($p['calificacion'] ?? 0);
                    $rateCnt    = (int)($p['total_calificaciones'] ?? 0);
                    $paseos     = (int)($p['total_paseos'] ?? 0);
                    $disponible = (int)($p['disponible'] ?? 0) === 1;

                    $fotoReal    = resolveFotoUrl((string)($p['foto_url'] ?? ''));
                    $fotoMostrar = ($fotoReal !== '') ? $fotoReal : $DEFAULT_AVATAR;
                    ?>

                    <div class="col-sm-6 col-lg-4">
                        <div class="card paseador-card h-100">

                            <div class="paseador-cover">
                                <img
                                    src="<?= h($fotoMostrar) ?>"
                                    alt="Foto de <?= h($nombre) ?>"
                                    loading="lazy"
                                    onerror="this.onerror=null; this.src='<?= h($DEFAULT_AVATAR) ?>';">

                                <span class="badge badge-disponible <?= $disponible ? 'bg-success' : 'bg-dark' ?>">
                                    <?= $disponible ? 'Disponible' : 'No disponible' ?>
                                </span>
                            </div>

                            <div class="card-body d-flex flex-column">
                                <div class="paseador-title">
                                    <h5><?= h($nombre) ?></h5>
                                    <span class="badge bg-success">⭐ <?= number_format($rate, 1) ?></span>
                                </div>

                                <div class="paseador-sub mt-2">
                                    <div>
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <?= h($ubiTxt !== '' ? $ubiTxt : 'Sin ubicación') ?>
                                    </div>
                                    <div>
  <i class="fas fa-location-dot me-1"></i>
  
  <?php
    $zRaw = trim((string)$zonaTxt);

    $zonaOut = 'Sin zona';

    if ($zRaw !== '') {
        // 1) Intento: JSON (aunque venga escapado mil veces)
        $try = $zRaw;
        for ($i = 0; $i < 4; $i++) {
            $decoded = json_decode($try, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                // si quedó como string otra vez (doble encode), seguimos
                if (is_string($decoded)) {
                    $try = $decoded;
                    continue;
                }
                // si quedó array, lo formateamos
                if (is_array($decoded)) {
                    $flat = [];
                    $it = new RecursiveIteratorIterator(new RecursiveArrayIterator($decoded));
                    foreach ($it as $v) {
                        $v = trim((string)$v);
                        $v = trim($v, "\"' \t\n\r\0\x0B");
                        if ($v !== '') $flat[] = $v;
                    }
                    $flat = array_values(array_unique($flat));
                    if (!empty($flat)) {
                        $zonaOut = implode(', ', $flat);
                    }
                }
                break;
            }
            // si no es JSON, probamos quitando slashes y comillas raras
            $try = stripslashes($try);
            $try = trim($try, "\"'");
        }

        // 2) Si no era JSON (o quedó vacío), mostramos el texto tal cual (limpiando basura)
        if ($zonaOut === 'Sin zona') {
            $clean = stripslashes($zRaw);
            $clean = trim($clean);
            $clean = preg_replace('/\s+/', ' ', $clean);

            // si parece algo tipo ["..."] pero roto, le sacamos corchetes y comillas
            $clean = str_replace(['[',']','\\"','"'], ['', '', '', ''], $clean);
            $clean = trim($clean, " ,");

            if ($clean !== '') $zonaOut = $clean;
        }
    }

    echo h($zonaOut);
  ?>
</div>

                                    <div>
                                        <i class="fas fa-phone me-1"></i>
                                        <?= h($telefono !== '' ? $telefono : 'Sin teléfono') ?>
                                    </div>
                                </div>

                                <div class="paseador-badges">
                                    <span class="badge bg-light text-dark">Opiniones: <?= (int)$rateCnt ?></span>
                                    <span class="badge bg-secondary">₲<?= number_format($precio, 0, ',', '.') ?>/h</span>
                                    <span class="badge bg-light text-dark">Paseos: <?= (int)$paseos ?></span>
                                </div>

                                <p class="text-muted small mb-0">
                                    <?= h(mb_strimwidth((string)($p['descripcion'] ?? ''), 0, 120, '…')) ?>
                                </p>

                                <div class="paseador-actions mt-3">
                                    <a class="btn btn-outline-success btn-avatar"
                                       href="<?= $baseFeatures; ?>/VerPaseador.php?id=<?= (int)$id ?>">
                                        <span class="avatar">
                                            <img
                                                src="<?= h($fotoMostrar) ?>"
                                                alt="Avatar"
                                                onerror="this.onerror=null; this.src='<?= h($DEFAULT_AVATAR) ?>';">
                                        </span>
                                        Ver perfil
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
                            <span class="page-link">Página <?= (int)$page ?>/<?= (int)$totalPages ?></span>
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

        <footer class="mt-4 text-center text-muted small">
            © <?= date('Y') ?> Jaguata — Panel del Dueño
        </footer>

    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
