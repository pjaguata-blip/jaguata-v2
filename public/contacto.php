
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Helpers\Validaciones;
use Jaguata\Helpers\Funciones;

AppConfig::init();

$error   = Session::getError();
$success = Session::getSuccess();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre  = Validaciones::sanitizarString($_POST['nombre'] ?? '');
    $email   = Validaciones::sanitizarString($_POST['email'] ?? '');
    $mensaje = Validaciones::sanitizarString($_POST['mensaje'] ?? '');
    $csrf    = $_POST['csrf_token'] ?? '';

    if (!Validaciones::verificarCSRF($csrf)) {
        $error = 'Token CSRF inválido';
    } elseif (empty($nombre) || empty($email) || empty($mensaje)) {
        $error = 'Todos los campos son obligatorios';
    } elseif (!Validaciones::validarEmail($email)) {
        $error = 'El correo no es válido';
    } else {
        if (Funciones::enviarEmail('contacto@jaguata.com', "Nuevo mensaje de $nombre", "<p>$mensaje</p>", $email)) {
            $success = 'Mensaje enviado con éxito';
        } else {
            $error = 'No se pudo enviar el mensaje';
        }
    }
}

include __DIR__ . '/../src/Templates/Header.php';
?>

<h2>Contacto</h2>
<?= $error   ? Funciones::generarAlerta('error', $error)     : '' ?>
<?= $success ? Funciones::generarAlerta('success', $success) : '' ?>

<form method="post">
  <input type="hidden" name="csrf_token" value="<?= Validaciones::generarCSRF(); ?>">
  <label>Nombre: <input type="text" name="nombre"></label><br>
  <label>Email: <input type="email" name="email"></label><br>
  <label>Mensaje:<br><textarea name="mensaje"></textarea></label><br>
  <button type="submit">Enviar</button>
</form>

<?php include __DIR__ . '/../src/Templates/Footer.php'; ?>
