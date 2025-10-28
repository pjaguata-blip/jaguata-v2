<?php

namespace Jaguata\Controllers;

use Jaguata\Services\DatabaseService;
use PDO;
use Exception;

/**
 * Controlador de Auditoría del sistema
 * -------------------------------------
 * Recupera y registra los eventos del sistema (logins, ediciones, pagos, etc.)
 */
class AuditoriaController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance()->getConnection();
    }

    /**
     * 🔹 Obtiene los últimos registros de auditoría (hasta 200 por defecto)
     */
    public function index(int $limit = 200): array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM auditoria ORDER BY fecha DESC LIMIT :lim");
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error AuditoriaController::index -> " . $e->getMessage());
            return [];
        }
    }

    /**
     * 🔹 Registra un evento nuevo en la tabla auditoria
     * Ejemplo: $auditoria->registrar('admin', 'Eliminación', 'Usuarios', 'Eliminó el usuario #5');
     */
    public function registrar(string $usuario, string $accion, string $modulo, string $detalles = ''): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO auditoria (usuario, accion, modulo, detalles, fecha)
                VALUES (:usuario, :accion, :modulo, :detalles, NOW())
            ");
            $stmt->bindValue(':usuario', $usuario);
            $stmt->bindValue(':accion', $accion);
            $stmt->bindValue(':modulo', $modulo);
            $stmt->bindValue(':detalles', $detalles);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error AuditoriaController::registrar -> " . $e->getMessage());
            return false;
        }
    }
}
