<?php

namespace Jaguata\Controllers;

require_once __DIR__ . '/../Config/AppConfig.php';
require_once __DIR__ . '/../Services/DatabaseService.php';
require_once __DIR__ . '/../Helpers/Session.php';
require_once __DIR__ . '/../Models/Notificacion.php';
require_once __DIR__ . '/../Helpers/Auditoria.php';

use Jaguata\Config\AppConfig;
use Jaguata\Services\DatabaseService;
use Jaguata\Helpers\Session;
use Jaguata\Models\Notificacion;
use Jaguata\Helpers\Auditoria;
use PDO;
use PDOException;

AppConfig::init();

class NotificacionController
{
    private PDO $db;
    private Notificacion $notificacionModel;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance()->getConnection();
        $this->notificacionModel = new Notificacion();
    }

    /* ==========================================================
       ADMIN
       ========================================================== */

    public function indexAdmin(string $destino = 'todos'): array
    {
        try {
            $sql = "
                SELECT 
                    n.noti_id AS id,
                    COALESCE(u.nombre, CONCAT('Usuario ID ', n.usu_id)) AS usuario,
                    n.rol_destinatario,
                    n.titulo,
                    n.mensaje,
                    n.estado,
                    n.leido,
                    n.created_at AS fecha
                FROM notificaciones n
                LEFT JOIN usuarios u ON u.usu_id = n.usu_id
                WHERE 1 = 1
            ";

            $params = [];

            $destino = strtolower(trim($destino));
            if (in_array($destino, ['admin', 'paseador', 'dueno', 'dueño', 'todos'], true)) {
                if ($destino === 'dueno') $destino = 'dueño';
                if ($destino !== 'todos') {
                    $sql .= " AND n.rol_destinatario = :destino";
                    $params[':destino'] = $destino;
                }
            }

            $sql .= " ORDER BY n.created_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('❌ NotificacionController::indexAdmin => ' . $e->getMessage());
            return [];
        }
    }

    public function crearDesdeAdmin(array $data): array
    {
        $titulo    = trim($data['titulo'] ?? '');
        $mensaje   = trim($data['mensaje'] ?? '');
        $rawDest   = trim($data['destinatario'] ?? 'todos');
        $tipo      = trim($data['tipo'] ?? 'general');
        $prioridad = strtolower(trim($data['prioridad'] ?? 'media'));
        $canal     = strtolower(trim($data['canal'] ?? 'app'));

        if ($titulo === '' || $mensaje === '') {
            return ['success' => false, 'error' => 'Título y mensaje son obligatorios.'];
        }

        $mapDestinos = [
            'todos' => 'todos',
            'todo' => 'todos',
            'duenos' => 'dueño',
            'dueños' => 'dueño',
            'dueno' => 'dueño',
            'dueño' => 'dueño',
            'paseadores' => 'paseador',
            'paseador' => 'paseador',
            'admin' => 'admin',
            'administrador' => 'admin',
        ];

        $destKey      = mb_strtolower($rawDest, 'UTF-8');
        $destinatario = $mapDestinos[$destKey] ?? 'todos';

        $validPrioridades = ['baja', 'media', 'alta'];
        if (!in_array($prioridad, $validPrioridades, true)) $prioridad = 'media';

        $validCanales = ['app', 'email', 'push'];
        if (!in_array($canal, $validCanales, true)) $canal = 'app';

        $adminId = Session::getUsuarioId() ?: null;

        try {
            $sql = "
    INSERT INTO notificaciones (
        usu_id,
        rol_destinatario,
        admin_id,
        tipo,
        prioridad,
        canal,
        titulo,
        mensaje,
        paseo_id,
        leido,
        estado
    ) VALUES (
        :usu_id,
        :rol_destinatario,
        :admin_id,
        :tipo,
        :prioridad,
        :canal,
        :titulo,
        :mensaje,
        NULL,
        0,
        'enviada'
    )
";


            $stmt = $this->db->prepare($sql);

            // masiva => usu_id NULL
            $stmt->bindValue(':usu_id', null, PDO::PARAM_NULL);

            if ($adminId !== null) $stmt->bindValue(':admin_id', $adminId, PDO::PARAM_INT);
            else $stmt->bindValue(':admin_id', null, PDO::PARAM_NULL);

            $stmt->bindValue(':rol_destinatario', $destinatario);
            $stmt->bindValue(':tipo', $tipo);
            $stmt->bindValue(':prioridad', $prioridad);
            $stmt->bindValue(':canal', $canal);
            $stmt->bindValue(':titulo', $titulo);
            $stmt->bindValue(':mensaje', $mensaje);

            $stmt->execute();

            return ['success' => true];
        } catch (PDOException $e) {
            error_log('❌ NotificacionController::crearDesdeAdmin => ' . $e->getMessage());
            return ['success' => false, 'error' => 'Error al guardar: ' . $e->getMessage()];
        }
    }

    /* ==========================================================
       USUARIO (dueño / paseador)
       ========================================================== */

    public function index(array $filters = []): array
    {
        $usuId = (int)(Session::getUsuarioId() ?? 0);
        $rol   = (string)(Session::getUsuarioRol() ?? '');

        if ($usuId <= 0 || $rol === '') {
            return ['data' => [], 'total' => 0, 'page' => 1, 'perPage' => 10, 'totalPages' => 1];
        }

        $q       = trim((string)($filters['q'] ?? ''));
        $leido   = $filters['leido'] ?? '';
        $page    = max(1, (int)($filters['page'] ?? 1));
        $perPage = max(1, (int)($filters['perPage'] ?? 10));

        $leidoFilter = ($leido === '' ? null : (int)$leido);

        return $this->notificacionModel->listByUser(
            $usuId,
            $rol,
            $leidoFilter,
            $q !== '' ? $q : null,
            $page,
            $perPage,
            null
        );
    }

    public function listForCurrentUser(int $page = 1, int $perPage = 10, ?int $leido = null, ?string $q = null): array
    {
        $filters = ['page' => $page, 'perPage' => $perPage];
        if ($leido !== null) $filters['leido'] = $leido;
        if ($q !== null && $q !== '') $filters['q'] = $q;
        return $this->index($filters);
    }

    public function marcarLeidaForCurrentUser(int $notiId): bool
    {
        $usuId = (int)(Session::getUsuarioId() ?? 0);
        if ($usuId <= 0 || $notiId <= 0) return false;
        return $this->notificacionModel->markRead($notiId, $usuId);
    }

    public function marcarTodasForCurrentUser(): int
    {
        $usuId = (int)(Session::getUsuarioId() ?? 0);
        $rol   = (string)(Session::getUsuarioRol() ?? '');
        if ($usuId <= 0 || $rol === '') return 0;
        return $this->notificacionModel->markAllRead($usuId, $rol);
    }

    /* ==========================================================
       ✅ LO QUE TE FALTA: LIMPIAR / ELIMINAR
       - personal (usu_id = user) => archivada=1
       - masiva  (usu_id IS NULL) => notificaciones_ocultas
       ========================================================== */

    public function limpiarUnaForCurrentUser(int $notiId): bool
    {
        $usuId = (int)(Session::getUsuarioId() ?? 0);
        $rol   = strtolower(trim((string)(Session::getUsuarioRol() ?? '')));

        if ($usuId <= 0 || $notiId <= 0 || $rol === '') return false;

        $tipo = $this->notificacionModel->tipoParaUsuario($notiId, $usuId, $rol);
        if ($tipo === null) return false;

        if ($tipo === 'personal') {
            return $this->notificacionModel->archivarPersonal($notiId, $usuId);
        }

        return $this->notificacionModel->ocultarMasivaParaUsuario($notiId, $usuId);
    }

    public function limpiarTodasForCurrentUser(): int
    {
        $usuId = (int)(Session::getUsuarioId() ?? 0);
        $rol   = strtolower(trim((string)(Session::getUsuarioRol() ?? '')));

        if ($usuId <= 0 || $rol === '') return 0;

        $a = $this->notificacionModel->archivarTodasPersonales($usuId);
        $b = $this->notificacionModel->ocultarTodasMasivasParaUsuario($usuId, $rol);

        return $a + $b;
    }

    public function getRecientes(int $usuId, int $limit = 5): array
    {
        if ($usuId <= 0) return [];
        $rol = (string)(Session::getUsuarioRol() ?? 'paseador');
        try {
            return $this->notificacionModel->getRecientes($usuId, $rol, $limit);
        } catch (PDOException $e) {
            error_log('❌ NotificacionController::getRecientes => ' . $e->getMessage());
            return [];
        }
    }

    /* (tus métodos de auditoría “simulados” los dejo igual) */
    public function enviarNotificacionUsuario(int $usuarioId, string $titulo, string $mensaje): bool
    {
        $ok = true;

        if ($ok) {
            $adminId = Session::getUsuarioId();
            Auditoria::log(
                'ENVIAR NOTIFICACIÓN',
                'Notificaciones',
                'El admin ID ' . $adminId . ' envió una notificación al usuario ID ' . $usuarioId . ' con título: "' . $titulo . '"',
                $usuarioId,
                $adminId
            );
        }

        return $ok;
    }

    public function enviarNotificacionMasiva(array $idsUsuarios, string $titulo, string $mensaje): bool
    {
        $cantidad = count($idsUsuarios);
        $ok = true;

        if ($ok) {
            $adminId = Session::getUsuarioId();
            Auditoria::log(
                'ENVIAR NOTIFICACIÓN MASIVA',
                'Notificaciones',
                'El admin ID ' . $adminId . ' envió una notificación masiva a ' . $cantidad . ' usuarios. Título: "' . $titulo . '"',
                null,
                $adminId
            );
        }

        return $ok;
    }
}
