<?php

require_once __DIR__ . '/../src/Config/AppConfig.php';
require_once __DIR__ . '/../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;

AppConfig::init(); 

Session::logout();

header('Location: ' . BASE_URL . '/login.php?mensaje=sesion_cerrada');
exit;
