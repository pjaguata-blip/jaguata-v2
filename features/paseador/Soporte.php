<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/AuthController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Controllers\AuthController;

AppConfig::init();

// 🔒 Verificación de sesión y rol
$auth = new AuthController();
$auth->checkRole('paseador');

// Datos simulados de tickets de soporte
$tickets = [
    [
        'id' => 1,
        'asunto' => 'Error al registrar un paseo',
        'mensaje' => 'Intenté registrar un paseo y no se guardó correctamente.',
        'estado' => 'Pendiente',
        'respuesta' => null,
        'fecha' => '2025-10-28 09:45:00'
    ],
    [
        'id' => 2,
        'asunto' => 'Problema con el pago recibido',
        'mensaje' => 'Recibí un monto menor al esperado por un paseo completado.',
        'estado' => 'En progreso',
        'respuesta' => 'Estamos verificando con el área de pagos.',
        'fecha' => '2025-10-27 17:30:00'
    ],
    [
        'id' => 3,
        'asunto' => 'Sugerencia sobre la app',
        'mensaje' => 'Sería útil poder marcar mis horarios favoritos.',
        'estado' => 'Resuelto',
        'respuesta' => 'Gracias por tu sugerencia, será considerada en la próxima actualización.',
        'fecha' => '2025-10-25 11:20:00'
    ]
];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soporte - Jaguata Paseador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --verde-jaguata: #3c6255;
            --verde-claro: #20c997;
            --gris-fondo: #f4f6f9;
            --blanco: #fff;
            --gris-texto: #555;
        }

        body {
            background: var(--gris-fondo);
            font-family: "Poppins", sans-serif;
            color: var(--gris-texto);
        }

        /* Sidebar */
        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            color: var(--blanco);
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 1.5rem;
        }

        .sidebar .nav-link {
            color: #ccc;
            display: flex;
            align-items: center;
            gap: .8rem;
            padding: 12px 18px;
            border-radius: 8px;
            margin: 6px 10px;
            transition: all .2s ease;
            font-size: 0.95rem;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: var(--verde-claro);
            color: var(--blanco);
            transform: translateX(4px);
        }

        main {
            margin-left: 250px;
            padding: 2rem;
        }

        /* Header */
        .header-box {
            background: linear-gradient(90deg, var(--verde-claro), var(--verde-jaguata));
            color: var(--blanco);
            padding: 1.5rem 2rem;
            border-radius: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        /* Table */
        .table {
            background: var(--blanco);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        }

        .table thead {
            background: var(--verde-jaguata);
            color: var(--blanco);
        }

        .badge-pendiente {
            background: #ffc107;
            color: #000;
        }

        .badge-en-progreso {
            background: #0dcaf0;
            color: #000;
        }

        .badge-resuelto {
            background: #20c997;
            color: #fff;
        }

        .btn-ver {
            background: var(--verde-claro);
            color: var(--blanco);
            border: none;
            padding: 6px 12px;
            border-radius: 8px;
        }

        .btn-ver:hover {
            background: var(--verde-jaguata);
        }

        footer {
            text-align: center;
            color: #777;
            font-size: 0.9rem;
            padding: 1rem;
            margin-top: 2rem;
        }

        /* Modal */
        .modal-content {
            border-radius: 12px;
            overflow: hidden;
        }

        .modal-header {
            background: var(--verde-jaguata);
            color: var(--blanco);
        }

        /* Filtros */
        .filter-box {
            background: #fff;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="text-center mb-4">
            <img src="<?= ASSETS_URL; ?>/uploads/perfiles/logojag.png" alt="Logo" width="60">
            <h6 class="mt-2 fw-bold text-success">Jaguata Paseador</h6>
            <hr class="text-light">
        </div>
        <ul class="nav flex-column">
            <li><a class="nav-link" href="Dashboard.php"><i class="fas fa-home"></i>Inicio</a></li>
            <li><a class="nav-link" href="MisPaseos.php"><i class="fas fa-list"></i>Mis Paseos</a></li>
            <li><a class="nav-link" href="Disponibilidad.php"><i class="fas fa-calendar-check"></i>Disponibilidad</a></li>
            <li><a class="nav-link" href="Estadisticas.php"><i class="fas fa-chart-line"></i>Estadísticas</a></li>
            <li><a class="nav-link" href="Mensajeria.php"><i class="fas fa-comments"></i>Mensajería</a></li>
            <li><a class="nav-link active" href="#"><i class="fas fa-headset"></i>Soporte</a></li>
            <li><a class="nav-link text-danger" href="/jaguata/public/logout.php"><i class="fas fa-sign-out-alt"></i>Salir</a></li>
        </ul>
    </aside>

    <!-- Contenido -->
    <main>
        <div class="header-box">
            <div>
                <h1 class="fw-bold">Centro de Soporte</h1>
                <p>Consulta tus tickets y comunícate con el equipo de soporte técnico 🧑‍💻</p>
            </div>
            <i class="fas fa-headset fa-3x opacity-75"></i>
        </div>

        <!-- Filtros -->
        <div class="filter-box">
            <form class="row g-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" placeholder="Buscar por asunto o mensaje...">
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
                <div class="col-md-2 text-end">
                    <button class="btn btn-success w-100"><i class="fas fa-search me-1"></i>Filtrar</button>
                </div>
            </form>
        </div>

        <!-- Tabla -->
        <div class="table-responsive">
            <table class="table align-middle text-center">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Asunto</th>
                        <th>Mensaje</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $t): ?>
                        <tr>
                            <td><?= $t['id'] ?></td>
                            <td><?= htmlspecialchars($t['asunto']) ?></td>
                            <td><?= htmlspecialchars($t['mensaje']) ?></td>
                            <td>
                                <span class="badge 
                                    <?= strtolower($t['estado']) === 'pendiente' ? 'badge-pendiente' : (strtolower($t['estado']) === 'en progreso' ? 'badge-en-progreso' : 'badge-resuelto') ?>">
                                    <?= $t['estado'] ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($t['fecha'])) ?></td>
                            <td><button class="btn-ver" data-bs-toggle="modal" data-bs-target="#verTicketModal"
                                    data-id="<?= $t['id'] ?>">Ver</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="verTicketModal" tabindex="-1" aria-labelledby="verTicketLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="verTicketLabel"><i class="fas fa-envelope-open-text me-2"></i>Ticket de Soporte</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <h6><strong>Asunto:</strong> <span id="ticketAsunto">---</span></h6>
                        <p class="mt-3" id="ticketMensaje">---</p>
                        <hr>
                        <h6><strong>Respuesta del equipo:</strong></h6>
                        <p id="ticketRespuesta" class="text-success">---</p>
                        <hr>
                        <form>
                            <label for="nuevoMensaje">Enviar un mensaje adicional:</label>
                            <textarea id="nuevoMensaje" class="form-control mt-2" rows="3" placeholder="Escribe un mensaje..."></textarea>
                            <button type="submit" class="btn btn-success mt-3">
                                <i class="fas fa-paper-plane me-1"></i>Enviar mensaje
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <footer>
            <small>© <?= date('Y') ?> Jaguata — Centro de Soporte Paseador</small>
        </footer>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const tickets = <?= json_encode($tickets) ?>;
        const modal = document.getElementById('verTicketModal');
        const asunto = document.getElementById('ticketAsunto');
        const mensaje = document.getElementById('ticketMensaje');
        const respuesta = document.getElementById('ticketRespuesta');

        modal.addEventListener('show.bs.modal', e => {
            const id = e.relatedTarget.getAttribute('data-id');
            const ticket = tickets.find(t => t.id == id);
            asunto.textContent = ticket.asunto;
            mensaje.textContent = ticket.mensaje;
            respuesta.textContent = ticket.respuesta ?? 'Aún no hay respuesta del equipo.';
        });
    </script>
</body>

</html>