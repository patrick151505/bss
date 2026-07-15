<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎉 Lucky Draw — {{ $event->title }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --red:    #FF2D55;
            --orange: #FF9500;
            --yellow: #FFD60A;
            --blue:   #0A84FF;
            --white:  #FFFFFF;
            --bg:     #1C0050;
            --card:   #2A0072;
            --sidebar-bg: #200060;
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
            background: var(--bg);
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
            background: var(--card);
            border-bottom: 4px solid var(--yellow);
            z-index: 10;
        }

        #topbar-title {
            font-size: 1.05rem; font-weight: 900;
            color: var(--yellow);
            letter-spacing: 0.02em;
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
            background: var(--sidebar-bg);
            border-right: 3px solid var(--blue);
            display: flex; flex-direction: column;
        }
        #sidebar.open { width: 288px; }
        #sidebar-inner { width: 288px; display: flex; flex-direction: column; height: 100%; }

        #sidebar-header {
            padding: 14px 18px;
            background: var(--blue);
            font-size: 0.72rem; font-weight: 900;
            letter-spacing: 0.12em; text-transform: uppercase;
            color: #fff;
        }

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
            display: flex; align-items: center; gap: 10px;
            padding: 11px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .w-avatar {
            width: 36px; height: 36px; border-radius: 50%;
            overflow: hidden; flex-shrink: 0;
            background: var(--bg);
            border: 2px solid var(--yellow);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.75rem; font-weight: 900; color: var(--yellow);
        }
        .w-badge {
            width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;
            background: var(--blue);
            color: #fff; font-size: 0.65rem; font-weight: 900;
            display: flex; align-items: center; justify-content: center;
        }
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
        <div id="live-pill"><span id="live-dot"></span> Live</div>
    </div>

    {{-- Stage --}}
    <div id="stage">

        {{-- Sidebar --}}
        <div id="sidebar">
            <div id="sidebar-inner">
                <div id="sidebar-header">🏆 Winner History</div>
                <div id="winner-list">
                    <div style="padding:32px 16px;text-align:center;color:rgba(255,255,255,0.2);font-size:0.82rem;font-weight:700;">No draws yet! 🎲</div>
                </div>
            </div>
        </div>

        {{-- Draw area --}}
        <div id="draw-area">
            <div id="draw-title">✨ Lucky Draw ✨</div>
            <div id="pool-label"></div>

            <div id="drum-wrap">
                <div id="drum-fade-top"></div>
                <div id="drum-fade-bot"></div>
                <div id="drum-band"></div>
                <div id="drum-star-l" class="drum-corner">⭐</div>
                <div id="drum-star-r" class="drum-corner">⭐</div>
                <div id="drum-viewport">
                    <div id="drum-track">
                        <div class="drum-item" style="color:rgba(255,255,255,0.2);">Waiting for draw… 🎰</div>
                    </div>
                </div>
            </div>

            <div id="draw-status"></div>

            {{-- Prize label + Draw button --}}
            <div style="width:80%;max-width:900px;display:flex;flex-direction:column;align-items:center;gap:12px;">
                <input id="prize-label" type="text"
                    placeholder="🎁 Prize label (optional)…"
                    style="width:100%;background:#2A0072;border:3px solid #FF9500;border-radius:16px;
                           padding:12px 18px;color:#fff;font-size:0.95rem;font-weight:800;
                           font-family:'Nunito',sans-serif;outline:none;text-align:center;
                           box-shadow:0 5px 0 #b86a00;">

                <button id="draw-btn" onclick="startDraw()"
                    style="width:100%;background:#FF2D55;border:none;border-radius:20px;
                           padding:18px;color:#fff;font-size:1.6rem;font-weight:900;
                           font-family:'Nunito',sans-serif;cursor:pointer;
                           box-shadow:0 8px 0 #b01e3a,0 16px 32px rgba(255,45,85,0.3);
                           letter-spacing:0.04em;transition:transform 0.1s,box-shadow 0.1s;"
                    onmousedown="this.style.transform='translateY(5px)';this.style.boxShadow='0 3px 0 #b01e3a'"
                    onmouseup="this.style.transform='';this.style.boxShadow='0 8px 0 #b01e3a,0 16px 32px rgba(255,45,85,0.3)'"
                    ontouchstart="this.style.transform='translateY(5px)';this.style.boxShadow='0 3px 0 #b01e3a'"
                    ontouchend="this.style.transform='';this.style.boxShadow='0 8px 0 #b01e3a,0 16px 32px rgba(255,45,85,0.3)'">
                    🎰 Draw!
                </button>
            </div>

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
            <div id="winner-label">🎊 We have a winner! 🎊</div>
            <div id="winner-avatar-wrap">
                <div id="winner-avatar"></div>
                <span id="winner-trophy">🏆</span>
            </div>
            <div id="modal-winner-name"></div>
            <div id="modal-winner-prize"></div>
            <div id="modal-winner-round"></div>
            <button id="modal-close" onclick="closeWinnerModal()">Yay! Close 🎉</button>
        </div>
    </div>
</div>

<div id="cf-container" style="position:fixed;inset:0;pointer-events:none;z-index:9998;"></div>

{{-- ── QR Check-in button (bottom-right FAB) ── --}}
<button id="qr-fab" onclick="openQrModal()"
    style="position:fixed;bottom:24px;right:24px;z-index:50;
           width:64px;height:64px;border-radius:50%;border:none;
           background:#FF9500;color:#fff;font-size:1.8rem;
           box-shadow:0 6px 0 #b86a00,0 10px 24px rgba(0,0,0,0.4);
           cursor:pointer;display:flex;align-items:center;justify-content:center;
           font-family:'Nunito',sans-serif;
           transition:transform 0.1s,box-shadow 0.1s;"
    title="Scan QR to check in"
    onmousedown="this.style.transform='translateY(4px)';this.style.boxShadow='0 2px 0 #b86a00,0 6px 16px rgba(0,0,0,0.4)'"
    onmouseup="this.style.transform='';this.style.boxShadow='0 6px 0 #b86a00,0 10px 24px rgba(0,0,0,0.4)'"
    ontouchstart="this.style.transform='translateY(4px)';this.style.boxShadow='0 2px 0 #b86a00,0 6px 16px rgba(0,0,0,0.4)'"
    ontouchend="this.style.transform='';this.style.boxShadow='0 6px 0 #b86a00,0 10px 24px rgba(0,0,0,0.4)'">
    📷
</button>

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
};
const ITEM_H = 100;

let rafflePool      = [];
let lastRoundSeen   = 0;
let isAnimating     = false;
let sidebarOpen     = false;
let winnerCount     = 0;
let localDrawRound  = 0; // round triggered by this device — skip poll animation for it

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
});

// ─── Sidebar ───────────────────────────────────────────────────────────────

function toggleSidebar() {
    sidebarOpen = !sidebarOpen;
    document.getElementById('sidebar').classList.toggle('open', sidebarOpen);
    document.getElementById('sidebar-icon').textContent  = sidebarOpen ? '✕' : '☰';
    document.getElementById('sidebar-label').textContent = sidebarOpen ? 'Hide' : 'Winners';
    if (sidebarOpen) document.getElementById('sidebar-badge').style.display = 'none';
}

function bumpBadge() {
    winnerCount++;
    if (!sidebarOpen) {
        const b = document.getElementById('sidebar-badge');
        b.textContent   = winnerCount;
        b.style.display = 'inline-block';
    }
}

// ─── Pool ──────────────────────────────────────────────────────────────────

async function loadPool() {
    try {
        const res = await fetch(ROUTES.pool).then(r => r.json());
        rafflePool = res.success ? res.pool : [];
        document.getElementById('pool-label').textContent = rafflePool.length
            ? rafflePool.length + ' player' + (rafflePool.length !== 1 ? 's' : '') + ' in the draw 🎟️'
            : '';
        buildIdleDrum();
    } catch(e) {}
}

function buildIdleDrum() {
    if (isAnimating || !rafflePool.length) return;
    const track = document.getElementById('drum-track');
    let html = '';
    for (let c = 0; c < 2; c++)
        for (const p of rafflePool)
            html += `<div class="drum-item">${p.name}</div>`;
    track.innerHTML = html;
    track.style.transform = 'translateY(0)';
    track.style.filter    = '';
}

// ─── Animate ───────────────────────────────────────────────────────────────

function animateDraw(winner, poolSnapshot) {
    if (!poolSnapshot.length) return;
    isAnimating = true;

    const pool = poolSnapshot;
    const n    = pool.length;
    let idx    = pool.findIndex(p => p.citizenId === winner.citizenId);
    if (idx < 0) idx = 0;

    const leadCopies  = Math.ceil(40 / n);
    const totalCopies = leadCopies + 1;
    const landingCopy = leadCopies;

    const track = document.getElementById('drum-track');
    let html = '';
    for (let c = 0; c < totalCopies; c++)
        for (let i = 0; i < n; i++) {
            const win = c === landingCopy && i === idx;
            html += `<div class="drum-item${win?' is-winner':''}">${pool[i].name}</div>`;
        }
    track.innerHTML = html;
    track.style.transform = 'translateY(0)';
    track.style.filter    = '';

    const finalY   = (landingCopy * n + idx) * ITEM_H - ITEM_H;
    const duration = 3800;
    const startT   = performance.now();
    function ease(t) { return 1 - Math.pow(1 - t, 4); }

    (function frame(now) {
        const t   = Math.min((now - startT) / duration, 1);
        const y   = finalY * ease(t);
        const spd = 1 - t;
        track.style.filter    = spd > 0.25 ? `blur(${Math.round(spd * 9)}px)` : '';
        track.style.transform = `translateY(-${y}px)`;
        if (t < 1) requestAnimationFrame(frame);
        else { track.style.filter = ''; isAnimating = false; }
    })(performance.now());
}

// ─── Polling ───────────────────────────────────────────────────────────────

async function pollWinners() {
    try {
        const res = await fetch(ROUTES.winners).then(r => r.json());
        if (!res.success) {
            // PIN expired while audience was watching — show session-ended overlay
            showSessionEnded();
            return;
        }
        if (!res.winners || !res.winners.length) return;
        const winners = res.winners;
        const latest  = winners[winners.length - 1];
        if (latest.round > lastRoundSeen) {
            lastRoundSeen = latest.round;
            // Only animate + modal if this device didn't trigger it
            if (latest.round !== localDrawRound) {
                const snap = [...rafflePool];
                animateDraw(latest, snap);
                loadPool();
                setTimeout(() => showWinnerModal(latest), 4200);
            }
            renderWinnerList(winners);
            bumpBadge();
        }
    } catch(e) {}
}

let sessionEndedShown = false;
function showSessionEnded() {
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
                The draw session has expired.<br>Ask the organizer to share a new link.
            </div>
        </div>
    `;
    document.body.appendChild(overlay);
}

async function loadWinners() {
    try {
        const res = await fetch(ROUTES.winners).then(r => r.json());
        if (!res.success || !res.winners || !res.winners.length) return;
        const winners = res.winners;
        lastRoundSeen = winners[winners.length - 1].round;
        winnerCount   = winners.length;
        renderWinnerList(winners);
        const b = document.getElementById('sidebar-badge');
        b.textContent   = winnerCount;
        b.style.display = 'inline-block';
    } catch(e) {}
}

// ─── Winner modal ──────────────────────────────────────────────────────────

function showWinnerModal(w) {
    const av = document.getElementById('winner-avatar');
    av.innerHTML = w.photo
        ? `<img src="${w.photo}" style="width:100%;height:100%;object-fit:cover;">`
        : initials(w.name);
    document.getElementById('modal-winner-name').textContent  = w.name;
    document.getElementById('modal-winner-prize').textContent = w.prize_label ? '🎁 ' + w.prize_label : '';
    document.getElementById('modal-winner-round').textContent = `Round #${w.round}`;
    const box = document.getElementById('winner-modal-box');
    box.style.animation = 'none'; box.offsetHeight; box.style.animation = '';
    document.getElementById('winner-modal').classList.add('show');
    launchConfetti();
}

function closeWinnerModal() {
    document.getElementById('winner-modal').classList.remove('show');
}

// ─── Winner list ───────────────────────────────────────────────────────────

function renderWinnerList(winners) {
    const el = document.getElementById('winner-list');
    if (!winners || !winners.length) {
        el.innerHTML = '<div style="padding:32px 16px;text-align:center;color:rgba(255,255,255,0.2);font-size:0.82rem;font-weight:700;">No draws yet! 🎲</div>';
        return;
    }
    el.innerHTML = [...winners].reverse().map(w => `
        <div class="w-row">
            <div class="w-avatar">
                ${w.photo ? `<img src="${w.photo}" style="width:100%;height:100%;object-fit:cover;">` : initials(w.name)}
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:0.85rem;font-weight:900;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${w.name}</div>
                <div style="font-size:0.68rem;color:rgba(255,255,255,0.3);font-weight:700;">${w.prize_label?`<span style="color:var(--orange);">${w.prize_label}</span> · `:'' }${w.drawn_at}</div>
            </div>
            <div class="w-badge">#${w.round}</div>
        </div>
    `).join('');
}

// ─── Confetti ──────────────────────────────────────────────────────────────

function launchConfetti() {
    const colors = ['#FF2D55','#FF9500','#FFD60A','#0A84FF','#FFFFFF'];
    const cont   = document.getElementById('cf-container');
    for (let i = 0; i < 100; i++) {
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

async function startDraw() {
    if (isAnimating) return;

    if (rafflePool.length === 0) {
        alert('No eligible attendees in the pool!');
        return;
    }

    isAnimating = true;
    const btn = document.getElementById('draw-btn');
    btn.disabled = true;
    btn.textContent = '⏳ Drawing…';

    const prizeLabel = document.getElementById('prize-label').value.trim();

    // Pick winner from current pool
    const winnerIdx  = Math.floor(Math.random() * rafflePool.length);
    const winner     = rafflePool[winnerIdx];
    const snap       = [...rafflePool];

    // Animate immediately
    animateDraw(winner, snap);

    // Save to server after animation finishes
    setTimeout(async () => {
        const res = await fetch(ROUTES.recordWinner, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json' },
            body: JSON.stringify({ citizenId: winner.citizenId, prize_label: prizeLabel, pin: PIN }),
        }).then(r => r.json()).catch(() => ({ success: false, message: 'Network error.' }));

        btn.disabled = false;
        btn.textContent = '🎰 Draw!';

        if (!res.success) {
            alert(res.message);
            return;
        }

        localDrawRound = res.round; // mark so poll doesn't double-animate
        lastRoundSeen  = res.round;

        document.getElementById('prize-label').value = '';
        winner.round       = res.round;
        winner.prize_label = prizeLabel || null;
        winner.drawn_at    = new Date().toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit', hour12: true });

        showWinnerModal(winner);
        loadPool();
        loadWinners();
    }, 4200);
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
