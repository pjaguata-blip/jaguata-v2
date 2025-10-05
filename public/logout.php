<?php
// C:\xampp\htdocs\jaguata\public\logout.php

require_once __DIR__ . '/../src/Config/AppConfig.php';
require_once __DIR__ . '/../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;

AppConfig::init(); // define BASE_URL y abre sesión

// (Opcional) Forzar método POST para más seguridad
// if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
//     header('Location: ' . BASE_URL . '/');
//     exit;
// }

Session::logout();

header('Location: ' . BASE_URL . '/login.php?mensaje=sesion_cerrada');
exit;
