const AUTOPLAY_MS = 3600;

/**
 * Swipeable duties carousel: native scroll-snap for touch, plus arrows,
 * pagination dots and a gentle autoplay so the rest of the list is discoverable.
 */
export function initDutiesCarousel() {
    const root = document.querySelector('[data-duties]');
    const track = root?.querySelector('[data-duties-track]');

    if (!root || !track) {
        return;
    }

    const slides = Array.from(track.children);
    if (slides.length === 0) {
        return;
    }

    const prev = root.querySelector('[data-duties-prev]');
    const next = root.querySelector('[data-duties-next]');
    const dotsWrap = root.querySelector('[data-duties-dots]');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    let index = 0;
    let paused = false;
    let timer = null;
    let frame = 0;

    const step = () => {
        const gap = Number.parseFloat(getComputedStyle(track).columnGap) || 0;
        return slides[0].getBoundingClientRect().width + gap;
    };

    const lastIndex = () => {
        const perView = Math.max(1, Math.round(track.clientWidth / step()));
        return Math.max(0, slides.length - perView);
    };

    const buildDots = () => {
        if (!dotsWrap) {
            return;
        }

        dotsWrap.replaceChildren();

        for (let position = 0; position <= lastIndex(); position += 1) {
            const dot = document.createElement('button');
            dot.type = 'button';
            dot.className = 'duties-dot';
            dot.setAttribute('aria-label', `Go to duty ${position + 1}`);
            dot.addEventListener('click', () => goTo(position));
            dotsWrap.appendChild(dot);
        }
    };

    const sync = () => {
        const current = Math.max(0, Math.min(lastIndex(), Math.round(track.scrollLeft / step())));
        index = current;

        dotsWrap?.querySelectorAll('.duties-dot').forEach((dot, position) => {
            dot.dataset.active = String(position === current);
        });

        if (prev) {
            prev.disabled = current <= 0;
        }

        if (next) {
            next.disabled = current >= lastIndex();
        }
    };

    const goTo = (target) => {
        const max = lastIndex();
        index = target > max ? 0 : target < 0 ? max : target;
        track.scrollTo({ left: index * step(), behavior: reducedMotion ? 'auto' : 'smooth' });
        sync();
    };

    const start = () => {
        if (reducedMotion || timer !== null) {
            return;
        }

        timer = window.setInterval(() => {
            if (!paused) {
                goTo(index + 1);
            }
        }, AUTOPLAY_MS);
    };

    const stop = () => {
        if (timer !== null) {
            window.clearInterval(timer);
            timer = null;
        }
    };

    const pause = () => {
        paused = true;
    };
    const resume = () => {
        paused = false;
    };

    prev?.addEventListener('click', () => goTo(index - 1));
    next?.addEventListener('click', () => goTo(index + 1));

    track.addEventListener('scroll', () => {
        if (frame) {
            return;
        }

        frame = requestAnimationFrame(() => {
            frame = 0;
            sync();
        });
    }, { passive: true });

    root.addEventListener('pointerenter', pause);
    root.addEventListener('pointerleave', resume);
    root.addEventListener('focusin', pause);
    root.addEventListener('focusout', resume);
    root.addEventListener('pointerdown', pause, { passive: true });
    root.addEventListener('pointerup', () => window.setTimeout(resume, 4000), { passive: true });
    document.addEventListener('visibilitychange', () => {
        paused = document.hidden;
    });

    window.addEventListener('resize', () => {
        buildDots();
        sync();
    }, { passive: true });

    buildDots();
    sync();

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(([entry]) => {
            if (entry.isIntersecting) {
                start();
            } else {
                stop();
            }
        }, { threshold: 0.25 });

        observer.observe(root);
    } else {
        start();
    }
}
