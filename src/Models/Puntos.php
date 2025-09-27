<?php

namespace Jaguata\Models;

use PDO;
use Jaguata\Services\DatabaseService;

class Punto
{
    private $db;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance()->getConnection();
    }

    // Obtener todos los puntos de un usuario
    public function getByUsuario(int $usuarioId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM puntos WHERE usuario_id = ? ORDER BY fecha DESC");
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Calcular el total de puntos
    public function getTotal(int $usuarioId): int
    {
        $stmt = $this->db->prepare("SELECT SUM(puntos) as total FROM puntos WHERE usuario_id = ?");
        $stmt->execute([$usuarioId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    // Agregar puntos
    public function add(int $usuarioId, string $descripcion, int $puntos): int
    {
        $stmt = $this->db->prepare("INSERT INTO puntos (usuario_id, descripcion, puntos) VALUES (?, ?, ?)");
        $stmt->execute([$usuarioId, $descripcion, $puntos]);
        return (int) $this->db->lastInsertId();
    }
}
