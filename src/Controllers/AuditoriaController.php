<?php

namespace Jaguata\Controllers;

use Jaguata\Services\DatabaseService;
use PDO;
use PDOException;

class AuditoriaController
{
    private $db;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance()->getConnection();
    }

    public function index(): array
    {
        try {
            $sql = "SELECT id, usuario, accion, modulo, detalles, fecha
                    FROM auditoria
                    ORDER BY fecha DESC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('❌ Error AuditoriaController::index() => ' . $e->getMessage());
            return [];
        }
    }

    public function obtenerDatosExportacion(): array
    {
        try {
            $sql = "SELECT id, usuario, accion, modulo, detalles, fecha
                    FROM auditoria
                    ORDER BY fecha DESC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('❌ Error AuditoriaController::obtenerDatosExportacion() => ' . $e->getMessage());
            return [];
        }
    }

    public function registrar(string $usuario, string $accion, string $modulo, string $detalles = null): bool
    {
        try {
            $sql = "INSERT INTO auditoria (usuario, accion, modulo, detalles, fecha)
                    VALUES (:usuario, :accion, :modulo, :detalles, NOW())";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':usuario'  => $usuario,
                ':accion'   => $accion,
                ':modulo'   => $modulo,
                ':detalles' => $detalles
            ]);
        } catch (PDOException $e) {
            error_log('❌ Error AuditoriaController::registrar() => ' . $e->getMessage());
            return false;
        }
    }
}
