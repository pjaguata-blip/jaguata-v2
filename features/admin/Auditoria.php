<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/AuditoriaController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Controllers\AuditoriaController;

AppConfig::init();

// Seguridad admin
if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'admin') {
    header('Location: ' . BASE_URL . '/public/login.php?error=unauthorized');
    exit;
}

$auditoriaController = new AuditoriaController();
$registros = $auditoriaController->index();
$sinDatos  = empty($registros);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Auditoría del Sistema - Jaguata</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">
</head>

<body>

    <?php include __DIR__ . '/../../src/Templates/SidebarAdmin.php'; ?>

    <main>
        <div class="header-box header-auditoria">
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
                        <option value="Paseos">Paseos</option>
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


        <!-- EXPORT -->
        <div class="export-buttons">
            <button class="btn btn-excel"
                onclick="window.location.href='<?= BASE_URL; ?>/public/api/auditoria/exportarAuditoria.php'">
                <i class="fas fa-file-excel"></i> Excel
            </button>
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
                    <?php if ($sinDatos): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">
                                No hay registros de auditoría disponibles.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($registros as $r): ?>
                            <?php
                            $accion = strtolower($r['accion'] ?? '');
                            $color  = str_contains($accion, 'elimin') ? 'bg-danger'
                                : (str_contains($accion, 'actualiz') ? 'bg-warning text-dark'
                                    : (str_contains($accion, 'inicio') ? 'bg-success'
                                        : 'bg-info text-dark'));
                            ?>
                            <tr data-modulo="<?= strtolower($r['modulo'] ?? '') ?>">
                                <td><strong>#<?= htmlspecialchars((string)($r['id'] ?? '')) ?></strong></td>
                                <td>
                                    <?php
                                    $fechaRaw = $r['fecha'] ?? null;
                                    echo $fechaRaw ? date('d/m/Y H:i:s', strtotime($fechaRaw)) : '-';
                                    ?>
                                </td>
                                <td><?= htmlspecialchars((string)($r['usuario'] ?? '')) ?></td>
                                <td><span class="badge <?= $color ?>"><?= htmlspecialchars((string)($r['accion'] ?? '')) ?></span></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars((string)($r['modulo'] ?? '')) ?></span></td>
                                <td><?= htmlspecialchars((string)($r['detalles'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
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
                const moduloRow = (row.dataset.modulo || '').toLowerCase();
                const fechaTexto = row.cells[1].textContent.split(' ')[0]; // dd/mm/YYYY
                let coincideFecha = true;

                if (fechaTexto && fechaTexto.includes('/')) {
                    const [d, m, y] = fechaTexto.split('/');
                    const fechaRow = new Date(`${y}-${m}-${d}`);

                    coincideFecha =
                        (!fDesde || fechaRow >= fDesde) &&
                        (!fHasta || fechaRow <= fHasta);
                }

                const coincideTexto = contenido.includes(texto);
                const coincideModulo = !modVal || moduloRow === modVal.toLowerCase();

                row.style.display = (coincideTexto && coincideModulo && coincideFecha) ? '' : 'none';
            });
        }

        [input, modulo, desde, hasta].forEach(el => el.addEventListener('input', aplicarFiltros));
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>