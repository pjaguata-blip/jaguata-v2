<?php

namespace Jaguata\Models;

use Jaguata\Models\BaseModel;

class Usuario extends BaseModel
{
    protected string $table = 'usuario';
    protected string $primaryKey = 'usu_id';

    public function getByEmail(string $email)
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
        return $this->fetchOne($sql, ['email' => strtolower(trim($email))]);
    }

    public function authenticate(string $email, string $password)
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

    public function createUsuario(array $data)
    {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        return $this->create($data);
    }

    public function updateUsuario(int $id, array $data)
    {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        return $this->update($id, $data);
    }

    public function deleteUsuario(int $id)
    {
        return $this->delete($id);
    }

    public function getAllUsuarios(int $limite = null)
    {
        $sql = "SELECT * FROM {$this->table}";
        if ($limite) {
            $sql .= " LIMIT :limite";
            return $this->fetchAll($sql, ['limite' => $limite]);
        }
        return $this->fetchAll($sql);
    }

    public function getById(int $id)
    {
        return $this->find($id);
    }
}
