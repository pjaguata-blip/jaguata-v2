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
$paseadorId      = (int)(Session::getUsuarioId() ?? 0);
$paseos          = $paseoController->indexForPaseador($paseadorId);

$estadosValidos = ['pendiente', 'confirmado', 'en_curso', 'completo', 'cancelado'];
$estadoFiltro   = strtolower(trim((string)($_GET['estado'] ?? '')));

if ($estadoFiltro !== '' && in_array($estadoFiltro, $estadosValidos, true)) {
    $paseos = array_values(
        array_filter(
            $paseos,
            fn($p) => strtolower($p['estado']) === $estadoFiltro
        )
    );
}

$by = fn($s) => array_filter($paseos, fn($p) => strtolower($p['estado']) === $s);

$totalPaseos       = count($paseos);
$paseosPendientes  = count($by('pendiente')) + count($by('confirmado'));
$paseosCompletados = count($by('completo'));
$paseosCancelados  = count($by('cancelado'));
$ingresosTotales   = array_reduce(
    $by('completo'),
    fn($a, $p) => $a + (float)($p['precio_total'] ?? 0),
    0
);

// Para links base
$rolMenu      = Session::getUsuarioRol() ?: 'paseador';
$baseFeatures = BASE_URL . "/features/{$rolMenu}";
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
</head>

<body>
    <!-- Botón hamburguesa mobile -->
    <button class="btn btn-outline-secondary d-md-none ms-2 mt-2" id="toggleSidebar">
        <i class="fas fa-bars"></i>
    </button>

    <div class="layout">
        <!-- Sidebar unificado del paseador -->
        <?php include __DIR__ . '/../../src/Templates/SidebarPaseador.php'; ?>

        <!-- Contenido principal -->
        <main class="content bg-light">
            <div class="container-fluid py-4">

                <!-- Header con estilo global -->
                <div class="header-box header-dashboard mb-4">
                    <div>
                        <h1 class="h4 mb-1">
                            <i class="fas fa-walking me-2"></i> Mis paseos asignados
                        </h1>
                        <p class="mb-0 text-white-50">
                            Revisá el historial de tus paseos, estados e ingresos.
                        </p>
                    </div>
                    <a href="<?= $baseFeatures; ?>/Dashboard.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>

                <!-- Cards de estadísticas -->
                <div class="row g-3 mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card text-center">
                            <i class="fas fa-list text-success mb-2"></i>
                            <h4><?= $totalPaseos ?></h4>
                            <p class="mb-0">Total de paseos</p>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card text-center">
                            <i class="fas fa-hourglass-half text-warning mb-2"></i>
                            <h4><?= $paseosPendientes ?></h4>
                            <p class="mb-0">Pendientes / confirmados</p>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card text-center">
                            <i class="fas fa-check-circle text-primary mb-2"></i>
                            <h4><?= $paseosCompletados ?></h4>
                            <p class="mb-0">Completados</p>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card text-center">
                            <i class="fas fa-wallet text-info mb-2"></i>
                            <h4>₲<?= number_format($ingresosTotales, 0, ',', '.') ?></h4>
                            <p class="mb-0">Ingresos totales</p>
                        </div>
                    </div>
                </div>

                <!-- Botón exportar -->
                <div class="d-flex justify-content-end mb-3">
                    <button class="btn btn-success d-flex align-items-center gap-2"
                        onclick="window.location.href='<?= BASE_URL; ?>/public/api/paseos/exportarPaseosPaseador.php'">
                        <i class="fas fa-file-excel"></i> Exportar Excel
                    </button>
                </div>

                <!-- Filtros -->
                <div class="card jag-card shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <i class="fas fa-filter me-2"></i>Filtros
                    </div>
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label">Buscar</label>
                                <input type="text" id="searchInput" class="form-control" placeholder="Mascota o dueño...">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Estado</label>
                                <select id="filterEstado" class="form-select" onchange="aplicarFiltroEstado()">
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

                <!-- Tabla de paseos -->
                <?php if (empty($paseos)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-dog fa-5x text-secondary mb-4"></i>
                        <h4 class="text-muted">No tenés paseos asignados por el momento</h4>
                    </div>
                <?php else: ?>
                    <div class="card jag-card shadow-sm">
                        <div class="card-header bg-success text-white">
                            <i class="fas fa-list me-2"></i>Lista de paseos
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle" id="tablaPaseos">
                                    <thead class="table-light">
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
                                                <td><?= h($p['mascota_nombre'] ?? $p['nombre_mascota'] ?? '') ?></td>
                                                <td><?= h($p['dueno_nombre'] ?? $p['nombre_dueno'] ?? '') ?></td>
                                                <td>
                                                    <strong><?= isset($p['inicio']) ? date('d/m/Y', strtotime($p['inicio'])) : '—' ?></strong><br>
                                                    <small><?= isset($p['inicio']) ? date('H:i', strtotime($p['inicio'])) : '—' ?></small>
                                                </td>
                                                <td><?= h($p['duracion'] ?? $p['duracion_min'] ?? '') ?> min</td>
                                                <td>
                                                    <span class="badge bg-<?= $badge ?>">
                                                        <?= ucfirst(str_replace('_', ' ', $estado)) ?>
                                                    </span>
                                                </td>
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

                <footer class="text-center text-muted small mt-4">
                    &copy; <?= date('Y') ?> Jaguata — Todos los derechos reservados.
                </footer>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar en mobile
        document.getElementById('toggleSidebar')?.addEventListener('click', function() {
            document.getElementById('sidebar')?.classList.toggle('sidebar-open');
        });

        // Pequeño helper para recargar con filtro de estado
        function aplicarFiltroEstado() {
            const estado = document.getElementById('filterEstado')?.value || '';
            const url = new URL(window.location.href);
            if (estado) {
                url.searchParams.set('estado', estado);
            } else {
                url.searchParams.delete('estado');
            }
            window.location.href = url.toString();
        }
    </script>
</body>

</html>