<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;

AppConfig::init();
$auth = new AuthController();
$auth->checkRole('dueno');

// ===== Helpers =====
function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function bindUsedParams(PDOStatement $st, string $sql, array $params): void
{
    foreach ($params as $k => $v) {
        if (strpos($sql, $k) !== false) {
            $type = is_int($v) ? PDO::PARAM_INT : (is_null($v) ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $st->bindValue($k, $v, $type);
        }
    }
}

// ===== Filtros =====
$q        = trim($_GET['q'] ?? '');
$zona     = trim($_GET['zona'] ?? '');
$minRate  = $_GET['minRate'] ?? '';
$maxRate  = $_GET['maxRate'] ?? '';
$minPrice = $_GET['minPrice'] ?? '';
$maxPrice = $_GET['maxPrice'] ?? '';
$disp     = $_GET['disp'] ?? '';
$sort     = $_GET['sort'] ?? 'score';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset  = ($page - 1) * $perPage;

// ===== Normalización =====
$minRateF  = ($minRate === '' ? null : max(0, min(5, (float)$minRate)));
$maxRateF  = ($maxRate === '' ? null : max(0, min(5, (float)$maxRate)));
$minPriceF = ($minPrice === '' ? null : max(0, (float)$minPrice));
$maxPriceF = ($maxPrice === '' ? null : max(0, (float)$maxPrice));
$dispF     = ($disp === '' ? null : (int)$disp);

// ===== WHERE =====
$where = [];
$args = [];
if ($q !== '') {
    $where[] = '(p.nombre LIKE :q OR p.descripcion LIKE :q OR p.zona LIKE :q)';
    $args[':q'] = "%$q%";
}
if ($zona !== '') {
    $where[] = 'p.zona = :zona';
    $args[':zona'] = $zona;
}
if ($minRateF !== null) {
    $where[] = 'COALESCE(p.calificacion,0) >= :minRate';
    $args[':minRate'] = $minRateF;
}
if ($maxRateF !== null) {
    $where[] = 'COALESCE(p.calificacion,0) <= :maxRate';
    $args[':maxRate'] = $maxRateF;
}
if ($minPriceF !== null) {
    $where[] = 'p.precio_hora >= :minPrice';
    $args[':minPrice'] = $minPriceF;
}
if ($maxPriceF !== null) {
    $where[] = 'p.precio_hora <= :maxPrice';
    $args[':maxPrice'] = $maxPriceF;
}
if ($dispF !== null) {
    $where[] = 'p.disponible = :disp';
    $args[':disp'] = $dispF;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
$orderBy = match ($sort) {
    'precio_asc'  => 'p.precio_hora ASC',
    'precio_desc' => 'p.precio_hora DESC',
    'nombre'      => 'p.nombre ASC',
    'recientes'   => 'p.created_at DESC',
    default       => 'p.calificacion DESC, p.total_paseos DESC'
};

// ===== Query =====
$pdo = AppConfig::db();
$sqlCount = "SELECT COUNT(*) FROM paseadores p $whereSql";
$stc = $pdo->prepare($sqlCount);
bindUsedParams($stc, $sqlCount, $args);
$stc->execute();
$total = (int)$stc->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

$sql = "SELECT p.paseador_id AS id, p.nombre, p.zona, p.descripcion, p.foto_url,
       p.experiencia, p.disponible, p.precio_hora, p.calificacion,
       p.total_paseos, p.created_at
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

function qs(array $overrides = []): string
{
    $params = array_merge($_GET, $overrides);
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    return http_build_query($params);
}

$rol = 'dueno';
$baseFeatures = BASE_URL . "/features/{$rol}";
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar Paseadores - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f7fa;
            font-family: "Poppins", sans-serif;
            margin: 0;
        }

        .layout {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            color: #f8f9fa;
            width: 240px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 1.5rem;
            box-shadow: 4px 0 12px rgba(0, 0, 0, .15);
            z-index: 1000;
            transition: transform .3s ease-in-out;
        }

        .sidebar .nav-link {
            color: #ddd;
            display: flex;
            align-items: center;
            padding: 10px 16px;
            border-radius: 8px;
            margin: 4px 8px;
            font-weight: 500;
        }

        .sidebar .nav-link i {
            width: 22px;
            margin-right: 10px;
        }

        .sidebar .nav-link:hover {
            background-color: #343454;
            color: #fff;
            transform: translateX(4px);
        }

        .sidebar .nav-link.active {
            background: #3c6255;
            color: #fff;
        }

        .menu-toggle {
            display: none;
            position: fixed;
            top: 16px;
            left: 16px;
            background: #1e1e2f;
            color: white;
            border: none;
            padding: 8px 10px;
            border-radius: 6px;
            z-index: 1100;
        }

        @media(max-width:768px) {
            .menu-toggle {
                display: block;
            }

            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }
        }

        main.content {
            flex-grow: 1;
            margin-left: 240px;
            padding: 2.5rem;
            width: calc(100% - 240px);
        }

        @media(max-width:768px) {
            main.content {
                margin-left: 0;
                width: 100%;
                padding: 1.5rem;
            }
        }

        .welcome-box {
            background: linear-gradient(90deg, #20c997, #3c6255);
            color: #fff;
            padding: 1.5rem 2rem;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 15px rgba(0, 0, 0, .08);
        }

        .card-premium {
            border: none;
            border-radius: 14px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, .05);
            background: #fff;
        }

        .btn-gradient {
            background: linear-gradient(90deg, #3c6255, #20c997);
            border: none;
            color: #fff;
            font-weight: 500;
        }

        .btn-gradient:hover {
            opacity: .9;
        }
    </style>
</head>

<body>
    <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
    <div class="layout">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>

        <!-- Contenido -->
        <main class="content">
            <div class="welcome-box mb-4">
                <div>
                    <h1 class="fw-bold"><i class="fas fa-dog me-2"></i> Buscar Paseadores</h1>
                    <p>Encontrá paseadores de confianza cerca tuyo 🐾</p>
                </div>
                <a href="<?= $baseFeatures; ?>/Dashboard.php" class="btn btn-light text-success fw-semibold">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
            </div>

            <!-- Filtros -->
            <div class="card-premium mb-4">
                <div class="card-header bg-gradient text-white fw-semibold">
                    <i class="fas fa-filter me-2"></i>Filtros de búsqueda
                </div>
                <div class="card-body">
                    <form class="row gy-3" method="get">
                        <div class="col-md-4"><label class="form-label">Nombre / palabra clave</label>
                            <input type="text" name="q" class="form-control" value="<?= h($q) ?>">
                        </div>
                        <div class="col-md-3"><label class="form-label">Zona</label>
                            <input type="text" name="zona" class="form-control" value="<?= h($zona) ?>">
                        </div>
                        <div class="col-md-2"><label class="form-label">Calif. mín.</label>
                            <input type="number" step="0.1" min="0" max="5" name="minRate" value="<?= h((string)$minRate) ?>" class="form-control">
                        </div>
                        <div class="col-md-2"><label class="form-label">Precio máx. (Gs/h)</label>
                            <input type="number" min="0" name="maxPrice" value="<?= h((string)$maxPrice) ?>" class="form-control">
                        </div>
                        <div class="col-md-2"><label class="form-label">¿Disponible?</label>
                            <select name="disp" class="form-select">
                                <option value="">Todos</option>
                                <option value="1" <?= $dispF === 1 ? 'selected' : '' ?>>Sí</option>
                                <option value="0" <?= $dispF === 0 ? 'selected' : '' ?>>No</option>
                            </select>
                        </div>
                        <div class="col-md-3"><label class="form-label">Ordenar por</label>
                            <select name="sort" class="form-select">
                                <option value="score" <?= $sort === 'score' ? 'selected' : '' ?>>Mejor calificados</option>
                                <option value="precio_asc" <?= $sort === 'precio_asc' ? 'selected' : '' ?>>Precio ascendente</option>
                                <option value="precio_desc" <?= $sort === 'precio_desc' ? 'selected' : '' ?>>Precio descendente</option>
                                <option value="recientes" <?= $sort === 'recientes' ? 'selected' : '' ?>>Más recientes</option>
                                <option value="nombre" <?= $sort === 'nombre' ? 'selected' : '' ?>>Nombre (A-Z)</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button class="btn btn-gradient w-100"><i class="fas fa-search me-1"></i> Buscar</button>
                            <a href="BuscarPaseadores.php" class="btn btn-outline-secondary w-100">Limpiar</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Resultados -->
            <?php if (empty($paseadores)): ?>
                <div class="alert alert-info text-center">No se encontraron paseadores con esos filtros.</div>
            <?php else: ?>
                <div class="text-muted mb-2 small">Mostrando <?= count($paseadores) ?> de <?= $total ?> resultados</div>
                <div class="row g-4">
                    <?php foreach ($paseadores as $p): ?>
                        <div class="col-sm-6 col-lg-4">
                            <div class="card-premium h-100">
                                <?php if ($p['foto_url']): ?>
                                    <img src="<?= h($p['foto_url']) ?>" style="height:180px;object-fit:cover;width:100%;border-top-left-radius:14px;border-top-right-radius:14px;">
                                <?php else: ?>
                                    <div class="bg-light d-flex align-items-center justify-content-center" style="height:180px;"><i class="fas fa-user-circle fa-5x text-secondary"></i></div>
                                <?php endif; ?>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="mb-1"><?= h($p['nombre']) ?></h5>
                                    <p class="text-muted small mb-2"><i class="fas fa-map-marker-alt me-1"></i><?= h($p['zona'] ?: 'Sin zona') ?></p>
                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                        <span class="badge bg-success">⭐ <?= number_format((float)$p['calificacion'], 1) ?></span>
                                        <span class="badge bg-secondary">₲<?= number_format((float)$p['precio_hora'], 0, ',', '.') ?>/h</span>
                                        <span class="badge <?= (int)$p['disponible'] ? 'bg-primary' : 'bg-dark' ?>"><?= (int)$p['disponible'] ? 'Disponible' : 'No disponible' ?></span>
                                        <span class="badge bg-light text-dark">Paseos: <?= (int)$p['total_paseos'] ?></span>
                                    </div>
                                    <p class="text-muted small mb-3"><?= h(mb_strimwidth($p['descripcion'] ?? '', 0, 100, '…')) ?></p>
                                    <a href="SolicitarPaseo.php?paseador_id=<?= (int)$p['id'] ?>" class="btn btn-gradient mt-auto w-100"><i class="fas fa-paw me-1"></i> Solicitar Paseo</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Paginación -->
                <?php if ($totalPages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="?<?= qs(['page' => 1]) ?>">«</a></li>
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="?<?= qs(['page' => $page - 1]) ?>">‹</a></li>
                            <li class="page-item disabled"><span class="page-link">Página <?= $page ?>/<?= $totalPages ?></span></li>
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>"><a class="page-link" href="?<?= qs(['page' => $page + 1]) ?>">›</a></li>
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>"><a class="page-link" href="?<?= qs(['page' => $totalPages]) ?>">»</a></li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const toggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        toggle.addEventListener('click', () => sidebar.classList.toggle('show'));
    </script>
</body>

</html>