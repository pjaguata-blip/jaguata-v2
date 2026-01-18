<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;

AppConfig::init();

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/* ========= CONFIG WHATSAPP ========= */
$WHATSAPP_NUM = '595986314958'; // 0986314958 -> 595986314958
$WA_BASE      = "https://wa.me/{$WHATSAPP_NUM}";

/* ========= Estado / rol / sidebar (igual Home) ========= */
$logueado = Session::isLoggedIn();

$rol = null;
if ($logueado) {
    $rolTmp = method_exists(Session::class, 'getUsuarioRolSeguro')
        ? Session::getUsuarioRolSeguro()
        : (Session::get('rol') ?? null);

    $rolTmp = strtolower(trim((string)$rolTmp));
    if (in_array($rolTmp, ['admin', 'dueno', 'paseador'], true)) {
        $rol = $rolTmp;
    }
}

$usuarioNombre = $logueado ? (Session::getUsuarioNombre() ?? 'Usuario') : '';
$usuarioEmail  = $logueado ? (Session::getUsuarioEmail() ?? '') : '';
$usuarioId     = $logueado ? (int)(Session::getUsuarioId() ?? 0) : 0;
$estadoUsuario = $logueado ? (Session::getUsuarioEstado() ?? '') : '';

$panelUrl = $rol ? (BASE_URL . "/features/{$rol}/Dashboard.php") : null;

/* ✅ VOLVER: siempre a /jaguata/sobre_nosotros.php (tu ruta real) */
$urlVolver = BASE_URL . '/sobre_nosotros.php';

/* Sidebar según rol */
$sidebarPath = null;
if ($rol === 'dueno')    $sidebarPath = __DIR__ . '/../src/Templates/SidebarDueno.php';
if ($rol === 'paseador') $sidebarPath = __DIR__ . '/../src/Templates/SidebarPaseador.php';
if ($rol === 'admin')    $sidebarPath = __DIR__ . '/../src/Templates/SidebarAdmin.php';

/* ========= helper para links ========= */
function wa_link(string $base, string $msg): string {
    return $base . '?text=' . rawurlencode($msg);
}

/* ========= Contexto: SOLO si está logueado ========= */
$contexto = '';
if ($logueado) {
    $contexto  = "*Datos del usuario*\n";
    if ($usuarioNombre !== '')  $contexto .= "- Nombre: {$usuarioNombre}\n";
    if ($rol)                   $contexto .= "- Rol: {$rol}\n";
    if ($usuarioId > 0)         $contexto .= "- ID: {$usuarioId}\n";
    if ($usuarioEmail !== '')   $contexto .= "- Email: {$usuarioEmail}\n";
    if ($estadoUsuario !== '')  $contexto .= "- Estado: {$estadoUsuario}\n";
    $contexto .= "\n";
}

/* ========= Mensajes BOT (sin emojis para evitar �) ========= */
$opciones = [
    [
        'icon'  => 'fa-solid fa-user-check',
        'title' => 'Consultar estado de mi cuenta',
        'desc'  => 'Aprobación, revisión, documentos o bloqueo.',
        'msg'   =>
            "Hola, soporte Jaguata.\n\n"
          . "Quiero consultar el *estado de mi cuenta*.\n\n"
          . $contexto
          . "*Consulta*\n"
          . "- En que estado esta mi cuenta?\n"
          . "- Que falta para aprobar?\n\n"
          . "Gracias."
    ],
    [
        'icon'  => 'fa-solid fa-id-card',
        'title' => 'Problemas con documentos / fotos',
        'desc'  => 'Cedula, selfie, antecedentes o foto perfil.',
        'msg'   =>
            "Hola, soporte Jaguata.\n\n"
          . "Tengo un problema con *documentos o fotos*.\n\n"
          . $contexto
          . "*Detalle*\n"
          . "- Documento: (cedula frente/dorso, selfie, antecedentes, perfil)\n"
          . "- Que pasa: (no sube / error / se queda cargando)\n"
          . "- Dispositivo: (PC / celular)\n\n"
          . "Puedo enviar captura si hace falta.\n"
          . "Gracias."
    ],
    [
        'icon'  => 'fa-solid fa-calendar-xmark',
        'title' => 'Problema con un paseo',
        'desc'  => 'Solicitud, confirmacion, cancelacion o finalizacion.',
        'msg'   =>
            "Hola, soporte Jaguata.\n\n"
          . "Tengo un problema con un *paseo*.\n\n"
          . $contexto
          . "*Datos del paseo*\n"
          . "- ID del paseo (si aparece):\n"
          . "- Que paso exactamente:\n"
          . "- Fecha y hora:\n"
          . "- Zona:\n\n"
          . "Gracias."
    ],
    [
        'icon'  => 'fa-solid fa-location-dot',
        'title' => 'No me aparecen paseos / solicitudes',
        'desc'  => 'Disponibilidad, zona o filtros.',
        'msg'   =>
            "Hola, soporte Jaguata.\n\n"
          . "No me aparecen paseos o solicitudes.\n\n"
          . $contexto
          . "*Detalle*\n"
          . "- Mi zona:\n"
          . "- Mi disponibilidad (dias/horarios):\n"
          . "- Desde cuando ocurre:\n"
          . "- Si soy paseador: tengo suscripcion activa? (si/no)\n\n"
          . "Gracias."
    ],
    [
        'icon'  => 'fa-solid fa-credit-card',
        'title' => 'Pagos / comprobantes / suscripcion',
        'desc'  => 'Cobro, pago, historial o comprobante.',
        'msg'   =>
            "Hola, soporte Jaguata.\n\n"
          . "Tengo una consulta sobre *pagos o comprobantes*.\n\n"
          . $contexto
          . "*Detalle*\n"
          . "- Que necesito: (pagar / ver comprobante / historial / suscripcion)\n"
          . "- ID del paseo o transaccion (si aparece):\n"
          . "- Que error o duda te sale:\n\n"
          . "Gracias."
    ],
    [
        'icon'  => 'fa-solid fa-star',
        'title' => 'Calificaciones / reputacion',
        'desc'  => 'No puedo calificar o no aparece.',
        'msg'   =>
            "Hola, soporte Jaguata.\n\n"
          . "Tengo un problema con *calificaciones o reputacion*.\n\n"
          . $contexto
          . "*Detalle*\n"
          . "- Que intentas hacer: (calificar / ver resenas / aparece mal)\n"
          . "- ID del paseo (si aplica):\n"
          . "- Que pasa exactamente:\n\n"
          . "Gracias."
    ],
    [
        'icon'  => 'fa-solid fa-user-gear',
        'title' => 'Perfil y datos personales',
        'desc'  => 'No guarda cambios, datos incorrectos, contrasena.',
        'msg'   =>
            "Hola, soporte Jaguata.\n\n"
          . "Necesito ayuda con *mi perfil o mis datos*.\n\n"
          . $contexto
          . "*Detalle*\n"
          . "- Que queres cambiar o corregir:\n"
          . "- Que te muestra o que error sale:\n\n"
          . "Gracias."
    ],
    [
        'icon'  => 'fa-solid fa-bug',
        'title' => 'Error tecnico (pantalla / sistema)',
        'desc'  => 'No carga, error 500, se queda en blanco, etc.',
        'msg'   =>
            "Hola, soporte Jaguata.\n\n"
          . "Tengo un *error tecnico*.\n\n"
          . $contexto
          . "*Detalle del error*\n"
          . "- Pantalla donde ocurre:\n"
          . "- Mensaje de error (si aparece):\n"
          . "- Pasos para reproducir:\n"
          . "- Dispositivo/navegador:\n\n"
          . "Puedo enviar captura si hace falta.\n"
          . "Gracias."
    ],
];

/* ========= Mensaje libre ========= */
$prefill = trim((string)($_GET['m'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mensaje = trim((string)($_POST['mensaje'] ?? ''));

    $baseMsg  = "Hola, soporte Jaguata.\n\n";
    $baseMsg .= "Quiero hacer una consulta.\n\n";
    if ($logueado) $baseMsg .= $contexto;
    $baseMsg .= "*Mensaje*\n" . ($mensaje !== '' ? $mensaje : 'Necesito ayuda con mi cuenta.') . "\n\n";
    $baseMsg .= "Gracias.";

    header('Location: ' . wa_link($WA_BASE, $baseMsg));
    exit;
}

$titulo = 'Soporte - Jaguata (WhatsApp)';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= h($titulo) ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <!-- tu theme -->
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <style>
        html, body { height: 100%; }
        body { background: var(--gris-fondo, #f4f6f9); }

        .layout{ display:flex; min-height:100vh; }

        main.main-content{
            margin-left: 260px;
            min-height: 100vh;
            padding: 24px;
            width: 100%;
        }
        body.no-sidebar main.main-content{ margin-left: 0 !important; }

        @media (max-width: 768px){
            main.main-content{
                margin-left: 0;
                width: 100% !important;
                padding: calc(16px + var(--topbar-h)) 16px 16px !important;
            }
        }

        .mini-card{
            border-radius: 18px;
            border: 0;
            box-shadow: 0 10px 22px rgba(0,0,0,.06);
            height: 100%;
        }

        .bot-card{
            cursor: pointer;
            transition: transform .08s ease, box-shadow .2s ease;
        }
        .bot-card:hover{
            transform: translateY(-1px);
            box-shadow: 0 14px 26px rgba(0,0,0,.08);
        }

        .bot-icon{
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display:flex;
            align-items:center;
            justify-content:center;
            background: rgba(60,98,85,.10);
            border: 1px solid rgba(60,98,85,.18);
            color: var(--verde-jaguata, #3c6255);
            flex: 0 0 44px;
        }

        .wa-badge{
            display:inline-flex;
            align-items:center;
            gap:.45rem;
            padding:.28rem .7rem;
            border-radius:999px;
            font-size:.82rem;
            border: 1px solid rgba(0,0,0,.08);
            background:#fff;
        }

        .wa-big{
            border-radius: 14px;
            font-weight: 800;
            padding: .85rem 1rem;
        }

        .form-control{
            border-radius: 14px;
        }
    </style>
</head>

<body class="<?= $rol ? '' : 'no-sidebar' ?>">

<div class="layout">
    <?php if ($rol && $sidebarPath && file_exists($sidebarPath)): ?>
        <?php include $sidebarPath; ?>
    <?php endif; ?>

    <main class="main-content">
        <div class="py-2">

            <div class="header-box header-dashboard mb-3">
                <div>
                    <h1 class="mb-1">
                        <?= $logueado ? ('Soporte — ' . h($usuarioNombre)) : 'Soporte Jaguata' ?>
                    </h1>
                    <p class="mb-0">
                        Elegi una opcion y abrimos WhatsApp con un mensaje ya preparado.
                    </p>
                   
                </div>

                <div class="d-flex gap-2 align-items-center">
                    <?php if ($panelUrl): ?>
                        <a href="<?= h($panelUrl) ?>" class="btn btn-outline-light border">
                            <i class="fa-solid fa-gauge-high me-2"></i>Panel
                        </a>
                    <?php endif; ?>

                    <a href="<?= h($urlVolver) ?>" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>
            </div>

            <div class="section-card mb-3">
                <div class="section-header">
                    <i class="fa-solid fa-headset me-2"></i>Soporte rapido
                </div>
                <div class="section-body">
                    <div class="row g-3">
                        <?php foreach ($opciones as $op): ?>
                            <?php $link = wa_link($WA_BASE, $op['msg']); ?>
                            <div class="col-md-6 col-lg-4">
                                <a class="text-decoration-none" href="<?= h($link) ?>" target="_blank" rel="noopener">
                                    <div class="card mini-card bot-card">
                                        <div class="card-body">
                                            <div class="d-flex gap-3 align-items-start">
                                                <div class="bot-icon">
                                                    <i class="<?= h($op['icon']) ?>"></i>
                                                </div>
                                                <div>
                                                    <h6 class="fw-bold mb-1 text-dark"><?= h($op['title']) ?></h6>
                                                    <div class="text-muted small"><?= h($op['desc']) ?></div>
                                                    <div class="mt-2 small fw-semibold text-success">
                                                        <i class="fa-brands fa-whatsapp me-1"></i>Abrir WhatsApp
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="alert alert-light border mt-3 mb-0">
                        <i class="fa-solid fa-circle-info me-2"></i>
                        Si tenes un error, envia una <b>captura</b> o el <b>ID del paseo</b> para ayudarte mas rapido.
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header">
                    <i class="fa-brands fa-whatsapp me-2"></i>Consulta libre
                </div>
                <div class="section-body">
                    <form method="post" class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                <i class="fa-solid fa-message me-2"></i>Mensaje
                            </label>
                            <textarea
                                class="form-control"
                                name="mensaje"
                                rows="5"
                                placeholder="Escribi tu consulta..."><?= h($prefill) ?></textarea>
                        </div>

                        <div class="col-12 d-flex flex-wrap gap-2">
                            <button class="btn btn-success wa-big" type="submit">
                                <i class="fa-brands fa-whatsapp me-2"></i>Abrir WhatsApp
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <footer class="mt-4 text-center text-muted small">
                © <?= date('Y'); ?> Jaguata — Soporte
            </footer>

        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
