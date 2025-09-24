<?php
namespace Jaguata\Helpers;

require_once __DIR__ . '/../Config/Constantes.php';

class Funciones {
    public static function formatearPrecio(float $precio, bool $mostrarSimbolo = true): string {
        $formateado = number_format($precio, CURRENCY_DECIMALS, ',', '.');
        return $mostrarSimbolo ? CURRENCY_SYMBOL . ' ' . $formateado : $formateado;
    }

    public static function formatearFecha(string $fecha, string $formato = 'd/m/Y H:i'): string {
        $date = new \DateTime($fecha);
        return $date->format($formato);
    }

    public static function generarCodigo(int $longitud = 8): string {
        return bin2hex(random_bytes($longitud / 2));
    }

    public static function enviarEmail(string $para, string $asunto, string $mensaje, ?string $de = null): bool {
        $de = $de ?? SMTP_FROM_EMAIL;
        $headers = [
            'From: ' . SMTP_FROM_NAME . ' <' . $de . '>',
            'Reply-To: ' . $de,
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8'
        ];
        return mail($para, $asunto, $mensaje, implode("\r\n", $headers));
    }

    public static function generarAlerta(string $tipo, string $mensaje): string {
        $map = [
            'success' => 'alert-success',
            'error'   => 'alert-danger',
            'info'    => 'alert-info',
            'warning' => 'alert-warning',
        ];
        $clase = $map[$tipo] ?? 'alert-info';
        return "<div class='alert $clase'>$mensaje</div>";
    }
}
