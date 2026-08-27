document.addEventListener('click', (event) => {
    const button = event.target.closest('.movie-expand');
    if (!button) {
        return;
    }
    const details = document.getElementById(button.dataset.target);
    const icon = button.querySelector('i');
    details.hidden = !details.hidden;
    icon.className = details.hidden ? 'bi bi-chevron-down' : 'bi bi-chevron-up';
});