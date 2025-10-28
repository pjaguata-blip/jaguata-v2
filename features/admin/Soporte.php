<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;

// Inicialización
AppConfig::init();

if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'admin') {
    header('Location: /jaguata/public/login.php');
    exit;
}

// Datos simulados
$tickets = [
    [
        'id' => 1,
        'usuario' => 'Ana Gómez',
        'email' => 'ana@correo.com',
        'asunto' => 'Problema con el pago del paseo',
        'estado' => 'Pendiente',
        'fecha' => '2025-10-27 10:15:00'
    ],
    [
        'id' => 2,
        'usuario' => 'Carlos López',
        'email' => 'carlos@correo.com',
        'asunto' => 'Mi paseador no se presentó',
        'estado' => 'En progreso',
        'fecha' => '2025-10-26 18:45:00'
    ],
    [
        'id' => 3,
        'usuario' => 'María Rivas',
        'email' => 'maria@correo.com',
        'asunto' => 'Duda sobre tarifas',
        'estado' => 'Resuelto',
        'fecha' => '2025-10-25 09:30:00'
    ]
];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soporte - Jaguata Admin</title>
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

        .table {
            background: var(--blanco);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        }

        .table th {
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

        .modal-content {
            border-radius: 12px;
            overflow: hidden;
        }

        .modal-header {
            background: var(--verde-jaguata);
            color: var(--blanco);
        }

        .modal-body textarea {
            resize: none;
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="text-center mb-4">
            <img src="<?= ASSETS_URL; ?>/uploads/perfiles/logojag.png" alt="Logo" width="60">
            <h6 class="mt-2 fw-bold text-success">Jaguata Admin</h6>
            <hr class="text-light">
        </div>
        <ul class="nav flex-column">
            <li><a class="nav-link" href="Dashboard.php"><i class="fas fa-home"></i>Inicio</a></li>
            <li><a class="nav-link" href="Usuarios.php"><i class="fas fa-users"></i>Usuarios</a></li>
            <li><a class="nav-link" href="Paseos.php"><i class="fas fa-dog"></i>Paseos</a></li>
            <li><a class="nav-link" href="../mensajeria/chat.php"><i class="fas fa-comments"></i>Mensajería</a></li>
            <li><a class="nav-link" href="Pagos.php"><i class="fas fa-wallet"></i>Pagos</a></li>
            <li><a class="nav-link" href="Servicios.php"><i class="fas fa-briefcase"></i>Servicios</a></li>
            <li><a class="nav-link" href="Notificaciones.php"><i class="fas fa-bell"></i>Notificaciones</a></li>
            <li><a class="nav-link active" href="#"><i class="fas fa-headset"></i>Soporte</a></li>
            <li><a class="nav-link" href="RolesPermisos.php"><i class="fas fa-user-lock"></i>Roles</a></li>
            <li><a class="nav-link" href="Configuracion.php"><i class="fas fa-cogs"></i>Configuración</a></li>
            <li><a class="nav-link" href="Auditoria.php"><i class="fas fa-shield-halved"></i>Auditoría</a></li>
            <li><a class="nav-link text-danger" href="/jaguata/public/logout.php"><i class="fas fa-sign-out-alt"></i>Salir</a></li>
        </ul>
    </aside>

    <!-- Contenido -->
    <main>
        <div class="header-box">
            <div>
                <h1 class="fw-bold">Centro de Soporte</h1>
                <p>Gestiona tickets y consultas de los usuarios 📬</p>
            </div>
            <i class="fas fa-headset fa-3x opacity-75"></i>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Correo</th>
                        <th>Asunto</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $t): ?>
                        <tr>
                            <td><?= $t['id'] ?></td>
                            <td><?= htmlspecialchars($t['usuario']) ?></td>
                            <td><?= htmlspecialchars($t['email']) ?></td>
                            <td><?= htmlspecialchars($t['asunto']) ?></td>
                            <td>
                                <span class="badge 
                                <?= strtolower($t['estado']) === 'pendiente' ? 'badge-pendiente' : (strtolower($t['estado']) === 'en progreso' ? 'badge-en-progreso' : 'badge-resuelto') ?>">
                                    <?= $t['estado'] ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($t['fecha'])) ?></td>
                            <td><button class="btn-ver" data-bs-toggle="modal" data-bs-target="#verTicketModal" data-id="<?= $t['id'] ?>">Ver</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Modal para ver ticket -->
        <div class="modal fade" id="verTicketModal" tabindex="-1" aria-labelledby="verTicketLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="verTicketLabel"><i class="fas fa-envelope-open-text me-2"></i>Ticket de soporte</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <h6><strong>Usuario:</strong> <span id="ticketUsuario">Ana Gómez</span></h6>
                        <h6><strong>Correo:</strong> <span id="ticketCorreo">ana@correo.com</span></h6>
                        <hr>
                        <p id="ticketMensaje">Hola, tengo un problema con el pago de mi paseo.</p>

                        <form class="mt-3">
                            <label for="respuesta">Responder:</label>
                            <textarea id="respuesta" class="form-control mt-2" rows="3" placeholder="Escribe una respuesta..."></textarea>
                            <button type="submit" class="btn btn-success mt-3"><i class="fas fa-paper-plane me-1"></i>Enviar respuesta</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <footer>
            <small>© <?= date('Y') ?> Jaguata — Centro de Soporte</small>
        </footer>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.btn-ver').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-id');
                // Aquí podrías cargar dinámicamente el ticket con fetch() a una API
                console.log("Ver ticket", id);
            });
        });
    </script>

</body>

</html>