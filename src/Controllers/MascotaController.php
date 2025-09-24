<?php
namespace Jaguata\Controllers;

use Jaguata\Models\Mascota;
use Jaguata\Helpers\Session;
use Exception;

class MascotaController {
    private Mascota $mascotaModel;

    public function __construct() {
        $this->mascotaModel = new Mascota();
    }

    /**
     * Listar todas las mascotas del usuario logueado
     */
    public function index() {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }
        return $this->mascotaModel->allByOwner(Session::getUsuarioId());
    }

    /**
     * Mostrar una mascota por ID
     */
    public function show(int $id): ?array {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }

        $mascota = $this->mascotaModel->find($id);

        if (!$mascota || $mascota['dueno_id'] !== Session::getUsuarioId()) {
            return ['error' => 'Mascota no encontrada o no autorizada'];
        }

        return $mascota;
    }

    /**
     * Guardar mascota desde formulario (AgregarMascota.php)
     */
    public function store(): void {
        if (!Session::isLoggedIn()) {
            $_SESSION['error'] = "Debe iniciar sesión para agregar mascotas";
            header("Location: " . BASE_URL . "/login.php");
            exit;
        }

        $nombre = trim($_POST['nombre'] ?? '');
        $raza   = trim($_POST['raza'] ?? '');
        $tamano = $_POST['tamano'] ?? '';
        $edad   = (int)($_POST['edad'] ?? 0);
        $obs    = trim($_POST['observaciones'] ?? '');

        $errors = [];

        if ($nombre === '') {
            $errors[] = "El nombre es obligatorio";
        }
        if (!in_array($tamano, ['pequeno', 'mediano', 'grande', ''], true)) {
            $errors[] = "El tamaño seleccionado no es válido";
        }
        if ($edad < 0 || $edad > 30) {
            $errors[] = "La edad debe estar entre 0 y 30 años";
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            header("Location: " . BASE_URL . "/features/dueno/AgregarMascota.php");
            exit;
        }

        try {
            $this->mascotaModel->create([
                'dueno_id'      => Session::getUsuarioId(),
                'nombre'        => $nombre,
                'raza'          => $raza,
                'tamano'        => $tamano,
                'edad'          => $edad,
                'observaciones' => $obs,
            ]);

            $_SESSION['success'] = "Mascota agregada correctamente";
            header("Location: " . BASE_URL . "/features/dueno/MisMascotas.php");
            exit;

        } catch (Exception $e) {
            error_log("Error al guardar mascota: " . $e->getMessage());
            $_SESSION['error'] = "Error interno al guardar la mascota";
            header("Location: " . BASE_URL . "/features/dueno/AgregarMascota.php");
            exit;
        }
    }

    /**
     * Actualizar mascota desde formulario (EditarMascota.php)
     */
    public function update(int $id): void {
        if (!Session::isLoggedIn()) {
            $_SESSION['error'] = "Debe iniciar sesión para editar mascotas";
            header("Location: " . BASE_URL . "/login.php");
            exit;
        }

        $nombre = trim($_POST['nombre'] ?? '');
        $raza   = trim($_POST['raza'] ?? '');
        $tamano = $_POST['tamano'] ?? '';
        $edad   = (int)($_POST['edad'] ?? 0);
        $obs    = trim($_POST['observaciones'] ?? '');

        $errors = [];

        if ($nombre === '') {
            $errors[] = "El nombre es obligatorio";
        }
        if (!in_array($tamano, ['pequeno', 'mediano', 'grande', ''], true)) {
            $errors[] = "El tamaño seleccionado no es válido";
        }
        if ($edad < 0 || $edad > 30) {
            $errors[] = "La edad debe estar entre 0 y 30 años";
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            header("Location: " . BASE_URL . "/features/dueno/EditarMascota.php?id=" . $id);
            exit;
        }

        try {
            $ok = $this->mascotaModel->update($id, [
                'nombre'        => $nombre,
                'raza'          => $raza,
                'tamano'        => $tamano,
                'edad'          => $edad,
                'observaciones' => $obs,
            ]);

            if ($ok) {
                $_SESSION['success'] = "Mascota actualizada correctamente";
                header("Location: " . BASE_URL . "/features/dueno/MisMascotas.php");
            } else {
                $_SESSION['error'] = "No se pudo actualizar la mascota";
                header("Location: " . BASE_URL . "/features/dueno/EditarMascota.php?id=" . $id);
            }
            exit;

        } catch (Exception $e) {
            error_log("Error al actualizar mascota: " . $e->getMessage());
            $_SESSION['error'] = "Error interno al actualizar la mascota";
            header("Location: " . BASE_URL . "/features/dueno/EditarMascota.php?id=" . $id);
            exit;
        }
    }
    /**
 * Eliminar mascota
 */
public function destroy(int $id): void {
    if (!Session::isLoggedIn()) {
        $_SESSION['error'] = "Debe iniciar sesión para eliminar mascotas";
        header("Location: " . BASE_URL . "/login.php");
        exit;
    }

    try {
        $mascota = $this->mascotaModel->find($id);

        if (!$mascota || $mascota['dueno_id'] !== Session::getUsuarioId()) {
            $_SESSION['error'] = "Mascota no encontrada o no autorizada";
            header("Location: " . BASE_URL . "/features/dueno/MisMascotas.php");
            exit;
        }

        $ok = $this->mascotaModel->delete($id);

        if ($ok) {
            $_SESSION['success'] = "Mascota eliminada correctamente";
        } else {
            $_SESSION['error'] = "No se pudo eliminar la mascota";
        }

        header("Location: " . BASE_URL . "/features/dueno/MisMascotas.php");
        exit;

    } catch (Exception $e) {
        error_log("Error al eliminar mascota: " . $e->getMessage());
        $_SESSION['error'] = "Error interno al eliminar la mascota";
        header("Location: " . BASE_URL . "/features/dueno/MisMascotas.php");
        exit;
    }
}

}
