<?php

namespace Jaguata\Helpers;

require_once __DIR__ . '/../Config/AppConfig.php';
require_once __DIR__ . '/../Controllers/ConfiguracionController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\ConfiguracionController;

class Permisos
{
    private static ?array $permisosModuloRol = null;

    private static function cargar(): void
    {
        if (self::$permisosModuloRol !== null) {
            return; // ya cargado
        }

        AppConfig::init();
        $cfg = new ConfiguracionController();
        $all = $cfg->getAll();

        if (!empty($all['permisos_modulo_rol'])) {
            self::$permisosModuloRol = json_decode($all['permisos_modulo_rol'], true);
        } else {
            self::$permisosModuloRol = [];
        }
    }

    public static function puedeVer(string $rol, string $modulo): bool
    {
        self::cargar();

        $rol = strtolower($rol);
        return !empty(self::$permisosModuloRol[$modulo][$rol]);
    }
}
