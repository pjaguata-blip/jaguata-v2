<?php

namespace Jaguata\Models;

require_once __DIR__ . '/BaseModel.php';

class MetodoPago extends BaseModel
{
    protected string $table = 'metodos_pago';
    protected string $primaryKey = 'metodo_id';

    public function findByUsuario(int $usuarioId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE usu_id = :id ORDER BY is_default DESC, created_at DESC";
        return $this->db->fetchAll($sql, ['id' => $usuarioId]);
    }

    public function createMetodo(array $data): int
    {
        return $this->create($data);
    }

    public function updateMetodo(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteMetodo(int $id): bool
    {
        return $this->delete($id);
    }

    public function setDefault(int $usuarioId, int $metodoId): bool
    {
        $this->db->executeQuery("UPDATE {$this->table} SET is_default = 0 WHERE usu_id = :uid", ['uid' => $usuarioId]);
        return $this->db->executeQuery("UPDATE {$this->table} SET is_default = 1 WHERE metodo_id = :mid", ['mid' => $metodoId]);
    }
}
