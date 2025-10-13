<?php

namespace Jaguata\Controllers;

use Jaguata\Models\Paseo;
use Jaguata\Models\Usuario;
use Jaguata\Helpers\Session;
use Jaguata\Services\DatabaseService;
use Jaguata\Config\AppConfig;   // ✅ agregar esto
use PDO;                        // ✅ y esto
use Exception;

class PaseoController
{
    private PDO $db;
    private Paseo $paseoModel;
    private Usuario $usuarioModel;


    public function __construct()
    {
        $this->paseoModel = new Paseo();
        $this->usuarioModel = new Usuario();
        $this->db = DatabaseService::getInstance()->getConnection();
    }

    // Obtener paseo por ID
    public function getPaseoById(int $id)
    {
        return $this->paseoModel->findWithRelations($id);
    }

    // Listar todos los paseos
    public function index()
    {
        return $this->paseoModel->allWithRelations();
    }

    public function show($id)
    {
        return $this->paseoModel->find($id);
    }

    public function store()
    {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }

        $data = [
            'mascota_id'   => (int)($_POST['mascota_id'] ?? 0),
            'paseador_id'  => (int)($_POST['paseador_id'] ?? 0),
            'inicio'       => $_POST['inicio'] ?? '',
            'duracion'     => (int)($_POST['duracion'] ?? 0),
            'precio_total' => (float)($_POST['precio_total'] ?? 0)
        ];

        return ['id' => $this->paseoModel->create($data)];
    }

    public function update($id)
    {
        $data = [
            'inicio'       => $_POST['inicio'] ?? '',
            'duracion'     => (int)($_POST['duracion'] ?? 0),
            'precio_total' => (float)($_POST['precio_total'] ?? 0),
        ];
        return $this->paseoModel->update($id, $data);
    }

    public function destroy($id)
    {
        return $this->paseoModel->delete($id);
    }

    public function confirmar($id)
    {
        return $this->paseoModel->cambiarEstado($id, 'confirmado');
    }

    public function apiCompletar($id)
    {
        $resultado = $this->paseoModel->cambiarEstado($id, 'completado');

        if ($resultado) {
            $paseo = $this->paseoModel->find($id);
            if ($paseo && isset($paseo['dueno_id'])) {
                $this->usuarioModel->sumarPuntos((int)$paseo['dueno_id'], 10);
            }
        }

        return $resultado;
    }

    public function indexByDueno(int $duenoId)
    {
        return $this->paseoModel->findByDueno($duenoId);
    }

    public function indexForPaseador(int $paseadorId): array
    {
        return $this->paseoModel->findByPaseador($paseadorId);
    }

    public function getSolicitudesPendientes(int $paseadorId): array
    {
        $sql = "SELECT 
                p.paseo_id,
                p.inicio,
                p.duracion,
                p.precio_total,
                m.nombre AS nombre_mascota,
                u.nombre AS nombre_dueno
            FROM paseos p
            INNER JOIN mascotas m ON m.mascota_id = p.mascota_id
            INNER JOIN usuarios u ON u.usu_id = m.dueno_id
            WHERE p.paseador_id = :paseadorId
              AND p.estado = 'solicitado'
            ORDER BY p.inicio DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['paseadorId' => $paseadorId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }




    public function getGananciasPorPaseador(int $paseadorId): float
    {
        $paseos = $this->paseoModel->getByPaseador($paseadorId);
        $total = 0;
        foreach ($paseos as $p) {
            if (($p['estado'] ?? null) === 'completo') {
                $total += (float)($p['precio_total'] ?? 0);
            }
        }
        return $total;
    }

    /**
     * Devuelve datos del paseo necesarios para la pantalla de pago del dueño.
     * Retorna null si no existe.
     * Nota: tu UI usa 'duracion_min'; mapeamos desde p.duracion.
     */
    public function getDetalleParaPago(int $paseoId): ?array
    {
        $db = DatabaseService::getInstance();

        $sql = "
            SELECT 
                p.paseo_id                             AS paseo_id,
                m.dueno_id                             AS dueno_id,         -- dueño viene de mascotas
                p.paseador_id                          AS paseador_id,
                p.inicio                               AS inicio,
                p.duracion                             AS duracion_min,     -- la pantalla espera 'duracion_min'
                p.precio_total                         AS precio_total,

                u.nombre                               AS nombre_paseador,

                dp.banco                               AS paseador_banco,
                dp.alias                               AS paseador_alias,
                dp.cuenta                              AS paseador_cuenta

            FROM paseos p
            INNER JOIN mascotas m     ON m.mascota_id = p.mascota_id
            INNER JOIN usuarios u     ON u.usu_id     = p.paseador_id
            LEFT  JOIN datos_pago dp  ON dp.usuario_id = p.paseador_id

            WHERE p.paseo_id = :id
            LIMIT 1
        ";

        return $db->fetchOne($sql, ['id' => $paseoId]) ?: null;
    }
    public function listarMascotasDeDueno(int $duenoId): array
    {
        try {
            $db = AppConfig::db();
            $st = $db->prepare("
            SELECT id, nombre
            FROM mascotas
            WHERE dueno_id = :d AND estado <> 'eliminado'
            ORDER BY nombre ASC, id ASC
        ");
            $st->execute([':d' => $duenoId]);
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            error_log('listarMascotasDeDueno: ' . $e->getMessage());
            return [];
        }
    }

    public function listarPaseadores(): array
    {
        try {
            $db = AppConfig::db();
            $st = $db->query("
            SELECT id, nombre
            FROM usuarios
            WHERE role = 'paseador' AND (estado IS NULL OR estado <> 'inactivo')
            ORDER BY nombre ASC, id ASC
        ");
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            error_log('listarPaseadores: ' . $e->getMessage());
            return [];
        }
    }
    public function getById(int $id): ?array
    {
        try {
            return $this->paseoModel->getById($id);
        } catch (Exception $e) {
            return null;
        }
    }
    public function apiIniciar(int $id)
    {
        try {
            // Cambia el estado a 'en_curso'
            $resultado = $this->paseoModel->cambiarEstado($id, 'en_curso');

            if ($resultado) {
                return ['success' => true, 'message' => 'Paseo iniciado correctamente'];
            } else {
                return ['error' => 'No se pudo iniciar el paseo'];
            }
        } catch (\Throwable $e) {
            error_log('Error en apiIniciar: ' . $e->getMessage());
            return ['error' => 'Error interno al iniciar el paseo'];
        }
    }
}
