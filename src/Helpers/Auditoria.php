<?php
declare(strict_types=1);

namespace Jaguata\Helpers;

require_once __DIR__ . '/../Config/AppConfig.php';
require_once __DIR__ . '/../Controllers/AuditoriaController.php';
require_once __DIR__ . '/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuditoriaController;

class Auditoria
{
    public static function log(
        string $accion,
        ?string $modulo = null,
        ?string $detalles = null,
        ?int $usuarioId = null,
        ?int $adminId = null
    ): void {
        try {
            AppConfig::init();

            if ($usuarioId === null && Session::isLoggedIn()) {
                $usuarioId = Session::getUsuarioId();
            }

            if ($adminId === null && Session::isLoggedIn() && Session::getUsuarioRol() === 'admin') {
                $adminId = Session::getUsuarioId();
            }

            $ctrl = new AuditoriaController();
            $ctrl->registrar($accion, $usuarioId, $modulo, $detalles, $adminId);
        } catch (\Throwable $e) {
            error_log('❌ Error Auditoria::log() => ' . $e->getMessage());
        }
    }
}
