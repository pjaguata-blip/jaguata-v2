<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Helpers\Session;

AppConfig::init();

/* 🔒 Autenticación (rol dueño) */
$auth = new AuthController();
$auth->checkRole('dueno');

/* Rutas base */
$rolUsuario   = Session::getUsuarioRol() ?? 'dueno';
$baseFeatures = BASE_URL . "/features/{$rolUsuario}";

/* Controlador y datos */
$paseoCtrl = new PaseoController();
$duenoId   = (int)(Session::getUsuarioId() ?? 0);
$paseos    = $duenoId ? ($paseoCtrl->indexByDueno($duenoId) ?? []) : [];

/* Normalización de estados */
$norm = static function (?string $s): string {
    return strtolower(trim((string)$s));
};

/* Filtro por estado (GET) */
$estadoFiltro = $norm($_GET['estado'] ?? '');
if ($estadoFiltro !== '') {
    $paseos = array_values(array_filter(
        $paseos,
        fn($p) => $norm($p['estado'] ?? '') === $estadoFiltro
    ));
}

/* Métricas (sobre todos los paseos del dueño, sin filtrar por GET) */
$all        = $duenoId ? ($paseoCtrl->indexByDueno($duenoId) ?? []) : [];
$total      = count($all);
$pendientes = array_filter($all, fn($p) => in_array($norm($p['estado'] ?? ''), ['pendiente', 'confirmado'], true));
$completos  = array_filter($all, fn($p) => $norm($p['estado'] ?? '') === 'completo');
$cancelados = array_filter($all, fn($p) => $norm($p['estado'] ?? '') === 'cancelado');
$gastoTotal = array_sum(array_map(fn($p) => (float)($p['precio_total'] ?? 0), $completos));

/* Util */
$h = static function ($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
};

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Mis Paseos - Jaguata</title>

    <!-- CSS global -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <style>
        /* Que estire como los otros dashboards */
        html,
        body {
            height: 100%;
        }

        body {
            background: var(--gris-fondo, #f4f6f9);
        }

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

        /* Tarjetas tipo dashboard */
        .dash-card {
            background: #ffffff;
            border-radius: 18px;
            padding: 18px 20px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.06);
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 6px;
        }

        .dash-card-icon {
            font-size: 2rem;
            margin-bottom: 6px;
        }

        .dash-card-value {
            font-size: 1.4rem;
            font-weight: 700;
            color: #222;
        }

        .dash-card-label {
            font-size: 0.9rem;
            color: #555;
        }

        .icon-blue {
            color: #0d6efd;
        }

        .icon-green {
            color: var(--verde-jaguata, #3c6255);
        }

        .icon-yellow {
            color: #ffc107;
        }

        .icon-red {
            color: #dc3545;
        }
    </style>
</head>

<body>

    <!-- Sidebar dueño -->
    <?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>

    <!-- Botón hamburguesa para mobile -->
    <button class="btn btn-outline-secondary d-md-none ms-2 mt-3" id="toggleSidebar">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Contenido principal -->
    <main class="main-content">
        <div class="py-2">

            <!-- Header -->
            <div class="header-box header-paseos mb-4 d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="fw-bold mb-1">
                        <i class="fas fa-walking me-2"></i>Mis Paseos
                    </h1>
                    <p class="mb-0">Listado de paseos realizados, pendientes y cancelados 🐾</p>
                </div>
                <div class="d-none d-md-block">
                    <a href="<?= $baseFeatures; ?>/SolicitarPaseo.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-plus me-1"></i> Agregar un nuevo paseo
                    </a>
                </div>
            </div>

            <!-- Métricas (tarjetas como dashboard) -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="dash-card">
                        <i class="fas fa-list dash-card-icon icon-blue"></i>
                        <div class="dash-card-value"><?= $total; ?></div>
                        <div class="dash-card-label">Total de paseos</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="dash-card">
                        <i class="fas fa-hourglass-half dash-card-icon icon-yellow"></i>
                        <div class="dash-card-value"><?= count($pendientes); ?></div>
                        <div class="dash-card-label">Pendientes / Confirmados</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="dash-card">
                        <i class="fas fa-check-circle dash-card-icon icon-green"></i>
                        <div class="dash-card-value"><?= count($completos); ?></div>
                        <div class="dash-card-label">Completados</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="dash-card">
                        <i class="fas fa-wallet dash-card-icon icon-red"></i>
                        <div class="dash-card-value">₲<?= number_format($gastoTotal, 0, ',', '.'); ?></div>
                        <div class="dash-card-label">Gasto total</div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="filtros d-flex flex-wrap align-items-center justify-content-between">
                <div class="mb-2 mb-md-0">
                    <strong>Filtrar por estado:</strong>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <label for="filtroEstado" class="mb-0 small text-muted">Estado</label>
                    <select class="form-select form-select-sm" id="filtroEstado" style="min-width: 180px;" onchange="aplicarFiltro()">
                        <?php
                        $opts = [
                            ''           => 'Todos',
                            'pendiente'  => 'Pendientes',
                            'confirmado' => 'Confirmados',
                            'en_curso'   => 'En curso',
                            'completo'   => 'Completos',
                            'cancelado'  => 'Cancelados',
                        ];
                        ?>
                        <?php foreach ($opts as $val => $label): ?>
                            <option value="<?= $h($val); ?>" <?= $estadoFiltro === $val ? 'selected' : ''; ?>>
                                <?= $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Lista / estado -->
            <?php if (empty($paseos)): ?>
                <div class="section-card text-center">
                    <div class="mb-3">
                        <i class="fas fa-dog fa-3x text-muted"></i>
                    </div>
                    <h5 class="text-muted mb-3">
                        No tenés paseos
                        <?= $estadoFiltro ? 'en “' . $h($estadoFiltro) . '”' : 'registrados'; ?>.
                    </h5>
                    <a href="<?= $baseFeatures; ?>/SolicitarPaseo.php" class="btn-enviar">
                        <i class="fas fa-plus me-1"></i> Solicitar tu primer paseo
                    </a>
                </div>
            <?php else: ?>
                <div class="section-card">
                    <div class="section-header">
                        <i class="fas fa-list me-2"></i> Lista de Paseos
                    </div>
                    <div class="section-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Mascota</th>
                                        <th>Paseador</th>
                                        <th>Fecha</th>
                                        <th>Duración</th>
                                        <th>Estado</th>
                                        <th>Precio</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($paseos as $p): ?>
                                        <?php
                                        $estado = $norm($p['estado'] ?? '');
                                        $badge  = match ($estado) {
                                            'completo'   => 'success',
                                            'cancelado'  => 'danger',
                                            'en_curso'   => 'info',
                                            'confirmado' => 'primary',
                                            default      => 'warning', // pendiente u otros
                                        };
                                        ?>
                                        <tr>
                                            <td>
                                                <i class="fas fa-paw text-success me-2"></i>
                                                <?= $h($p['nombre_mascota'] ?? '-'); ?>
                                            </td>
                                            <td>
                                                <i class="fas fa-user text-secondary me-2"></i>
                                                <?= $h($p['nombre_paseador'] ?? '-'); ?>
                                            </td>
                                            <td>
                                                <?= !empty($p['inicio'])
                                                    ? date('d/m/Y H:i', strtotime((string)$p['inicio']))
                                                    : '-'; ?>
                                            </td>
                                            <td><?= (int)($p['duracion'] ?? 0); ?> min</td>
                                            <td>
                                                <span class="badge bg-<?= $badge; ?>">
                                                    <?= ucfirst($estado ?: '-'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                ₲<?= number_format((float)($p['precio_total'] ?? 0), 0, ',', '.'); ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <!-- 🔍 Botón Ver con el mismo estilo que admin (btn-ver) -->
                                                    <a href="<?= $baseFeatures; ?>/DetallePaseo.php?paseo_id=<?= (int)($p['paseo_id'] ?? 0); ?>"
                                                        class="btn-ver"
                                                        title="Ver detalles">
                                                        <i class="fas fa-eye"></i> Ver
                                                    </a>

                                                    <?php if (in_array($estado, ['pendiente', 'confirmado'], true)): ?>
                                                        <a href="<?= $baseFeatures; ?>/CancelarPaseo.php?id=<?= (int)($p['paseo_id'] ?? 0); ?>"
                                                            class="btn btn-sm btn-accion btn-rechazar"
                                                            onclick="return confirm('¿Cancelar este paseo?')"
                                                            title="Cancelar">
                                                            <i class="fas fa-times"></i>
                                                        </a>
                                                        <a href="<?= $baseFeatures; ?>/pago_paseo_dueno.php?paseo_id=<?= (int)($p['paseo_id'] ?? 0); ?>"
                                                            class="btn btn-sm btn-accion btn-activar"
                                                            title="Pagar">
                                                            <i class="fas fa-wallet"></i>
                                                        </a>
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

            <footer class="mt-4 text-center text-muted small">
                © <?= date('Y'); ?> Jaguata — Panel del Dueño
            </footer>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filtro por estado (mantiene backend tal cual, solo manipula querystring)
        function aplicarFiltro() {
            const estado = document.getElementById('filtroEstado').value;
            const url = new URL(window.location.href);
            if (estado) {
                url.searchParams.set('estado', estado);
            } else {
                url.searchParams.delete('estado');
            }
            window.location.replace(url.toString());
        }

        // Toggle sidebar en mobile
        document.getElementById('toggleSidebar')?.addEventListener('click', function() {
            document.getElementById('sidebar')?.classList.toggle('sidebar-open');
        });
    </script>
</body>

</html>