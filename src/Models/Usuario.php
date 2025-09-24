<?php
namespace Jaguata\Models;

require_once __DIR__ . '/BaseModel.php';

class Usuario extends BaseModel {
    protected $table = 'usuarios';
    protected $primaryKey = 'usu_id';

    public function findByEmail($email) {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email";
        return $this->db->fetchOne($sql, ['email' => $email]);
    }

    public function findByRol($rol) {
        return $this->findAll(['rol' => $rol]);
    }

    public function authenticate($email, $password) {
        $user = $this->findByEmail($email);
        if ($user && password_verify($password, $user['pass'])) {
            return $user;
        }
        return false;
    }

    public function createUser($data) {
        // Hash the password before storing
        if (isset($data['pass'])) {
            $data['pass'] = password_hash($data['pass'], PASSWORD_DEFAULT);
        }
        
        // Set timestamps
        $data['created_at'] = date('Y-m-d H:i:s');
        
        return $this->create($data);
    }

    public function updateUser($id, $data) {
        // Hash password if provided
        if (isset($data['pass'])) {
            $data['pass'] = password_hash($data['pass'], PASSWORD_DEFAULT);
        }
        
        // Set updated timestamp
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return $this->update($id, $data);
    }

    public function getPaseadorProfile($userId) {
        $sql = "SELECT u.*, p.* FROM usuarios u 
                LEFT JOIN paseadores p ON u.usu_id = p.paseador_id 
                WHERE u.usu_id = :userId AND u.rol = 'paseador'";
        return $this->db->fetchOne($sql, ['userId' => $userId]);
    }

    public function getDuenoWithMascotas($userId) {
        $sql = "SELECT u.*, m.mascota_id, m.nombre as mascota_nombre, m.raza, m.tamano, m.edad 
                FROM usuarios u 
                LEFT JOIN mascotas m ON u.usu_id = m.dueno_id 
                WHERE u.usu_id = :userId AND u.rol = 'dueno'";
        return $this->db->fetchAll($sql, ['userId' => $userId]);
    }
}
