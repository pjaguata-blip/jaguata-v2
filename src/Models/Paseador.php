<?php

namespace Jaguata\Models;

require_once __DIR__ . '/BaseModel.php';

class Paseador extends BaseModel
{
    protected string $table = 'paseadores';
    protected string $primaryKey = 'paseador_id';

    /**
     * Obtener todos los paseadores disponibles
     */
    public function getDisponibles(): array
    {
        $sql = "SELECT p.*, u.nombre, u.email 
                FROM {$this->table} p
                JOIN usuarios u ON u.usu_id = p.paseador_id
                WHERE p.disponibilidad = 1";
        return $this->fetchAll($sql);
    }

    /**
     * Crear un paseador
     */
    public function createPaseador(array $data): int
    {
        return $this->create($data);
    }

    /**
     * Actualizar datos del paseador
     */
    public function updatePaseador(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    /**
     * Obtener un paseador por ID
     */
    public function getById(int $id): ?array
    {
        return $this->find($id) ?: null;
    }

    /**
     * Sumar paseo + calificación
     */
    public function registrarPaseo(int $id, float $nuevaCalificacion): bool
    {
        $sql = "UPDATE {$this->table}
                SET total_paseos = total_paseos + 1,
                    calificacion = (calificacion + :calificacion) / 2
                WHERE {$this->primaryKey} = :id";
        return $this->db->executeQuery($sql, [
            'id' => $id,
            'calificacion' => $nuevaCalificacion
        ]);
    }
}
