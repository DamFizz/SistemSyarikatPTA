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
