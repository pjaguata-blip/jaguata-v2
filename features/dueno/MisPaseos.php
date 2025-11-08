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

/* Controlador y datos */
$paseoCtrl = new PaseoController();
$duenoId   = (int) (Session::get('usuario_id') ?? 0);
$paseos    = $paseoCtrl->indexByDueno($duenoId) ?? [];

/* Normalización de estados */
$norm = static function (?string $s): string {
    return strtolower(trim((string)$s));
};

/* Filtro por estado (GET) */
$estadoFiltro = $norm($_GET['estado'] ?? '');
if ($estadoFiltro !== '') {
    $paseos = array_values(array_filter($paseos, fn($p) => $norm($p['estado'] ?? '') === $estadoFiltro));
}

/* Métricas (sobre todos los paseos del dueño, sin filtrar por GET) */
$all        = $paseoCtrl->indexByDueno($duenoId) ?? [];
$total      = count($all);
$pendientes = array_filter($all, fn($p) => in_array($norm($p['estado'] ?? ''), ['pendiente', 'confirmado'], true));
$completos  = array_filter($all, fn($p) => $norm($p['estado'] ?? '') === 'completo');
$cancelados = array_filter($all, fn($p) => $norm($p['estado'] ?? '') === 'cancelado');
$gastoTotal = array_sum(array_map(fn($p) => (float)($p['precio_total'] ?? 0), $completos));

/* Util */
$h = static function ($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
};

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Mis Paseos - Jaguata</title>
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
            color: var(--gris-texto);
            margin: 0
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
            padding: 1.8rem 2rem;
            border-radius: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .1)
        }

        .stat-card {
            background: var(--blanco);
            border-radius: 14px;
            text-align: center;
            padding: 1.5rem 1rem;
            box-shadow: 0 3px 15px rgba(0, 0, 0, .08)
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

        .btn-gradient {
            background: linear-gradient(90deg, var(--verde-jaguata), var(--verde-claro));
            border: none;
            color: #fff;
            font-weight: 500
        }

        .btn-gradient:hover {
            opacity: .92
        }

        .table thead {
            background: var(--verde-jaguata);
            color: #fff
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
        <div class="welcome-box mb-4">
            <div>
                <h1 class="fw-bold"><i class="fas fa-walking me-2"></i>Mis Paseos</h1>
                <p>Listado de paseos realizados, pendientes y cancelados 🐾</p>
            </div>
            <a href="SolicitarPaseo.php" class="btn btn-light fw-semibold">
                <i class="fas fa-plus me-1"></i> Solicitar nuevo paseo
            </a>
        </div>

        <!-- Métricas -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card"><i class="fas fa-list text-primary mb-2"></i>
                    <h4><?= $total ?></h4>
                    <p>Total</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card"><i class="fas fa-hourglass-half text-warning mb-2"></i>
                    <h4><?= count($pendientes) ?></h4>
                    <p>Pendientes</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card"><i class="fas fa-check-circle text-success mb-2"></i>
                    <h4><?= count($completos) ?></h4>
                    <p>Completados</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card"><i class="fas fa-wallet text-info mb-2"></i>
                    <h4>₲<?= number_format($gastoTotal, 0, ',', '.') ?></h4>
                    <p>Gasto total</p>
                </div>
            </div>
        </div>

        <!-- Lista / estado -->
        <?php if (empty($paseos)): ?>
            <div class="card p-5 text-center">
                <div class="mb-3"><i class="fas fa-dog fa-3x text-muted"></i></div>
                <h5 class="text-muted mb-3">No tenés paseos <?= $estadoFiltro ? 'en “' . $h($estadoFiltro) . '”' : 'registrados' ?>.</h5>
                <a href="SolicitarPaseo.php" class="btn btn-gradient"><i class="fas fa-plus me-1"></i> Solicitar tu primer paseo</a>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-list me-2"></i>Lista de Paseos</span>
                    <div class="d-flex align-items-center gap-2">
                        <label for="filtroEstado" class="text-white-50 small mb-0">Filtrar:</label>
                        <select class="form-select form-select-sm" id="filtroEstado" style="width:auto" onchange="aplicarFiltro()">
                            <?php
                            $opts = [
                                '' => 'Todos',
                                'pendiente' => 'Pendientes',
                                'confirmado' => 'Confirmados',
                                'en_curso' => 'En curso',
                                'completo' => 'Completos',
                                'cancelado' => 'Cancelados'
                            ];
                            ?>
                            <?php foreach ($opts as $val => $label): ?>
                                <option value="<?= $h($val) ?>" <?= $estadoFiltro === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="card-body p-0">
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
                                    $estado  = $norm($p['estado'] ?? '');
                                    $badge   = match ($estado) {
                                        'completo'   => 'success',
                                        'cancelado'  => 'danger',
                                        'en_curso'   => 'info',
                                        'confirmado' => 'primary',
                                        default      => 'warning', // pendiente u otros
                                    };
                                    ?>
                                    <tr>
                                        <td><i class="fas fa-paw text-success me-2"></i><?= $h($p['nombre_mascota'] ?? '-') ?></td>
                                        <td><i class="fas fa-user text-secondary me-2"></i><?= $h($p['nombre_paseador'] ?? '-') ?></td>
                                        <td><?= !empty($p['inicio']) ? date('d/m/Y H:i', strtotime((string)$p['inicio'])) : '-' ?></td>
                                        <td><?= (int)($p['duracion'] ?? 0) ?> min</td>
                                        <td><span class="badge bg-<?= $badge ?>"><?= ucfirst($estado ?: '-') ?></span></td>
                                        <td>₲<?= number_format((float)($p['precio_total'] ?? 0), 0, ',', '.') ?></td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a href="DetallePaseo.php?paseo_id=<?= (int)($p['paseo_id'] ?? 0) ?>" class="btn btn-sm btn-outline-primary" title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if (in_array($estado, ['pendiente', 'confirmado'], true)): ?>
                                                    <a href="CancelarPaseo.php?id=<?= (int)($p['paseo_id'] ?? 0) ?>"
                                                        class="btn btn-sm btn-outline-danger"
                                                        onclick="return confirm('¿Cancelar este paseo?')"
                                                        title="Cancelar">
                                                        <i class="fas fa-times"></i>
                                                    </a>
                                                    <a href="pago_paseo_dueno.php?paseo_id=<?= (int)($p['paseo_id'] ?? 0) ?>"
                                                        class="btn btn-sm btn-outline-success"
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

        <footer><small>© <?= date('Y') ?> Jaguata — Panel del Dueño</small></footer>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
    </script>
</body>

</html>