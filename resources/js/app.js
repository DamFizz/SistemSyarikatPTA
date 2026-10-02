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

/* ------------------------------------------------------------------
 | "Install the SEMS app" (Progressive Web App).
 | Chrome/Edge fire beforeinstallprompt when the site can be installed; we keep the
 | event so the Install button on My Profile can open the browser's own dialog.
 | Safari and Firefox never fire it, so the card shows the manual steps instead.
 * ------------------------------------------------------------------ */
let installPrompt = null;

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    installPrompt = event;
    window.dispatchEvent(new CustomEvent('sems:installable'));
});

window.addEventListener('appinstalled', () => {
    installPrompt = null;
    window.dispatchEvent(new CustomEvent('sems:installed'));
});

if ('serviceWorker' in navigator && window.isSecureContext) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
}

const detectPlatform = () => {
    const ua = navigator.userAgent;
    const ios = /iPhone|iPad|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    if (ios) return 'ios';
    if (/SamsungBrowser/.test(ua)) return 'samsung';
    if (/Android/.test(ua)) return /Firefox/.test(ua) ? 'android-firefox' : 'android';
    if (/Firefox/.test(ua)) return 'desktop-firefox';
    if (/Edg\//.test(ua)) return 'desktop-edge';
    if (/Safari/.test(ua) && !/Chrome|Chromium/.test(ua)) return 'desktop-safari';
    return 'desktop-chrome';
};

Alpine.data('installApp', () => ({
    canPrompt: !!installPrompt,
    installed: window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true,
    platform: detectPlatform(),
    busy: false,
    declined: false,

    init() {
        window.addEventListener('sems:installable', () => { this.canPrompt = true; });
        window.addEventListener('sems:installed', () => { this.installed = true; this.canPrompt = false; });
    },

    async install() {
        if (!installPrompt || this.busy) return;
        this.busy = true;
        installPrompt.prompt();
        const { outcome } = await installPrompt.userChoice;
        installPrompt = null;
        this.canPrompt = false;
        this.busy = false;
        if (outcome === 'accepted') this.installed = true;
        else this.declined = true;
    },
}));

/*
 | Photo lightbox (selfies etc.). Any link with [data-lightbox] opens its image in a
 | glass popup on the same page — the picture zooms out of its thumbnail and back.
 | Links sharing data-lightbox="<group>" can be flicked through with the arrows/swipe.
 | Without JavaScript the link still opens the image normally.
 */
Alpine.data('lightbox', () => ({
    open: false,
    closing: false,
    items: [],
    index: 0,
    loaded: false,
    origin: null,
    direction: 0,
    sliding: false,
    touchX: null,

    get item() {
        return this.items[this.index] ?? null;
    },

    init() {
        document.addEventListener('click', (event) => {
            const link = event.target.closest?.('a[data-lightbox]');
            if (!link || event.metaKey || event.ctrlKey || event.shiftKey) return;
            event.preventDefault();
            this.show(link);
        });
    },

    show(link) {
        const group = link.dataset.lightbox;
        const links = group ? [...document.querySelectorAll(`a[data-lightbox="${CSS.escape(group)}"]`)] : [link];
        this.items = links.map((a) => ({ src: a.href, caption: a.dataset.caption || a.title || '', thumb: a }));
        this.index = Math.max(0, links.indexOf(link));
        this.loaded = false;
        this.closing = false;
        this.direction = 0;
        this.origin = link.getBoundingClientRect();
        this.open = true;
        navigator.vibrate?.(6);
    },

    /**
     * Runs when the picture has loaded. On open it zooms out of its thumbnail; after an
     * arrow / swipe it slides in from the side it is coming from. Transform only (GPU).
     */
    reveal() {
        this.loaded = true;
        const img = this.$refs.img;
        if (!img || reduceMotion) return;

        if (this.origin) {
            const to = img.getBoundingClientRect();
            const from = this.origin;
            this.origin = null;
            img.animate([
                { transform: `translate(${from.left + from.width / 2 - (to.left + to.width / 2)}px, ${from.top + from.height / 2 - (to.top + to.height / 2)}px) scale(${from.width / to.width})`, borderRadius: '40%', opacity: 0.6 },
                { transform: 'none', borderRadius: '1.5rem', opacity: 1 },
            ], { duration: 420, easing: 'cubic-bezier(0.3, 1.2, 0.4, 1)' });
        } else if (this.direction) {
            img.animate([
                { transform: `translateX(${this.direction * 70}px) scale(0.94)`, opacity: 0 },
                { transform: 'none', opacity: 1 },
            ], { duration: 340, easing: 'cubic-bezier(0.25, 1.15, 0.4, 1)' });
            this.direction = 0;
        }
    },

    close() {
        if (!this.open || this.closing) return;
        this.closing = true;

        const finish = () => {
            if (!this.open) return;
            this.open = false;
            this.closing = false;
        };

        const img = this.$refs.img;
        const thumb = this.item?.thumb;
        const from = img?.getBoundingClientRect();
        const to = thumb?.getBoundingClientRect();
        const visible = to && to.bottom > 0 && to.top < window.innerHeight && to.width > 0;

        if (img && from && from.width && visible && !reduceMotion) {
            img.animate([
                { transform: 'none', opacity: 1 },
                { transform: `translate(${to.left + to.width / 2 - (from.left + from.width / 2)}px, ${to.top + to.height / 2 - (from.top + from.height / 2)}px) scale(${to.width / from.width})`, borderRadius: '40%', opacity: 0.4 },
            ], { duration: 280, easing: 'cubic-bezier(0.4, 0, 0.2, 1)', fill: 'forwards' }).finished.then(finish);
        } else if (img && !reduceMotion) {
            img.animate([{ opacity: 1, transform: 'none' }, { opacity: 0, transform: 'scale(0.9)' }], { duration: 200, easing: 'ease-in', fill: 'forwards' }).finished.then(finish);
        } else {
            finish();
        }

        setTimeout(finish, 450); // in case animations are paused (e.g. a background tab)
    },

    /** Slide the current picture out to one side, then bring the next one in from the other. */
    step(delta) {
        if (this.items.length < 2 || this.sliding || this.closing) return;
        const next = (this.index + delta + this.items.length) % this.items.length;
        const img = this.$refs.img;
        navigator.vibrate?.(5);

        const swap = () => {
            this.sliding = false;
            this.direction = delta;
            this.loaded = false;
            this.index = next;
        };

        if (!img || reduceMotion) {
            swap();
            return;
        }

        this.sliding = true;
        img.animate([
            { transform: 'none', opacity: 1 },
            { transform: `translateX(${-delta * 70}px) scale(0.94)`, opacity: 0 },
        ], { duration: 170, easing: 'cubic-bezier(0.4, 0, 1, 1)', fill: 'forwards' }).finished.then((animation) => {
            swap();
            animation.cancel();
        });
    },

    swipeStart(event) {
        this.touchX = event.touches[0].clientX;
    },

    swipeEnd(event) {
        if (this.touchX === null) return;
        const dx = event.changedTouches[0].clientX - this.touchX;
        this.touchX = null;
        if (Math.abs(dx) > 50) this.step(dx < 0 ? 1 : -1);
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

/**
 * Real Liquid Glass refraction (Chromium): each [data-refract] element gets an SVG
 * displacement lens built for its exact size and corner radius. Near the rim the
 * backdrop is magnified towards the rim, as light is through the curved edge of a glass slab;
 * the middle stays undistorted. Safari / Firefox keep the plain clear glass.
 */
function initRefraction() {
    const ua = navigator.userAgent;
    const chromium = /(Chrome|Edg)\//.test(ua) && !/(CriOS|FxiOS|EdgiOS|Firefox)/.test(ua);
    const lessGlass = window.matchMedia('(prefers-reduced-transparency: reduce)').matches;
    // The lens is computed on the CPU every frame its backdrop changes. Phones therefore get it
    // only on the tab bar's own lens layer (faded out while scrolling — see initScrollChrome),
    // in a single pass; desktops get it on every glass control, with the colour split.
    const touch = window.matchMedia('(pointer: coarse)').matches;
    const targets = [...document.querySelectorAll(touch ? '.glass-lens[data-refract]' : '[data-refract]')];
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
                // Sample inward (towards the centre): the rim magnifies what is just inside it.
                // Sampling outward read pixels beyond the element, which a backdrop filter does not
                // have — that showed up as a ghost edge at the rounded ends of the tab bar.
                image.data[i] = 128 - nx * strength * 127;
                image.data[i + 1] = 128 - ny * strength * 127;
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
            ...(touch ? [node('feDisplacementMap', { in: 'SourceGraphic', in2: 'lens', scale: Math.round(strength), xChannelSelector: 'R', yChannelSelector: 'G' })] : [
            ...channel('red', strength * 1.12, '1 0 0 0 0  0 0 0 0 0  0 0 0 0 0  0 0 0 1 0'),
            ...channel('green', strength, '0 0 0 0 0  0 1 0 0 0  0 0 0 0 0  0 0 0 1 0'),
            ...channel('blue', strength * 0.88, '0 0 0 0 0  0 0 0 0 0  0 0 1 0 0  0 0 0 1 0'),
            node('feBlend', { in: 'red', in2: 'green', mode: 'screen', result: 'rg' }),
            node('feBlend', { in: 'rg', in2: 'blue', mode: 'screen' }),
            ]),
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
    let still = null;

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
        // While the page moves the refraction lens fades out (CSS), and fades back once still.
        root.classList.add('lens-off');
        clearTimeout(still);
        still = setTimeout(() => root.classList.remove('lens-off'), 220);

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
    initScrollChrome();
    initRefraction();
});
