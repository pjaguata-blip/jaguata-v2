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
    public function getPaseoById(int $id): ?array
    {
        $db = \Jaguata\Services\DatabaseService::getInstance();

        $sql = "
        SELECT 
            p.paseo_id,
            p.inicio,
            p.duracion,
            p.precio_total,
            p.estado,
            m.mascota_id,
            m.nombre AS nombre_mascota,
            d.usu_id AS dueno_id,
            d.nombre AS nombre_dueno,
            u.usu_id AS paseador_id,
            u.nombre AS nombre_paseador,
            u.telefono AS telefono_paseador,
            u.zona AS zona,
            u.latitud AS paseador_latitud,
            u.longitud AS paseador_longitud
        FROM paseos p
        INNER JOIN mascotas m ON m.mascota_id = p.mascota_id
        INNER JOIN usuarios u ON u.usu_id = p.paseador_id
        INNER JOIN usuarios d ON d.usu_id = m.dueno_id
        WHERE p.paseo_id = :id
        LIMIT 1
    ";

        $row = $db->fetchOne($sql, ['id' => $id]) ?: null;

        // 🔹 agregar campo rated_id para el formulario de calificación
        if ($row) {
            $row['rated_id'] = $row['paseador_id'];
        }

        return $row;
    }




    // Listar todos los paseos
    public function index()
    {
        return $this->paseoModel->allWithRelations();
    }

    public function store()
    {
        if (!Session::isLoggedIn()) {
            $_SESSION['error'] = "Debes iniciar sesión.";
            header("Location: " . BASE_URL . "/public/login.php");
            exit;
        }

        try {
            $data = [
                'mascota_id'   => $_POST['mascota_id'] ?? null,
                'paseador_id'  => $_POST['paseador_id'] ?? null,
                'inicio'       => $_POST['inicio'] ?? date('Y-m-d H:i:s'),
                'duracion'     => $_POST['duracion'] ?? 60,
                'precio_total' => $_POST['precio_total'] ?? 0,
            ];

            return ['id' => $this->paseoModel->create($data)];
        } catch (Exception $e) {
            error_log('Error en store: ' . $e->getMessage());
            return ['error' => 'Error al crear el paseo'];
        }
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
    public function confirmar(int $id)
    {
        return $this->paseoModel->cambiarEstado($id, 'confirmado');
    }

    public function apiIniciar(int $id): array
    {
        try {
            $ok = $this->paseoModel->cambiarEstado($id, 'en_curso');
            return $ok ? ['success' => true] : ['error' => 'No se pudo iniciar'];
        } catch (\Throwable $e) {
            error_log('apiIniciar: ' . $e->getMessage());
            return ['error' => 'Error interno'];
        }
    }

    public function apiCompletar(int $id): array
    {
        try {
            $ok = $this->paseoModel->cambiarEstado($id, 'completo');
            return $ok ? ['success' => true] : ['error' => 'No se pudo completar'];
        } catch (\Throwable $e) {
            error_log('apiCompletar: ' . $e->getMessage());
            return ['error' => 'Error interno'];
        }
    }

    public function apiCancelar(int $id): array
    {
        try {
            $ok = $this->paseoModel->cambiarEstado($id, 'cancelado');
            return $ok ? ['success' => true] : ['error' => 'No se pudo cancelar'];
        } catch (\Throwable $e) {
            error_log('apiCancelar: ' . $e->getMessage());
            return ['error' => 'Error interno'];
        }
    }

    public function cancelarPaseo(int $paseoId, string $motivo = ''): array
    {
        try {
            $db = AppConfig::db();

            // Verificar si el paseo existe y su estado actual
            $stmt = $db->prepare("SELECT * FROM paseos WHERE paseo_id = :id");
            $stmt->bindValue(':id', $paseoId, PDO::PARAM_INT);
            $stmt->execute();
            $paseo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$paseo) {
                return ['error' => 'No se encontró el paseo.'];
            }

            if (!in_array($paseo['estado'], ['pendiente', 'confirmado'], true)) {
                return ['error' => 'Solo se pueden cancelar paseos pendientes o confirmados.'];
            }

            // Actualizar estado
            $update = $db->prepare("
            UPDATE paseos
            SET estado = 'cancelado',
                motivo_cancelacion = :motivo,
                fecha_cancelacion = NOW()
            WHERE paseo_id = :id
        ");
            $update->bindValue(':motivo', $motivo);
            $update->bindValue(':id', $paseoId, PDO::PARAM_INT);
            $update->execute();

            return ['success' => true];
        } catch (\Throwable $e) {
            return ['error' => 'Error al cancelar paseo: ' . $e->getMessage()];
        }
    }
    public function completarPaseo(int $paseoId, string $comentario = ''): array
    {
        try {
            $db = AppConfig::db();

            // Validar que exista el paseo
            $stmt = $db->prepare("SELECT * FROM paseos WHERE paseo_id = :id");
            $stmt->bindValue(':id', $paseoId, PDO::PARAM_INT);
            $stmt->execute();
            $paseo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$paseo) {
                return ['error' => 'No se encontró el paseo especificado.'];
            }

            // Verificar que el estado permita completarlo
            if (!in_array($paseo['estado'], ['en_curso', 'confirmado'], true)) {
                return ['error' => 'El paseo no puede ser marcado como completado en este estado.'];
            }

            // Actualizar estado a completado
            $update = $db->prepare("
            UPDATE paseos
            SET estado = 'completado',
                comentario_final = :comentario,
                fecha_completado = NOW()
            WHERE paseo_id = :id
        ");
            $update->bindValue(':comentario', $comentario);
            $update->bindValue(':id', $paseoId, PDO::PARAM_INT);
            $update->execute();

            if ($update->rowCount() > 0) {
                return ['success' => true];
            }

            return ['error' => 'No se realizaron cambios.'];
        } catch (\Throwable $e) {
            return ['error' => 'Error al completar paseo: ' . $e->getMessage()];
        }
    }
    public function show(int $id): ?array
    {
        $paseoModel = new \Jaguata\Models\Paseo();
        return $paseoModel->getById($id);
    }
}
