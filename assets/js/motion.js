function easeOutCubic(t) {
    return 1 - (1 - t) ** 3;
}

/**
 * @param {HTMLElement} el
 * @param {number} end
 * @param {number} durationMs
 * @param {(n: number) => string} format
 */
function animateNumber(el, end, durationMs, format) {
    const start = performance.now();
    const from = 0;

    function frame(now) {
        const t = Math.min(1, (now - start) / durationMs);
        const v = from + (end - from) * easeOutCubic(t);
        el.textContent = format(v);
        if (t < 1) {
            requestAnimationFrame(frame);
        } else {
            el.textContent = format(end);
        }
    }
    requestAnimationFrame(frame);
}

function initCounters(root) {
    const els = root.querySelectorAll('[data-ooh-count-end]');
    if (!els.length) {
        return;
    }

    const io = new IntersectionObserver(
        (entries) => {
            entries.forEach((en) => {
                if (!en.isIntersecting) {
                    return;
                }
                const el = en.target;
                if (el.dataset.oohCountDone === '1') {
                    return;
                }
                el.dataset.oohCountDone = '1';
                io.unobserve(el);

                const end = parseFloat(el.dataset.oohCountEnd || '0');
                const suffix = el.dataset.oohCountSuffix || '';
                const decimals = parseInt(el.dataset.oohCountDecimals || '0', 10);
                const duration = parseInt(el.dataset.oohCountDuration || '1600', 10);

                const format = (n) => {
                    const fixed = decimals > 0 ? n.toFixed(decimals) : String(Math.round(n));
                    const withComma = fixed.replace('.', ',');
                    return withComma + suffix;
                };

                animateNumber(el, end, duration, format);
            });
        },
        { threshold: 0.35, rootMargin: '0px' },
    );

    els.forEach((el) => io.observe(el));
}

function initReveals(root) {
    const els = root.querySelectorAll('.ooh-reveal');
    if (!els.length) {
        return;
    }

    const io = new IntersectionObserver(
        (entries) => {
            entries.forEach((en) => {
                if (en.isIntersecting) {
                    en.target.classList.add('is-visible');
                    io.unobserve(en.target);
                }
            });
        },
        { threshold: 0.1, rootMargin: '0px 0px -6% 0px' },
    );

    els.forEach((el) => io.observe(el));
}

/* ----------------------------------------------------------------------
   НОВЫЕ хелперы дизайн-системы v2: [data-reveal] / [data-count] / [data-cycler].
   Работают на всех страницах (вызываются из initPageMotion).
   ---------------------------------------------------------------------- */

function initDataReveal(root) {
    const els = root.querySelectorAll('[data-reveal]');
    if (!els.length) {
        return;
    }
    if (prefersReducedMotion()) {
        els.forEach((el) => el.classList.add('in'));
        return;
    }

    const io = new IntersectionObserver(
        (entries) => {
            entries.forEach((en) => {
                if (en.isIntersecting) {
                    en.target.classList.add('in');
                    io.unobserve(en.target);
                }
            });
        },
        { threshold: 0.15, rootMargin: '0px 0px -8% 0px' },
    );

    els.forEach((el) => {
        if (el.dataset.revealBound === '1') {
            return;
        }
        el.dataset.revealBound = '1';
        io.observe(el);
    });

    registerCleanup(() => io.disconnect());
}

function animateDataCount(el) {
    const end = parseFloat(el.dataset.count || '0');
    if (prefersReducedMotion()) {
        el.textContent = String(end);
        return;
    }
    const start = performance.now();
    const dur = 1400;
    function frame(now) {
        const t = Math.min(1, (now - start) / dur);
        el.textContent = String(Math.round(end * easeOutCubic(t)));
        if (t < 1) {
            requestAnimationFrame(frame);
        } else {
            el.textContent = String(end);
        }
    }
    requestAnimationFrame(frame);
}

function initDataCount(root) {
    const els = root.querySelectorAll('[data-count]');
    if (!els.length) {
        return;
    }

    const io = new IntersectionObserver(
        (entries) => {
            entries.forEach((en) => {
                if (en.isIntersecting) {
                    animateDataCount(en.target);
                    io.unobserve(en.target);
                }
            });
        },
        { threshold: 0.6 },
    );

    els.forEach((el) => {
        if (el.dataset.countBound === '1') {
            return;
        }
        el.dataset.countBound = '1';
        io.observe(el);
    });

    registerCleanup(() => io.disconnect());
}

function initFormatCycler(root) {
    const slots = root.querySelectorAll('[data-cycler]');
    if (!slots.length) {
        return;
    }
    const reduce = prefersReducedMotion();

    slots.forEach((slot) => {
        if (slot.dataset.cyclerBound === '1') {
            return;
        }
        slot.dataset.cyclerBound = '1';

        const words = (slot.dataset.cycler || '').split('|').map((s) => s.trim()).filter(Boolean);
        if (!words.length) {
            return;
        }
        slot.innerHTML = '';
        const els = words.map((w, i) => {
            const s = document.createElement('span');
            s.textContent = w;
            if (i === 0) s.className = 'on';
            slot.appendChild(s);
            return s;
        });

        if (reduce || els.length < 2) {
            return;
        }
        let i = 0;
        const id = setInterval(() => {
            els[i].classList.remove('on');
            i = (i + 1) % els.length;
            els[i].classList.add('on');
        }, 2300);
        registerCleanup(() => clearInterval(id));
    });
}

function bootHomeHero(root) {
    const home = root.querySelector('.home-ooh');
    if (!home) {
        return;
    }
    home.classList.remove('is-booted');
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            home.classList.add('is-booted');
        });
    });
}

/**
 * Анимации появления и счётчики (главная и внутренние страницы).
 */
export function initPageMotion(root = document) {
    cleanupMotion();
    // на всякий случай — если базовый inline-скрипт не отработал
    document.documentElement.classList.add('js');
    bootHomeHero(root);
    initReveals(root);
    initCounters(root);
    initDataReveal(root);
    initDataCount(root);
    initFormatCycler(root);
    initScrollProgress();
    initSectionNavHighlight();
    initParallax(root);
    initSpotlight(root);
}

function clamp(n, min, max) {
    return Math.min(max, Math.max(min, n));
}

function getMotionState() {
    const key = '__oohMotionState__';
    if (!window[key]) {
        window[key] = { cleanups: [] };
    }
    return window[key];
}

function registerCleanup(fn) {
    if (typeof fn !== 'function') {
        return;
    }
    getMotionState().cleanups.push(fn);
}

function cleanupMotion() {
    const state = getMotionState();
    if (!state.cleanups || !state.cleanups.length) {
        return;
    }
    state.cleanups.forEach((fn) => {
        try {
            fn();
        } catch (e) {
            // no-op
        }
    });
    state.cleanups = [];
}

function prefersReducedMotion() {
    return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

function initScrollProgress() {
    const progressEl = document.getElementById('ooh-scroll-progress');
    if (!progressEl || prefersReducedMotion()) {
        return;
    }

    const update = () => {
        const doc = document.documentElement;
        const scrollTop = window.scrollY || doc.scrollTop || 0;
        const height = doc.scrollHeight - window.innerHeight;
        const progress = height > 0 ? clamp(scrollTop / height, 0, 1) : 0;
        progressEl.style.transform = `scaleX(${progress})`;
    };

    let rafId = 0;
    const onScroll = () => {
        if (rafId) return;
        rafId = window.requestAnimationFrame(() => {
            rafId = 0;
            update();
        });
    };

    update();
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', update);

    registerCleanup(() => {
        window.removeEventListener('scroll', onScroll);
        window.removeEventListener('resize', update);
        if (rafId) {
            window.cancelAnimationFrame(rafId);
        }
    });
}

function initSectionNavHighlight() {
    const nav = document.querySelector('nav[aria-label="Разделы главной"]');
    if (!nav || prefersReducedMotion()) {
        return;
    }

    const links = Array.from(nav.querySelectorAll('a[href^="#"]'));
    if (!links.length) {
        return;
    }

    const targets = links
        .map((a) => {
            const href = a.getAttribute('href') || '';
            const id = href.startsWith('#') ? href.slice(1) : '';
            return { id, link: a };
        })
        .filter((x) => x.id && document.getElementById(x.id));

    const clearActive = () => {
        links.forEach((l) => l.classList.remove('ooh-nav-active'));
    };

    const io = new IntersectionObserver(
        (entries) => {
            const visible = entries
                .filter((e) => e.isIntersecting)
                .sort((a, b) => (b.intersectionRatio || 0) - (a.intersectionRatio || 0))[0];
            if (!visible) return;

            clearActive();
            const id = visible.target.id;
            const match = targets.find((t) => t.id === id);
            if (match) {
                match.link.classList.add('ooh-nav-active');
            }
        },
        { threshold: [0.2, 0.35, 0.5], rootMargin: '-20% 0px -55% 0px' },
    );

    targets.forEach((t) => io.observe(document.getElementById(t.id)));

    const setInitialActive = () => {
        const topBias = 140;
        let best = null;
        targets.forEach((t) => {
            const section = document.getElementById(t.id);
            if (!section) return;
            const rect = section.getBoundingClientRect();
            const dist = Math.abs(rect.top - topBias);
            if (!best || dist < best.dist) {
                best = { dist, link: t.link };
            }
        });
        clearActive();
        if (best) {
            best.link.classList.add('ooh-nav-active');
        }
    };

    setInitialActive();
    window.addEventListener('resize', setInitialActive);
    registerCleanup(() => io.disconnect());
    registerCleanup(() => window.removeEventListener('resize', setInitialActive));
}

function initParallax(root) {
    const els = root.querySelectorAll('[data-ooh-parallax]');
    if (!els.length || prefersReducedMotion()) {
        return;
    }

    const update = () => {
        els.forEach((el) => {
            const factor = parseFloat(el.dataset.oohParallax || '0.12');
            const maxPx = parseFloat(el.dataset.oohParallaxMax || '26');
            const rect = el.getBoundingClientRect();
            const viewportCenter = window.innerHeight * 0.5;
            const elCenter = rect.top + rect.height * 0.5;
            const distance = viewportCenter - elCenter;
            const ty = clamp(distance * factor, -maxPx, maxPx);
            el.style.setProperty('--ooh-parallax-ty', `${ty.toFixed(1)}px`);
        });
    };

    let rafId = 0;
    const onScroll = () => {
        if (rafId) return;
        rafId = window.requestAnimationFrame(() => {
            rafId = 0;
            update();
        });
    };

    update();
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', update);

    registerCleanup(() => {
        window.removeEventListener('scroll', onScroll);
        window.removeEventListener('resize', update);
        if (rafId) {
            window.cancelAnimationFrame(rafId);
        }
    });
}

function initSpotlight(root) {
    if (prefersReducedMotion()) {
        return;
    }
    if (!window.matchMedia || !window.matchMedia('(pointer: fine)').matches) {
        return;
    }

    const els = root.querySelectorAll('.ooh-spotlight');
    if (!els.length) {
        return;
    }

    let cleanupFns = [];

    els.forEach((el) => {
        el.classList.remove('is-spotlight-on');

        let rafId = 0;
        let nextX = 0;
        let nextY = 0;

        const apply = () => {
            rafId = 0;
            el.style.setProperty('--ooh-spot-x', `${nextX}px`);
            el.style.setProperty('--ooh-spot-y', `${nextY}px`);
        };

        const onMove = (e) => {
            const rect = el.getBoundingClientRect();
            nextX = e.clientX - rect.left;
            nextY = e.clientY - rect.top;
            el.classList.add('is-spotlight-on');

            if (rafId) return;
            rafId = window.requestAnimationFrame(apply);
        };

        const onLeave = () => {
            el.classList.remove('is-spotlight-on');
        };

        el.addEventListener('mousemove', onMove, { passive: true });
        el.addEventListener('mouseleave', onLeave, { passive: true });

        cleanupFns.push(() => {
            el.removeEventListener('mousemove', onMove);
            el.removeEventListener('mouseleave', onLeave);
            if (rafId) {
                window.cancelAnimationFrame(rafId);
            }
        });
    });

    registerCleanup(() => cleanupFns.forEach((fn) => fn()));
}
