@php
    $logo  = $brgy->logo ? asset('storage/' . preg_replace('#^public/#', '', $brgy->logo)) : null;
    $bName = $brgy->barangay_name ? 'Barangay ' . preg_replace('/^barangay\s+/i', '', $brgy->barangay_name) : 'Barangay Hall';
    $place = collect([$brgy->municipality, $brgy->province])->filter()->implode(', ');
@endphp
<!DOCTYPE html>
<html lang="en" data-mode="light">
<head>
    <meta charset="utf-8">
    <title>{{ $bName }} · Kiosk</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="robots" content="noindex, nofollow">
    @if($logo)<link rel="icon" href="{{ $logo }}">@endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/scss/app.scss', 'resources/scss/icons.scss'])
    <style>
        :root {
            --ink: #0b1220; --ink-2: #334155; --muted: #64748b; --line: #e3e8f2; --bg: #f3f6fc; --card: #ffffff;
            --blue: #2563eb; --indigo: #4f46e5; --sky: #e8f0ff; --green: #059669; --teal: #0d9488; --mint: #e6f7f1;
            --amber: #b45309; --amber-bg: #fff6e5; --red: #dc2626; --red-bg: #fff1f1;
            --shadow-sm: 0 1px 2px rgba(16,24,40,.05), 0 4px 12px -4px rgba(16,24,40,.08);
            --shadow: 0 1px 2px rgba(16,24,40,.04), 0 18px 40px -16px rgba(16,24,40,.22);
            --ease: cubic-bezier(.2,.8,.2,1);
        }
        html, body { height: 100%; }
        body { margin: 0; background: var(--bg); color: var(--ink); overflow: hidden;
               font-family: 'Plus Jakarta Sans', Inter, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
               -webkit-font-smoothing: antialiased; -webkit-user-select: none; user-select: none;
               -webkit-tap-highlight-color: transparent; touch-action: manipulation; }
        input, textarea, select { -webkit-user-select: text; user-select: text; font-family: inherit; }
        button { font-family: inherit; }
        .k-hide { display: none !important; }
        /* Events: just Scan ID → Event (no details check). */
        body.mode-events .k-stepnav li:nth-child(2), body.mode-events .k-stepnav li:nth-child(n+4) { display: none; }
        .k-fil { display: block; font-size: .8em; font-weight: 500; opacity: .72; margin-top: .2em; letter-spacing: 0; }

        /* Soft colour glows behind every screen */
        /* Moving colour shapes behind every screen. Soft radial gradients (no blur filter)
           and transform-only animation, so it stays smooth on a low-cost tablet. */
        .k-glow { position: fixed; inset: 0; pointer-events: none; overflow: hidden; }
        .k-glow::after { content: ''; position: absolute; inset: 0;   /* light dot pattern, fading toward the middle */
                         background-image: radial-gradient(rgba(79,70,229,.16) 1.3px, transparent 1.6px); background-size: 26px 26px;
                         -webkit-mask-image: radial-gradient(ellipse at center, transparent 30%, #000 85%); mask-image: radial-gradient(ellipse at center, transparent 30%, #000 85%); }
        .k-blob { position: absolute; border-radius: 50%; will-change: transform; animation: k-float 26s ease-in-out infinite alternate; }
        .k-blob.b1 { width: 52vmax; height: 52vmax; right: -16vmax; top: -22vmax; background: radial-gradient(circle, rgba(129,160,255,.72), rgba(129,160,255,0) 65%); }
        .k-blob.b2 { width: 46vmax; height: 46vmax; left: -18vmax; bottom: -22vmax; background: radial-gradient(circle, rgba(110,231,183,.66), rgba(110,231,183,0) 65%); animation-duration: 31s; animation-delay: -8s; }
        .k-blob.b3 { width: 34vmax; height: 34vmax; left: 30%; top: -14vmax; background: radial-gradient(circle, rgba(253,186,116,.55), rgba(253,186,116,0) 65%); animation-name: k-float2; animation-duration: 34s; }
        .k-blob.b4 { width: 30vmax; height: 30vmax; right: 8%; bottom: -12vmax; background: radial-gradient(circle, rgba(196,181,253,.6), rgba(196,181,253,0) 65%); animation-name: k-float2; animation-duration: 29s; animation-delay: -12s; }
        .k-blob.b5 { width: 22vmax; height: 22vmax; left: -6vmax; top: 30%; background: radial-gradient(circle, rgba(125,211,252,.55), rgba(125,211,252,0) 65%); animation-duration: 23s; animation-delay: -5s; }
        .k-shape { position: absolute; will-change: transform; animation: k-drift 40s linear infinite; }
        .k-shape.s1 { width: 9rem; height: 9rem; right: 7%; top: 18%; border: 2px solid rgba(79,70,229,.18); border-radius: 50%; }
        .k-shape.s2 { width: 6rem; height: 6rem; left: 6%; top: 14%; border: 2px solid rgba(16,185,129,.22); border-radius: 1.6rem; animation-duration: 52s; animation-direction: reverse; }
        .k-shape.s3 { width: 4.5rem; height: 4.5rem; left: 46%; bottom: 7%; border-radius: 1.2rem; background: rgba(245,158,11,.14); animation-duration: 46s; }
        .k-shape.s4 { width: 3rem; height: 3rem; right: 26%; bottom: 22%; border-radius: 50%; background: rgba(79,70,229,.1); animation-duration: 36s; animation-direction: reverse; }
        @keyframes k-float  { 0% { transform: translate(0, 0) scale(1); } 50% { transform: translate(-6vw, 5vh) scale(1.12); } 100% { transform: translate(4vw, -4vh) scale(.95); } }
        @keyframes k-float2 { 0% { transform: translate(0, 0) scale(1); } 50% { transform: translate(7vw, 6vh) scale(.9); } 100% { transform: translate(-5vw, 3vh) scale(1.1); } }
        @keyframes k-drift  { 0% { transform: translate(0, 0) rotate(0deg); } 50% { transform: translate(2.5vw, -3vh) rotate(180deg); } 100% { transform: translate(0, 0) rotate(360deg); } }

        .k-screen { position: fixed; inset: 0; display: flex; flex-direction: column; }
        .k-enter .k-body > * > *, .k-enter .k-foot { animation: k-up .5s var(--ease) both; }
        .k-enter .k-body > * > *:nth-child(2) { animation-delay: .05s; }
        .k-enter .k-body > * > *:nth-child(3) { animation-delay: .1s; }
        .k-enter .k-body > * > *:nth-child(4) { animation-delay: .15s; }
        @keyframes k-up { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }

        /* ── Buttons ── */
        .k-btn { position: relative; display: inline-flex; align-items: center; justify-content: center; gap: .6rem; min-height: 3.6rem; padding: .7rem 1.4rem;
                 border-radius: 1.1rem; font-size: 1.15rem; font-weight: 700; border: 1px solid transparent; cursor: pointer; text-align: center;
                 transition: transform .12s var(--ease), box-shadow .2s, background .2s, opacity .2s; overflow: hidden; }
        .k-btn:active { transform: scale(.97); }
        .k-btn i { font-size: 1.35em; }
        .k-btn-ghost { background: rgba(255,255,255,.85); color: var(--ink); border-color: var(--line); box-shadow: var(--shadow-sm); }
        .k-btn-primary { background: linear-gradient(135deg, var(--blue), var(--indigo)); color: #fff; box-shadow: 0 14px 28px -12px rgba(79,70,229,.75); }
        .k-btn-green { background: linear-gradient(135deg, #10b981, var(--teal)); color: #fff; box-shadow: 0 14px 28px -12px rgba(13,148,136,.75); }
        .k-btn-big { min-height: 4.75rem; font-size: 1.4rem; padding: 1rem 2rem; border-radius: 1.4rem; }
        .k-btn[disabled] { opacity: .38; filter: grayscale(.6); pointer-events: none; box-shadow: none; }

        /* ── App bar ── */
        .k-bar { position: relative; z-index: 3; display: flex; align-items: center; gap: 1rem; margin: 1rem 1.25rem 0; padding: .65rem .75rem .65rem 1rem;
                 border-radius: 1.5rem; background: rgba(255,255,255,.72); border: 1px solid rgba(255,255,255,.9);
                 -webkit-backdrop-filter: blur(18px) saturate(1.5); backdrop-filter: blur(18px) saturate(1.5); box-shadow: var(--shadow-sm); }
        .k-brand { display: flex; align-items: center; gap: .8rem; min-width: 0; flex-shrink: 1; overflow: hidden; }
        .k-brand > div { min-width: 0; overflow: hidden; }
        .k-brand b, .k-brand small { overflow: hidden; text-overflow: ellipsis; }
        .k-brand img { width: 2.9rem; height: 2.9rem; object-fit: contain; border-radius: .8rem; background: #fff; }
        .k-brand b { display: block; font-size: 1.1rem; font-weight: 800; line-height: 1.2; letter-spacing: -.01em; white-space: nowrap; }
        .k-brand small { display: block; font-size: .85rem; color: var(--muted); white-space: nowrap; }
        .k-bar-btns { margin-left: auto; display: flex; gap: .6rem; }
        .k-stepnav { flex-shrink: 0; display: flex; gap: .35rem; margin: 0 auto; padding: .3rem; border-radius: 999px; background: rgba(241,245,249,.9); list-style: none; }
        .k-stepnav li { white-space: nowrap; display: flex; align-items: center; gap: .5rem; padding: .35rem .85rem .35rem .4rem; border-radius: 999px; font-weight: 700; font-size: .95rem; color: var(--muted); }
        .k-stepnum { width: 1.9rem; height: 1.9rem; border-radius: 999px; display: flex; align-items: center; justify-content: center; background: #fff; border: 1px solid var(--line); font-size: .9rem; }
        .k-stepnav li.done { color: var(--green); }
        .k-stepnav li.done .k-stepnum { background: var(--green); border-color: var(--green); color: #fff; }
        .k-stepnav li.cur { background: #fff; color: var(--ink); box-shadow: var(--shadow-sm); }
        .k-stepnav li.cur .k-stepnum { background: linear-gradient(135deg, var(--blue), var(--indigo)); border-color: transparent; color: #fff; }

        .k-body { position: relative; z-index: 1; flex: 1; overflow-y: auto; padding: 2rem 1.25rem 2.5rem; }
        .k-wrap { max-width: 1180px; margin: 0 auto; }
        .k-eyebrow { display: inline-flex; align-items: center; gap: .5rem; font-size: .95rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: var(--blue); }
        .k-title { font-size: clamp(2rem, 3.6vw, 2.8rem); font-weight: 800; letter-spacing: -.03em; line-height: 1.1; margin-top: .4rem; }
        .k-title .k-fil { font-size: .5em; color: var(--muted); opacity: 1; margin-top: .45rem; letter-spacing: 0; }
        .k-sub { font-size: 1.2rem; color: var(--muted); margin-top: .6rem; }

        /* ── 1. Start screen ── */
        .k-attract { background: #0b1220; cursor: pointer; color: #fff; }
        .k-slide { position: absolute; inset: 0; opacity: 0; transition: opacity 1.2s ease; }
        .k-slide.on { opacity: 1; }
        .k-slide img { width: 100%; height: 100%; object-fit: cover; }
        .k-slide.on img { animation: k-zoom 9s ease-out forwards; }
        @keyframes k-zoom { from { transform: scale(1); } to { transform: scale(1.07); } }
        .k-shade { position: absolute; inset: 0; background: linear-gradient(180deg, rgba(11,18,32,.55) 0%, rgba(11,18,32,0) 22%, rgba(11,18,32,0) 45%, rgba(11,18,32,.88) 100%); }
        .k-empty { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 1.25rem; text-align: center; padding: 2rem;
                   background: linear-gradient(125deg, #1d4ed8, #4338ca, #0f766e, #1d4ed8); background-size: 300% 300%; animation: k-mesh 18s ease infinite; }
        @keyframes k-mesh { 0%,100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }
        .k-empty::before, .k-empty::after { content: ''; position: absolute; border-radius: 50%; filter: blur(60px); opacity: .45; animation: k-drift 14s ease-in-out infinite alternate; }
        .k-empty::before { width: 34rem; height: 34rem; background: #60a5fa; top: -8rem; left: -6rem; }
        .k-empty::after  { width: 30rem; height: 30rem; background: #34d399; bottom: -10rem; right: -6rem; animation-delay: -7s; }
        @keyframes k-drift { to { transform: translate(4rem, 3rem) scale(1.1); } }
        .k-empty > * { position: relative; }
        .k-seal { width: 12rem; height: 12rem; border-radius: 50%; padding: 1.1rem; background: rgba(255,255,255,.16); border: 1px solid rgba(255,255,255,.35);
                  -webkit-backdrop-filter: blur(12px); backdrop-filter: blur(12px); box-shadow: 0 30px 60px -20px rgba(0,0,0,.5); }
        .k-seal img { width: 100%; height: 100%; object-fit: contain; border-radius: 50%; background: #fff; }
        .k-empty h1 { color: #fff; font-size: clamp(2.4rem, 5.5vw, 4rem); font-weight: 800; letter-spacing: -.03em; line-height: 1.05; }
        .k-empty p { font-size: 1.35rem; opacity: .85; }

        .k-top { position: relative; z-index: 2; display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.25rem 1.5rem; }
        .k-glass { display: flex; align-items: center; gap: .85rem; padding: .6rem 1.1rem .6rem .6rem; border-radius: 1.25rem; color: #fff;
                   background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.25); -webkit-backdrop-filter: blur(14px); backdrop-filter: blur(14px); }
        .k-glass img { width: 3rem; height: 3rem; object-fit: contain; border-radius: .85rem; background: #fff; }
        .k-glass b { display: block; font-size: 1.15rem; font-weight: 800; line-height: 1.2; }
        .k-glass small { display: block; font-size: .9rem; opacity: .8; }
        .k-clock { padding: .6rem 1.2rem; text-align: right; display: block; }
        .k-clock b { font-size: 1.9rem; line-height: 1; font-variant-numeric: tabular-nums; letter-spacing: -.02em; }

        .k-bottom { position: relative; z-index: 2; margin-top: auto; padding: 0 2rem 2.5rem; display: flex; align-items: flex-end; justify-content: space-between; gap: 2rem; }
        .k-segs { display: flex; gap: .45rem; margin-bottom: 1rem; }
        .k-segs span { width: 3rem; height: .3rem; border-radius: 99px; background: rgba(255,255,255,.3); overflow: hidden; }
        .k-segs span i { display: block; height: 100%; width: 0; background: #fff; border-radius: 99px; }
        .k-segs span.past i { width: 100%; }
        .k-segs span.on i { animation: k-seg 7s linear forwards; }
        @keyframes k-seg { to { width: 100%; } }
        .k-caption { font-size: clamp(1.6rem, 3.2vw, 2.6rem); font-weight: 800; letter-spacing: -.02em; line-height: 1.15; max-width: 22ch; text-shadow: 0 2px 20px rgba(0,0,0,.45); }
        .k-tap { position: relative; flex-shrink: 0; display: inline-flex; align-items: center; gap: 1rem; padding: .85rem 2rem .85rem .85rem; border-radius: 999px;
                 background: #fff; color: var(--ink); box-shadow: 0 24px 50px -16px rgba(0,0,0,.6); }
        .k-tap::before, .k-tap::after { content: ''; position: absolute; inset: 0; border-radius: inherit; border: 2px solid rgba(255,255,255,.7); animation: k-ring 2.4s ease-out infinite; }
        .k-tap::after { animation-delay: 1.2s; }
        @keyframes k-ring { from { transform: scale(1); opacity: .9; } to { transform: scale(1.25, 1.6); opacity: 0; } }
        .k-tap .k-tapico { width: 4rem; height: 4rem; border-radius: 999px; display: flex; align-items: center; justify-content: center;
                           background: linear-gradient(135deg, var(--blue), var(--indigo)); color: #fff; font-size: 2rem; }
        .k-tap b { font-size: 1.6rem; font-weight: 800; letter-spacing: -.01em; }
        .k-tap .k-fil { font-size: .95rem; color: var(--muted); opacity: 1; }

        /* ── 2. Menu ── */
        .k-choices { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1.5rem; margin-top: 2rem; }
        .k-choice { position: relative; overflow: hidden; display: flex; flex-direction: column; align-items: flex-start; gap: 1rem; padding: 2.25rem; border-radius: 2rem;
                    color: #fff; text-align: left; min-height: 23rem; cursor: pointer; border: 0; transition: transform .15s var(--ease); }
        .k-choice:active { transform: scale(.985); }
        .k-choice::before { content: ''; position: absolute; width: 28rem; height: 28rem; right: -9rem; top: -12rem; border-radius: 50%;
                            background: radial-gradient(circle, rgba(255,255,255,.32), transparent 68%); }
        .k-choice-doc { background: linear-gradient(140deg, #3b82f6 0%, #4f46e5 100%); box-shadow: 0 30px 60px -24px rgba(79,70,229,.7); }
        .k-choice-reg { background: linear-gradient(140deg, #10b981 0%, #0f766e 100%); box-shadow: 0 30px 60px -24px rgba(15,118,110,.7); }
        .k-choice-evt { background: linear-gradient(140deg, #f59e0b 0%, #ea580c 100%); box-shadow: 0 30px 60px -24px rgba(234,88,12,.7); }
        .k-choice > * { position: relative; }
        .k-choice .k-ghost { position: absolute; right: -1.5rem; bottom: -3rem; font-size: 15rem; opacity: .1; line-height: 1; }
        .k-ico { width: 5rem; height: 5rem; border-radius: 1.5rem; display: flex; align-items: center; justify-content: center; font-size: 2.6rem;
                 background: rgba(255,255,255,.2); border: 1px solid rgba(255,255,255,.35); }
        .k-choice h2 { font-size: clamp(1.6rem, 2.3vw, 2.15rem); font-weight: 800; letter-spacing: -.025em; line-height: 1.1; margin-top: .5rem; }
        .k-choice p { font-size: 1.15rem; opacity: .9; max-width: 30ch; line-height: 1.45; }
        .k-cta { margin-top: auto; width: 100%; display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding-top: .5rem; }
        .k-cta span { font-size: 1.25rem; font-weight: 700; }
        .k-arrow { width: 4rem; height: 4rem; border-radius: 999px; background: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 10px 24px -8px rgba(0,0,0,.35); }
        .k-choice-doc .k-arrow { color: var(--indigo); }
        .k-choice-reg .k-arrow { color: var(--teal); }
        .k-choice-evt .k-arrow { color: #ea580c; }

        /* ── 3. Scan ── */
        .k-scan-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; align-items: center; }
        .k-tips { list-style: none; display: grid; gap: .9rem; margin: 1.75rem 0; padding: 0; }
        .k-tips li { display: flex; align-items: center; gap: 1rem; font-size: 1.2rem; font-weight: 600; color: var(--ink-2); }
        .k-tips li span { flex-shrink: 0; width: 2.5rem; height: 2.5rem; border-radius: .9rem; background: var(--sky); color: var(--blue); font-weight: 800; display: flex; align-items: center; justify-content: center; }
        .k-note { display: flex; gap: .9rem; align-items: flex-start; padding: 1.1rem 1.25rem; border-radius: 1.25rem; background: rgba(255,255,255,.85); border: 1px solid var(--line); font-size: 1.1rem; color: var(--ink-2); }
        .k-note i { font-size: 1.6rem; color: var(--blue); }
        .k-stage { position: relative; aspect-ratio: 4 / 3.2; border-radius: 2rem; overflow: hidden; display: flex; align-items: center; justify-content: center;
                   background: radial-gradient(circle at 50% 35%, #1e40af 0%, #172554 55%, #0b1220 100%); box-shadow: 0 40px 80px -30px rgba(23,37,84,.75); }
        .k-stage::before { content: ''; position: absolute; inset: 0; background-image: radial-gradient(rgba(255,255,255,.12) 1px, transparent 1px); background-size: 22px 22px; }
        .k-idcard { position: relative; width: 66%; aspect-ratio: 1.586; border-radius: 1.1rem; background: linear-gradient(135deg, #ffffff, #e9efff); overflow: hidden;
                    box-shadow: 0 30px 50px -20px rgba(0,0,0,.6); animation: k-float 4.5s ease-in-out infinite; display: grid; grid-template-columns: 28% 1fr 32%; gap: 6%; padding: 15% 6% 7%; }
        @keyframes k-float { 0%,100% { transform: translateY(0) rotate(-2deg); } 50% { transform: translateY(-10px) rotate(-1deg); } }
        .k-idcard::before { content: ''; position: absolute; left: 0; right: 0; top: 0; height: 9%; background: linear-gradient(90deg, var(--blue), var(--indigo)); }
        .k-idphoto { border-radius: .5rem; background: linear-gradient(180deg, #c7d2fe, #a5b4fc); }
        .k-idlines { display: flex; flex-direction: column; gap: 12%; padding-top: 4%; }
        .k-idlines i { display: block; height: 9%; min-height: 6px; border-radius: 4px; background: #cbd5e1; }
        .k-idlines i:first-child { width: 90%; background: #94a3b8; }
        .k-idlines i:nth-child(2) { width: 70%; } .k-idlines i:nth-child(3) { width: 80%; } .k-idlines i:nth-child(4) { width: 55%; }
        .k-idqr { align-self: center; }
        .k-idqr svg { width: 100%; height: auto; display: block; }
        .k-corner { position: absolute; width: 2.6rem; height: 2.6rem; border: 4px solid #93c5fd; }
        .k-corner.tl { top: 13%; left: 11%; border-right: 0; border-bottom: 0; border-radius: 1rem 0 0 0; }
        .k-corner.tr { top: 13%; right: 11%; border-left: 0; border-bottom: 0; border-radius: 0 1rem 0 0; }
        .k-corner.bl { bottom: 13%; left: 11%; border-right: 0; border-top: 0; border-radius: 0 0 0 1rem; }
        .k-corner.br { bottom: 13%; right: 11%; border-left: 0; border-top: 0; border-radius: 0 0 1rem 0; }
        .k-beam { position: absolute; left: 12%; right: 12%; height: 3px; border-radius: 3px; background: linear-gradient(90deg, transparent, #60a5fa 20%, #e0f2fe 50%, #60a5fa 80%, transparent);
                  box-shadow: 0 0 24px 6px rgba(96,165,250,.55); animation: k-beam 2.4s ease-in-out infinite; }
        @keyframes k-beam { 0%,100% { top: 18%; } 50% { top: 80%; } }
        .k-status { display: inline-flex; align-items: center; gap: .75rem; margin-top: 1.25rem; padding: .9rem 1.4rem; border-radius: 999px; font-size: 1.15rem; font-weight: 700;
                    background: #fff; border: 1px solid var(--line); box-shadow: var(--shadow-sm); }
        .k-status .k-dot { width: .8rem; height: .8rem; border-radius: 99px; background: #22c55e; box-shadow: 0 0 0 0 rgba(34,197,94,.6); animation: k-ping 1.6s infinite; }
        @keyframes k-ping { 70% { box-shadow: 0 0 0 10px rgba(34,197,94,0); } 100% { box-shadow: 0 0 0 0 rgba(34,197,94,0); } }
        .k-status.wait { color: var(--blue); background: var(--sky); border-color: #c7d7fe; }
        .k-status.err { color: var(--red); background: var(--red-bg); border-color: #fecaca; border-radius: 1.25rem; align-items: flex-start; text-align: left; }
        .k-spin { display: inline-block; width: 1.3rem; height: 1.3rem; border: 3px solid currentColor; border-right-color: transparent; border-radius: 50%; animation: k-rot .8s linear infinite; flex-shrink: 0; }
        @keyframes k-rot { to { transform: rotate(360deg); } }

        /* ── 4. Hello + documents ── */
        .k-hello { display: flex; align-items: center; gap: 1.25rem; padding: 1.25rem 1.5rem; border-radius: 1.75rem; margin-bottom: 2rem;
                   background: linear-gradient(120deg, rgba(232,240,255,.95), rgba(230,247,241,.95)); border: 1px solid #dbe5ff; }
        .k-avatar { width: 5.25rem; height: 5.25rem; border-radius: 1.5rem; object-fit: cover; flex-shrink: 0; border: 4px solid #fff; box-shadow: var(--shadow-sm);
                    background: linear-gradient(135deg, var(--blue), var(--indigo)); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 2.2rem; font-weight: 800; }
        .k-hello h2 { font-size: 2.1rem; font-weight: 800; letter-spacing: -.025em; line-height: 1.05; }
        .k-hello p { color: var(--muted); font-size: 1.05rem; margin-top: .35rem; }
        .k-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(330px, 1fr)); gap: 1rem; margin-top: 1.5rem; }
        .k-doc { display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: 1.1rem; padding: 1.25rem 1.25rem 1.25rem 1.25rem; border-radius: 1.5rem;
                 background: #fff; border: 1px solid var(--line); box-shadow: var(--shadow-sm); text-align: left; cursor: pointer; min-height: 7.25rem;
                 transition: transform .12s var(--ease), box-shadow .2s, border-color .2s; }
        .k-doc:active { transform: scale(.98); border-color: var(--blue); }
        .k-docico { width: 3.75rem; height: 3.75rem; border-radius: 1.1rem; display: flex; align-items: center; justify-content: center; font-size: 1.9rem;
                    background: var(--sky); color: var(--blue); }
        .k-doc b { display: block; font-size: 1.3rem; font-weight: 800; letter-spacing: -.01em; line-height: 1.2; }
        .k-chev { width: 2.75rem; height: 2.75rem; border-radius: 99px; background: var(--sky); color: var(--blue); display: flex; align-items: center; justify-content: center; font-size: 1.3rem; }
        .k-doc.off { background: #f8fafc; box-shadow: none; cursor: default; }
        .k-doc.off .k-docico { background: #eef2f7; color: #94a3b8; }
        .k-doc.off b { color: var(--muted); }
        .k-why { display: block; margin-top: .45rem; font-size: .95rem; color: var(--amber); font-weight: 600; line-height: 1.35; }
        .k-fee { display: inline-flex; align-items: center; gap: .35rem; margin-top: .5rem; padding: .25rem .75rem; border-radius: 99px; font-size: 1rem; font-weight: 800; }
        .k-fee-free { background: var(--mint); color: var(--green); }
        .k-fee-paid { background: var(--amber-bg); color: var(--amber); }

        /* ── 5. Purpose ── */
        .k-chip { display: inline-flex; align-items: center; gap: .5rem; padding: .5rem 1rem .5rem .6rem; border-radius: 999px; background: #fff; border: 1px solid var(--line);
                  font-weight: 700; font-size: 1.05rem; box-shadow: var(--shadow-sm); }
        .k-chip i { width: 2rem; height: 2rem; border-radius: 99px; background: var(--sky); color: var(--blue); display: inline-flex; align-items: center; justify-content: center; }
        .k-pgrid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: .85rem; margin-top: 1.75rem; }
        .k-pbtn { display: flex; align-items: center; justify-content: space-between; gap: .75rem; min-height: 4.6rem; padding: .8rem 1rem .8rem 1.3rem; border-radius: 1.25rem;
                  background: #fff; border: 2px solid var(--line); font-size: 1.2rem; font-weight: 700; text-align: left; cursor: pointer; transition: all .15s var(--ease); }
        .k-pbtn .k-tick { flex-shrink: 0; width: 1.9rem; height: 1.9rem; border-radius: 99px; border: 2px solid #cbd5e1; display: flex; align-items: center; justify-content: center; color: transparent; font-size: 1.1rem; }
        .k-pbtn.other { border-style: dashed; color: var(--ink-2); }
        .k-pbtn.sel { border-color: var(--blue); background: var(--sky); color: #1e3a8a; box-shadow: 0 0 0 4px rgba(37,99,235,.12); }
        .k-pbtn.sel .k-tick { background: var(--blue); border-color: var(--blue); color: #fff; }
        .k-input { width: 100%; min-height: 4.2rem; padding: .9rem 1.2rem; border-radius: 1.1rem; border: 2px solid #cbd5e1; font-size: 1.35rem; background: #fff; color: var(--ink); }
        .k-input:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 5px rgba(37,99,235,.15); }
        .k-label { display: block; font-size: 1.15rem; font-weight: 800; margin-bottom: .55rem; }
        .k-section { margin-top: 2rem; }
        .k-foot { position: relative; z-index: 3; display: flex; align-items: center; gap: 1rem; margin: 0 1.25rem 1rem; padding: .75rem .75rem .75rem 1.4rem; border-radius: 1.6rem;
                  background: rgba(255,255,255,.8); border: 1px solid rgba(255,255,255,.95); -webkit-backdrop-filter: blur(18px) saturate(1.5); backdrop-filter: blur(18px) saturate(1.5);
                  box-shadow: 0 -2px 30px -10px rgba(16,24,40,.18); }
        .k-foot-info { min-width: 0; }
        .k-foot-info small { display: block; font-size: .9rem; color: var(--muted); font-weight: 600; }
        .k-foot-info b { display: block; font-size: 1.2rem; font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .k-foot .k-btn { margin-left: auto; }
        .k-alert { margin-top: 1.25rem; display: flex; gap: .75rem; align-items: flex-start; padding: 1rem 1.25rem; border-radius: 1.1rem; font-size: 1.15rem; font-weight: 600;
                   background: var(--red-bg); color: var(--red); border: 1px solid #fecaca; }
        .k-alert.wait { background: var(--sky); color: var(--blue); border-color: #c7d7fe; }

        /* ── 6. Preview ── */
        .k-prev { display: grid; grid-template-columns: minmax(0, 1fr) 380px; gap: 1.5rem; align-items: start; }
        .k-paper-box { border-radius: 1.75rem; padding: 1.25rem; overflow: hidden; background-color: #1e293b;
                       background-image: radial-gradient(rgba(255,255,255,.08) 1px, transparent 1px); background-size: 18px 18px; }
        .k-paper-scale { transform-origin: top left; }
        #review-paper { background: #fff; box-sizing: border-box; font-family: 'Times New Roman', Times, serif; font-size: 14px; line-height: 1.8; color: #111827;
                        box-shadow: 0 20px 40px -12px rgba(0,0,0,.6); -webkit-user-select: none; user-select: none; }
        @include('documents.requests._paper-css')
        .k-sum { padding: 1.6rem; border-radius: 1.75rem; background: #fff; border: 1px solid var(--line); box-shadow: var(--shadow); position: sticky; top: 0; }
        .k-sum h2 { font-size: 1.7rem; font-weight: 800; letter-spacing: -.02em; line-height: 1.15; }
        .k-sum h2 .k-fil { font-size: .55em; color: var(--muted); opacity: 1; }
        .k-sum dl { display: grid; gap: .5rem; margin: 1.25rem 0 1.4rem; }
        .k-row { display: flex; align-items: center; gap: .9rem; padding: .65rem .75rem; border-radius: 1rem; background: #f8fafc; }
        .k-row i { flex-shrink: 0; width: 2.5rem; height: 2.5rem; border-radius: .8rem; background: #fff; color: var(--blue); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; box-shadow: var(--shadow-sm); }
        .k-row dt { font-size: .85rem; color: var(--muted); font-weight: 600; }
        .k-row dd { font-size: 1.15rem; font-weight: 800; line-height: 1.25; }
        .k-row.fee { background: var(--amber-bg); }
        .k-row.fee i { color: var(--amber); }
        .k-row.fee.free { background: var(--mint); }
        .k-row.fee.free i { color: var(--green); }
        .k-sum-btns { display: grid; gap: .7rem; }
        .k-sum .k-btn { width: 100%; }

        /* ── 7. Done ── */
        .k-done { max-width: 860px; margin: 0 auto; text-align: center; }
        .k-success { position: relative; width: 5.5rem; height: 5.5rem; margin: 0 auto 1rem; border-radius: 99px; display: flex; align-items: center; justify-content: center;
                     background: linear-gradient(135deg, #10b981, var(--teal)); color: #fff; font-size: 2.8rem; box-shadow: 0 18px 36px -12px rgba(13,148,136,.7);
                     animation: k-pop .6s var(--ease) both; }
        .k-success::after { content: ''; position: absolute; inset: -10px; border-radius: inherit; border: 3px solid rgba(16,185,129,.35); animation: k-halo 2s ease-out infinite; }
        @keyframes k-pop { from { transform: scale(.4); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        @keyframes k-halo { from { transform: scale(.9); opacity: 1; } to { transform: scale(1.35); opacity: 0; } }
        .k-ticket { position: relative; margin: 1.5rem auto; max-width: 620px; border-radius: 1.75rem; background: #fff; box-shadow: var(--shadow); overflow: hidden; }
        .k-ticket-top { padding: 1.5rem 2rem 1.25rem; }
        .k-ticket-top small { font-size: 1.05rem; color: var(--muted); font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
        .k-qno { display: block; font-size: clamp(4.5rem, 11vw, 7.5rem); font-weight: 800; letter-spacing: -.04em; line-height: 1; margin-top: .3rem;
                 background: linear-gradient(135deg, var(--blue), var(--indigo)); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .k-ticket-cut { position: relative; height: 0; border-top: 2px dashed var(--line); margin: 0 1.5rem; }
        .k-ticket-cut::before, .k-ticket-cut::after { content: ''; position: absolute; top: -1rem; width: 2rem; height: 2rem; border-radius: 99px; background: var(--bg); }
        .k-ticket-cut::before { left: -2.6rem; } .k-ticket-cut::after { right: -2.6rem; }
        .k-ticket-bot { display: flex; align-items: center; justify-content: center; gap: .9rem; flex-wrap: wrap; padding: 1.1rem 1.5rem 1.4rem; }
        .k-ticket-bot b { font-size: 1.35rem; font-weight: 800; }
        .k-next { display: grid; grid-template-columns: repeat(3, 1fr); gap: .9rem; text-align: left; }
        .k-next div { padding: 1.1rem 1.2rem; border-radius: 1.25rem; background: rgba(255,255,255,.9); border: 1px solid var(--line); }
        .k-next i { width: 2.6rem; height: 2.6rem; border-radius: .85rem; background: var(--sky); color: var(--blue); display: flex; align-items: center; justify-content: center; font-size: 1.35rem; margin-bottom: .6rem; }
        .k-next p { font-size: 1.1rem; font-weight: 700; line-height: 1.35; }
        .k-next .k-fil { font-size: .85em; color: var(--muted); opacity: 1; font-weight: 500; }
        .k-done-btn { margin-top: 1.5rem; min-width: 18rem; }
        .k-done-btn .k-bar-fill { position: absolute; left: 0; bottom: 0; height: 5px; width: 100%; background: rgba(255,255,255,.55); transform-origin: left; }

        /* ── Events ── */
        .k-elist { display: grid; grid-template-columns: repeat(auto-fill, minmax(460px, 1fr)); gap: 1rem; margin-top: 1.5rem; }
        .k-evt { display: grid; grid-template-columns: 8.5rem 1fr; gap: 1.1rem; padding: 1rem; border-radius: 1.6rem; background: #fff; border: 1px solid var(--line);
                 box-shadow: var(--shadow-sm); text-align: left; }
        .k-evt-pic { width: 8.5rem; height: 100%; min-height: 8.5rem; border-radius: 1.15rem; object-fit: cover; display: flex; align-items: center; justify-content: center;
                     background: linear-gradient(140deg, #fbbf24, #ea580c); color: #fff; }
        .k-evt-pic.date { flex-direction: column; font-size: 1rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; }
        .k-evt-pic.date b { font-size: 3rem; line-height: 1; letter-spacing: -.03em; }
        .k-evt h3 { font-size: 1.4rem; font-weight: 800; letter-spacing: -.015em; line-height: 1.2; }
        .k-evt-meta { display: grid; gap: .3rem; margin-top: .5rem; color: var(--ink-2); font-size: 1.02rem; font-weight: 600; }
        .k-evt-meta span { display: flex; align-items: center; gap: .45rem; }
        .k-evt-meta i { color: var(--muted); font-size: 1.15rem; }
        .k-tags { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .6rem; }
        .k-tag { padding: .2rem .7rem; border-radius: 99px; font-size: .9rem; font-weight: 700; background: #f1f5f9; color: var(--ink-2); }
        .k-tag.raffle { background: var(--amber-bg); color: var(--amber); }
        .k-evt-act { grid-column: 1 / -1; display: flex; align-items: center; gap: .75rem; }
        .k-evt-act .k-btn { flex: 1; min-height: 4.2rem; font-size: 1.3rem; }
        .k-evt-act .k-why { margin: 0; flex: 1; font-size: 1.05rem; display: flex; gap: .5rem; align-items: center; padding: .85rem 1rem; border-radius: 1rem; background: var(--amber-bg); }
        .k-evt-act .k-why i { font-size: 1.35rem; }
        .k-evt-act .k-why.in { background: var(--mint); color: var(--green); }
        .k-evt.soon { background: #f8fafc; box-shadow: none; }
        .k-evt.soon .k-evt-pic { filter: grayscale(.5); opacity: .8; }
        .k-soon-h { grid-column: 1 / -1; margin-top: 1rem; font-size: 1.1rem; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: .05em; }
        #joined-next { grid-template-columns: repeat(var(--n, 3), 1fr); }
        /* "Checking in for" banner on the event scan screen */
        .k-evban { display: flex; align-items: center; gap: 1rem; margin-bottom: 1.25rem; padding: 1rem 1.25rem; border-radius: 1.5rem; color: #fff;
                   background: linear-gradient(135deg, #f59e0b, #ea580c); box-shadow: 0 20px 40px -20px rgba(234,88,12,.75); }
        .k-evban img, .k-evban .k-evban-ico { width: 4.5rem; height: 4.5rem; border-radius: 1rem; object-fit: cover; flex-shrink: 0;
                   background: rgba(255,255,255,.22); display: flex; align-items: center; justify-content: center; font-size: 2.2rem; }
        .k-evban small { display: block; font-size: .9rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; opacity: .85; }
        .k-evban b { display: block; font-size: 1.55rem; font-weight: 800; letter-spacing: -.015em; line-height: 1.15; margin-top: .1rem; }
        .k-evban span.meta { display: block; font-size: 1rem; font-weight: 600; opacity: .92; margin-top: .25rem; }
        .k-evban ul { margin: .3rem 0 0; padding-left: 1.2rem; font-size: 1.15rem; font-weight: 700; line-height: 1.4; }
        .k-evban.none { background: var(--amber-bg); color: var(--amber); box-shadow: none; border: 1px solid #fde3b0; }
        .k-evban.none .k-evban-ico { background: #fff; }
        .k-choice .k-evtoday { display: inline-block; margin-top: .25rem; padding: .35rem .85rem; border-radius: 99px; background: rgba(255,255,255,.22);
                   border: 1px solid rgba(255,255,255,.35); font-size: 1.05rem; font-weight: 800; }
        .k-joined-evt { margin-top: .35rem; font-size: clamp(1.8rem, 3.5vw, 2.6rem); font-weight: 800; letter-spacing: -.02em; line-height: 1.15; }

        /* ── Check your details ── */
        .k-banner { display: flex; gap: .85rem; align-items: center; margin-bottom: 1.25rem; padding: 1rem 1.25rem; border-radius: 1.25rem;
                    background: var(--amber-bg); color: var(--amber); border: 1px solid #fde3b0; font-size: 1.1rem; font-weight: 700; }
        .k-banner i { font-size: 1.6rem; }
        .k-dgrid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; margin-top: 1.25rem; }
        .k-drow { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.1rem; border-radius: 1.25rem; background: #fff; border: 1px solid var(--line); box-shadow: var(--shadow-sm); min-width: 0; }
        .k-drow > i { flex-shrink: 0; width: 3rem; height: 3rem; border-radius: 1rem; background: var(--sky); color: var(--blue); display: flex; align-items: center; justify-content: center; font-size: 1.45rem; }
        .k-drow small { display: block; font-size: .9rem; color: var(--muted); font-weight: 600; }
        .k-drow b { display: block; font-size: 1.2rem; font-weight: 800; line-height: 1.3; overflow-wrap: anywhere; }
        .k-drow .k-lock { margin-left: auto; flex-shrink: 0; font-size: .8rem; font-weight: 700; color: var(--muted); display: inline-flex; align-items: center; gap: .25rem; }
        .k-foot .k-foot-btns { margin-left: auto; display: flex; gap: .75rem; }
        .k-foot .k-foot-btns .k-btn { margin-left: 0; }

        /* ── Update my details ── */
        .k-fcard { margin-top: 1.25rem; padding: 1.4rem; border-radius: 1.5rem; background: #fff; border: 1px solid var(--line); box-shadow: var(--shadow-sm); }
        .k-fcard h3 { display: flex; align-items: center; gap: .6rem; font-size: 1.3rem; font-weight: 800; margin-bottom: 1rem; }
        .k-fcard h3 i { width: 2.4rem; height: 2.4rem; border-radius: .8rem; background: var(--sky); color: var(--blue); display: inline-flex; align-items: center; justify-content: center; font-size: 1.2rem; }
        .k-fgrid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem 1.25rem; }
        .k-fgrid .wide { grid-column: 1 / -1; }
        .k-hint { display: block; margin-top: .35rem; font-size: .95rem; color: var(--muted); }
        .k-opts { display: flex; flex-wrap: wrap; gap: .6rem; }
        .k-opt { min-height: 3.6rem; padding: .6rem 1.2rem; border-radius: 1rem; border: 2px solid var(--line); background: #fff; font-size: 1.15rem; font-weight: 700; cursor: pointer; }
        .k-opt.sel { border-color: var(--blue); background: var(--sky); color: #1e3a8a; box-shadow: 0 0 0 4px rgba(37,99,235,.12); }
        select.k-input { appearance: none; background-image: linear-gradient(45deg, transparent 50%, #64748b 50%), linear-gradient(135deg, #64748b 50%, transparent 50%);
                         background-position: calc(100% - 1.5rem) 50%, calc(100% - 1.1rem) 50%; background-size: .45rem .45rem; background-repeat: no-repeat; padding-right: 2.75rem; }

        /* ── Pop-ups ── */
        .k-overlay { position: fixed; inset: 0; z-index: 50; background: rgba(11,18,32,.45); -webkit-backdrop-filter: blur(8px); backdrop-filter: blur(8px);
                     display: flex; align-items: center; justify-content: center; padding: 1.5rem; animation: k-fade .2s ease both; }
        @keyframes k-fade { from { opacity: 0; } }
        .k-dialog { width: 100%; max-width: 560px; padding: 2.25rem; border-radius: 2rem; background: #fff; text-align: center; box-shadow: 0 40px 80px -20px rgba(0,0,0,.45); animation: k-pop .35s var(--ease) both; }
        .k-dlg-ico { width: 5rem; height: 5rem; margin: 0 auto; border-radius: 1.5rem; display: flex; align-items: center; justify-content: center; font-size: 2.6rem; background: var(--amber-bg); color: var(--amber); }
        .k-dialog h3 { font-size: 1.9rem; font-weight: 800; letter-spacing: -.02em; margin: 1rem 0 .5rem; }
        .k-dialog p { font-size: 1.2rem; color: var(--muted); line-height: 1.5; }
        .k-dialog .k-btn { margin-top: 1.75rem; width: 100%; }

        /* ── Tablet: portrait (or any narrow screen) ── */
        @media (orientation: portrait), (max-width: 760px) {
            .k-bar { margin: .75rem .75rem 0; }
            .k-body { padding: 1.5rem 1rem 2rem; }
            .k-stepnav li:not(.cur) .k-steplbl { display: none; }
            .k-stepnav li:not(.cur) { padding: .35rem; }
            .k-title { font-size: 2.2rem; }
            .k-choices { grid-template-columns: 1fr; gap: 1.1rem; margin-top: 1.5rem; }
            /* Three choices: compact rows (icon · title · arrow) so all fit without scrolling. */
            .k-choice { min-height: 0; display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: 1.25rem; padding: 1.75rem; }
            .k-choice h2 { margin: 0; font-size: 1.9rem; }
            .k-choice p, .k-cta > span:first-child { display: none; }
            .k-choice .k-evtoday { display: none; }
            .k-cta { margin: 0; width: auto; padding: 0; }
            .k-elist { grid-template-columns: 1fr; }
            .k-scan-grid { grid-template-columns: 1fr; gap: 1.5rem; }
            .k-stage { max-width: 560px; width: 100%; margin: 0 auto; }
            .k-scan-side { text-align: center; }
            .k-tips { display: none; }
            /* minmax(0, …) so the full-size paper inside can't stretch the column past the screen */
            .k-prev { grid-template-columns: minmax(0, 1fr); gap: 1rem; }
            .k-paper-box { max-height: max(36vh, calc(100vh - 560px)); overflow-y: auto; -webkit-overflow-scrolling: touch; }
            .k-sum { position: static; padding: 1.25rem; }
            .k-sum dl { grid-template-columns: 1fr 1fr; margin: 1rem 0; }
            .k-sum-btns { grid-template-columns: 3fr 2fr; }
            .k-sum-btns .k-btn-big { min-height: 4.4rem; font-size: 1.3rem; }
            .k-foot { margin: 0 .75rem .75rem; }
            .k-foot .k-btn { flex: 1; }
            .k-next, #joined-next { grid-template-columns: 1fr; }
            .k-dgrid, .k-fgrid { grid-template-columns: 1fr; }
            .k-foot .k-foot-btns { flex: 1; }
            .k-foot .k-foot-btns .k-btn { flex: 1; }
            .k-foot-info.k-hide-narrow { display: none; }
            .k-next div { display: flex; gap: 1rem; align-items: center; }
            .k-next i { margin: 0; flex-shrink: 0; }
            .k-done-btn { width: 100%; }
            .k-bottom { flex-direction: column; align-items: stretch; gap: 1.75rem; padding: 0 1.5rem 2.5rem; }
            .k-tap { align-self: center; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: .01ms !important; animation-iteration-count: 1 !important; transition-duration: .01ms !important; }
        }

        /* Narrower screens: only the current step keeps its label; the barangay name gives way to the logo. */
        @media (max-width: 1180px) {
            .k-stepnav li:not(.cur) .k-steplbl { display: none; }
            .k-stepnav li:not(.cur) { padding: .35rem; }
        }
        @media (max-width: 900px) {
            .k-bar:has(.k-stepnav) .k-brand > div { display: none; }
        }
        @media (max-width: 640px) {
            .k-stepnav { display: none; }
            .k-evt { grid-template-columns: 1fr; }
            .k-evt-pic { width: 100%; min-height: 7rem; max-height: 9rem; }
            .k-brand small { display: none; }
        }

        /* ── Phone (portrait, up to ~480px wide) ── */
        @media (max-width: 480px) {
            .k-bar { margin: .5rem .5rem 0; padding: .45rem .5rem .45rem .6rem; border-radius: 1.15rem; gap: .5rem; }
            .k-brand img { width: 2.3rem; height: 2.3rem; border-radius: .6rem; }
            .k-brand b { font-size: .95rem; }
            .k-bar .k-btn { min-height: 2.75rem; padding: .4rem .8rem; font-size: .95rem; border-radius: .85rem; gap: .35rem; }
            .k-body { padding: 1rem .75rem 1.5rem; }
            .k-eyebrow { font-size: .8rem; }
            .k-title { font-size: 1.65rem; }
            .k-sub { font-size: 1rem; }

            /* Start screen */
            .k-top { padding: .75rem; gap: .5rem; }
            .k-glass { padding: .45rem .75rem .45rem .45rem; gap: .6rem; border-radius: 1rem; min-width: 0; }
            .k-glass img { width: 2.4rem; height: 2.4rem; }
            .k-glass b { font-size: .95rem; }
            .k-glass small { font-size: .78rem; }
            .k-clock { padding: .45rem .8rem; flex-shrink: 0; }
            .k-clock b { font-size: 1.35rem; white-space: nowrap; }
            .k-caption { font-size: 1.45rem; }
            .k-bottom { padding: 0 1rem 1.5rem; gap: 1.1rem; }
            .k-tap { padding: .6rem 1.4rem .6rem .6rem; }
            .k-tap .k-tapico { width: 3.2rem; height: 3.2rem; font-size: 1.6rem; }
            .k-tap b { font-size: 1.3rem; }
            .k-seal { width: 7.5rem; height: 7.5rem; }
            .k-empty h1 { font-size: 2rem; }
            .k-empty p { font-size: 1.1rem; }

            /* Menu: three compact rows that fit on one screen */
            .k-choices { gap: .75rem; margin-top: 1rem; }
            .k-choice { grid-template-columns: auto minmax(0, 1fr) auto; gap: .85rem; padding: 1rem 1rem 1rem 1.1rem; border-radius: 1.4rem; }
            .k-choice h2 { font-size: 1.3rem; }
            .k-choice h2 .k-fil { font-size: .7em; }
            .k-choice .k-ghost { font-size: 8rem; }
            .k-ico { width: 3.25rem; height: 3.25rem; font-size: 1.7rem; border-radius: 1rem; }
            .k-arrow { width: 2.75rem; height: 2.75rem; font-size: 1.3rem; }

            /* Scan */
            .k-scan-grid { gap: 1rem; }
            .k-status { font-size: 1rem; padding: .7rem 1.1rem; }
            .k-note { font-size: 1rem; padding: .9rem 1rem; }

            /* "Hello, Maria" */
            .k-hello { gap: .85rem; padding: .85rem 1rem; border-radius: 1.25rem; margin-bottom: 1.1rem; }
            .k-avatar { width: 3.5rem; height: 3.5rem; border-radius: 1rem; font-size: 1.5rem; border-width: 3px; }
            .k-hello h2 { font-size: 1.45rem; }
            .k-hello p { font-size: .9rem; }

            /* Lists */
            .k-grid { grid-template-columns: minmax(0, 1fr); gap: .65rem; margin-top: 1rem; }
            .k-doc { gap: .8rem; padding: .9rem; min-height: 0; border-radius: 1.15rem; }
            .k-docico { width: 2.9rem; height: 2.9rem; font-size: 1.45rem; border-radius: .85rem; }
            .k-doc b { font-size: 1.1rem; }
            .k-chev { width: 2.25rem; height: 2.25rem; }
            .k-pgrid { grid-template-columns: minmax(0, 1fr); gap: .55rem; margin-top: 1rem; }
            .k-pbtn { min-height: 3.6rem; font-size: 1.05rem; padding: .6rem .9rem .6rem 1.1rem; }
            .k-dgrid { gap: .55rem; margin-top: .9rem; }
            .k-drow { gap: .75rem; padding: .75rem .85rem; border-radius: 1rem; }
            .k-drow > i { width: 2.4rem; height: 2.4rem; font-size: 1.2rem; border-radius: .8rem; }
            .k-drow b { font-size: 1.05rem; }
            .k-elist { gap: .75rem; margin-top: 1rem; }
            .k-evt { grid-template-columns: 4.5rem minmax(0, 1fr); padding: .8rem; border-radius: 1.25rem; gap: .8rem; }
            .k-evt-pic { width: 4.5rem; height: 4.5rem; min-height: 0; max-height: none; border-radius: .9rem; }
            .k-evt-pic.date { font-size: .75rem; }
            .k-evt-pic.date b { font-size: 1.7rem; }
            .k-evt h3 { font-size: 1.2rem; }
            .k-evt-act .k-btn { min-height: 3.5rem; font-size: 1.15rem; }
            .k-evban { gap: .75rem; padding: .8rem 1rem; border-radius: 1.15rem; }
            .k-evban img, .k-evban .k-evban-ico { width: 3.25rem; height: 3.25rem; font-size: 1.6rem; }
            .k-evban b { font-size: 1.2rem; }

            /* Update my details */
            .k-fcard { padding: 1rem; border-radius: 1.15rem; }
            .k-fcard h3 { font-size: 1.15rem; }
            .k-input { min-height: 3.4rem; font-size: 1.1rem; padding: .7rem 1rem; border-radius: .9rem; }
            .k-label { font-size: 1rem; }
            .k-opt { min-height: 3rem; font-size: 1rem; padding: .5rem .95rem; }

            /* Bottom bar: buttons share the width, labels stay on one line */
            .k-foot { margin: 0 .5rem .5rem; padding: .5rem; gap: .5rem; border-radius: 1.2rem; flex-wrap: wrap; }
            .k-foot-info { flex: 1 1 100%; padding: .1rem .4rem 0; }
            .k-foot-info small { font-size: .8rem; }
            .k-foot-info b { font-size: 1rem; }
            .k-foot .k-btn-big, .k-foot .k-foot-btns .k-btn { min-height: 3.5rem; font-size: 1rem; padding: .55rem .7rem; border-radius: 1rem; gap: .4rem; white-space: nowrap; min-width: 0; }
            .k-foot .k-foot-btns { gap: .5rem; min-width: 0; }

            /* Preview: summary in one column */
            .k-paper-box { padding: .6rem; border-radius: 1.15rem; max-height: 40vh; }
            .k-sum { padding: 1rem; border-radius: 1.25rem; }
            .k-sum h2 { font-size: 1.35rem; }
            .k-sum dl { grid-template-columns: minmax(0, 1fr); gap: .4rem; }
            .k-row dd { font-size: 1.05rem; }
            .k-sum-btns { grid-template-columns: minmax(0, 1fr); }
            .k-sum-btns .k-btn-big { min-height: 3.6rem; font-size: 1.1rem; }

            /* Done / checked in */
            .k-success { width: 4rem; height: 4rem; font-size: 2rem; }
            .k-ticket { margin: 1rem auto; border-radius: 1.35rem; }
            .k-ticket-top { padding: 1rem 1.25rem .85rem; }
            .k-qno { font-size: 4rem; }
            .k-joined-evt { font-size: 1.5rem; }
            .k-next p { font-size: 1rem; }

            /* Pop-ups */
            .k-overlay { padding: .75rem; }
            .k-dialog { padding: 1.5rem 1.25rem; border-radius: 1.5rem; }
            .k-dialog h3 { font-size: 1.45rem; }
            .k-dialog p { font-size: 1.05rem; }
            .k-dialog .k-btn { margin-top: 1.25rem; }
        }

        /* Small phones (360px and below): bottom-bar buttons drop their icons to keep the labels whole. */
        @media (max-width: 380px) {
            .k-foot .k-btn-big, .k-foot .k-foot-btns .k-btn { font-size: .95rem; padding: .5rem .55rem; }
            .k-foot .k-foot-btns .k-btn > i { display: none; }
        }

        /* ── Tablet: landscape with a short screen (e.g. 1024 × 600–768) ── */
        @media (orientation: landscape) and (max-height: 820px) {
            .k-body { padding-top: 1.25rem; }
            .k-title { font-size: clamp(1.8rem, 3.2vw, 2.3rem); }
            .k-choices { margin-top: 1.25rem; }
            .k-choice { min-height: 50vh; padding: 1.5rem; }
            .k-choice p { font-size: 1.05rem; }
            .k-choice h2 { font-size: 1.9rem; }
            .k-ico { width: 4rem; height: 4rem; font-size: 2.1rem; }
            .k-tips { margin: 1.1rem 0; gap: .6rem; }
            .k-tips li { font-size: 1.1rem; }
            .k-hello { margin-bottom: 1.1rem; padding: 1rem 1.25rem; }
            .k-avatar { width: 4.25rem; height: 4.25rem; }
            .k-success { display: none; }
            .k-ticket { margin: 1rem auto; }
            .k-ticket-top { padding: 1rem 2rem .9rem; }
            .k-qno { font-size: clamp(3.5rem, 9vw, 5.5rem); }
            .k-next p { font-size: 1rem; }
            .k-done-btn { margin-top: 1rem; }
            .k-seal { width: 9rem; height: 9rem; }
            .k-bottom { padding-bottom: 1.75rem; }
        }
        /* ── Phone turned sideways (short screen): everything compact so the content has room ── */
        @media (orientation: landscape) and (max-height: 500px) {
            .k-bar { margin: .4rem .6rem 0; padding: .3rem .4rem .3rem .6rem; border-radius: 1rem; }
            .k-brand img { width: 2rem; height: 2rem; }
            .k-brand small { display: none; }
            .k-bar .k-btn { min-height: 2.4rem; padding: .3rem .75rem; font-size: .9rem; }
            .k-stepnav li { padding: .2rem .6rem .2rem .25rem; font-size: .85rem; }
            .k-stepnum { width: 1.6rem; height: 1.6rem; }
            .k-body { padding: .75rem .75rem 1rem; }
            .k-title { font-size: 1.45rem; }
            .k-sub, .k-tips { display: none; }
            .k-top { padding: .6rem .75rem; }
            .k-bottom { padding: 0 1rem 1rem; }
            .k-caption { font-size: 1.3rem; }
            .k-seal { width: 5.5rem; height: 5.5rem; }
            /* Menu: three short cards side by side */
            .k-choices { gap: .75rem; margin-top: .75rem; }
            .k-choice { min-height: 0; padding: 1rem; gap: .5rem; border-radius: 1.25rem; }
            .k-choice p, .k-choice .k-evtoday, .k-choice .k-ghost, .k-cta > span:first-child { display: none; }
            .k-choice h2 { font-size: 1.2rem; margin: 0; }
            .k-ico { width: 2.75rem; height: 2.75rem; font-size: 1.45rem; border-radius: .85rem; }
            .k-cta { margin-top: .25rem; padding: 0; }
            .k-arrow { width: 2.4rem; height: 2.4rem; font-size: 1.1rem; }
            /* Scan */
            .k-scan-grid { grid-template-columns: 1fr 1fr; gap: 1rem; }
            .k-stage { max-width: 320px; }
            /* Hello card and lists */
            .k-hello { padding: .6rem .9rem; margin-bottom: .75rem; gap: .75rem; border-radius: 1.1rem; }
            .k-avatar { width: 2.9rem; height: 2.9rem; font-size: 1.3rem; border-radius: .85rem; border-width: 2px; }
            .k-hello h2 { font-size: 1.3rem; }
            .k-hello p { font-size: .85rem; margin-top: .15rem; }
            .k-dgrid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .5rem; margin-top: .5rem; }
            .k-drow { padding: .6rem .75rem; gap: .65rem; }
            .k-drow > i { width: 2.2rem; height: 2.2rem; font-size: 1.1rem; }
            .k-drow b { font-size: 1rem; }
            .k-doc { min-height: 0; padding: .75rem; }
            .k-pbtn { min-height: 3.2rem; font-size: 1rem; }
            /* Bottom bar */
            .k-foot { margin: 0 .6rem .4rem; padding: .4rem; border-radius: 1rem; }
            .k-foot-info.k-hide-narrow { display: none; }
            .k-foot-info small { font-size: .78rem; }
            .k-foot-info b { font-size: .95rem; }
            .k-foot .k-btn-big, .k-foot .k-foot-btns .k-btn { min-height: 2.9rem; font-size: 1rem; padding: .45rem 1rem; border-radius: .9rem; white-space: nowrap; }
            /* Done */
            .k-success { display: none; }
            .k-ticket { margin: .75rem auto; }
            .k-qno { font-size: 3.25rem; }
            .k-next { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .k-dialog { padding: 1.25rem; max-width: 460px; }
            .k-dlg-ico { width: 3.5rem; height: 3.5rem; font-size: 1.8rem; }
            .k-dialog h3 { font-size: 1.35rem; margin: .6rem 0 .35rem; }
            .k-dialog .k-btn { margin-top: 1rem; min-height: 3.2rem; }
        }

    </style>
</head>
<body>
<div class="k-glow" aria-hidden="true">
    <span class="k-blob b1"></span><span class="k-blob b2"></span><span class="k-blob b3"></span><span class="k-blob b4"></span><span class="k-blob b5"></span>
    <span class="k-shape s1"></span><span class="k-shape s2"></span><span class="k-shape s3"></span><span class="k-shape s4"></span>
</div>

{{-- 1. Start screen: picture carousel, tap anywhere to start --}}
<section id="s-attract" class="k-screen k-attract">
    @forelse($slides as $i => $s)
    <div class="k-slide {{ $i === 0 ? 'on' : '' }}" data-caption="{{ $s->caption }}">
        <img src="{{ $s->url }}" alt="">
    </div>
    @empty
    <div class="k-empty">
        @if($logo)<div class="k-seal"><img src="{{ $logo }}" alt=""></div>@endif
        <h1>{{ $bName }}</h1>
        @if($place)<p>{{ $place }}</p>@endif
    </div>
    @endforelse
    <div class="k-shade"></div>

    <div class="k-top">
        <div class="k-glass">
            @if($logo)<img src="{{ $logo }}" alt="">@endif
            <div><b>{{ $bName }}</b>@if($place)<small>{{ $place }}</small>@endif</div>
        </div>
        <div class="k-glass k-clock"><b id="clock-time">--:--</b><small id="clock-date"></small></div>
    </div>

    <div class="k-bottom">
        <div>
            @if($slides->count() > 1)
            <div class="k-segs">@foreach($slides as $i => $s)<span class="{{ $i === 0 ? 'on' : '' }}"><i></i></span>@endforeach</div>
            @endif
            <div class="k-caption" id="slide-caption">{{ $slides->first()?->caption ?: ($slides->isEmpty() ? 'Self-service kiosk' : '') }}</div>
        </div>
        <div class="k-tap">
            <span class="k-tapico"><i class="mgc_finger_tap_line"></i></span>
            <span><b>Tap to start</b><span class="k-fil">Pindutin para magsimula</span></span>
        </div>
    </div>
</section>

{{-- 2. Menu: documents, events or register --}}
<section id="s-menu" class="k-screen k-hide">
    @include('kiosk._bar', ['back' => '', 'step' => null])
    <div class="k-body"><div class="k-wrap">
        <span class="k-eyebrow" id="greet">Welcome</span>
        <h1 class="k-title">What would you like to do?<span class="k-fil">Ano ang nais mong gawin?</span></h1>
        <div class="k-choices">
            <button type="button" class="k-choice k-choice-doc" onclick="K.start('docs')">
                <i class="k-ghost mgc_file_certificate_line"></i>
                <span class="k-ico"><i class="mgc_file_certificate_line"></i></span>
                <h2>Request a document<span class="k-fil">Humiling ng dokumento</span></h2>
                <p>Barangay clearance, certificates and more. Have your Barangay ID ready.</p>
                <span class="k-cta"><span>Scan my ID</span><span class="k-arrow"><i class="mgc_arrow_right_line"></i></span></span>
            </button>
            <button type="button" class="k-choice k-choice-evt" onclick="K.start('events')">
                <i class="k-ghost mgc_calendar_line"></i>
                <span class="k-ico"><i class="mgc_calendar_line"></i></span>
                <h2>Join an event<span class="k-fil">Sumali sa event</span></h2>
                <p>Check in to today's barangay event with your Barangay ID.</p>
                <span class="k-evtoday k-hide" id="menu-evt"></span>
                <span class="k-cta"><span>Scan my ID</span><span class="k-arrow"><i class="mgc_arrow_right_line"></i></span></span>
            </button>
            <button type="button" class="k-choice k-choice-reg" onclick="K.register()">
                <i class="k-ghost mgc_user_add_line"></i>
                <span class="k-ico"><i class="mgc_user_add_line"></i></span>
                <h2>Register as a resident<span class="k-fil">Magparehistro bilang residente</span></h2>
                <p>New to the barangay or not yet listed? Fill in the form — staff will approve it.</p>
                <span class="k-cta"><span>Start registration</span><span class="k-arrow"><i class="mgc_arrow_right_line"></i></span></span>
            </button>
        </div>
    </div></div>
</section>

{{-- 3. Scan the Barangay ID --}}
<section id="s-scan" class="k-screen k-hide">
    @include('kiosk._bar', ['back' => 'menu', 'step' => 1])
    <div class="k-body"><div class="k-wrap">
        <div class="k-scan-grid">
            <div class="k-scan-side">
                <div id="scan-event" class="k-hide"></div>
                <span class="k-eyebrow"><i class="mgc_qrcode_line"></i> <span id="scan-step">Step 1 of 5</span></span>
                <h1 class="k-title" id="scan-title">Scan your Barangay ID<span class="k-fil">I-scan ang iyong Barangay ID</span></h1>
                <ol class="k-tips">
                    <li><span>1</span> Find the QR code on your Barangay ID.</li>
                    <li><span>2</span> Hold it under the scanner's light.</li>
                    <li><span>3</span> Wait for the beep — that's it!</li>
                </ol>
                <div id="scan-msg"></div>
            </div>
            <div>
                <div class="k-stage">
                    <div class="k-idcard">
                        <div class="k-idphoto"></div>
                        <div class="k-idlines"><i></i><i></i><i></i><i></i></div>
                        <div class="k-idqr">
                            <svg viewBox="0 0 21 21" shape-rendering="crispEdges" aria-hidden="true">
                                <path fill="#0b1220" d="M0 0h7v7H0zM14 0h7v7h-7zM0 14h7v7H0z"/>
                                <path fill="#fff" d="M1 1h5v5H1zM15 1h5v5h-5zM1 15h5v5H1z"/>
                                <path fill="#0b1220" d="M2 2h3v3H2zM16 2h3v3h-3zM2 16h3v3H2zM8 0h1v2H8zM10 1h2v1h-2zM9 3h2v2H9zM12 4h1v3h-1zM8 6h2v1H8zM8 8h3v1H8zM12 8h2v2h-2zM15 8h1v1h-1zM17 8h4v1h-4zM0 8h2v1H0zM3 8h3v2H3zM1 10h1v2H1zM8 10h1v3H8zM10 11h2v1h-2zM14 11h3v1h-3zM18 10h2v2h-2zM3 12h4v1H3zM10 13h1v2h-1zM12 13h3v2h-3zM16 13h1v1h-1zM18 13h3v1h-3zM8 15h1v1H8zM16 15h2v2h-2zM19 15h2v1h-2zM9 17h3v1H9zM13 16h2v1h-2zM14 18h3v1h-3zM8 19h2v2H8zM11 19h2v1h-2zM18 18h1v3h-1zM20 19h1v2h-1z"/>
                            </svg>
                        </div>
                    </div>
                    <span class="k-corner tl"></span><span class="k-corner tr"></span><span class="k-corner bl"></span><span class="k-corner br"></span>
                    <span class="k-beam"></span>
                </div>
            </div>
        </div>
        <div class="k-note" style="margin-top:1.75rem"><i class="mgc_information_line"></i><span>No Barangay ID yet? Please go to the counter.<span class="k-fil">Wala pang Barangay ID? Pumunta po sa counter.</span></span></div>
    </div></div>
</section>

{{-- 3b. Check your details (right after the scan) --}}
<section id="s-details" class="k-screen k-hide">
    @include('kiosk._bar', ['back' => 'scan', 'step' => 2])
    <div class="k-body"><div class="k-wrap">
        <div class="k-hello">
            <div id="det-photo"></div>
            <div>
                <h2 id="det-name"></h2>
                <p>Are these your details? <span style="opacity:.8">· Tama ba ang iyong mga detalye?</span></p>
            </div>
        </div>
        <div id="det-pending"></div>
        <div class="k-dgrid" id="det-grid"></div>
        <div class="k-note" style="margin-top:1.25rem"><i class="mgc_lock_line"></i><span>Wrong name, birthday or sex? Please tell the counter — these need a valid ID to change.<span class="k-fil">Mali ang pangalan, kaarawan o kasarian? Sabihin po sa counter.</span></span></div>
    </div></div>
    <div class="k-foot">
        <div class="k-foot-info k-hide-narrow"><small>Not you? Tap Home.</small><b>Check, then continue</b></div>
        <div class="k-foot-btns">
            <button type="button" class="k-btn k-btn-ghost k-btn-big" onclick="K.openUpdate()"><i class="mgc_edit_line"></i> Update my details</button>
            <button type="button" class="k-btn k-btn-green k-btn-big" id="det-ok" onclick="K.detailsOk()"><i class="mgc_check_line"></i> <span id="det-ok-label">Yes, all correct</span></button>
        </div>
    </div>
</section>

{{-- 3c. Update my details (sent to staff for approval) --}}
<section id="s-update" class="k-screen k-hide">
    @include('kiosk._bar', ['back' => 'details', 'step' => 2])
    <div class="k-body"><div class="k-wrap" style="max-width:980px">
        <span class="k-eyebrow"><i class="mgc_edit_line"></i> Update my details</span>
        <h1 class="k-title">Change only what is wrong<span class="k-fil">Baguhin lamang ang mali</span></h1>
        <p class="k-sub">Staff will check your changes before they are saved.</p>

        <div class="k-fcard">
            <h3><i class="mgc_phone_line"></i> Contact</h3>
            <div class="k-fgrid">
                <div><label class="k-label" for="u-contact">Mobile number</label>
                    <input type="tel" id="u-contact" class="k-input" maxlength="11" inputmode="numeric" autocomplete="off" placeholder="09XXXXXXXXX">
                    <span class="k-hint" id="u-contact-hint"></span></div>
                <div><label class="k-label" for="u-email">Email</label>
                    <input type="email" id="u-email" class="k-input" maxlength="250" autocomplete="off" placeholder="name@example.com">
                    <span class="k-hint" id="u-email-hint"></span></div>
            </div>
        </div>

        <div class="k-fcard">
            <h3><i class="mgc_heart_line"></i> Civil status &amp; work</h3>
            <div class="k-opts" id="u-civil"></div>
            <div style="margin-top:1.1rem"><label class="k-label" for="u-occupation">Occupation</label>
                <input type="text" id="u-occupation" class="k-input" maxlength="250" autocomplete="off" placeholder="e.g. Teacher, Vendor, Retired"></div>
        </div>

        <div class="k-fcard">
            <h3><i class="mgc_map_pin_line"></i> Address</h3>
            <div class="k-fgrid">
                <div class="wide"><label class="k-label" for="u-address">Zone / area</label>
                    <select id="u-address" class="k-input">
                        @foreach($zones as $z)<option value="{{ $z->id }}" data-subd="{{ $z->is_subd }}" data-rules="{{ $z->rules_required }}">{{ $z->description }}</option>@endforeach
                    </select></div>
                <div data-part="blk"><label class="k-label" for="u-blk">Block no.</label><input type="number" id="u-blk" class="k-input" min="1" inputmode="numeric"></div>
                <div data-part="lot"><label class="k-label" for="u-lot">Lot no.</label><input type="number" id="u-lot" class="k-input" min="1" inputmode="numeric"></div>
                <div data-part="phase"><label class="k-label" for="u-phase">Phase</label><input type="number" id="u-phase" class="k-input" min="1" inputmode="numeric"></div>
                <div data-part="no"><label class="k-label" for="u-no">House no.</label><input type="text" id="u-no" class="k-input" maxlength="50"></div>
                <div data-part="street" class="wide"><label class="k-label" for="u-street">Street</label><input type="text" id="u-street" class="k-input" maxlength="250"></div>
                <div data-part="complete_address" class="wide"><label class="k-label" for="u-complete_address">Complete address</label><textarea id="u-complete_address" class="k-input" rows="2" maxlength="500"></textarea></div>
            </div>
        </div>

        <div class="k-fcard">
            <h3><i class="mgc_check_circle_line"></i> Voter</h3>
            <div class="k-opts" id="u-voters">
                <button type="button" class="k-opt" data-v="1">Yes, registered voter</button>
                <button type="button" class="k-opt" data-v="0">No</button>
            </div>
            <div id="u-precinct-box" style="margin-top:1.1rem"><label class="k-label" for="u-pricinct_no">Precinct no.</label>
                <input type="text" id="u-pricinct_no" class="k-input" minlength="5" maxlength="6" style="text-transform:uppercase" placeholder="e.g. 0055F"></div>
        </div>

        <div class="k-fcard">
            <h3><i class="mgc_phone_call_line"></i> Emergency contact</h3>
            <div class="k-fgrid">
                <div><label class="k-label" for="u-ic_fullname">Full name</label><input type="text" id="u-ic_fullname" class="k-input" maxlength="250"></div>
                <div><label class="k-label" for="u-ic_relationship">Relationship</label><input type="text" id="u-ic_relationship" class="k-input" maxlength="250" placeholder="e.g. Daughter"></div>
                <div><label class="k-label" for="u-ic_contact">Mobile number</label><input type="tel" id="u-ic_contact" class="k-input" maxlength="11" inputmode="numeric" placeholder="09XXXXXXXXX"><span class="k-hint" id="u-ic_contact-hint"></span></div>
                <div><label class="k-label" for="u-ic_email">Email</label><input type="email" id="u-ic_email" class="k-input" maxlength="250"><span class="k-hint" id="u-ic_email-hint"></span></div>
                <div class="wide"><label class="k-label" for="u-ic_address">Address</label><input type="text" id="u-ic_address" class="k-input" maxlength="500"></div>
            </div>
        </div>
        <div id="update-msg"></div>
    </div></div>
    <div class="k-foot">
        <div class="k-foot-info k-hide-narrow"><small>Nothing changes until staff approve</small><b>Review, then send</b></div>
        <div class="k-foot-btns">
            <button type="button" class="k-btn k-btn-ghost k-btn-big" onclick="K.go('details')"><i class="mgc_close_line"></i> Cancel</button>
            <button type="button" class="k-btn k-btn-primary k-btn-big" id="update-send" onclick="K.sendUpdate()"><i class="mgc_send_plane_line"></i> Send changes</button>
        </div>
    </div>
</section>

{{-- 3d. Join an event --}}
<section id="s-events" class="k-screen k-hide">
    @include('kiosk._bar', ['back' => 'details', 'step' => 3])
    <div class="k-body"><div class="k-wrap">
        <div class="k-hello">
            <div id="evt-photo"></div>
            <div>
                <h2 id="evt-name"></h2>
                <p>Not you? Tap <b>Home</b> and scan your own ID.</p>
            </div>
        </div>
        <h1 class="k-title">Choose an event to join<span class="k-fil">Pumili ng event na sasalihan</span></h1>
        <div class="k-elist" id="evt-list"></div>
    </div></div>
</section>

{{-- 3e. Checked in to an event --}}
<section id="s-joined" class="k-screen k-hide">
    @include('kiosk._bar', ['back' => '', 'step' => null])
    <div class="k-body"><div class="k-done">
        <div class="k-success"><i class="mgc_check_line"></i></div>
        <h1 class="k-title">You're checked in!<span class="k-fil">Naka-check in ka na!</span></h1>
        <div class="k-ticket">
            <div class="k-ticket-top">
                <small>Event</small>
                <div class="k-joined-evt" id="joined-event"></div>
            </div>
            <div class="k-ticket-cut"></div>
            <div class="k-ticket-bot"><b id="joined-name"></b><span class="k-fee k-fee-free" id="joined-time"></span></div>
        </div>
        <div class="k-next" id="joined-next"></div>
        <button type="button" class="k-btn k-btn-primary k-btn-big k-done-btn" onclick="K.nextResident()">
            <i class="mgc_qrcode_line"></i> Next resident <span id="joined-count" style="opacity:.75;font-weight:600"></span>
            <span class="k-bar-fill" id="joined-bar"></span>
        </button>
    </div></div>
</section>

{{-- 4. Hello + choose a document --}}
<section id="s-docs" class="k-screen k-hide">
    @include('kiosk._bar', ['back' => 'details', 'step' => 3])
    <div class="k-body"><div class="k-wrap">
        <div class="k-hello">
            <div id="hello-photo"></div>
            <div>
                <h2 id="hello-name"></h2>
                <p>Not you? Tap <b>Home</b> and scan your own ID.</p>
            </div>
        </div>
        <h1 class="k-title">Choose a document<span class="k-fil">Pumili ng dokumento</span></h1>
        <div class="k-grid" id="doc-grid"></div>
    </div></div>
</section>

{{-- 5. Purpose (+ any details the document needs) --}}
<section id="s-purpose" class="k-screen k-hide">
    @include('kiosk._bar', ['back' => 'docs', 'step' => 4])
    <div class="k-body"><div class="k-wrap">
        <span class="k-chip"><i class="mgc_file_certificate_line"></i> <span id="purpose-doc"></span></span>
        <h1 class="k-title" style="margin-top:1rem">What is it for?<span class="k-fil">Para saan ito?</span></h1>
        <div class="k-pgrid" id="purpose-grid"></div>
        <div id="purpose-other" class="k-section k-hide">
            <label class="k-label" for="purpose-text">Type the purpose <span style="font-weight:500;color:var(--muted)">· Isulat ang layunin</span></label>
            <input type="text" id="purpose-text" class="k-input" maxlength="255" autocomplete="off" placeholder="e.g. Scholarship application">
        </div>
        <div id="extra-fields" class="k-section k-hide">
            <h2 class="k-title" style="font-size:1.6rem">A few more details<span class="k-fil">Ilang detalye pa</span></h2>
            <div id="extra-list" style="display:grid;gap:1.25rem;margin-top:1.25rem"></div>
        </div>
        <div id="purpose-msg"></div>
    </div></div>
    <div class="k-foot">
        <div class="k-foot-info"><small id="purpose-fee"></small><b id="purpose-pick">Choose a purpose</b></div>
        <button type="button" class="k-btn k-btn-primary k-btn-big" id="purpose-next" onclick="K.preview()" disabled>
            Next: check the document <i class="mgc_arrow_right_line"></i>
        </button>
    </div>
</section>

{{-- 6. Preview: is everything correct? --}}
<section id="s-preview" class="k-screen k-hide">
    @include('kiosk._bar', ['back' => 'purpose', 'step' => 5])
    <div class="k-body"><div class="k-wrap" style="max-width:1320px">
        <div class="k-prev">
            <div class="k-paper-box" id="paper-box">
                <div class="k-paper-scale" id="paper-scale"><div id="review-paper"></div></div>
            </div>
            <div class="k-sum">
                <h2>Is everything correct?<span class="k-fil">Tama ba ang lahat?</span></h2>
                <dl>
                    <div class="k-row"><i class="mgc_user_3_line"></i><div><dt>Name</dt><dd id="sum-name"></dd></div></div>
                    <div class="k-row"><i class="mgc_file_certificate_line"></i><div><dt>Document</dt><dd id="sum-doc"></dd></div></div>
                    <div class="k-row"><i class="mgc_target_line"></i><div><dt>Purpose</dt><dd id="sum-purpose"></dd></div></div>
                    <div class="k-row fee" id="sum-fee-row"><i class="mgc_wallet_3_line"></i><div><dt>Fee</dt><dd id="sum-fee"></dd></div></div>
                </dl>
                <div id="preview-msg"></div>
                <div class="k-sum-btns">
                    <button type="button" class="k-btn k-btn-green k-btn-big" id="btn-submit" onclick="K.submit()">
                        <i class="mgc_check_line"></i> <span>Yes, send request<span class="k-fil">Oo, ipadala</span></span>
                    </button>
                    <button type="button" class="k-btn k-btn-ghost" onclick="K.wrong()">
                        <i class="mgc_close_line"></i> Something is wrong
                    </button>
                </div>
            </div>
        </div>
    </div></div>
</section>

{{-- 7. Done: queue number --}}
<section id="s-done" class="k-screen k-hide">
    @include('kiosk._bar', ['back' => '', 'step' => null])
    <div class="k-body"><div class="k-done">
        <div class="k-success"><i class="mgc_check_line"></i></div>
        <h1 class="k-title">Request sent!<span class="k-fil">Naipadala na ang iyong request!</span></h1>
        <div class="k-ticket">
            <div class="k-ticket-top">
                <small>Your number · Ang iyong numero</small>
                <b class="k-qno" id="done-queue"></b>
            </div>
            <div class="k-ticket-cut"></div>
            <div class="k-ticket-bot"><b id="done-doc"></b><span class="k-fee" id="done-chip"></span></div>
        </div>
        <div class="k-next">
            <div><i class="mgc_counter_line"></i><p>Go to the counter and tell them your number.<span class="k-fil">Pumunta sa counter at sabihin ang numero.</span></p></div>
            <div><i class="mgc_wallet_3_line"></i><p id="done-fee"></p></div>
            <div><i class="mgc_print_line"></i><p>Staff will check your request and print it.<span class="k-fil">Susuriin at ipi-print ng staff.</span></p></div>
        </div>
        <button type="button" class="k-btn k-btn-primary k-btn-big k-done-btn" onclick="K.home()">
            <i class="mgc_home_3_line"></i> Done <span id="done-count" style="opacity:.75;font-weight:600"></span>
            <span class="k-bar-fill" id="done-bar"></span>
        </button>
    </div></div>
</section>

{{-- Message pop-up --}}
<div id="o-msg" class="k-overlay k-hide">
    <div class="k-dialog">
        <div class="k-dlg-ico" id="o-msg-box"><i id="o-msg-icon" class="mgc_information_line"></i></div>
        <h3 id="o-msg-title"></h3>
        <p id="o-msg-text"></p>
        <button type="button" class="k-btn k-btn-primary k-btn-big" id="o-msg-btn">OK</button>
    </div>
</div>

{{-- Still there? --}}
<div id="o-idle" class="k-overlay k-hide">
    <div class="k-dialog">
        <div class="k-dlg-ico" style="background:var(--sky);color:var(--blue)"><i class="mgc_time_line"></i></div>
        <h3>Are you still there?<span class="k-fil" style="font-size:.6em;color:var(--muted);opacity:1">Nandiyan ka pa ba?</span></h3>
        <p>Going back to the start in <b id="idle-count">15</b> seconds.</p>
        <button type="button" class="k-btn k-btn-primary k-btn-big" onclick="K.stillHere()">Yes, continue</button>
    </div>
</div>

<script>
const K = (() => {
    const URLS = {
        scan: @json(route('kiosk.scan')),
        preview: @json(route('kiosk.preview')),
        submit: @json(route('kiosk.submit')),
        register: @json(route('kiosk.register')),
        update: @json(route('kiosk.update')),
        join: @json(route('kiosk.join')),
        events: @json(route('kiosk.events')),
    };
    const CIVILS = @json($civils);
    const LOADED = Date.now();
    const $ = id => document.getElementById(id);
    const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const peso = n => '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2 });
    const feeText = t => t.is_paid ? peso(t.fee) : 'Free';
    const READY = '<div class="k-status"><span class="k-dot"></span> Ready — waiting for your ID</div>';

    let screen = 'attract', busy = false;
    let st = {};   // { code, citizen, types, purposes, events, type, purpose }
    let mode = 'docs';   // what the resident chose on the menu: 'docs' or 'events'
    function setMode(m) {
        mode = m;
        document.body.classList.toggle('mode-events', m === 'events');
        document.querySelectorAll('.k-stepnav li:nth-child(3) .k-steplbl').forEach(el => el.textContent = m === 'events' ? 'Event' : 'Document');
        // The Event step is the 2nd of 2 (the step guide is drawn for documents, where it is 3rd).
        document.querySelectorAll('.k-stepnav li:nth-child(3) .k-stepnum').forEach(el => { if (!el.querySelector('i')) el.textContent = m === 'events' ? '2' : '3'; });
        $('scan-step').textContent = m === 'events' ? 'Step 1 of 2' : 'Step 1 of 5';
        $('scan-title').innerHTML = m === 'events'
            ? 'Scan your ID to check in<span class="k-fil">I-scan ang ID para mag-check in</span>'
            : 'Scan your Barangay ID<span class="k-fil">I-scan ang iyong Barangay ID</span>';
        $('scan-event').classList.toggle('k-hide', m !== 'events');
        if (m === 'events') loadEvents();
    }

    // Today's events open for check-in: shown on the event scan screen and the menu card,
    // so residents know what they are checking in for.
    let todayEvents = null;
    async function loadEvents() {
        try {
            const res = await fetch(URLS.events, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
            if (res.ok) todayEvents = (await res.json()).events || [];
        } catch (e) { /* keep the last list */ }
        showTodayEvents();
    }
    function showTodayEvents() {
        const list = todayEvents || [];
        const box = $('scan-event');
        if (!list.length) {
            box.innerHTML = `<div class="k-evban none"><span class="k-evban-ico"><i class="mgc_calendar_line"></i></span>
                <div><small>Event check-in</small><b style="font-size:1.25rem">No event is open for check-in right now.</b>
                <span class="meta">Please ask the staff. · Magtanong po sa staff.</span></div></div>`;
        } else if (list.length === 1) {
            const e = list[0];
            box.innerHTML = `<div class="k-evban">
                ${e.cover ? `<img src="${esc(e.cover)}" alt="">` : '<span class="k-evban-ico"><i class="mgc_calendar_line"></i></span>'}
                <div style="min-width:0"><small>Checking in for · Mag-check in para sa</small><b>${esc(e.title)}</b>
                <span class="meta">${[e.time, e.venue].filter(Boolean).map(esc).join(' · ')}${e.raffle ? ' · 🎁 Raffle' : ''}</span></div></div>`;
        } else {
            box.innerHTML = `<div class="k-evban"><span class="k-evban-ico"><i class="mgc_calendar_line"></i></span>
                <div style="min-width:0"><small>Today's events · Mga event ngayon</small>
                <ul>${list.map(e => `<li>${esc(e.title)}${e.time ? ` <span style="font-weight:600;opacity:.85">· ${esc(e.time)}</span>` : ''}</li>`).join('')}</ul>
                <span class="meta">You will choose the event after scanning.</span></div></div>`;
        }
        const chip = $('menu-evt');
        chip.textContent = list.length === 1 ? 'Today: ' + list[0].title : list.length > 1 ? `Today: ${list.length} events` : '';
        chip.classList.toggle('k-hide', !list.length);
    }
    function start(m) { setMode(m); go('scan'); }
    // After an event check-in: straight back to "Scan your ID" for the next person in line.
    function nextResident() {
        clearInterval(doneTimer);
        st = {}; busy = false;
        hideMsg();
        setMode('events');
        go('scan');
    }

    // ── Screens ──
    function go(name) {
        document.querySelectorAll('.k-screen').forEach(s => s.classList.add('k-hide'));
        const el = $('s-' + name);
        el.classList.remove('k-hide', 'k-enter');
        void el.offsetWidth;          // restart the entrance animation
        el.classList.add('k-enter');
        el.querySelector('.k-body')?.scrollTo(0, 0);
        screen = name;
        if (name === 'scan') $('scan-msg').innerHTML = READY;
        if (name === 'menu') { greet(); loadEvents(); }
        carousel(name === 'attract');
        bump();
    }
    function home() {
        st = {}; busy = false;
        setMode('docs');
        clearInterval(doneTimer);
        hideMsg();
        // Pick up new pictures and settings now and then (only from the start screen).
        if (Date.now() - LOADED > 30 * 60 * 1000 && navigator.onLine) { location.reload(); return; }
        go('attract');
    }
    $('s-attract').addEventListener('click', () => go('menu'));
    function greet() {
        const h = new Date().getHours();
        $('greet').textContent = h < 12 ? 'Good morning · Magandang umaga' : h < 18 ? 'Good afternoon · Magandang hapon' : 'Good evening · Magandang gabi';
    }

    // ── Carousel ──
    const slides = [...document.querySelectorAll('.k-slide')];
    const segs = [...document.querySelectorAll('.k-segs span')];
    let si = 0, slideTimer = null;
    function showSlide(n) {
        si = (n + slides.length) % slides.length;
        slides.forEach((s, i) => s.classList.toggle('on', i === si));
        segs.forEach((d, i) => {
            d.classList.remove('on'); d.classList.toggle('past', i < si);
            if (i === si) { void d.offsetWidth; d.classList.add('on'); }
        });
        $('slide-caption').textContent = slides[si].dataset.caption || '';
    }
    function carousel(on) {
        clearInterval(slideTimer);
        if (on && slides.length > 1) { showSlide(si); slideTimer = setInterval(() => showSlide(si + 1), 7000); }
    }

    // ── Clock ──
    function tick() {
        const d = new Date();
        $('clock-time').textContent = d.toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' });
        $('clock-date').textContent = d.toLocaleDateString('en-PH', { weekday: 'long', month: 'long', day: 'numeric' });
    }
    tick(); setInterval(tick, 15000);

    // ── Idle: back to the start screen ──
    let lastTouch = Date.now(), idleShown = false;
    function bump() { lastTouch = Date.now(); if (idleShown) { idleShown = false; $('o-idle').classList.add('k-hide'); } }
    ['pointerdown', 'keydown', 'input', 'scroll'].forEach(ev => document.addEventListener(ev, bump, { capture: true, passive: true }));
    setInterval(() => {
        // Event check-in desk: the scan screen stays ready for the next resident.
        if (screen === 'attract' || busy || (screen === 'scan' && mode === 'events')) return;
        const limit = screen === 'update' ? 180 : ['purpose', 'preview', 'details', 'events'].includes(screen) ? 120 : (['done', 'joined'].includes(screen) ? 9999 : 60);
        const idle = (Date.now() - lastTouch) / 1000;
        if (idle >= limit + 15) { idleShown = false; $('o-idle').classList.add('k-hide'); home(); }
        else if (idle >= limit) {
            idleShown = true; $('o-idle').classList.remove('k-hide');
            $('idle-count').textContent = Math.max(0, Math.ceil(limit + 15 - idle));
        }
    }, 1000);

    // ── Message pop-up ──
    function msg(title, text, { icon = 'mgc_information_line', btn = 'OK', then = null, ok = false } = {}) {
        $('o-msg-icon').className = icon;
        $('o-msg-box').style.background = ok ? 'var(--mint)' : ''; $('o-msg-box').style.color = ok ? 'var(--green)' : '';
        $('o-msg-title').textContent = title; $('o-msg-text').textContent = text;
        $('o-msg-btn').textContent = btn;
        $('o-msg-btn').onclick = () => { hideMsg(); then && then(); };
        $('o-msg').classList.remove('k-hide');
    }
    function hideMsg() { $('o-msg').classList.add('k-hide'); }

    // ── Server ──
    async function post(url, body) {
        let res;
        try {
            res = await fetch(url, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify(body),
            });
        } catch (e) {
            throw new Error('No connection. Please try again or go to the counter.');
        }
        let data = {};
        try { data = await res.json(); } catch (e) {}
        // Logged out (401) or the page is too old (419): reload — it goes to the login page if needed.
        if (res.status === 401 || res.status === 419) { location.reload(); throw new Error('Please wait…'); }
        if (res.status === 403) { setTimeout(() => location.reload(), 4000); }
        if (!res.ok) {
            const first = data.errors ? Object.values(data.errors).flat()[0] : null;
            throw new Error(first || data.message || 'Something went wrong. Please go to the counter.');
        }
        return data;
    }

    // ── USB QR scanner (types the code like a keyboard, then Enter) ──
    let buf = '', lastKey = 0;
    // Screens where a new scan is welcome (the next person in line can just scan).
    const SCAN_OK = ['attract', 'menu', 'scan', 'details', 'docs', 'events', 'done', 'joined'];
    const TEST_KEYS = @json((bool) config('app.debug'));
    document.addEventListener('keydown', e => {
        if (e.target.matches && e.target.matches('input, textarea, select')) return;
        // Testing without a scanner (debug mode only): Ctrl+2 (or Alt+2) scans citizen #2's ID.
        if (e.ctrlKey || e.altKey || e.metaKey) {
            if (TEST_KEYS && /^Digit[1-9]$/.test(e.code) && SCAN_OK.includes(screen) && !busy) {
                e.preventDefault();
                lookup(null, Number(e.code.slice(5)));
            }
            return;
        }
        const now = Date.now();
        if (now - lastKey > 120) buf = '';
        lastKey = now;
        if (e.key === 'Enter') {
            e.preventDefault();
            const code = buf.trim(); buf = '';
            if (code.length >= 6 && SCAN_OK.includes(screen) && !busy) lookup(code);
            return;
        }
        if (e.key.length === 1) buf += e.key;
    });

    async function lookup(code, testId = null) {
        clearInterval(doneTimer);
        hideMsg();
        go('scan');
        busy = true;
        $('scan-msg').innerHTML = '<div class="k-status wait"><span class="k-spin"></span> Checking your ID…</div>';
        try {
            const d = await post(URLS.scan, testId ? { test_id: testId } : { code });
            st = { code: d.code || code, citizen: d.citizen, types: d.types, purposes: d.purposes, events: d.events || [],
                   details: d.details, form: d.form, pending: d.pending_update };
            // Joining an event needs no details check — straight to the events.
            mode === 'events' ? showEvents() : showDetails();
        } catch (e) {
            $('scan-msg').innerHTML = `<div class="k-status err"><i class="mgc_close_circle_line" style="font-size:1.4rem"></i><span>${esc(e.message)}</span></div>`;
        } finally { busy = false; }
    }

    // ── Check your details ──
    function avatar(c) {
        return c.photo ? `<img src="${esc(c.photo)}" class="k-avatar" alt="">` : `<div class="k-avatar">${esc((c.first || '?').charAt(0))}</div>`;
    }
    function showDetails() {
        const c = st.citizen;
        $('det-photo').innerHTML = avatar(c);
        $('det-name').innerHTML = `Hello, ${esc(c.first)}! 👋<span class="k-fil" style="font-size:.5em;color:var(--muted);opacity:1">Kumusta, ${esc(c.first)}!</span>`;
        $('det-pending').innerHTML = st.pending
            ? `<div class="k-banner"><i class="mgc_time_line"></i><span>Your changes sent on ${esc(st.pending)} are waiting for staff to check.<span class="k-fil">Hinihintay pa ang pag-check ng staff.</span></span></div>` : '';
        $('det-grid').innerHTML = st.details.map(r => `
            <div class="k-drow"><i class="${esc(r.icon)}"></i>
                <div style="min-width:0"><small>${esc(r.label)}</small><b>${esc(r.value)}</b></div>
                ${r.locked ? '<span class="k-lock"><i class="mgc_lock_line"></i> Counter</span>' : ''}
            </div>`).join('');
        $('det-ok-label').textContent = st.pending ? 'Continue' : 'Yes, all correct';
        go('details');
    }
    function detailsOk() { mode === 'events' ? showEvents() : showDocs(); }

    // ── Events ──
    function showEvents() {
        const c = st.citizen;
        $('evt-photo').innerHTML = avatar(c);
        $('evt-name').innerHTML = `Hello, ${esc(c.first)}! 👋<span class="k-fil" style="font-size:.5em;color:var(--muted);opacity:1">Kumusta, ${esc(c.first)}!</span>`;
        const today = st.events.filter(e => e.today), soon = st.events.filter(e => !e.today);
        const card = e => {
            const day = (e.date || '').split(' ');   // "Fri, Nov 20" -> month and day for the date tile
            const pic = e.cover ? `<img src="${esc(e.cover)}" class="k-evt-pic" alt="">`
                : `<div class="k-evt-pic date"><span>${esc(day[1] || '')}</span><b>${esc(day[2] || '')}</b></div>`;
            const tags = [e.category ? `<span class="k-tag">${esc(e.category)}</span>` : '', e.raffle ? '<span class="k-tag raffle">🎁 Raffle</span>' : '']
                .concat(e.rules.map(r => `<span class="k-tag">${esc(r)}</span>`)).join('');
            const act = e.can_join
                ? `<button type="button" class="k-btn k-btn-green" data-join="${e.id}"><i class="mgc_check_line"></i> Join · Sumali</button>`
                : `<span class="k-why ${e.joined ? 'in' : ''}"><i class="${e.joined ? 'mgc_check_circle_line' : (e.today ? 'mgc_information_line' : 'mgc_time_line')}"></i>${esc(e.reason)}</span>`;
            return `<div class="k-evt ${e.today ? '' : 'soon'}">
                ${pic}
                <div style="min-width:0">
                    <h3>${esc(e.title)}</h3>
                    <div class="k-evt-meta">
                        <span><i class="mgc_calendar_line"></i>${esc(e.date)}${e.time ? ' · ' + esc(e.time) : ''}</span>
                        ${e.venue ? `<span><i class="mgc_map_pin_line"></i>${esc(e.venue)}</span>` : ''}
                    </div>
                    ${tags ? `<div class="k-tags">${tags}</div>` : ''}
                </div>
                <div class="k-evt-act">${act}</div>
            </div>`;
        };
        $('evt-list').innerHTML = (today.length ? today.map(card).join('')
                : `<div class="k-note" style="grid-column:1/-1"><i class="mgc_information_line"></i><span>No events are open for check-in today.<span class="k-fil">Walang event na bukas ngayon.</span></span></div>`)
            + (soon.length ? '<div class="k-soon-h">Coming soon · Malapit na</div>' + soon.map(card).join('') : '');
        $('evt-list').querySelectorAll('[data-join]').forEach(b => b.addEventListener('click', () => join(Number(b.dataset.join), b)));
        go('events');
    }
    async function join(id, btn) {
        if (busy) return;
        busy = true;
        const label = btn.innerHTML;
        btn.innerHTML = '<span class="k-spin"></span> Joining…'; btn.disabled = true;
        try {
            const d = await post(URLS.join, { code: st.code, event_id: id });
            st.events = d.events;
            $('joined-event').textContent = d.event;
            $('joined-name').textContent = st.citizen.name;
            $('joined-time').textContent = 'In at ' + d.time;
            $('joined-next').innerHTML = [
                d.venue ? `<div><i class="mgc_map_pin_line"></i><p>Please proceed to ${esc(d.venue)}.<span class="k-fil">Pumunta po sa ${esc(d.venue)}.</span></p></div>` : '',
                d.raffle ? `<div><i class="mgc_gift_line"></i><p>You're in the raffle draw. Good luck!<span class="k-fil">Kasali ka na sa raffle. Good luck!</span></p></div>` : '',
                `<div><i class="mgc_contacts_line"></i><p>Keep your Barangay ID with you.<span class="k-fil">Dalhin ang iyong Barangay ID.</span></p></div>`,
            ].join('');
            $('joined-next').style.setProperty('--n', $('joined-next').children.length);
            go('joined');
            let left = 8;
            $('joined-count').textContent = `(${left})`;
            const bar = $('joined-bar');
            bar.style.transition = 'none'; bar.style.transform = 'scaleX(1)';
            requestAnimationFrame(() => requestAnimationFrame(() => { bar.style.transition = `transform ${left}s linear`; bar.style.transform = 'scaleX(0)'; }));
            clearInterval(doneTimer);
            doneTimer = setInterval(() => { left--; $('joined-count').textContent = `(${left})`; if (left <= 0) nextResident(); }, 1000);
        } catch (e) {
            msg("Can't join this event", e.message, { icon: 'mgc_alert_line' });
            btn.innerHTML = label; btn.disabled = false;
        } finally { busy = false; }
    }

    // ── Update my details ──
    const U = id => $('u-' + id);
    let uVoter = 0, uCivil = '';
    function openUpdate() {
        const f = st.form;
        ['occupation', 'no', 'street', 'blk', 'lot', 'phase', 'complete_address', 'pricinct_no', 'ic_fullname', 'ic_relationship', 'ic_address']
            .forEach(k => U(k).value = f[k] ?? '');
        ['contact', 'email', 'ic_contact', 'ic_email'].forEach(k => {
            U(k).value = '';
            $('u-' + k + '-hint').textContent = f['hint_' + k] ? `On record: ${f['hint_' + k]} — leave blank to keep it.` : 'None on record.';
        });
        U('address').value = f.address ?? U('address').value;
        uCivil = String(f.civil_status ?? '');
        $('u-civil').innerHTML = CIVILS.map(c => `<button type="button" class="k-opt ${String(c.id) === uCivil ? 'sel' : ''}" data-v="${c.id}">${esc(c.description)}</button>`).join('');
        $('u-civil').querySelectorAll('.k-opt').forEach(b => b.addEventListener('click', () => {
            uCivil = b.dataset.v; $('u-civil').querySelectorAll('.k-opt').forEach(x => x.classList.toggle('sel', x === b));
        }));
        setVoter(f.voters ? 1 : 0);
        zoneParts();
        $('update-msg').innerHTML = '';
        go('update');
    }
    function setVoter(v) {
        uVoter = v;
        $('u-voters').querySelectorAll('.k-opt').forEach(b => b.classList.toggle('sel', Number(b.dataset.v) === v));
        $('u-precinct-box').classList.toggle('k-hide', v !== 1);
    }
    $('u-voters').querySelectorAll('.k-opt').forEach(b => b.addEventListener('click', () => setVoter(Number(b.dataset.v))));
    function zoneParts() {
        const opt = U('address').selectedOptions[0];
        const subd = Number(opt?.dataset.subd || 0);
        const show = subd === 1 ? ['blk', 'lot', 'phase', 'street'] : subd === 2 ? ['complete_address'] : ['no', 'street'];
        document.querySelectorAll('#s-update [data-part]').forEach(el => el.classList.toggle('k-hide', !show.includes(el.dataset.part)));
        return show;
    }
    U('address').addEventListener('change', zoneParts);
    async function sendUpdate() {
        if (busy) return;
        const show = zoneParts();
        const body = { code: st.code, civil_status: uCivil, occupation: U('occupation').value.trim(), address: U('address').value,
                       voters: uVoter, pricinct_no: uVoter === 1 ? U('pricinct_no').value.trim() : '' };
        ['no', 'street', 'blk', 'lot', 'phase', 'complete_address'].forEach(k => body[k] = show.includes(k) ? U(k).value.trim() : '');
        ['contact', 'email', 'ic_fullname', 'ic_relationship', 'ic_contact', 'ic_email', 'ic_address'].forEach(k => body[k] = U(k).value.trim());
        busy = true;
        const btn = $('update-send'); const label = btn.innerHTML;
        btn.innerHTML = '<span class="k-spin"></span> Sending…'; btn.disabled = true;
        $('update-msg').innerHTML = '';
        try {
            const d = await post(URLS.update, body);
            st.pending = 'today';
            st.details = d.details;
            msg('Changes sent!', `Staff will check your ${d.count === 1 ? 'change' : d.count + ' changes'} before saving. You can still request a document now.`,
                { icon: 'mgc_check_line', ok: true, btn: 'Continue', then: showDetails });
        } catch (e) {
            $('update-msg').innerHTML = `<div class="k-alert"><i class="mgc_alert_line"></i><span>${esc(e.message)}</span></div>`;
            $('update-msg').scrollIntoView({ behavior: 'smooth', block: 'center' });
        } finally { busy = false; btn.innerHTML = label; btn.disabled = false; }
    }

    // ── Documents ──
    function showDocs() {
        const c = st.citizen;
        $('hello-photo').innerHTML = avatar(c);
        $('hello-name').innerHTML = `Hello, ${esc(c.first)}! 👋<span class="k-fil" style="font-size:.5em;color:var(--muted);opacity:1">Kumusta, ${esc(c.first)}!</span>`;
        $('doc-grid').innerHTML = st.types.length ? st.types.map((t, i) => `
            <button type="button" class="k-doc ${t.available ? '' : 'off'}" data-i="${i}">
                <span class="k-docico"><i class="${t.available ? 'mgc_file_certificate_line' : 'mgc_lock_line'}"></i></span>
                <span>
                    <b>${esc(t.name)}</b>
                    ${t.available ? `<span class="k-fee ${t.is_paid ? 'k-fee-paid' : 'k-fee-free'}">${feeText(t)}</span>`
                                  : `<span class="k-why">${esc(t.reason)}</span>`}
                </span>
                ${t.available ? '<span class="k-chev"><i class="mgc_right_line"></i></span>' : ''}
            </button>`).join('')
            : `<div class="k-note" style="grid-column:1/-1"><i class="mgc_information_line"></i><span>No documents can be requested at this kiosk yet. Please go to the counter.</span></div>`;
        $('doc-grid').querySelectorAll('.k-doc').forEach(b => b.addEventListener('click', () => {
            const t = st.types[b.dataset.i];
            if (!t.available) { msg('Please go to the counter', t.reason); return; }
            pickType(t);
        }));
        go('docs');
    }

    // ── Purpose ──
    function pickType(t) {
        st.type = t; st.purpose = '';
        $('purpose-doc').textContent = t.name;
        $('purpose-fee').textContent = t.name + ' · ' + feeText(t);
        $('purpose-msg').innerHTML = '';
        $('purpose-text').value = '';
        $('purpose-other').classList.add('k-hide');
        const list = [...st.purposes, '__other'];
        $('purpose-grid').innerHTML = list.map(p => p === '__other'
            ? `<button type="button" class="k-pbtn other" data-p="__other"><span><i class="mgc_edit_line"></i> Other · Iba pa</span><span class="k-tick"><i class="mgc_check_line"></i></span></button>`
            : `<button type="button" class="k-pbtn" data-p="${esc(p)}"><span>${esc(p)}</span><span class="k-tick"><i class="mgc_check_line"></i></span></button>`).join('');
        $('purpose-grid').querySelectorAll('.k-pbtn').forEach(b => b.addEventListener('click', () => {
            $('purpose-grid').querySelectorAll('.k-pbtn').forEach(x => x.classList.toggle('sel', x === b));
            const other = b.dataset.p === '__other';
            $('purpose-other').classList.toggle('k-hide', !other);
            st.purpose = other ? $('purpose-text').value.trim() : b.dataset.p;
            if (other) setTimeout(() => $('purpose-text').focus(), 50);
            check();
        }));

        // Details the document needs that the record can't fill.
        $('extra-fields').classList.toggle('k-hide', !t.fields.length);
        $('extra-list').innerHTML = t.fields.map(f => {
            const id = 'xf-' + esc(f.key);
            let input;
            if (f.type === 'select') {
                input = `<select id="${id}" data-key="${esc(f.key)}" class="k-input"><option value="">— Choose —</option>${f.options.map(o => `<option>${esc(o)}</option>`).join('')}</select>`;
            } else if (f.type === 'textarea') {
                input = `<textarea id="${id}" data-key="${esc(f.key)}" class="k-input" rows="3"></textarea>`;
            } else {
                input = `<input type="${f.type === 'date' ? 'date' : (f.type === 'number' ? 'number' : 'text')}" id="${id}" data-key="${esc(f.key)}" class="k-input" autocomplete="off">`;
            }
            return `<div><label class="k-label" for="${id}">${esc(f.label)}</label>${input}</div>`;
        }).join('');
        $('extra-list').querySelectorAll('[data-key]').forEach(el => el.addEventListener('input', check));
        $('extra-list').querySelectorAll('select').forEach(el => el.addEventListener('change', check));
        check();
        go('purpose');
    }
    $('purpose-text').addEventListener('input', () => { st.purpose = $('purpose-text').value.trim(); check(); });
    function extras() {
        const out = {};
        $('extra-list').querySelectorAll('[data-key]').forEach(el => out['field_' + el.dataset.key] = el.value.trim());
        return out;
    }
    function check() {
        const ok = st.purpose && Object.values(extras()).every(v => v !== '');
        $('purpose-next').disabled = !ok;
        $('purpose-pick').textContent = st.purpose ? 'For: ' + st.purpose : 'Choose a purpose';
    }
    function payload() {
        return Object.assign({ code: st.code, document_type_id: st.type.id, purpose: st.purpose }, extras());
    }

    // ── Preview ──
    async function preview() {
        if (busy) return;
        busy = true;
        const btn = $('purpose-next'); const label = btn.innerHTML;
        btn.innerHTML = '<span class="k-spin"></span> Preparing…'; btn.disabled = true;
        $('purpose-msg').innerHTML = '';
        try {
            const d = await post(URLS.preview, payload());
            $('sum-name').textContent = st.citizen.name;
            $('sum-doc').textContent = st.type.name;
            $('sum-purpose').textContent = st.purpose;
            $('sum-fee').textContent = st.type.is_paid ? peso(st.type.fee) + ' · pay at the counter' : 'Free';
            $('sum-fee-row').classList.toggle('free', !st.type.is_paid);
            $('preview-msg').innerHTML = '';
            $('btn-submit').disabled = false;
            go('preview');
            renderPaper(d);
        } catch (e) {
            $('purpose-msg').innerHTML = `<div class="k-alert"><i class="mgc_alert_line"></i><span>${esc(e.message)}</span></div>`;
        } finally { busy = false; btn.innerHTML = label; check(); }
    }
    function renderPaper(d) {
        const paper = $('review-paper'), p = d.padding;
        const sizes = { a4: ['8.27in', '11.69in'], letter: ['8.5in', '11in'], half_letter: ['5.5in', '8.5in'], long: ['8.5in', '13in'] };
        let [pw, ph] = sizes[d.paper_size] || sizes.letter;
        if (d.orientation === 'landscape') [pw, ph] = [ph, pw];
        Object.assign(paper.style, { width: pw, minWidth: pw, minHeight: ph, height: 'auto', padding: `${p.top}px ${p.right}px ${p.bottom}px ${p.left}px`,
            backgroundImage: d.bg_url ? `url('${d.bg_url}')` : 'none', backgroundSize: 'cover', backgroundPosition: 'center', backgroundRepeat: 'no-repeat' });
        // A letterhead background already prints its own header.
        paper.innerHTML = (d.bg_url ? '' : (d.header || '')) + (d.body || '');
        fitPaper();
    }
    function fitPaper() {
        const box = $('paper-box'), scaleEl = $('paper-scale'), paper = $('review-paper');
        if (!paper.offsetWidth) return;
        const avail = box.clientWidth - 40;
        const s = Math.min(1, avail / paper.offsetWidth);
        scaleEl.style.transform = `scale(${s})`;
        scaleEl.style.width = paper.offsetWidth + 'px';
        scaleEl.style.height = (paper.offsetHeight * s) + 'px';
    }
    window.addEventListener('resize', fitPaper);
    window.addEventListener('orientationchange', () => setTimeout(fitPaper, 300));
    function wrong() {
        msg('Please go to the counter', 'Staff will correct your details and prepare the document for you. Nothing was sent.', { then: home });
    }

    // ── Submit ──
    let doneTimer = null;
    async function submit() {
        if (busy) return;
        busy = true;
        const btn = $('btn-submit'); btn.disabled = true;
        $('preview-msg').innerHTML = '<div class="k-alert wait" style="margin:0 0 1rem"><span class="k-spin"></span> Sending…</div>';
        try {
            const d = await post(URLS.submit, payload());
            $('done-queue').textContent = d.queue_no;
            $('done-doc').textContent = d.document;
            $('done-chip').className = 'k-fee ' + (d.is_paid ? 'k-fee-paid' : 'k-fee-free');
            $('done-chip').textContent = d.is_paid ? peso(d.fee) : 'Free';
            $('done-fee').innerHTML = d.is_paid
                ? `Pay <b>${peso(d.fee)}</b> at the counter.<span class="k-fil">Magbayad ng ${peso(d.fee)} sa counter.</span>`
                : `This document is <b>free</b>.<span class="k-fil">Libre ang dokumentong ito.</span>`;
            go('done');
            let left = 30;
            $('done-count').textContent = `(${left})`;
            const bar = $('done-bar');
            bar.style.transition = 'none'; bar.style.transform = 'scaleX(1)';
            requestAnimationFrame(() => requestAnimationFrame(() => { bar.style.transition = `transform ${left}s linear`; bar.style.transform = 'scaleX(0)'; }));
            doneTimer = setInterval(() => { left--; $('done-count').textContent = `(${left})`; if (left <= 0) home(); }, 1000);
        } catch (e) {
            $('preview-msg').innerHTML = `<div class="k-alert" style="margin:0 0 1rem"><i class="mgc_alert_line"></i><span>${esc(e.message)}</span></div>`;
            btn.disabled = false;
        } finally { busy = false; }
    }

    function register() { location.href = URLS.register; }

    // Back buttons in the app bar.
    document.querySelectorAll('[data-back]').forEach(b => b.addEventListener('click', () => {
        const to = b.dataset.back;
        if (to === 'details' && mode === 'events') { st = {}; go('scan'); return; }
        if (to === 'scan') { st = {}; }
        go(to);
    }));
    document.querySelectorAll('[data-home]').forEach(b => b.addEventListener('click', home));
    document.addEventListener('contextmenu', e => e.preventDefault());
    carousel(true);

    return { go, home, start, nextResident, preview, submit, wrong, register, stillHere: bump, detailsOk, openUpdate, sendUpdate };
})();
</script>
</body>
</html>
