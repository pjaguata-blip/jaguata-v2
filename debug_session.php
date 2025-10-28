<?php
require_once __DIR__ . '/src/Helpers/Session.php';

use Jaguata\Helpers\Session;

Session::set('test', 'ok');
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<p>Usuario logueado: " . (Session::isLoggedIn() ? 'Sí' : 'No') . "</p>";
echo "<p>Rol detectado: " . Session::getUsuarioRol() . "</p>";
