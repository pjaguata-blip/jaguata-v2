<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Models/Mascota.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Models\Mascota;

AppConfig::init();

// 🔒 Seguridad
if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'admin') {
    header('Location: ' . BASE_URL . '/public/login.php?error=unauthorized');
    exit;
}

// Modelo
$mascotaModel = new Mascota();
$mascotas     = $mascotaModel->getAllWithDueno() ?: [];

// Estadísticas
$totalMascotas = count($mascotas);
$totPequeno    = 0;
$totMediano    = 0;
$totGrande     = 0;

foreach ($mascotas as $m) {
    $t = strtolower($m['tamano'] ?? '');
    if ($t === 'pequeno') $totPequeno++;
    if ($t === 'mediano') $totMediano++;
    if ($t === 'grande')  $totGrande++;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Mascotas - Jaguata</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">
</head>

<body>

    <?php include __DIR__ . '/../../src/Templates/SidebarAdmin.php'; ?>

    <main>
        <div class="container-fluid px-3 px-md-4">

            <!-- HEADER (alineado con Usuarios/Paseos) -->
            <div class="header-box header-usuarios">
                <div>
                    <h1 class="fw-bold mb-1">Gestión de Mascotas</h1>
                    <p class="mb-0">Administrá mascotas registradas y sus dueños 🐾</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <!-- Toggle sidebar en móvil -->
                    <button class="btn btn-light d-lg-none" id="btnSidebarToggle" type="button">
                        <i class="fas fa-bars"></i>
                    </button>
                    <i class="fas fa-paw fa-3x opacity-75 d-none d-lg-block"></i>
                </div>
            </div>

            <!-- FILTROS -->
            <div class="filtros mb-3">
                <form class="row g-3 align-items-end">

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Buscar</label>
                        <input id="searchInput" type="text"
                            class="form-control"
                            placeholder="Nombre, dueño, correo o raza...">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Tamaño</label>
                        <select id="filterTamano" class="form-select">
                            <option value="">Todos</option>
                            <option value="pequeno">Pequeño</option>
                            <option value="mediano">Mediano</option>
                            <option value="grande">Grande</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Raza</label>
                        <input id="filterRaza" type="text" class="form-control"
                            placeholder="Ej: Poodle, Mestizo...">
                    </div>

                </form>
            </div>

            <!-- EXPORT -->
            <div class="export-buttons mb-3">
                <a class="btn btn-excel"
                    href="<?= BASE_URL; ?>/public/api/mascotas/exportMascotas.php">
                    <i class="fas fa-file-excel"></i> Excel
                </a>
            </div>

            <!-- CARD + TABLA -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Mascotas registradas</h5>
                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <span class="badge bg-secondary">
                            <?= $totalMascotas; ?> registro(s)
                        </span>
                        <?php if ($totalMascotas > 0): ?>
                            <span class="badge bg-success-subtle text-success">
                                Pequeñas: <?= $totPequeno; ?>
                            </span>
                            <span class="badge bg-info-subtle text-info">
                                Medianas: <?= $totMediano; ?>
                            </span>
                            <span class="badge bg-warning-subtle text-warning">
                                Grandes: <?= $totGrande; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table text-center align-middle table-hover" id="tablaMascotas">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Mascota</th>
                                    <th>Dueño</th>
                                    <th>Raza</th>
                                    <th>Tamaño</th>
                                    <th>Peso</th>
                                    <th>Edad</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if (empty($mascotas)): ?>
                                    <tr>
                                        <td colspan="8" class="text-muted py-3">
                                            No se encontraron mascotas registradas.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($mascotas as $m): ?>
                                        <?php
                                        /* Tamaño con badges */
                                        $t = strtolower($m['tamano'] ?? '');
                                        $badgeClass = match ($t) {
                                            'pequeno' => 'bg-success',
                                            'mediano' => 'bg-info',
                                            'grande'  => 'bg-warning',
                                            default   => 'bg-secondary'
                                        };
                                        $tamanoLabel = $t ? ucfirst($t) : 'N/D';

                                        /* Edad formateada */
                                        $mes = (int)($m['edad_meses'] ?? 0);
                                        if ($mes >= 12) {
                                            $anios = intdiv($mes, 12);
                                            $resto = $mes % 12;
                                            $edad  = $anios . " año" . ($anios > 1 ? "s" : "");
                                            if ($resto > 0) {
                                                $edad .= " y {$resto} mes" . ($resto > 1 ? "es" : "");
                                            }
                                        } elseif ($mes > 0) {
                                            $edad = $mes . " mes" . ($mes > 1 ? "es" : "");
                                        } else {
                                            $edad = "N/D";
                                        }

                                        $textoBusqueda = strtolower(
                                            ($m['nombre'] ?? '') . ' ' .
                                                ($m['dueno_nombre'] ?? '') . ' ' .
                                                ($m['dueno_email'] ?? '') . ' ' .
                                                ($m['raza'] ?? '')
                                        );
                                        ?>
                                        <tr class="fade-in-row"
                                            data-texto="<?= htmlspecialchars($textoBusqueda, ENT_QUOTES, 'UTF-8'); ?>"
                                            data-tamano="<?= htmlspecialchars(strtolower($m['tamano'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                            data-raza="<?= htmlspecialchars(strtolower($m['raza'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

                                            <!-- ID uniforme: # + negrita -->
                                            <td class="text-center">
                                                <strong>#<?= (int)$m['mascota_id'] ?></strong>
                                            </td>

                                            <td>
                                                <div class="d-flex align-items-center justify-content-center gap-2">
                                                    <?php if (!empty($m['foto_url'])): ?>
                                                        <img src="<?= htmlspecialchars($m['foto_url']); ?>"
                                                            class="rounded-circle"
                                                            style="width:40px;height:40px;object-fit:cover;">
                                                    <?php else: ?>
                                                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                                                            style="width:40px;height:40px;background:#eaf7f1;">
                                                            <i class="fas fa-dog text-success"></i>
                                                        </div>
                                                    <?php endif; ?>

                                                    <strong><?= htmlspecialchars($m['nombre']); ?></strong>
                                                </div>
                                            </td>

                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-semibold"><?= htmlspecialchars($m['dueno_nombre']); ?></span>
                                                    <small class="text-muted"><?= htmlspecialchars($m['dueno_email']); ?></small>
                                                </div>
                                            </td>

                                            <td><?= htmlspecialchars($m['raza']); ?></td>

                                            <td>
                                                <span class="badge <?= $badgeClass ?>"><?= $tamanoLabel ?></span>
                                            </td>

                                            <td><?= number_format((float)($m['peso_kg'] ?? 0), 1, ',', '.'); ?> kg</td>

                                            <td><?= $edad; ?></td>

                                            <td>
                                                <button class="btn-accion btn-editar-dueno"
                                                    type="button"
                                                    data-id="<?= $m['dueno_id'] ?>"
                                                    title="Ver dueño">
                                                    <i class="fas fa-user"></i>
                                                </button>

                                                <a href="<?= BASE_URL ?>/features/admin/PerfilMascotaAdmin.php?id=<?= (int)$m['mascota_id']; ?>"
                                                    class="btn-ver"
                                                    title="Ver detalle de mascota">
                                                    <i class="fas fa-eye"></i> Ver
                                                </a>
                                            </td>


                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <p class="text-muted small mt-2 mb-0">
                        Tip: combiná la búsqueda con filtros de tamaño y raza para encontrar mascotas específicas.
                    </p>
                </div>
            </div>

            <footer class="mt-3">
                <small>© <?= date('Y') ?> Jaguata — Panel de Administración</small>
            </footer>
        </div><!-- /.container-fluid -->
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // === Toggle sidebar en mobile (igual que en las otras) ===
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.querySelector('.sidebar');
            const btnToggle = document.getElementById('btnSidebarToggle');

            if (btnToggle && sidebar) {
                btnToggle.addEventListener('click', () => {
                    sidebar.classList.toggle('show');
                });
            }
        });

        // --------------------------
        // FILTROS
        // --------------------------
        const searchInput = document.getElementById("searchInput");
        const filterTamano = document.getElementById("filterTamano");
        const filterRaza = document.getElementById("filterRaza");
        const rows = document.querySelectorAll("#tablaMascotas tbody tr[data-texto]");

        function aplicarFiltros() {
            const busqueda = (searchInput.value || "").toLowerCase();
            const tamano = (filterTamano.value || "").toLowerCase();
            const raza = (filterRaza.value || "").toLowerCase();

            rows.forEach(row => {
                const txt = (row.dataset.texto || "").toLowerCase();
                const t = (row.dataset.tamano || "").toLowerCase();
                const r = (row.dataset.raza || "").toLowerCase();

                const okTexto = !busqueda || txt.includes(busqueda);
                const okTamano = !tamano || t === tamano;
                const okRaza = !raza || r.includes(raza);

                row.style.display = (okTexto && okTamano && okRaza) ? "" : "none";
            });
        }

        if (searchInput && filterTamano && filterRaza) {
            searchInput.addEventListener("input", aplicarFiltros);
            filterTamano.addEventListener("change", aplicarFiltros);
            filterRaza.addEventListener("input", aplicarFiltros);
        }

        // --------------------------
        // ACCIONES
        // --------------------------
        document.querySelectorAll(".btn-editar-dueno").forEach(btn => {
            btn.addEventListener("click", () => {
                window.location.href =
                    "<?= BASE_URL ?>/features/admin/editar_usuario.php?id=" + btn.dataset.id;
            });
        });
    </script>

</body>

</html>