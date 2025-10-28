<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ✅ Dependencias
require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/AuditoriaController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Controllers\AuditoriaController;

// 🔹 Inicialización
AppConfig::init();

// 🔹 Seguridad
if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'admin') {
    header('Location: /jaguata/public/login.php?error=unauthorized');
    exit;
}

// 🔹 Cargar registros
$auditoriaController = new AuditoriaController();
$registros = $auditoriaController->index();

// 🔹 Datos simulados
if (empty($registros)) {
    $registros = [
        [
            'id' => 1,
            'fecha' => '2025-10-27 09:32:10',
            'usuario' => 'admin@jaguata.com',
            'accion' => 'Inicio de sesión',
            'modulo' => 'Autenticación',
            'detalles' => 'Inicio de sesión exitoso desde IP 192.168.0.12'
        ],
        [
            'id' => 2,
            'fecha' => '2025-10-27 09:45:01',
            'usuario' => 'admin@jaguata.com',
            'accion' => 'Actualización de datos',
            'modulo' => 'Usuarios',
            'detalles' => 'Se actualizó el rol de usuario "paseador1" a "admin"'
        ],
        [
            'id' => 3,
            'fecha' => '2025-10-27 10:05:22',
            'usuario' => 'admin@jaguata.com',
            'accion' => 'Eliminación de registro',
            'modulo' => 'Mascotas',
            'detalles' => 'Se eliminó la mascota con ID #23 (Rex)'
        ],
        [
            'id' => 4,
            'fecha' => '2025-10-27 10:45:09',
            'usuario' => 'admin@jaguata.com',
            'accion' => 'Pago confirmado',
            'modulo' => 'Pagos',
            'detalles' => 'Se confirmó pago del paseo #110 por ₲95.000'
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoría del Sistema - Jaguata</title>
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
            font-family: "Poppins", sans-serif;
            background-color: var(--gris-fondo);
        }

        /* === Sidebar === */
        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            width: 250px;
            height: 100vh;
            position: fixed;
            color: #fff;
            top: 0;
            left: 0;
            padding-top: 1.5rem;
        }

        .sidebar .nav-link {
            color: #ddd;
            padding: 10px 16px;
            border-radius: 8px;
            margin: 4px 8px;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: var(--verde-claro);
            color: #fff;
            transform: translateX(4px);
        }

        /* === Main === */
        main {
            margin-left: 250px;
            padding: 2rem;
        }

        /* === Header === */
        .welcome-box {
            background: linear-gradient(90deg, var(--verde-claro), var(--verde-jaguata));
            color: #fff;
            padding: 1.5rem 2rem;
            border-radius: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        /* === Filtros === */
        .filtros {
            background: var(--blanco);
            padding: 1rem 1.5rem;
            border-radius: 14px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
        }

        .filtros input,
        .filtros select {
            border-radius: 10px;
            font-size: 0.95rem;
        }

        /* === Export Buttons === */
        .export-buttons {
            display: flex;
            justify-content: flex-end;
            gap: .5rem;
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
            background: var(--verde-jaguata);
        }

        /* === Tabla === */
        .table {
            background: var(--blanco);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
        }

        .table thead {
            background: var(--verde-jaguata);
            color: #fff;
        }

        .table-hover tbody tr:hover {
            background: #eef8f2;
        }

        .badge {
            font-size: 0.85rem;
            padding: 0.4em 0.7em;
            border-radius: 8px;
        }

        /* === Footer === */
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
            <hr class="text-light">
        </div>
        <ul class="nav flex-column gap-1 px-2">
            <li><a class="nav-link" href="Dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            <li><a class="nav-link" href="Usuarios.php"><i class="fas fa-users"></i> Usuarios</a></li>
            <li><a class="nav-link" href="Paseos.php"><i class="fas fa-dog"></i> Paseos</a></li>
            <li><a class="nav-link" href="Pagos.php"><i class="fas fa-wallet"></i> Pagos</a></li>
            <li><a class="nav-link" href="Reportes.php"><i class="fas fa-chart-pie"></i> Reportes</a></li>
            <li><a class="nav-link active" href="#"><i class="fas fa-shield-halved"></i> Auditoría</a></li>
            <li><a class="nav-link text-danger" href="/jaguata/public/logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
        </ul>
    </aside>

    <!-- Contenido -->
    <main>
        <div class="welcome-box">
            <div>
                <h1>Auditoría del Sistema</h1>
                <p>Registro detallado de acciones realizadas por usuarios 🕵️‍♂️</p>
            </div>
            <i class="fas fa-shield-halved fa-3x opacity-75"></i>
        </div>

        <!-- Filtros -->
        <div class="filtros">
            <form class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Buscar</label>
                    <input type="text" id="searchInput" class="form-control" placeholder="Usuario o acción...">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Módulo</label>
                    <select id="filterModulo" class="form-select">
                        <option value="">Todos</option>
                        <option value="Usuarios">Usuarios</option>
                        <option value="Pagos">Pagos</option>
                        <option value="Mascotas">Mascotas</option>
                        <option value="Autenticación">Autenticación</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Desde</label>
                    <input type="date" id="filterDesde" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Hasta</label>
                    <input type="date" id="filterHasta" class="form-control">
                </div>
            </form>
        </div>

        <!-- Botones exportación -->
        <div class="export-buttons">
            <button class="btn btn-pdf"><i class="fas fa-file-pdf"></i> PDF</button>
            <button class="btn btn-excel"><i class="fas fa-file-excel"></i> Excel</button>
            <button class="btn btn-csv"><i class="fas fa-file-csv"></i> CSV</button>
        </div>

        <!-- Tabla -->
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="tablaAuditoria">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Acción</th>
                        <th>Módulo</th>
                        <th>Detalles</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registros as $r): ?>
                        <?php
                        $accion = strtolower($r['accion']);
                        $color = str_contains($accion, 'elimin') ? 'bg-danger' : (str_contains($accion, 'actualiz') ? 'bg-warning text-dark' : (str_contains($accion, 'inicio') ? 'bg-success' : 'bg-info text-dark'));
                        ?>
                        <tr data-modulo="<?= strtolower($r['modulo']) ?>">
                            <td><strong>#<?= htmlspecialchars($r['id']) ?></strong></td>
                            <td><?= date('d/m/Y H:i:s', strtotime($r['fecha'])) ?></td>
                            <td><?= htmlspecialchars($r['usuario']) ?></td>
                            <td><span class="badge <?= $color ?>"><?= htmlspecialchars($r['accion']) ?></span></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($r['modulo']) ?></span></td>
                            <td><?= htmlspecialchars($r['detalles']) ?></td>
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
        const modulo = document.getElementById('filterModulo');
        const desde = document.getElementById('filterDesde');
        const hasta = document.getElementById('filterHasta');
        const rows = document.querySelectorAll('#tablaAuditoria tbody tr');

        function aplicarFiltros() {
            const texto = input.value.toLowerCase();
            const modVal = modulo.value.toLowerCase();
            const fDesde = desde.value ? new Date(desde.value) : null;
            const fHasta = hasta.value ? new Date(hasta.value) : null;

            rows.forEach(row => {
                const contenido = row.textContent.toLowerCase();
                const moduloRow = row.dataset.modulo;
                const fechaTexto = row.cells[1].textContent.split(' ')[0];
                const [d, m, y] = fechaTexto.split('/');
                const fechaRow = new Date(`${y}-${m}-${d}`);

                const coincideTexto = contenido.includes(texto);
                const coincideModulo = !modVal || moduloRow === modVal;
                const coincideFecha = (!fDesde || fechaRow >= fDesde) && (!fHasta || fechaRow <= fHasta);

                row.style.display = coincideTexto && coincideModulo && coincideFecha ? '' : 'none';
            });
        }

        [input, modulo, desde, hasta].forEach(el => el.addEventListener('input', aplicarFiltros));

        document.querySelectorAll('.export-buttons .btn').forEach(btn => {
            btn.addEventListener('click', () => alert(`Exportar a ${btn.textContent.trim()} aún no implementado 🚀`));
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>