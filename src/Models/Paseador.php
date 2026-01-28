<?php

declare(strict_types=1);

namespace Jaguata\Models;

use PDO;
use PDOException;

class Paseador extends BaseModel
{
    protected string $table = 'paseadores';
    protected string $primaryKey = 'paseador_id';

    public function __construct()
    {
        parent::__construct();
    }

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
            $stmt = $this->db->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('❌ Error Paseador::getDisponibles => ' . $e->getMessage());
            return [];
        }
    }

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
 
    public function setDisponible(int $id, bool $estado): bool
    {
        // OJO: usá el nombre real de la columna: 'disponible' o 'disponibilidad'
        $sql = "UPDATE {$this->table}
                SET disponible = :estado
                WHERE {$this->primaryKey} = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':estado' => (int)$estado,
            ':id'     => $id,
        ]);
    }

    public function updateCalificacion(int $id, float $valor): bool
    {
        $sql = "UPDATE {$this->table}
                SET calificacion = :cal
                WHERE {$this->primaryKey} = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':cal' => $valor,
            ':id'  => $id,
        ]);
    }

    public function incrementarPaseos(int $id): bool
    {
        $sql = "UPDATE {$this->table}
                SET total_paseos = total_paseos + 1
                WHERE {$this->primaryKey} = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
        ]);
    }

    public function search(string $query): array
    {
        try {
            $sql = "SELECT *
                    FROM {$this->table}
                    WHERE nombre   LIKE :query
                       OR email    LIKE :query
                       OR telefono LIKE :query
                    ORDER BY calificacion DESC, total_paseos DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':query' => '%' . $query . '%']);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('Error en Paseador::search() => ' . $e->getMessage());
            return [];
        }
    }
    public function updatePrecioHora(int $paseadorId, float $precioHora): bool
    {
        $sql = "UPDATE paseadores SET precio_hora = :precio_hora WHERE paseador_id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':precio_hora' => $precioHora,
            ':id'          => $paseadorId
        ]);
    }
}
