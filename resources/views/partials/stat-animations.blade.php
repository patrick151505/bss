{{--
    Reusable stat animations:
    • Count-up  — any element with [data-countup] animates from 0 to its number.
                  Put the target number in data-countup; optional data-prefix / data-suffix
                  and data-decimals. The visible text is replaced during the animation.
    • Entrance  — any element with class .anim-card fades in + slides up, staggered.
    Respects prefers-reduced-motion.
--}}
<style>
    .anim-card { opacity: 0; transform: translateY(14px); }
    .anim-card.anim-in {
        opacity: 1; transform: translateY(0);
        transition: opacity .5s ease, transform .5s cubic-bezier(.22,.61,.36,1);
    }
    @media (prefers-reduced-motion: reduce) {
        .anim-card { opacity: 1 !important; transform: none !important; transition: none !important; }
    }
</style>
<script>
(function () {
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // ── Count-up ──────────────────────────────────────────────────────────
    function formatNum(n, decimals) {
        return Number(n).toLocaleString(undefined, {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        });
    }
    function countUp(el) {
        const target   = parseFloat(el.dataset.countup);
        if (isNaN(target)) return;
        const decimals = parseInt(el.dataset.decimals || '0', 10);
        const prefix   = el.dataset.prefix || '';
        const suffix   = el.dataset.suffix || '';
        const dur      = 1100;

        if (reduce) { el.textContent = prefix + formatNum(target, decimals) + suffix; return; }

        const start = performance.now();
        function tick(now) {
            const p = Math.min(1, (now - start) / dur);
            // easeOutCubic
            const eased = 1 - Math.pow(1 - p, 3);
            el.textContent = prefix + formatNum(target * eased, decimals) + suffix;
            if (p < 1) requestAnimationFrame(tick);
            else el.textContent = prefix + formatNum(target, decimals) + suffix;
        }
        requestAnimationFrame(tick);
    }

    // ── Reveal on scroll (or immediately for above-the-fold) ──────────────
    const io = 'IntersectionObserver' in window
        ? new IntersectionObserver((entries, obs) => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) return;
                const el = entry.target;
                obs.unobserve(el);
                if (el.classList.contains('anim-card')) {
                    const delay = parseInt(el.dataset.animDelay || '0', 10);
                    setTimeout(() => el.classList.add('anim-in'), delay);
                }
                el.querySelectorAll('[data-countup]').forEach(countUp);
                if (el.hasAttribute('data-countup')) countUp(el);
            });
        }, { threshold: 0.15 })
        : null;

    function init() {
        // Stagger the entrance delay across cards in document order.
        const cards = document.querySelectorAll('.anim-card');
        cards.forEach((c, i) => { if (!c.dataset.animDelay) c.dataset.animDelay = (i % 8) * 70; });

        if (!io) {
            // No observer support — just show everything and count instantly.
            cards.forEach(c => c.classList.add('anim-in'));
            document.querySelectorAll('[data-countup]').forEach(countUp);
            return;
        }
        cards.forEach(c => io.observe(c));
        // Count-up elements that aren't inside an .anim-card get observed directly.
        document.querySelectorAll('[data-countup]').forEach(el => {
            if (!el.closest('.anim-card')) io.observe(el);
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
</script>
