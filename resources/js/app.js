import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
document.documentElement.classList.toggle('reduced', reduced);

/* ------------------------------------------------------------------ motion */
function initMotion() {
    if (reduced) return;

    // Hero: the gate opens.
    const gate = document.querySelector('[data-gate]');
    if (gate) {
        gsap.fromTo(gate, { clipPath: 'inset(100% 0% 0% 0% round 999px 999px 0 0)' }, {
            clipPath: 'inset(0% 0% 0% 0% round 999px 999px 0 0)', duration: 1.6, ease: 'power3.out', delay: 0.15,
        });
        const img = gate.querySelector('img');
        if (img) {
            gsap.fromTo(img, { scale: 1.12 }, { scale: 1, duration: 2.2, ease: 'power2.out' });
            gsap.to(img, { yPercent: 8, ease: 'none', scrollTrigger: { trigger: gate, start: 'top top', end: 'bottom top', scrub: true } });
        }
    }

    // Hero background: slow settle on load, gentle parallax (≤ 8%) on scroll.
    const hero = document.querySelector('[data-hero]');
    const media = gsap.utils.toArray('[data-hero-media]');
    if (hero && media.length) {
        gsap.fromTo(media, { scale: 1.08 }, { scale: 1, duration: 2.6, ease: 'power2.out' });
        gsap.to(media, { yPercent: 8, ease: 'none', scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: true } });
    }

    gsap.utils.toArray('[data-hero-line]').forEach((el, i) => {
        gsap.fromTo(el, { opacity: 0, y: 28 }, { opacity: 1, y: 0, duration: 1.1, delay: 0.35 + i * 0.12, ease: 'power3.out' });
    });

    // Staggered reveals.
    ScrollTrigger.batch('.reveal', {
        start: 'top 88%',
        once: true,
        onEnter: (els) => gsap.to(els, { opacity: 1, y: 0, duration: 0.9, ease: 'power3.out', stagger: 0.08, overwrite: true }),
    });

    // Twinkle the star marks.
    gsap.utils.toArray('[data-twinkle]').forEach((el) => {
        gsap.to(el, { opacity: 0.35, duration: 1.8 + Math.random() * 2, repeat: -1, yoyo: true, ease: 'sine.inOut', delay: Math.random() * 2 });
    });
}

/* -------------------------------------------------------------- hero video */
/**
 * Loads the hero loop only when it is worth it: not under reduced motion,
 * Save-Data or 2G. Fades in on first frame, pauses off-screen, and offers a
 * pause/play control (WCAG 2.2.2). The poster image stays as the fallback.
 */
function initHeroVideo() {
    const video = document.querySelector('[data-hero-video]');
    if (!video) return;
    const conn = navigator.connection || {};
    if (reduced || conn.saveData || /(^|-)2g$/.test(conn.effectiveType || '')) return;

    const toggle = document.querySelector('[data-hero-video-toggle]');
    let userPaused = false;
    const sync = () => {
        if (!toggle) return;
        const paused = video.paused;
        toggle.setAttribute('aria-pressed', String(paused));
        toggle.querySelector('[data-label]').textContent = paused ? toggle.dataset.labelPlay : toggle.dataset.labelPause;
        toggle.querySelector('[data-icon-play]').hidden = !paused;
        toggle.querySelector('[data-icon-pause]').hidden = paused;
    };
    const play = () => video.play().catch(() => {});

    video.querySelectorAll('source[data-src]').forEach((s) => { s.src = s.dataset.src; });
    video.load();
    video.addEventListener('playing', () => {
        video.classList.remove('opacity-0');
        if (toggle) toggle.hidden = false;
    }, { once: true });
    video.addEventListener('play', sync);
    video.addEventListener('pause', sync);
    play();

    new IntersectionObserver(([entry]) => {
        if (userPaused) return;
        entry.isIntersecting ? play() : video.pause();
    }, { threshold: 0.05 }).observe(video);

    document.addEventListener('visibilitychange', () => {
        if (!userPaused && !document.hidden) play();
    });

    toggle?.addEventListener('click', () => {
        userPaused = !video.paused;
        userPaused ? video.pause() : play();
    });
}

/* ------------------------------------------------------------------ header */
function initHeader() {
    const header = document.querySelector('[data-header]');
    if (!header) return;
    const onScroll = () => header.toggleAttribute('data-scrolled', window.scrollY > 24);
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
}

/* ---------------------------------------------------------- Alpine pieces */
document.addEventListener('alpine:init', () => {
    /**
     * Two-month range picker backed by /api/availability.
     * Usage: x-data="rangePicker({ endpoint, checkIn, checkOut, locale, minNights })"
     * Emits `range-selected` with { checkIn, checkOut }.
     */
    window.Alpine.data('rangePicker', (opts) => ({
        endpoint: opts.endpoint,
        locale: opts.locale || document.documentElement.lang,
        minNights: opts.minNights || 1,
        checkIn: opts.checkIn || null,
        checkOut: opts.checkOut || null,
        hover: null,
        cursor: null,
        days: {},
        currency: '',
        loading: false,
        open: opts.inline ?? false,

        init() {
            const base = this.checkIn ? new Date(this.checkIn + 'T00:00:00') : new Date();
            this.cursor = new Date(base.getFullYear(), base.getMonth(), 1);
            this.load();
        },
        iso(d) {
            return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        },
        months() {
            return [0, 1].map((o) => new Date(this.cursor.getFullYear(), this.cursor.getMonth() + o, 1));
        },
        monthLabel(m) {
            return m.toLocaleDateString(this.locale, { month: 'long', year: 'numeric' });
        },
        weekdays() {
            const start = new Date(2024, 0, 7); // a Sunday
            return [...Array(7)].map((_, i) => new Date(start.getTime() + i * 864e5).toLocaleDateString(this.locale, { weekday: 'narrow' }));
        },
        cells(m) {
            const first = new Date(m.getFullYear(), m.getMonth(), 1);
            const count = new Date(m.getFullYear(), m.getMonth() + 1, 0).getDate();
            return [...Array(first.getDay()).fill(null), ...[...Array(count)].map((_, i) => new Date(m.getFullYear(), m.getMonth(), i + 1))];
        },
        async load() {
            if (!this.endpoint) return;
            this.loading = true;
            const from = this.iso(this.cursor);
            const to = this.iso(new Date(this.cursor.getFullYear(), this.cursor.getMonth() + 2, 1));
            try {
                const res = await fetch(`${this.endpoint}?from=${from}&to=${to}`, { headers: { Accept: 'application/json' } });
                const json = await res.json();
                this.days = { ...this.days, ...json.days };
                this.currency = json.currency;
                if (json.min_nights) this.minNights = json.min_nights;
            } catch (e) { /* calendar still works without prices */ }
            this.loading = false;
        },
        shift(n) {
            const next = new Date(this.cursor.getFullYear(), this.cursor.getMonth() + n, 1);
            const today = new Date(); today.setDate(1); today.setHours(0, 0, 0, 0);
            if (next < today) return;
            this.cursor = next;
            this.load();
        },
        isPast(d) { const t = new Date(); t.setHours(0, 0, 0, 0); return d < t; },
        info(d) { return this.days[this.iso(d)]; },
        isFull(d) { const i = this.info(d); return i && i.free === 0; },
        isStart(d) { return this.checkIn === this.iso(d); },
        isEnd(d) { return this.checkOut === this.iso(d); },
        inRange(d) {
            const s = this.checkIn, e = this.checkOut || (this.hover && this.hover > s ? this.hover : null);
            const v = this.iso(d);
            return s && e && v > s && v < e;
        },
        rangeBlocked(start, end) {
            for (let d = new Date(start + 'T00:00:00'); this.iso(d) < end; d.setDate(d.getDate() + 1)) {
                if (this.isFull(d)) return true;
            }
            return false;
        },
        pick(d) {
            if (this.isPast(d)) return;
            const v = this.iso(d);
            if (!this.checkIn || this.checkOut || v <= this.checkIn) {
                if (this.isFull(d)) return;
                this.checkIn = v; this.checkOut = null;
                return;
            }
            if (this.rangeBlocked(this.checkIn, v)) { this.checkIn = this.isFull(d) ? null : v; this.checkOut = null; return; }
            this.checkOut = v;
            this.$dispatch('range-selected', { checkIn: this.checkIn, checkOut: this.checkOut });
            if (!opts.inline) this.open = false;
        },
        nights() {
            if (!this.checkIn || !this.checkOut) return 0;
            return Math.round((new Date(this.checkOut) - new Date(this.checkIn)) / 864e5);
        },
        priceLabel(d) {
            const i = this.info(d);
            if (!i || !i.from || this.isPast(d)) return '';
            return i.from >= 1000 ? (i.from / 1000).toFixed(i.from % 1000 ? 1 : 0) + 'k' : String(i.from);
        },
        fmt(v) {
            return v ? new Date(v + 'T00:00:00').toLocaleDateString(this.locale, { weekday: 'short', day: 'numeric', month: 'short' }) : '';
        },
        clear() { this.checkIn = null; this.checkOut = null; },
    }));
});

document.addEventListener('DOMContentLoaded', () => {
    initHeader();
    initMotion();
    initHeroVideo();
});
document.addEventListener('livewire:navigated', () => ScrollTrigger.refresh());
