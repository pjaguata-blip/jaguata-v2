<?php

declare(strict_types=1);

namespace Jaguata\Models;

require_once __DIR__ . '/BaseModel.php';

use PDO;
use PDOException;

class Usuario extends BaseModel
{
    protected string $table      = 'usuarios';
    protected string $primaryKey = 'usu_id';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Devuelve los campos base para los SELECT
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

    /**
     * Buscar usuario por email
     */
    public function getByEmail(string $email): ?array
    {
        $sql = "
            SELECT {$this->baseSelectFields()}
            FROM {$this->table}
            WHERE email = :email
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    /**
     * Crear usuario desde formulario de registro
     */
    public function crearDesdeRegistro(array $data): array
    {
        try {
            // Normalizar campos
            $data['nombre'] = trim($data['nombre'] ?? '');
            $data['email']  = strtolower(trim($data['email'] ?? ''));
            $data['rol']    = $data['rol'] ?? 'dueno';

            if (!empty($data['pass'])) {
                $data['pass'] = password_hash($data['pass'], PASSWORD_BCRYPT);
            }

            // Por defecto puntos 0
            if (!isset($data['puntos'])) {
                $data['puntos'] = 0;
            }

            // Mapear perfil_foto → foto_perfil si viene así del formulario
            if (isset($data['perfil_foto']) && !isset($data['foto_perfil'])) {
                $data['foto_perfil'] = $data['perfil_foto'];
                unset($data['perfil_foto']);
            }

            $nuevoId = $this->create($data);
            $usuario = $this->find($nuevoId);

            return [
                'success' => true,
                'usuario' => $usuario,
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'error'   => 'Error al registrar usuario: ' . $e->getMessage(),
            ];
        }
    }
    public function actualizarPassword(int $id, string $hashPassword): bool
    {
        $sql = "UPDATE usuarios SET pass = :pass WHERE usu_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':pass', $hashPassword);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
    public function createUsuario(array $data): int
    {
        // Aseguramos hash de contraseña
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);

        $sql = "
        INSERT INTO usuarios (
            nombre,
            email,
            pass,
            rol,
            telefono,
            estado,
            foto_cedula_frente,
            foto_cedula_dorso,
            foto_selfie,
            certificado_antecedentes,
            acepto_terminos,
            fecha_aceptacion,
            ip_registro
        ) VALUES (
            :nombre,
            :email,
            :pass,
            :rol,
            :telefono,
            :estado,
            :foto_cedula_frente,
            :foto_cedula_dorso,
            :foto_selfie,
            :certificado_antecedentes,
            :acepto_terminos,
            :fecha_aceptacion,
            :ip_registro
        )
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':nombre'                 => $data['nombre'],
            ':email'                  => $data['email'],
            ':pass'                   => $passwordHash,
            ':rol'                    => $data['rol'],
            ':telefono'               => $data['telefono'],
            ':estado'                 => $data['estado'] ?? 'pendiente',
            ':foto_cedula_frente'     => $data['foto_cedula_frente'] ?? null,
            ':foto_cedula_dorso'      => $data['foto_cedula_dorso'] ?? null,
            ':foto_selfie'            => $data['foto_selfie'] ?? null,
            ':certificado_antecedentes' => $data['certificado_antecedentes'] ?? null,
            ':acepto_terminos'        => $data['acepto_terminos'] ?? 0,
            ':fecha_aceptacion'       => $data['fecha_aceptacion'] ?? date('Y-m-d H:i:s'),
            ':ip_registro'            => $data['ip_registro'] ?? null,
        ]);

        return (int)$this->db->lastInsertId();
    }
}
