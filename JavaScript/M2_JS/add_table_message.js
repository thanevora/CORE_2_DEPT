document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);

    if (urlParams.get('success') === '1') {
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: 'Stock request submitted successfully.',
            timer: 1000,
            showConfirmButton: false
        });
    } else if (urlParams.get('error') === '1') {
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'Failed to submit stock request.',
        });
    } else if (urlParams.get('invalid') === '1') {
        Swal.fire({
            icon: 'warning',
            title: 'Invalid Request',
            text: 'Invalid form submission method.',
        });
    }

    // Clean the URL after handling the alert
    if (urlParams.has('success') || urlParams.has('error') || urlParams.has('invalid')) {
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});