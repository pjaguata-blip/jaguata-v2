<?php

namespace Jaguata\Models;

use Jaguata\Services\DatabaseService;
use PDO;
use Exception;

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
        // ✅ Verificar paseador válido
        $checkP = $this->db->prepare("SELECT COUNT(*) FROM usuarios WHERE usu_id = :id AND rol = 'paseador'");
        $checkP->execute(['id' => $data['paseador_id']]);
        if ($checkP->fetchColumn() == 0) {
            throw new Exception("El paseador seleccionado no existe o no es válido.");
        }

        // ✅ Verificar mascota válida
        $checkM = $this->db->prepare("SELECT COUNT(*) FROM mascotas WHERE mascota_id = :id");
        $checkM->execute(['id' => $data['mascota_id']]);
        if ($checkM->fetchColumn() == 0) {
            throw new Exception("La mascota seleccionada no existe.");
        }

        $stmt = $this->db->prepare("
            INSERT INTO paseos (mascota_id, paseador_id, inicio, duracion, precio_total, estado)
            VALUES (:mascota_id, :paseador_id, :inicio, :duracion, :precio_total, 'Pendiente')
        ");
        $stmt->execute([
            ':mascota_id'   => $data['mascota_id'],
            ':paseador_id'  => $data['paseador_id'],
            ':inicio'       => $data['inicio'],
            ':duracion'     => $data['duracion'],
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
            ':inicio'       => $data['inicio'],
            ':duracion'     => $data['duracion'],
            ':precio_total' => $data['precio_total'],
            ':id'           => $id
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
            ':id'     => $id
        ]);
    }

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
}
