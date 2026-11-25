export function showResult({ title, message }, label = null, timeoutMs = null) {
    const dialog = document.createElement('dialog');

    dialog.innerHTML = `
        <h2></h2>
        <p></p>
        <p role="timer" hidden></p>
        <form method="dialog">
            <button autofocus>OK</button>
        </form>
    `;

    dialog.querySelector('h2').textContent = title;
    dialog.querySelector('p').textContent = message;
    dialog.querySelector('button').textContent = label ?? 'OK';

    const countdown = dialog.querySelector('[role="timer"]');

    return new Promise((resolve) => {
        let countdownId;

        dialog.addEventListener('close', () => {
            clearInterval(countdownId);
            dialog.remove();
            resolve();
        }, { once: true });

        document.body.append(dialog);
        dialog.showModal()

        if (Number.isFinite(timeoutMs) && timeoutMs > 0) {
            const deadline = performance.now() + timeoutMs;

            const updateCountdown = () => {
                const remaningMs = deadline - performance.now();

                if (remaningMs <= 0) {
                    dialog.close();
                    return;
                }

                countdown.textContent = `Closes in ${Math.ceil(remaningMs / 1000)}s`;
            }

            countdown.hidden = false;
            updateCountdown();
            countdownId = setInterval(updateCountdown, 100);
        }
    });
}

export function showChoice(title, message, okLabel, cancelLabel) {
    const dialog = document.createElement('dialog');

    dialog.innerHTML = `
        <h2></h2>
        <p></p>
        <form method="dialog">
            <button type="submit" value="cancel" autofocus></button>
            <button type="submit" value="ok"></button>
        </form>
    `;

    dialog.querySelector('h2').textContent = title;
    dialog.querySelector('p').textContent = message;
    dialog.querySelector('[value="cancel"]').textContent = cancelLabel;
    dialog.querySelector('[value="ok"]').textContent = okLabel;

    return new Promise((resolve) => {
        dialog.addEventListener('close', () => {
            const confirmed = dialog.returnValue === 'ok';
            dialog.remove();
            resolve(confirmed);
        }, { once: true });

        document.body.append(dialog);
        dialog.showModal();
    });
}

export async function showUnexpectedError(title = null, message = null) {
    const choice = await showChoice(
        title ?? 'Something Went Wrong',
        message ?? 'An unexpected error occurred. Would you like to try again?',
        'Retry',
        'Cancel'
    );

    return choice;
}
