<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Helpers\Session;

AppConfig::init();
$auth = new AuthController();
$auth->checkRole('paseador');

function h($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$paseoController = new PaseoController();
$paseadorId = (int)(Session::get('usuario_id') ?? 0);
$paseos = $paseoController->indexForPaseador($paseadorId);

$estadosValidos = ['pendiente', 'confirmado', 'en_curso', 'completo', 'cancelado'];
$estadoFiltro = strtolower(trim((string)($_GET['estado'] ?? '')));
if ($estadoFiltro !== '' && in_array($estadoFiltro, $estadosValidos, true)) {
    $paseos = array_values(array_filter($paseos, fn($p) => strtolower($p['estado']) === $estadoFiltro));
}

$by = fn($s) => array_filter($paseos, fn($p) => strtolower($p['estado']) === $s);
$totalPaseos       = count($paseos);
$paseosPendientes  = count($by('pendiente')) + count($by('confirmado'));
$paseosCompletados = count($by('completo'));
$paseosCancelados  = count($by('cancelado'));
$ingresosTotales   = array_reduce($by('completo'), fn($a, $p) => $a + (float)($p['precio_total'] ?? 0), 0);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Paseos - Paseador | Jaguata</title>

    <!-- 🌿 Tema general Jaguata -->
    <link href="<?= ASSETS_URL; ?>/css/jaguata-theme.css" rel="stylesheet">

    <!-- Bootstrap y FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        /* === Layout Jaguata Paseador === */
        html,
        body {
            width: 100%;
            height: 100%;
            margin: 0;
            background-color: #f6f9f7;
            font-family: "Poppins", sans-serif;
        }

        .layout {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        /* Sidebar */
        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #2e2f45 100%);
            color: #fff;
            width: 240px;
            min-height: 100vh;
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
            transition: background 0.2s, transform 0.2s;
        }

        .sidebar .nav-link:hover {
            background-color: #3c6255;
            color: #fff;
            transform: translateX(4px);
        }

        .sidebar .nav-link.active {
            background: #20c997;
            color: #fff;
        }

        /* Contenido principal */
        main.content {
            flex-grow: 1;
            padding: 2.5rem;
            background-color: #f6f9f7;
        }

        /* Header */
        .page-header {
            background: linear-gradient(90deg, #20c997, #3c6255);
            color: #fff;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .page-header h2 {
            font-weight: 600;
            margin: 0;
        }

        /* Cards estadísticas */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, .08);
        }

        .card-header {
            background: linear-gradient(90deg, #3c6255, #20c997);
            color: #fff;
            font-weight: 600;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }

        /* Botón exportar */
        .btn-success {
            background-color: #3c6255;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all .2s ease-in-out;
        }

        .btn-success:hover {
            background-color: #2f4e45;
            transform: translateY(-2px);
        }

        /* Footer */
        footer {
            background-color: #3c6255;
            color: #fff;
            text-align: center;
            padding: 1.5rem 0;
            width: 100%;
            margin-top: 3rem;
        }

        /* Tabla */
        .table thead {
            background-color: #3c6255;
            color: #fff;
        }

        .table-hover tbody tr:hover {
            background-color: #eef8f2;
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../../src/Templates/SidebarPaseador.php'; ?>
    <main class="content">
        <div class="page-header mb-4">
            <h2><i class="fas fa-walking me-2"></i> Mis Paseos Asignados</h2>
            <a href="Dashboard.php" class="btn btn-outline-light btn-sm"><i class="fas fa-arrow-left me-1"></i> Volver</a>
        </div>

        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase">Total Paseos</h6>
                        <h3 class="fw-bold"><?= $totalPaseos ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase">Pendientes</h6>
                        <h3 class="fw-bold text-warning"><?= $paseosPendientes ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase">Completados</h6>
                        <h3 class="fw-bold text-success"><?= $paseosCompletados ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase">Ingresos Totales</h6>
                        <h3 class="fw-bold text-primary">₲<?= number_format($ingresosTotales, 0, ',', '.') ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end mb-3">
            <button class="btn btn-success d-flex align-items-center gap-2"
                onclick="window.location.href='/jaguata/public/api/paseos/exportarPaseosPaseador.php'">
                <i class="fas fa-file-excel"></i> Exportar Excel
            </button>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header"><i class="fas fa-filter me-2"></i> Filtros</div>
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Buscar</label>
                        <input type="text" id="searchInput" class="form-control" placeholder="Mascota o dueño...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Estado</label>
                        <select id="filterEstado" class="form-select">
                            <option value="">Todos</option>
                            <?php foreach ($estadosValidos as $v): ?>
                                <option value="<?= $v ?>" <?= $estadoFiltro === $v ? 'selected' : '' ?>>
                                    <?= ucfirst(str_replace('_', ' ', $v)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Desde</label>
                        <input type="date" id="filterDesde" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Hasta</label>
                        <input type="date" id="filterHasta" class="form-control">
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($paseos)): ?>
            <div class="text-center py-5">
                <i class="fas fa-dog fa-5x text-secondary mb-4"></i>
                <h4 class="text-muted">No tienes paseos asignados por el momento</h4>
            </div>
        <?php else: ?>
            <div class="card shadow-sm">
                <div class="card-header"><i class="fas fa-list me-2"></i> Lista de Paseos</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="tablaPaseos">
                            <thead>
                                <tr>
                                    <th>Mascota</th>
                                    <th>Dueño</th>
                                    <th>Fecha</th>
                                    <th>Duración</th>
                                    <th>Estado</th>
                                    <th>Pago</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($paseos as $p):
                                    $estado  = strtolower($p['estado']);
                                    $paseoId = (int)$p['paseo_id'];
                                    $badge = match ($estado) {
                                        'completo'   => 'success',
                                        'cancelado'  => 'danger',
                                        'en_curso'   => 'info',
                                        'confirmado' => 'primary',
                                        default      => 'warning'
                                    };
                                ?>
                                    <tr data-estado="<?= $estado ?>">
                                        <td><?= h($p['nombre_mascota'] ?? '') ?></td>
                                        <td><?= h($p['nombre_dueno'] ?? '') ?></td>
                                        <td>
                                            <strong><?= isset($p['inicio']) ? date('d/m/Y', strtotime($p['inicio'])) : '—' ?></strong><br>
                                            <small><?= isset($p['inicio']) ? date('H:i', strtotime($p['inicio'])) : '—' ?></small>
                                        </td>
                                        <td><?= h($p['duracion'] ?? $p['duracion_min'] ?? '') ?> min</td>
                                        <td><span class="badge bg-<?= $badge ?>"><?= ucfirst(str_replace('_', ' ', $estado)) ?></span></td>
                                        <td>
                                            <?php if (($p['estado_pago'] ?? '') === 'procesado'): ?>
                                                <span class="text-success">Pagado</span>
                                            <?php elseif (($p['estado_pago'] ?? '') === 'pendiente'): ?>
                                                <span class="text-warning">Pendiente</span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="VerPaseo.php?id=<?= $paseoId ?>" class="btn btn-sm btn-outline-primary" title="Ver">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($estado === 'pendiente'): ?>
                                                    <form action="AccionPaseo.php" method="post" class="d-inline">
                                                        <input type="hidden" name="id" value="<?= $paseoId ?>">
                                                        <input type="hidden" name="accion" value="confirmar">
                                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Confirmar">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                    <form action="AccionPaseo.php" method="post" class="d-inline">
                                                        <input type="hidden" name="id" value="<?= $paseoId ?>">
                                                        <input type="hidden" name="accion" value="cancelar">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Cancelar">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        </div>

        <footer>© <?= date('Y') ?> Jaguata — Todos los derechos reservados.</footer>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>