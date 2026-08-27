document.addEventListener('input', (event) => {
    if (event.target.name === 'likes') {
        document.querySelector('#likes-value').textContent = event.target.value;
    }
});

document.addEventListener('change', (event) => {
    const control = event.target.closest('#movie-toolbar select, #movie-toolbar input:not([type="hidden"])');
    if (!control) {
        return;
    }
    control.form.requestSubmit();
});