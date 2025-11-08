<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Helpers\Session;

AppConfig::init();

/* 🔒 Rol: paseador */
$auth = new AuthController();
$auth->checkRole('paseador');

$baseFeatures = BASE_URL . '/features/paseador';

/* Helpers */
function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function clsEstado(string $estado): string
{
    $e = mb_strtolower($estado);
    return $e === 'pendiente' ? 'warning'
        : ($e === 'en progreso' ? 'info'
            : ($e === 'resuelto' ? 'success' : 'secondary'));
}

/* Tickets (simulado; reemplazar por consulta real) — SOLO del paseador */
$tickets = [
    ['id' => 501, 'asunto' => 'App lenta al iniciar ruta', 'mensaje' => 'La pantalla tarda en cargar cuando inicio un paseo.', 'estado' => 'Pendiente', 'respuesta' => null, 'fecha' => '2025-11-03 08:40:00'],
    ['id' => 502, 'asunto' => 'Problema con geolocalización', 'mensaje' => 'El mapa no actualiza mi posición en tiempo real.', 'estado' => 'En progreso', 'respuesta' => 'Estamos revisando los permisos de ubicación.', 'fecha' => '2025-10-30 16:10:00'],
    ['id' => 503, 'asunto' => 'Sugerencia de UX', 'mensaje' => 'Sería útil marcar disponibilidad semanal rápida.', 'estado' => 'Resuelto', 'respuesta' => 'Se incluirá en el próximo release.', 'fecha' => '2025-10-25 12:05:00'],
];

/* Alta de ticket (simulada) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'crearTicket') {
    $_SESSION['success'] = 'Tu ticket fue enviado. ✅';
    header('Location: Soporte_paseador.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Soporte - Paseador | Jaguata</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="<?= ASSETS_URL; ?>/css/jaguata-theme.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --verde-jaguata: #3c6255;
            --verde-claro: #20c997;
            --gris-fondo: #f5f7fa;
        }

        body {
            background: var(--gris-fondo);
            font-family: "Poppins", sans-serif
        }

        main {
            margin-left: 250px;
            padding: 2rem
        }

        .page-header {
            background: linear-gradient(90deg, var(--verde-claro), var(--verde-jaguata));
            color: #fff;
            border-radius: 10px;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, .07)
        }

        .card-header {
            background: linear-gradient(90deg, var(--verde-jaguata), var(--verde-claro));
            color: #fff;
            font-weight: 600
        }

        .table thead th {
            background: var(--verde-jaguata);
            color: #fff;
            border: none
        }

        .table-hover tbody tr:hover {
            background: #e6f4ea
        }

        .btn-outline-secondary {
            border-color: var(--verde-claro);
            color: var(--verde-claro)
        }

        .btn-outline-secondary:hover {
            background: var(--verde-claro);
            color: #fff
        }

        .btn-success {
            background: var(--verde-claro);
            border-color: var(--verde-claro)
        }

        .btn-success:hover {
            background: var(--verde-jaguata);
            border-color: var(--verde-jaguata)
        }
    </style>
</head>

<body>

    <?php include __DIR__ . '/../../src/Templates/SidebarPaseador.php'; ?>

    <main>
        <!-- Encabezado consistente -->
        <div class="page-header">
            <h1 class="h4 m-0"><i class="fas fa-headset me-2"></i>Soporte (Paseador)</h1>
            <a href="Dashboard.php" class="btn btn-outline-light btn-sm"><i class="fas fa-arrow-left me-1"></i> Volver</a>
        </div>

        <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?= h($_SESSION['success']);
                                                        unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Crear ticket -->
        <div class="card mb-3">
            <div class="card-header"><i class="fas fa-plus me-2"></i>Crear ticket</div>
            <div class="card-body">
                <form method="post" class="row g-3">
                    <input type="hidden" name="action" value="crearTicket">
                    <div class="col-12">
                        <label class="form-label">Asunto</label>
                        <input type="text" name="asunto" class="form-control" placeholder="Ej.: Problema con geolocalización" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Mensaje</label>
                        <textarea name="mensaje" rows="4" class="form-control" placeholder="Describe el problema o consulta…" required></textarea>
                    </div>
                    <div class="col-12 text-end">
                        <button class="btn btn-success">
                            <i class="fas fa-paper-plane me-1"></i> Enviar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card mb-3">
            <div class="card-body">
                <form class="row g-2">
                    <div class="col-md-5">
                        <input type="text" class="form-control" placeholder="Buscar por asunto o mensaje…">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select">
                            <option value="">Todos los estados</option>
                            <option value="Pendiente">Pendiente</option>
                            <option value="En progreso">En progreso</option>
                            <option value="Resuelto">Resuelto</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="date" class="form-control">
                    </div>
                    <div class="col-md-1">
                        <button class="btn btn-outline-secondary w-100">
                            <i class="fas fa-filter me-1"></i> Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla -->
        <div class="card">
            <div class="card-body">
                <?php if (empty($tickets)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-bell-slash fa-2x mb-2"></i>
                        <p>No hay tickets aún.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-center">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Asunto</th>
                                    <th>Mensaje</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th>Ver</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tickets as $t): ?>
                                    <?php $cls = clsEstado($t['estado']); ?>
                                    <tr>
                                        <td><?= (int)$t['id'] ?></td>
                                        <td class="text-start"><?= h($t['asunto']) ?></td>
                                        <td class="text-start"><?= h($t['mensaje']) ?></td>
                                        <td><span class="badge bg-<?= $cls ?>"><?= h($t['estado']) ?></span></td>
                                        <td><?= date('d/m/Y H:i', strtotime($t['fecha'])) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-secondary"
                                                data-bs-toggle="modal" data-bs-target="#ticketModal"
                                                data-id="<?= (int)$t['id'] ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal -->
    <div class="modal fade" id="ticketModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background:var(--verde-jaguata);color:#fff">
                    <h5 class="modal-title"><i class="fas fa-envelope-open-text me-2"></i>Detalle del ticket</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6><strong>Asunto:</strong> <span id="mAsunto">—</span></h6>
                    <p class="mt-2" id="mMensaje">—</p>
                    <hr>
                    <h6><strong>Respuesta del equipo:</strong></h6>
                    <p id="mRespuesta" class="text-success">—</p>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../../src/Templates/Footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const tickets = <?= json_encode($tickets, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const modal = document.getElementById('ticketModal');
        const mAsunto = document.getElementById('mAsunto');
        const mMensaje = document.getElementById('mMensaje');
        const mRespuesta = document.getElementById('mRespuesta');

        modal.addEventListener('show.bs.modal', e => {
            const id = e.relatedTarget.getAttribute('data-id');
            const t = tickets.find(x => x.id == id);
            if (!t) return;
            mAsunto.textContent = t.asunto || '—';
            mMensaje.textContent = t.mensaje || '—';
            mRespuesta.textContent = t.respuesta ?? 'Aún no hay respuesta.';
        });
    </script>
</body>

</html>