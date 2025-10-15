<?php

namespace Jaguata\Models;

use Jaguata\Services\DatabaseService;
use PDO;

class Mascota
{
    private $db;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance()->getConnection();
    }

    public function allByOwner($ownerId)
    {
        $stmt = $this->db->prepare("SELECT * FROM mascotas WHERE dueno_id = ?");
        $stmt->execute([$ownerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM mascotas WHERE mascota_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
        INSERT INTO mascotas (dueno_id, nombre, raza, peso_kg, tamano, edad_meses, observaciones)
        VALUES (:dueno_id, :nombre, :raza, :peso_kg, :tamano, :edad_meses, :observaciones)
    ");
        $stmt->execute([
            ':dueno_id'      => $data['dueno_id'],
            ':nombre'        => $data['nombre'],
            ':raza'          => $data['raza'],
            ':peso_kg'       => $data['peso_kg'],
            ':tamano'        => $data['tamano'],
            ':edad_meses'    => $data['edad_meses'],
            ':observaciones' => $data['observaciones']
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data)
    {
        $stmt = $this->db->prepare("
        UPDATE mascotas 
        SET nombre = :nombre, raza = :raza, peso_kg = :peso_kg, tamano = :tamano, edad_meses = :edad_meses,
            observaciones = :observaciones, updated_at = NOW()
        WHERE mascota_id = :id
    ");
        return $stmt->execute([
            ':nombre'        => $data['nombre'],
            ':raza'          => $data['raza'],
            ':peso_kg'       => $data['peso_kg'],
            ':tamano'        => $data['tamano'],
            ':edad_meses'    => $data['edad_meses'],
            ':observaciones' => $data['observaciones'],
            ':id'            => $id
        ]);
    }


    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM mascotas WHERE mascota_id = ?");
        return $stmt->execute([$id]);
    }
}
