<?php

namespace Jaguata\Models;

require_once __DIR__ . '/BaseModel.php';

class MetodoPago extends BaseModel
{
    protected string $table = 'metodos_pago';
    protected string $primaryKey = 'metodo_id';

    /**
     * Obtener métodos de pago de un usuario
     */
    public function getByUsuario(int $usuId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE usu_id = :usu_id";
        return $this->fetchAll($sql, ['usu_id' => $usuId]);
    }

    /**
     * Establecer un método como predeterminado
     */
    public function setDefault(int $metodoId, int $usuId): bool
    {
        // Primero quitar el anterior default
        $sql1 = "UPDATE {$this->table} SET is_default = 0 WHERE usu_id = :usu_id";
        $this->db->executeQuery($sql1, ['usu_id' => $usuId]);

        // Ahora poner el nuevo
        $sql2 = "UPDATE {$this->table} SET is_default = 1 WHERE metodo_id = :metodo_id AND usu_id = :usu_id";
        return $this->db->executeQuery($sql2, [
            'metodo_id' => $metodoId,
            'usu_id'    => $usuId
        ]);
    }
}
