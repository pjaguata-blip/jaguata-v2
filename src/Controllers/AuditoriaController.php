<?php
declare(strict_types=1);

namespace Jaguata\Controllers;

require_once __DIR__ . '/../Services/DatabaseService.php';

use Jaguata\Services\DatabaseService;
use PDO;
use PDOException;

class AuditoriaController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance()->getConnection();
    }

    public function index(): array
    {
        try {
            $sql = "
                SELECT 
                    a.id,
                    COALESCE(u.nombre, CONCAT('Usuario ID ', a.usuario_id)) AS usuario,
                    a.accion,
                    a.modulo,
                    a.detalles,
                    a.fecha
                FROM auditoria_admin a
                LEFT JOIN usuarios u ON u.usu_id = a.usuario_id
                ORDER BY a.fecha DESC, a.id DESC
            ";

            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('❌ Error AuditoriaController::index() => ' . $e->getMessage());
            return [];
        }
    }

    public function obtenerDatosExportacion(): array
    {
        return $this->index();
    }

    public function registrar(
        string $accion,
        ?int $usuarioId = null,
        ?string $modulo = null,
        ?string $detalles = null,
        ?int $adminId = null
    ): void {
        try {
            $sql = "
                INSERT INTO auditoria_admin (admin_id, usuario_id, modulo, accion, detalles)
                VALUES (:admin_id, :usuario_id, :modulo, :accion, :detalles)
            ";

            $stmt = $this->db->prepare($sql);

            $stmt->bindValue(':admin_id', $adminId, $adminId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':usuario_id', $usuarioId, $usuarioId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':modulo', $modulo, ($modulo === null || $modulo === '') ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':accion', $accion, PDO::PARAM_STR);
            $stmt->bindValue(':detalles', $detalles, ($detalles === null || $detalles === '') ? PDO::PARAM_NULL : PDO::PARAM_STR);

            $stmt->execute();
        } catch (PDOException $e) {
            error_log('❌ Error AuditoriaController::registrar() => ' . $e->getMessage());
        }
    }
}
