<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/MascotaController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\MascotaController;

// Inicializar
AppConfig::init();

// Verificar autenticación
$auth = new AuthController();
$auth->checkRole('dueno');

// Controlador
$controller = new MascotaController();

// Obtener ID
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['error'] = "ID de mascota inválido";
    header("Location: MisMascotas.php");
    exit;
}

// Ejecutar eliminación
$controller->destroy($id);
