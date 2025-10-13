<?php

/**
 * features/dueno/BuscarPaseadores.php
 * Listado + filtros de paseadores con tu esquema real:
 * paseador_id, nombre, experiencia, disponible, zona, descripcion, foto_url,
 * precio_hora, disponibilidad, calificacion, total_paseos, created_at
 */

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;

// ===== Init + auth (dueño)
AppConfig::init();
$auth = new AuthController();
$auth->checkRole('dueno');

// ===== Helpers =====
function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
/** Bindea SOLO parámetros presentes en el SQL para evitar HY093 */
function bindUsedParams(PDOStatement $st, string $sql, array $params): void
{
    foreach ($params as $k => $v) {
        if (strpos($sql, $k) !== false) {
            $type = is_int($v) ? PDO::PARAM_INT : (is_null($v) ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $st->bindValue($k, $v, $type);
        }
    }
}

// ===== Filtros (GET) =====
$q        = trim($_GET['q']      ?? '');   // busca en nombre/descripcion/zona
$zona     = trim($_GET['zona']   ?? '');   // zona exacta
$minRate  = $_GET['minRate']     ?? '';    // 0..5
$maxRate  = $_GET['maxRate']     ?? '';
$minPrice = $_GET['minPrice']    ?? '';
$maxPrice = $_GET['maxPrice']    ?? '';
$disp     = $_GET['disp']        ?? '';    // 1 o 0 (columna 'disponible')
$sort     = $_GET['sort']        ?? 'score';

$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 12;
$offset   = ($page - 1) * $perPage;

// Normalización
$minRateF  = ($minRate  === '' ? null : max(0, min(5, (float)$minRate)));
$maxRateF  = ($maxRate  === '' ? null : max(0, min(5, (float)$maxRate)));
$minPriceF = ($minPrice === '' ? null : max(0, (float)$minPrice));
$maxPriceF = ($maxPrice === '' ? null : max(0, (float)$maxPrice));
$dispF     = ($disp     === '' ? null : (int)$disp);

// ===== WHERE (solo columnas reales) =====
$where = [];
$args  = [];

if ($q !== '') {
    $where[]   = '(p.nombre LIKE :q OR p.descripcion LIKE :q OR p.zona LIKE :q)';
    $args[':q'] = "%$q%";
}
if ($zona !== '') {
    $where[]     = 'p.zona = :zona';
    $args[':zona'] = $zona;
}
if ($minRateF !== null) {
    $where[]        = 'COALESCE(p.calificacion,0) >= :minRate';
    $args[':minRate'] = $minRateF;
}
if ($maxRateF !== null) {
    $where[]        = 'COALESCE(p.calificacion,0) <= :maxRate';
    $args[':maxRate'] = $maxRateF;
}
if ($minPriceF !== null) {
    $where[]         = 'p.precio_hora >= :minPrice';
    $args[':minPrice'] = $minPriceF;
}
if ($maxPriceF !== null) {
    $where[]         = 'p.precio_hora <= :maxPrice';
    $args[':maxPrice'] = $maxPriceF;
}
if ($dispF !== null) {
    $where[]       = 'p.disponible = :disp';
    $args[':disp'] = $dispF;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// ===== Ordenamiento (usando tus columnas) =====
$validSort = [
    'score'       => 'p.calificacion DESC, p.total_paseos DESC, p.paseador_id DESC',
    'precio_asc'  => 'p.precio_hora ASC, p.paseador_id DESC',
    'precio_desc' => 'p.precio_hora DESC, p.paseador_id DESC',
    'nombre'      => 'p.nombre ASC, p.paseador_id DESC',
    'recientes'   => 'p.created_at DESC, p.paseador_id DESC',
];
$orderBy = $validSort[$sort] ?? $validSort['score'];

/** @var PDO $pdo */
$pdo = AppConfig::db();

// ===== COUNT total (evitar HY093) =====
$sqlCount = "SELECT COUNT(*) FROM paseadores p $whereSql";
$stc = $pdo->prepare($sqlCount);
bindUsedParams($stc, $sqlCount, $args);
$stc->execute();
$total = (int)$stc->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

// ===== SELECT página =====
$sql = "
SELECT
  p.paseador_id      AS id,
  p.nombre,
  p.zona,
  p.descripcion,
  p.foto_url,
  p.experiencia,
  p.disponible,
  p.disponibilidad,
  p.precio_hora,
  p.calificacion,
  p.total_paseos,
  p.created_at
FROM paseadores p
$whereSql
ORDER BY $orderBy
LIMIT :limit OFFSET :offset";
$st = $pdo->prepare($sql);
bindUsedParams($st, $sql, $args);
$st->bindValue(':limit', $perPage, PDO::PARAM_INT);
$st->bindValue(':offset', $offset, PDO::PARAM_INT);
$st->execute();
$paseadores = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

// ===== QS helper =====
function qs(array $overrides = []): string
{
    $params = array_merge($_GET, $overrides);
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    return http_build_query($params);
}

// ===== Header / Footer (ajusta rutas a tus plantillas reales) =====
include __DIR__ . '/../../src/Templates/Header.php';
include __DIR__ . '/../../src/Templates/Navbar.php';
?>
<div class="container-fluid my-4">
    <div class="d-flex align-items-center mb-3">
        <h1 class="h3 mb-0">Buscar paseadores</h1>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form class="row gy-3" method="get" action="">
                <div class="col-12 col-md-4">
                    <label class="form-label">Nombre / palabras clave</label>
                    <input type="text" name="q" value="<?= h($q) ?>" class="form-control" placeholder="Ej: Ana, adiestrador, pitbull" />
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label">Zona exacta</label>
                    <input type="text" name="zona" value="<?= h($zona) ?>" class="form-control" placeholder="Ej: Centro" />
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Calificación mín.</label>
                    <input type="number" step="0.1" min="0" max="5" name="minRate" value="<?= h((string)$minRate) ?>" class="form-control" />
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Calificación máx.</label>
                    <input type="number" step="0.1" min="0" max="5" name="maxRate" value="<?= h((string)$maxRate) ?>" class="form-control" />
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Precio mín. (Gs/h)</label>
                    <input type="number" min="0" name="minPrice" value="<?= h((string)$minPrice) ?>" class="form-control" />
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Precio máx. (Gs/h)</label>
                    <input type="number" min="0" name="maxPrice" value="<?= h((string)$maxPrice) ?>" class="form-control" />
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">¿Disponible?</label>
                    <select name="disp" class="form-select">
                        <option value="" <?= $dispF === null ? 'selected' : '' ?>>Todos</option>
                        <option value="1" <?= $dispF === 1 ? 'selected' : '' ?>>Sí</option>
                        <option value="0" <?= $dispF === 0 ? 'selected' : '' ?>>No</option>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label">Ordenar por</label>
                    <select name="sort" class="form-select">
                        <option value="score" <?= $sort === 'score' ? 'selected' : '' ?>>Mejor calificados</option>
                        <option value="recientes" <?= $sort === 'recientes' ? 'selected' : '' ?>>Más recientes</option>
                        <option value="precio_asc" <?= $sort === 'precio_asc' ? 'selected' : '' ?>>Precio: menor a mayor</option>
                        <option value="precio_desc" <?= $sort === 'precio_desc' ? 'selected' : '' ?>>Precio: mayor a menor</option>
                        <option value="nombre" <?= $sort === 'nombre' ? 'selected' : '' ?>>Nombre (A-Z)</option>
                    </select>
                </div>
                <div class="col-12 col-md-3 d-flex align-items-end gap-2">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                    <a class="btn btn-outline-secondary" href="BuscarPaseadores.php">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($paseadores)): ?>
        <div class="alert alert-info">No se encontraron paseadores con esos filtros.</div>
    <?php else: ?>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <small class="text-muted">Mostrando <?= count($paseadores) ?> de <?= $total ?> resultados</small>
        </div>

        <div class="row g-3">
            <?php foreach ($paseadores as $row): ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm">
                        <?php if (!empty($row['foto_url'])): ?>
                            <img src="<?= h($row['foto_url']) ?>" class="card-img-top" alt="Foto de <?= h($row['nombre'] ?? ('Paseador #' . (int)$row['id'])) ?>" style="object-fit:cover; height: 180px;">
                        <?php else: ?>
                            <div class="bg-light d-flex align-items-center justify-content-center" style="height:180px;">
                                <i class="bi bi-person-circle" style="font-size:64px;"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title mb-1"><?= h($row['nombre'] ?: ('Paseador #' . (int)$row['id'])) ?></h5>
                            <div class="mb-2 text-muted small"><i class="bi bi-geo-alt"></i> <?= h($row['zona'] ?? 'Sin zona') ?></div>

                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <span class="badge bg-success">⭐ <?= number_format((float)$row['calificacion'], 1) ?></span>
                                <span class="badge bg-secondary">Gs <?= number_format((float)$row['precio_hora'], 0, ',', '.') ?>/h</span>
                                <?php if (!empty($row['experiencia'])): ?>
                                    <span class="badge bg-info">Exp.</span>
                                <?php endif; ?>
                                <span class="badge <?= (int)$row['disponible'] === 1 ? 'bg-primary' : 'bg-dark' ?>">
                                    <?= (int)$row['disponible'] === 1 ? 'Disponible' : 'No disponible' ?>
                                </span>
                                <span class="badge bg-light text-dark">Paseos: <?= (int)$row['total_paseos'] ?></span>
                            </div>

                            <?php if (!empty($row['descripcion'])): ?>
                                <p class="card-text small text-muted">
                                    <?= h(mb_strimwidth((string)$row['descripcion'], 0, 120, '…', 'UTF-8')) ?>
                                </p>
                            <?php endif; ?>

                            <div class="mt-auto d-grid gap-2">
                                <a class="btn btn-outline-primary" href="SolicitarPaseo.php?paseador_id=<?= (int)$row['id'] ?>">
                                    Solicitar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="mt-4" aria-label="Resultados">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= qs(['page' => 1]) ?>">« Primero</a>
                    </li>
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= qs(['page' => $page - 1]) ?>">‹ Anterior</a>
                    </li>
                    <li class="page-item disabled"><span class="page-link">Página <?= $page ?> / <?= $totalPages ?></span></li>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= qs(['page' => $page + 1]) ?>">Siguiente ›</a>
                    </li>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= qs(['page' => $totalPages]) ?>">Último »</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
include __DIR__ . '/../../src/Templates/Footer.php';
