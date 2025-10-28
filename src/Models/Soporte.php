<?php

declare(strict_types=1);

namespace Jaguata\Models;

use Jaguata\Models\BaseModel;
use PDO;

if (!class_exists(BaseModel::class)) {
    require_once __DIR__ . '/BaseModel.php';
}

class Soporte extends BaseModel
{
    protected string $table = 'tickets_soporte';
    protected string $primaryKey = 'ticket_id';

    public function __construct()
    {
        parent::__construct();
    }

    /** 🔹 Listar todos los tickets */
    public function getAll(?string $estado = null): array
    {
        $sql = "SELECT t.*, u.nombre AS usuario_nombre, u.email AS usuario_email
                FROM {$this->table} t
                JOIN usuarios u ON u.usu_id = t.usu_id";
        $params = [];

        if ($estado) {
            $sql .= " WHERE t.estado = :estado";
            $params['estado'] = $estado;
        }

        $sql .= " ORDER BY t.created_at DESC";

        $stmt = $this->db->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(":$k", $v);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** 🔹 Obtener un ticket específico */
    public function getById(int $id): ?array
    {
        $sql = "SELECT t.*, u.nombre AS usuario_nombre, u.email AS usuario_email
                FROM {$this->table} t
                JOIN usuarios u ON u.usu_id = t.usu_id
                WHERE t.{$this->primaryKey} = :id
                LIMIT 1";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** 🔹 Crear nuevo ticket (para usuarios) */
    public function crearTicket(int $usuId, string $asunto, string $mensaje): bool
    {
        $sql = "INSERT INTO {$this->table} (usu_id, asunto, mensaje)
                VALUES (:usu_id, :asunto, :mensaje)";
        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute([
            ':usu_id' => $usuId,
            ':asunto' => $asunto,
            ':mensaje' => $mensaje
        ]);
    }

    /** 🔹 Responder ticket (admin) */
    public function responder(int $ticketId, string $respuesta): bool
    {
        $sql = "UPDATE {$this->table}
                SET respuesta = :respuesta, estado = 'Resuelto', updated_at = NOW()
                WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute([
            ':respuesta' => $respuesta,
            ':id' => $ticketId
        ]);
    }

    /** 🔹 Cambiar estado manualmente */
    public function actualizarEstado(int $ticketId, string $estado): bool
    {
        $sql = "UPDATE {$this->table}
                SET estado = :estado, updated_at = NOW()
                WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute([
            ':estado' => $estado,
            ':id' => $ticketId
        ]);
    }

    /** 🔹 Eliminar ticket */
    public function eliminarTicket(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}
