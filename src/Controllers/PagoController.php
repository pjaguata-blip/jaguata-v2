<?php
namespace Jaguata\Controllers;

use Jaguata\Models\Pago;
use Jaguata\Helpers\Session;
use Exception;

class PagoController {
    private $pagoModel;

    public function __construct() {
        $this->pagoModel = new Pago();
    }

    /**
     * Listar todos los pagos
     */
    public function index() {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }
        return $this->pagoModel->all();
    }

    /**
     * Mostrar un pago específico
     */
    public function show($id) {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }
        return $this->pagoModel->find($id);
    }

    /**
     * Crear un pago
     */
    public function apiStore() {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }

        $data = [
            'paseo_id' => $_POST['paseo_id'] ?? null,
            'dueno_id' => $_SESSION['usuario_id'],
            'paseador_id' => $_POST['paseador_id'] ?? null,
            'monto' => $_POST['monto'] ?? 0,
            'tarifa' => $_POST['tarifa'] ?? 0,
            'ganancia_paseador' => $_POST['ganancia_paseador'] ?? 0,
            'metodo_id' => $_POST['metodo_id'] ?? null,
            'currency' => $_POST['currency'] ?? 'PYG'
        ];

        try {
            $id = $this->pagoModel->create($data);
            return ['success' => true, 'id' => $id];
        } catch (Exception $e) {
            error_log('Error al crear pago: ' . $e->getMessage());
            return ['error' => 'Error interno al crear pago'];
        }
    }

    /**
     * Actualizar estado de un pago
     */
    public function apiUpdate($id) {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }

        try {
            $result = $this->pagoModel->update($id, $_POST);
            return $result
                ? ['success' => true, 'message' => 'Pago actualizado']
                : ['error' => 'No se pudo actualizar pago'];
        } catch (Exception $e) {
            error_log('Error al actualizar pago: ' . $e->getMessage());
            return ['error' => 'Error interno del servidor'];
        }
    }

    /**
     * Eliminar un pago
     */
    public function apiDestroy($id) {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }

        try {
            $result = $this->pagoModel->delete($id);
            return $result
                ? ['success' => true, 'message' => 'Pago eliminado']
                : ['error' => 'No se pudo eliminar pago'];
        } catch (Exception $e) {
            error_log('Error al eliminar pago: ' . $e->getMessage());
            return ['error' => 'Error interno del servidor'];
        }
    }
}
