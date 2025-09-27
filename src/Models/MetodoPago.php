<?php

namespace Jaguata\Models;

class MetodoPago extends BaseModel
{
    protected string $table = 'metodos';
    protected string $primaryKey = 'metodo_id';

    /**
     * Obtener todos los métodos de pago de un usuario
     */
    public function getByUsuario(int $usuId): array
    {
        return $this->findAll(['usu_id' => $usuId], 'created_at DESC');
    }

    /**
     * Obtener el método de pago por defecto de un usuario
     */
    public function getDefault(int $usuId): ?array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE usu_id = :usu_id AND is_default = 1 
                LIMIT 1";
        return $this->fetchOne($sql, ['usu_id' => $usuId]);
    }

    /**
     * Marcar un método de pago como predeterminado
     */
    public function setDefault(int $usuId, int $metodoId): bool
    {
        // Primero desmarcar todos los anteriores
        $this->updateAllByUser($usuId, ['is_default' => 0]);

        // Luego marcar el nuevo como predeterminado
        return $this->update($metodoId, ['is_default' => 1]);
    }

    /**
     * Helper: actualizar todos los métodos de un usuario
     */
    private function updateAllByUser(int $usuId, array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $fields = implode(', ', array_map(fn($f) => "$f = :$f", array_keys($data)));
        $sql = "UPDATE {$this->table} SET $fields WHERE usu_id = :usu_id";
        $data['usu_id'] = $usuId;

        return $this->db->executeQuery($sql, $data);
    }
}
