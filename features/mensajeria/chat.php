<?php
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';

use Jaguata\Helpers\Session;

$paseoId = $_GET['paseo_id'] ?? 0;
$destinatarioId = $_GET['destinatario_id'] ?? 0;
$mensajes = $mensajes ?? [];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat del Paseo #<?= htmlspecialchars($paseoId) ?> - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/emojionearea@3.4.2/dist/emojionearea.min.css">
    <style>
        :root {
            --verde-jaguata: #3c6255;
            --verde-claro: #20c997;
            --gris-oscuro: #1e1e2f;
            --gris-medio: #2b2b3d;
            --blanco: #ffffff;
            --texto: #e8e8e8;
        }

        body {
            background: var(--gris-oscuro);
            color: var(--texto);
            font-family: "Poppins", sans-serif;
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        header {
            background: linear-gradient(90deg, var(--verde-jaguata), var(--verde-claro));
            color: #fff;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.3);
        }

        header h1 {
            font-weight: 600;
            font-size: 1.5rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: .6rem;
        }

        header a {
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: .5rem 1.2rem;
            text-decoration: none;
            transition: .2s;
        }

        header a:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .chat-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 1.2rem;
            max-width: 900px;
            width: 100%;
            margin: auto;
        }

        .mensajes-box {
            flex: 1;
            background: var(--gris-medio);
            border-radius: 14px;
            padding: 1.2rem;
            overflow-y: auto;
            box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
        }

        .mensaje {
            display: inline-block;
            padding: .9rem 1rem;
            margin: .4rem 0;
            border-radius: 14px;
            max-width: 70%;
            line-height: 1.4;
            animation: fadeIn .25s ease-in;
            white-space: pre-wrap;
            word-break: break-word;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(6px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .mensaje.propio {
            background: var(--verde-claro);
            align-self: flex-end;
            color: #fff;
            border-bottom-right-radius: 0;
        }

        .mensaje.ajeno {
            background: var(--verde-jaguata);
            align-self: flex-start;
            color: #fff;
            border-bottom-left-radius: 0;
        }

        .mensaje strong {
            display: block;
            font-weight: 600;
            font-size: .9rem;
            opacity: .9;
        }

        .mensaje small {
            display: block;
            font-size: .75rem;
            color: rgba(255, 255, 255, 0.8);
            margin-top: .3rem;
            text-align: right;
        }

        form {
            display: flex;
            align-items: center;
            gap: .6rem;
            background: var(--gris-medio);
            border-radius: 12px;
            padding: .6rem .8rem;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.4);
        }

        textarea {
            flex: 1;
            border: none;
            background: #2f2f42;
            color: var(--texto);
            border-radius: 10px;
            padding: .8rem;
            font-size: 1rem;
            outline: none;
            resize: none;
        }

        textarea::placeholder {
            color: #aaa;
        }

        button {
            background: var(--verde-claro);
            color: var(--blanco);
            border: none;
            border-radius: 50%;
            width: 48px;
            height: 48px;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: .2s;
        }

        button:hover {
            background: #25d5a1;
            transform: scale(1.05);
        }

        footer {
            text-align: center;
            color: #aaa;
            font-size: .85rem;
            padding: .6rem;
            background: #181824;
        }
    </style>
</head>

<body>
    <header>
        <h1><i class="fas fa-comments"></i> Chat del Paseo #<?= htmlspecialchars($paseoId) ?></h1>
        <a href="/jaguata/features/admin/Dashboard.php"><i class="fas fa-arrow-left me-1"></i> Volver</a>
    </header>

    <section class="chat-wrapper">
        <div id="mensajesBox" class="mensajes-box">
            <?php foreach ($mensajes as $m): ?>
                <div class="mensaje <?= $m['remitente_id'] === Session::getUsuarioId() ? 'propio' : 'ajeno' ?>">
                    <strong><?= htmlspecialchars($m['remitente_nombre']) ?></strong>
                    <p><?= nl2br(htmlspecialchars($m['mensaje'])) ?></p>
                    <small><?= date('d/m H:i', strtotime($m['created_at'])) ?></small>
                </div>
            <?php endforeach; ?>
        </div>

        <form id="formMensaje" method="post" action="/jaguata/api/mensajes/enviar.php">
            <input type="hidden" name="paseo_id" value="<?= $paseoId ?>">
            <input type="hidden" name="destinatario_id" value="<?= $destinatarioId ?>">
            <textarea name="mensaje" id="mensajeInput" placeholder="Escribe un mensaje o agrega un emoji 😊..." required></textarea>
            <button type="submit"><i class="fas fa-paper-plane"></i></button>
        </form>
    </section>

    <footer>
        <small>© <?= date('Y') ?> Jaguata — Mensajería Interna</small>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/emojionearea@3.4.2/dist/emojionearea.min.js"></script>
    <script>
        // Inicializar selector de emojis
        $(document).ready(function() {
            $("#mensajeInput").emojioneArea({
                pickerPosition: "top",
                tonesStyle: "bullet",
                search: false,
                filtersPosition: "bottom",
                placeholder: "Escribe un mensaje o agrega un emoji 😊...",
                styles: {
                    backgroundColor: "#2f2f42",
                    color: "#fff"
                }
            });
        });

        const mensajesBox = document.getElementById('mensajesBox');
        const form = document.getElementById('formMensaje');
        const scrollBottom = () => mensajesBox.scrollTop = mensajesBox.scrollHeight;
        scrollBottom();

        form.addEventListener('submit', async e => {
            e.preventDefault();
            const textarea = document.querySelector('.emojionearea-editor');
            const mensaje = textarea.innerHTML.trim();
            if (!mensaje) return;
            const fd = new FormData(form);
            fd.set('mensaje', mensaje);
            const res = await fetch(form.action, {
                method: 'POST',
                body: fd
            });
            const data = await res.json();
            if (data.success) {
                const msg = document.createElement('div');
                msg.classList.add('mensaje', 'propio');
                msg.innerHTML = `<strong>Tú</strong><p>${mensaje}</p><small>${new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})}</small>`;
                mensajesBox.appendChild(msg);
                textarea.innerHTML = '';
                scrollBottom();
            } else alert(data.error || 'Error al enviar mensaje.');
        });

        // Actualización periódica
        setInterval(async () => {
            const res = await fetch(`/jaguata/api/mensajes/listar.php?paseo_id=<?= $paseoId ?>`);
            const msgs = await res.json();
            mensajesBox.innerHTML = '';
            msgs.forEach(m => {
                const div = document.createElement('div');
                div.classList.add('mensaje', m.remitente_id == <?= Session::getUsuarioId() ?> ? 'propio' : 'ajeno');
                div.innerHTML = `<strong>${m.remitente_nombre}</strong><p>${m.mensaje}</p><small>${new Date(m.created_at).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})}</small>`;
                mensajesBox.appendChild(div);
            });
            scrollBottom();
        }, 5000);
    </script>
</body>

</html>