document.addEventListener('DOMContentLoaded', () => {

  const handleAction = async (id, accion, confirmMsg, btn) => {
    try {
      // Confirmación visual (SweetAlert2)
      const confirmResult = await Swal.fire({
        title: confirmMsg || '¿Confirmar acción?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33'
      });

      if (!confirmResult.isConfirmed) return;

      // Deshabilitar botón mientras se procesa
      if (btn) btn.disabled = true;

      const formData = new FormData();
      formData.append('id', id);
      formData.append('accion', accion);

      const res = await fetch('/jaguata/public/api/usuarios/accionesUsuarios.php', {
        method: 'POST',
        body: formData
      });

      if (!res.ok) throw new Error(`Error HTTP ${res.status}`);

      const data = await res.json();

      await Swal.fire({
        title: data.ok ? '✅ Éxito' : '❌ Error',
        text: data.mensaje || 'Operación completada.',
        icon: data.ok ? 'success' : 'error',
        confirmButtonText: 'Aceptar'
      });

      if (data.ok) location.reload();

    } catch (error) {
      console.error('Error en handleAction:', error);
      Swal.fire({
        title: 'Error',
        text: 'Ocurrió un problema al procesar la solicitud.',
        icon: 'error'
      });
    } finally {
      if (btn) btn.disabled = false;
    }
  };

  document.querySelectorAll('.btn-editar').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.id;
      window.location.href = `/jaguata/public/admin/editar_usuario.php?id=${id}`;
    });
  });

  document.querySelectorAll('.btn-eliminar').forEach(btn => {
    btn.addEventListener('click', () => {
      handleAction(btn.dataset.id, 'eliminar', '¿Seguro que deseas eliminar este usuario?', btn);
    });
  });

  document.querySelectorAll('.btn-suspender').forEach(btn => {
    btn.addEventListener('click', () => {
      handleAction(btn.dataset.id, 'suspender', '¿Suspender este usuario?', btn);
    });
  });

  document.querySelectorAll('.btn-activar').forEach(btn => {
    btn.addEventListener('click', () => {
      handleAction(btn.dataset.id, 'activar', '¿Activar este usuario?', btn);
    });
  });
});
