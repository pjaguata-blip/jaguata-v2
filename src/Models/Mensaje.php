<?php

namespace Jaguata\Models;

use Jaguata\Services\DatabaseService;
use PDO;

class Mensaje
{
    private PDO $db;
    protected string $table = 'mensajes';

    public function __construct()
    {
        $this->db = DatabaseService::getInstance()->getConnection();
    }

    /** Obtener todos los mensajes de un paseo */
    public function getMensajes(int $paseoId): array
    {
        $stmt = $this->db->prepare("
            SELECT m.*, u.nombre AS remitente_nombre
            FROM {$this->table} m
            JOIN usuarios u ON u.usu_id = m.remitente_id
            WHERE paseo_id = :id
            ORDER BY m.created_at ASC
        ");
        $stmt->execute(['id' => $paseoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** Enviar mensaje nuevo */
    public function enviarMensaje(int $paseoId, int $remitenteId, int $destinatarioId, string $mensaje): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table}
            (paseo_id, remitente_id, destinatario_id, mensaje, leido, created_at)
            VALUES (:paseo_id, :remitente_id, :destinatario_id, :mensaje, 0, NOW())
        ");
        return $stmt->execute([
            'paseo_id' => $paseoId,
            'remitente_id' => $remitenteId,
            'destinatario_id' => $destinatarioId,
            'mensaje' => trim($mensaje),
        ]);
    }

    /** Marcar todos los mensajes del paseo como leídos por un usuario */
    public function marcarLeido(int $paseoId, int $usuarioId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET leido = 1
            WHERE paseo_id = :paseo_id AND destinatario_id = :usuario_id
        ");
        return $stmt->execute(['paseo_id' => $paseoId, 'usuario_id' => $usuarioId]);
    }

    /** Contar mensajes no leídos para el usuario */
    public function contarNoLeidos(int $usuarioId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM {$this->table}
            WHERE destinatario_id = :id AND leido = 0
        ");
        $stmt->execute(['id' => $usuarioId]);
        return (int) $stmt->fetchColumn();
    }
}
