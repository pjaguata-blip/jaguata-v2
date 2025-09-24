<?php
namespace Jaguata\Models;

use PDO;
use Jaguata\Services\DatabaseService;

class Paseador {
    private $db;

    public function __construct() {
        $this->db = DatabaseService::getInstance()->getConnection();
    }

    /**
     * Listar todos los paseadores
     */
    public function all(): array {
        $stmt = $this->db->query("SELECT * FROM paseadores ORDER BY nombre ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener paseadores disponibles (para solicitudes de paseo)
     */
    public function getDisponibles(): array {
        $stmt = $this->db->query("
            SELECT *
            FROM paseadores
            WHERE disponible = 1
            ORDER BY calificacion DESC, precio_hora ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar un paseador por ID
     */
    public function find(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM paseadores WHERE paseador_id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Crear un paseador
     */
    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO paseadores (nombre, email, telefono, experiencia, disponible, precio_hora, calificacion, total_paseos)
            VALUES (:nombre, :email, :telefono, :experiencia, :disponible, :precio_hora, :calificacion, :total_paseos)
        ");
        $stmt->execute([
            ':nombre'       => $data['nombre'],
            ':email'        => $data['email'],
            ':telefono'     => $data['telefono'],
            ':experiencia'  => $data['experiencia'],
            ':disponible'   => $data['disponible'] ?? 1,
            ':precio_hora'  => $data['precio_hora'] ?? 0,
            ':calificacion' => $data['calificacion'] ?? 0,
            ':total_paseos' => $data['total_paseos'] ?? 0
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Actualizar paseador
     */
    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare("
            UPDATE paseadores 
               SET nombre = :nombre,
                   telefono = :telefono,
                   experiencia = :experiencia,
                   disponible = :disponible,
                   precio_hora = :precio_hora,
                   calificacion = :calificacion,
                   total_paseos = :total_paseos
             WHERE paseador_id = :id
        ");
        return $stmt->execute([
            ':nombre'       => $data['nombre'],
            ':telefono'     => $data['telefono'],
            ':experiencia'  => $data['experiencia'],
            ':disponible'   => $data['disponible'] ?? 1,
            ':precio_hora'  => $data['precio_hora'] ?? 0,
            ':calificacion' => $data['calificacion'] ?? 0,
            ':total_paseos' => $data['total_paseos'] ?? 0,
            ':id'           => $id
        ]);
    }

    /**
     * Eliminar un paseador
     */
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM paseadores WHERE paseador_id = ?");
        return $stmt->execute([$id]);
    }
}
