<?php

namespace Jaguata\Controllers;
require_once __DIR__ . '/../Services/DatabaseService.php'; // ✅ AÑADIR ESTO

use Jaguata\Services\DatabaseService;
use PDO;
use PDOException;

/**
 * Controlador de Auditoría
 * ------------------------
 * Mapea la tabla auditoria_admin a la vista de Admin y permite registrar eventos.
 *
 * Estructura esperada de la tabla auditoria_admin:
 *  - id          INT PK AUTO_INCREMENT
 *  - admin_id    INT NULL (admin que realiza la acción)
 *  - usuario_id  INT NULL (usuario afectado)
 *  - modulo      VARCHAR(100) NULL (Usuarios, Pagos, Paseos, etc.)
 *  - accion      VARCHAR(100) NOT NULL (CREAR, EDITAR, ELIMINAR, LOGIN, etc.)
 *  - detalles    TEXT NULL (info adicional)
 *  - fecha       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
 */
class AuditoriaController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance()->getConnection();
    }

    /**
     * 🔹 Listar auditoría para la vista de Admin
     * Devuelve:
     *  id, usuario, accion, modulo, detalles, fecha
     */
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
                ORDER BY a.fecha DESC
            ";

            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('❌ Error AuditoriaController::index() => ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 🔹 Datos para exportación (usa las mismas columnas que index)
     */
    public function obtenerDatosExportacion(): array
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
                ORDER BY a.fecha DESC
            ";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('❌ Error AuditoriaController::obtenerDatosExportacion() => ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 🔹 Registrar un evento de auditoría
     *
     * @param string   $accion    Texto corto, ej. 'CREAR', 'EDITAR', 'ELIMINAR', 'LOGIN'
     * @param int|null $usuarioId Usuario afectado (puede ser null en algunas acciones)
     * @param string|null $modulo Módulo lógico: 'Usuarios', 'Pagos', 'Paseos', 'Autenticación', etc.
     * @param string|null $detalles Detalles adicionales
     * @param int|null $adminId   Admin que realiza la acción (si aplica)
     */
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

            // admin_id
            if ($adminId === null) {
                $stmt->bindValue(':admin_id', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':admin_id', $adminId, PDO::PARAM_INT);
            }

            // usuario_id
            if ($usuarioId === null) {
                $stmt->bindValue(':usuario_id', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
            }

            // modulo
            if ($modulo === null || $modulo === '') {
                $stmt->bindValue(':modulo', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':modulo', $modulo, PDO::PARAM_STR);
            }

            // accion (obligatorio)
            $stmt->bindValue(':accion', $accion, PDO::PARAM_STR);

            // detalles
            if ($detalles === null || $detalles === '') {
                $stmt->bindValue(':detalles', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':detalles', $detalles, PDO::PARAM_STR);
            }

            $stmt->execute();
        } catch (PDOException $e) {
            error_log('❌ Error AuditoriaController::registrar() => ' . $e->getMessage());
        }
    }
}
