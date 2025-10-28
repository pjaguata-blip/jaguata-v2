<?php

namespace Jaguata\Controllers;

use Jaguata\Services\DatabaseService;
use PDO;
use Exception;

/**
 * Controlador de Configuración del Sistema
 * ----------------------------------------
 * Permite leer y actualizar los parámetros globales del sistema Jaguata.
 * (nombre, correo de soporte, modo mantenimiento, comisiones, tarifas, etc.)
 */
class ConfiguracionController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance()->getConnection();
    }

    /**
     * 🔹 Devuelve todas las configuraciones en forma de array asociativo.
     */
    public function getAll(): array
    {
        try {
            $stmt = $this->db->query("SELECT clave, valor FROM configuracion");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $config = [];
            foreach ($rows as $r) {
                $config[$r['clave']] = $r['valor'];
            }
            return $config;
        } catch (Exception $e) {
            error_log("Error ConfiguracionController::getAll -> " . $e->getMessage());
            return [];
        }
    }

    /**
     * 🔹 Obtiene una configuración específica por clave.
     */
    public function get(string $clave): ?string
    {
        try {
            $stmt = $this->db->prepare("SELECT valor FROM configuracion WHERE clave = :c LIMIT 1");
            $stmt->bindValue(':c', $clave);
            $stmt->execute();
            $r = $stmt->fetch(PDO::FETCH_ASSOC);
            return $r ? $r['valor'] : null;
        } catch (Exception $e) {
            error_log("Error ConfiguracionController::get -> " . $e->getMessage());
            return null;
        }
    }

    /**
     * 🔹 Guarda o actualiza una configuración
     * (si existe la clave, actualiza; si no, la crea).
     */
    public function set(string $clave, string $valor): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO configuracion (clave, valor)
                VALUES (:clave, :valor)
                ON DUPLICATE KEY UPDATE valor = :valor2
            ");
            $stmt->bindValue(':clave', $clave);
            $stmt->bindValue(':valor', $valor);
            $stmt->bindValue(':valor2', $valor);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error ConfiguracionController::set -> " . $e->getMessage());
            return false;
        }
    }

    /**
     * 🔹 Guarda múltiples configuraciones de una vez.
     */
    public function saveMany(array $data): bool
    {
        try {
            $this->db->beginTransaction();
            foreach ($data as $clave => $valor) {
                $this->set($clave, $valor);
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error ConfiguracionController::saveMany -> " . $e->getMessage());
            return false;
        }
    }
}
