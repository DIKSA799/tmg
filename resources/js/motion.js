const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

export function initReveal() {
    const items = document.querySelectorAll('.reveal');
    if (items.length === 0) {
        return;
    }

    if (prefersReducedMotion || !('IntersectionObserver' in window)) {
        items.forEach((item) => item.classList.add('is-visible'));
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.12 });

    items.forEach((item) => observer.observe(item));
}

export function initCounters() {
    const counters = document.querySelectorAll('[data-count]');
    if (counters.length === 0) {
        return;
    }

    const format = new Intl.NumberFormat();

    const run = (element) => {
        const target = Number(element.dataset.count) || 0;

        if (prefersReducedMotion) {
            element.textContent = format.format(target);
            return;
        }

        const duration = 900;
        const start = performance.now();

        const tick = (now) => {
            const progress = Math.min(1, (now - start) / duration);
            const eased = 1 - (1 - progress) ** 3;
            element.textContent = format.format(Math.round(target * eased));

            if (progress < 1) {
                requestAnimationFrame(tick);
            }
        };

        requestAnimationFrame(tick);
    };

    if (!('IntersectionObserver' in window)) {
        counters.forEach(run);
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                run(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.4 });

    counters.forEach((counter) => observer.observe(counter));
}

export function initNavSpy() {
    const links = Array.from(document.querySelectorAll('[data-nav-link]'));
    const sections = links
        .map((link) => document.querySelector(link.getAttribute('href')))
        .filter(Boolean);

    if (sections.length === 0 || !('IntersectionObserver' in window)) {
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) {
                return;
            }

            links.forEach((link) => {
                link.classList.toggle('text-[color:var(--accent)]', link.getAttribute('href') === `#${entry.target.id}`);
            });
        });
    }, { rootMargin: '-45% 0px -50% 0px' });

    sections.forEach((section) => observer.observe(section));
}

export function initScramble() {
    const element = document.querySelector('[data-scramble]');

    if (element === null || prefersReducedMotion) {
        return;
    }

    const final = element.textContent;
    const characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    const duration = 1100;
    const start = performance.now();

    const frame = (now) => {
        const progress = Math.min(1, (now - start) / duration);
        let output = '';

        for (let index = 0; index < final.length; index += 1) {
            output += final[index] === ' '
                ? ' '
                : (index / final.length < progress ? final[index] : characters[Math.floor(Math.random() * characters.length)]);
        }

        element.textContent = output;

        if (progress < 1) {
            requestAnimationFrame(frame);
        } else {
            element.textContent = final;
        }
    };

    requestAnimationFrame(frame);
}
