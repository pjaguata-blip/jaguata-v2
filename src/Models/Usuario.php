<?php

namespace Jaguata\Models;

require_once __DIR__ . '/BaseModel.php';

use Jaguata\Services\DatabaseService;
use PDO;
use PDOException;

/**
 * Modelo de Usuario
 * -----------------
 * Gestiona los datos del usuario: autenticación, CRUD, puntos, etc.
 */
class Usuario extends BaseModel
{
    protected string $table = 'usuarios';
    protected string $primaryKey = 'usu_id';

    public function __construct()
    {
        parent::__construct(); // inicializa $this->db = DatabaseService::getInstance()
    }

    /**
     * Campos base para consultas SELECT.
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
            foto_perfil AS perfil_foto,
            banco_nombre,
            alias_cuenta,
            cuenta_numero,
            fecha_nacimiento,
            created_at,
            updated_at,
            descripcion
        ";
    }

    // ==========================
    // 🔹 Métodos de lectura
    // ==========================

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
        $stmt = $this->db->getConnection()->prepare(
            "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getAllUsuarios(?int $limite = null): array
    {
        $sql = "SELECT " . $this->baseSelectFields() . " FROM {$this->table}";
        if ($limite !== null) {
            $lim = max(1, (int) $limite);
            $sql .= " LIMIT {$lim}";
        }
        return $this->fetchAll($sql);
    }

    // ==========================
    // 🔹 Autenticación
    // ==========================

    /**
     * Autenticación segura mediante password_verify.
     */
    public function authenticate(string $email, string $password): ?array
    {
        $usuario = $this->getByEmail($email);
        if (!$usuario) {
            return null;
        }

        $hash = (string) ($usuario['pass'] ?? '');
        $ok = password_verify($password, $hash);

        // ⚠️ Fallback opcional (solo si aún hay contraseñas sin hash)
        // if (!$ok && hash_equals($hash, $password)) {
        //     $ok = true;
        // }

        return $ok ? $usuario : null;
    }

    // ==========================
    // 🔹 CRUD de usuarios
    // ==========================

    public function createUsuario(array $data): int
    {
        try {
            // Normalizar email
            if (isset($data['email'])) {
                $data['email'] = strtolower(trim($data['email']));
            }

            // Hashear contraseña
            if (!empty($data['password'])) {
                $data['pass'] = password_hash($data['password'], PASSWORD_BCRYPT);
                unset($data['password']);
            } elseif (!empty($data['pass'])) {
                $data['pass'] = password_hash($data['pass'], PASSWORD_BCRYPT);
            }

            // Rol y puntos por defecto
            $data['rol']    = $data['rol']    ?? 'dueno';
            $data['puntos'] = $data['puntos'] ?? 0;

            // Mapear foto
            if (isset($data['perfil_foto']) && !isset($data['foto_perfil'])) {
                $data['foto_perfil'] = $data['perfil_foto'];
                unset($data['perfil_foto']);
            }

            return $this->create($data);
        } catch (PDOException $e) {
            error_log("Error crearUsuario: " . $e->getMessage());
            return 0;
        }
    }

    public function updateUsuario(int $id, array $data): bool
    {
        try {
            // Normalizar email
            if (isset($data['email'])) {
                $data['email'] = strtolower(trim($data['email']));
            }

            // Hashear contraseña si existe
            if (!empty($data['password'])) {
                $data['pass'] = password_hash($data['password'], PASSWORD_BCRYPT);
                unset($data['password']);
            } elseif (!empty($data['pass'])) {
                $data['pass'] = password_hash($data['pass'], PASSWORD_BCRYPT);
            }

            // Mapear foto
            if (isset($data['perfil_foto'])) {
                $data['foto_perfil'] = $data['perfil_foto'];
                unset($data['perfil_foto']);
            }

            // Campos permitidos
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
                'fecha_nacimiento',
                'banco_nombre',
                'alias_cuenta',
                'cuenta_numero',
                'descripcion',
            ];

            $filteredData = array_intersect_key($data, array_flip($allowed));

            return $this->update($id, $filteredData);
        } catch (PDOException $e) {
            error_log("Error updateUsuario: " . $e->getMessage());
            return false;
        }
    }

    public function deleteUsuario(int $id): bool
    {
        try {
            return $this->delete($id);
        } catch (PDOException $e) {
            error_log("Error deleteUsuario: " . $e->getMessage());
            return false;
        }
    }

    // ==========================
    // 🔹 Sistema de puntos
    // ==========================

    public function getPuntos(int $id): int
    {
        $sql = "SELECT puntos FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        $r = $this->fetchOne($sql, ['id' => $id]);
        return $r ? (int) $r['puntos'] : 0;
    }

    public function sumarPuntos(int $id, int $puntos): bool
    {
        try {
            $sql = "UPDATE {$this->table}
                SET puntos = puntos + :puntos, updated_at = NOW()
                WHERE {$this->primaryKey} = :id";
            $stmt = $this->db->getConnection()->prepare($sql);
            return $stmt->execute([':puntos' => $puntos, ':id' => $id]);
        } catch (PDOException $e) {
            error_log("Error sumarPuntos: " . $e->getMessage());
            return false;
        }
    }

    public function restarPuntos(int $id, int $puntos): bool
    {
        try {
            $sql = "UPDATE {$this->table}
                SET puntos = GREATEST(puntos - :puntos, 0), updated_at = NOW()
                WHERE {$this->primaryKey} = :id";
            $stmt = $this->db->getConnection()->prepare($sql);
            return $stmt->execute([':puntos' => $puntos, ':id' => $id]);
        } catch (PDOException $e) {
            error_log("Error restarPuntos: " . $e->getMessage());
            return false;
        }
    }
}
