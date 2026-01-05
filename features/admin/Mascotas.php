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

/* 🔒 Seguridad */
if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'admin') {
    header('Location: ' . BASE_URL . '/public/login.php?error=unauthorized');
    exit;
}

function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/* Modelo */
$mascotaModel = new Mascota();
$mascotas     = $mascotaModel->getAllWithDueno() ?: [];

/* Estadísticas */
$totalMascotas = count($mascotas);
$totPequeno    = 0;
$totMediano    = 0;
$totGrande     = 0;

foreach ($mascotas as $m) {
    $t = strtolower((string)($m['tamano'] ?? ''));
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

    <!-- ✅ mini ajustes para que se vea lindo sin romper tu theme -->
    <style>
        .estado-chip {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            justify-content: center;
            min-width: 110px;
        }

        .estado-dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            display: inline-block;
        }

        .estado-dot.activo {
            background: #198754;
        }

        .estado-dot.inactivo {
            background: #6c757d;
        }

        .btn-accion {
            cursor: pointer;
        }

        .btn-accion[disabled] {
            opacity: .6;
            cursor: not-allowed;
        }

        /* grilla acciones más prolija */
        .acciones-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
            justify-content: center;
            align-items: center;
        }
    </style>
</head>

<body>

    <?php include __DIR__ . '/../../src/Templates/SidebarAdmin.php'; ?>

    <main>
        <div class="container-fluid px-3 px-md-2">

            <!-- HEADER -->
            <div class="header-box header-usuarios">
                <div>
                    <h1 class="fw-bold mb-1">Gestión de Mascotas</h1>
                    <p class="mb-0">Administrá mascotas registradas y sus dueños 🐾</p>
                </div>
                <div class="d-flex align-items-center gap-2">
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
                        <input id="searchInput" type="text" class="form-control"
                            placeholder="Nombre, dueño, correo o raza...">
                    </div>

                    <div class="col-md-2">
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

                    <!-- ✅ NUEVO: filtro por estado -->
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

            <!-- EXPORT -->
            <div class="export-buttons mb-3">
                <a class="btn btn-excel" href="<?= BASE_URL; ?>/public/api/mascotas/exportMascotas.php">
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
                            <span class="badge bg-success-subtle text-success">Pequeñas: <?= $totPequeno; ?></span>
                            <span class="badge bg-info-subtle text-info">Medianas: <?= $totMediano; ?></span>
                            <span class="badge bg-warning-subtle text-warning">Grandes: <?= $totGrande; ?></span>
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
                                    <th>Estado</th> <!-- ✅ NUEVO -->
                                    <th>Acciones</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if (empty($mascotas)): ?>
                                    <tr>
                                        <td colspan="9" class="text-muted py-3">
                                            No se encontraron mascotas registradas.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($mascotas as $m): ?>
                                        <?php
                                        /* Tamaño badge */
                                        $t = strtolower((string)($m['tamano'] ?? ''));
                                        $badgeClass = match ($t) {
                                            'pequeno' => 'bg-success',
                                            'mediano' => 'bg-info',
                                            'grande'  => 'bg-warning',
                                            default   => 'bg-secondary'
                                        };
                                        $tamanoLabel = $t ? ucfirst($t) : 'N/D';

                                        /* Edad */
                                        $mes = (int)($m['edad_meses'] ?? 0);
                                        if ($mes >= 12) {
                                            $anios = intdiv($mes, 12);
                                            $resto = $mes % 12;
                                            $edad  = $anios . " año" . ($anios > 1 ? "s" : "");
                                            if ($resto > 0) $edad .= " y {$resto} mes" . ($resto > 1 ? "es" : "");
                                        } elseif ($mes > 0) {
                                            $edad = $mes . " mes" . ($mes > 1 ? "es" : "");
                                        } else {
                                            $edad = "N/D";
                                        }

                                        /* ✅ Estado */
                                        $estadoMascota = strtolower((string)($m['estado'] ?? 'activo'));
                                        if (!in_array($estadoMascota, ['activo', 'inactivo'], true)) {
                                            $estadoMascota = 'activo';
                                        }
                                        $badgeEstado = $estadoMascota === 'activo' ? 'estado-activo' : 'estado-inactivo';

                                        /* texto para buscar */
                                        $textoBusqueda = strtolower(
                                            (string)($m['nombre'] ?? '') . ' ' .
                                                (string)($m['dueno_nombre'] ?? '') . ' ' .
                                                (string)($m['dueno_email'] ?? '') . ' ' .
                                                (string)($m['raza'] ?? '')
                                        );
                                        ?>
                                        <tr class="fade-in-row"
                                            data-texto="<?= h($textoBusqueda); ?>"
                                            data-tamano="<?= h(strtolower((string)($m['tamano'] ?? ''))); ?>"
                                            data-raza="<?= h(strtolower((string)($m['raza'] ?? ''))); ?>"
                                            data-estado="<?= h($estadoMascota); ?>">

                                            <td class="text-center">
                                                <strong>#<?= (int)$m['mascota_id'] ?></strong>
                                            </td>

                                            <td>
                                                <div class="d-flex align-items-center justify-content-center gap-2">
                                                    <?php if (!empty($m['foto_url'])): ?>
                                                        <img src="<?= h($m['foto_url']); ?>"
                                                            class="rounded-circle"
                                                            style="width:40px;height:40px;object-fit:cover;">
                                                    <?php else: ?>
                                                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                                                            style="width:40px;height:40px;background:#eaf7f1;">
                                                            <i class="fas fa-dog text-success"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <strong><?= h($m['nombre'] ?? ''); ?></strong>
                                                </div>
                                            </td>

                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-semibold"><?= h($m['dueno_nombre'] ?? ''); ?></span>
                                                    <small class="text-muted"><?= h($m['dueno_email'] ?? ''); ?></small>
                                                </div>
                                            </td>

                                            <td><?= h($m['raza'] ?? ''); ?></td>

                                            <td>
                                                <span class="badge <?= h($badgeClass) ?>"><?= h($tamanoLabel) ?></span>
                                            </td>

                                            <td><?= number_format((float)($m['peso_kg'] ?? 0), 1, ',', '.'); ?> kg</td>

                                            <td><?= h($edad); ?></td>

                                            <!-- ✅ ESTADO -->
                                            <td>
                                                <span class="badge-estado <?= h($badgeEstado) ?> estado-chip">
                                                    <span class="estado-dot <?= h($estadoMascota) ?>"></span>
                                                    <?= h(ucfirst($estadoMascota)) ?>
                                                </span>
                                            </td>

                                            <td>
                                                <div class="acciones-wrap">
                                                    <button class="btn-accion btn-editar-dueno"
                                                        type="button"
                                                        data-id="<?= (int)$m['dueno_id'] ?>"
                                                        title="Ver dueño">
                                                        <i class="fas fa-user"></i>
                                                    </button>

                                                    <a href="<?= BASE_URL ?>/features/admin/PerfilMascotaAdmin.php?id=<?= (int)$m['mascota_id']; ?>"
                                                        class="btn-ver"
                                                        title="Ver detalle de mascota">
                                                        <i class="fas fa-eye"></i> Ver
                                                    </a>

                                                    <!-- ✅ Activar / Inactivar -->
                                                    <?php if ($estadoMascota === 'activo'): ?>
                                                        <button
                                                            type="button"
                                                            class="btn-accion btn-desactivar btn-mascota-accion"
                                                            data-id="<?= (int)$m['mascota_id'] ?>"
                                                            data-accion="inactivar"
                                                            title="Inactivar mascota">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button
                                                            type="button"
                                                            class="btn-accion btn-activar btn-mascota-accion"
                                                            data-id="<?= (int)$m['mascota_id'] ?>"
                                                            data-accion="activar"
                                                            title="Activar mascota">
                                                            <i class="fas fa-check-circle"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <p class="text-muted small mt-2 mb-0">
                        Tip: combiná búsqueda + filtros de tamaño/raza/estado para encontrar mascotas específicas.
                    </p>
                </div>
            </div>

            <footer class="mt-3">
                <small>© <?= date('Y') ?> Jaguata — Panel de Administración</small>
            </footer>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Sidebar mobile
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.querySelector('.sidebar');
            const btnToggle = document.getElementById('btnSidebarToggle');
            if (btnToggle && sidebar) btnToggle.addEventListener('click', () => sidebar.classList.toggle('show'));
        });

        // FILTROS
        const searchInput = document.getElementById("searchInput");
        const filterTamano = document.getElementById("filterTamano");
        const filterRaza = document.getElementById("filterRaza");
        const filterEstado = document.getElementById("filterEstado");
        const rows = document.querySelectorAll("#tablaMascotas tbody tr[data-texto]");

        function aplicarFiltros() {
            const busqueda = (searchInput.value || "").toLowerCase();
            const tamano = (filterTamano.value || "").toLowerCase();
            const raza = (filterRaza.value || "").toLowerCase();
            const estado = (filterEstado.value || "").toLowerCase();

            rows.forEach(row => {
                const txt = (row.dataset.texto || "").toLowerCase();
                const t = (row.dataset.tamano || "").toLowerCase();
                const r = (row.dataset.raza || "").toLowerCase();
                const e = (row.dataset.estado || "").toLowerCase();

                const okTexto = !busqueda || txt.includes(busqueda);
                const okTamano = !tamano || t === tamano;
                const okRaza = !raza || r.includes(raza);
                const okEstado = !estado || e === estado;

                row.style.display = (okTexto && okTamano && okRaza && okEstado) ? "" : "none";
            });
        }

        if (searchInput) searchInput.addEventListener("input", aplicarFiltros);
        if (filterTamano) filterTamano.addEventListener("change", aplicarFiltros);
        if (filterRaza) filterRaza.addEventListener("input", aplicarFiltros);
        if (filterEstado) filterEstado.addEventListener("change", aplicarFiltros);

        // ACCIONES: ver dueño
        document.querySelectorAll(".btn-editar-dueno").forEach(btn => {
            btn.addEventListener("click", () => {
                window.location.href = "<?= BASE_URL ?>/features/admin/editar_usuario.php?id=" + btn.dataset.id;
            });
        });

        // ✅ ACCIONES: activar/inactivar mascota
        document.querySelectorAll('.btn-mascota-accion').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = btn.dataset.id;
                const accion = btn.dataset.accion;

                const msg = (accion === 'activar') ?
                    '¿Activar esta mascota?' :
                    '¿Inactivar esta mascota?';

                if (!confirm(msg)) return;

                btn.disabled = true;

                const fd = new FormData();
                fd.append('id', id);
                fd.append('accion', accion);

                try {
                    const res = await fetch('<?= BASE_URL; ?>/public/api/mascotas/accionesMascota.php', {
                        method: 'POST',
                        body: fd
                    });
                    const data = await res.json();
                    alert(data.mensaje || 'Operación realizada');

                    if (data.ok) location.reload();
                } catch (e) {
                    console.error(e);
                    alert('Error inesperado al actualizar el estado.');
                } finally {
                    btn.disabled = false;
                }
            });
        });
    </script>

</body>

</html>