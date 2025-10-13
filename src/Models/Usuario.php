<?php

namespace Jaguata\Models;

require_once __DIR__ . '/BaseModel.php';

class Usuario extends BaseModel
{
    protected string $table = 'usuarios';
    protected string $primaryKey = 'usu_id';

    /**
     * Campos base para SELECT (ojo: alias foto_perfil AS perfil_foto)
     */
    private function baseSelectFields(): string
    {
        return "
            usu_id,
            nombre,
            email,
            pass,
            rol,
            telefono,
            direccion,
            experiencia,
            departamento,
            ciudad,
            barrio,
            calle,
            zona,
            puntos,
            -- columna real + alias esperado por las vistas:
            foto_perfil AS perfil_foto,
            -- datos útiles para pagos/transferencia:
            banco_nombre,
            alias_cuenta,
            cuenta_numero,
            fecha_nacimiento,
            created_at,
            updated_at,
            descripcion
        ";
    }

    public function getByEmail(string $email): ?array
    {
        $sql = "SELECT " . $this->baseSelectFields() . "
                FROM {$this->table}
                WHERE email = :email
                LIMIT 1";
        return $this->fetchOne($sql, ['email' => strtolower(trim($email))]) ?: null;
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT " . $this->baseSelectFields() . "
                FROM {$this->table}
                WHERE {$this->primaryKey} = :id
                LIMIT 1";
        return $this->fetchOne($sql, ['id' => $id]) ?: null;
    }

    /**
     * Autenticación segura.
     * - Intenta password_verify (hash).
     * - Si falla y sigues migrando, puedes permitir fallback en texto plano (desaconsejado):
     *   descomenta el bloque indicado más abajo.
     */
    public function authenticate(string $email, string $password): ?array
    {
        $usuario = $this->getByEmail($email);
        if (!$usuario) return null;

        $hash = (string)($usuario['pass'] ?? '');
        $ok = password_verify($password, $hash);

        // Fallback opcional (si aún hay contraseñas en texto plano):
        // if (!$ok && hash_equals($hash, $password)) {
        //     $ok = true;
        // }

        if (!$ok) return null;

        return $usuario; // si tu BaseModel no filtra 'pass', haz unset aquí si quieres
    }

    /**
     * Crear usuario (escribe SIEMPRE en foto_perfil).
     * $data puede traer 'password' o 'pass'; si trae 'password' lo convierto a 'pass' hasheado.
     */
    public function createUsuario(array $data): int
    {
        // normalizar email
        if (isset($data['email'])) {
            $data['email'] = strtolower(trim($data['email']));
        }

        // password
        if (isset($data['password']) && $data['password'] !== '') {
            $data['pass'] = password_hash($data['password'], PASSWORD_BCRYPT);
            unset($data['password']);
        } elseif (isset($data['pass']) && $data['pass'] !== '') {
            // si te llega 'pass' en claro: hashealo
            $data['pass'] = password_hash($data['pass'], PASSWORD_BCRYPT);
        }

        // rol / puntos por defecto
        $data['rol']    = $data['rol']    ?? 'dueno';
        $data['puntos'] = $data['puntos'] ?? 0;

        // mapear perfil_foto -> foto_perfil (BD real)
        if (isset($data['perfil_foto']) && !isset($data['foto_perfil'])) {
            $data['foto_perfil'] = $data['perfil_foto'];
            unset($data['perfil_foto']);
        }

        return $this->create($data);
    }

    /**
     * Actualiza usuario (mapea perfil_foto -> foto_perfil).
     * Si trae 'password' o 'pass', los hashea.
     */
    public function updateUsuario(int $id, array $data): bool
    {
        // normalizar email
        if (isset($data['email'])) {
            $data['email'] = strtolower(trim($data['email']));
        }

        // password
        if (isset($data['password']) && $data['password'] !== '') {
            $data['pass'] = password_hash($data['password'], PASSWORD_BCRYPT);
            unset($data['password']);
        } elseif (isset($data['pass']) && $data['pass'] !== '') {
            $data['pass'] = password_hash($data['pass'], PASSWORD_BCRYPT);
        }

        // mapear perfil_foto -> foto_perfil
        if (isset($data['perfil_foto'])) {
            $data['foto_perfil'] = $data['perfil_foto'];
            unset($data['perfil_foto']);
        }

        // solo permitir estas columnas
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
            'foto_perfil', // BD real
            'fecha_nacimiento',
            'banco_nombre',
            'alias_cuenta',
            'cuenta_numero',
            'descripcion',
        ];
        $filteredData = array_intersect_key($data, array_flip($allowed));

        return $this->update($id, $filteredData);
    }

    public function deleteUsuario(int $id): bool
    {
        return $this->delete($id);
    }

    /**
     * OJO con LIMIT cuando PDO tiene emulación desactivada.
     * Aquí lo concateno seguro (int) para evitar problemas.
     */
    public function getAllUsuarios(?int $limite = null): array
    {
        $sql = "SELECT " . $this->baseSelectFields() . " FROM {$this->table}";
        if ($limite !== null) {
            $lim = max(1, (int)$limite);
            $sql .= " LIMIT {$lim}";
        }
        return $this->fetchAll($sql);
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
