<?php

namespace Jaguata\Models;

use Jaguata\Services\DatabaseService;
use PDO;

class Paseo
{
    private $db;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance()->getConnection();
    }

    public function all()
    {
        $stmt = $this->db->query("SELECT * FROM paseos ORDER BY inicio DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM paseos WHERE paseo_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO paseos (mascota_id, paseador_id, inicio, duracion, precio_total)
            VALUES (:mascota_id, :paseador_id, :inicio, :duracion, :precio_total)
        ");
        $stmt->execute([
            ':mascota_id' => $data['mascota_id'],
            ':paseador_id' => $data['paseador_id'],
            ':inicio' => $data['inicio'],
            ':duracion' => $data['duracion'],
            ':precio_total' => $data['precio_total']
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data)
    {
        $stmt = $this->db->prepare("
            UPDATE paseos 
            SET inicio = :inicio, duracion = :duracion, precio_total = :precio_total, updated_at = NOW()
            WHERE paseo_id = :id
        ");
        return $stmt->execute([
            ':inicio' => $data['inicio'],
            ':duracion' => $data['duracion'],
            ':precio_total' => $data['precio_total'],
            ':id' => $id
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM paseos WHERE paseo_id = ?");
        return $stmt->execute([$id]);
    }

    public function cambiarEstado($id, $estado)
    {
        $stmt = $this->db->prepare("
            UPDATE paseos SET estado = :estado, updated_at = NOW() WHERE paseo_id = :id
        ");
        return $stmt->execute([
            ':estado' => $estado,
            ':id' => $id
        ]);
    }

    // ================================================
    // 🚀 Métodos agregados con JOINs
    // ================================================

    public function allWithRelations(): array
    {
        $sql = "SELECT p.*, 
                       u.nombre AS nombre_paseador,
                       m.nombre AS nombre_mascota
                FROM paseos p
                LEFT JOIN usuarios u ON u.usu_id = p.paseador_id
                LEFT JOIN mascotas m ON m.mascota_id = p.mascota_id
                ORDER BY p.inicio DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByDueno(int $duenoId): array
    {
        $sql = "SELECT p.*, 
                       u.nombre AS nombre_paseador,
                       m.nombre AS nombre_mascota
                FROM paseos p
                LEFT JOIN usuarios u ON u.usu_id = p.paseador_id
                LEFT JOIN mascotas m ON m.mascota_id = p.mascota_id
                WHERE m.dueno_id = :dueno_id
                ORDER BY p.inicio DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['dueno_id' => $duenoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 🚀 NUEVO: traer paseos asignados a un paseador
    public function findByPaseador(int $paseadorId): array
    {
        $sql = "SELECT p.*, 
                   d.nombre AS nombre_dueno,
                   m.nombre AS nombre_mascota
            FROM paseos p
            LEFT JOIN mascotas m ON m.mascota_id = p.mascota_id
            LEFT JOIN usuarios d ON d.usu_id = m.dueno_id
            WHERE p.paseador_id = :paseador_id
            ORDER BY p.inicio DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['paseador_id' => $paseadorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function findWithRelations(int $id): ?array
    {
        $sql = "SELECT p.*, 
                       u.nombre AS nombre_paseador,
                       m.nombre AS nombre_mascota
                FROM paseos p
                LEFT JOIN usuarios u ON u.usu_id = p.paseador_id
                LEFT JOIN mascotas m ON m.mascota_id = p.mascota_id
                WHERE p.paseo_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
