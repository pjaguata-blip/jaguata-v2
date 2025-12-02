<?php

namespace Jaguata\Helpers;

require_once __DIR__ . '/../Config/AppConfig.php';
require_once __DIR__ . '/../Controllers/AuditoriaController.php';
require_once __DIR__ . '/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuditoriaController;

/**
 * Helper para registrar eventos en auditoria_admin
 */
class Auditoria
{
    /**
     * Registra una acción en la tabla auditoria_admin.
     *
     * @param string      $accion    Ej: 'LOGIN', 'LOGOUT', 'REGISTRO'
     * @param string|null $modulo    Ej: 'Autenticación'
     * @param string|null $detalles  Texto libre
     * @param int|null    $usuarioId Si es null, toma el usuario de la sesión
     * @param int|null    $adminId   Si aplica (para acciones de admin); normalmente null aquí
     */
    public static function log(
        string $accion,
        ?string $modulo = null,
        ?string $detalles = null,
        ?int $usuarioId = null,
        ?int $adminId = null
    ): void {
        try {
            // Aseguramos que la app esté inicializada
            AppConfig::init();

            // Si no se pasa usuarioId, usamos el de la sesión (dueño, paseador o admin)
            if ($usuarioId === null && Session::isLoggedIn()) {
                $usuarioId = Session::getUsuarioId();
            }

            $ctrl = new AuditoriaController();
            $ctrl->registrar(
                $accion,
                $usuarioId,
                $modulo,
                $detalles,
                $adminId
            );
        } catch (\Throwable $e) {
            // No romper el flujo si falla la auditoría
            error_log('❌ Error Auditoria::log() => ' . $e->getMessage());
        }
    }
}
