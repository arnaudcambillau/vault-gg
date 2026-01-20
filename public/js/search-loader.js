document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('search-form');
    const loader = document.getElementById('search-loader');

    if (!form || !loader) return;

    // Cacher le loader par défaut
    loader.classList.add('hidden');

    // Afficher le loader au submit
    form.addEventListener('submit', () => {
        loader.classList.remove('hidden');
    });
});
