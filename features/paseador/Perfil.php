<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/DisponibilidadController.php';
require_once __DIR__ . '/../../src/Models/Usuario.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';
require_once __DIR__ . '/../../src/Services/CalificacionService.php'; // ⭐ reputación
require_once __DIR__ . '/../../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\DisponibilidadController;
use Jaguata\Models\Usuario;
use Jaguata\Helpers\Session;
use Jaguata\Services\CalificacionService;

AppConfig::init();

/* 🔒 Solo paseador */
$auth = new AuthController();
$auth->checkRole('paseador');

$usuarioModel = new Usuario();
$usuarioId    = (int) Session::getUsuarioId();
$usuario      = $usuarioModel->find($usuarioId);

if (!$usuario) {
    echo "Error: No se encontró el usuario.";
    exit;
}

/* ===== Helpers perfil ===== */
function h(?string $v, string $fallback = '—'): string
{
    $v = trim((string)($v ?? ''));
    return $v !== '' ? htmlspecialchars($v, ENT_QUOTES, 'UTF-8') : $fallback;
}
function fechaLatina(?string $ymd): string
{
    if (!$ymd) return '—';
    $ts = strtotime($ymd);
    return $ts ? date('d/m/Y', $ts) : htmlspecialchars($ymd, ENT_QUOTES, 'UTF-8');
}
function calcularEdad(?string $ymd): ?int
{
    if (!$ymd) return null;
    try {
        $nac = new DateTime($ymd);
        $hoy = new DateTime('today');
        return $nac->diff($hoy)->y;
    } catch (\Throwable) {
        return null;
    }
}
function esUrlAbsoluta(string $p): bool
{
    return (bool)preg_match('#^https?://#i', $p);
}

/* ===== Datos derivados perfil ===== */
$foto = $usuario['foto_perfil'] ?? ($usuario['perfil_foto'] ?? '');
if ($foto && !esUrlAbsoluta($foto)) {
    $foto = rtrim(BASE_URL, '/') . $foto;
}
if (!$foto) {
    $foto = ASSETS_URL . '/images/user-placeholder.png';
}

$edad = calcularEdad($usuario['fecha_nacimiento'] ?? null);

// Zonas (JSON o CSV)
$zonas = [];
if (!empty($usuario['zona'])) {
    $decoded = json_decode($usuario['zona'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $zonas = array_values(array_filter(array_map('trim', $decoded)));
    } else {
        $zonas = array_values(array_filter(array_map('trim', explode(',', $usuario['zona']))));
    }
}

/* 💳 Datos de cuenta para recibir transferencias
   Ajustá los índices según cómo se llamen en tu tabla `usuarios`.
   Ejemplo: banco_pago / alias_pago / cuenta_pago  */
$ctaBanco  = $usuario['banco_pago']  ?? ($usuario['banco']  ?? '');
$ctaAlias  = $usuario['alias_pago']  ?? ($usuario['alias']  ?? '');
$ctaCuenta = $usuario['cuenta_pago'] ?? ($usuario['cuenta'] ?? '');

/* ===== Disponibilidad ===== */
$diasSemana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

$dispCtrl             = new DisponibilidadController();
$disponibilidadActual = $dispCtrl->getFormDataByPaseador($usuarioId); // ['Lunes' => ['inicio'=>..]]

/* ⭐ Reputación del paseador (promedio + total) */
$califService = new CalificacionService();
$stats        = $califService->getPromedioByUsuario($usuarioId);
$repPromedio  = isset($stats['promedio']) ? (float)$stats['promedio'] : 0.0;
$repTotal     = isset($stats['total']) ? (int)$stats['total'] : 0;

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Paseador | Jaguata</title>

    <!-- CSS global Jaguata (igual que MiPerfil dueño) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <style>
        /* 🐾 Foto de perfil igual que en MiPerfil dueño */
        .perfil-avatar-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.75rem;
        }

        .perfil-avatar {
            width: 160px !important;
            height: 160px !important;
            max-width: 160px !important;
            max-height: 160px !important;
            object-fit: cover;
            border-radius: 50% !important;
            border: 4px solid var(--verde-jaguata);
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.12);
            display: block;
        }

        .badge-rol {
            background-color: #e6f4ea;
            color: var(--verde-jaguata);
            border-radius: 8px;
            font-size: 0.85rem;
            padding: 0.4em 0.6em;
        }

        .rating-block-paseador {
            border-top: 1px solid #e6e6e6;
            margin-top: 1rem;
            padding-top: 0.75rem;
        }

        .rating-block-paseador .rating-stars i {
            margin-right: 2px;
        }

        /* Disponibilidad dentro de section-card */
        .day-row {
            display: grid;
            grid-template-columns: 130px 110px 1fr;
            align-items: center;
            border-bottom: 1px solid #eaeaea;
            padding: 0.7rem 0;
            gap: 0.75rem;
        }

        .day-name {
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--verde-jaguata);
        }

        .form-switch .form-check-input {
            width: 3.2em;
            height: 1.5em;
        }

        .form-check-input:checked {
            background-color: var(--verde-claro);
            border-color: var(--verde-claro);
        }

        .time-group input[type="time"] {
            border-radius: 8px;
            border: 1px solid #d0d0d0;
            padding: 0.35rem 0.5rem;
            font-size: 0.85rem;
            width: 115px;
        }

        .time-group span {
            color: #888;
            margin: 0 0.3rem;
        }

        .time-group.disabled input,
        .time-group.disabled button {
            opacity: 0.4;
            pointer-events: none;
        }

        .copy-btn {
            border: none;
            background: transparent;
            margin-left: 0.5rem;
        }

        .copy-btn i {
            color: var(--verde-jaguata);
        }

        #alerta {
            position: fixed;
            bottom: 25px;
            right: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .2);
            display: none;
            font-size: 0.95rem;
            z-index: 2000;
        }
    </style>
</head>

<body>
    <!-- Sidebar paseador unificado -->
    <?php include __DIR__ . '/../../src/Templates/SidebarPaseador.php'; ?>

    <!-- Botón hamburguesa mobile -->
    <button class="btn btn-outline-secondary d-md-none ms-2 mt-3" id="toggleSidebar">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Contenido principal (mismo layout que MiPerfil dueño) -->
    <main>
        <div class="py-4">

            <!-- HEADER estilo dueño -->
            <div class="header-box header-dashboard mb-4 d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="fw-bold mb-1">
                        <i class="fas fa-user me-2"></i>Mi Perfil — Paseador
                    </h1>
                    <p class="mb-0">
                        Zonas de trabajo, tu experiencia, disponibilidad y reputación dentro de Jaguata 🐾
                    </p>
                </div>

                <div class="text-end">
                    <?php if ($repPromedio > 0 && $repTotal > 0): ?>
                        <div class="fs-4 fw-semibold mb-1">
                            <i class="fas fa-star text-warning me-1"></i>
                            <?= number_format($repPromedio, 1, ',', '.'); ?>/5
                        </div>
                        <small class="text-white-50 d-block mb-2">
                            <?= $repTotal ?> opinión<?= $repTotal === 1 ? '' : 'es' ?>
                        </small>
                    <?php else: ?>
                        <small class="text-white-50 d-block mb-2">
                            Aún sin calificaciones.
                        </small>
                    <?php endif; ?>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="Dashboard.php" class="btn btn-outline-light btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </a>
                        <a href="EditarPerfil.php" class="btn btn-light btn-sm text-success">
                            <i class="fas fa-edit me-1"></i> Editar
                        </a>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <!-- Columna izquierda: Info básica + reputación -->
                <div class="col-lg-4">
                    <div class="section-card text-center">
                        <div class="mb-3">
                            <div class="perfil-avatar-wrapper">
                                <img
                                    src="<?= h($foto) ?>"
                                    alt="Foto de perfil"
                                    class="perfil-avatar mb-2"
                                    width="160"
                                    height="160">
                            </div>
                            <h4 class="mb-1"><?= h($usuario['nombre'] ?? null, 'Sin nombre') ?></h4>
                            <span class="badge-rol">Paseador</span>
                        </div>

                        <div class="perfil-datos text-start small">
                            <div class="mb-2">
                                <i class="fa-solid fa-envelope me-2"></i>
                                <strong>Email:</strong> <?= h($usuario['email']) ?>
                            </div>
                            <div class="mb-2">
                                <i class="fa-solid fa-phone me-2"></i>
                                <strong>Teléfono:</strong> <?= h($usuario['telefono']) ?>
                            </div>
                            <div class="mb-2">
                                <i class="fa-solid fa-cake-candles me-2"></i>
                                <strong>Cumpleaños:</strong>
                                <?php if (!empty($usuario['fecha_nacimiento'])): ?>
                                    <?= fechaLatina($usuario['fecha_nacimiento']) ?>
                                    <?php if ($edad !== null): ?>
                                        <span class="text-muted">(<?= $edad ?> años)</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">No especificado</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- ⭐ Reputación como paseador -->
                        <div class="rating-block-paseador text-start mt-3">
                            <h6 class="text-muted mb-2">
                                <i class="fas fa-star me-2 text-warning"></i>Reputación como paseador
                            </h6>

                            <?php if ($repPromedio > 0 && $repTotal > 0): ?>
                                <div class="d-flex flex-column align-items-start">
                                    <div class="fs-5 fw-semibold">
                                        <?= number_format($repPromedio, 1, ',', '.'); ?>/5
                                    </div>
                                    <div class="rating-stars mb-1">
                                        <?php
                                        $rounded = (int) round($repPromedio);
                                        for ($i = 1; $i <= 5; $i++):
                                            $cls = $i <= $rounded ? 'fas text-warning' : 'far text-muted';
                                        ?>
                                            <i class="<?= $cls ?> fa-star"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <small class="text-muted">
                                        <?= $repTotal ?> opinión<?= $repTotal === 1 ? '' : 'es' ?> de dueños
                                    </small>
                                </div>
                            <?php else: ?>
                                <div class="text-muted small">
                                    <i class="far fa-star me-1"></i>
                                    Aún no tenés calificaciones como paseador.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Columna derecha: zonas + experiencia + cuenta + disponibilidad -->
                <div class="col-lg-8">
                    <div class="row g-3">
                        <!-- Zonas de trabajo -->
                        <div class="col-12">
                            <div class="section-card">
                                <div class="section-header">
                                    <i class="fa-solid fa-map-location-dot me-2"></i> Zonas de trabajo
                                </div>
                                <div class="section-body">
                                    <?php if (empty($zonas)): ?>
                                        <span class="text-muted">Sin zonas registradas.</span>
                                    <?php else: ?>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php foreach ($zonas as $z): ?>
                                                <span class="badge bg-success-subtle text-success-emphasis">
                                                    <?= htmlspecialchars($z, ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Experiencia -->
                        <div class="col-12">
                            <div class="section-card">
                                <div class="section-header">
                                    <i class="fa-solid fa-briefcase me-2"></i> Experiencia
                                </div>
                                <div class="section-body">
                                    <?php if (!empty($usuario['experiencia'])): ?>
                                        <div class="text-muted" style="white-space: pre-wrap;">
                                            <?= htmlspecialchars($usuario['experiencia'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">No especificada.</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- 💳 Datos de cuenta para recibir transferencias -->
                        <div class="col-12">
                            <div class="section-card">
                                <div class="section-header">
                                    <i class="fas fa-piggy-bank me-2"></i> Datos para recibir transferencias
                                </div>
                                <div class="section-body">
                                    <p class="text-muted small mb-3">
                                        Estos datos se mostrarán al dueño al momento de pagar tu paseo
                                        (en la pantalla de pago), para que pueda transferirte correctamente. 💸
                                    </p>

                                    <form id="formCuenta">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label">Banco</label>
                                                <input type="text"
                                                    name="banco"
                                                    class="form-control"
                                                    maxlength="100"
                                                    value="<?= htmlspecialchars($ctaBanco, ENT_QUOTES, 'UTF-8') ?>"
                                                    placeholder="Ej: Banco Familiar">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Alias / Cuenta</label>
                                                <input type="text"
                                                    name="alias"
                                                    class="form-control"
                                                    maxlength="120"
                                                    value="<?= htmlspecialchars($ctaAlias, ENT_QUOTES, 'UTF-8') ?>"
                                                    placeholder="Alias o número de cuenta">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Número de cuenta (opcional)</label>
                                                <input type="text"
                                                    name="cuenta"
                                                    class="form-control"
                                                    maxlength="120"
                                                    value="<?= htmlspecialchars($ctaCuenta, ENT_QUOTES, 'UTF-8') ?>"
                                                    placeholder="Si es distinto del alias">
                                            </div>
                                        </div>

                                        <div class="text-end mt-3">
                                            <button type="submit" class="btn btn-gradient px-4 py-2">
                                                <i class="fas fa-save me-2"></i> Guardar datos de cuenta
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Disponibilidad semanal -->
                        <div class="col-12">
                            <div class="section-card">
                                <div class="section-header">
                                    <i class="fas fa-calendar-check me-2"></i> Disponibilidad semanal
                                </div>
                                <div class="section-body">
                                    <p class="text-muted mb-3">
                                        Activá los días que estás disponible y definí tus horarios.
                                        <br><small class="text-secondary">Podés copiar tus horarios de un día a otro fácilmente.</small>
                                    </p>

                                    <form id="formDisponibilidad">
                                        <?php foreach ($diasSemana as $dia):
                                            $dispo   = $disponibilidadActual[$dia] ?? null;
                                            $activo  = $dispo['activo'] ?? false;
                                            $checked = $activo ? 'checked' : '';
                                            $inicio  = $dispo['inicio'] ?? '';
                                            $fin     = $dispo['fin'] ?? '';
                                        ?>
                                            <div class="day-row">
                                                <div class="day-name"><?= $dia ?></div>
                                                <div class="form-switch">
                                                    <input type="checkbox"
                                                        class="form-check-input toggle-dia"
                                                        data-dia="<?= $dia ?>"
                                                        <?= $checked ?>>
                                                </div>
                                                <div class="time-group <?= $checked ? '' : 'disabled' ?>">
                                                    <input type="time" class="hora-inicio" value="<?= $inicio ?>">
                                                    <span>–</span>
                                                    <input type="time" class="hora-fin" value="<?= $fin ?>">
                                                    <button type="button" class="copy-btn" title="Copiar horario a todos">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>

                                        <div class="text-end mt-3">
                                            <button type="submit" class="btn btn-gradient px-4 py-2">
                                                <i class="fas fa-save me-2"></i> Guardar cambios
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <footer class="mt-4 text-center text-muted small">
                © <?= date('Y') ?> Jaguata — Panel del Paseador
            </footer>
        </div>
    </main>

    <!-- Alertita flotante -->
    <div id="alerta" class="alert" role="alert"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar en mobile
        document.getElementById('toggleSidebar')?.addEventListener('click', function() {
            document.getElementById('sidebar')?.classList.toggle('sidebar-open');
        });

        const alerta = document.getElementById('alerta');
        const formDisp = document.getElementById('formDisponibilidad');
        const formCuenta = document.getElementById('formCuenta');
        const PASEADOR_ID = <?= (int)$usuarioId ?>;

        function mostrarAlerta(ok, mensaje) {
            alerta.className = 'alert ' + (ok ? 'alert-success' : 'alert-danger');
            alerta.innerHTML = `<i class="fas fa-${ok ? 'check-circle' : 'exclamation-triangle'} me-2"></i>${mensaje}`;
            alerta.style.display = 'block';
            setTimeout(() => alerta.style.display = 'none', 2500);
        }

        // ====== DISPONIBILIDAD ======
        // Habilitar / deshabilitar filas según checkbox
        document.querySelectorAll('.toggle-dia').forEach(toggle => {
            toggle.addEventListener('change', e => {
                const grupo = e.target.closest('.day-row').querySelector('.time-group');
                grupo.classList.toggle('disabled', !e.target.checked);
            });
        });

        // Copiar horario de una fila a todos los días activos
        document.querySelectorAll('.copy-btn').forEach(btn => {
            btn.addEventListener('click', e => {
                const row = e.target.closest('.day-row');
                const inicio = row.querySelector('.hora-inicio').value;
                const fin = row.querySelector('.hora-fin').value;

                if (!inicio || !fin) {
                    alert("Completá los horarios antes de copiar.");
                    return;
                }

                document.querySelectorAll('.day-row').forEach(r => {
                    const activo = r.querySelector('.toggle-dia').checked;
                    if (activo) {
                        r.querySelector('.hora-inicio').value = inicio;
                        r.querySelector('.hora-fin').value = fin;
                    }
                });
            });
        });

        // Guardar disponibilidad vía API
        formDisp.addEventListener('submit', async (e) => {
            e.preventDefault();

            const disponibilidad = [];

            document.querySelectorAll('.day-row').forEach(row => {
                const dia = row.querySelector('.toggle-dia').dataset.dia;
                const activo = row.querySelector('.toggle-dia').checked;
                const inicio = row.querySelector('.hora-inicio').value;
                const fin = row.querySelector('.hora-fin').value;

                if (activo && inicio && fin) {
                    disponibilidad.push({
                        dia,
                        inicio,
                        fin,
                        activo: 1
                    });
                }
            });

            try {
                const resp = await fetch('<?= BASE_URL ?>/public/api/paseador/guardarDisponibilidad.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        paseador_id: PASEADOR_ID,
                        disponibilidad
                    })
                });

                const raw = await resp.text();
                let data;
                try {
                    data = JSON.parse(raw);
                } catch (e) {
                    data = {
                        ok: false,
                        mensaje: 'Respuesta inválida del servidor'
                    };
                }

                mostrarAlerta(!!data.ok, data.mensaje || 'Error al guardar la disponibilidad.');

            } catch (err) {
                console.error(err);
                mostrarAlerta(false, 'Error al guardar la disponibilidad.');
            }
        });

        // ====== CUENTA PARA TRANSFERENCIAS ======
        formCuenta.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(formCuenta);
            const payload = {
                banco: formData.get('banco') || '',
                alias: formData.get('alias') || '',
                cuenta: formData.get('cuenta') || ''
            };

            try {
                const resp = await fetch('<?= BASE_URL ?>/public/api/paseador/guardarCuentaPago.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await resp.json().catch(() => ({
                    ok: false,
                    mensaje: 'Respuesta inválida del servidor'
                }));

                mostrarAlerta(!!data.ok, data.mensaje || 'Error al guardar los datos de cuenta.');

            } catch (err) {
                console.error(err);
                mostrarAlerta(false, 'Error al guardar los datos de cuenta.');
            }
        });
    </script>
</body>

</html>