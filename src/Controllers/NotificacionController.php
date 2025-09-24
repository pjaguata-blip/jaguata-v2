<?php
namespace Jaguata\Controllers;

use Jaguata\Models\Notificacion;
use Jaguata\Services\NotificacionService;
use Jaguata\Services\DatabaseService;
use Jaguata\Helpers\Session;
use Exception;

class NotificacionController {
    private $db;
    private $notificacionModel;
    private $notificacionService;
    
    public function __construct() {
        $this->db = DatabaseService::getInstance();
        $this->notificacionModel = new Notificacion();
        $this->notificacionService = new NotificacionService();
    }

    /**
     * Listar notificaciones
     */
    public function index() {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }
        $usuarioId = $_SESSION['usuario_id'];
        $leido = $_GET['leido'] ?? null;
        $limite = (int)($_GET['limite'] ?? 20);

        return $this->notificacionService->getNotificacionesByUsuario($usuarioId, $leido, $limite);
    }

    /**
     * Crear notificación
     */
    public function crear() {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }

        $usuId = (int)($_POST['usu_id'] ?? 0);
        $tipo = $_POST['tipo'] ?? '';
        $titulo = trim($_POST['titulo'] ?? '');
        $mensaje = trim($_POST['mensaje'] ?? '');
        $paseoId = (int)($_POST['paseo_id'] ?? 0);

        if (!$usuId || !$tipo || !$titulo || !$mensaje) {
            return ['error' => 'Datos inválidos'];
        }

        try {
            $id = $this->notificacionModel->create([
                'usu_id'  => $usuId,
                'tipo'    => $tipo,
                'titulo'  => $titulo,
                'mensaje' => $mensaje,
                'paseo_id'=> $paseoId ?: null
            ]);
            return ['success' => true, 'id' => $id];
        } catch (Exception $e) {
            error_log('Error crear notificación: ' . $e->getMessage());
            return ['error' => 'Error interno'];
        }
    }

    /**
     * Actualizar notificación
     */
    public function update($id) {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }

        $titulo = trim($_POST['titulo'] ?? '');
        $mensaje = trim($_POST['mensaje'] ?? '');

        if (!$titulo || !$mensaje) {
            return ['error' => 'Datos inválidos'];
        }

        try {
            $ok = $this->notificacionModel->update($id, [
                'titulo'  => $titulo,
                'mensaje' => $mensaje
            ]);
            return $ok ? ['success' => true] : ['error' => 'No se pudo actualizar'];
        } catch (Exception $e) {
            error_log('Error update notificación: ' . $e->getMessage());
            return ['error' => 'Error interno'];
        }
    }

    /**
     * Marcar como leída
     */
    public function marcarLeida($id) {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }
        return $this->notificacionService->marcarComoLeida($id)
            ? ['success' => true]
            : ['error' => 'No se pudo marcar'];
    }

    /**
     * Marcar todas como leídas
     */
    public function marcarTodasLeidas() {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }
        $usuarioId = $_SESSION['usuario_id'];
        return $this->notificacionService->marcarTodasComoLeidas($usuarioId)
            ? ['success' => true]
            : ['error' => 'No se pudieron marcar'];
    }

    /**
     * Eliminar notificación
     */
    public function eliminar($id) {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }
        return $this->notificacionModel->delete($id)
            ? ['success' => true]
            : ['error' => 'No se pudo eliminar'];
    }

    /**
     * Contador de no leídas
     */
    public function getContadorNoLeidas() {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }
        $usuarioId = $_SESSION['usuario_id'];
        return ['contador' => $this->notificacionService->getContadorNoLeidas($usuarioId)];
    }

    /**
     * Recientes
     */
    public function getRecientes() {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }
        $usuarioId = $_SESSION['usuario_id'];
        $limite = (int)($_GET['limite'] ?? 5);
        return $this->notificacionService->getNotificacionesRecientes($usuarioId, $limite);
    }

    /**
     * Estadísticas
     */
    public function getEstadisticas() {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }
        $usuarioId = $_SESSION['usuario_id'];
        return $this->notificacionService->getEstadisticas($usuarioId);
    }
}
