<?php

namespace Jaguata\Helpers;

use Jaguata\Models\Paseo;
use Jaguata\Models\Pago;
use Jaguata\Models\MetodoPago;

class PagoHelper
{
    /**
     * Validar datos de creación de pago
     */
    public static function validatePagoData(array $data): array
    {
        $errors = [];

        if (empty($data['paseo_id'])) {
            $errors[] = 'El ID de paseo es requerido';
        } else {
            $paseoModel = new Paseo();
            $paseo = $paseoModel->find($data['paseo_id']);
            if (!$paseo) {
                $errors[] = 'Paseo no encontrado';
            } elseif ($paseo['estado_pago'] !== 'pendiente') {
                $errors[] = 'El paseo ya ha sido pagado';
            } elseif ($paseo['estado'] !== 'confirmado') {
                $errors[] = 'El paseo debe estar confirmado para poder pagarlo';
            }
        }

        if (empty($data['metodo_id'])) {
            $errors[] = 'El método de pago es requerido';
        } else {
            $metodoModel = new MetodoPago();
            $metodo = $metodoModel->find($data['metodo_id']);
            if (!$metodo || $metodo['usu_id'] != $_SESSION['usuario_id']) {
                $errors[] = 'Método de pago no válido';
            }
        }

        return $errors;
    }

    /**
     * Validar datos de método de pago
     */
    public static function validateMetodoPagoData(array $data): array
    {
        $errors = [];

        if (empty($data['tipo'])) {
            $errors[] = 'El tipo de método de pago es requerido';
        } elseif (!in_array($data['tipo'], ['transferencia', 'efectivo'])) {
            $errors[] = 'El tipo debe ser transferencia o efectivo';
        }

        if (empty($data['alias'])) {
            $errors[] = 'El alias es requerido';
        } elseif (strlen($data['alias']) > 50) {
            $errors[] = 'El alias no puede tener más de 50 caracteres';
        }

        if ($data['tipo'] === 'transferencia' && empty($data['expiracion'])) {
            $errors[] = 'La fecha de expiración es requerida para transferencias';
        }

        if (!empty($data['expiracion']) && !preg_match('/^\d{2}\/\d{4}$/', $data['expiracion'])) {
            $errors[] = 'La fecha de expiración debe tener el formato MM/YYYY';
        }

        return $errors;
    }

    /**
     * Verificar permisos de pago
     */
    public static function checkPagoPermissions(int $pagoId, int $usuarioId, string $rol): ?array
    {
        $pagoModel = new Pago();
        $pago = $pagoModel->find($pagoId);

        if (!$pago) {
            return ['success' => false, 'error' => 'Pago no encontrado', 'code' => 'NOT_FOUND'];
        }

        if ($rol === 'dueno' && $pago['dueno_id'] != $usuarioId) {
            return ['success' => false, 'error' => 'No tienes permisos para este pago', 'code' => 'FORBIDDEN'];
        }

        if ($rol === 'paseador' && $pago['paseador_id'] != $usuarioId) {
            return ['success' => false, 'error' => 'No tienes permisos para este pago', 'code' => 'FORBIDDEN'];
        }

        return null;
    }

    /**
     * Verificar permisos de método de pago
     */
    public static function checkMetodoPagoPermissions(int $metodoId, int $usuarioId): ?array
    {
        $metodoModel = new MetodoPago();
        $metodo = $metodoModel->find($metodoId);

        if (!$metodo) {
            return ['success' => false, 'error' => 'Método de pago no encontrado', 'code' => 'NOT_FOUND'];
        }

        if ($metodo['usu_id'] != $usuarioId) {
            return ['success' => false, 'error' => 'No tienes permisos para este método de pago', 'code' => 'FORBIDDEN'];
        }

        return null;
    }
}
