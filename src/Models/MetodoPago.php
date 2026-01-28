<?php

namespace Jaguata\Models;

require_once __DIR__ . '/BaseModel.php';

class MetodoPago extends BaseModel
{
    protected string $table = 'metodos_pago';
    protected string $primaryKey = 'metodo_id';

    public function getByUsuario(int $usuId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE usu_id = :usu_id";
        return $this->fetchAll($sql, ['usu_id' => $usuId]);
    }
    public function setDefault(int $metodoId, int $usuId): bool
    {
        $sql1 = "UPDATE {$this->table} SET is_default = 0 WHERE usu_id = :usu_id";
        $this->executeQuery($sql1, ['usu_id' => $usuId]);
        $sql2 = "UPDATE {$this->table} 
                 SET is_default = 1 
                 WHERE metodo_id = :metodo_id AND usu_id = :usu_id";
        return $this->executeQuery($sql2, [
            'metodo_id' => $metodoId,
            'usu_id'    => $usuId
        ]);
    }
}
