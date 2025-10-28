<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/UsuarioController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Controllers\UsuarioController;

AppConfig::init();

// 🔹 Seguridad
if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'admin') {
    header('Location: /jaguata/public/login.php?error=unauthorized');
    exit;
}

// 🔹 Cargar usuarios (desde BD o simulados)
$usuarioController = new UsuarioController();
$usuarios = $usuarioController->index();
if (empty($usuarios)) {
    $usuarios = [
        ['usu_id' => 1, 'nombre' => 'Juan Pérez', 'email' => 'juan@correo.com', 'rol' => 'paseador', 'estado' => 'activo'],
        ['usu_id' => 2, 'nombre' => 'Ana Gómez', 'email' => 'ana@correo.com', 'rol' => 'dueno', 'estado' => 'inactivo'],
        ['usu_id' => 3, 'nombre' => 'Carlos Rojas', 'email' => 'carlos@correo.com', 'rol' => 'admin', 'estado' => 'activo'],
    ];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --verde-jaguata: #3c6255;
            --verde-claro: #20c997;
            --gris-fondo: #f4f6f9;
            --blanco: #fff;
        }

        body {
            background: var(--gris-fondo);
            font-family: "Poppins", sans-serif;
            color: #444;
        }

        /* === Sidebar === */
        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            color: var(--blanco);
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 1.5rem;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.2);
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

        /* === Encabezado === */
        .header-box {
            background: linear-gradient(90deg, var(--verde-claro), var(--verde-jaguata));
            color: var(--blanco);
            padding: 1.8rem 2.2rem;
            border-radius: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .header-box h1 {
            font-weight: 600;
            font-size: 1.8rem;
        }

        /* === Filtros === */
        .filtros {
            background: var(--blanco);
            padding: 1rem 1.5rem;
            border-radius: 14px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
        }

        .filtros select,
        .filtros input {
            border-radius: 10px;
            font-size: 0.95rem;
        }

        /* === Botones exportar === */
        .export-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .export-buttons .btn {
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            color: #fff;
            transition: 0.2s;
        }

        .btn-pdf {
            background: #dc3545;
        }

        .btn-excel {
            background: #198754;
        }

        .btn-csv {
            background: #20c997;
        }

        .btn-pdf:hover {
            background: #b02a37;
        }

        .btn-excel:hover {
            background: #157347;
        }

        .btn-csv:hover {
            background: #3c6255;
        }

        /* === Tabla === */
        .table {
            background: var(--blanco);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
        }

        .table thead {
            background: var(--verde-jaguata);
            color: var(--blanco);
        }

        .table tbody tr:hover {
            background: #f1f5f3;
        }

        .badge {
            font-size: 0.85rem;
            padding: 0.4em 0.7em;
            border-radius: 8px;
        }

        /* === Acciones === */
        .btn-accion {
            font-size: 0.85rem;
            margin: 0 2px;
            border-radius: 6px;
            padding: 6px 10px;
            transition: 0.2s;
            border: none;
        }

        .btn-editar {
            background-color: var(--verde-claro);
            color: #fff;
        }

        .btn-eliminar {
            background-color: #dc3545;
            color: #fff;
        }

        .btn-suspender {
            background-color: #ffc107;
            color: #000;
        }

        .btn-activar {
            background-color: #198754;
            color: #fff;
        }

        .btn-editar:hover {
            background-color: var(--verde-jaguata);
        }

        .btn-eliminar:hover {
            background-color: #b52b3a;
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
            <img src="<?= ASSETS_URL ?>/uploads/perfiles/logojag.png" alt="Logo" width="60">
            <h6 class="mt-2 fw-bold text-success">Jaguata Admin</h6>
            <hr class="text-light">
        </div>
        <ul class="nav flex-column">
            <li><a class="nav-link" href="Dashboard.php"><i class="fas fa-home"></i> Inicio</a></li>
            <li><a class="nav-link active" href="#"><i class="fas fa-users"></i> Usuarios</a></li>
            <li><a class="nav-link" href="Paseos.php"><i class="fas fa-dog"></i> Paseos</a></li>
            <li><a class="nav-link" href="../mensajeria/chat.php"><i class="fas fa-comments"></i> Mensajería</a></li>
            <li><a class="nav-link" href="Pagos.php"><i class="fas fa-wallet"></i> Pagos</a></li>
            <li><a class="nav-link" href="Servicios.php"><i class="fas fa-briefcase"></i> Servicios</a></li>
            <li><a class="nav-link" href="Notificaciones.php"><i class="fas fa-bell"></i> Notificaciones</a></li>
            <li><a class="nav-link" href="Soporte.php"><i class="fas fa-headset"></i> Soporte</a></li>
            <li><a class="nav-link" href="Configuracion.php"><i class="fas fa-cogs"></i> Configuración</a></li>
            <li><a class="nav-link text-danger" href="/jaguata/public/logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
        </ul>
    </aside>

    <!-- Contenido -->
    <main>
        <div class="header-box">
            <div>
                <h1>Gestión de Usuarios</h1>
                <p class="mb-0">Administrá los usuarios, roles y estados 👥</p>
            </div>
            <i class="fas fa-user-cog fa-3x opacity-75"></i>
        </div>

        <!-- Filtros -->
        <div class="filtros mb-3">
            <form class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Buscar</label>
                    <input type="text" id="searchInput" class="form-control" placeholder="Nombre o correo...">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Rol</label>
                    <select id="filterRol" class="form-select">
                        <option value="">Todos</option>
                        <option value="admin">Admin</option>
                        <option value="paseador">Paseador</option>
                        <option value="dueno">Dueño</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Estado</label>
                    <select id="filterEstado" class="form-select">
                        <option value="">Todos</option>
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>
            </form>
        </div>

        <!-- Botones exportar -->
        <div class="export-buttons">
            <button class="btn btn-pdf"><i class="fas fa-file-pdf"></i> PDF</button>
            <button class="btn btn-excel"><i class="fas fa-file-excel"></i> Excel</button>
            <button class="btn btn-csv"><i class="fas fa-file-csv"></i> CSV</button>
        </div>

        <!-- Tabla -->
        <div class="table-responsive">
            <table class="table align-middle text-center" id="tablaUsuarios">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                        <?php
                        $estado = strtolower($u['estado'] ?? 'activo');
                        $badge = $estado === 'activo' ? 'bg-success' : 'bg-secondary';
                        ?>
                        <tr data-rol="<?= strtolower($u['rol']) ?>" data-estado="<?= $estado ?>">
                            <td><strong>#<?= htmlspecialchars($u['usu_id']) ?></strong></td>
                            <td><?= htmlspecialchars($u['nombre']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><span class="badge bg-info text-dark"><?= ucfirst($u['rol']) ?></span></td>
                            <td><span class="badge <?= $badge ?>"><?= ucfirst($estado) ?></span></td>
                            <td>
                                <button class="btn-accion btn-editar"><i class="fas fa-edit"></i></button>
                                <button class="btn-accion btn-eliminar"><i class="fas fa-trash"></i></button>
                                <?php if ($estado === 'activo'): ?>
                                    <button class="btn-accion btn-suspender"><i class="fas fa-user-slash"></i></button>
                                <?php else: ?>
                                    <button class="btn-accion btn-activar"><i class="fas fa-user-check"></i></button>
                                <?php endif; ?>
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

    <script>
        const input = document.getElementById('searchInput');
        const filterRol = document.getElementById('filterRol');
        const filterEstado = document.getElementById('filterEstado');
        const rows = document.querySelectorAll('#tablaUsuarios tbody tr');

        function aplicarFiltros() {
            const texto = input.value.toLowerCase();
            const rol = filterRol.value.toLowerCase();
            const estado = filterEstado.value.toLowerCase();

            rows.forEach(row => {
                const coincideTexto = row.textContent.toLowerCase().includes(texto);
                const coincideRol = !rol || row.dataset.rol === rol;
                const coincideEstado = !estado || row.dataset.estado === estado;
                row.style.display = coincideTexto && coincideRol && coincideEstado ? '' : 'none';
            });
        }

        input.addEventListener('keyup', aplicarFiltros);
        filterRol.addEventListener('change', aplicarFiltros);
        filterEstado.addEventListener('change', aplicarFiltros);

        // Simulación exportar (por ahora alerta)
        document.querySelectorAll('.export-buttons .btn').forEach(btn => {
            btn.addEventListener('click', () => {
                alert(`Exportar a ${btn.textContent.trim()} aún no implementado 🚀`);
            });
        });
    </script>
</body>

</html>