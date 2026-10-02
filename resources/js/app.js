import Alpine from 'alpinejs';

window.Alpine = Alpine;

const pad = (n) => String(n).padStart(2, '0');

/**
 * Clock-in countdown driven by the server's ShiftReminderService payload.
 * Phases: calm (> warning window) → warning (last 10 min) → final (last 10 s) → late.
 * The clock is corrected by the server/device time offset so phones with a wrong clock still count correctly.
 */
Alpine.data('shiftCountdown', (reminder, options = {}) => ({
    r: reminder,
    offset: reminder.server_now - Date.now(),
    nowMs: Date.now(),
    remaining: null,
    phase: 'idle',
    sheetOpen: false,
    finalOpen: false,
    dismissed: { sheet: false, final: false },
    timer: null,

    init() {
        this.storageKey = 'sems-countdown:' + (reminder.start || 'none');
        try {
            Object.assign(this.dismissed, JSON.parse(sessionStorage.getItem(this.storageKey) || '{}'));
        } catch (e) {
            // Storage can be unavailable (private mode) — dismissals just won't persist.
        }
        this.tick();
        this.timer = setInterval(() => this.tick(), 250);
    },

    destroy() {
        clearInterval(this.timer);
    },

    tick() {
        this.nowMs = Date.now() + this.offset;

        if (this.r.state !== 'upcoming') {
            this.phase = 'idle';
            return;
        }

        this.remaining = (Date.parse(this.r.start) - this.nowMs) / 1000;

        if (this.r.end && this.nowMs >= Date.parse(this.r.end)) {
            // The shift is over — nothing left to count down to.
            this.phase = 'ended';
            this.finalOpen = false;
            this.sheetOpen = false;
            return;
        }

        if (this.remaining <= 0) {
            // Too late: get every overlay out of the way immediately.
            this.phase = 'late';
            this.finalOpen = false;
            this.sheetOpen = false;
            return;
        }

        if (this.remaining <= this.r.final_seconds) {
            this.phase = 'final';
            this.sheetOpen = false;
            if (!this.dismissed.final && options.final !== false) this.finalOpen = true;
            return;
        }

        if (this.remaining <= this.r.warning_seconds) {
            this.phase = 'warning';
            const isPhone = window.matchMedia('(max-width: 1023px)').matches;
            if (options.sheet && isPhone && !this.dismissed.sheet) this.sheetOpen = true;
            return;
        }

        this.phase = 'calm';
    },

    dismiss(which) {
        this.dismissed[which] = true;
        if (which === 'sheet') this.sheetOpen = false;
        if (which === 'final') this.finalOpen = false;
        try {
            sessionStorage.setItem(this.storageKey, JSON.stringify(this.dismissed));
        } catch (e) {}
    },

    get clock() {
        const d = new Date(this.nowMs);
        return `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
    },

    get countdown() {
        const s = Math.max(0, Math.ceil(this.remaining ?? 0));
        return `${pad(Math.floor(s / 60))}:${pad(s % 60)}`;
    },

    get finalNumber() {
        return Math.max(0, Math.ceil(this.remaining ?? 0));
    },

    /** 1 → 0 across the warning window, for progress rings. */
    get progress() {
        return Math.min(1, Math.max(0, (this.remaining ?? 0) / this.r.warning_seconds));
    },

    get untilLabel() {
        const m = Math.max(0, Math.round((this.remaining ?? 0) / 60));
        if (m < 60) return `${m} min`;
        return `${Math.floor(m / 60)}h ${pad(m % 60)}m`;
    },

    get lateLabel() {
        const m = Math.max(1, Math.round(-(this.remaining ?? 0) / 60));
        return m < 60 ? `${m} min` : `${Math.floor(m / 60)}h ${pad(m % 60)}m`;
    },

    get urgent() {
        return this.phase === 'warning' && this.remaining <= 180;
    },
}));

Alpine.start();

/* ------------------------------------------------------------------
 | Interaction layer: small touches that make the glass feel alive.
 * ------------------------------------------------------------------ */

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/** Specular light on glass cards follows the pointer (mouse / pen only). */
function initGlassSpotlight() {
    if (!window.matchMedia('(hover: hover)').matches) return;

    let frame = null;
    let current = null;

    document.addEventListener('pointermove', (event) => {
        if (frame) return;
        frame = requestAnimationFrame(() => {
            frame = null;
            const card = event.target.closest?.('.card, .glass');
            if (current && current !== card) {
                current.style.removeProperty('--mx');
                current.style.removeProperty('--my');
            }
            current = card;
            if (!card) return;
            const rect = card.getBoundingClientRect();
            card.style.setProperty('--mx', `${event.clientX - rect.left}px`);
            card.style.setProperty('--my', `${event.clientY - rect.top}px`);
        });
    }, { passive: true });
}

/** Cards rise in one after another the first time they scroll into view. */
function initReveal() {
    if (reduceMotion || !('IntersectionObserver' in window)) return;

    const targets = [...document.querySelectorAll('main .card, main .table-modern tbody tr')]
        // Nested cards animate with their parent; cards inside modals are left alone.
        .filter((el) => !el.parentElement.closest('.card, [role="dialog"], [x-show]'));

    let batch = 0;
    const observer = new IntersectionObserver((entries) => {
        entries.filter((e) => e.isIntersecting).forEach((entry) => {
            const el = entry.target;
            el.style.setProperty('--i', batch++);
            el.classList.add('reveal');
            el.addEventListener('animationend', () => el.classList.remove('reveal'), { once: true });
            observer.unobserve(el);
        });
        batch = 0;
    }, { rootMargin: '0px 0px -8% 0px' });

    targets.forEach((el) => observer.observe(el));
}

/** Big numbers on stat cards count up from zero (keeps any "RM", "days", decimals, commas). */
function initCountUp() {
    document.querySelectorAll('[data-countup]').forEach((el) => {
        const text = el.textContent.trim();
        const match = text.match(/-?[\d,]*\.?\d+/);
        if (!match || reduceMotion) return;

        const raw = match[0];
        // Times ("08:52"), dates and zero-padded codes are not quantities.
        if (text.includes(':') || /^0\d/.test(raw) || /[a-z]{3}\s*\d/i.test(text.slice(0, match.index + 1))) return;
        const target = parseFloat(raw.replace(/,/g, ''));
        if (!Number.isFinite(target) || target === 0) return;

        const decimals = (raw.split('.')[1] || '').length;
        const grouped = raw.includes(',');
        const format = (n) => n.toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals, useGrouping: grouped });
        const [before, after] = [text.slice(0, match.index), text.slice(match.index + raw.length)];
        const duration = 900;
        const start = performance.now();

        const step = (now) => {
            const t = Math.min(1, (now - start) / duration);
            const eased = 1 - Math.pow(1 - t, 4);
            el.textContent = before + format(target * eased) + after;
            if (t < 1) requestAnimationFrame(step);
        };
        el.textContent = before + format(0) + after;
        requestAnimationFrame(step);
    });
}

/** On phones, tables become cards: each cell is labelled with its column heading. */
function labelTableCells() {
    document.querySelectorAll('.table-modern').forEach((table) => {
        const headings = [...table.querySelectorAll('thead th')].map((th) => th.textContent.trim());
        table.querySelectorAll('tbody tr').forEach((row) => {
            [...row.children].forEach((cell, i) => {
                if (cell.hasAttribute('colspan')) return;
                if (headings[i]) cell.dataset.label = headings[i];
            });
        });
    });
}

/** The tab bar's glass lens slides from the previous tab to the new one, stretching like liquid. */
function animateTabIndicator() {
    const bar = document.querySelector('.tabbar');
    const lens = bar?.querySelector('.tab-indicator');
    if (!bar || reduceMotion) return;

    const index = getComputedStyle(bar).getPropertyValue('--tab-index').trim();
    let previous = null;
    try {
        previous = sessionStorage.getItem('sems-tab');
        sessionStorage.setItem('sems-tab', lens ? index : '');
    } catch (e) {}

    if (!lens || !previous || previous === index) return;

    bar.style.setProperty('--tab-index', previous);
    requestAnimationFrame(() => requestAnimationFrame(() => {
        lens.classList.add('is-moving');
        lens.addEventListener('animationend', () => lens.classList.remove('is-moving'), { once: true });
        bar.style.setProperty('--tab-index', index);
    }));
}

/**
 * Real Liquid Glass refraction (Chromium): each [data-refract] element gets an SVG
 * displacement lens built for its exact size and corner radius. Near the rim the
 * backdrop is bent outward, as light is through the curved edge of a glass slab;
 * the middle stays undistorted. Safari / Firefox keep the plain clear glass.
 */
function initRefraction() {
    const ua = navigator.userAgent;
    const chromium = /(Chrome|Edg)\//.test(ua) && !/(CriOS|FxiOS|EdgiOS|Firefox)/.test(ua);
    const lessGlass = window.matchMedia('(prefers-reduced-transparency: reduce)').matches;
    const targets = [...document.querySelectorAll('[data-refract]')];
    if (!chromium || lessGlass || !targets.length) return;

    const NS = 'http://www.w3.org/2000/svg';
    const defs = document.createElementNS(NS, 'svg');
    defs.setAttribute('aria-hidden', 'true');
    defs.setAttribute('width', '0');
    defs.setAttribute('height', '0');
    defs.style.position = 'absolute';
    document.body.appendChild(defs);

    let seq = 0;
    const maps = new Map(); // size → data URL, so a bar that shrinks and grows reuses its lenses

    const lensMap = (w, h, radius, bezel) => {
        const key = `${w}x${h}r${radius}b${bezel}`;
        if (maps.has(key)) return maps.get(key);
        const canvas = document.createElement('canvas');
        canvas.width = w;
        canvas.height = h;
        const ctx = canvas.getContext('2d');
        const image = ctx.createImageData(w, h);
        const hw = w / 2;
        const hh = h / 2;

        for (let y = 0; y < h; y++) {
            for (let x = 0; x < w; x++) {
                // Signed distance to the rounded rectangle (negative inside) and its outward normal.
                const px = x + 0.5 - hw;
                const py = y + 0.5 - hh;
                const qx = Math.abs(px) - (hw - radius);
                const qy = Math.abs(py) - (hh - radius);
                let nx;
                let ny;
                let dist;
                if (qx > 0 && qy > 0) {
                    const len = Math.hypot(qx, qy) || 1;
                    nx = qx / len;
                    ny = qy / len;
                    dist = len - radius;
                } else if (qx > qy) {
                    nx = 1;
                    ny = 0;
                    dist = qx - radius;
                } else {
                    nx = 0;
                    ny = 1;
                    dist = qy - radius;
                }
                nx *= Math.sign(px) || 1;
                ny *= Math.sign(py) || 1;

                const depth = -dist; // distance in from the rim
                const strength = depth < bezel ? Math.pow(1 - Math.max(0, depth) / bezel, 2) : 0;
                const i = (y * w + x) * 4;
                image.data[i] = 128 + nx * strength * 127;
                image.data[i + 1] = 128 + ny * strength * 127;
                image.data[i + 2] = 128;
                image.data[i + 3] = 255;
            }
        }

        ctx.putImageData(image, 0, 0);
        maps.set(key, canvas.toDataURL());
        return maps.get(key);
    };

    const apply = (el) => {
        // While the menu is open the glass is frosted by CSS; the lens is rebuilt once it closes.
        if (el.closest('.menu-open')) return;
        el.style.backdropFilter = '';
        el.style.webkitBackdropFilter = '';
        const style = getComputedStyle(el);
        const w = Math.round(el.offsetWidth);
        const h = Math.round(el.offsetHeight);
        if (!w || !h || style.display === 'none') return;

        const radius = Math.min(parseFloat(style.borderTopLeftRadius) || 0, w / 2, h / 2);
        const bezel = Math.max(8, Math.min(24, Math.min(w, h) * 0.36));
        const id = (el.dataset.refractId ||= `lg-lens-${seq++}`);

        defs.querySelector(`#${id}`)?.remove();
        const filter = document.createElementNS(NS, 'filter');
        filter.id = id;
        filter.setAttribute('filterUnits', 'userSpaceOnUse');
        filter.setAttribute('primitiveUnits', 'userSpaceOnUse');
        filter.setAttribute('color-interpolation-filters', 'sRGB');
        for (const [k, v] of Object.entries({ x: 0, y: 0, width: w, height: h })) filter.setAttribute(k, v);

        const map = document.createElementNS(NS, 'feImage');
        map.setAttribute('href', lensMap(w, h, radius, bezel));
        for (const [k, v] of Object.entries({ x: 0, y: 0, width: w, height: h, result: 'lens', preserveAspectRatio: 'none' })) map.setAttribute(k, v);

        // Red, green and blue bend by slightly different amounts — the faint colour
        // fringing (chromatic aberration) that real glass edges show.
        const node = (tag, attrs) => {
            const el = document.createElementNS(NS, tag);
            for (const [k, v] of Object.entries(attrs)) el.setAttribute(k, v);
            return el;
        };
        const strength = bezel * 2.2;
        const channel = (name, scale, matrix) => [
            node('feDisplacementMap', { in: 'SourceGraphic', in2: 'lens', scale: Math.round(scale), xChannelSelector: 'R', yChannelSelector: 'G', result: `${name}-bent` }),
            node('feColorMatrix', { in: `${name}-bent`, type: 'matrix', values: matrix, result: name }),
        ];

        filter.append(
            map,
            ...channel('red', strength * 1.12, '1 0 0 0 0  0 0 0 0 0  0 0 0 0 0  0 0 0 1 0'),
            ...channel('green', strength, '0 0 0 0 0  0 1 0 0 0  0 0 0 0 0  0 0 0 1 0'),
            ...channel('blue', strength * 0.88, '0 0 0 0 0  0 0 0 0 0  0 0 1 0 0  0 0 0 1 0'),
            node('feBlend', { in: 'red', in2: 'green', mode: 'screen', result: 'rg' }),
            node('feBlend', { in: 'rg', in2: 'blue', mode: 'screen' }),
        );
        defs.appendChild(filter);

        const base = style.backdropFilter && style.backdropFilter !== 'none' ? style.backdropFilter : '';
        el.style.backdropFilter = `url(#${id}) ${base}`.trim();
    };

    const pending = new Map();
    const observer = new ResizeObserver((entries) => {
        entries.forEach(({ target }) => {
            clearTimeout(pending.get(target));
            pending.set(target, setTimeout(() => apply(target), 120));
        });
    });

    targets.forEach((el) => {
        apply(el);
        observer.observe(el);
    });
}

/** iOS 26 scroll chrome: compact title + edge blur once scrolled; tab bar shrinks while scrolling down. */
function initScrollChrome() {
    const root = document.documentElement;
    let lastY = window.scrollY;
    let ticking = false;

    const update = () => {
        ticking = false;
        const y = window.scrollY;
        root.classList.toggle('is-scrolled', y > 56);
        if (Math.abs(y - lastY) > 10) {
            root.classList.toggle('tabbar-min', y > lastY && y > 140);
            lastY = y;
        }
    };

    window.addEventListener('scroll', () => {
        if (!ticking) {
            ticking = true;
            requestAnimationFrame(update);
        }
    }, { passive: true });
    update();
}

document.addEventListener('DOMContentLoaded', () => {
    labelTableCells();
    initGlassSpotlight();
    initReveal();
    initCountUp();
    animateTabIndicator();
    initScrollChrome();
    initRefraction();
});
