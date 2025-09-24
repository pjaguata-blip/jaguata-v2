<?php

namespace Jaguata\Helpers;

class Validaciones
{
    /**
     * Genera y devuelve un token CSRF seguro
     */
    public static function generarCSRF(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(\random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verifica un token CSRF recibido contra el almacenado en sesión
     */
    public static function verificarCSRF(?string $token): bool
    {
        return isset($_SESSION['csrf_token'])
            && \is_string($token)
            && \hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Valida un email (bool simple)
     */
    public static function isEmail(string $email): bool
    {
        return \filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * (Mantén la versión anterior si la usas en otros lugares)
     */
    public static function validarEmail(string $email): array
    {
        return self::isEmail($email)
            ? ['valido' => true]
            : ['valido' => false, 'mensaje' => 'El email no es válido'];
    }

    /**
     * Valida una contraseña con reglas mínimas (unifica a 8)
     */
    public static function validarPassword(string $password, int $min = 8): array
    {
        if (\strlen($password) < $min) {
            return [
                'valido' => false,
                'mensaje' => "La contraseña debe tener al menos {$min} caracteres"
            ];
        }
        return ['valido' => true];
    }

    /**
     * Valida teléfono tipo 0981-123-456 o solo dígitos de 9 a 15
     */
    public static function validarTelefono(string $telefono): bool
    {
        $telefono = \trim($telefono);
        // 1) Formato con guiones 4-3-3 (p. ej., 0981-123-456)
        $conGuion = \preg_match('/^[0-9]{4}-[0-9]{3}-[0-9]{3}$/', $telefono) === 1;
        // 2) Solo dígitos (9 a 15)
        $soloDigitos = \preg_match('/^[0-9]{9,15}$/', $telefono) === 1;
        return $conGuion || $soloDigitos;
    }

    /**
     * Limpia y sanitiza un string (úsalo al imprimir)
     */
    public static function sanitizarString(string $string): string
    {
        return \htmlspecialchars(\trim($string), ENT_QUOTES, 'UTF-8');
    }

    /**
     * (Opcional) Validador compuesto para forms grandes
     */
    public static function validarDatosUsuario(array $data): array
    {
        $errores = [];

        if (empty($data['nombre']) || \strlen($data['nombre']) < 3) {
            $errores['nombre'] = 'El nombre debe tener al menos 3 caracteres';
        }

        if (!self::isEmail($data['email'] ?? '')) {
            $errores['email'] = 'El email no es válido';
        }

        if (empty($data['pass']) || \strlen($data['pass']) < 8) {
            $errores['pass'] = 'La contraseña debe tener al menos 8 caracteres';
        }

        if (!empty($data['telefono']) && !self::validarTelefono($data['telefono'])) {
            $errores['telefono'] = 'Teléfono inválido (ej: 0981-123-456 o solo dígitos)';
        }

        if (empty($data['rol']) || !\in_array($data['rol'], ['dueno', 'paseador'], true)) {
            $errores['rol'] = 'Debes seleccionar un rol válido';
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }
}
