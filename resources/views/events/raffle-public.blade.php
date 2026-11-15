<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎉 Lucky Draw — {{ $event->title }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --red:    #FF2D55;
            --orange: #FF9500;
            --yellow: #FFD60A;
            --blue:   #0A84FF;
            --white:  #FFFFFF;
            --bg:     #060c2e;
            --card:   #0f1d5e;
            --sidebar-bg: #0a1445;
        }

        html, body {
            height: 100%; overflow: hidden;
            font-family: 'Nunito', sans-serif;
            color: var(--white);
            background: var(--bg);
        }

        /* ── Animated background dots ── */
        #bg {
            position: fixed; inset: 0; z-index: 0;
            background: radial-gradient(ellipse at 50% 45%, #1a2f8a 0%, #0d1a55 38%, #060c2e 75%, #040822 100%);
            overflow: hidden;
        }

        .bubble {
            position: absolute; border-radius: 50%;
            animation: float-up linear infinite;
        }
        @keyframes float-up {
            0%   { transform: translateY(110vh); opacity: 0.22; }
            100% { transform: translateY(-15vh); opacity: 0; }
        }

        .star-bg {
            position: absolute;
            animation: twinkle ease-in-out infinite alternate;
        }
        @keyframes twinkle {
            from { opacity: 0.12; transform: scale(0.85); }
            to   { opacity: 0.35; transform: scale(1.1); }
        }

        /* ── App shell ── */
        #app {
            position: relative; z-index: 1;
            display: flex; flex-direction: column;
            height: 100vh;
        }

        /* ── Topbar ── */
        #topbar {
            flex-shrink: 0; height: 60px;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 20px;
            background: rgba(8, 16, 60, 0.72);
            border-bottom: 1px solid rgba(150, 170, 255, 0.22);
            backdrop-filter: blur(6px);
            z-index: 10; font-family: 'Poppins', system-ui, sans-serif;
        }

        #topbar-title {
            font-size: 1.02rem; font-weight: 700;
            color: #fff;
            letter-spacing: 0.01em;
        }
        #topbar-venue { font-size: 0.68rem; color: rgba(255,255,255,0.45); margin-top: 2px; }

        #live-pill {
            display: flex; align-items: center; gap: 6px;
            background: var(--red);
            border-radius: 99px;
            padding: 5px 14px;
            font-size: 0.7rem; font-weight: 900;
            letter-spacing: 0.1em; text-transform: uppercase;
            color: #fff;
        }
        #live-dot {
            width: 7px; height: 7px; border-radius: 50%;
            background: #fff;
            animation: blink 1s ease-in-out infinite alternate;
        }
        @keyframes blink { from { opacity: 1; } to { opacity: 0.25; } }

        #toggle-sidebar {
            display: flex; align-items: center; gap: 6px;
            background: var(--blue);
            border: none; border-radius: 99px;
            padding: 6px 16px;
            color: #fff; font-size: 0.75rem; font-weight: 800;
            cursor: pointer; letter-spacing: 0.03em;
            font-family: 'Nunito', sans-serif;
            transition: opacity 0.15s;
        }
        #toggle-sidebar:hover { opacity: 0.85; }

        #sidebar-badge {
            display: none;
            background: var(--yellow); color: #1C0050;
            border-radius: 99px; padding: 1px 7px;
            font-size: 0.65rem; font-weight: 900;
        }

        /* ── Stage ── */
        #stage { flex: 1; display: flex; overflow: hidden; }

        /* ── Sidebar ── */
        #sidebar {
            width: 0; overflow: hidden; flex-shrink: 0;
            transition: width 0.3s cubic-bezier(.4,0,.2,1);
            background: #fff;
            border-right: 1px solid #e5e8f2;
            display: flex; flex-direction: column;
            font-family: 'Poppins', system-ui, sans-serif;
        }
        #sidebar.open { width: 288px; }
        #sidebar-inner { width: 288px; display: flex; flex-direction: column; height: 100%; }

        #sidebar-header {
            display: flex; align-items: baseline; justify-content: space-between;
            padding: 18px 18px 12px;
            font-size: 0.95rem; font-weight: 600; color: #111827;
        }
        #sidebar-count { font-size: 0.75rem; font-weight: 500; color: #9ca3af; }

        #winner-list { flex: 1; overflow-y: auto; }

        /* ── Draw area ── */
        #draw-area {
            flex: 1; display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            gap: 16px; padding: 24px 20px; min-width: 0;
        }

        #draw-title {
            font-size: 1.8rem; font-weight: 900;
            color: var(--yellow);
            text-align: center; letter-spacing: 0.04em;
            text-transform: uppercase;
            animation: bob 2s ease-in-out infinite alternate;
        }
        @keyframes bob {
            from { transform: translateY(0); }
            to   { transform: translateY(-5px); }
        }

        #pool-label {
            font-size: 0.78rem; font-weight: 800;
            color: rgba(255,255,255,0.35);
            letter-spacing: 0.05em; margin-top: -8px;
        }

        /* ── Drum ── */
        #drum-wrap { position: relative; width: 80%; max-width: 900px; }

        #drum-viewport {
            overflow: hidden;
            border-radius: 24px;
            height: 300px; /* exactly 3 × 100px */
            background: var(--card);
            border: 4px solid var(--orange);
            box-shadow: 0 8px 0 #b86a00, 0 20px 40px rgba(0,0,0,0.5);
        }

        #drum-track {
            display: flex; flex-direction: column;
            will-change: transform;
        }

        .drum-item {
            height: 100px;
            display: flex; align-items: center; justify-content: center;
            font-size: clamp(1.6rem, 4vw, 3rem); font-weight: 900;
            color: rgba(255,255,255,0.8);
            padding: 0 4rem;
            text-align: center; flex-shrink: 0;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            max-width: 100%;
            letter-spacing: 0.01em;
        }
        .drum-item.is-winner { color: var(--yellow); }
        /* Idle "ready" state — the centre band shows a prompt, never a name,
           so nobody looks pre-selected between draws. */
        .drum-item.drum-ready { color: var(--yellow); animation: ready-pulse 1.8s ease-in-out infinite; }
        .drum-item.drum-ready-dim { color: rgba(255,255,255,0.28); font-size: clamp(1rem, 2.2vw, 1.5rem); font-weight: 800; }
        @keyframes ready-pulse { 0%,100% { opacity: 1; } 50% { opacity: .55; } }

        /* fades — same colour as drum background */
        #drum-fade-top, #drum-fade-bot {
            position: absolute; left: 0; right: 0; height: 100px;
            pointer-events: none; z-index: 5;
        }
        #drum-fade-top { top: 0;    background: linear-gradient(to bottom, #2A0072, transparent); }
        #drum-fade-bot { bottom: 0; background: linear-gradient(to top,   #2A0072, transparent); }

        /* centre band */
        #drum-band {
            position: absolute; left: 0; right: 0;
            top: 100px; height: 100px;
            background: rgba(255,214,10,0.07);
            border-top:    3px solid var(--yellow);
            border-bottom: 3px solid var(--yellow);
            pointer-events: none; z-index: 6;
        }

        .drum-corner {
            position: absolute; top: 100px; height: 100px;
            display: flex; align-items: center;
            font-size: 2rem;
            pointer-events: none; z-index: 7;
            animation: spin 3s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }
        #drum-star-l { left: 16px;  animation-direction: normal; }
        #drum-star-r { right: 16px; animation-direction: reverse; }

        #draw-status {
            font-size: 0.85rem; font-weight: 800;
            color: rgba(255,255,255,0.3);
            min-height: 1.4rem; text-align: center;
        }

        /* ══ Winner picker — horizontal 3D card carousel shown during a draw ══ */
        /* The carousel IS the main stage (idle → drawing → landed, via data-mode). */
        #draw-area { font-family: 'Poppins', system-ui, sans-serif; position: relative; overflow: hidden; }
        #picker-confetti { position: fixed; inset: 0; pointer-events: none; z-index: 0; }

        #draw-area[data-mode="idle"] #picker-progress,
        #draw-area[data-mode="idle"] #picker-status { display: none; }
        #draw-area[data-mode="drawing"] #draw-controls,
        #draw-area[data-mode="landed"]  #draw-controls,
        #draw-area[data-mode="drawing"] #pool-label,
        #draw-area[data-mode="landed"]  #pool-label { display: none; }

        /* Idle centre card — a "ready" prompt, never a person */
        .pk-card.pk-ready { animation: ready-breathe 2.2s ease-in-out infinite; }
        .pk-card.pk-ready .pk-name { font-size: clamp(1.1rem, 2vw, 1.75rem); white-space: normal; line-height: 1.2; }
        /* Old purple-theme decorations — the confetti layer replaces them */
        #bg .bubble, #bg .star-bg { display: none; }
        /* The player count is shown on the centre card; no duplicate line */
        #pool-label { display: none !important; }
        @keyframes ready-breathe {
            0%,100% { box-shadow: 0 0 0 2px rgba(255,255,255,.9), 0 0 30px 6px rgba(110,140,255,.55), 0 18px 40px rgba(0,0,40,.45); }
            50%     { box-shadow: 0 0 0 2px rgba(255,255,255,.9), 0 0 48px 12px rgba(110,140,255,.85), 0 18px 40px rgba(0,0,40,.45); }
        }

        /* Controls under the carousel */
        #draw-controls { width: min(560px, 86%); display: flex; flex-direction: column; gap: 12px; position: relative; }
        #prize-label {
            width: 100%; padding: 13px 18px; border-radius: 14px; outline: none; text-align: center;
            background: rgba(150,170,255,.12); border: 1.5px solid rgba(190,205,255,.3); color: #fff;
            font: 500 .95rem 'Poppins', system-ui, sans-serif;
        }
        #prize-label::placeholder { color: rgba(220,228,255,.6); }
        /* Winners-per-draw stepper (next to the prize label) */
        #draw-options { display: flex; gap: 10px; }
        #draw-options #prize-label { flex: 1; min-width: 0; }
        #win-stepper {
            display: flex; align-items: center; gap: 2px; padding: 4px; border-radius: 14px; flex-shrink: 0;
            background: rgba(150,170,255,.12); border: 1.5px solid rgba(190,205,255,.3);
            font: 500 .9rem 'Poppins', system-ui, sans-serif; color: rgba(225,232,255,.85);
        }
        #win-stepper.is-multi { border-color: #ffd60a; background: rgba(255,214,10,.10); }
        #win-stepper button {
            width: 36px; height: 36px; border: 0; border-radius: 10px; cursor: pointer; color: #fff;
            background: rgba(160,180,255,.16) !important; font: 600 1.2rem/1 'Poppins', system-ui, sans-serif;
        }
        #win-stepper button:hover { background: rgba(160,180,255,.3) !important; }
        #win-count {
            width: 44px; border: 0; outline: none; background: transparent; color: #fff; text-align: center;
            font: 700 1.1rem 'Poppins', system-ui, sans-serif; -moz-appearance: textfield;
        }
        #win-count::-webkit-inner-spin-button, #win-count::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        #win-unit { padding: 0 8px 0 2px; }
        @media (max-width: 640px) { #draw-options { flex-direction: column; } #win-stepper { justify-content: center; } }

        /* Multi-winner results pop-up */
        #batch-modal {
            position: fixed; inset: 0; z-index: 215; display: none; align-items: center; justify-content: center; padding: 16px;
            background: rgba(4, 8, 34, .72); backdrop-filter: blur(6px);
        }
        #batch-modal.show { display: flex; }
        #batch-box {
            width: 100%; max-width: 940px; max-height: calc(100vh - 32px); display: flex; flex-direction: column; text-align: center;
            font-family: 'Poppins', system-ui, sans-serif; color: #fff; border-radius: 22px; padding: 26px 26px 22px;
            background: linear-gradient(180deg, rgba(30, 50, 140, .97) 0%, rgba(13, 24, 84, .98) 100%);
            border: 1px solid rgba(175, 195, 255, .35);
            box-shadow: 0 0 60px rgba(90, 120, 255, .35), 0 30px 60px rgba(0, 0, 30, .6);
            animation: modal-in .35s cubic-bezier(.2,.9,.3,1.15) forwards;
        }
        #batch-label {
            align-self: center; display: inline-flex; padding: 5px 14px; border-radius: 99px;
            background: rgba(160,180,255,.14); border: 1px solid rgba(190,205,255,.28); color: #dfe6ff;
            font-size: .72rem; font-weight: 600; letter-spacing: .12em; text-transform: uppercase;
        }
        #batch-title { margin-top: 12px; font-size: 1.6rem; font-weight: 700; }
        #batch-prize {
            align-self: center; margin-top: 8px; padding: 5px 14px; border-radius: 99px;
            background: rgba(255,214,10,.14); border: 1px solid rgba(255,214,10,.4); color: #ffe066; font-size: .88rem; font-weight: 600;
        }
        #batch-prize:empty { display: none; }
        #batch-hint { margin-top: 8px; font-size: .82rem; color: rgba(215,225,255,.65); }
        #batch-grid {
            margin-top: 18px; padding: 4px; overflow-y: auto; flex: 1; min-height: 0;
            display: grid; gap: 12px; grid-template-columns: repeat(auto-fill, minmax(158px, 1fr));
        }
        .bt-card {
            position: relative; display: flex; flex-direction: column; align-items: center; padding: 16px 10px 12px; border-radius: 16px;
            background: rgba(150,170,255,.12); border: 1px solid rgba(190,205,255,.25);
            animation: bt-in .4s cubic-bezier(.2,.9,.3,1.2) backwards;
        }
        @keyframes bt-in { from { opacity: 0; transform: translateY(10px) scale(.92); } }
        #batch-grid.no-anim .bt-card { animation: none; }
        .bt-num { position: absolute; top: 8px; left: 10px; font-size: .7rem; font-weight: 600; color: rgba(215,225,255,.5); }
        .bt-av {
            width: 54px; height: 54px; border-radius: 50%; overflow: hidden; display: flex; align-items: center; justify-content: center;
            background: linear-gradient(180deg, #fff, #e8edff); color: #2b3fd6; font-weight: 700; font-size: 1.05rem;
            box-shadow: 0 0 0 3px rgba(120,150,255,.35), 0 0 18px rgba(110,140,255,.55);
        }
        .bt-name { margin-top: 10px; max-width: 100%; font-size: .88rem; font-weight: 600; line-height: 1.25;
                   display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .bt-code { margin-top: 2px; font-size: .74rem; color: rgba(215,225,255,.6); }
        .bt-x {
            margin-top: 10px; padding: 6px 12px; border: 0; border-radius: 99px; cursor: pointer;
            background: rgba(255,90,90,.12) !important; color: #ffb3b3; box-shadow: inset 0 0 0 1px rgba(255,120,120,.4);
            font: 600 .74rem 'Poppins', system-ui, sans-serif;
        }
        .bt-x:hover { background: rgba(255,90,90,.25) !important; color: #fff; }
        .bt-round { margin-top: 10px; font-size: .74rem; color: rgba(215,225,255,.55); }
        #batch-foot { margin-top: 18px; display: flex; justify-content: center; }
        #batch-confirm {
            min-width: 260px; padding: 14px 24px; border: 0; border-radius: 14px; cursor: pointer; color: #fff;
            font: 600 1rem 'Poppins', system-ui, sans-serif;
            background: linear-gradient(90deg, #3b5bff, #6d83ff) !important;
            box-shadow: 0 0 24px rgba(110,140,255,.55), 0 8px 20px rgba(0,0,40,.4);
        }
        #batch-confirm:disabled { opacity: .6; cursor: wait; }

        /* ── Screen modes: display (projector, no controls) / control (staff device) ── */
        body.mode-display #draw-controls, body.mode-display #filter-fab, body.mode-display #confirm-actions,
        body.mode-display #modal-close, body.mode-display #batch-foot, body.mode-display .w-undo { display: none !important; }
        #confirm-wait { display: none; margin-top: 18px; font-size: .92rem; color: rgba(215,225,255,.75); }
        #confirm-modal.readonly #confirm-actions { display: none; }
        #confirm-modal.readonly #confirm-wait { display: block; }
        #mode-banner {
            flex-shrink: 0; display: flex; align-items: center; justify-content: center; gap: 10px; padding: 7px 16px;
            background: rgba(255,214,10,.12); border-bottom: 1px solid rgba(255,214,10,.3); color: #ffe066;
            font: 500 .82rem 'Poppins', system-ui, sans-serif;
        }
        #mode-banner a { color: #fff; font-weight: 600; text-decoration: underline; }
        #link-status { display: inline-flex; align-items: center; gap: 6px; padding: 2px 10px; border-radius: 99px; background: rgba(48,209,88,.15); color: #7ee2a0; font-weight: 600; }
        #link-status::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
        #link-status.is-bad { background: rgba(255,90,90,.18); color: #ff9b9b; animation: snd-pulse 1.2s ease-in-out infinite; }
        #live-pill.is-off { background: #6b7280; }
        #live-pill.is-off #live-dot { animation: none; opacity: .5; }

        /* Sound toggle (top bar) */
        #sound-btn {
            display: inline-flex; align-items: center; gap: 6px; height: 34px; padding: 0 12px; border-radius: 99px; cursor: pointer;
            background: rgba(160,180,255,.14) !important; border: 1px solid rgba(190,205,255,.3); color: #fff;
            font: 600 .8rem 'Poppins', system-ui, sans-serif;
        }
        #sound-btn:hover { background: rgba(160,180,255,.26) !important; }
        #sound-btn.is-locked { border-color: #ffd60a; color: #ffe066; animation: snd-pulse 1.6s ease-in-out infinite; }
        @keyframes snd-pulse { 50% { box-shadow: 0 0 0 6px rgba(255,214,10,.15); } }

        /* Grand-prize mode */
        #grand-btn {
            width: 48px; flex-shrink: 0; border-radius: 14px; cursor: pointer; font-size: 1.25rem;
            background: rgba(150,170,255,.12) !important; border: 1.5px solid rgba(190,205,255,.3);
            filter: grayscale(.7); transition: filter .2s, box-shadow .2s;
        }
        #grand-btn:hover { filter: none; }
        body.grand #grand-btn { filter: none; border-color: #ffd60a; background: rgba(255,214,10,.18) !important; box-shadow: 0 0 18px rgba(255,214,10,.45); }
        #win-stepper.is-locked { opacity: .45; pointer-events: none; }
        body.grand #picker-title { color: #ffe066; text-shadow: 0 2px 22px rgba(255,190,40,.6); }
        body.grand #picker-glow {
            background: radial-gradient(circle, rgba(255,200,60,.42) 0%, rgba(255,170,40,.14) 40%, transparent 64%),
                        repeating-radial-gradient(circle, transparent 0 34px, rgba(255,210,100,.12) 35px 36px);
        }
        body.grand .pk-card.is-center { border-color: #ffd60a; box-shadow: 0 0 0 3px #ffd60a, 0 0 46px 12px rgba(255,190,40,.7), 0 18px 40px rgba(0,0,40,.45); }
        body.grand .pk-card.is-center .pk-name { color: #a15c00; }
        body.grand .pk-card.is-center .pk-code { color: #b8860b; }
        body.grand #picker-bar { background: linear-gradient(90deg, #f59e0b, #ffd60a); box-shadow: 0 0 14px rgba(255,200,40,.9); }
        body.grand #draw-btn { background: linear-gradient(90deg, #f59e0b, #ffd60a) !important; color: #3b2400; box-shadow: 0 0 30px rgba(255,200,40,.6), 0 10px 26px rgba(0,0,40,.45); }
        body.grand #winner-modal-box { border-color: rgba(255,214,10,.7); box-shadow: 0 0 80px rgba(255,190,40,.5), 0 30px 60px rgba(0,0,30,.6); }
        body.grand #winner-label { background: rgba(255,214,10,.2); border-color: rgba(255,214,10,.55); color: #ffe066; }
        body.grand #winner-avatar { box-shadow: 0 0 0 4px #ffd60a, 0 0 40px rgba(255,200,40,.85); }
        body.grand #modal-close { background: linear-gradient(90deg, #f59e0b, #ffd60a) !important; color: #3b2400; }

        /* Undo a winner (sidebar link + pop-up) */
        .w-undo { flex-shrink: 0; border: 0; background: none !important; cursor: pointer; padding: 2px 4px;
                  font: 500 .72rem 'Poppins', system-ui, sans-serif; color: #9ca3af; }
        .w-undo:hover { color: #dc2626; text-decoration: underline; }
        #undo-modal {
            position: fixed; inset: 0; z-index: 230; display: none; align-items: center; justify-content: center; padding: 16px;
            background: rgba(4, 8, 34, .72); backdrop-filter: blur(6px);
        }
        #undo-modal.show { display: flex; }
        #undo-box {
            width: 100%; max-width: 420px; padding: 24px; border-radius: 20px; color: #fff; font-family: 'Poppins', system-ui, sans-serif;
            background: linear-gradient(180deg, rgba(30, 50, 140, .97) 0%, rgba(13, 24, 84, .98) 100%);
            border: 1px solid rgba(175, 195, 255, .35); box-shadow: 0 30px 60px rgba(0, 0, 30, .6);
            animation: modal-in .3s cubic-bezier(.2,.9,.3,1.15) forwards;
        }
        #undo-who { margin-top: 6px; font-size: .88rem; color: rgba(215,225,255,.8); }
        .undo-field { display: block; margin-top: 16px; font-size: .8rem; color: rgba(215,225,255,.75); }
        #undo-reason {
            display: block; width: 100%; margin-top: 6px; padding: 11px 14px; border-radius: 12px; outline: none; box-sizing: border-box;
            background: rgba(150,170,255,.12); border: 1.5px solid rgba(190,205,255,.3); color: #fff; font: 500 .9rem 'Poppins', system-ui, sans-serif;
        }
        #undo-reason.is-invalid { border-color: #ff6b6b; }
        .undo-check { display: flex; align-items: center; gap: 10px; margin-top: 14px; font-size: .84rem; color: rgba(225,232,255,.85); cursor: pointer; }
        .undo-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
        .undo-actions button { border: 0; border-radius: 12px; padding: 11px 18px; cursor: pointer; font: 600 .9rem 'Poppins', system-ui, sans-serif; color: #fff; }
        .undo-cancel { background: rgba(160,180,255,.16) !important; }
        #undo-go { background: #e5484d !important; box-shadow: 0 0 18px rgba(229,72,77,.45); }
        #undo-go:disabled { opacity: .6; cursor: wait; }

        /* Area filter: floating button (bottom right) + multi-select pop-up */
        #filter-fab {
            position: fixed; right: 24px; bottom: 24px; z-index: 150;
            display: inline-flex; align-items: center; gap: 8px; padding: 12px 18px; border-radius: 99px; cursor: pointer;
            font: 600 .9rem 'Poppins', system-ui, sans-serif; color: #fff;
            background: rgba(30, 50, 140, .85) !important; border: 1px solid rgba(175, 195, 255, .4);
            box-shadow: 0 0 24px rgba(90, 120, 255, .35), 0 10px 24px rgba(0, 0, 30, .5); backdrop-filter: blur(6px);
            transition: transform .15s, box-shadow .2s;
        }
        #filter-fab:hover { transform: translateY(-2px); box-shadow: 0 0 32px rgba(120, 150, 255, .6), 0 10px 24px rgba(0, 0, 30, .5); }
        #filter-fab.is-active { background: linear-gradient(90deg, #3b5bff, #6d83ff) !important; border-color: rgba(255,255,255,.5); }
        #filter-fab-count {
            display: none; min-width: 22px; height: 22px; padding: 0 6px; border-radius: 99px;
            background: #ffd60a; color: #1a1a2e; font-size: .75rem; font-weight: 700; line-height: 22px; text-align: center;
        }
        #filter-fab.is-active #filter-fab-count { display: inline-block; }
        body:has(#draw-area[data-mode="drawing"]) #filter-fab,
        body:has(#draw-area[data-mode="landed"])  #filter-fab { display: none; }

        #filter-modal {
            position: fixed; inset: 0; z-index: 220; display: none; align-items: center; justify-content: center; padding: 16px;
            background: rgba(4, 8, 34, .72); backdrop-filter: blur(6px);
        }
        #filter-modal.show { display: flex; }
        #filter-box {
            width: 100%; max-width: 440px; max-height: min(640px, calc(100vh - 32px)); display: flex; flex-direction: column;
            font-family: 'Poppins', system-ui, sans-serif; color: #fff; border-radius: 22px; overflow: hidden;
            background: linear-gradient(180deg, rgba(30, 50, 140, .97) 0%, rgba(13, 24, 84, .98) 100%);
            border: 1px solid rgba(175, 195, 255, .35);
            box-shadow: 0 0 60px rgba(90, 120, 255, .35), 0 30px 60px rgba(0, 0, 30, .6);
            animation: modal-in .3s cubic-bezier(.2,.9,.3,1.15) forwards;
        }
        .fm-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; padding: 22px 22px 14px; }
        .fm-title { font-size: 1.15rem; font-weight: 700; }
        .fm-sub { margin-top: 2px; font-size: .8rem; color: rgba(215, 225, 255, .65); }
        .fm-x { background: rgba(160,180,255,.12) !important; border: 0; color: #dfe6ff; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: .9rem; flex-shrink: 0; }
        .fm-x:hover { background: rgba(160,180,255,.25) !important; }
        #fm-search {
            margin: 0 22px; padding: 11px 14px; border-radius: 12px; outline: none;
            background: rgba(150,170,255,.12); border: 1.5px solid rgba(190,205,255,.3); color: #fff;
            font: 500 .9rem 'Poppins', system-ui, sans-serif;
        }
        #fm-search::placeholder { color: rgba(220,228,255,.55); }
        #fm-search:focus { border-color: rgba(190,205,255,.75); }
        .fm-tools { display: flex; justify-content: space-between; align-items: center; padding: 12px 22px 6px; font-size: .78rem; color: rgba(215,225,255,.6); }
        .fm-tools button { background: none !important; border: 0; color: #9fb2ff; font: 600 .8rem 'Poppins', system-ui, sans-serif; cursor: pointer; padding: 2px 4px; }
        .fm-tools button:hover { color: #fff; }
        #fm-list { flex: 1; overflow-y: auto; padding: 4px 12px 8px; }
        .fm-row {
            display: flex; align-items: center; gap: 12px; padding: 10px 10px; border-radius: 12px; cursor: pointer; user-select: none;
        }
        .fm-row:hover { background: rgba(160,180,255,.10); }
        .fm-row input { display: none; }
        .fm-check {
            width: 20px; height: 20px; border-radius: 6px; flex-shrink: 0; display: flex; align-items: center; justify-content: center;
            border: 1.5px solid rgba(190,205,255,.5); color: transparent; font-size: .75rem; font-weight: 700;
        }
        .fm-row input:checked + .fm-check { background: #4f6bff; border-color: #6d83ff; color: #fff; }
        .fm-name { flex: 1; min-width: 0; font-size: .9rem; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .fm-n { font-size: .78rem; color: rgba(215,225,255,.6); }
        .fm-empty { padding: 24px 10px; text-align: center; font-size: .85rem; color: rgba(215,225,255,.55); }
        .fm-foot { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 14px 22px 20px; border-top: 1px solid rgba(175,195,255,.2); }
        #fm-summary { font-size: .82rem; color: rgba(215,225,255,.75); }
        #fm-apply {
            border: 0; border-radius: 12px; padding: 11px 22px; cursor: pointer; color: #fff; font: 600 .95rem 'Poppins', system-ui, sans-serif;
            background: linear-gradient(90deg, #3b5bff, #6d83ff) !important; box-shadow: 0 0 20px rgba(110,140,255,.5);
        }
        #prize-label:focus { border-color: rgba(190,205,255,.75); box-shadow: 0 0 0 3px rgba(110,140,255,.25); }
        #draw-btn {
            width: 100%; padding: 16px; border: 0; border-radius: 14px; cursor: pointer; color: #fff;
            font: 700 1.25rem 'Poppins', system-ui, sans-serif; letter-spacing: .02em;
            background: linear-gradient(90deg, #3b5bff, #6d83ff) !important;
            box-shadow: 0 0 24px rgba(110,140,255,.55), 0 10px 26px rgba(0,0,40,.45);
            transition: transform .1s, box-shadow .2s, opacity .2s;
        }
        #draw-btn:hover  { box-shadow: 0 0 34px rgba(130,160,255,.8), 0 10px 26px rgba(0,0,40,.45); }
        #draw-btn:active { transform: translateY(2px); }
        #draw-btn:disabled { opacity: .55; cursor: wait; }
        #pool-label { position: relative; margin-top: -6px; font-size: .9rem; font-weight: 500; color: rgba(225,232,255,.75); }
        .pk-bit { position: absolute; border-radius: 2px; opacity: .9; animation: pk-float linear infinite; }
        .pk-dot { position: absolute; border-radius: 50%; filter: blur(1px); opacity: .55; animation: pk-float linear infinite; }
        @keyframes pk-float {
            0%   { transform: translateY(0) rotate(var(--r, 0deg)); }
            50%  { transform: translateY(-14px) rotate(calc(var(--r, 0deg) + 40deg)); }
            100% { transform: translateY(0) rotate(calc(var(--r, 0deg) + 80deg)); }
        }

        #picker-title    { position: relative; font-size: clamp(1.6rem, 3.4vw, 2.6rem); font-weight: 700; letter-spacing: .01em; text-shadow: 0 2px 18px rgba(80,120,255,.5); }
        #picker-subtitle { position: relative; margin-top: 6px; font-size: clamp(.85rem, 1.4vw, 1.05rem); font-weight: 500; color: rgba(225,232,255,.85); }

        /* Carousel card size — one place to tune it */
        :root { --card-w: clamp(170px, 22vw, 320px); --card-h: clamp(112px, 13.5vw, 190px); }
        #picker-stage {
            position: relative; width: 100%; height: clamp(210px, 32vw, 330px);
            margin: clamp(26px, 5vh, 48px) 0 clamp(24px, 4vh, 40px);
            perspective: 1100px; perspective-origin: 50% 50%;
        }
        /* concentric glow rings behind the centre card */
        #picker-glow {
            position: absolute; left: 50%; top: 50%; width: clamp(300px, 40vw, 540px); aspect-ratio: 1;
            transform: translate(-50%, -50%); border-radius: 50%; pointer-events: none;
            background:
                radial-gradient(circle, rgba(120,150,255,.35) 0%, rgba(90,110,255,.12) 38%, transparent 62%),
                repeating-radial-gradient(circle, transparent 0 34px, rgba(140,160,255,.10) 35px 36px);
            animation: glow-pulse 1.6s ease-in-out infinite;
        }
        @keyframes glow-pulse { 0%,100% { opacity: .75; transform: translate(-50%,-50%) scale(1); } 50% { opacity: 1; transform: translate(-50%,-50%) scale(1.05); } }

        .pk-card {
            position: absolute; left: 50%; top: 50%;
            width: var(--card-w); height: var(--card-h);
            margin-left: calc(var(--card-w) / -2); margin-top: calc(var(--card-h) / -2);
            border-radius: 20px; display: flex; flex-direction: column; align-items: center; justify-content: center;
            padding: 0 18px; text-align: center; will-change: transform, opacity; backface-visibility: hidden;
            background: rgba(150,170,255,.14); border: 1.5px solid rgba(190,205,255,.28);
            box-shadow: 0 10px 30px rgba(0,0,30,.35); backdrop-filter: blur(4px);
        }
        .pk-card .pk-name { font-size: clamp(1.05rem, 2.1vw, 1.9rem); font-weight: 600; color: #eef2ff; max-width: 100%;
                            white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .pk-card .pk-code { margin-top: 6px; font-size: clamp(.8rem, 1.35vw, 1.2rem); font-weight: 500; color: rgba(220,228,255,.7); }
        .pk-card.is-center {
            background: linear-gradient(180deg, #ffffff 0%, #eef2ff 100%); border-color: #fff;
            box-shadow: 0 0 0 2px rgba(255,255,255,.9), 0 0 38px 8px rgba(110,140,255,.75), 0 18px 40px rgba(0,0,40,.45);
        }
        .pk-card.is-center .pk-name { color: #2b3fd6; font-weight: 700; }
        .pk-card.is-center .pk-code { color: #4655d8; font-weight: 600; }

        #picker-progress { position: relative; width: min(560px, 70vw); height: 6px; border-radius: 99px; background: rgba(160,175,255,.22); }
        #picker-bar { position: absolute; left: 0; top: 0; bottom: 0; width: 0; border-radius: 99px;
                      background: linear-gradient(90deg, #3b5bff, #7c93ff); box-shadow: 0 0 12px rgba(110,140,255,.8); }
        #picker-bar::after { content: ''; position: absolute; right: -7px; top: 50%; width: 14px; height: 14px; margin-top: -7px;
                             border-radius: 50%; background: #fff; box-shadow: 0 0 14px 4px rgba(150,175,255,.95); }
        #picker-status { position: relative; margin-top: 14px; font-size: .9rem; font-weight: 500; color: rgba(225,232,255,.8); }

        /* ── Presence confirmation modal (before recording a winner) ── */
        #confirm-modal {
            display: none; position: fixed; inset: 0; z-index: 210;
            align-items: center; justify-content: center;
            background: rgba(0,0,0,0.78);
        }
        #confirm-modal.show { display: flex; }
        #confirm-modal-box {
            position: relative; background: var(--card);
            border: 5px solid var(--blue); border-radius: 28px;
            padding: 0 0 28px; text-align: center; max-width: 460px; width: 92%;
            box-shadow: 0 10px 0 #0560c2, 0 30px 60px rgba(0,0,0,0.7);
            animation: pop-in 0.4s cubic-bezier(.175,.885,.32,1.4) forwards; overflow: hidden;
        }
        #confirm-body { padding: 22px 28px 0; }
        #confirm-label {
            font-size: 0.75rem; font-weight: 900; letter-spacing: 0.2em; text-transform: uppercase;
            color: var(--blue); margin-bottom: 14px;
        }
        #confirm-avatar {
            width: 108px; height: 108px; border-radius: 50%; overflow: hidden; margin: 0 auto 14px;
            background: var(--bg); border: 5px solid var(--blue);
            display: flex; align-items: center; justify-content: center;
            font-size: 2.4rem; font-weight: 900; color: var(--blue);
        }
        #confirm-name { font-size: 1.55rem; font-weight: 900; color: #fff; line-height: 1.15; }
        #confirm-prize { font-size: 0.95rem; font-weight: 800; color: var(--yellow); margin-top: 6px; min-height: 1.2em; }
        #confirm-question { margin-top: 16px; font-size: 1.15rem; font-weight: 900; color: #fff; }
        #confirm-hint { font-size: 0.8rem; font-weight: 700; color: rgba(255,255,255,0.45); margin-top: 2px; }
        #confirm-actions { display: grid; gap: 10px; margin-top: 18px; }
        #confirm-actions button {
            border: 0; border-radius: 16px; padding: 14px 16px; cursor: pointer;
            font: 900 1rem 'Nunito', system-ui, sans-serif; letter-spacing: 0.02em;
            transition: transform .1s, opacity .2s;
        }
        #confirm-actions button:active { transform: translateY(2px); }
        #confirm-actions button:disabled { opacity: .55; cursor: wait; }
        #confirm-yes { background: #30D158 !important; color: #06290f; box-shadow: 0 5px 0 #1f9a3f; }
        #confirm-no  { background: rgba(255,255,255,0.08) !important; color: #ff8a8a; box-shadow: inset 0 0 0 2px rgba(255,69,58,0.55); }

        /* ── Winner modal ── */
        #winner-modal {
            display: none; position: fixed; inset: 0; z-index: 200;
            align-items: center; justify-content: center;
            background: rgba(0,0,0,0.7);
        }
        #winner-modal.show { display: flex; }

        #winner-modal-box {
            position: relative;
            background: var(--card);
            border: 5px solid var(--yellow);
            border-radius: 28px;
            padding: 0 0 32px;
            text-align: center;
            max-width: 420px; width: 92%;
            box-shadow: 0 10px 0 #b8a000, 0 30px 60px rgba(0,0,0,0.7);
            animation: pop-in 0.4s cubic-bezier(.175,.885,.32,1.4) forwards;
            overflow: hidden;
        }
        @keyframes pop-in {
            from { transform: scale(0.5) rotate(-5deg); opacity: 0; }
            to   { transform: scale(1)   rotate(0deg);  opacity: 1; }
        }

        /* coloured stripe tabs at top of modal */
        #modal-stripe {
            display: flex; height: 10px;
        }
        #modal-stripe span {
            flex: 1;
        }

        #modal-body { padding: 24px 32px 0; }

        #winner-label {
            font-size: 0.72rem; font-weight: 900;
            letter-spacing: 0.2em; text-transform: uppercase;
            color: var(--orange);
            margin-bottom: 16px;
        }

        #winner-avatar-wrap {
            position: relative; display: inline-block; margin-bottom: 18px;
        }

        #winner-avatar {
            width: 116px; height: 116px; border-radius: 50%;
            overflow: hidden;
            background: var(--bg);
            border: 5px solid var(--yellow);
            display: flex; align-items: center; justify-content: center;
            font-size: 2.6rem; font-weight: 900; color: var(--yellow);
            margin: 0 auto;
            animation: avatar-ring 1.8s ease-in-out infinite;
        }
        @keyframes avatar-ring {
            0%,100% { box-shadow: 0 0 0 6px rgba(255,214,10,0.2); }
            50%      { box-shadow: 0 0 0 14px rgba(255,214,10,0.05); }
        }

        #winner-trophy {
            position: absolute; bottom: -2px; right: -4px; font-size: 2rem;
            animation: trophy-bounce 0.7s ease-in-out infinite alternate;
        }
        @keyframes trophy-bounce {
            from { transform: translateY(0)    rotate(-8deg); }
            to   { transform: translateY(-7px) rotate(8deg); }
        }

        #modal-winner-name {
            font-size: 2rem; font-weight: 900; color: var(--yellow);
            line-height: 1.15; margin-bottom: 6px;
        }
        #modal-winner-prize { font-size: 0.95rem; font-weight: 800; color: var(--orange); margin-top: 6px; }
        #modal-winner-round { font-size: 0.75rem; font-weight: 700; color: rgba(255,255,255,0.3); margin-top: 4px; }

        #modal-close {
            margin-top: 24px;
            background: var(--red);
            border: none; border-radius: 99px;
            padding: 12px 42px;
            font-size: 0.9rem; font-weight: 900; color: #fff;
            cursor: pointer; font-family: 'Nunito', sans-serif;
            box-shadow: 0 5px 0 #b01e3a;
            transition: transform 0.1s, box-shadow 0.1s;
        }
        #modal-close:active { transform: translateY(3px); box-shadow: 0 2px 0 #b01e3a; }

        /* ── Confetti ── */
        @keyframes cf-fall {
            0%   { transform: translateY(-30px) rotate(0deg);   opacity: 1; }
            100% { transform: translateY(108vh) rotate(800deg); opacity: 0; }
        }
        .cf { position: fixed; top: -30px; animation: cf-fall linear forwards; pointer-events: none; z-index: 9999; }

        /* ── Sidebar rows ── */
        .w-row {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 18px;
            border-top: 1px solid #f1f3f8;
        }
        .w-avatar {
            width: 34px; height: 34px; border-radius: 50%;
            overflow: hidden; flex-shrink: 0;
            background: #eef1ff;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.72rem; font-weight: 600; color: #3b5bff;
        }
        .w-name  { font-size: 0.85rem; font-weight: 600; color: #111827; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .w-meta  { font-size: 0.72rem; font-weight: 400; color: #9ca3af; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .w-prize { color: #4b5563; }
        .w-badge { flex-shrink: 0; font-size: 0.72rem; font-weight: 500; color: #9ca3af; }
        .w-empty { padding: 32px 18px; text-align: center; color: #9ca3af; font-size: 0.82rem; }

        /* ══ Pop-ups restyled to match the blue glass stage ══════════════════ */
        #confirm-modal, #winner-modal { background: rgba(4, 8, 34, 0.72); backdrop-filter: blur(6px); }
        #confirm-modal-box, #winner-modal-box {
            font-family: 'Poppins', system-ui, sans-serif;
            background: linear-gradient(180deg, rgba(30, 50, 140, 0.96) 0%, rgba(13, 24, 84, 0.97) 100%);
            border: 1px solid rgba(175, 195, 255, 0.35); border-radius: 22px;
            box-shadow: 0 0 0 1px rgba(255,255,255,.04) inset, 0 0 60px rgba(90, 120, 255, 0.35), 0 30px 60px rgba(0, 0, 30, 0.6);
            width: calc(100% - 32px); max-width: 440px; box-sizing: border-box; padding: 30px clamp(18px, 5vw, 30px) 26px;
            animation: modal-in .35s cubic-bezier(.2,.9,.3,1.15) forwards;
        }
        @keyframes modal-in { from { transform: translateY(14px) scale(.96); opacity: 0; } to { transform: none; opacity: 1; } }
        #confirm-modal-box #modal-stripe, #winner-modal-box #modal-stripe { display: none; }
        #confirm-body, #modal-body { padding: 0; }

        /* label pill */
        #confirm-label, #winner-label {
            display: inline-flex; align-items: center; gap: 6px; margin: 0 0 18px;
            padding: 5px 14px; border-radius: 99px; background: rgba(160, 180, 255, 0.14);
            border: 1px solid rgba(190, 205, 255, 0.28); color: #dfe6ff;
            font-size: .72rem; font-weight: 600; letter-spacing: .12em; text-transform: uppercase;
        }

        /* avatar: white disc with a soft blue glow, like the centre card */
        #confirm-avatar, #winner-avatar {
            width: 96px; height: 96px; margin: 0 auto 14px; border-radius: 50%; overflow: hidden;
            background: linear-gradient(180deg, #ffffff, #e8edff); border: 3px solid #fff;
            box-shadow: 0 0 0 4px rgba(120, 150, 255, 0.35), 0 0 34px rgba(110, 140, 255, 0.7);
            display: flex; align-items: center; justify-content: center;
            color: #2b3fd6; font-size: 2rem; font-weight: 700; animation: none;
        }
        #winner-avatar-wrap { display: block; position: relative; width: 96px; margin: 0 auto 14px; }
        #winner-avatar-wrap #winner-avatar { margin: 0; }
        #winner-trophy { bottom: 10px; right: -6px; font-size: 1.7rem; }

        #confirm-name, #modal-winner-name { font-size: 1.55rem; font-weight: 700; color: #fff; line-height: 1.2; margin: 0; }
        #confirm-code, #modal-winner-code { margin-top: 3px; font-size: .9rem; font-weight: 500; color: rgba(215, 225, 255, 0.7); min-height: 1.2em; }

        /* prize chip */
        #confirm-prize, #modal-winner-prize {
            display: inline-block; margin-top: 12px; padding: 5px 14px; border-radius: 99px; min-height: 0;
            background: rgba(255, 214, 10, 0.14); border: 1px solid rgba(255, 214, 10, 0.4);
            color: #ffe066; font-size: .88rem; font-weight: 600;
        }
        #confirm-prize:empty, #modal-winner-prize:empty { display: none; }
        #modal-winner-round { margin-top: 10px; font-size: .78rem; font-weight: 500; color: rgba(215, 225, 255, 0.55); }

        #confirm-question {
            margin-top: 20px; padding-top: 18px; border-top: 1px solid rgba(175, 195, 255, 0.2);
            font-size: 1.08rem; font-weight: 600; color: #fff;
        }
        #confirm-hint { margin-top: 3px; font-size: .8rem; font-weight: 400; color: rgba(215, 225, 255, 0.6); }

        /* buttons */
        #confirm-actions { gap: 10px; margin-top: 18px; }
        #confirm-actions button, #modal-close {
            border-radius: 14px; padding: 14px 16px; font: 600 1rem 'Poppins', system-ui, sans-serif; letter-spacing: .01em;
        }
        #confirm-yes, #modal-close {
            background: linear-gradient(90deg, #3b5bff, #6d83ff) !important; color: #fff;
            box-shadow: 0 0 24px rgba(110, 140, 255, 0.55), 0 8px 20px rgba(0, 0, 40, 0.4);
        }
        #confirm-yes:hover, #modal-close:hover { box-shadow: 0 0 34px rgba(130, 160, 255, 0.85), 0 8px 20px rgba(0, 0, 40, 0.4); }
        #confirm-no {
            background: rgba(160, 180, 255, 0.10) !important; color: #ffb3b3;
            box-shadow: inset 0 0 0 1.5px rgba(255, 120, 120, 0.45);
        }
        #confirm-no:hover { background: rgba(255, 90, 90, 0.14) !important; }
        #modal-close { width: 100%; margin-top: 22px; }
        #modal-close:active, #confirm-actions button:active { transform: translateY(2px); box-shadow: none; }
    </style>
</head>
<body>

<div id="bg" aria-hidden="true"></div>

<div id="app">

    {{-- Topbar --}}
    <div id="topbar">
        <div style="display:flex;align-items:center;gap:12px;">
            <button id="toggle-sidebar" onclick="toggleSidebar()">
                <span id="sidebar-icon">☰</span>
                <span id="sidebar-label">Winners</span>
                <span id="sidebar-badge"></span>
            </button>
            <div>
                <div id="topbar-title">🎉 {{ $event->title }}</div>
                @if($event->venue)
                <div id="topbar-venue">📍 {{ $event->venue }}</div>
                @endif
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
            <button type="button" id="sound-btn" onclick="Sound.toggle()" title="Sound effects">🔊</button>
            <div id="live-pill"><span id="live-dot"></span> Live</div>
        </div>
    </div>
    @if(request('mode') === 'control')
    <div id="mode-banner">
        🎮 Controller — the audience display mirrors everything you do here.
        <span id="link-status" title="Whether your last step reached the server for the display">Display link OK</span>
        <a href="{{ route('events.raffle.public', ['event' => $event->id, 'pin' => request('pin'), 'mode' => 'display']) }}" target="_blank">Open display ↗</a>
    </div>
    @endif

    {{-- Stage --}}
    <div id="stage">

        {{-- Sidebar --}}
        <div id="sidebar">
            <div id="sidebar-inner">
                <div id="sidebar-header">Winner History <span id="sidebar-count"></span></div>
                <div id="winner-list">
                    <div class="w-empty">No draws yet</div>
                </div>
            </div>
        </div>

        {{-- Draw area — the card carousel is the stage (idle / drawing / landed) --}}
        <div id="draw-area" data-mode="idle">
            <div id="picker-title">Lucky Draw</div>
            <div id="picker-subtitle">Press Draw to pick a random winner.</div>
            <div id="pool-label"></div>
            <div id="picker-stage">
                <div id="picker-glow"></div>
                <div id="picker-track"></div>
            </div>
            <div id="picker-progress"><div id="picker-bar"></div></div>
            <div id="picker-status">Random selection in progress...</div>

            <div id="draw-controls">
                <div id="draw-options">
                    <input id="prize-label" type="text" placeholder="🎁 Prize label (optional)…">
                    <div id="win-stepper" title="How many winners one draw picks">
                        <button type="button" onclick="stepWinners(-1)" aria-label="Fewer winners">−</button>
                        <input id="win-count" type="number" min="1" max="50" value="1" onchange="stepWinners(0)" aria-label="Winners per draw">
                        <span id="win-unit">winner</span>
                        <button type="button" onclick="stepWinners(1)" aria-label="More winners">+</button>
                    </div>
                    <button type="button" id="grand-btn" onclick="setGrand(!grandMode)" title="Grand prize mode — one winner, slower spin, bigger reveal">⭐</button>
                </div>
                <button id="draw-btn" onclick="startDraw()">🎰 Draw!</button>
            </div>
        </div>

    </div>
</div>

{{-- Presence confirmation — shown after the spin, BEFORE anything is recorded.
     Must-be-present rule: only a confirmed person becomes the winner. --}}
<div id="confirm-modal">
    <div id="confirm-modal-box">
        <div id="modal-stripe">
            <span style="background:var(--blue)"></span>
            <span style="background:var(--yellow)"></span>
            <span style="background:var(--blue)"></span>
            <span style="background:var(--yellow)"></span>
        </div>
        <div id="confirm-body">
            <div id="confirm-label">🎯 Winner selected</div>
            <div id="confirm-avatar"></div>
            <div id="confirm-name"></div>
            <div id="confirm-code"></div>
            <div id="confirm-prize"></div>
            <div id="confirm-question">Is this person present?</div>
            <div id="confirm-hint">The prize goes only to a winner who is present.</div>
            <div id="confirm-actions">
                <button type="button" id="confirm-yes" onclick="confirmPresent()">✓ Present — Confirm Winner</button>
                <button type="button" id="confirm-no" onclick="markNotPresent()">✗ Not present — draw again</button>
            </div>
            <div id="confirm-wait">⏳ Waiting for staff to confirm…</div>
        </div>
    </div>
</div>

{{-- Winner modal --}}
<div id="winner-modal" onclick="if(event.target===this)closeWinnerModal()">
    <div id="winner-modal-box">
        <div id="modal-stripe">
            <span style="background:var(--red)"></span>
            <span style="background:var(--orange)"></span>
            <span style="background:var(--yellow)"></span>
            <span style="background:var(--blue)"></span>
        </div>
        <div id="modal-body">
            <div id="winner-label">🎉 Congratulations!</div>
            <div id="winner-avatar-wrap">
                <div id="winner-avatar"></div>
                <span id="winner-trophy">🏆</span>
            </div>
            <div id="modal-winner-name"></div>
            <div id="modal-winner-code"></div>
            <div id="modal-winner-prize"></div>
            <div id="modal-winner-round"></div>
            <button id="modal-close" onclick="closeWinnerModal()">Continue</button>
        </div>
    </div>
</div>

{{-- Floating confetti behind the stage --}}
<div id="picker-confetti" aria-hidden="true"></div>

{{-- Undo a confirmed winner --}}
<div id="undo-modal" onclick="if (event.target === this) closeUndo()">
    <div id="undo-box" role="dialog" aria-labelledby="undo-title">
        <div class="fm-title" id="undo-title">Undo this winner?</div>
        <div id="undo-who"></div>
        <label class="undo-field">Reason (required)
            <input id="undo-reason" type="text" maxlength="200" placeholder="e.g. Confirmed by mistake" autocomplete="off">
        </label>
        <label class="undo-check"><input type="checkbox" id="undo-keepout"> Keep them out of the rest of this draw</label>
        <div class="undo-actions">
            <button type="button" class="undo-cancel" onclick="closeUndo()">Cancel</button>
            <button type="button" id="undo-go" onclick="submitUndo()">Undo winner</button>
        </div>
    </div>
</div>

{{-- Multi-winner results (one draw, several winners) --}}
<div id="batch-modal">
    <div id="batch-box" role="dialog" aria-labelledby="batch-title">
        <div id="batch-label"></div>
        <div id="batch-title"></div>
        <div id="batch-prize"></div>
        <div id="batch-hint"></div>
        <div id="batch-grid"></div>
        <div id="batch-foot"><button type="button" id="batch-confirm"></button></div>
    </div>
</div>

{{-- Area filter: floating button + multi-select pop-up --}}
<button type="button" id="filter-fab" onclick="openFilterModal()" title="Only draw from selected areas">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 5h18l-7 8.5V19l-4 2v-7.5z"/></svg>
    <span id="filter-fab-label">Filter areas</span>
    <span id="filter-fab-count"></span>
</button>
<div id="filter-modal" onclick="if (event.target === this) closeFilterModal()">
    <div id="filter-box" role="dialog" aria-labelledby="fm-title">
        <div class="fm-head">
            <div>
                <div class="fm-title" id="fm-title">Filter by area</div>
                <div class="fm-sub">Only attendees from the selected areas can be drawn.</div>
            </div>
            <button type="button" class="fm-x" onclick="closeFilterModal()" aria-label="Close">✕</button>
        </div>
        <input id="fm-search" type="text" placeholder="Search area…" oninput="renderFilterList()" autocomplete="off">
        <div class="fm-tools">
            <span id="fm-picked"></span>
            <span>
                <button type="button" onclick="fmSelectAll(true)">Select all</button>
                <button type="button" onclick="fmSelectAll(false)">Clear</button>
            </span>
        </div>
        <div id="fm-list"></div>
        <div class="fm-foot">
            <span id="fm-summary"></span>
            <button type="button" id="fm-apply" onclick="applyFilterModal()">Apply</button>
        </div>
    </div>
</div>

<div id="cf-container" style="position:fixed;inset:0;pointer-events:none;z-index:9998;"></div>


{{-- ── QR Modal ── --}}
<div id="qr-modal" onclick="if(event.target===this)closeQrModal()"
    style="display:none;position:fixed;inset:0;z-index:300;
           align-items:center;justify-content:center;
           background:rgba(0,0,0,0.75);">

    <div style="background:#2A0072;border:4px solid #FF9500;border-radius:28px;
                max-width:400px;width:92%;overflow:hidden;
                box-shadow:0 8px 0 #b86a00,0 24px 48px rgba(0,0,0,0.7);
                animation:pop-in 0.4s cubic-bezier(.175,.885,.32,1.4) forwards;">

        {{-- stripe --}}
        <div style="display:flex;height:8px;">
            <span style="flex:1;background:#FF2D55"></span>
            <span style="flex:1;background:#FF9500"></span>
            <span style="flex:1;background:#FFD60A"></span>
            <span style="flex:1;background:#0A84FF"></span>
        </div>

        <div style="padding:24px 24px 28px;">

            {{-- header --}}
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                <div style="font-size:1rem;font-weight:900;color:#FFD60A;">📷 Scan to Check In</div>
                <button onclick="closeQrModal()"
                    style="background:rgba(255,255,255,0.1);border:none;border-radius:50%;
                           width:32px;height:32px;color:#fff;font-size:1rem;cursor:pointer;
                           display:flex;align-items:center;justify-content:center;font-family:'Nunito',sans-serif;">✕</button>
            </div>

            {{-- camera feed --}}
            <div id="qr-scanner-wrap" style="position:relative;border-radius:16px;overflow:hidden;
                                              background:#1C0050;border:3px solid #FF9500;
                                              aspect-ratio:1;width:100%;">
                <video id="qr-video" style="width:100%;height:100%;object-fit:cover;" playsinline muted></video>

                {{-- scan frame overlay --}}
                <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none;">
                    <div style="width:55%;height:55%;position:relative;">
                        <div style="position:absolute;top:0;left:0;width:24px;height:24px;border-top:3px solid #FFD60A;border-left:3px solid #FFD60A;border-radius:4px 0 0 0;"></div>
                        <div style="position:absolute;top:0;right:0;width:24px;height:24px;border-top:3px solid #FFD60A;border-right:3px solid #FFD60A;border-radius:0 4px 0 0;"></div>
                        <div style="position:absolute;bottom:0;left:0;width:24px;height:24px;border-bottom:3px solid #FFD60A;border-left:3px solid #FFD60A;border-radius:0 0 0 4px;"></div>
                        <div style="position:absolute;bottom:0;right:0;width:24px;height:24px;border-bottom:3px solid #FFD60A;border-right:3px solid #FFD60A;border-radius:0 0 4px 0;"></div>
                    </div>
                </div>

                {{-- scan line animation --}}
                <div id="scan-line" style="position:absolute;left:10%;right:10%;height:3px;
                                           background:#FFD60A;border-radius:99px;opacity:0.8;
                                           animation:scan-sweep 2s ease-in-out infinite;pointer-events:none;"></div>
            </div>

            <p id="qr-hint" style="text-align:center;font-size:0.78rem;font-weight:700;
                                    color:rgba(255,255,255,0.35);margin-top:12px;">
                Point camera at your QR code
            </p>

            {{-- manual input fallback --}}
            <div style="display:flex;gap:8px;margin-top:12px;">
                <input id="qr-manual" type="text" placeholder="Or type QR code manually…"
                    style="flex:1;background:#1C0050;border:2px solid rgba(255,255,255,0.15);
                           border-radius:12px;padding:10px 14px;color:#fff;
                           font-size:0.82rem;font-weight:700;font-family:'Nunito',sans-serif;outline:none;"
                    onkeydown="if(event.key==='Enter')submitQr(this.value)">
                <button onclick="submitQr(document.getElementById('qr-manual').value)"
                    style="background:#0A84FF;border:none;border-radius:12px;padding:10px 16px;
                           color:#fff;font-size:0.85rem;font-weight:900;cursor:pointer;
                           font-family:'Nunito',sans-serif;white-space:nowrap;">Go</button>
            </div>

        </div>
    </div>
</div>

{{-- ── Attendance result modal ── --}}
<div id="attend-modal" onclick="if(event.target===this)closeAttendModal()"
    style="display:none;position:fixed;inset:0;z-index:400;
           align-items:center;justify-content:center;
           background:rgba(0,0,0,0.75);">

    <div id="attend-box"
        style="background:#2A0072;border-radius:28px;
               max-width:380px;width:92%;overflow:hidden;
               box-shadow:0 8px 0 #333,0 24px 48px rgba(0,0,0,0.7);
               animation:pop-in 0.4s cubic-bezier(.175,.885,.32,1.4) forwards;">

        {{-- dynamic top stripe colour set by JS --}}
        <div id="attend-stripe" style="height:10px;background:#FFD60A;"></div>

        <div style="padding:28px 28px 32px;text-align:center;">

            {{-- avatar --}}
            <div id="attend-avatar"
                style="width:100px;height:100px;border-radius:50%;overflow:hidden;
                       margin:0 auto 16px;border:5px solid #FFD60A;
                       background:#1C0050;display:flex;align-items:center;justify-content:center;
                       font-size:2.4rem;font-weight:900;color:#FFD60A;">
            </div>

            <div id="attend-status-icon" style="font-size:2rem;margin-bottom:6px;"></div>
            <div id="attend-status-label" style="font-size:0.7rem;font-weight:900;letter-spacing:0.15em;text-transform:uppercase;margin-bottom:10px;"></div>
            <div id="attend-name" style="font-size:1.6rem;font-weight:900;color:#FFD60A;line-height:1.2;margin-bottom:16px;"></div>

            {{-- detail rows --}}
            <div id="attend-details"
                style="background:#1C0050;border-radius:14px;padding:14px 16px;
                       text-align:left;display:flex;flex-direction:column;gap:8px;">
            </div>

            <button onclick="closeAttendModal()"
                style="margin-top:20px;background:#FF2D55;border:none;border-radius:99px;
                       padding:12px 40px;color:#fff;font-size:0.9rem;font-weight:900;
                       cursor:pointer;font-family:'Nunito',sans-serif;
                       box-shadow:0 5px 0 #b01e3a;">
                Close
            </button>
        </div>
    </div>
</div>

<style>
@keyframes scan-sweep {
    0%   { top: 15%; }
    50%  { top: 75%; }
    100% { top: 15%; }
}
</style>

<script>
const PIN   = '{{ request('pin') }}';
const BASE  = '{{ url('events/' . $event->id . '/raffle/public') }}';
const CSRF  = '{{ csrf_token() }}';
const ROUTES = {
    pool:        BASE + '/pool?pin='    + PIN,
    winners:     BASE + '/winners?pin=' + PIN,
    checkinQr:   BASE + '/checkin-qr',
    recordWinner: BASE + '/winner',
    noShow:       BASE + '/no-show',
    recordWinners: BASE + '/winners',
    undo:          BASE + '/undo',
    liveWait:      BASE + '/live/wait?pin=' + PIN,
    livePush:      BASE + '/live',
};

// Screen mode: 'display' (projector — no controls, follows the controller),
// 'control' (staff phone/tablet) or 'both' (one screen does everything).
const MODE = ['display', 'control'].includes(new URLSearchParams(location.search).get('mode'))
    ? new URLSearchParams(location.search).get('mode') : 'both';
document.body.classList.add('mode-' + MODE);
const ITEM_H = 100;

let allPool         = [];   // everyone eligible (from the server, minus no-shows)
let rafflePool      = [];   // allPool narrowed to the chosen area — what the draw uses
let areaFilter      = new Set();   // chosen area keys (address id, or 'none'); empty = all areas
let areaCounts      = new Map();   // area key → { label, n } for the current pool
let eventAreas      = null;        // the event's allowed areas [{id, label}] (null = open to everyone)
let lastRoundSeen   = 0;
let isAnimating     = false;
let sidebarOpen     = false;
let winnerCount     = 0;
// People drawn but NOT present: skipped for the rest of this session so the
// re-draw can't land on them again. (Not stored as winners.)
const skipped       = new Set();
let pendingDraw     = null;  // { winner, prizeLabel } waiting for presence confirmation
// True while THIS screen is saving its confirmed winner. The 1-second poll must
// not treat that new round as a draw from another screen and spin again.
let recordingLocal  = false;

// ─── Background ────────────────────────────────────────────────────────────

(function buildBg() {
    const bg     = document.getElementById('bg');
    const colors = ['#FF2D55','#FF9500','#FFD60A','#0A84FF'];
    for (let i = 0; i < 16; i++) {
        const b  = document.createElement('div');
        b.className = 'bubble';
        const sz = 30 + Math.random() * 90;
        b.style.cssText = `
            width:${sz}px;height:${sz}px;
            left:${Math.random()*100}%;
            background:${colors[i % colors.length]};
            opacity:0.15;
            animation-duration:${9+Math.random()*14}s;
            animation-delay:-${Math.random()*14}s;
        `;
        bg.appendChild(b);
    }
    const emojis = ['⭐','🌟','✨','💫','🎈','🎀'];
    for (let i = 0; i < 18; i++) {
        const s = document.createElement('div');
        s.className = 'star-bg';
        s.textContent = emojis[i % emojis.length];
        s.style.cssText = `
            left:${Math.random()*100}%;
            top:${Math.random()*100}%;
            font-size:${0.9+Math.random()*1.2}rem;
            animation-duration:${1.5+Math.random()*2.5}s;
            animation-delay:-${Math.random()*2.5}s;
        `;
        bg.appendChild(s);
    }
})();

// ─── Init ──────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    loadPool();
    loadWinners();
    setInterval(pollWinners, 1000);
    // The display takes one clock sample before listening (so the first spin it hears is timed
    // right), then refines the clock in the background.
    syncClock(1).then(() => {
        if (MODE === 'display') liveLoop();
        syncClock();
    });
    setInterval(syncClock, 60000);
});

// ─── Sidebar ───────────────────────────────────────────────────────────────

function toggleSidebar() {
    sidebarOpen = !sidebarOpen;
    document.getElementById('sidebar').classList.toggle('open', sidebarOpen);
    document.getElementById('sidebar-icon').textContent  = sidebarOpen ? '✕' : '☰';
    document.getElementById('sidebar-label').textContent = sidebarOpen ? 'Hide' : 'Winners';
    if (sidebarOpen) document.getElementById('sidebar-badge').style.display = 'none';
    setTimeout(() => buildIdleDrum(), 320);   // re-fit the carousel once the panel has slid in/out
}

// ─── Pool ──────────────────────────────────────────────────────────────────

async function loadPool() {
    try {
        const res = await fetch(ROUTES.pool).then(r => r.json());
        allPool = res.success ? res.pool.filter(p => !skipped.has(p.citizenId)) : [];
        eventAreas = res.success ? (res.areas || null) : null;
        refreshAreaFilter();
        buildIdleDrum();
    } catch(e) {}
}

// ─── Area filter ───────────────────────────────────────────────────────────
// Narrows the draw to attendees from one address zone (e.g. "Lakeside only").

const areaKey = p => p.addressId ? String(p.addressId) : 'none';

function refreshAreaFilter() {
    const prev = areaCounts;
    areaCounts = new Map();
    // A restricted event lists all its areas, even ones with nobody checked in yet.
    (eventAreas || []).forEach(a => areaCounts.set(String(a.id), { label: a.label, n: 0 }));
    allPool.forEach(p => {
        const k = areaKey(p);
        if (!areaCounts.has(k)) areaCounts.set(k, { label: p.address || 'No address', n: 0 });
        areaCounts.get(k).n++;
    });
    // Keep chosen areas listed even after their last person is drawn, so the filter doesn't silently reset.
    areaFilter.forEach(k => {
        if (!areaCounts.has(k)) areaCounts.set(k, { label: prev.get(k)?.label || 'Selected area', n: 0 });
    });

    rafflePool = areaFilter.size ? allPool.filter(p => areaFilter.has(areaKey(p))) : allPool;
    document.getElementById('pool-label').textContent = rafflePool.length
        ? rafflePool.length + ' player' + (rafflePool.length !== 1 ? 's' : '') + ' in the draw 🎟️'
        : '';

    const fab = document.getElementById('filter-fab');
    fab.classList.toggle('is-active', areaFilter.size > 0);
    document.getElementById('filter-fab-label').textContent = areaFilter.size ? 'Areas' : 'Filter areas';
    document.getElementById('filter-fab-count').textContent = areaFilter.size || '';
}

// "Lakesidenest Subd", "Lakesidenest Subd and Southville", or "3 areas".
function areaLabel() {
    const names = [...areaFilter].map(k => areaCounts.get(k)?.label || 'Selected area');
    if (names.length <= 2) return names.join(' and ');
    return `${names.length} areas`;
}

// ── Pop-up (edits a draft; nothing changes until Apply) ──
let filterDraft = new Set();

function openFilterModal() {
    if (isAnimating || pendingDraw) return;               // never change the pool mid-draw
    filterDraft = new Set(areaFilter);
    document.getElementById('fm-search').value = '';
    renderFilterList();
    document.getElementById('filter-modal').classList.add('show');
}

function closeFilterModal() {
    document.getElementById('filter-modal').classList.remove('show');
}

function renderFilterList() {
    const q    = document.getElementById('fm-search').value.trim().toLowerCase();
    const rows = [...areaCounts.entries()]
        .sort((a, b) => a[1].label.localeCompare(b[1].label))
        .filter(([, o]) => !q || o.label.toLowerCase().includes(q));
    document.getElementById('fm-list').innerHTML = rows.length
        ? rows.map(([k, o]) => `
            <label class="fm-row">
                <input type="checkbox" value="${escapeHtml(k)}" ${filterDraft.has(k) ? 'checked' : ''} onchange="fmToggle(this)">
                <span class="fm-check">✓</span>
                <span class="fm-name">${escapeHtml(o.label)}</span>
                <span class="fm-n">${o.n}</span>
            </label>`).join('')
        : `<div class="fm-empty">${areaCounts.size ? 'No area matches your search.' : 'No attendees yet.'}</div>`;
    updateFilterSummary();
}

function fmToggle(cb) {
    cb.checked ? filterDraft.add(cb.value) : filterDraft.delete(cb.value);
    updateFilterSummary();
}

// Select all / Clear act on the rows currently shown (respects the search box).
function fmSelectAll(on) {
    document.querySelectorAll('#fm-list input[type=checkbox]').forEach(cb => {
        cb.checked = on;
        on ? filterDraft.add(cb.value) : filterDraft.delete(cb.value);
    });
    updateFilterSummary();
}

function updateFilterSummary() {
    const n = filterDraft.size
        ? [...filterDraft].reduce((sum, k) => sum + (areaCounts.get(k)?.n || 0), 0)
        : allPool.length;
    document.getElementById('fm-picked').textContent  = filterDraft.size ? `${filterDraft.size} selected` : 'All areas';
    document.getElementById('fm-summary').textContent = `${n} player${n !== 1 ? 's' : ''} in the draw`;
}

function applyFilterModal() {
    areaFilter = new Set(filterDraft);
    closeFilterModal();
    refreshAreaFilter();
    idleSides = null;                                     // reshuffle the side cards from the new pool
    buildIdleDrum(true);
    pushLive('idle');
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && document.getElementById('filter-modal').classList.contains('show')) closeFilterModal();
});

// Idle stage: the carousel with a "Ready" card in the centre (never a person,
// so nobody looks pre-selected) and attendee cards on the sides. Only redrawn
// while idle unless forced (after a winner pop-up closes / pool runs out).
let idleSides = null;   // a fixed shuffle so the side cards don't jump on every refresh
// Re-layout the idle carousel when the screen crosses the 7/9-card breakpoint.
let lastSideCount = null;
window.addEventListener('resize', () => {
    if (sideCount() !== lastSideCount) buildIdleDrum();
});

function buildIdleDrum(force = false) {
    if (isAnimating) return;
    if (!force && stageMode !== 'idle') return;
    if (document.getElementById('winner-modal').classList.contains('show')) return;
    if (document.getElementById('confirm-modal').classList.contains('show')) return;
    if (document.getElementById('batch-modal').classList.contains('show')) return;
    setMode('idle');

    const n = rafflePool.length;
    const side = sideCount();
    lastSideCount = side;
    if (!idleSides || idleSides.length !== Math.min(side * 2, n) || idleSides.some(p => !rafflePool.find(q => q.citizenId === p.citizenId))) {
        idleSides = [...rafflePool].sort(() => Math.random() - 0.5).slice(0, side * 2);
    }
    const half  = Math.ceil(idleSides.length / 2);    // small pools (e.g. one area) still fill both sides
    const left  = idleSides.slice(0, half), right = idleSides.slice(half);
    const ready = !n ? ['Waiting for players…', 'Check in with your QR code 📷']
                     : [lastRoundSeen > 0 ? 'Ready for the next draw!' : 'Ready to draw!', `${n} player${n !== 1 ? 's' : ''} in the draw`];

    const track = document.getElementById('picker-track');
    track.innerHTML = left.map(cardHtml).join('')
        + `<div class="pk-card pk-ready"><div class="pk-name">${ready[0]}</div><div class="pk-code">${ready[1]}</div></div>`
        + right.map(cardHtml).join('');
    const cards = [...track.children];
    layoutCards(cards, left.length);
    cards.forEach(c => c.classList.toggle('is-center', c.classList.contains('pk-ready')));

    document.getElementById('picker-title').textContent    = grandMode ? '🏆 Grand Prize Draw' : 'Lucky Draw';
    document.getElementById('picker-subtitle').textContent = areaFilter.size
        ? `Drawing from ${areaLabel()} only.`
        : (lastRoundSeen > 0 ? 'Ready for the next draw.' : 'Press Draw to pick a random winner.');
    document.getElementById('picker-bar').style.width = '0%';
}

// ─── Animate ───────────────────────────────────────────────────────────────

// Winner picker: a horizontal 3D card carousel that decelerates onto the
// winner in the centre. Positions are a continuous float `pos` (index of the
// card at the centre); every frame each card is placed by its offset from it.
const PICK_MS  = 3800;
const GRAND_MS = 7500;          // grand prize: a longer, more suspenseful spin
let grandMode  = false;
const spinMs   = () => grandMode ? GRAND_MS : PICK_MS;
let spinId     = 0;             // a newer spin cancels an older animation loop
let spinWaiters = [];           // callbacks to run when the current spin lands
function whenSpinDone(fn) { isAnimating ? spinWaiters.push(fn) : fn(); }

(function buildPickerConfetti() {
    const box = document.getElementById('picker-confetti');
    const cols = ['#FFD60A', '#FF4D6D', '#3B82F6', '#22D3EE', '#A78BFA', '#FB923C'];
    for (let i = 0; i < 46; i++) {
        const el = document.createElement('div');
        const isDot = i % 3 === 0;
        el.className = isDot ? 'pk-dot' : 'pk-bit';
        const w = isDot ? 4 + Math.random() * 7 : 4 + Math.random() * 5;
        const h = isDot ? w : 10 + Math.random() * 12;
        el.style.cssText = `left:${Math.random() * 100}%;top:${Math.random() * 100}%;width:${w}px;height:${h}px;`
            + `background:${cols[i % cols.length]};--r:${Math.round(Math.random() * 180)}deg;`
            + `animation-duration:${3 + Math.random() * 5}s;animation-delay:-${Math.random() * 5}s;`;
        box.appendChild(el);
    }
})();

let stageMode = 'idle';
function setMode(mode) {
    stageMode = mode;
    document.getElementById('draw-area').dataset.mode = mode;
}

function cardHtml(p) {
    return `<div class="pk-card"><div class="pk-name">${escapeHtml(p.name)}</div><div class="pk-code">#${escapeHtml(p.code || p.citizenId)}</div></div>`;
}

// Place cards around a continuous centre position: neighbours shrink, tilt
// away in 3D and fade; the nearest card to the centre is highlighted.
// Cards visible on each side of the centre: 4 on desktop (9 cards), 3 on smaller screens (7).
function sideCount() {
    return window.innerWidth >= 1200 ? 4 : 3;
}

// Edge-to-edge packing. A tilted card in perspective isn't symmetric, so instead
// of estimating widths we measure an invisible probe card at each slot once and
// compute the translateX that makes each card's inner edge meet its neighbour.
let slotCache = { key: '', t: [] };
function slotOffsets(side, wide, cardW) {
    const stage = document.getElementById('picker-stage');
    const key = [side, cardW, stage.clientWidth].join('|');
    if (slotCache.key === key) return slotCache.t;
    if (!stage.clientWidth) return Array(side + 2).fill(0);   // stage hidden: nothing to measure yet

    const probe = document.createElement('div');
    probe.className = 'pk-card';
    probe.style.visibility = 'hidden';
    document.getElementById('picker-track').appendChild(probe);
    const sr = stage.getBoundingClientRect(), cx = sr.left + sr.width / 2;

    const t = [0];
    let prevRight = 0;
    for (let k = 0; k <= side + 1; k++) {
        const rest = `translateZ(${-cardDepth(k, wide)}px) rotateY(${-cardRot(k, wide)}deg) scale(${cardScale(k, side, wide)})`;
        probe.style.transform = 'translateX(100px) ' + rest;
        const shifted = probe.getBoundingClientRect().left;
        probe.style.transform = rest;
        const r = probe.getBoundingClientRect();
        const proj = (shifted - r.left) / 100;          // on-screen px per translateX px at this depth
        if (k > 0) t[k] = (prevRight - (r.left - cx)) / proj;
        prevRight = (r.right - cx) + t[k] * proj;
    }
    probe.remove();
    slotCache = { key, t };
    return t;
}
const cardScale = (a, side, wide) => 1 - Math.min(a, side) * (wide ? 0.15 : 0.17);
const cardRot   = (a, wide) => Math.min(38, a * (wide ? 18 : 22));
const cardDepth = (a, wide) => a * (wide ? 55 : 70);

function layoutCards(cards, pos) {
    const side = sideCount();
    const wide = side === 4;
    const t    = slotOffsets(side, wide, (cards[0] && cards[0].offsetWidth) || 240);
    let centre = null, best = 9;
    for (let i = 0; i < cards.length; i++) {
        const off = i - pos, a = Math.abs(off), el = cards[i];
        if (a > side + 0.6) { if (el.style.display !== 'none') el.style.display = 'none'; continue; }
        el.style.display = '';
        const dir = Math.sign(off), k = Math.min(Math.floor(a), side), f = a - k;
        const x   = dir * (t[k] + (t[k + 1] - t[k]) * f);
        el.style.transform = `translateX(${x}px) translateZ(${-cardDepth(a, wide)}px) rotateY(${-dir * cardRot(a, wide)}deg) scale(${cardScale(a, side, wide)})`;
        el.style.opacity   = String(Math.max(0, 1 - a * (wide ? 0.17 : 0.27)));
        el.style.zIndex    = String(100 - Math.round(a * 10));
        if (a < best) { best = a; centre = el; }
    }
    cards.forEach(c => c.classList.toggle('is-center', c === centre));
}

// elapsedMs > 0 starts the spin part-way through so a late display lands at the
// same moment as the controller; elapsedMs >= the spin length lands instantly.
function animateDraw(winner, poolSnapshot, elapsedMs = 0) {
    if (!poolSnapshot.length) return;
    isAnimating = true;
    setMode('drawing');

    let pool = poolSnapshot;
    let idx  = pool.findIndex(p => p.citizenId === winner.citizenId);
    if (idx < 0) { pool = [...pool, winner]; idx = pool.length - 1; }   // e.g. a display whose pool is a moment behind
    const n  = pool.length;
    const myId = ++spinId;
    const dur  = spinMs();

    // Sequence long enough to scroll ~30+ cards, ending on the winner — plus extra
    // copies after it so the right side is still full when the winner is last in the pool.
    const copies  = Math.max(2, Math.ceil(34 / n));
    const tail    = Math.ceil((sideCount() + 2) / n);
    const seq     = [];
    for (let c = 0; c <= copies + tail; c++) for (let i = 0; i < n; i++) seq.push(pool[i]);
    const startAt = sideCount();                        // side cards already filled on the left
    const target  = copies * n + idx;

    const track = document.getElementById('picker-track');
    track.innerHTML = seq.map(cardHtml).join('');
    const cards = [...track.children];
    const bar   = document.getElementById('picker-bar');

    document.getElementById('picker-title').textContent    = grandMode ? 'Picking the GRAND PRIZE winner…' : 'Picking a winner...';
    document.getElementById('picker-subtitle').textContent = 'Please wait while we select a random participant.';
    document.getElementById('picker-status').textContent   = 'Random selection in progress...';

    const ease  = grandMode ? (t => 1 - Math.pow(1 - t, 5)) : (t => 1 - Math.pow(1 - t, 4));   // grand: longer crawl at the end
    const snap  = elapsedMs >= dur - 120;              // too late to show any spin — just land
    const start = performance.now() - Math.min(Math.max(0, elapsedMs), dur);
    layoutCards(cards, startAt);
    let lastTickCard = startAt;
    (function frame(now) {
        if (myId !== spinId) return;                    // superseded by a newer spin
        const t = Math.min((now - start) / dur, 1);
        const pos = startAt + (target - startAt) * ease(t);
        layoutCards(cards, pos);
        if (Math.round(pos) !== lastTickCard) { lastTickCard = Math.round(pos); if (!snap) Sound.tick(); }
        bar.style.width = (t * 100) + '%';
        if (t < 1) { requestAnimationFrame(frame); return; }
        isAnimating = false;
        if (!snap) Sound.land(grandMode);
        setMode('landed');
        document.getElementById('picker-title').textContent    = 'We have a winner!';
        document.getElementById('picker-subtitle').textContent = 'Checking that the winner is present…';
        document.getElementById('picker-status').textContent   = 'Selection complete';
        const waiting = spinWaiters; spinWaiters = [];
        waiting.forEach(fn => fn());
    })(performance.now());
}

window.addEventListener('resize', () => { if (stageMode === 'idle') buildIdleDrum(); });

function escapeHtml(v) {
    return String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

// ─── Polling ───────────────────────────────────────────────────────────────

async function pollWinners() {
    if (recordingLocal) return;   // our own save in flight — not a remote draw
    try {
        const res = await fetch(ROUTES.winners).then(r => r.json());
        // Re-check: a poll that started just before Confirm can return during
        // (or right after) our save; that new round is ours, not a remote draw.
        if (recordingLocal || pendingDraw) return;
        if (!res.success) {
            // PIN expired/changed while audience was watching — show session-ended overlay
            showSessionEnded(res.message);
            return;
        }
        // Compare by (round, citizen) — after an Undo the next draw can reuse a round number.
        const winners = res.winners || [];
        const sig = winners.map(winnerKey).join(',');
        if (sig === winnersSig) return;
        const fresh = winners.filter(w => !seenKeys.has(winnerKey(w)));
        winnersSig = sig;
        winners.forEach(w => seenKeys.add(winnerKey(w)));
        lastRoundSeen = winners.length ? winners[winners.length - 1].round : 0;
        renderWinnerList(winners);
        setBadge(winners.length);

        // A draw saved by another screen (not this one): replay it here. The display
        // follows the live channel instead, so it never replays from the list.
        if (fresh.length && MODE !== 'display') {
            const snap  = [...allPool];
            const codes = new Map(snap.map(p => [p.citizenId, p.code]));
            fresh.forEach(w => { w.code = w.code || codes.get(w.citizenId); });
            animateDraw(fresh[0], snap);
            loadPool();
            setTimeout(() => fresh.length > 1 ? showBatchDone(fresh, fresh[0].prize_label) : showWinnerModal(fresh[0]), spinMs() + 400);
        }
    } catch(e) {}
}

let winnersSig  = null;
const seenKeys  = new Set();
const winnerKey = w => w.round + ':' + w.citizenId;
let lastWinners = [];

function setBadge(count) {
    winnerCount = count;
    const b = document.getElementById('sidebar-badge');
    b.textContent   = count;
    b.style.display = count && !sidebarOpen ? 'inline-block' : 'none';
}

let sessionEndedShown = false;
function showSessionEnded(reason) {
    if (sessionEndedShown) return;
    sessionEndedShown = true;

    const overlay = document.createElement('div');
    overlay.style.cssText = `
        position:fixed;inset:0;z-index:500;
        background:rgba(28,0,80,0.92);
        display:flex;align-items:center;justify-content:center;
        animation:fade-in 0.4s ease;
    `;
    overlay.innerHTML = `
        <div style="background:#2A0072;border:4px solid #FFD60A;border-radius:24px;
                    padding:40px 32px;text-align:center;max-width:360px;width:90%;
                    box-shadow:0 8px 0 #b8a000;">
            <div style="font-size:3.5rem;margin-bottom:12px;">⏰</div>
            <div style="font-size:1.4rem;font-weight:900;color:#FFD60A;margin-bottom:8px;">Session Ended</div>
            <div style="font-size:0.88rem;font-weight:700;color:rgba(255,255,255,0.45);line-height:1.5;">
                ${reason ? escapeHtml(reason) : 'The draw session has expired.<br>Ask the organizer to share a new link.'}
            </div>
        </div>
    `;
    document.body.appendChild(overlay);
}

async function loadWinners() {
    try {
        const res = await fetch(ROUTES.winners).then(r => r.json());
        if (!res.success) return;
        const winners = res.winners || [];
        winners.forEach(w => seenKeys.add(winnerKey(w)));
        winnersSig    = winners.map(winnerKey).join(',');
        lastRoundSeen = winners.length ? winners[winners.length - 1].round : 0;
        renderWinnerList(winners);
        setBadge(winners.length);
    } catch(e) {}
}

// ─── Winner modal ──────────────────────────────────────────────────────────

function showWinnerModal(w) {
    const av = document.getElementById('winner-avatar');
    av.innerHTML = w.photo
        ? `<img src="${w.photo}" style="width:100%;height:100%;object-fit:cover;">`
        : initials(w.name);
    document.getElementById('modal-winner-name').textContent  = w.name;
    // Remote draws come from the winners list (no code); look it up in the pool snapshot if possible.
    const code = w.code || (rafflePool.find(p => p.citizenId === w.citizenId) || {}).code;
    document.getElementById('modal-winner-code').textContent  = code ? '#' + code : '';
    document.getElementById('modal-winner-prize').textContent = w.prize_label ? '🎁 ' + w.prize_label : '';
    document.getElementById('modal-winner-round').textContent = `Round #${w.round}`;
    document.getElementById('winner-label').textContent = grandMode ? '🏆 Grand prize winner!' : '🎉 Congratulations!';
    const box = document.getElementById('winner-modal-box');
    box.style.animation = 'none'; box.offsetHeight; box.style.animation = '';
    document.getElementById('winner-modal').classList.add('show');
    launchConfetti(grandMode ? 260 : 100);
    Sound.cheer(grandMode);
}

function closeWinnerModal() {
    document.getElementById('winner-modal').classList.remove('show');
    buildIdleDrum(true);   // stage returns to "Ready for the next draw"
    pushLive('idle');
}

// ─── Winner list ───────────────────────────────────────────────────────────

function renderWinnerList(winners) {
    lastWinners = winners || [];
    const el = document.getElementById('winner-list');
    const count = (winners || []).length;
    document.getElementById('sidebar-count').textContent = count ? `${count} drawn` : '';
    if (!count) {
        el.innerHTML = '<div class="w-empty">No draws yet</div>';
        return;
    }
    el.innerHTML = [...winners].reverse().map(w => `
        <div class="w-row">
            <div class="w-avatar">
                ${w.photo ? `<img src="${escapeHtml(w.photo)}" style="width:100%;height:100%;object-fit:cover;">` : escapeHtml(initials(w.name))}
            </div>
            <div style="flex:1;min-width:0;">
                <div class="w-name">${escapeHtml(w.name)}</div>
                <div class="w-meta">${w.prize_label ? `<span class="w-prize">${escapeHtml(w.prize_label)}</span> · ` : ''}${escapeHtml(w.drawn_at)}</div>
            </div>
            <div class="w-badge">#${escapeHtml(w.round)}</div>
            ${w.claimed ? '' : `<button type="button" class="w-undo" onclick="openUndo(${Number(w.round)})" title="Undo this winner">Undo</button>`}
        </div>
    `).join('');
}

// ─── Confetti ──────────────────────────────────────────────────────────────

function launchConfetti(count = 100) {
    const colors = grandMode ? ['#FFD60A','#FFB800','#FFE680','#FFFFFF','#F59E0B'] : ['#FF2D55','#FF9500','#FFD60A','#0A84FF','#FFFFFF'];
    const cont   = document.getElementById('cf-container');
    for (let i = 0; i < count; i++) {
        const el  = document.createElement('div');
        const w   = 6 + Math.random() * 8;
        const h   = i % 2 === 0 ? w : 10 + Math.random() * 12;
        el.className = 'cf';
        el.style.cssText = `
            left:${Math.random()*100}vw;
            width:${w}px;height:${h}px;
            background:${colors[Math.floor(Math.random()*colors.length)]};
            border-radius:${i%2===0?'50%':'3px'};
            animation-duration:${2+Math.random()*3}s;
            animation-delay:${Math.random()*0.6}s;
        `;
        cont.appendChild(el);
        setTimeout(() => el.remove(), 6000);
    }
}

// ─── Helpers ───────────────────────────────────────────────────────────────

function initials(name) {
    if (!name) return '?';
    return name.split(' ').map(w => w[0]).filter(Boolean).slice(0,2).join('').toUpperCase();
}

// ─── Start Draw (staff triggers from public page) ─────────────────────────

// single = true forces a one-winner draw (used by the "not present → draw again" re-spin).
async function startDraw(single = false) {
    if (isAnimating) return;

    if (rafflePool.length === 0) {
        alert(areaFilter.size ? `No one from ${areaLabel()} is left in the draw.` : 'No eligible attendees in the pool!');
        return;
    }

    isAnimating = true;
    const btn = document.getElementById('draw-btn');
    btn.disabled = true;
    btn.textContent = '⏳ Drawing…';

    let prizeLabel = document.getElementById('prize-label').value.trim();
    if (grandMode && !prizeLabel) prizeLabel = 'Grand Prize';
    if (!single && winCount > 1) return startBatchDraw(prizeLabel);

    // Pick winner from current pool
    const winnerIdx  = Math.floor(Math.random() * rafflePool.length);
    const winner     = rafflePool[winnerIdx];
    const snap       = [...rafflePool];

    // Animate immediately; when it stops, ask whether the person is present.
    // Nothing is recorded until staff confirm.
    currentDrawId = newDrawId();
    animateDraw(winner, snap);
    pushLive('spin', { winner: liveW(winner), count: 1, prize: prizeLabel, started_at: serverNow() });
    setTimeout(() => showConfirmModal(winner, prizeLabel), spinMs() + 400);
}

// ─── Several winners in one draw ───────────────────────────────────────────
// One spin, then a grid of every winner. Staff tap "Not here" on anyone absent
// and that card is swapped for a fresh random attendee; Confirm saves them all
// in one request (all-or-nothing).

let winCount = 1;

function stepWinners(delta) {
    if (grandMode) return;                                // the grand prize always has one winner
    const el = document.getElementById('win-count');
    const n  = Math.max(1, Math.min(50, (parseInt(el.value, 10) || 1) + delta));
    winCount = n;
    el.value = n;
    document.getElementById('win-unit').textContent = n === 1 ? 'winner' : 'winners';
    document.getElementById('win-stepper').classList.toggle('is-multi', n > 1);
    if (!isAnimating) document.getElementById('draw-btn').textContent = drawBtnLabel();
}

function drawBtnLabel() {
    if (grandMode) return '🏆 Draw the Grand Prize!';
    return winCount > 1 ? `🎰 Draw ${winCount} winners!` : '🎰 Draw!';
}

// ─── Grand-prize mode ──────────────────────────────────────────────────────
// One winner, gold theme, slower spin, bigger confetti and fanfare.

let countBeforeGrand = 1;
function setGrand(on, push = true) {
    if (isAnimating || pendingDraw) return;
    on = !!on;
    if (on === grandMode) return;
    if (on) {
        countBeforeGrand = winCount;
        document.getElementById('win-count').value = 1;
        stepWinners(0);                                   // winCount → 1 (before grandMode blocks the stepper)
    }
    grandMode = on;
    document.body.classList.toggle('grand', on);
    document.getElementById('win-stepper').classList.toggle('is-locked', on);
    if (!on) { document.getElementById('win-count').value = countBeforeGrand; stepWinners(0); }
    document.getElementById('draw-btn').textContent = drawBtnLabel();
    if (stageMode === 'idle') buildIdleDrum(true);
    if (push) pushLive('idle');
}

// ─── Live channel: controller → audience display ───────────────────────────
// Every screen that runs the draw publishes each step; a mode=display screen
// follows them (spin → presence check → winners → idle).

function liveW(w) {
    return { citizenId: w.citizenId, name: w.name, code: w.code || null, photo: w.photo || null,
             address: w.address || null, round: w.round ?? null, prize_label: w.prize_label ?? null };
}

// Clock sync: "server now" on this device, so a spin's start time means the same
// thing on the controller and on the display (device clocks are often off).
let clockOffset = 0, clockRtt = Infinity;
const serverNow = () => Date.now() + clockOffset;
async function syncClock(samples = 3) {
    for (let k = 0; k < samples; k++) {
        try {
            const t0 = Date.now();
            const res = await fetchTimeout(ROUTES.liveWait, 12000).then(r => r.json());
            const t1 = Date.now();
            if (res && res.now && (t1 - t0) <= clockRtt * 1.5) {   // keep the sample with the least network noise
                clockRtt    = Math.min(clockRtt, t1 - t0);
                clockOffset = res.now - (t0 + t1) / 2;
            }
        } catch (e) {}
    }
    if (samples > 1) clockRtt = clockRtt * 2;             // let the next round replace stale samples
}

function fetchTimeout(url, ms, opts = {}) {
    const ac = new AbortController();
    const timer = setTimeout(() => ac.abort(), ms);
    return fetch(url, { ...opts, signal: ac.signal }).finally(() => clearTimeout(timer));
}
const sleep = ms => new Promise(r => setTimeout(r, ms));

let currentDrawId = null;
const newDrawId = () => Date.now().toString(36) + Math.random().toString(36).slice(2, 6);

// Publish a step. Steps go out in order through a small queue and failed sends are
// retried. A newer step replaces queued spin/check/idle steps (the display only
// needs the latest of those — it jumps to the winner if it missed the spin), but a
// winner reveal ('done') or an 'undo' is never dropped and is retried for longer.
const pushQueue = [];
let pushing = false;
const KEEP = ['done', 'undo'];

function pushLive(phase, extra = {}) {
    if (MODE === 'display') return;
    for (let k = pushQueue.length - 1; k >= 0; k--) {
        if (!KEEP.includes(pushQueue[k].phase)) pushQueue.splice(k, 1);
    }
    pushQueue.push({
        phase,
        body: JSON.stringify({ pin: PIN, state: { phase, draw: currentDrawId, grand: grandMode, areas: [...areaFilter], ...extra } }),
    });
    drainPushes();
}

async function drainPushes() {
    if (pushing) return;
    pushing = true;
    const waits = [300, 800, 1500, 3000];
    while (pushQueue.length) {
        const item = pushQueue[0];
        const tries = KEEP.includes(item.phase) ? 12 : 5;          // ~30 s for a reveal, ~9 s otherwise
        for (let attempt = 0; attempt < tries && pushQueue[0] === item; attempt++) {
            try {
                const res = await fetchTimeout(ROUTES.livePush, 6000, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: item.body,
                }).then(r => r.json());
                if (res && res.success) { setLinkStatus(true); break; }
            } catch (e) {}
            setLinkStatus(false);
            await sleep(waits[Math.min(attempt, waits.length - 1)]);
        }
        if (pushQueue[0] === item) pushQueue.shift();           // sent, or gave up
    }
    pushing = false;
}

function setLinkStatus(ok) {
    const el = document.getElementById('link-status');
    if (!el) return;
    el.classList.toggle('is-bad', !ok);
    el.textContent = ok ? 'Display link OK' : 'Not synced — retrying…';
}

// ── Display side: long-poll loop ──
// Asks "anything newer than version N?" and the server answers the moment there is.
// Errors back off and show "Reconnecting…"; after 3 failures in a row it assumes the
// host is cutting long requests and switches to quick polling for a minute.
const SIM_LAG = MODE === 'display' ? Math.max(0, parseInt(new URLSearchParams(location.search).get('lag'), 10) || 0) : 0;
let liveVer = null, liveFails = 0, shortPollUntil = 0, lastSpunDraw = null, lastSpunWinner = null, wasOffline = false;
const displayedWinners = new Set();

async function liveLoop() {
    while (!sessionEndedShown) {
        const short = Date.now() < shortPollUntil;
        const url = ROUTES.liveWait + '&since=' + (liveVer ?? -1) + (short ? '&hold=0' : '');
        try {
            const res = await fetchTimeout(url, short ? 6000 : 28000).then(r => { if (!r.ok) throw new Error(r.status); return r.json(); });
            if (!res.success) { showSessionEnded(res.message); return; }
            liveFails = 0;
            setDisplayLink(true);
            if (SIM_LAG) await sleep(SIM_LAG);                // staff rehearsal: pretend the network is slow
            if (res.state && res.ver !== liveVer) {
                // First state, or first after an outage: treat like a fresh join (don't re-show old results).
                const joining = liveVer === null || wasOffline;
                liveVer = res.ver;
                applyLive(res.state, joining);
            } else if (liveVer === null) {
                liveVer = res.ver;
            }
            wasOffline = false;
            if (short) await sleep(400);
        } catch (e) {
            wasOffline = true;
            liveFails++;
            setDisplayLink(false);
            if (liveFails >= 3) shortPollUntil = Date.now() + 60000;
            await sleep(Math.min(5000, 500 * liveFails));
        }
    }
}

function setDisplayLink(ok) {
    const pill = document.getElementById('live-pill');
    pill.classList.toggle('is-off', !ok);
    pill.lastChild.textContent = ok ? (SIM_LAG ? ` Live · test lag ${SIM_LAG / 1000}s` : ' Live') : ' Reconnecting…';
}

function syncLiveSettings(st) {
    if (!!st.grand !== grandMode) {
        grandMode = !!st.grand;
        document.body.classList.toggle('grand', grandMode);
    }
    const next = new Set(st.areas || []);
    if (next.size !== areaFilter.size || [...next].some(k => !areaFilter.has(k))) {
        areaFilter = next;
        refreshAreaFilter();
        idleSides = null;
    }
    if (stageMode === 'idle' && !isAnimating) buildIdleDrum(true);
}

function closeLiveModals() {
    ['confirm-modal', 'winner-modal', 'batch-modal'].forEach(id => document.getElementById(id).classList.remove('show'));
}

// joining = first state this display has seen (opened/refreshed mid-event).
function applyLive(st, joining = false) {
    syncLiveSettings(st);
    const age = serverNow() - (st.server_at || serverNow());   // how old this step is
    switch (st.phase) {
        case 'idle':
            spinWaiters = [];
            closeLiveModals();
            whenSpinDone(() => buildIdleDrum(true));
            break;
        case 'spin': {
            if (!st.winner) break;
            spinWaiters = [];
            closeLiveModals();
            lastSpunDraw   = st.draw || null;
            lastSpunWinner = st.winner.citizenId;
            // Start part-way in so we land when the controller does (or instantly if very late).
            const elapsed = Math.max(0, serverNow() - (st.started_at || st.server_at || serverNow()));
            animateDraw(st.winner, [...allPool], elapsed);
            break;
        }
        case 'check':
            ensureLanded(st);                             // missed the spin? jump to the winner first
            whenSpinDone(() => {
                if (st.batch) {
                    const open = document.getElementById('batch-modal').classList.contains('show');
                    renderBatchSelect(st, !open, true);
                    document.getElementById('batch-modal').classList.add('show');
                } else if (st.winners && st.winners[0]) {
                    showConfirmModal(st.winners[0], st.prize, true);
                }
            });
            break;
        case 'done':
            if (joining && age > 60000) break;            // an old result — don't pop it up on a freshly opened display
            ensureLanded(st);
            whenSpinDone(() => {
                closeLiveModals();
                const ws = st.winners || [];
                displayedWinners.clear();
                ws.forEach(w => displayedWinners.add(w.citizenId));
                if (st.batch) showBatchDone(ws, st.prize);
                else if (ws[0]) showWinnerModal(ws[0]);
                loadPool();
                loadWinners();
            });
            break;
        case 'undo':
            // The winner on screen was just undone → take the pop-up down.
            if (st.citizenId && displayedWinners.has(st.citizenId)) {
                closeLiveModals();
                displayedWinners.clear();
                whenSpinDone(() => buildIdleDrum(true));
            }
            loadPool();
            loadWinners();
            break;
    }
}

// A check/done for a draw this display never spun (missed it while offline, or
// opened mid-draw): put the winner in the centre at once.
function ensureLanded(st) {
    const w = (st.winners || [])[0];
    if (!w) return;
    if (st.draw ? st.draw === lastSpunDraw : w.citizenId === lastSpunWinner) return;   // already spun (or spinning) to this winner
    lastSpunDraw   = st.draw || null;
    lastSpunWinner = w.citizenId;
    spinWaiters = [];
    animateDraw(w, [...allPool], Infinity);
}

// ─── Undo a confirmed winner ───────────────────────────────────────────────

let undoRound = null;
function openUndo(round) {
    const w = lastWinners.find(x => Number(x.round) === Number(round));
    if (!w || MODE === 'display') return;
    undoRound = w.round;
    document.getElementById('undo-who').textContent = `${w.name} · Round #${w.round}${w.prize_label ? ' · ' + w.prize_label : ''}`;
    const reason = document.getElementById('undo-reason');
    reason.value = '';
    reason.classList.remove('is-invalid');
    document.getElementById('undo-keepout').checked = false;
    document.getElementById('undo-go').disabled = false;
    document.getElementById('undo-modal').classList.add('show');
    setTimeout(() => reason.focus(), 50);
}

function closeUndo() {
    document.getElementById('undo-modal').classList.remove('show');
    undoRound = null;
}

async function submitUndo() {
    const reasonEl = document.getElementById('undo-reason');
    const reason   = reasonEl.value.trim();
    if (reason.length < 3) { reasonEl.classList.add('is-invalid'); reasonEl.focus(); return; }
    const w = lastWinners.find(x => Number(x.round) === Number(undoRound));
    const keepOut = document.getElementById('undo-keepout').checked;
    const btn = document.getElementById('undo-go');
    btn.disabled = true;

    const res = await fetch(ROUTES.undo, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ pin: PIN, round: undoRound, reason, keep_out: keepOut }),
    }).then(r => r.json()).catch(() => ({ success: false, message: 'Network error — nothing was changed.' }));

    if (!res.success) {
        btn.disabled = false;
        alert(res.message || 'Could not undo this winner.');
        return;
    }
    if (keepOut && w) skipped.add(w.citizenId);
    closeUndo();
    await loadPool();
    await loadWinners();
    pushLive('undo', { name: res.name, citizenId: w ? w.citizenId : null });
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && document.getElementById('undo-modal').classList.contains('show')) closeUndo();
    if (e.key === 'Enter'  && document.getElementById('undo-modal').classList.contains('show')) submitUndo();
});

// ─── Sounds (synthesised with Web Audio — no files) ────────────────────────
// Happy, game-show style: a marimba "plink" climbing a major scale each time a
// card passes the centre (it slows down with the cards), a sparkly "ding!" when
// they land, and a bouncy "ta-da!" with party horn, twinkles and applause on the
// reveal. Browsers only allow audio after a tap, so a display shows "Tap for
// sound" until someone taps it once. Muted by default on the controller.

const Sound = (() => {
    const KEY = 'raffle-sound-' + MODE;
    let muted = MODE === 'control';
    try { const v = localStorage.getItem(KEY); if (v !== null) muted = v === 'off'; } catch (e) {}
    let ctx = null, out = null, noise = null;
    let step = 0, lastTick = 0;
    // C major pentatonic, two octaves — every note sounds happy next to every other.
    const SCALE = [523.25, 587.33, 659.25, 783.99, 880.00, 1046.5, 1174.7, 1318.5, 1568.0, 1760.0];

    function ensure() {
        try {
            if (!ctx) {
                ctx = new (window.AudioContext || window.webkitAudioContext)();
                out = ctx.createDynamicsCompressor();             // keeps layered sounds from clipping
                const master = ctx.createGain();
                master.gain.value = 0.9;
                out.connect(master).connect(ctx.destination);
            }
            if (ctx.state === 'suspended') ctx.resume().then(render, () => {});
        } catch (e) { ctx = null; }
        return ctx;
    }
    const ready = () => !muted && ctx && ctx.state === 'running';

    function env(g, t, peak, attack, dur) {
        g.gain.setValueAtTime(0.0001, t);
        g.gain.exponentialRampToValueAtTime(peak, t + attack);
        g.gain.exponentialRampToValueAtTime(0.0001, t + dur);
    }
    function osc(t, freq, dur, peak, type = 'sine', attack = 0.005) {
        const o = ctx.createOscillator(), g = ctx.createGain();
        o.type = type; o.frequency.value = freq;
        env(g, t, peak, attack, dur);
        o.connect(g).connect(out);
        o.start(t); o.stop(t + dur + 0.05);
    }
    // Marimba-ish pluck: round fundamental + a quick bright overtone.
    function pluck(t, f, peak = 0.22, dur = 0.35) {
        osc(t, f, dur, peak);
        osc(t, f * 4, dur * 0.25, peak * 0.25);
    }
    // Bell / chime: sine with inharmonic shimmer partials.
    function bell(t, f, dur, peak) {
        osc(t, f, dur, peak, 'sine', 0.003);
        osc(t, f * 2.76, dur * 0.5, peak * 0.3, 'sine', 0.003);
        osc(t, f * 5.4, dur * 0.25, peak * 0.12, 'sine', 0.003);
    }
    // Soft "brassy" fanfare note: filtered sawtooth.
    function brass(t, f, dur, peak) {
        const o = ctx.createOscillator(), lp = ctx.createBiquadFilter(), g = ctx.createGain();
        o.type = 'sawtooth'; o.frequency.value = f;
        lp.type = 'lowpass'; lp.frequency.setValueAtTime(900, t); lp.frequency.linearRampToValueAtTime(2600, t + 0.08);
        env(g, t, peak, 0.03, dur);
        o.connect(lp).connect(g).connect(out);
        o.start(t); o.stop(t + dur + 0.05);
    }
    // Party horn: a quick upward "brrt!" glide.
    function partyHorn(t) {
        const o = ctx.createOscillator(), lp = ctx.createBiquadFilter(), g = ctx.createGain();
        o.type = 'sawtooth';
        o.frequency.setValueAtTime(330, t);
        o.frequency.exponentialRampToValueAtTime(880, t + 0.25);
        lp.type = 'lowpass'; lp.frequency.value = 2200;
        env(g, t, 0.12, 0.02, 0.45);
        o.connect(lp).connect(g).connect(out);
        o.start(t); o.stop(t + 0.5);
    }
    function sparkles(t, count, span) {
        for (let i = 0; i < count; i++) {
            bell(t + Math.random() * span, SCALE[5 + Math.floor(Math.random() * 5)] * 2, 0.25, 0.05 + Math.random() * 0.04);
        }
    }
    function noiseBuf() {
        if (!noise) {
            noise = ctx.createBuffer(1, ctx.sampleRate * 2, ctx.sampleRate);
            const d = noise.getChannelData(0);
            for (let i = 0; i < d.length; i++) d[i] = Math.random() * 2 - 1;
        }
        return noise;
    }
    function clap(t, peak) {
        const src = ctx.createBufferSource(), f = ctx.createBiquadFilter(), g = ctx.createGain();
        src.buffer = noiseBuf();
        f.type = 'bandpass'; f.frequency.value = 1200 + Math.random() * 1800; f.Q.value = 1.2;
        g.gain.setValueAtTime(peak, t);
        g.gain.exponentialRampToValueAtTime(0.0001, t + 0.03 + Math.random() * 0.04);
        src.connect(f).connect(g).connect(out);
        src.start(t, Math.random() * 1.5); src.stop(t + 0.1);
    }
    function applause(t, count, span) {
        for (let i = 0; i < count; i++) clap(t + Math.random() * span * Math.random() ** 0.4, 0.03 + Math.random() * 0.05);
    }
    // "Ta-da!": bouncy pickup notes into a big bright major chord.
    function tada(t, big) {
        [[392.00, 0], [523.25, 0.09]].forEach(([f, d]) => { brass(t + d, f, 0.12, 0.14); pluck(t + d, f * 2, 0.12); });
        const at = t + 0.2, hold = big ? 1.6 : 1.1;
        [523.25, 659.25, 783.99].forEach(f => brass(at, f, hold, 0.11));
        [1046.5, 1318.5, 1568.0].forEach(f => bell(at, f, hold, 0.08));
    }

    function render() {
        const b = document.getElementById('sound-btn');
        if (!b) return;
        const locked = !muted && (!ctx || ctx.state !== 'running');
        b.classList.toggle('is-locked', locked && MODE === 'display');
        b.textContent = muted ? '🔇' : (locked && MODE === 'display' ? '🔈 Tap for sound' : '🔊');
        b.title = muted ? 'Sound off — tap to turn on' : 'Sound on — tap to mute';
    }

    // Any tap on the page unlocks audio (needed once per page load).
    document.addEventListener('pointerdown', () => { if (!muted) { ensure(); setTimeout(render, 100); } }, { capture: true });
    document.addEventListener('DOMContentLoaded', render);

    return {
        toggle() {
            const wasLocked = !muted && (!ctx || ctx.state !== 'running');
            ensure();
            if (!wasLocked) muted = !muted;                   // first tap on a locked display just enables sound
            try { localStorage.setItem(KEY, muted ? 'off' : 'on'); } catch (e) {}
            setTimeout(render, 100);
            if (!muted) setTimeout(() => ready() && bell(ctx.currentTime + 0.02, 1046.5, 0.4, 0.12), 120);   // little "sound on" chime
        },
        // One card passed the centre: next note up the scale (wraps around).
        tick() {
            if (!ready()) return;
            const now = performance.now();
            if (now - lastTick < 45) return;                  // very fast at the start — don't machine-gun
            lastTick = now;
            pluck(ctx.currentTime + 0.005, SCALE[step++ % SCALE.length], 0.16, 0.28);
        },
        // Cards stopped: sparkly "ding!" (bigger for the grand prize).
        land(grand) {
            if (!ready()) return;
            const t = ctx.currentTime + 0.02;
            step = 0;
            bell(t, 1318.5, 0.9, 0.2);
            bell(t + 0.09, 1760.0, grand ? 1.4 : 1.0, 0.18);
            sparkles(t + 0.1, grand ? 10 : 5, grand ? 0.8 : 0.5);
        },
        // Winner revealed: ta-da + party horn + twinkles + applause.
        cheer(grand) {
            if (!ready()) return;
            const t = ctx.currentTime + 0.05;
            tada(t, grand);
            partyHorn(t + 0.25);
            sparkles(t + 0.3, grand ? 24 : 12, grand ? 2.4 : 1.4);
            applause(t + 0.25, grand ? 200 : 110, grand ? 3.0 : 2.0);
            if (grand) { tada(t + 1.9, true); partyHorn(t + 2.1); }   // grand prize: a second, bigger ta-da
        },
        // For tests: is audio actually running?
        _state: () => (ctx ? ctx.state : 'none') + (muted ? '/muted' : ''),
    };
})();

// Unbiased random index (crypto RNG where available).
function randInt(n) {
    if (window.crypto && crypto.getRandomValues) {
        const lim = Math.floor(0x100000000 / n) * n, buf = new Uint32Array(1);
        do { crypto.getRandomValues(buf); } while (buf[0] >= lim);
        return buf[0] % n;
    }
    return Math.floor(Math.random() * n);
}

function pickRandom(pool, k) {
    const a = [...pool];
    for (let i = 0; i < k; i++) {
        const j = i + randInt(a.length - i);
        [a[i], a[j]] = [a[j], a[i]];
    }
    return a.slice(0, k);
}

function startBatchDraw(prizeLabel) {
    const picks = pickRandom(rafflePool, Math.min(winCount, rafflePool.length));
    pendingDraw = { batch: true, winners: picks, prizeLabel, short: winCount - picks.length };
    currentDrawId = newDrawId();
    animateDraw(picks[0], [...rafflePool]);
    pushLive('spin', { winner: liveW(picks[0]), count: picks.length, prize: prizeLabel, started_at: serverNow() });
    setTimeout(showBatchModal, spinMs() + 400);
}

function batchCardHtml(w, i, done, readOnly = false) {
    const av = w.photo ? `<img src="${escapeHtml(w.photo)}" style="width:100%;height:100%;object-fit:cover;">` : escapeHtml(initials(w.name));
    return `<div class="bt-card" data-i="${i}" style="animation-delay:${Math.min(i, 20) * 45}ms">
        <span class="bt-num">${i + 1}</span>
        <div class="bt-av">${av}</div>
        <div class="bt-name">${escapeHtml(w.name)}</div>
        <div class="bt-code">${w.code ? '#' + escapeHtml(w.code) : '&nbsp;'}</div>
        ${done ? `<div class="bt-round">Round #${escapeHtml(w.round)}</div>`
               : readOnly ? '' : `<button type="button" class="bt-x" onclick="batchRemove(${i})">✗ Not here</button>`}
    </div>`;
}

// animate = false re-renders without the pop-in (after a card is removed).
function showBatchModal(animate = true) {
    const d = pendingDraw;
    if (!d || !d.batch) return;
    renderBatchSelect(d, animate, false);
    pushLive('check', { batch: true, winners: d.winners.map(liveW), prize: d.prizeLabel, removed: d.removed || 0, short: d.short || 0 });
}

// The "winners selected" grid. readOnly = the display's mirror of the controller.
function renderBatchSelect(d, animate, readOnly) {
    const prize = d.prizeLabel ?? d.prize;
    const drawn = d.winners.length + (d.removed || 0);
    document.getElementById('batch-label').textContent = '🎯 Winners selected';
    document.getElementById('batch-title').textContent = `${d.winners.length} winner${d.winners.length !== 1 ? 's' : ''}`;
    document.getElementById('batch-prize').textContent = prize ? '🎁 ' + prize : '';
    document.getElementById('batch-hint').textContent  = (d.short > 0 ? `Only ${drawn} player${drawn !== 1 ? 's were' : ' was'} left in the draw. ` : '')
        + (d.removed ? `${d.removed} removed (not here). ` : '')
        + (readOnly ? 'Checking who’s here…' : 'Tap “Not here” to remove anyone absent.');
    const grid = document.getElementById('batch-grid');
    grid.classList.toggle('no-anim', !animate);
    grid.innerHTML = d.winners.map((w, i) => batchCardHtml(w, i, false, readOnly)).join('');
    const btn = document.getElementById('batch-confirm');
    btn.disabled = false;
    btn.textContent = `✓ Confirm ${d.winners.length} winner${d.winners.length !== 1 ? 's' : ''}`;
    btn.onclick = confirmBatch;
    document.getElementById('batch-modal').classList.add('show');
}

// Absent winner: just drop their card (no replacement draw). They're skipped for the
// rest of this session and the no-show is logged, same as a single draw.
function batchRemove(i) {
    const d = pendingDraw;
    if (!d || !d.batch) return;
    const gone = d.winners[i];

    skipped.add(gone.citizenId);
    allPool = allPool.filter(p => p.citizenId !== gone.citizenId);
    refreshAreaFilter();
    fetch(ROUTES.noShow, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json' },
        body: JSON.stringify({ citizenId: gone.citizenId, prize_label: d.prizeLabel, pin: PIN }),
    }).catch(() => {});

    d.winners.splice(i, 1);
    d.removed = (d.removed || 0) + 1;
    if (!d.winners.length) {
        pendingDraw = null;
        document.getElementById('batch-modal').classList.remove('show');
        resetDrawButton();
        alert('Everyone drawn was marked not here — no winners were saved.');
        buildIdleDrum(true);
        pushLive('idle');
        return;
    }
    showBatchModal(false);
}

async function confirmBatch() {
    const d = pendingDraw;
    if (!d || !d.batch) return;
    const btn = document.getElementById('batch-confirm');
    btn.disabled = true;
    btn.textContent = '⏳ Saving…';
    document.querySelectorAll('#batch-grid .bt-x').forEach(b => b.disabled = true);

    recordingLocal = true;
    const res = await fetch(ROUTES.recordWinners, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ citizenIds: d.winners.map(w => w.citizenId), prize_label: d.prizeLabel, pin: PIN }),
    }).then(r => r.json()).catch(() => ({ success: false, message: 'Network error — winners not saved. Please try again.' }));

    if (!res.success) {
        recordingLocal = false;
        showBatchModal();                                   // re-enable buttons
        alert(res.message || 'Winners not saved. Please try again.');
        return;
    }

    const rounds = res.rounds || {};
    const now = new Date().toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit', hour12: true });
    d.winners.forEach(w => { w.round = rounds[w.citizenId]; w.prize_label = d.prizeLabel || null; w.drawn_at = now; });
    const last = Math.max(...Object.values(rounds));
    d.winners.forEach(w => seenKeys.add(w.round + ':' + w.citizenId));   // so the poll doesn't replay our own draw
    lastRoundSeen  = last;
    pendingDraw    = null;
    recordingLocal = false;
    resetDrawButton();
    document.getElementById('prize-label').value = '';

    showBatchDone(d.winners, d.prizeLabel);
    pushLive('done', { batch: true, winners: d.winners.map(liveW), prize: d.prizeLabel });
    loadPool();
    loadWinners();
}

// Read-only "Congratulations" grid — after confirming here, or when another screen drew several.
function showBatchDone(winners, prizeLabel) {
    document.getElementById('batch-label').textContent = '🎉 Congratulations!';
    document.getElementById('batch-title').textContent = `${winners.length} winner${winners.length !== 1 ? 's' : ''}`;
    document.getElementById('batch-prize').textContent = prizeLabel ? '🎁 ' + prizeLabel : '';
    document.getElementById('batch-hint').textContent  = '';
    document.getElementById('batch-grid').classList.remove('no-anim');
    document.getElementById('batch-grid').innerHTML    = winners.map((w, i) => batchCardHtml(w, i, true)).join('');
    const btn = document.getElementById('batch-confirm');
    btn.disabled = false;
    btn.textContent = 'Continue';
    btn.onclick = () => {
        document.getElementById('batch-modal').classList.remove('show');
        buildIdleDrum(true);
        pushLive('idle');
    };
    const box = document.getElementById('batch-box');
    box.style.animation = 'none'; box.offsetHeight; box.style.animation = '';
    document.getElementById('batch-modal').classList.add('show');
    launchConfetti(160);
    Sound.cheer(false);
}

// ─── Presence confirmation ─────────────────────────────────────────────────

// readOnly: the display mirrors the controller's presence check (no buttons).
function showConfirmModal(winner, prizeLabel, readOnly = false) {
    if (!readOnly) {
        pendingDraw = { winner, prizeLabel };
        pushLive('check', { winners: [liveW(winner)], prize: prizeLabel });
    }
    document.getElementById('confirm-modal').classList.toggle('readonly', readOnly);
    const av = document.getElementById('confirm-avatar');
    av.innerHTML = winner.photo
        ? `<img src="${winner.photo}" style="width:100%;height:100%;object-fit:cover;">`
        : initials(winner.name);
    document.getElementById('confirm-name').textContent  = winner.name;
    document.getElementById('confirm-code').textContent  = winner.code ? '#' + winner.code : '';
    document.getElementById('confirm-prize').textContent = prizeLabel ? '🎁 ' + prizeLabel : '';
    document.querySelectorAll('#confirm-actions button').forEach(b => b.disabled = false);
    const box = document.getElementById('confirm-modal-box');
    box.style.animation = 'none'; box.offsetHeight; box.style.animation = '';
    document.getElementById('confirm-modal').classList.add('show');
}

function hideConfirmModal() {
    document.getElementById('confirm-modal').classList.remove('show');
}

function resetDrawButton() {
    isAnimating = false;
    const btn = document.getElementById('draw-btn');
    btn.disabled = false;
    btn.textContent = drawBtnLabel();
}

// ✓ Present → record the winner, then celebrate.
async function confirmPresent() {
    if (!pendingDraw) return;
    const { winner, prizeLabel } = pendingDraw;
    document.querySelectorAll('#confirm-actions button').forEach(b => b.disabled = true);

    recordingLocal = true;
    const res = await fetch(ROUTES.recordWinner, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json' },
        body: JSON.stringify({ citizenId: winner.citizenId, prize_label: prizeLabel, pin: PIN }),
    }).then(r => r.json()).catch(() => ({ success: false, message: 'Network error — winner not saved. Please try again.' }));

    if (!res.success) {
        recordingLocal = false;
        document.querySelectorAll('#confirm-actions button').forEach(b => b.disabled = false);
        alert(res.message);
        return;
    }

    pendingDraw = null;
    hideConfirmModal();
    resetDrawButton();

    seenKeys.add(res.round + ':' + winner.citizenId);   // so the poll doesn't replay our own draw
    lastRoundSeen  = res.round;
    recordingLocal = false;

    document.getElementById('prize-label').value = '';
    winner.round       = res.round;
    winner.prize_label = prizeLabel || null;
    winner.drawn_at    = new Date().toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit', hour12: true });

    showWinnerModal(winner);
    pushLive('done', { winners: [liveW(winner)], prize: prizeLabel });
    loadPool();
    loadWinners();
}

// ✗ Not present → not a winner; skip them for this session, log it, and
// draw again for the same prize.
async function markNotPresent() {
    if (!pendingDraw) return;
    const { winner, prizeLabel } = pendingDraw;
    pendingDraw = null;

    skipped.add(winner.citizenId);
    allPool = allPool.filter(p => p.citizenId !== winner.citizenId);
    refreshAreaFilter();

    // Audit trail (best effort — the re-draw shouldn't wait on it).
    fetch(ROUTES.noShow, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json' },
        body: JSON.stringify({ citizenId: winner.citizenId, prize_label: prizeLabel, pin: PIN }),
    }).catch(() => {});

    hideConfirmModal();
    resetDrawButton();

    if (!rafflePool.length) {
        alert(areaFilter.size ? `No one else from ${areaLabel()} is left in the draw.` : 'No one else is left in the draw.');
        buildIdleDrum(true);
        pushLive('idle');
        return;
    }
    // Same prize stays in the box; spin again.
    setTimeout(() => startDraw(true), 350);
}

// ─── QR Modal ──────────────────────────────────────────────────────────────

let qrStream    = null;
let qrScanning  = false;
let qrInterval  = null;

function openQrModal() {
    document.getElementById('qr-modal').style.display = 'flex';
    document.getElementById('qr-manual').value = '';
    document.getElementById('qr-hint').textContent = 'Point camera at your QR code';
    startCamera();
}

function closeQrModal() {
    document.getElementById('qr-modal').style.display = 'none';
    stopCamera();
}

async function startCamera() {
    try {
        qrStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment' }
        });
        const video = document.getElementById('qr-video');
        video.srcObject = qrStream;
        await video.play();
        qrScanning = true;
        scanFrame();
    } catch(e) {
        document.getElementById('qr-hint').textContent = 'Camera not available — use manual input below.';
    }
}

function stopCamera() {
    qrScanning = false;
    if (qrStream) {
        qrStream.getTracks().forEach(t => t.stop());
        qrStream = null;
    }
}

function scanFrame() {
    if (!qrScanning) return;
    const video = document.getElementById('qr-video');
    if (video.readyState < 2) { requestAnimationFrame(scanFrame); return; }

    const canvas  = document.createElement('canvas');
    canvas.width  = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);

    // Use BarcodeDetector if available (modern browsers)
    if ('BarcodeDetector' in window) {
        const detector = new BarcodeDetector({ formats: ['qr_code'] });
        detector.detect(canvas).then(codes => {
            if (codes.length > 0 && qrScanning) {
                qrScanning = false; // pause scanning
                submitQr(codes[0].rawValue);
            } else {
                requestAnimationFrame(scanFrame);
            }
        }).catch(() => requestAnimationFrame(scanFrame));
    } else {
        // BarcodeDetector not supported — manual input only
        document.getElementById('qr-hint').textContent = 'Auto-scan not supported on this browser — use manual input.';
    }
}

async function submitQr(code) {
    code = (code || '').trim();
    if (!code) return;

    document.getElementById('qr-hint').textContent = '⏳ Checking…';

    const res = await fetch(ROUTES.checkinQr, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json' },
        body: JSON.stringify({ qrcode: code, pin: PIN }),
    }).then(r => r.json()).catch(() => ({ success: false, message: 'Network error.' }));

    closeQrModal();
    showAttendResult(res);
}

function showAttendResult(res) {
    const box    = document.getElementById('attend-box');
    const stripe = document.getElementById('attend-stripe');
    const avatar = document.getElementById('attend-avatar');
    const icon   = document.getElementById('attend-status-icon');
    const label  = document.getElementById('attend-status-label');
    const name   = document.getElementById('attend-name');
    const details = document.getElementById('attend-details');

    if (!res.success) {
        stripe.style.background = '#FF2D55';
        box.style.border        = '4px solid #FF2D55';
        box.style.boxShadow     = '0 8px 0 #b01e3a,0 24px 48px rgba(0,0,0,0.7)';
        avatar.innerHTML        = '❌';
        avatar.style.borderColor = '#FF2D55';
        icon.textContent        = '';
        label.textContent       = 'Not Found';
        label.style.color       = '#FF2D55';
        name.textContent        = res.message;
        name.style.fontSize     = '1rem';
        details.innerHTML       = '';
    } else if (res.already) {
        stripe.style.background  = '#FF9500';
        box.style.border         = '4px solid #FF9500';
        box.style.boxShadow      = '0 8px 0 #b86a00,0 24px 48px rgba(0,0,0,0.7)';
        avatar.innerHTML         = res.photo
            ? `<img src="${res.photo}" style="width:100%;height:100%;object-fit:cover;">`
            : initials(res.name);
        avatar.style.borderColor = '#FF9500';
        icon.textContent         = '⚠️';
        label.textContent        = 'Already Checked In';
        label.style.color        = '#FF9500';
        name.textContent         = res.name;
        name.style.fontSize      = '1.6rem';
        details.innerHTML        = detailRows(res);
    } else {
        stripe.style.background  = '#34C759';
        box.style.border         = '4px solid #34C759';
        box.style.boxShadow      = '0 8px 0 #1e7a36,0 24px 48px rgba(0,0,0,0.7)';
        avatar.innerHTML         = res.photo
            ? `<img src="${res.photo}" style="width:100%;height:100%;object-fit:cover;">`
            : initials(res.name);
        avatar.style.borderColor = '#34C759';
        icon.textContent         = '✅';
        label.textContent        = 'Attendance Confirmed!';
        label.style.color        = '#34C759';
        name.textContent         = res.name;
        name.style.fontSize      = '1.6rem';
        details.innerHTML        = detailRows(res);
    }

    // re-trigger animation
    box.style.animation = 'none'; box.offsetHeight; box.style.animation = '';
    document.getElementById('attend-modal').style.display = 'flex';
}

function detailRows(res) {
    const rows = [
        { icon: '📍', label: 'Address',  value: res.address },
        { icon: '🎂', label: 'Age',      value: res.age + ' years old' },
        { icon: '👤', label: 'Gender',   value: res.gender },
        { icon: '🕐', label: 'Time In',  value: res.time_in },
    ];
    return rows.map(r => `
        <div style="display:flex;align-items:center;gap:8px;">
            <span style="font-size:1rem;width:22px;text-align:center;flex-shrink:0;">${r.icon}</span>
            <span style="font-size:0.72rem;font-weight:700;color:rgba(255,255,255,0.35);width:54px;flex-shrink:0;">${r.label}</span>
            <span style="font-size:0.85rem;font-weight:800;color:#fff;">${r.value ?? '—'}</span>
        </div>
    `).join('');
}

function closeAttendModal() {
    document.getElementById('attend-modal').style.display = 'none';
    // resume camera scanning if qr modal was open
}
</script>
</body>
</html>
