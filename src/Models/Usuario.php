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
    private function baseSelectFields(): string
    {
        return "
            usu_id,
            nombre,
            email,
            pass,
            rol,
            estado,
            telefono,
            direccion,
            experiencia,
            departamento,
            ciudad,
            barrio,
            calle,
            zona,
            puntos,
            foto_perfil,
            foto_cedula_frente,
            foto_cedula_dorso,
            foto_selfie,
            certificado_antecedentes,
            acepto_terminos,
            fecha_aceptacion,
            ip_registro,
            latitud,
            longitud,
            sexo,
            tipo_documento,
            numero_documento,
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
        $sql = "
            SELECT {$this->baseSelectFields()}
            FROM {$this->table}
            WHERE LOWER(TRIM(email)) = LOWER(TRIM(:email))
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => trim($email)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    public function crearDesdeRegistro(array $data): array
    {
        try {
            // Normalizar básicos
            $data['nombre'] = trim((string)($data['nombre'] ?? ''));
            $data['email']  = strtolower(trim((string)($data['email'] ?? '')));
            $data['rol']    = (string)($data['rol'] ?? 'dueno');
            if (empty($data['pass']) && !empty($data['password'])) {
                $data['pass'] = $data['password'];
            }
            unset($data['password']); // para que NO intente insertarlo como columna

            // Hash de contraseña
            if (!empty($data['pass'])) {
                $data['pass'] = password_hash((string)$data['pass'], PASSWORD_BCRYPT);
            }

            // Estado por defecto (si no viene)
            if (empty($data['estado'])) {
                $data['estado'] = 'pendiente';
            }

            // Puntos por defecto
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
        $stmt->bindValue(':pass', $hashPassword, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function createUsuario(array $data): int
    {
        $nombre   = trim((string)($data['nombre'] ?? ''));
        $email    = strtolower(trim((string)($data['email'] ?? '')));
        $rolIn    = (string)($data['rol'] ?? 'dueno');
        $rol      = in_array($rolIn, ['dueno', 'paseador', 'admin'], true) ? $rolIn : 'dueno';
        $telefono = trim((string)($data['telefono'] ?? ''));
        $plain = (string)($data['password'] ?? ($data['pass'] ?? ''));
        $passwordHash = password_hash($plain, PASSWORD_DEFAULT);

        $sql = "
            INSERT INTO usuarios (
                nombre, email, pass, rol, estado, telefono,
                foto_cedula_frente, foto_cedula_dorso, foto_selfie, certificado_antecedentes,
                acepto_terminos, fecha_aceptacion, ip_registro,
                puntos
            ) VALUES (
                :nombre, :email, :pass, :rol, :estado, :telefono,
                :foto_cedula_frente, :foto_cedula_dorso, :foto_selfie, :certificado_antecedentes,
                :acepto_terminos, :fecha_aceptacion, :ip_registro,
                :puntos
            )
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombre' => $nombre,
            ':email'  => $email,
            ':pass'   => $passwordHash,
            ':rol'    => $rol,
            ':estado' => $data['estado'] ?? 'pendiente',
            ':telefono' => $telefono !== '' ? $telefono : null,

            ':foto_cedula_frente' => $data['foto_cedula_frente'] ?? null,
            ':foto_cedula_dorso'  => $data['foto_cedula_dorso'] ?? null,
            ':foto_selfie'        => $data['foto_selfie'] ?? null,
            ':certificado_antecedentes' => $data['certificado_antecedentes'] ?? null,

            ':acepto_terminos'  => (int)($data['acepto_terminos'] ?? 0),
            ':fecha_aceptacion' => $data['fecha_aceptacion'] ?? null,
            ':ip_registro'      => $data['ip_registro'] ?? null,

            ':puntos' => (int)($data['puntos'] ?? 0),
        ]);

        return (int)$this->db->lastInsertId();
    }
    public function getDb(): \PDO
{
    return $this->db;
}

}
