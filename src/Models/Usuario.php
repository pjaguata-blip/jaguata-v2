<?php

namespace Jaguata\Models;

require_once __DIR__ . '/BaseModel.php';

class Usuario extends BaseModel
{
    protected string $table = 'usuarios';
    protected string $primaryKey = 'usu_id';

    /**
     * Buscar un usuario por email
     */
    public function getByEmail(string $email): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
        return $this->fetchOne($sql, ['email' => strtolower(trim($email))]) ?: null;
    }

    /**
     * Autenticar un usuario
     */
    public function authenticate(string $email, string $password): ?array
    {
        $usuario = $this->getByEmail($email);
        if (!$usuario) {
            return null;
        }

        if (!password_verify($password, $usuario['password'])) {
            return null;
        }

        if (password_needs_rehash($usuario['password'], PASSWORD_BCRYPT)) {
            $this->updateUsuario($usuario['usu_id'], ['password' => $password]);
        }

        return $usuario;
    }

    /**
     * Crear un usuario
     */
    public function createUsuario(array $data): int
    {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        return $this->create($data);
    }

    /**
     * Actualizar un usuario
     */
    public function updateUsuario(int $id, array $data): bool
    {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        return $this->update($id, $data);
    }

    /**
     * Eliminar un usuario
     */
    public function deleteUsuario(int $id): bool
    {
        return $this->delete($id);
    }

    /**
     * Obtener todos los usuarios (con límite opcional)
     */
    public function getAllUsuarios(?int $limite = null): array
    {
        $sql = "SELECT * FROM {$this->table}";
        if ($limite !== null) {
            $sql .= " LIMIT :limite";
            return $this->fetchAll($sql, ['limite' => $limite]);
        }
        return $this->fetchAll($sql);
    }

    /**
     * Buscar usuario por ID
     */
    public function getById(int $id): ?array
    {
        return $this->find($id) ?: null;
    }

    /**
     * Obtener puntos del usuario
     */
    public function getPuntos(int $id): int
    {
        $sql = "SELECT puntos FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        $result = $this->fetchOne($sql, ['id' => $id]);
        return $result ? (int)$result['puntos'] : 0;
    }

    /**
     * Sumar puntos al usuario
     */
    public function sumarPuntos(int $id, int $puntos): bool
    {
        $sql = "UPDATE {$this->table} SET puntos = puntos + :puntos WHERE {$this->primaryKey} = :id";
        return $this->db->executeQuery($sql, ['puntos' => $puntos, 'id' => $id]);
    }

    /**
     * Restar puntos al usuario
     */
    public function restarPuntos(int $id, int $puntos): bool
    {
        $sql = "UPDATE {$this->table} SET puntos = GREATEST(puntos - :puntos, 0) WHERE {$this->primaryKey} = :id";
        return $this->db->executeQuery($sql, ['puntos' => $puntos, 'id' => $id]);
    }
}
