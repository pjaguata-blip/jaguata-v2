<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/DisponibilidadController.php';
require_once __DIR__ . '/../../src/Models/Usuario.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\DisponibilidadController;
use Jaguata\Models\Usuario;
use Jaguata\Helpers\Session;

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
    return $ts ? date('d/m/Y', $ts) : htmlspecialchars($ymd);
}
function calcularEdad(?string $ymd): ?int
{
    if (!$ymd) return null;
    try {
        $nac = new DateTime($ymd);
        $hoy = new DateTime('today');
        return $nac->diff($hoy)->y;
    } catch (\Throwable $e) {
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

/* ===== Datos disponibilidad ===== */
$diasSemana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

$dispCtrl             = new DisponibilidadController();
$disponibilidadActual = $dispCtrl->getFormDataByPaseador($usuarioId); // ['Lunes' => ['inicio'=>..]]

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Paseador | Jaguata</title>

    <!-- CSS global Jaguata (sidebar + layout) -->
    <link href="<?= ASSETS_URL; ?>/css/jaguata-theme.css" rel="stylesheet">
    <!-- Bootstrap y FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* Solo estilos específicos de esta página (no tocamos .sidebar ni main) */

        .page-header-perfil {
            background: linear_gradient(90deg, var(--verde-claro), var(--verde-jaguata));
            background: linear-gradient(90deg, var(--verde-claro), var(--verde-jaguata));
            color: #fff;
            padding: 1.5rem 2rem;
            border-radius: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .page-header-perfil h2 {
            margin: 0;
            font-size: 1.6rem;
            font-weight: 600;
        }

        .card-perfil {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        img.rounded-circle {
            border: 4px solid var(--verde-jaguata);
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.12);
        }

        .badge-rol {
            background-color: #e6f4ea;
            color: var(--verde-jaguata);
            border-radius: 8px;
            font-size: 0.85rem;
            padding: 0.4em 0.6em;
        }

        /* ===== Disponibilidad ===== */
        .card-disponibilidad {
            border: none;
            border-radius: 18px;
            background: var(--blanco);
            box-shadow: 0 8px 25px rgba(0, 0, 0, .05);
            padding: 1.8rem 1.8rem 2.2rem;
            margin-top: 1.5rem;
        }

        .day-row {
            display: grid;
            grid-template-columns: 130px 100px 1fr;
            align-items: center;
            border-bottom: 1px solid #eaeaea;
            padding: 0.8rem 0;
            gap: 1rem;
        }

        .day-name {
            font-weight: 600;
            font-size: 1rem;
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
            padding: 0.4rem 0.6rem;
            font-size: 0.9rem;
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

        .btn-gradient {
            background: linear-gradient(90deg, var(--verde-jaguata), var(--verde-claro));
            border: none;
            color: #fff;
            font-weight: 500;
            border-radius: 10px;
            transition: 0.3s;
        }

        .btn-gradient:hover {
            transform: translateY(-1px);
            opacity: 0.95;
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
    <?php include __DIR__ . '/../../src/Templates/Navbar.php'; ?>

    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="layout">
        <?php include __DIR__ . '/../../src/Templates/SidebarPaseador.php'; ?>

        <!-- Contenido principal -->
        <main class="content">
            <div class="page-header-perfil">
                <h2><i class="fas fa-user me-2"></i> Mi Perfil - Paseador</h2>
                <div class="d-flex flex-wrap gap-2">
                    <a href="Dashboard.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                    <a href="EditarPerfil.php" class="btn btn-light btn-sm text-success">
                        <i class="fas fa-edit me-1"></i> Editar
                    </a>
                </div>
            </div>

            <div class="row g-3">
                <!-- Columna izquierda: Info básica -->
                <div class="col-lg-4">
                    <div class="card-perfil card h-100">
                        <div class="card-body text-center">
                            <img src="<?= htmlspecialchars($foto) ?>" alt="Foto de perfil"
                                class="rounded-circle mb-3"
                                style="width:160px;height:160px;object-fit:cover;">
                            <h4 class="mb-1"><?= h($usuario['nombre'] ?? null, 'Sin nombre') ?></h4>
                            <span class="badge badge-rol">Paseador</span>

                            <div class="mt-3 text-start small">
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
                        </div>
                    </div>
                </div>

                <!-- Columna derecha: zonas + experiencia + disponibilidad -->
                <div class="col-lg-8">
                    <div class="card-perfil card mb-3">
                        <div class="card-header bg-success text-white">
                            <i class="fa-solid fa-map-location-dot me-2"></i> Zonas de trabajo
                        </div>
                        <div class="card-body">
                            <?php if (empty($zonas)): ?>
                                <span class="text-muted">Sin zonas registradas.</span>
                            <?php else: ?>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($zonas as $z): ?>
                                        <span class="badge bg-success-subtle text-success-emphasis">
                                            <?= htmlspecialchars($z) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card-perfil card mb-3">
                        <div class="card-header bg-primary text-white">
                            <i class="fa-solid fa-briefcase me-2"></i> Experiencia
                        </div>
                        <div class="card-body">
                            <?php if (!empty($usuario['experiencia'])): ?>
                                <div class="text-muted" style="white-space: pre-wrap;">
                                    <?= htmlspecialchars($usuario['experiencia']) ?>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">No especificada.</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- ===== Disponibilidad semanal dentro del mismo Perfil ===== -->
                    <div class="card-disponibilidad">
                        <h5 class="mb-3">
                            <i class="fas fa-calendar-check me-2"></i> Disponibilidad semanal
                        </h5>
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

                            <div class="text-end mt-4">
                                <button type="submit" class="btn btn-gradient px-4 py-2">
                                    <i class="fas fa-save me-2"></i> Guardar cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <footer class="mt-4">
                © <?= date('Y') ?> Jaguata — Todos los derechos reservados.
            </footer>
        </main>
    </div>

    <!-- Alertita flotante -->
    <div id="alerta" class="alert" role="alert"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar en mobile
        const toggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar'); // id en SidebarPaseador.php

        if (toggle && sidebar) {
            toggle.addEventListener('click', () => {
                sidebar.classList.toggle('show');
            });
        }

        /* ===== Lógica de Disponibilidad ===== */
        const alerta = document.getElementById('alerta');
        const form = document.getElementById('formDisponibilidad');

        // 👇 ID del paseador inyectado desde PHP
        const PASEADOR_ID = <?= (int)$usuarioId ?>;

        document.querySelectorAll('.toggle-dia').forEach(toggle => {
            toggle.addEventListener('change', e => {
                const grupo = e.target.closest('.day-row').querySelector('.time-group');
                grupo.classList.toggle('disabled', !e.target.checked);
            });
        });

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

        form.addEventListener('submit', async (e) => {
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

            console.log('Enviando disponibilidad:', disponibilidad);

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
                console.log('Status:', resp.status);
                console.log('Respuesta cruda:', raw);

                let data;
                try {
                    data = JSON.parse(raw);
                } catch (e) {
                    data = {
                        ok: false,
                        mensaje: 'Respuesta inválida del servidor'
                    };
                }

                alerta.className = 'alert ' + (data.ok ? 'alert-success' : 'alert-danger');
                alerta.innerHTML = `<i class="fas fa-${data.ok ? 'check-circle' : 'exclamation-triangle'} me-2"></i>${data.mensaje || ''}`;
                alerta.style.display = 'block';
                setTimeout(() => alerta.style.display = 'none', 2500);

            } catch (err) {
                console.error(err);
                alerta.className = 'alert alert-danger';
                alerta.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Error al guardar la disponibilidad.';
                alerta.style.display = 'block';
                setTimeout(() => alerta.style.display = 'none', 2500);
            }
        });
    </script>

</body>

</html>