<?php

namespace Jaguata\Controllers;

use Jaguata\Models\Paseador;
use Jaguata\Helpers\Session;
use Exception;

class PaseadorController
{
    private Paseador $paseadorModel;

    public function __construct()
    {
        $this->paseadorModel = new Paseador();
    }

    public function index()
    {
        return $this->paseadorModel->all();
    }

    public function disponibles()
    {
        return $this->paseadorModel->getDisponibles();
    }

    public function show($id)
    {
        return $this->paseadorModel->find((int)$id);
    }

    public function apiStore()
    {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }

        $data = [
            'nombre'       => $_POST['nombre'] ?? '',
            'email'        => $_POST['email'] ?? '',
            'telefono'     => $_POST['telefono'] ?? '',
            'experiencia'  => $_POST['experiencia'] ?? 0,
            'disponible'   => $_POST['disponible'] ?? 1,
            'precio_hora'  => $_POST['precio_hora'] ?? 0,
            'calificacion' => $_POST['calificacion'] ?? 0,
            'total_paseos' => $_POST['total_paseos'] ?? 0,
        ];

        try {
            $id = $this->paseadorModel->create($data);
            return ['success' => true, 'id' => $id];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function apiUpdate($id)
    {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }

        $data = [
            'nombre'       => $_POST['nombre'] ?? '',
            'telefono'     => $_POST['telefono'] ?? '',
            'experiencia'  => $_POST['experiencia'] ?? 0,
            'disponible'   => $_POST['disponible'] ?? 1,
            'precio_hora'  => $_POST['precio_hora'] ?? 0,
            'calificacion' => $_POST['calificacion'] ?? 0,
            'total_paseos' => $_POST['total_paseos'] ?? 0,
        ];

        return $this->paseadorModel->update((int)$id, $data)
            ? ['success' => true]
            : ['error' => 'No se pudo actualizar'];
    }

    public function apiDelete($id)
    {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }

        return $this->paseadorModel->delete((int)$id)
            ? ['success' => true]
            : ['error' => 'No se pudo eliminar'];
    }

    public function apiSetDisponible($id)
    {
        return $this->paseadorModel->setDisponible((int)$id, (bool)($_POST['disponible'] ?? true));
    }

    public function apiUpdateCalificacion($id)
    {
        return $this->paseadorModel->updateCalificacion((int)$id, (float)($_POST['calificacion'] ?? 0));
    }

    public function apiIncrementarPaseos($id)
    {
        return $this->paseadorModel->incrementarPaseos((int)$id);
    }

    public function buscar(string $query = '')
    {
        return empty($query)
            ? $this->paseadorModel->all()
            : $this->paseadorModel->search($query);
    }
    public function obtenerDisponibilidad(int $paseadorId): array
    {
        try {
            $pdo = $this->db ?? \Jaguata\Config\AppConfig::db();
            $sql = "SELECT dias, hora_inicio, hora_fin 
                FROM paseador_disponibilidad 
                WHERE paseador_id = :id LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $paseadorId]);
            $data = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$data) {
                return ['dias' => [], 'hora_inicio' => '08:00', 'hora_fin' => '18:00'];
            }

            // Los días se guardan como JSON
            $data['dias'] = json_decode($data['dias'], true) ?: [];
            return $data;
        } catch (\Throwable $e) {
            error_log("Error obtenerDisponibilidad: " . $e->getMessage());
            return ['dias' => [], 'hora_inicio' => '08:00', 'hora_fin' => '18:00'];
        }
    }

    public function actualizarDisponibilidad(int $paseadorId, array $dias, string $horaInicio, string $horaFin): array
    {
        try {
            $pdo = $this->db ?? \Jaguata\Config\AppConfig::db();
            $jsonDias = json_encode($dias, JSON_UNESCAPED_UNICODE);

            // Si ya existe, actualiza. Si no, inserta.
            $sql = "INSERT INTO paseador_disponibilidad (paseador_id, dias, hora_inicio, hora_fin)
                VALUES (:id, :dias, :inicio, :fin)
                ON DUPLICATE KEY UPDATE 
                    dias = VALUES(dias),
                    hora_inicio = VALUES(hora_inicio),
                    hora_fin = VALUES(hora_fin)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id' => $paseadorId,
                ':dias' => $jsonDias,
                ':inicio' => $horaInicio,
                ':fin' => $horaFin
            ]);

            return ['success' => true];
        } catch (\Throwable $e) {
            error_log("Error actualizarDisponibilidad: " . $e->getMessage());
            return ['error' => 'Error al guardar la disponibilidad.'];
        }
    }
    public function getDisponibilidad(int $paseadorId): array
    {
        // 🔹 Si todavía no tenés tabla de disponibilidad, devolvemos datos simulados
        // Podés reemplazar luego con una consulta real al modelo
        $fakeData = [
            1 => [
                ['dia' => 'Lunes', 'desde' => '08:00', 'hasta' => '16:00'],
                ['dia' => 'Miércoles', 'desde' => '10:00', 'hasta' => '14:00'],
            ],
            2 => [
                ['dia' => 'Martes', 'desde' => '09:00', 'hasta' => '17:00'],
                ['dia' => 'Jueves', 'desde' => '13:00', 'hasta' => '18:00'],
            ],
        ];

        // Devuelve según el paseador, o vacío si no hay registros
        return $fakeData[$paseadorId] ?? [];
    }
}
