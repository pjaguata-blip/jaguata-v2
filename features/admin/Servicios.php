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

// 🔹 Datos simulados (puedes reemplazar por datos reales desde la BD)
$servicios = [
    ['id' => 1, 'nombre' => 'Paseo corto', 'duracion' => '30 min', 'precio' => 20000, 'estado' => 'activo'],
    ['id' => 2, 'nombre' => 'Paseo largo', 'duracion' => '1 hora', 'precio' => 35000, 'estado' => 'activo'],
    ['id' => 3, 'nombre' => 'Baño básico', 'duracion' => '45 min', 'precio' => 50000, 'estado' => 'inactivo'],
    ['id' => 4, 'nombre' => 'Guardería diaria', 'duracion' => '8 horas', 'precio' => 120000, 'estado' => 'activo'],
];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servicios - Jaguata</title>
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

        .btn-add {
            background: #3c6255;
            color: #fff;
            border: none;
            padding: .5rem 1rem;
            border-radius: 8px;
        }

        .btn-add:hover {
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
            <li><a class="nav-link active" href="#"><i class="fas fa-briefcase"></i> Servicios</a></li>
            <li><a class="nav-link" href="Notificaciones.php"><i class="fas fa-bell"></i> Notificaciones</a></li>
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
                <h1>Servicios disponibles</h1>
                <p>Gestiona los tipos de servicios que ofrece Jaguata 💼</p>
            </div>
            <i class="fas fa-briefcase fa-3x opacity-75"></i>
        </div>

        <div class="card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="text-success fw-bold"><i class="fas fa-list me-2"></i>Lista de servicios</h5>
                <button class="btn-add"><i class="fas fa-plus me-2"></i>Nuevo servicio</button>
            </div>

            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Duración</th>
                        <th>Precio (₲)</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($servicios as $s): ?>
                        <tr>
                            <td><strong>#<?= htmlspecialchars($s['id']) ?></strong></td>
                            <td><?= htmlspecialchars($s['nombre']) ?></td>
                            <td><?= htmlspecialchars($s['duracion']) ?></td>
                            <td><?= number_format($s['precio'], 0, ',', '.') ?></td>
                            <td>
                                <span class="badge <?= $s['estado'] === 'activo' ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= ucfirst($s['estado']) ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary"><i class="fas fa-pen"></i></button>
                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card p-4">
            <h5 class="text-success fw-bold mb-3"><i class="fas fa-chart-bar me-2"></i>Resumen de servicios</h5>
            <div class="row text-center">
                <div class="col-md-4">
                    <h3 class="text-primary"><?= count(array_filter($servicios, fn($s) => $s['estado'] === 'activo')) ?></h3>
                    <p class="text-muted">Activos</p>
                </div>
                <div class="col-md-4">
                    <h3 class="text-secondary"><?= count(array_filter($servicios, fn($s) => $s['estado'] === 'inactivo')) ?></h3>
                    <p class="text-muted">Inactivos</p>
                </div>
                <div class="col-md-4">
                    <h3 class="text-success"><?= number_format(array_sum(array_column($servicios, 'precio')), 0, ',', '.') ?></h3>
                    <p class="text-muted">Precio total acumulado (₲)</p>
                </div>
            </div>
        </div>

        <footer>
            <small>© <?= date('Y') ?> Jaguata — Panel de Administración</small>
        </footer>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>