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

/** The tab bar's glass pill slides from the previous tab to the new one. */
function animateTabIndicator() {
    const bar = document.querySelector('.tabbar');
    if (!bar || reduceMotion) return;

    const index = getComputedStyle(bar).getPropertyValue('--tab-index').trim();
    let previous = null;
    try {
        previous = sessionStorage.getItem('sems-tab');
        sessionStorage.setItem('sems-tab', index);
    } catch (e) {}

    if (previous === null || previous === index || !bar.querySelector('.tab-indicator')) return;

    bar.style.setProperty('--tab-index', previous);
    requestAnimationFrame(() => requestAnimationFrame(() => bar.style.setProperty('--tab-index', index)));
}

document.addEventListener('DOMContentLoaded', () => {
    labelTableCells();
    initGlassSpotlight();
    initReveal();
    initCountUp();
    animateTabIndicator();
});
