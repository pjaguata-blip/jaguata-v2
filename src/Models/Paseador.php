<?php

declare(strict_types=1);

namespace Jaguata\Models;

use PDO;
use PDOException;

class Paseador extends BaseModel
{
    // 👇 Estos los usa BaseModel para find(), all(), etc.
    protected string $table = 'paseadores';
    protected string $primaryKey = 'paseador_id';

    public function __construct()
    {
        // Usa el constructor de BaseModel (que ya crea $this->db)
        parent::__construct();
    }

    /**
     * 🔹 Obtiene el listado de paseadores disponibles para el dueño.
     *
     * - Solo devuelve paseadores marcados como disponibles.
     * - Junta datos de la tabla usuarios: ciudad, barrio, teléfono, etc.
     * - $fecha es opcional. Por ahora se mantiene en la firma para no romper
     *   el código, pero la versión simple no filtra por fecha.
     */
    public function getDisponibles(?string $fecha = null): array
    {
        try {
            $sql = "
                SELECT 
                    p.paseador_id,
                    p.nombre,
                    p.experiencia,
                    p.zona,
                    p.descripcion,
                    p.foto_url,
                    p.precio_hora,
                    p.calificacion,
                    p.total_paseos,
                    u.ciudad,
                    u.barrio,
                    u.telefono,
                    u.nombre AS usuario_nombre
                FROM {$this->table} p
                INNER JOIN usuarios u 
                    ON u.usu_id = p.paseador_id
                WHERE p.disponible = 1
                  AND p.disponibilidad = 1
                  AND u.estado = 'aprobado'
            ";

            // Más adelante podés agregar filtro por $fecha si querés
            $stmt = $this->db->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('❌ Error Paseador::getDisponibles => ' . $e->getMessage());
            return [];
        }
    }

    /**
     * (Opcional) Obtener un paseador puntual por ID
     */
    public function getById(int $id): ?array
    {
        try {
            $sql = "
                SELECT 
                    p.*,
                    u.ciudad,
                    u.barrio,
                    u.telefono,
                    u.nombre AS usuario_nombre
                FROM {$this->table} p
                INNER JOIN usuarios u 
                    ON u.usu_id = p.paseador_id
                WHERE p.paseador_id = :id
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            error_log('❌ Error Paseador::getById => ' . $e->getMessage());
            return null;
        }
    }
}
