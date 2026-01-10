document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('add-table-form');
  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(form);

    try {
      const res = await fetch('../../M1/sub-modules/add_table.php', {
        method: 'POST',
        body: formData
      });

      const data = await res.json();

      if (data.status === 'success') {
        Swal.fire({
          title: 'Success!',
          text: 'New table has been added.',
          icon: 'success',
          timer: 1000,
          showConfirmButton: false
        }).then(() => location.reload());
      } else {
        Swal.fire({
          title: 'Oops!',
          text: data.message || 'Something went wrong.',
          icon: 'error',
          confirmButtonColor: '#ef4444'
        });
      }
    } catch (error) {
      console.error('[Add Table Error]', error);
      Swal.fire({
        title: 'Server Error',
        text: 'Unable to connect to the server.',
        icon: 'error',
        confirmButtonColor: '#ef4444'
      });
    }
  });
});
