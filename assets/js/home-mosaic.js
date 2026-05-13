/**
 * Отложенная подстановка background-image для блоков .home-mosaic--lazy (меньше трафика до скролла).
 */
export function initHomeMosaicLazy() {
    const nodes = document.querySelectorAll('[data-home-mosaic-lazy]');
    if (!nodes.length) {
        return;
    }

    const applyBg = (el) => {
        const webp = el.dataset.bgWebp;
        const fallback = el.dataset.bg;
        const mime = el.dataset.bgMime || 'image/png';
        if (!fallback) {
            return;
        }
        if (webp) {
            el.style.backgroundImage = `image-set(url("${webp}") type("image/webp"), url("${fallback}") type("${mime}"))`;
        } else {
            el.style.backgroundImage = `url("${fallback}")`;
        }
        el.classList.add('home-mosaic--loaded');
    };

    if (!('IntersectionObserver' in window)) {
        nodes.forEach(applyBg);
        return;
    }

    const io = new IntersectionObserver(
        (entries, observer) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }
                applyBg(entry.target);
                observer.unobserve(entry.target);
            });
        },
        { rootMargin: '140px 0px', threshold: 0.01 },
    );

    nodes.forEach((n) => io.observe(n));
}
