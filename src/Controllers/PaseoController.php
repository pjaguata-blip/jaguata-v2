<?php

namespace Jaguata\Controllers;

use Jaguata\Models\Paseo;
use Jaguata\Models\Usuario;
use Jaguata\Helpers\Session;
use Jaguata\Services\DatabaseService;
use Jaguata\Config\AppConfig;
use PDO;
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
            p.ubicacion,  -- 🆕 incluir ubicación
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

        if ($row) {
            $row['rated_id'] = $row['paseador_id'];
        }

        return $row;
    }

    // Listar todos los paseos (para dueño)
    public function index(): array
    {
        $db = \Jaguata\Services\DatabaseService::connection();
        $duenoId = \Jaguata\Helpers\Session::getUsuarioId();

        $sql = "SELECT 
                p.paseo_id,
                p.mascota_id,
                p.paseador_id,
                p.inicio,
                p.duracion,
                p.ubicacion,     -- 🆕 mostrar ubicación
                p.precio_total,
                p.estado,
                m.nombre AS nombre_mascota,
                u.nombre AS nombre_paseador
            FROM paseos p
            INNER JOIN mascotas m ON m.mascota_id = p.mascota_id
            INNER JOIN usuarios u ON u.usu_id = p.paseador_id
            WHERE m.dueno_id = :dueno_id
            ORDER BY p.inicio DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute([':dueno_id' => $duenoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Crear nuevo paseo (desde dueño)
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
                'ubicacion'    => trim($_POST['ubicacion'] ?? ''), // 🆕 nueva clave
            ];

            if (
                empty($data['mascota_id']) ||
                empty($data['paseador_id']) ||
                empty($data['inicio']) ||
                empty($data['ubicacion'])
            ) {
                $_SESSION['error'] = "Todos los campos obligatorios deben completarse (incluyendo la ubicación).";
                header("Location: " . BASE_URL . "/features/dueno/SolicitarPaseo.php");
                exit;
            }

            $paseoId = $this->paseoModel->create($data);

            if ($paseoId) {
                $_SESSION['success'] = "Tu solicitud fue enviada correctamente.";
            } else {
                $_SESSION['error'] = "No se pudo guardar el paseo. Intenta de nuevo.";
            }

            header("Location: " . BASE_URL . "/features/dueno/SolicitarPaseo.php");
            exit;
        } catch (Exception $e) {
            error_log('Error en store: ' . $e->getMessage());
            $_SESSION['error'] = "Ocurrió un error al crear el paseo: " . $e->getMessage();
            header("Location: " . BASE_URL . "/features/dueno/SolicitarPaseo.php");
            exit;
        }
    }

    public function update($id)
    {
        $data = [
            'inicio'       => $_POST['inicio'] ?? '',
            'duracion'     => (int)($_POST['duracion'] ?? 0),
            'precio_total' => (float)($_POST['precio_total'] ?? 0),
            'ubicacion'    => trim($_POST['ubicacion'] ?? ''), // 🆕 actualización posible
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
                p.ubicacion,       -- 🆕 ubicación
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

    public function getDetalleParaPago(int $paseoId): ?array
    {
        $db = DatabaseService::getInstance();

        $sql = "
            SELECT 
                p.paseo_id AS paseo_id,
                m.dueno_id AS dueno_id,
                p.paseador_id AS paseador_id,
                p.inicio AS inicio,
                p.duracion AS duracion_min,
                p.precio_total AS precio_total,
                p.ubicacion AS ubicacion,       -- 🆕 para mostrar en pagos
                u.nombre AS nombre_paseador,
                dp.banco AS paseador_banco,
                dp.alias AS paseador_alias,
                dp.cuenta AS paseador_cuenta
            FROM paseos p
            INNER JOIN mascotas m ON m.mascota_id = p.mascota_id
            INNER JOIN usuarios u ON u.usu_id = p.paseador_id
            LEFT  JOIN datos_pago dp ON dp.usuario_id = p.paseador_id
            WHERE p.paseo_id = :id
            LIMIT 1
        ";

        return $db->fetchOne($sql, ['id' => $paseoId]) ?: null;
    }

    public function listarMascotasDeDueno(int $duenoId): array
    {
        $db = \Jaguata\Services\DatabaseService::connection();
        $stmt = $db->prepare("SELECT mascota_id AS id, nombre FROM mascotas WHERE dueno_id = ?");
        $stmt->execute([$duenoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarPaseadores(): array
    {
        $db = \Jaguata\Services\DatabaseService::connection();
        $stmt = $db->query("SELECT usu_id AS id, nombre FROM usuarios WHERE rol = 'paseador' AND estado = 'aprobado'");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

            $stmt = $db->prepare("SELECT * FROM paseos WHERE paseo_id = :id");
            $stmt->bindValue(':id', $paseoId, PDO::PARAM_INT);
            $stmt->execute();
            $paseo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$paseo) {
                return ['error' => 'No se encontró el paseo especificado.'];
            }

            if (!in_array($paseo['estado'], ['en_curso', 'confirmado'], true)) {
                return ['error' => 'El paseo no puede ser marcado como completado en este estado.'];
            }

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

    public function ejecutarAccion(string $accion, int $id): array
    {
        switch ($accion) {
            case 'eliminar':
                return $this->eliminarPaseo($id);
            case 'finalizar':
                return $this->finalizarPaseo($id);
            default:
                return ['ok' => false, 'mensaje' => 'Acción no reconocida'];
        }
    }

    public function obtenerDatosExportacion(): array
    {
        return $this->paseoModel->obtenerTodos();
    }

    public function eliminarPaseo(int $id): array
    {
        $ok = $this->paseoModel->eliminar($id);
        return [
            'ok' => $ok,
            'mensaje' => $ok ? 'Paseo eliminado correctamente.' : 'No se pudo eliminar el paseo.'
        ];
    }

    public function finalizarPaseo(int $id): array
    {
        $ok = $this->paseoModel->finalizar($id);
        return [
            'ok' => $ok,
            'mensaje' => $ok ? 'Paseo finalizado correctamente.' : 'No se pudo finalizar el paseo.'
        ];
    }
}
