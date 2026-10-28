// For success and error alerts
export async function showAlert(ok, message) {
    await Swal.fire({
        icon: ok ? 'success' : 'error',
        title: ok ? 'Success' : 'Error',
        text: message
    });
}

// For warning and question alerts
export async function showConfirm(icon, title, message, confirmText, cancelText) {
    const result = await Swal.fire({
        icon: icon,
        title: title,
        text: message,
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: cancelText,
        reverseButtons: true
    });

    return result.isConfirmed;
}

export async function showUnexpectedError(error) {
    await Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Unable to communicate with the server.'
    });
}

export async function showLoading(title, text) {
    await Swal.fire({
        title: title,
        text: text,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

// Format: September 20, 2005 14:35
export function formatTimestamp(timestamp) {
    if (!timestamp) return '-';

    const date = new Date(timestamp.replace(' ', 'T'));

    if (Number.isNaN(date.getTime())) return '-';

    const formattedDate = date.toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric'
    });
    const formattedTime = date.toLocaleTimeString('en-GB', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
    });

    return `${formattedDate} ${formattedTime}`;
}

// Format: September 20, 2005
export function formatDate(datetime) {
    if (!datetime) return '—';

    const date = new Date(datetime.replace(' ', 'T'));

    if (Number.isNaN(date.getTime())) return '—';

    return date.toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric'
    });
}
