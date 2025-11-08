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
        [
            'usu_id' => 1,
            'nombre' => 'Juan Pérez',
            'email' => 'juan@correo.com',
            'rol' => 'paseador',
            'estado' => 'pendiente'
        ]
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

    <?php include __DIR__ . '/../../src/Templates/SidebarAdmin.php'; ?>

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
                        <option value="pendiente">Pendiente</option>
                        <option value="aprobado">Aprobado</option>
                        <option value="rechazado">Rechazado</option>
                        <option value="cancelado">Cancelado</option>
                    </select>
                </div>
            </form>
        </div>

        <!-- Botones exportar -->
        <div class="export-buttons">
            <a class="btn btn-excel" href="/jaguata/public/api/usuarios/exportUsuarios.php">
                <i class="fas fa-file-excel"></i> Excel
            </a>
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
                        $estado = strtolower($u['estado'] ?? 'pendiente');
                        $badge = match ($estado) {
                            'aprobado' => 'bg-success',
                            'pendiente' => 'bg-warning text-dark',
                            'rechazado' => 'bg-danger',
                            'cancelado' => 'bg-secondary',
                            default => 'bg-light text-dark',
                        };
                        ?>
                        <tr data-rol="<?= strtolower($u['rol']) ?>" data-estado="<?= $estado ?>">
                            <td><strong>#<?= htmlspecialchars($u['usu_id']) ?></strong></td>
                            <td><?= htmlspecialchars($u['nombre']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><span class="badge bg-info text-dark"><?= ucfirst($u['rol']) ?></span></td>
                            <td><span class="badge <?= $badge ?>"><?= ucfirst($estado) ?></span></td>
                            <td>
                                <button class="btn-accion btn-editar" data-id="<?= htmlspecialchars($u['usu_id']) ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-accion btn-eliminar" data-id="<?= htmlspecialchars($u['usu_id']) ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php if ($estado === 'aprobado'): ?>
                                    <button class="btn-accion btn-suspender" data-id="<?= htmlspecialchars($u['usu_id']) ?>">
                                        <i class="fas fa-user-slash"></i>
                                    </button>
                                <?php else: ?>
                                    <button class="btn-accion btn-activar" data-id="<?= htmlspecialchars($u['usu_id']) ?>">
                                        <i class="fas fa-user-check"></i>
                                    </button>
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

        // === Acciones de los botones ===
        document.addEventListener('DOMContentLoaded', () => {
            const handleAction = async (id, accion, confirmMsg) => {
                if (confirmMsg && !confirm(confirmMsg)) return;

                const formData = new FormData();
                formData.append('id', id);
                formData.append('accion', accion);

                const res = await fetch('/jaguata/public/api/usuarios/accionesUsuarios.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await res.json();
                alert(data.mensaje);
                if (data.ok) location.reload();
            };

            document.querySelectorAll('.btn-editar').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.dataset.id;
                    window.location.href = `/jaguata/public/admin/editar_usuario.php?id=${id}`;
                });
            });

            document.querySelectorAll('.btn-eliminar').forEach(btn => {
                btn.addEventListener('click', () => {
                    handleAction(btn.dataset.id, 'eliminar', '¿Seguro que deseas eliminar este usuario?');
                });
            });

            document.querySelectorAll('.btn-suspender').forEach(btn => {
                btn.addEventListener('click', () => {
                    handleAction(btn.dataset.id, 'suspender', '¿Suspender este usuario?');
                });
            });

            document.querySelectorAll('.btn-activar').forEach(btn => {
                btn.addEventListener('click', () => {
                    handleAction(btn.dataset.id, 'activar', '¿Activar este usuario?');
                });
            });
        });
    </script>
</body>

</html>