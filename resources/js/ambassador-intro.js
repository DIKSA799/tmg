export function initDutiesCarousel() {
    const track = document.querySelector('[data-duties-track]');

    if (track === null) {
        return;
    }

    const prev = document.querySelector('[data-duties-prev]');
    const next = document.querySelector('[data-duties-next]');
    const hint = document.querySelector('[data-duties-hint]');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const behavior = reducedMotion ? 'auto' : 'smooth';

    const step = () => {
        const card = track.querySelector('li');
        if (card === null) {
            return track.clientWidth * 0.8;
        }

        const gap = Number.parseFloat(getComputedStyle(track).columnGap) || 0;

        return card.getBoundingClientRect().width + gap;
    };

    const sync = () => {
        const max = track.scrollWidth - track.clientWidth - 1;
        const scrollable = max > 1;

        if (prev !== null) {
            prev.disabled = !scrollable || track.scrollLeft <= 0;
        }

        if (next !== null) {
            next.disabled = !scrollable || track.scrollLeft >= max;
        }

        if (hint !== null) {
            hint.style.opacity = scrollable ? '' : '0';
        }
    };

    prev?.addEventListener('click', () => track.scrollBy({ left: -step(), behavior }));
    next?.addEventListener('click', () => track.scrollBy({ left: step(), behavior }));
    track.addEventListener('scroll', sync, { passive: true });
    window.addEventListener('resize', sync, { passive: true });

    sync();
}
