<?php

namespace Jaguata\Models;

require_once __DIR__ . '/BaseModel.php';

class Usuario extends BaseModel
{
    protected string $table = 'usuarios';
    protected string $primaryKey = 'usu_id';

    private function baseSelectFields(): string
    {
        return "usu_id, nombre, email, pass, rol, telefono, direccion, experiencia,
                departamento, ciudad, barrio, calle,
                zona, puntos,
                foto_perfil, perfil_foto,
                fecha_nacimiento,
                created_at, updated_at";
    }

    public function getByEmail(string $email): ?array
    {
        $sql = "SELECT " . $this->baseSelectFields() . "
                FROM {$this->table}
                WHERE email = :email
                LIMIT 1";
        return $this->fetchOne($sql, ['email' => strtolower(trim($email))]) ?: null;
    }

    public function authenticate(string $email, string $password): ?array
    {
        $usuario = $this->getByEmail($email);
        if (!$usuario) return null;
        if (!password_verify($password, $usuario['pass'])) return null;
        return $usuario;
    }

    public function createUsuario(array $data): int
    {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
            $data['pass'] = $data['password'];
            unset($data['password']);
        }
        $data['rol']    = $data['rol']    ?? 'dueno';
        $data['puntos'] = $data['puntos'] ?? 0;
        return $this->create($data);
    }

    public function updateUsuario(int $id, array $data): bool
    {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
            $data['pass'] = $data['password'];
            unset($data['password']);
        }

        $allowed = [
            'nombre',
            'email',
            'pass',
            'rol',
            'telefono',
            'direccion',
            'experiencia',
            'departamento',
            'ciudad',
            'barrio',
            'calle',
            'zona',
            'puntos',
            'foto_perfil',
            'perfil_foto',
            'fecha_nacimiento',
        ];
        $filteredData = array_intersect_key($data, array_flip($allowed));
        return $this->update($id, $filteredData);
    }

    public function deleteUsuario(int $id): bool
    {
        return $this->delete($id);
    }

    public function getAllUsuarios(?int $limite = null): array
    {
        $sql = "SELECT " . $this->baseSelectFields() . " FROM {$this->table}";
        if ($limite !== null) {
            $sql .= " LIMIT :limite";
            return $this->fetchAll($sql, ['limite' => $limite]);
        }
        return $this->fetchAll($sql);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT " . $this->baseSelectFields() . "
                FROM {$this->table}
                WHERE {$this->primaryKey} = :id
                LIMIT 1";
        return $this->fetchOne($sql, ['id' => $id]) ?: null;
    }

    public function getPuntos(int $id): int
    {
        $sql = "SELECT puntos FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        $r = $this->fetchOne($sql, ['id' => $id]);
        return $r ? (int)$r['puntos'] : 0;
    }

    public function sumarPuntos(int $id, int $puntos): bool
    {
        $sql = "UPDATE {$this->table}
                SET puntos = puntos + :puntos, updated_at = NOW()
                WHERE {$this->primaryKey} = :id";
        return $this->db->executeQuery($sql, ['puntos' => $puntos, 'id' => $id]);
    }

    public function restarPuntos(int $id, int $puntos): bool
    {
        $sql = "UPDATE {$this->table}
                SET puntos = GREATEST(puntos - :puntos, 0), updated_at = NOW()
                WHERE {$this->primaryKey} = :id";
        return $this->db->executeQuery($sql, ['puntos' => $puntos, 'id' => $id]);
    }
}
