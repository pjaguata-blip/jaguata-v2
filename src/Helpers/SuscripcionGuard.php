<?php
declare(strict_types=1);

namespace Jaguata\Helpers;

use Jaguata\Models\Suscripcion;

class SuscripcionGuard
{
    public static function requireActiva(int $paseadorId): void
    {
        $sub = new Suscripcion();

        if (!$sub->tieneActiva($paseadorId)) {
            Session::setError('Necesitás una suscripción activa (₲50.000/mes) para aceptar paseos.');
            header('Location: ' . BASE_URL . '/features/paseador/Suscripcion.php');
            exit;
        }
    }
}
