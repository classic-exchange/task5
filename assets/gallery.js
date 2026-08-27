let observer;

function initGallery() {
    observer?.disconnect();
    const loader = document.getElementById('gallery-loader');
    if (!loader) {
        return;
    }
    observer = new IntersectionObserver(async (entries) => {
        if (!entries[0].isIntersecting) {
            return;
        }
        observer.unobserve(loader);
        const params = new URLSearchParams({
            mode: 'gallery',
            partial: '1',
            page: loader.dataset.page,
            seed: loader.dataset.seed,
            locale: loader.dataset.locale,
            likes: loader.dataset.likes,
            reviews: loader.dataset.reviews
        });
        const response = await fetch(`/movie?${params}`);
        const html = await response.text();
        document.getElementById('gallery').insertAdjacentHTML('beforeend', html);
        loader.dataset.page = Number(loader.dataset.page) + 1;
        observer.observe(loader);
    });
    observer.observe(loader);
}

initGallery();
document.addEventListener('turbo:load', initGallery);