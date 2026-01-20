document.addEventListener('DOMContentLoaded', () => {
    const alerts = document.querySelectorAll('[data-flash]');

    alerts.forEach(alert => {
        setTimeout(() => {
            alert.classList.add(
                'opacity-0',
                '-translate-y-2',
                'transition-all',
                'duration-500'
            );

            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 2000);
    });
});
