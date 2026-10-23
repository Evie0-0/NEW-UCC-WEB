<?php
declare(strict_types=1);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Endpoint Test</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <h1>Test PHP Endpoint</h1>
    <input
        type="file"
        id="imageFile"
    >
    <button id="testButton" type="button">Test Endpoint</button>
    <pre id="response">Response will appear here.</pre>

    <script>
        const testButton = document.getElementById('testButton');
        const responseElement = document.getElementById('response');
        const imageInput = document.getElementById('imageFile');

        testButton.addEventListener('click', async () => {
            responseElement.textContent = 'Loading...';

            try {
                const params = new URLSearchParams({
                    class: 'university-posts'
                });

                const formData = new FormData();
                formData.append('image', imageInput.files[0]);

                const response = await fetch(`api/posts/save.php`, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                responseElement.textContent =
                    `HTTP Status: ${response.status}\n` +
                    `Response:\n${JSON.stringify(result, null, 2)}`;

                if (!response.ok) {
                    await showAlert(false, result.message);
                    return;
                }

                await showAlert(true, result.message);
                return;
            } catch (error) {
                responseElement.textContent = `Request failed: ${error.message}`;
                await showUnexpectedError(error);
            }
        });

        async function showAlert(ok, message) {
            await Swal.fire({
                icon: ok ? 'success' : 'error',
                title: ok ? 'Success' : 'Error',
                text: message
            });
        }

        async function showConfirm(icon, title, message, confirmText, cancelText) {
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

        async function showUnexpectedError(error) {
            console.error(error);

            await Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Unable to communicate with the server.'
            });
        }
    </script>
</body>
</html>
