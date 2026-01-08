 document.querySelectorAll('.status-btn').forEach(button => {
  button.addEventListener('click', async () => {
    const id = button.dataset.id;
    const status = button.dataset.status;

    // Confirm action before proceeding
    const confirm = await Swal.fire({
      title: `Change status to "${status}"?`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Yes, update it!',
      cancelButtonText: 'Cancel',
      reverseButtons: true
    });

    if (!confirm.isConfirmed) return;

    const formData = new FormData();
    formData.append('reservation_id', id);
    formData.append('status', status);

    try {
      const response = await fetch('sub-modules/update_reservation_status.php', {
        method: 'POST',
        body: formData
      });

      const data = await response.json();

      if (data.success) {   
        Swal.fire({
          icon: 'success',
          title: 'Updated!',
          text: data.message,
          timer: 1500,
          showConfirmButton: false
        }).then(() => location.reload());
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: data.error || 'Something went wrong!'
        });
      }
    } catch (err) {
      Swal.fire({
        icon: 'error',
        title: 'Connection Error',
        text: 'Failed to connect to the server.'
      });
    }
  });
});