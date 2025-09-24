<?php
namespace Jaguata\Models;

use Jaguata\Services\DatabaseService;
use PDO;

class Notificacion {
    private $db;

    public function __construct() {
        $this->db = DatabaseService::getInstance()->getConnection();
    }

    /**
     * Buscar notificación por ID
     */
    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM notificaciones WHERE noti_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Crear una nueva notificación
     */
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO notificaciones (usu_id, tipo, titulo, mensaje, paseo_id, leido, created_at) 
            VALUES (:usu_id, :tipo, :titulo, :mensaje, :paseo_id, 0, NOW())
        ");
        $stmt->execute([
            ':usu_id'   => $data['usu_id'],
            ':tipo'     => $data['tipo'],
            ':titulo'   => $data['titulo'],
            ':mensaje'  => $data['mensaje'],
            ':paseo_id' => $data['paseo_id'] ?? null
        ]);
        return $this->db->lastInsertId();
    }

    /**
     * Actualizar notificación
     */
    public function update($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE notificaciones 
               SET titulo = :titulo, 
                   mensaje = :mensaje, 
                   updated_at = NOW()
             WHERE noti_id = :id
        ");
        return $stmt->execute([
            ':titulo' => $data['titulo'],
            ':mensaje' => $data['mensaje'],
            ':id' => $id
        ]);
    }

    /**
     * Eliminar notificación
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM notificaciones WHERE noti_id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Marcar notificación como leída
     */
    public function marcarComoLeida($id) {
        $stmt = $this->db->prepare("UPDATE notificaciones SET leido = 1 WHERE noti_id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Marcar todas como leídas para un usuario
     */
    public function marcarTodasComoLeidas($usuarioId) {
        $stmt = $this->db->prepare("UPDATE notificaciones SET leido = 1 WHERE usu_id = ?");
        return $stmt->execute([$usuarioId]);
    }

    /**
     * Listar notificaciones por usuario
     */
    public function allByUsuario($usuarioId, $limit = 20) {
        $stmt = $this->db->prepare("
            SELECT * FROM notificaciones 
            WHERE usu_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Contar notificaciones no leídas
     */
    public function contarNoLeidas($usuarioId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM notificaciones WHERE usu_id = ? AND leido = 0");
        $stmt->execute([$usuarioId]);
        return (int)$stmt->fetchColumn();
    }
}
