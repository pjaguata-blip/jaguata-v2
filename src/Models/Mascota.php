<?php
namespace Jaguata\Models;

use Jaguata\Services\DatabaseService;
use PDO;

class Mascota {
    private $db;

    public function __construct() {
        $this->db = DatabaseService::getInstance()->getConnection();
    }

    public function allByOwner($ownerId) {
        $stmt = $this->db->prepare("SELECT * FROM mascotas WHERE dueno_id = ?");
        $stmt->execute([$ownerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM mascotas WHERE mascota_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO mascotas (dueno_id, nombre, raza, tamano, edad, observaciones)
            VALUES (:dueno_id, :nombre, :raza, :tamano, :edad, :observaciones)
        ");
        $stmt->execute([
            ':dueno_id' => $data['dueno_id'],
            ':nombre' => $data['nombre'],
            ':raza' => $data['raza'],
            ':tamano' => $data['tamano'],
            ':edad' => $data['edad'],
            ':observaciones' => $data['observaciones']
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE mascotas 
            SET nombre = :nombre, raza = :raza, tamano = :tamano, edad = :edad, observaciones = :observaciones, updated_at = NOW()
            WHERE mascota_id = :id
        ");
        return $stmt->execute([
            ':nombre' => $data['nombre'],
            ':raza' => $data['raza'],
            ':tamano' => $data['tamano'],
            ':edad' => $data['edad'],
            ':observaciones' => $data['observaciones'],
            ':id' => $id
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM mascotas WHERE mascota_id = ?");
        return $stmt->execute([$id]);
    }
}
