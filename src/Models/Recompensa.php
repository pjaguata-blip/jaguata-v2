<?php
declare(strict_types=1);

namespace Jaguata\Models;

use Jaguata\Services\DatabaseService;

class Recompensa
{
    private DatabaseService $db;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance();
    }

    /**
     * ✅ Trae recompensas activas.
     * Soporta:
     * - columna 'activo' (preferida)
     * - columna 'activa' (fallback si existiera)
     */
    public function getActivas(): array
    {
        // Preferimos 'activo'
        try {
            return $this->db->fetchAll("
                SELECT recompensa_id, titulo, descripcion, costo_puntos, activo, created_at
                FROM recompensas
                WHERE activo = 1
                ORDER BY costo_puntos ASC
            ");
        } catch (\Throwable $e) {
            // Fallback: si el entorno usa 'activa'
            return $this->db->fetchAll("
                SELECT recompensa_id, titulo, descripcion, costo_puntos, activa, created_at
                FROM recompensas
                WHERE activa = 1
                ORDER BY costo_puntos ASC
            ");
        }
    }

    public function getById(int $id): ?array
    {
        // Preferimos 'activo'
        try {
            return $this->db->fetchOne("
                SELECT recompensa_id, titulo, descripcion, costo_puntos, activo, created_at
                FROM recompensas
                WHERE recompensa_id = :id
                LIMIT 1
            ", [':id' => $id]);
        } catch (\Throwable $e) {
            return $this->db->fetchOne("
                SELECT recompensa_id, titulo, descripcion, costo_puntos, activa, created_at
                FROM recompensas
                WHERE recompensa_id = :id
                LIMIT 1
            ", [':id' => $id]);
        }
    }
}
