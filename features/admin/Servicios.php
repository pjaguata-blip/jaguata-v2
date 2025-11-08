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
            top: 0;
            left: 0;
            color: var(--blanco);
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
            gap: .7rem;
            transition: all .2s ease;
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

        .filtros {
            background: var(--blanco);
            border-radius: 14px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            padding: 1rem 1.5rem;
            margin: 1.5rem 0;
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
            transition: .25s;
        }

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
        }

        footer {
            text-align: center;
            color: #777;
            font-size: 0.85rem;
            margin-top: 2rem;
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
    <?php include __DIR__ . '/../../src/Templates/SidebarAdmin.php'; ?>

    <!-- Contenido -->
    <main>
        <div class="welcome-box mb-4">
            <div>
                <h1 class="fw-bold">Gestión de Servicios</h1>
                <p>Administra los servicios y tarifas disponibles 💼</p>
            </div>
            <i class="fas fa-briefcase fa-3x opacity-75"></i>
        </div>

        <!-- Filtros -->
        <div class="filtros">
            <form class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Buscar servicio</label>
                    <input type="text" id="searchInput" class="form-control" placeholder="Nombre o duración...">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Estado</label>
                    <select id="filterEstado" class="form-select">
                        <option value="">Todos</option>
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>
                <div class="col-md-2 text-end">
                    <button type="button" class="btn-add w-100" data-bs-toggle="modal" data-bs-target="#nuevoServicioModal"><i class="fas fa-plus me-2"></i>Nuevo</button>
                </div>
            </form>
        </div>

        <!-- Lista -->
        <div class="card p-4 mb-4">
            <h5 class="text-success fw-bold mb-3"><i class="fas fa-list me-2"></i>Servicios disponibles</h5>
            <table class="table table-hover align-middle" id="tablaServicios">
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
                        <?php $estado = strtolower($s['estado']); ?>
                        <tr data-estado="<?= $estado ?>">
                            <td><strong>#<?= htmlspecialchars($s['id']) ?></strong></td>
                            <td><?= htmlspecialchars($s['nombre']) ?></td>
                            <td><?= htmlspecialchars($s['duracion']) ?></td>
                            <td><?= number_format($s['precio'], 0, ',', '.') ?></td>
                            <td><span class="badge <?= $estado === 'activo' ? 'bg-success' : 'bg-secondary' ?>"><?= ucfirst($s['estado']) ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary"><i class="fas fa-pen"></i></button>
                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Resumen -->
        <div class="card p-4 text-center">
            <h5 class="text-success fw-bold mb-3"><i class="fas fa-chart-bar me-2"></i>Resumen general</h5>
            <div class="row">
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
                    <p class="text-muted">Suma total de precios (₲)</p>
                </div>
            </div>
        </div>

        <!-- Modal nuevo -->
        <div class="modal fade" id="nuevoServicioModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Nuevo servicio</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form>
                            <div class="mb-3"><label class="form-label fw-semibold">Nombre</label><input type="text" class="form-control" required></div>
                            <div class="mb-3"><label class="form-label fw-semibold">Duración</label><input type="text" class="form-control" placeholder="Ej. 1 hora"></div>
                            <div class="mb-3"><label class="form-label fw-semibold">Precio (₲)</label><input type="number" class="form-control" required></div>
                            <div class="mb-3"><label class="form-label fw-semibold">Estado</label>
                                <select class="form-select">
                                    <option>Activo</option>
                                    <option>Inactivo</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success w-100"><i class="fas fa-save me-2"></i>Guardar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <footer><small>© <?= date('Y') ?> Jaguata — Panel de Administración</small></footer>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const searchInput = document.getElementById('searchInput');
        const filterEstado = document.getElementById('filterEstado');
        const rows = document.querySelectorAll('#tablaServicios tbody tr');

        function aplicarFiltros() {
            const texto = searchInput.value.toLowerCase();
            const estadoVal = filterEstado.value.toLowerCase();
            rows.forEach(r => {
                const t = r.textContent.toLowerCase();
                const e = r.dataset.estado;
                const visible = t.includes(texto) && (!estadoVal || e === estadoVal);
                r.style.display = visible ? '' : 'none';
            });
        }
        [searchInput, filterEstado].forEach(el => el.addEventListener('input', aplicarFiltros));
    </script>
</body>

</html>