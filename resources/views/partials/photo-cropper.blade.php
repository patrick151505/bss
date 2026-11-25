{{--
    Photo positioner for citizen photos (profile, Barangay ID, document requests).

    Usage (JS):  PhotoCrop.open(fileOrBlobOrVideo, { mirror: false }).then(blob => { if (blob) … })
    Resolves with a square JPEG (800 × 800) of exactly what is inside the frame,
    or null when the user cancels. Include once per page: @include('partials.photo-cropper')
--}}
@once
<div id="pc-modal" style="z-index:1100" class="fixed inset-0 hidden items-center justify-center p-3 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="pc-title">
    <div class="absolute inset-0 bg-gray-900/70" data-pc-cancel></div>
    <div class="relative w-full max-w-md bg-white dark:bg-gray-800 rounded-xl shadow-xl overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-700">
            <div>
                <h3 id="pc-title" class="font-semibold text-gray-800 dark:text-gray-100">Position the photo</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Drag to move. Zoom so the face fits inside the guide.</p>
            </div>
            <button type="button" data-pc-cancel class="p-1.5 rounded text-gray-400 hover:text-gray-700 dark:hover:text-gray-200" aria-label="Close">
                <i class="mgc_close_line text-xl"></i>
            </button>
        </div>

        <div class="p-4">
            <div id="pc-stage" style="aspect-ratio:1/1" class="relative w-full overflow-hidden rounded-lg bg-gray-900 select-none touch-none cursor-grab">
                <img id="pc-img" alt="" draggable="false" class="absolute max-w-none pointer-events-none" style="transform-origin:center center">
                {{-- Face guide --}}
                <div class="pointer-events-none absolute inset-0">
                    <div class="absolute left-1/2 top-[44%] -translate-x-1/2 -translate-y-1/2 w-[56%] h-[70%] rounded-[50%] border-2 border-dashed border-white/80 shadow-[0_0_0_9999px_rgba(0,0,0,0.25)]"></div>
                    <div class="absolute left-1/2 top-0 bottom-0 w-px bg-white/25"></div>
                    <div class="absolute top-1/2 left-0 right-0 h-px bg-white/25"></div>
                </div>
            </div>

            <div class="flex items-center gap-3 mt-4">
                <i class="mgc_zoom_out_line text-gray-400"></i>
                <input type="range" id="pc-zoom" min="1" max="4" step="0.01" value="1" class="flex-1" aria-label="Zoom">
                <i class="mgc_zoom_in_line text-gray-400"></i>
            </div>

            <div class="flex flex-wrap gap-2 mt-3">
                <button type="button" id="pc-rotate" class="btn btn-sm bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">
                    <i class="mgc_clockwise_line"></i> Rotate
                </button>
                <button type="button" id="pc-center" class="btn btn-sm bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">
                    <i class="mgc_align_center_line"></i> Center
                </button>
                <button type="button" id="pc-fit" class="btn btn-sm bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">
                    <i class="mgc_fullscreen_line"></i> Fit
                </button>
            </div>
        </div>

        <div class="flex justify-end gap-2 px-4 py-3 border-t border-gray-100 dark:border-gray-700">
            <button type="button" data-pc-cancel class="btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">Cancel</button>
            <button type="button" id="pc-ok" class="btn bg-primary text-white"><i class="mgc_check_line"></i> Use photo</button>
        </div>
    </div>
</div>

<script>
window.PhotoCrop = (function () {
    const OUT = 800;                      // saved photo: 800 × 800 JPEG
    const modal = document.getElementById('pc-modal');
    const stage = document.getElementById('pc-stage');
    const img   = document.getElementById('pc-img');
    const zoomEl = document.getElementById('pc-zoom');

    let src = null, w = 0, h = 0, rot = 0;  // source and its natural size, rotation (deg)
    let zoom = 1, cx = 0, cy = 0;           // zoom (1 = just covers), image centre offset (stage px)
    let resolver = null, objectUrl = null;

    const S = () => stage.clientWidth;
    const turned = () => rot % 180 !== 0;
    const effW = () => turned() ? h : w;
    const effH = () => turned() ? w : h;
    const scale = () => (S() / Math.min(effW(), effH())) * zoom;

    // Keep the photo covering the whole frame (no empty edges).
    function clamp() {
        const k = scale();
        const mx = Math.max(0, (effW() * k - S()) / 2);
        const my = Math.max(0, (effH() * k - S()) / 2);
        cx = Math.min(mx, Math.max(-mx, cx));
        cy = Math.min(my, Math.max(-my, cy));
    }

    function render() {
        clamp();
        const k = scale(), s = S();
        img.style.width  = (w * k) + 'px';
        img.style.height = (h * k) + 'px';
        img.style.left   = (s / 2 + cx - (w * k) / 2) + 'px';
        img.style.top    = (s / 2 + cy - (h * k) / 2) + 'px';
        img.style.transform = 'rotate(' + rot + 'deg)';
        zoomEl.value = zoom;
    }

    function setZoom(z, ax = 0, ay = 0) {   // zoom around a point (stage-centre coords)
        const old = zoom;
        zoom = Math.min(4, Math.max(1, z));
        const f = zoom / old;
        cx = ax + (cx - ax) * f;
        cy = ay + (cy - ay) * f;
        render();
    }

    // ── Drag / pinch / wheel ──
    const pts = new Map();
    let last = null, pinch = null;
    stage.addEventListener('pointerdown', e => {
        stage.setPointerCapture(e.pointerId);
        pts.set(e.pointerId, { x: e.clientX, y: e.clientY });
        stage.classList.add('cursor-grabbing');
        if (pts.size === 2) {
            const [a, b] = [...pts.values()];
            pinch = { d: Math.hypot(a.x - b.x, a.y - b.y), z: zoom };
        }
        last = { x: e.clientX, y: e.clientY };
    });
    stage.addEventListener('pointermove', e => {
        if (!pts.has(e.pointerId)) return;
        pts.set(e.pointerId, { x: e.clientX, y: e.clientY });
        if (pts.size === 2 && pinch) {
            const [a, b] = [...pts.values()];
            const r = stage.getBoundingClientRect();
            const mx = (a.x + b.x) / 2 - r.left - r.width / 2, my = (a.y + b.y) / 2 - r.top - r.height / 2;
            setZoom(pinch.z * Math.hypot(a.x - b.x, a.y - b.y) / pinch.d, mx, my);
            return;
        }
        if (pts.size === 1 && last) {
            cx += e.clientX - last.x; cy += e.clientY - last.y;
            last = { x: e.clientX, y: e.clientY };
            render();
        }
    });
    const up = e => {
        pts.delete(e.pointerId);
        if (pts.size < 2) pinch = null;
        const p = [...pts.values()][0];
        last = p ? { x: p.x, y: p.y } : null;
        if (!pts.size) stage.classList.remove('cursor-grabbing');
    };
    stage.addEventListener('pointerup', up);
    stage.addEventListener('pointercancel', up);
    stage.addEventListener('wheel', e => {
        e.preventDefault();
        const r = stage.getBoundingClientRect();
        setZoom(zoom * (e.deltaY < 0 ? 1.08 : 1 / 1.08), e.clientX - r.left - r.width / 2, e.clientY - r.top - r.height / 2);
    }, { passive: false });

    zoomEl.addEventListener('input', () => setZoom(parseFloat(zoomEl.value)));
    document.getElementById('pc-rotate').addEventListener('click', () => { rot = (rot + 90) % 360; render(); });
    document.getElementById('pc-center').addEventListener('click', () => { cx = 0; cy = 0; render(); });
    document.getElementById('pc-fit').addEventListener('click', () => { zoom = 1; cx = 0; cy = 0; render(); });
    window.addEventListener('resize', () => { if (src) render(); });

    // Keyboard: arrows move, +/- zoom, Enter saves, Esc cancels.
    document.addEventListener('keydown', e => {
        if (modal.classList.contains('hidden')) return;
        const step = e.shiftKey ? 20 : 5;
        if (e.key === 'Escape') { finish(null); }
        else if (e.key === 'Enter' && e.target.tagName !== 'BUTTON') { e.preventDefault(); save(); }
        else if (e.key === 'ArrowLeft')  { cx -= step; render(); }
        else if (e.key === 'ArrowRight') { cx += step; render(); }
        else if (e.key === 'ArrowUp')    { cy -= step; render(); }
        else if (e.key === 'ArrowDown')  { cy += step; render(); }
        else if (e.key === '+' || e.key === '=') { setZoom(zoom * 1.08); }
        else if (e.key === '-') { setZoom(zoom / 1.08); }
        else return;
        if (e.key.startsWith('Arrow')) e.preventDefault();
    });

    modal.querySelectorAll('[data-pc-cancel]').forEach(b => b.addEventListener('click', () => finish(null)));
    document.getElementById('pc-ok').addEventListener('click', save);

    function save() {
        if (!src) return;
        const c = document.createElement('canvas');
        c.width = c.height = OUT;
        const ctx = c.getContext('2d');
        ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, OUT, OUT);
        ctx.imageSmoothingQuality = 'high';
        const f = OUT / S(), k = scale() * f;
        ctx.translate(OUT / 2 + cx * f, OUT / 2 + cy * f);
        ctx.rotate(rot * Math.PI / 180);
        ctx.drawImage(src, -w * k / 2, -h * k / 2, w * k, h * k);
        c.toBlob(b => finish(b), 'image/jpeg', 0.9);
    }

    function finish(blob) {
        modal.classList.add('hidden'); modal.classList.remove('flex');
        document.body.style.overflow = '';
        if (objectUrl) { URL.revokeObjectURL(objectUrl); objectUrl = null; }
        src = null; img.removeAttribute('src');
        const r = resolver; resolver = null;
        r && r(blob);
    }

    function show(source, nw, nh) {
        src = source; w = nw; h = nh; rot = 0; zoom = 1; cx = 0; cy = 0;
        img.src = source.tagName === 'CANVAS' ? source.toDataURL('image/jpeg', 0.92) : source.src;
        modal.classList.remove('hidden'); modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
        requestAnimationFrame(render);
        document.getElementById('pc-ok').focus();
    }

    /**
     * Open the positioner for a File/Blob, an <img>, or a <video> (current frame).
     * opts.mirror: flip a selfie camera frame so it matches the preview.
     */
    function open(source, opts = {}) {
        if (resolver) finish(null);
        return new Promise((resolve, reject) => {
            resolver = resolve;
            if (source instanceof Blob) {
                if (!/^image\//.test(source.type)) { resolver = null; reject(new Error('Choose a JPG or PNG picture.')); return; }
                const im = new Image();
                objectUrl = URL.createObjectURL(source);
                im.onload = () => show(im, im.naturalWidth, im.naturalHeight);
                im.onerror = () => { resolver = null; reject(new Error('That picture could not be opened. Try a JPG or PNG.')); };
                im.src = objectUrl;
            } else if (source && source.tagName === 'VIDEO') {
                const c = document.createElement('canvas');
                c.width = source.videoWidth; c.height = source.videoHeight;
                const ctx = c.getContext('2d');
                if (opts.mirror) { ctx.translate(c.width, 0); ctx.scale(-1, 1); }
                ctx.drawImage(source, 0, 0);
                show(c, c.width, c.height);
            } else if (source && source.tagName === 'IMG') {
                show(source, source.naturalWidth, source.naturalHeight);
            } else {
                resolver = null; reject(new Error('No picture to position.'));
            }
        });
    }

    /**
     * Wire a form's <input type="file">: picking a picture opens the positioner,
     * and the input is given the positioned photo instead. onDone(dataUrl) updates a preview.
     */
    function attachToInput(input, onDone) {
        if (!input) return;
        input.addEventListener('change', function (e) {
            if (input._pcSetting) return;          // our own replacement below
            const file = input.files && input.files[0];
            if (!file) return;
            e.stopImmediatePropagation();          // the page's own preview waits for the result
            open(file).then(blob => {
                if (!blob) { input.value = ''; return; }
                const out = new File([blob], 'photo.jpg', { type: 'image/jpeg' });
                const dt = new DataTransfer();
                dt.items.add(out);
                input._pcSetting = true;
                input.files = dt.files;
                input._pcSetting = false;
                const reader = new FileReader();
                reader.onload = ev => onDone && onDone(ev.target.result);
                reader.readAsDataURL(out);
            }).catch(err => {
                input.value = '';
                if (window.Swal) Swal.fire({ icon: 'warning', title: err.message });
            });
        }, true);                                  // capture: runs before the page's own handler
    }

    return { open, attachToInput };
})();
</script>
@endonce
