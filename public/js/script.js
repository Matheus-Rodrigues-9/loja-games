document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.js-confirm-delete').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const item = form.dataset.item || 'este registro';
            if (!window.confirm(`Tem certeza que deseja excluir "${item}"? Essa ação não pode ser desfeita.`)) {
                event.preventDefault();
            }
        });
    });
});
