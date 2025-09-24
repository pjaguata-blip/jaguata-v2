<?php
namespace Jaguata\Helpers;

class Validaciones {

    /**
     * Genera y devuelve un token CSRF seguro
     */
    public static function generarCSRF(): string {
        if (!isset($_SESSION['csrf_token'])) {
            // Usamos la función global random_bytes con \ para evitar conflictos de namespace
            $_SESSION['csrf_token'] = bin2hex(\random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verifica un token CSRF recibido contra el almacenado en sesión
     */
    public static function verificarCSRF(string $token): bool {
        return isset($_SESSION['csrf_token']) && \hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Valida un email
     */
    public static function validarEmail(string $email): array {
        if (!\filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'valido' => false,
                'mensaje' => 'El email no es válido'
            ];
        }
        return ['valido' => true];
    }

    /**
     * Valida una contraseña con reglas mínimas
     */
    public static function validarPassword(string $password): array {
        if (\strlen($password) < 6) {
            return [
                'valido' => false,
                'mensaje' => 'La contraseña debe tener al menos 6 caracteres'
            ];
        }
        return ['valido' => true];
    }

    /**
     * Limpia y sanitiza un string
     */
    public static function sanitizarString(string $string): string {
        return \htmlspecialchars(\trim($string), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Valida los datos de un usuario en el registro
     */
    public static function validarDatosUsuario(array $data): array {
        $errores = [];

        // Nombre
        if (empty($data['nombre']) || \strlen($data['nombre']) < 3) {
            $errores['nombre'] = 'El nombre debe tener al menos 3 caracteres';
        }

        // Email
        if (!\filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errores['email'] = 'El email no es válido';
        }

        // Password
        if (empty($data['pass']) || \strlen($data['pass']) < 8) {
            $errores['pass'] = 'La contraseña debe tener al menos 8 caracteres';
        }

        // Teléfono (ejemplo básico)
        if (empty($data['telefono']) || !\preg_match('/^[0-9]{3,15}$/', $data['telefono'])) {
            $errores['telefono'] = 'El teléfono no es válido';
        }

        // Rol
        if (empty($data['rol']) || !\in_array($data['rol'], ['dueno', 'paseador'])) {
            $errores['rol'] = 'Debes seleccionar un rol válido';
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }
}
