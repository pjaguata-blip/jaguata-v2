<?php
require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/PagoController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;

AppConfig::init();

if (!Session::isLoggedIn()) {
    header('Location: /jaguata/public/login.php');
    exit;
}
$rol = Session::getUsuarioRol() ?? 'admin';
$baseFeatures = BASE_URL . "/features/{$rol}";

/** Datos de ejemplo (reemplazar por PagoController cuando lo tengas) */
$pagos = [
    ['id' => 5001, 'usuario' => 'Lucas Díaz', 'monto' => 40000,  'fecha' => '2025-10-26', 'estado' => 'Pagado'],
    ['id' => 5002, 'usuario' => 'María López', 'monto' => 25000,  'fecha' => '2025-10-27', 'estado' => 'Pendiente'],
    ['id' => 5003, 'usuario' => 'Pedro Gómez', 'monto' => 150000, 'fecha' => '2025-10-28', 'estado' => 'Pagado'],
];
$pagosFiltrados = array_values(array_filter($pagos, fn($p) => strcasecmp($p['estado'], 'Pagado') === 0));
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagos efectuados - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f7fa;
            font-family: "Poppins", sans-serif
        }

        .layout {
            display: flex;
            width: 100%;
            min-height: 100vh
        }

        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            color: #f8f9fa;
            width: 240px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            padding-top: 1.5rem;
            box-shadow: 4px 0 12px rgba(0, 0, 0, .15);
            z-index: 1000
        }

        .sidebar .nav-link {
            color: #ddd;
            display: flex;
            align-items: center;
            padding: 10px 16px;
            border-radius: 8px;
            margin: 4px 8px;
            transition: .2s;
            font-weight: 500
        }

        .sidebar .nav-link.active {
            background: #3c6255;
            color: #fff
        }

        main.content {
            flex-grow: 1;
            margin-left: 240px;
            padding: 2rem 2.5rem;
            background: #f5f7fa
        }

        .welcome-box {
            background: linear-gradient(90deg, #20c997, #3c6255);
            color: #fff;
            padding: 1.5rem 2rem;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 15px rgba(0, 0, 0, .08)
        }

        .table {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, .08)
        }

        .table thead {
            background: #3c6255;
            color: #fff;
            text-transform: uppercase
        }

        .btn-ver {
            background: #20c997;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 6px 12px
        }

        .btn-ver:hover {
            background: #3c6255
        }

        footer {
            text-align: center;
            color: #777;
            font-size: .85rem;
            margin-top: 2rem
        }
    </style>
</head>

<body>
    <div class="layout">
        <?php include __DIR__ . '/../../src/Templates/SidebarAdmin.php'; ?>
        <main class="content">
            <div class="welcome-box mb-4 d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="fw-bold mb-1"><i class="fas fa-check-circle me-2"></i>Pagos efectuados</h1>
                    <p class="mb-0">Transacciones confirmadas en el sistema.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="PagosPendientes.php" class="btn btn-light fw-semibold border border-2 border-white rounded-pill px-3">
                        <i class="fas fa-clock me-1"></i> Ver pendientes
                    </a>
                    <a href="Dashboard.php" class="btn btn-outline-light fw-semibold rounded-pill px-3">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <table class="table table-hover align-middle text-center">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>Monto</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pagosFiltrados as $p): ?>
                                <tr>
                                    <td><strong>#<?= $p['id'] ?></strong></td>
                                    <td><?= htmlspecialchars($p['usuario']) ?></td>
                                    <td>₲<?= number_format($p['monto'], 0, ',', '.') ?></td>
                                    <td><?= date('d/m/Y', strtotime($p['fecha'])) ?></td>
                                    <td><span class="badge bg-success"><?= htmlspecialchars($p['estado']) ?></span></td>
                                    <td>
                                        <button class="btn-ver"
                                            onclick="verComprobante('<?= $p['id'] ?>','<?= $p['usuario'] ?>','<?= $p['monto'] ?>','<?= $p['fecha'] ?>','<?= $p['estado'] ?>')">
                                            <i class="fas fa-file-invoice"></i> Ver
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($pagosFiltrados)): ?>
                                <tr>
                                    <td colspan="6" class="text-muted">Sin pagos efectuados.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <footer><small>© <?= date('Y') ?> Jaguata — Sistema de Pagos</small></footer>
        </main>
    </div>

    <!-- Modal reutilizado -->
    <div class="modal fade" id="comprobanteModal" tabindex="-1" aria-labelledby="comprobanteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content shadow-lg border-0 rounded-4">
                <div class="modal-header justify-content-center" style="background:linear-gradient(90deg,#20c997,#3c6255);color:#fff;">
                    <h5 class="modal-title text-center fw-semibold" id="comprobanteModalLabel">
                        <i class="fas fa-file-invoice me-2"></i>Comprobante de Pago
                    </h5>
                </div>
                <div class="modal-body py-5 px-4" style="background:#f8f9fa;">
                    <div id="comprobante" class="bg-white rounded-4 shadow-sm mx-auto p-4 text-center" style="max-width:480px;">
                        <div class="mb-3">
                            <img src="<?= ASSETS_URL ?>/uploads/perfiles/logojag.png" alt="Logo Jaguata" style="width:38px;height:38px;margin-bottom:6px;">
                            <h4 class="fw-bold m-0" style="color:#3c6255;">Jaguata</h4>
                            <small class="text-muted d-block">Plataforma de Paseos</small>
                        </div>
                        <hr class="my-3" style="border-top:1px dashed #ccc;width:80%;margin:auto;">
                        <div class="text-center mt-4">
                            <h5 class="fw-semibold mb-3 text-success">Detalle del Pago</h5>
                            <div class="text-start d-inline-block text-secondary" style="min-width:260px;text-align:left;">
                                <p class="mb-2"><strong>ID de pago:</strong> <span id="comp-id"></span></p>
                                <p class="mb-2"><strong>Usuario:</strong> <span id="comp-usuario"></span></p>
                                <p class="mb-2"><strong>Monto:</strong> ₲<span id="comp-monto"></span></p>
                                <p class="mb-2"><strong>Fecha:</strong> <span id="comp-fecha"></span></p>
                                <p class="mb-2"><strong>Estado:</strong> <span id="comp-estado" class="badge bg-secondary"></span></p>
                            </div>
                        </div>
                        <hr class="my-4" style="border-top:1px dashed #ccc;width:80%;margin:auto;">
                        <div class="mt-3">
                            <small class="text-muted d-block mb-1">Este comprobante certifica el pago registrado en el sistema Jaguata.</small>
                            <small class="text-muted">Emitido automáticamente por el panel administrativo 🐾</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-center" style="background:#f8f9fa;border-top:none;">
                    <button class="btn btn-success px-4" onclick="imprimirComprobante()">
                        <i class="fas fa-download me-1"></i> Descargar comprobante
                    </button>
                    <button class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function verComprobante(id, usuario, monto, fecha, estado) {
            document.getElementById('comp-id').textContent = id;
            document.getElementById('comp-usuario').textContent = usuario;
            document.getElementById('comp-monto').textContent = new Intl.NumberFormat('es-PY').format(monto);
            document.getElementById('comp-fecha').textContent = new Date(fecha).toLocaleDateString('es-PY');
            const estadoSpan = document.getElementById('comp-estado');
            estadoSpan.textContent = estado;
            estadoSpan.className = 'badge ' + (estado.toLowerCase() === 'pagado' ? 'bg-success' : 'bg-warning text-dark');
            const modal = new bootstrap.Modal(document.getElementById('comprobanteModal'));
            modal.show();
        }

        function imprimirComprobante() {
            const contenido = document.getElementById('comprobante').innerHTML;
            const ventana = window.open('', '_blank');
            ventana.document.write(`<html><head><title>Comprobante Jaguata</title>
  <style>body{font-family:Poppins,sans-serif;padding:20px;background:#f9fafb;text-align:center}
  .comprobante{border:1px solid #3c6255;border-radius:10px;padding:20px;background:#fff;display:inline-block;text-align:left}
  h4{color:#3c6255;text-align:center}hr{margin:10px 0}</style></head><body>${contenido}</body></html>`);
            ventana.document.close();
            ventana.print();
        }
    </script>
</body>

</html>