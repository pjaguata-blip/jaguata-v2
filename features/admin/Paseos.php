<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/PaseoController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Controllers\PaseoController;

AppConfig::init();

if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'admin') {
    header('Location: /jaguata/public/login.php?error=unauthorized');
    exit;
}

$paseoController = new PaseoController();
$paseos = $paseoController->index();

if (empty($paseos) || isset($paseos['error'])) {
    $paseos = [
        [
            'paseo_id' => 101,
            'nombre_paseador' => 'María López',
            'nombre_dueno' => 'Lucas Díaz',
            'inicio' => '2025-10-27 09:00:00',
            'duracion' => 60,
            'estado' => 'Pendiente'
        ],
        [
            'paseo_id' => 102,
            'nombre_paseador' => 'Pedro Duarte',
            'nombre_dueno' => 'Verónica Ruiz',
            'inicio' => '2025-10-26 17:30:00',
            'duracion' => 30,
            'estado' => 'Finalizado'
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paseos - Jaguata</title>
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
            color: #333;
        }

        /* SIDEBAR */
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
            color: #ddd;
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

        /* MAIN */
        main {
            margin-left: 250px;
            padding: 2rem;
        }

        /* HEADER */
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

        /* FILTROS */
        .filtros {
            background: var(--blanco);
            border-radius: 14px;
            padding: 1.2rem 1.5rem;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            margin-top: 1.5rem;
        }

        .filtros .form-label {
            font-weight: 600;
            color: #444;
        }

        /* BOTONES EXPORTAR */
        .export-buttons {
            display: flex;
            justify-content: flex-end;
            gap: .5rem;
            margin: 1.2rem 0;
        }

        .export-buttons .btn {
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            color: #fff;
            transition: 0.25s;
            display: flex;
            align-items: center;
            gap: .4rem;
        }

        .btn i {
            transition: transform 0.2s;
        }

        .btn:hover i {
            transform: scale(1.1);
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

        /* TABLA */
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
            transition: 0.25s;
        }

        .badge {
            font-size: 0.85rem;
            padding: 0.4em 0.7em;
            border-radius: 8px;
            box-shadow: inset 0 0 6px rgba(255, 255, 255, 0.3);
        }

        .btn-ver {
            background-color: var(--verde-claro);
            color: #fff;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            transition: all 0.2s ease-in-out;
        }

        .btn-ver:hover {
            background-color: var(--verde-jaguata);
        }

        /* FOOTER */
        footer {
            text-align: center;
            color: #777;
            font-size: 0.85rem;
            margin-top: 2rem;
        }

        /* ANIMACIONES */
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

        .fade-in-row {
            animation: fadeIn 0.5s ease;
        }
    </style>
</head>

<body>

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="text-center mb-4">
            <img src="<?= ASSETS_URL ?>/uploads/perfiles/logojag.png" alt="Logo" width="70" class="rounded-circle bg-light p-2">
            <h6 class="mt-2 fw-bold text-success">Jaguata Admin</h6>
            <hr class="text-light">
        </div>
        <ul class="nav flex-column gap-1 px-2">
            <li><a class="nav-link" href="Dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            <li><a class="nav-link" href="Usuarios.php"><i class="fas fa-users"></i> Usuarios</a></li>
            <li><a class="nav-link active" href="#"><i class="fas fa-dog"></i> Paseos</a></li>
            <li><a class="nav-link" href="Pagos.php"><i class="fas fa-wallet"></i> Pagos</a></li>
            <li><a class="nav-link" href="Reportes.php"><i class="fas fa-file-alt"></i> Reportes</a></li>
            <li><a class="nav-link text-danger" href="/jaguata/public/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a></li>
        </ul>
    </aside>

    <!-- CONTENIDO -->
    <main>
        <div class="welcome-box">
            <div>
                <h1 class="fw-bold">Paseos registrados</h1>
                <p>Listado general de paseos activos, pendientes y completados 🐾</p>
            </div>
            <i class="fas fa-dog fa-3x opacity-75"></i>
        </div>

        <!-- FILTROS -->
        <div class="filtros">
            <form class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Buscar</label>
                    <input type="text" id="searchInput" class="form-control" placeholder="Paseador o cliente...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select id="filterEstado" class="form-select">
                        <option value="">Todos</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="confirmado">Confirmado</option>
                        <option value="en_curso">En curso</option>
                        <option value="finalizado">Finalizado</option>
                        <option value="cancelado">Cancelado</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha desde</label>
                    <input type="date" id="filterDesde" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha hasta</label>
                    <input type="date" id="filterHasta" class="form-control">
                </div>
            </form>
        </div>

        <!-- EXPORT -->
        <div class="export-buttons">
            <button class="btn btn-excel" onclick="window.location.href='/jaguata/public/api/paseos/exportarPaseos.php'">
                <i class="fas fa-file-excel"></i> Excel
            </button>
        </div>

        <!-- TABLA -->
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="tablaPaseos">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Paseador</th>
                        <th>Cliente</th>
                        <th>Fecha</th>
                        <th>Duración</th>
                        <th>Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($paseos as $p):
                        $estado = strtolower($p['estado'] ?? 'pendiente');
                        $badge = match ($estado) {
                            'pendiente' => 'bg-warning text-dark',
                            'confirmado' => 'bg-primary',
                            'en_curso' => 'bg-info text-dark',
                            'finalizado', 'completo' => 'bg-success',
                            'cancelado' => 'bg-danger',
                            default => 'bg-secondary'
                        };
                    ?>
                        <tr class="fade-in-row" data-estado="<?= $estado ?>">
                            <td><strong>#<?= htmlspecialchars($p['paseo_id']) ?></strong></td>
                            <td><?= htmlspecialchars($p['nombre_paseador'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($p['nombre_dueno'] ?? '-') ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($p['inicio'])) ?></td>
                            <td><?= (int)($p['duracion'] ?? 0) ?> min</td>
                            <td><span class="badge <?= $badge ?>"><?= ucfirst($estado) ?></span></td>
                            <td class="text-center">
                                <a href="VerPaseo.php?id=<?= $p['paseo_id'] ?>" class="btn-ver"><i class="fas fa-eye"></i> Ver</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <footer><small>© <?= date('Y') ?> Jaguata — Panel de Administración</small></footer>
    </main>

    <script>
        const search = document.getElementById('searchInput');
        const estado = document.getElementById('filterEstado');
        const desde = document.getElementById('filterDesde');
        const hasta = document.getElementById('filterHasta');
        const rows = document.querySelectorAll('#tablaPaseos tbody tr');

        function aplicarFiltros() {
            const texto = search.value.toLowerCase();
            const estadoVal = estado.value.toLowerCase();
            const fDesde = desde.value ? new Date(desde.value) : null;
            const fHasta = hasta.value ? new Date(hasta.value) : null;

            rows.forEach(row => {
                const rowEstado = row.dataset.estado;
                const rowTexto = row.textContent.toLowerCase();
                const fechaTexto = row.cells[3].textContent.split(' ')[0];
                const [d, m, y] = fechaTexto.split('/');
                const fechaRow = new Date(`${y}-${m}-${d}`);

                const coincideTexto = rowTexto.includes(texto);
                const coincideEstado = !estadoVal || rowEstado === estadoVal;
                const coincideFecha = (!fDesde || fechaRow >= fDesde) && (!fHasta || fechaRow <= fHasta);

                row.style.display = coincideTexto && coincideEstado && coincideFecha ? '' : 'none';
            });
        }

        [search, estado, desde, hasta].forEach(el => el.addEventListener('input', aplicarFiltros));

        document.querySelectorAll('.export-buttons .btn').forEach(btn => {
            // si el botón es Excel, no bloquear el click
            if (btn.classList.contains('btn-excel')) return;

            btn.addEventListener('click', e => {
                e.preventDefault();
                alert(`Exportar a ${btn.textContent.trim()} aún no implementado 🚀`);
            });
        });
    </script>
</body>

</html>