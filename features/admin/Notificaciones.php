<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;

AppConfig::init();

if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'admin') {
    header('Location: /jaguata/public/login.php?error=unauthorized');
    exit;
}

// 🔹 Datos simulados
$notificaciones = [
    ['id' => 1, 'titulo' => 'Nueva actualización disponible', 'mensaje' => 'Se agregó un nuevo módulo de Auditoría en el sistema.', 'destinatario' => 'Todos', 'fecha' => '2025-10-25 09:30', 'estado' => 'enviado'],
    ['id' => 2, 'titulo' => 'Recordatorio de pago', 'mensaje' => 'Recuerda liquidar los paseos completados antes de fin de mes.', 'destinatario' => 'Paseadores', 'fecha' => '2025-10-26 14:15', 'estado' => 'enviado'],
    ['id' => 3, 'titulo' => 'Promoción de paseo', 'mensaje' => 'Descuento del 10% en paseos de fin de semana.', 'destinatario' => 'Dueños', 'fecha' => '2025-10-27 10:00', 'estado' => 'pendiente']
];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificaciones - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --verde-jaguata: #3c6255;
            --verde-claro: #20c997;
            --gris-fondo: #f5f7fa;
            --blanco: #fff;
        }

        body {
            background-color: var(--gris-fondo);
            font-family: "Poppins", sans-serif;
            color: #444;
        }

        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            width: 250px;
            height: 100vh;
            position: fixed;
            color: #fff;
            top: 0;
            left: 0;
            padding-top: 1.5rem;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.25);
        }

        .sidebar .nav-link {
            color: #ccc;
            padding: 10px 16px;
            border-radius: 8px;
            margin: 4px 10px;
            display: flex;
            align-items: center;
            gap: 0.7rem;
            transition: all 0.2s ease;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: var(--verde-claro);
            color: #fff;
            transform: translateX(4px);
        }

        main {
            margin-left: 250px;
            padding: 2rem;
        }

        .welcome-box {
            background: linear-gradient(90deg, var(--verde-claro), var(--verde-jaguata));
            color: #fff;
            padding: 1.8rem 2rem;
            border-radius: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            animation: fadeIn 0.7s ease;
        }

        .card {
            background: var(--blanco);
            border-radius: 14px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
            animation: fadeIn 0.6s ease;
        }

        .table thead {
            background: var(--verde-jaguata);
            color: #fff;
        }

        .table-hover tbody tr:hover {
            background-color: #eef8f2;
            transition: 0.25s;
        }

        .btn-enviar {
            background: var(--verde-jaguata);
            color: #fff;
            border: none;
            padding: .6rem 1.3rem;
            border-radius: 8px;
            transition: 0.2s ease;
        }

        .btn-enviar:hover {
            background: var(--verde-claro);
            transform: translateY(-2px);
        }

        footer {
            text-align: center;
            color: #777;
            font-size: 0.85rem;
            margin-top: 2rem;
        }

        .filtros {
            background: var(--blanco);
            border-radius: 14px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }

        .badge-pendiente {
            background: #ffc107;
            color: #000;
        }

        .badge-enviado {
            background: #20c997;
            color: #fff;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <?php include __DIR__ . '/../../src/Templates/SidebarAdmin.php'; ?>

    <!-- Contenido -->
    <main>
        <div class="welcome-box mb-4">
            <div>
                <h1 class="fw-bold">Centro de Notificaciones</h1>
                <p>Envía avisos, recordatorios y promociones a los usuarios 🔔</p>
            </div>
            <i class="fas fa-bell fa-3x opacity-75"></i>
        </div>

        <!-- Filtros -->
        <div class="filtros">
            <form class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Buscar</label>
                    <input type="text" id="searchInput" class="form-control" placeholder="Título o destinatario...">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Estado</label>
                    <select id="filterEstado" class="form-select">
                        <option value="">Todos</option>
                        <option value="enviado">Enviado</option>
                        <option value="pendiente">Pendiente</option>
                    </select>
                </div>
            </form>
        </div>

        <!-- Nueva notificación -->
        <div class="card p-4 mb-4">
            <h5 class="text-success fw-bold mb-3"><i class="fas fa-paper-plane me-2"></i>Enviar nueva notificación</h5>
            <form method="post" action="#">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Título</label>
                        <input type="text" name="titulo" class="form-control" placeholder="Ej. Mantenimiento programado" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Destinatario</label>
                        <select name="destinatario" class="form-select">
                            <option value="Todos">Todos</option>
                            <option value="Dueños">Dueños</option>
                            <option value="Paseadores">Paseadores</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Mensaje</label>
                    <textarea name="mensaje" class="form-control" rows="4" placeholder="Escribe el contenido del aviso..." required></textarea>
                </div>
                <button type="submit" class="btn-enviar"><i class="fas fa-paper-plane me-2"></i>Enviar</button>
            </form>
        </div>

        <!-- Historial -->
        <div class="card p-4">
            <h5 class="text-success fw-bold mb-3"><i class="fas fa-history me-2"></i>Historial de notificaciones</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="tablaNotificaciones">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Destinatario</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notificaciones as $n): ?>
                            <?php $estado = strtolower($n['estado']); ?>
                            <tr data-estado="<?= $estado ?>">
                                <td>#<?= htmlspecialchars($n['id']) ?></td>
                                <td><?= htmlspecialchars($n['titulo']) ?></td>
                                <td><?= htmlspecialchars($n['destinatario']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($n['fecha'])) ?></td>
                                <td><span class="badge <?= $estado === 'enviado' ? 'badge-enviado' : 'badge-pendiente' ?>"><?= ucfirst($n['estado']) ?></span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#verNotiModal" data-id="<?= $n['id'] ?>"><i class="fas fa-eye"></i></button>
                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="verNotiModal" tabindex="-1" aria-labelledby="verNotiLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title"><i class="fas fa-bell me-2"></i>Detalle de Notificación</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <h6><strong>Título:</strong> <span id="notiTitulo">Actualización del sistema</span></h6>
                        <h6><strong>Destinatario:</strong> <span id="notiDestinatario">Todos</span></h6>
                        <hr>
                        <p id="notiMensaje">Se agregó un nuevo módulo de auditoría en Jaguata.</p>
                    </div>
                </div>
            </div>
        </div>

        <footer><small>© <?= date('Y') ?> Jaguata — Panel de Administración</small></footer>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // === Filtros dinámicos ===
        const searchInput = document.getElementById('searchInput');
        const filterEstado = document.getElementById('filterEstado');
        const rows = document.querySelectorAll('#tablaNotificaciones tbody tr');

        function aplicarFiltros() {
            const texto = searchInput.value.toLowerCase();
            const estadoVal = filterEstado.value.toLowerCase();
            rows.forEach(row => {
                const rowEstado = row.dataset.estado;
                const rowTexto = row.textContent.toLowerCase();
                const visible = rowTexto.includes(texto) && (!estadoVal || rowEstado === estadoVal);
                row.style.display = visible ? '' : 'none';
            });
        }
        [searchInput, filterEstado].forEach(el => el.addEventListener('input', aplicarFiltros));

        // === Modal de vista ===
        document.querySelectorAll('[data-bs-target="#verNotiModal"]').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;
                const row = btn.closest('tr');
                document.getElementById('notiTitulo').textContent = row.children[1].textContent;
                document.getElementById('notiDestinatario').textContent = row.children[2].textContent;
                document.getElementById('notiMensaje').textContent = "<?= addslashes($notificaciones[0]['mensaje']) ?>";
            });
        });
    </script>
</body>

</html>