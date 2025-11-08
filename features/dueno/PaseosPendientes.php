<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Controllers/MascotaController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Controllers\MascotaController;
use Jaguata\Helpers\Session;

AppConfig::init();

/* 🔒 Autenticación rol dueño */
$auth = new AuthController();
$auth->checkRole('dueno');

/* Rutas base UI */
$baseFeatures = BASE_URL . '/features/dueno';

/* Controladores */
$paseoController   = new PaseoController();
$mascotaController = new MascotaController();

/* Datos crudos */
$allPaseos = $paseoController->index() ?? [];
$mascotas  = $mascotaController->index() ?? [];

/* Helpers UI/dominio */
function fmtDate($v, string $format = 'd/m/Y H:i'): string
{
    if (!$v) return '-';
    $ts = is_numeric($v) ? (int)$v : strtotime((string)$v);
    return $ts ? date($format, $ts) : '-';
}
function fmtInt($v, string $suf = ' min'): string
{
    return ($v !== null && $v !== '') ? ((int)$v) . $suf : '-';
}
function fmtGs($v): string
{
    return '₲' . number_format((float)($v ?: 0), 0, ',', '.');
}
function estadoToBadge(string $estadoRaw): array
{
    $lc = mb_strtolower($estadoRaw);
    return match ($lc) {
        'pendiente', 'solicitado' => ['warning', '🕓 Solicitado'],
        'confirmado'             => ['primary', '📅 Confirmado'],
        'en_curso'               => ['info', '🚶 En curso'],
        'completo', 'completado'  => ['success', '✅ Completo'],
        'cancelado'              => ['danger', '❌ Cancelado'],
        default                  => ['secondary', ucfirst($estadoRaw ?: '-')],
    };
}

/* Parámetros de filtro GET */
$q      = trim((string)($_GET['q'] ?? ''));
$desde  = $_GET['desde'] ?? null;
$hasta  = $_GET['hasta'] ?? null;

/* Normalización de fechas (00:00 a 23:59) */
$normalizeTs = function (?string $d, bool $end = false): ?int {
    if (!$d) return null;
    $dt = DateTime::createFromFormat('Y-m-d', $d) ?: (strtotime($d) ? new DateTime($d) : null);
    if (!$dt) return null;
    $dt->setTime($end ? 23 : 0, $end ? 59 : 0, $end ? 59 : 0);
    return $dt->getTimestamp();
};
$tsDesde = $normalizeTs($desde, false);
$tsHasta = $normalizeTs($hasta, true);

/* Paseos solo del dueño (por mascotas del dueño) */
$idsMascotasDueno = [];
foreach ($mascotas as $m) {
    $mid = $m['mascota_id'] ?? $m['id'] ?? null;
    if ($mid !== null) $idsMascotasDueno[(int)$mid] = true;
}
$soloDueno = array_values(array_filter(
    $allPaseos,
    fn($p) => isset($idsMascotasDueno[(int)($p['mascota_id'] ?? 0)])
));

/* Pendientes = solicitado/pendiente/confirmado (opcionalmente acotar por fecha y búsqueda) */
$paseos = array_values(array_filter($soloDueno, function ($p) use ($q, $tsDesde, $tsHasta) {
    $estado = mb_strtolower((string)($p['estado'] ?? ''));
    if (!in_array($estado, ['pendiente', 'solicitado', 'confirmado', 'en_curso'], true)) return false;

    $iniTs = isset($p['inicio'])
        ? (is_numeric($p['inicio']) ? (int)$p['inicio'] : strtotime((string)$p['inicio']))
        : null;

    if ($tsDesde && $iniTs && $iniTs < $tsDesde) return false;
    if ($tsHasta && $iniTs && $iniTs > $tsHasta) return false;

    if ($q !== '') {
        foreach (['nombre_mascota', 'nombre_paseador', 'comentario', 'estado'] as $k) {
            if (!empty($p[$k]) && stripos((string)$p[$k], $q) !== false) return true;
        }
        return false;
    }
    return true;
}));

/* Escape rápido */
$h = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Paseos Pendientes - Jaguata</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --verde-jaguata: #3c6255;
            --verde-claro: #20c997;
            --gris-fondo: #f4f6f9;
            --gris-texto: #555;
            --blanco: #fff;
        }

        body {
            background: var(--gris-fondo);
            font-family: "Poppins", sans-serif;
            color: var(--gris-texto)
        }

        /* Sidebar (como admin) */
        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            color: var(--blanco);
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 1.5rem;
            box-shadow: 3px 0 10px rgba(0, 0, 0, .2)
        }

        .sidebar .nav-link {
            color: #ccc;
            display: flex;
            align-items: center;
            gap: .8rem;
            padding: 12px 18px;
            border-radius: 8px;
            margin: 6px 10px;
            transition: .2s;
            font-size: .95rem
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: var(--verde-claro);
            color: var(--blanco);
            transform: translateX(4px)
        }

        /* Main */
        main {
            margin-left: 250px;
            padding: 2rem
        }

        .welcome-box {
            background: linear-gradient(90deg, var(--verde-claro), var(--verde-jaguata));
            color: var(--blanco);
            padding: 1.6rem 2rem;
            border-radius: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .1)
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, .07)
        }

        .card-header {
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            background: var(--verde-jaguata);
            color: #fff;
            font-weight: 600
        }

        .table thead {
            background: var(--verde-jaguata);
            color: #fff
        }

        .table-hover tbody tr:hover {
            background: #eef8f2
        }

        .btn-gradient {
            background: linear-gradient(90deg, var(--verde-jaguata), var(--verde-claro));
            border: none;
            color: #fff;
            font-weight: 500
        }

        .btn-gradient:hover {
            opacity: .92
        }

        footer {
            text-align: center;
            padding: 1rem;
            color: #777;
            font-size: .9rem;
            margin-top: 2rem
        }
    </style>
</head>

<body>

    <?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>

    <main>
        <!-- Header -->
        <div class="welcome-box">
            <div>
                <h1 class="fw-bold"><i class="fas fa-walking me-2"></i>Paseos Pendientes</h1>
                <p>Consultá y gestioná tus paseos en estado pendiente, confirmado o en curso.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= $baseFeatures ?>/SolicitarPaseo.php" class="btn btn-light fw-semibold">
                    <i class="fas fa-plus me-1"></i> Nuevo paseo
                </a>
                <a href="<?= $baseFeatures ?>/ExportarDashboard.php?estado=pendiente" class="btn btn-outline-light fw-semibold">
                    <i class="fas fa-file-export me-1"></i> Exportar
                </a>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <form class="row g-3" method="get">
                    <div class="col-sm-3">
                        <label class="form-label fw-semibold">Desde</label>
                        <input type="date" name="desde" value="<?= $h($desde) ?>" class="form-control">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label fw-semibold">Hasta</label>
                        <input type="date" name="hasta" value="<?= $h($hasta) ?>" class="form-control">
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label fw-semibold">Buscar</label>
                        <input type="text" name="q" value="<?= $h($q) ?>" placeholder="Mascota, paseador, estado..." class="form-control">
                    </div>
                    <div class="col-sm-2 d-flex align-items-end">
                        <button class="btn btn-gradient w-100"><i class="fas fa-search me-1"></i> Filtrar</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla -->
        <div class="card">
            <div class="card-body">
                <?php if (empty($paseos)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-dog fa-3x mb-3 text-success"></i>
                        <p>No hay paseos pendientes con los filtros aplicados.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-center mb-0">
                            <thead>
                                <tr>
                                    <th>Mascota</th>
                                    <th>Paseador</th>
                                    <th>Inicio</th>
                                    <th>Fin</th>
                                    <th>Duración</th>
                                    <th>Precio</th>
                                    <th>Estado</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($paseos as $p):
                                    $inicio   = fmtDate($p['inicio'] ?? null);
                                    $fin      = fmtDate($p['fin'] ?? null);
                                    $duracion = fmtInt($p['duracion_min'] ?? $p['duracion'] ?? null);
                                    $precio   = fmtGs($p['precio_total'] ?? null);
                                    [$cls, $txt] = estadoToBadge((string)($p['estado'] ?? ''));
                                    $estadoLc = mb_strtolower((string)($p['estado'] ?? ''));
                                    $puedePagar = in_array($estadoLc, ['pendiente', 'solicitado', 'confirmado'], true) && !empty($p['paseo_id']);
                                ?>
                                    <tr>
                                        <td><?= $h($p['nombre_mascota'] ?? '-') ?></td>
                                        <td><?= $h($p['nombre_paseador'] ?? '-') ?></td>
                                        <td><?= $inicio ?></td>
                                        <td><?= $fin ?></td>
                                        <td><?= $duracion ?></td>
                                        <td><?= $precio ?></td>
                                        <td><span class="badge bg-<?= $cls ?>"><?= $txt ?></span></td>
                                        <td>
                                            <?php if ($puedePagar): ?>
                                                <a href="<?= $baseFeatures ?>/pago_paseo_dueno.php?paseo_id=<?= (int)($p['paseo_id'] ?? 0) ?>"
                                                    class="btn btn-sm btn-outline-success">
                                                    <i class="fas fa-wallet me-1"></i> Pagar
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <footer><small>© <?= date('Y') ?> Jaguata — Panel del Dueño</small></footer>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>