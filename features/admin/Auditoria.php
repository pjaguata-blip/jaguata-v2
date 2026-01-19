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

/* 🔒 Seguridad admin */
if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'admin') {
    header('Location: ' . BASE_URL . '/public/login.php?error=unauthorized');
    exit;
}

/* ✅ baseFeatures para botón volver (faltaba) */
$baseFeatures = BASE_URL . '/features/admin';

function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/* Datos */
$auditoriaController = new AuditoriaController();
$registros = $auditoriaController->index() ?: [];
$sinDatos  = empty($registros);

/* ✅ Stats (igual estilo Mascotas: badges resumen) */
$total = count($registros);
$totUsuarios = $totPagos = $totMascotas = $totAuth = $totPaseos = $totOtros = 0;

foreach ($registros as $r) {
    $mod = strtolower(trim((string)($r['modulo'] ?? '')));
    if ($mod === 'usuarios') $totUsuarios++;
    elseif ($mod === 'pagos') $totPagos++;
    elseif ($mod === 'mascotas') $totMascotas++;
    elseif ($mod === 'autenticación' || $mod === 'autenticacion') $totAuth++;
    elseif ($mod === 'paseos') $totPaseos++;
    else $totOtros++;
}
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

    <style>
        /* ✅ evita scroll horizontal */
        html, body { overflow-x: hidden; width: 100%; }
        .table-responsive { overflow-x: auto; }
        th, td { white-space: nowrap; }

        /* ✅ chips estilo pro (como Mascotas/Pagos) */
        .estado-chip{
            display:inline-flex;
            align-items:center;
            gap:.35rem;
            justify-content:center;
            min-width: 130px;
        }
        .estado-dot{
            width:10px;height:10px;border-radius:999px;display:inline-block;
        }
        .dot-eliminar{ background:#dc3545; }     /* rojo */
        .dot-actualizar{ background:#f0ad4e; }   /* amarillo */
        .dot-crear{ background:#198754; }        /* verde */
        .dot-login{ background:#0dcaf0; }        /* celeste */
        .dot-otro{ background:#6c757d; }         /* gris */

        .accion-mini{ font-size:.85rem; }
        .detalle-cut{
            max-width: 520px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        @media (max-width: 768px){
            .detalle-cut{ max-width: 240px; }
        }
    </style>
</head>

<body>

    <?php include __DIR__ . '/../../src/Templates/SidebarAdmin.php'; ?>

    <main>
        <div class="container-fluid px-3 px-md-2">

            <!-- ✅ HEADER (igual al resto: toggle + volver) -->
            <div class="header-box header-auditoria mb-3">
                <div>
                    <h1 class="fw-bold mb-1">Auditoría del Sistema</h1>
                    <p class="mb-0">Registro detallado de acciones realizadas por usuarios 🕵️‍♂️</p>
                </div>

                <div class="d-flex align-items-center gap-2">
                   
                </div>

                <a href="<?= $baseFeatures; ?>/Dashboard.php" class="btn btn-outline-light">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
            </div>

            <!-- FILTROS -->
            <div class="filtros mb-3">
                <form class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Buscar</label>
                        <input type="text" id="searchInput" class="form-control" placeholder="Usuario o acción...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Módulo</label>
                        <select id="filterModulo" class="form-select">
                            <option value="">Todos</option>
                            <option value="usuarios">Usuarios</option>
                            <option value="pagos">Pagos</option>
                            <option value="mascotas">Mascotas</option>
                            <option value="autenticación">Autenticación</option>
                            <option value="paseos">Paseos</option>
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
            <div class="export-buttons mb-3">
                <button class="btn btn-excel"
                    onclick="window.location.href='<?= BASE_URL; ?>/public/api/auditoria/exportarAuditoria.php'">
                    <i class="fas fa-file-excel"></i> Excel
                </button>
            </div>

            <!-- ✅ SECTION CARD (como Mascotas/Notificaciones) -->
            <div class="section-card mb-3">
                <div class="section-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-user-shield me-2"></i>
                        <span>Historial de auditoría</span>
                    </div>

                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <span class="badge bg-secondary"><?= $total; ?> registro(s)</span>

                        <?php if ($total > 0): ?>
                            <span class="badge bg-success-subtle text-success">Usuarios: <?= (int)$totUsuarios; ?></span>
                            <span class="badge bg-info-subtle text-info">Pagos: <?= (int)$totPagos; ?></span>
                            <span class="badge bg-warning-subtle text-warning">Mascotas: <?= (int)$totMascotas; ?></span>
                            <span class="badge bg-primary-subtle text-primary">Paseos: <?= (int)$totPaseos; ?></span>
                            <span class="badge bg-secondary-subtle text-secondary">Otros: <?= (int)$totOtros; ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="section-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="tablaAuditoria">
                            <thead>
                                <tr>
                                    <th class="text-center">ID</th>
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
                                        $id       = (int)($r['id'] ?? 0);
                                        $usuario  = (string)($r['usuario'] ?? '');
                                        $accionTx = (string)($r['accion'] ?? '');
                                        $moduloTx = (string)($r['modulo'] ?? '');
                                        $detalles = (string)($r['detalles'] ?? '');

                                        $moduloData = strtolower(trim($moduloTx));
                                        $accion = strtolower($accionTx);

                                        // ✅ estado/accion pro (dot + badge-estado)
                                        $dotClass = 'dot-otro';
                                        $badgeAcc = 'estado-pendiente'; // default suave

                                        if (str_contains($accion, 'elimin') || str_contains($accion, 'borr')) {
                                            $dotClass = 'dot-eliminar';
                                            $badgeAcc = 'estado-rechazado';
                                        } elseif (str_contains($accion, 'actualiz') || str_contains($accion, 'edit')) {
                                            $dotClass = 'dot-actualizar';
                                            $badgeAcc = 'estado-suspendido';
                                        } elseif (str_contains($accion, 'cre') || str_contains($accion, 'insert') || str_contains($accion, 'registr')) {
                                            $dotClass = 'dot-crear';
                                            $badgeAcc = 'estado-aprobado';
                                        } elseif (str_contains($accion, 'inicio') || str_contains($accion, 'login') || str_contains($accion, 'sesion')) {
                                            $dotClass = 'dot-login';
                                            $badgeAcc = 'estado-activo';
                                        }

                                        $fechaRaw = $r['fecha'] ?? null;
                                        $fechaShow = $fechaRaw ? date('d/m/Y H:i:s', strtotime((string)$fechaRaw)) : '-';
                                        $fechaData = $fechaRaw ? date('Y-m-d', strtotime((string)$fechaRaw)) : '';
                                        ?>
                                        <tr class="fade-in-row"
                                            data-modulo="<?= h($moduloData); ?>"
                                            data-fecha="<?= h($fechaData); ?>">

                                            <td class="text-center">
                                                <strong>#<?= $id > 0 ? (int)$id : 0; ?></strong>
                                            </td>

                                            <td><?= h($fechaShow); ?></td>
                                            <td><?= h($usuario); ?></td>

                                            <td>
                                                <span class="badge-estado <?= h($badgeAcc); ?> estado-chip accion-mini">
                                                    <span class="estado-dot <?= h($dotClass); ?>"></span>
                                                    <?= h($accionTx); ?>
                                                </span>
                                            </td>

                                            <td>
                                                <span class="badge bg-secondary"><?= h($moduloTx); ?></span>
                                            </td>

                                            <td class="detalle-cut" title="<?= h($detalles); ?>">
                                                <?= h($detalles); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <p class="text-muted small mt-2 mb-0">
                        Tip: combiná búsqueda, módulo y rango de fechas para encontrar eventos específicos.
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
        // ✅ Toggle sidebar en mobile (igual)
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.querySelector('.sidebar');
            const btnToggle = document.getElementById('btnSidebarToggle');
            if (btnToggle && sidebar) btnToggle.addEventListener('click', () => sidebar.classList.toggle('show'));
        });

        // ✅ FILTROS auditoría (robusto: usa data-fecha YYYY-MM-DD)
        const input  = document.getElementById('searchInput');
        const modulo = document.getElementById('filterModulo');
        const desde  = document.getElementById('filterDesde');
        const hasta  = document.getElementById('filterHasta');
        const rows   = document.querySelectorAll('#tablaAuditoria tbody tr[data-modulo]');

        function aplicarFiltros() {
            const texto  = (input.value || '').toLowerCase();
            const modVal = (modulo.value || '').toLowerCase();
            const fDesde = desde.value ? new Date(desde.value) : null;
            const fHasta = hasta.value ? new Date(hasta.value) : null;

            rows.forEach(row => {
                const contenido = row.textContent.toLowerCase();
                const moduloRow = (row.dataset.modulo || '').toLowerCase();

                const fechaStr = row.dataset.fecha || ''; // YYYY-MM-DD
                const fechaRow = fechaStr ? new Date(fechaStr) : null;

                const coincideTexto = !texto || contenido.includes(texto);
                const coincideModulo = !modVal || moduloRow === modVal;

                let coincideFecha = true;
                if (fDesde && fechaRow) coincideFecha = coincideFecha && (fechaRow >= fDesde);
                if (fHasta && fechaRow) coincideFecha = coincideFecha && (fechaRow <= fHasta);

                row.style.display = (coincideTexto && coincideModulo && coincideFecha) ? '' : 'none';
            });
        }

        [input, modulo, desde, hasta].forEach(el => {
            if (!el) return;
            el.addEventListener('input', aplicarFiltros);
            el.addEventListener('change', aplicarFiltros);
        });
    </script>

</body>
</html>
