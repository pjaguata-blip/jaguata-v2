<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ✅ Inclusión de dependencias
require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;

// 🔹 Inicialización
AppConfig::init();

// 🔹 Verificación de sesión y rol
if (!Session::isLoggedIn()) {
    header('Location: /jaguata/public/login.php');
    exit;
}
$rol = Session::getUsuarioRol();
if ($rol !== 'admin') {
    header('Location: /jaguata/public/login.php?error=unauthorized');
    exit;
}

// 🔹 Datos simulados (puedes reemplazar por datos reales desde BD)
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
        body {
            background-color: #f5f7fa;
            font-family: "Poppins", sans-serif;
        }

        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            color: #fff;
            width: 240px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 1.5rem;
        }

        .sidebar .nav-link {
            color: #ddd;
            padding: 10px 16px;
            border-radius: 8px;
            margin: 4px 8px;
            transition: background 0.2s;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: #3c6255;
            color: #fff;
        }

        main {
            margin-left: 240px;
            padding: 2rem;
        }

        .welcome-box {
            background: linear-gradient(90deg, #20c997, #3c6255);
            color: #fff;
            padding: 1.5rem 2rem;
            border-radius: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.07);
        }

        .table thead {
            background: #3c6255;
            color: #fff;
        }

        .table-hover tbody tr:hover {
            background-color: #eef8f2;
        }

        .btn-enviar {
            background: #3c6255;
            color: #fff;
            border: none;
            padding: .6rem 1.3rem;
            border-radius: 8px;
        }

        .btn-enviar:hover {
            background: #20c997;
        }

        footer {
            text-align: center;
            color: #777;
            font-size: 0.85rem;
            margin-top: 2rem;
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="text-center mb-4">
            <img src="<?= ASSETS_URL; ?>/uploads/perfiles/logojag.png" alt="Logo" width="50">
            <hr class="text-light">
        </div>
        <ul class="nav flex-column gap-1 px-2">
            <li><a class="nav-link" href="Dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            <li><a class="nav-link" href="Usuarios.php"><i class="fas fa-users"></i> Usuarios</a></li>
            <li><a class="nav-link" href="Paseos.php"><i class="fas fa-dog"></i> Paseos</a></li>
            <li><a class="nav-link" href="Pagos.php"><i class="fas fa-wallet"></i> Pagos</a></li>
            <li><a class="nav-link" href="Servicios.php"><i class="fas fa-briefcase"></i> Servicios</a></li>
            <li><a class="nav-link active" href="#"><i class="fas fa-bell"></i> Notificaciones</a></li>
            <li><a class="nav-link" href="RolesPermisos.php"><i class="fas fa-user-lock"></i> Roles y Permisos</a></li>
            <li><a class="nav-link" href="Reportes.php"><i class="fas fa-chart-pie"></i> Reportes</a></li>
            <li><a class="nav-link" href="Configuracion.php"><i class="fas fa-cogs"></i> Configuración</a></li>
            <li><a class="nav-link" href="Auditoria.php"><i class="fas fa-shield-halved"></i> Auditoría</a></li>
            <li><a class="nav-link text-danger" href="/jaguata/public/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a></li>
        </ul>
    </aside>

    <!-- Contenido -->
    <main>
        <div class="welcome-box">
            <div>
                <h1>Notificaciones del sistema</h1>
                <p>Envía avisos a usuarios, dueños o paseadores 🔔</p>
            </div>
            <i class="fas fa-bell fa-3x opacity-75"></i>
        </div>

        <!-- Formulario de nueva notificación -->
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
                <button type="submit" class="btn-enviar"><i class="fas fa-paper-plane me-2"></i>Enviar notificación</button>
            </form>
        </div>

        <!-- Historial de notificaciones -->
        <div class="card p-4">
            <h5 class="text-success fw-bold mb-3"><i class="fas fa-history me-2"></i>Historial de notificaciones</h5>
            <table class="table table-hover align-middle">
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
                        <tr>
                            <td><strong>#<?= htmlspecialchars($n['id']) ?></strong></td>
                            <td><?= htmlspecialchars($n['titulo']) ?></td>
                            <td><?= htmlspecialchars($n['destinatario']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($n['fecha'])) ?></td>
                            <td>
                                <span class="badge <?= $n['estado'] === 'enviado' ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= ucfirst($n['estado']) ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></button>
                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <footer>
            <small>© <?= date('Y') ?> Jaguata — Panel de Administración</small>
        </footer>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>