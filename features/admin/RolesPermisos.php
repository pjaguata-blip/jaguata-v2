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

$roles = [
    ['id' => 1, 'nombre' => 'admin', 'descripcion' => 'Acceso total al sistema', 'estado' => 'activo'],
    ['id' => 2, 'nombre' => 'paseador', 'descripcion' => 'Puede gestionar paseos y ganancias', 'estado' => 'activo'],
    ['id' => 3, 'nombre' => 'dueno', 'descripcion' => 'Puede solicitar paseos y ver historial', 'estado' => 'activo'],
    ['id' => 4, 'nombre' => 'soporte', 'descripcion' => 'Puede atender reclamos y reportes', 'estado' => 'inactivo'],
];

$permisos = [
    'Usuarios' => ['Ver', 'Editar', 'Eliminar'],
    'Paseos' => ['Ver', 'Editar', 'Cancelar'],
    'Pagos' => ['Ver', 'Procesar', 'Reembolsar'],
    'Reportes' => ['Ver'],
    'Configuración' => ['Ver', 'Editar'],
    'Auditoría' => ['Ver'],
];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roles y Permisos - Jaguata</title>
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
            color: #333;
        }

        /* Sidebar */
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

        /* Main Layout */
        main {
            margin-left: 250px;
            padding: 2rem;
        }

        /* Header */
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

        /* Card */
        .card {
            background: var(--blanco);
            border-radius: 14px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
            animation: fadeIn 0.5s ease;
        }

        .card h5 {
            color: var(--verde-jaguata);
        }

        /* Table */
        .table thead {
            background: var(--verde-jaguata);
            color: #fff;
        }

        .table-hover tbody tr:hover {
            background-color: #eef8f2;
            transition: 0.25s;
        }

        .badge {
            font-size: 0.85rem;
            padding: 0.4em 0.7em;
            border-radius: 8px;
        }

        /* Buttons */
        .btn-add {
            background: var(--verde-jaguata);
            color: #fff;
            border: none;
            padding: .55rem 1.1rem;
            border-radius: 10px;
            font-weight: 500;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.2);
            transition: all 0.2s ease-in-out;
        }

        .btn-add:hover {
            background: var(--verde-claro);
            transform: translateY(-2px);
            box-shadow: 0 5px 12px rgba(0, 0, 0, 0.25);
        }

        /* Filtros */
        .filtros {
            background: var(--blanco);
            border-radius: 14px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }

        .filtros label {
            font-weight: 600;
            color: #444;
        }

        .filtros input,
        .filtros select {
            border-radius: 10px;
            font-size: 0.95rem;
        }

        /* Footer */
        footer {
            text-align: center;
            color: #777;
            font-size: 0.85rem;
            margin-top: 2rem;
        }

        /* Animations */
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

    <?php include __DIR__ . '/../../src/Templates/SidebarAdmin.php'; ?>

    <!-- Contenido -->
    <main>
        <div class="welcome-box mb-4">
            <div>
                <h1 class="fw-bold">Roles y Permisos</h1>
                <p>Gestiona los niveles de acceso y acciones permitidas 👥</p>
            </div>
            <i class="fas fa-user-lock fa-3x opacity-75"></i>
        </div>

        <!-- Filtros -->
        <div class="filtros">
            <form class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label">Buscar rol</label>
                    <input type="text" id="searchInput" class="form-control" placeholder="Nombre o descripción...">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Estado</label>
                    <select id="filterEstado" class="form-select">
                        <option value="">Todos</option>
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex justify-content-end">
                    <button type="button" class="btn-add w-100"><i class="fas fa-plus me-2"></i>Nuevo Rol</button>
                </div>
            </form>
        </div>

        <!-- Roles -->
        <div class="card p-4 mb-5">
            <h5 class="fw-bold mb-3"><i class="fas fa-users-cog me-2"></i>Roles registrados</h5>
            <table class="table table-hover align-middle" id="tablaRoles">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $r): ?>
                        <tr data-estado="<?= strtolower($r['estado']) ?>">
                            <td><strong>#<?= htmlspecialchars($r['id']) ?></strong></td>
                            <td><?= htmlspecialchars($r['nombre']) ?></td>
                            <td><?= htmlspecialchars($r['descripcion']) ?></td>
                            <td><span class="badge <?= $r['estado'] === 'activo' ? 'bg-success' : 'bg-secondary' ?>"><?= ucfirst($r['estado']) ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary"><i class="fas fa-pen"></i></button>
                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Permisos -->
        <div class="card p-4">
            <h5 class="fw-bold mb-3"><i class="fas fa-key me-2"></i>Permisos por módulo</h5>
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th>Módulo</th>
                        <th>Acciones permitidas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($permisos as $modulo => $acciones): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($modulo) ?></strong></td>
                            <td>
                                <?php foreach ($acciones as $a): ?>
                                    <span class="badge bg-success text-white me-1"><?= htmlspecialchars($a) ?></span>
                                <?php endforeach; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <footer><small>© <?= date('Y') ?> Jaguata — Panel de Administración</small></footer>
    </main>

    <script>
        const searchInput = document.getElementById('searchInput');
        const filterEstado = document.getElementById('filterEstado');
        const rows = document.querySelectorAll('#tablaRoles tbody tr');

        function aplicarFiltros() {
            const texto = searchInput.value.toLowerCase();
            const estadoVal = filterEstado.value.toLowerCase();

            rows.forEach(row => {
                const rowEstado = row.dataset.estado;
                const rowTexto = row.textContent.toLowerCase();

                const coincideTexto = rowTexto.includes(texto);
                const coincideEstado = !estadoVal || rowEstado === estadoVal;

                row.style.display = coincideTexto && coincideEstado ? '' : 'none';
            });
        }

        [searchInput, filterEstado].forEach(el => el.addEventListener('input', aplicarFiltros));
    </script>
</body>

</html>