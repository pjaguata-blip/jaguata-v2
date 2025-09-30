<?php

namespace Jaguata\Models;

require_once __DIR__ . '/BaseModel.php';

class Paseador extends BaseModel
{
    protected string $table = 'paseadores';
    protected string $primaryKey = 'paseador_id';

    public function all(): array
    {
        $sql = "SELECT * FROM {$this->table}";
        return $this->fetchAll($sql);
    }

    public function getDisponibles(): array
    {
        $sql = "SELECT u.usu_id, u.nombre, u.email, u.telefono, u.zona, u.experiencia, u.perfil_foto
            FROM usuarios u
            WHERE u.rol = 'paseador'";
        return $this->fetchAll($sql);
    }


    public function search(string $query): array
    {
        $sql = "SELECT * FROM {$this->table}
            WHERE nombre LIKE :q OR zona LIKE :q";
        return $this->fetchAll($sql, ['q' => "%$query%"]);
    }

    public function setDisponible(int $id, bool $estado): bool
    {
        $sql = "UPDATE {$this->table}
                SET disponibilidad = :estado
                WHERE {$this->primaryKey} = :id";
        return $this->db->executeQuery($sql, [
            'id' => $id,
            'estado' => $estado ? 1 : 0
        ]);
    }

    public function updateCalificacion(int $id, float $nuevaCalificacion): bool
    {
        $sql = "UPDATE {$this->table}
                SET calificacion = :calificacion
                WHERE {$this->primaryKey} = :id";
        return $this->db->executeQuery($sql, [
            'id' => $id,
            'calificacion' => $nuevaCalificacion
        ]);
    }

    public function incrementarPaseos(int $id): bool
    {
        $sql = "UPDATE {$this->table}
                SET total_paseos = total_paseos + 1
                WHERE {$this->primaryKey} = :id";
        return $this->db->executeQuery($sql, ['id' => $id]);
    }
}
