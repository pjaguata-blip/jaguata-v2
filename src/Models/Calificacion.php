<?php
namespace Jaguata\Models;

use Jaguata\Services\DatabaseService;
use PDO;

class Calificacion {
    private $db;

    public function __construct() {
        $this->db = DatabaseService::getInstance()->getConnection();
    }

    public function all() {
        $stmt = $this->db->query("SELECT * FROM calificaciones ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM calificaciones WHERE cali_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO calificaciones 
            (paseo_id, rater_id, rated_id, calificacion, comentario, tipo)
            VALUES (:paseo_id, :rater_id, :rated_id, :calificacion, :comentario, :tipo)
        ");
        $stmt->execute([
            ':paseo_id' => $data['paseo_id'],
            ':rater_id' => $data['rater_id'],
            ':rated_id' => $data['rated_id'],
            ':calificacion' => $data['calificacion'],
            ':comentario' => $data['comentario'],
            ':tipo' => $data['tipo']
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE calificaciones 
            SET calificacion = :calificacion, comentario = :comentario, updated_at = NOW()
            WHERE cali_id = :id
        ");
        return $stmt->execute([
            ':calificacion' => $data['calificacion'],
            ':comentario' => $data['comentario'],
            ':id' => $id
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM calificaciones WHERE cali_id = ?");
        return $stmt->execute([$id]);
    }
}
