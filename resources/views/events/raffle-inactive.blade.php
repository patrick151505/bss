<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $event->title }} — Draw Inactive</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            height: 100%; overflow: hidden;
            font-family: 'Nunito', sans-serif;
            background: #1C0050; color: #fff;
            display: flex; align-items: center; justify-content: center;
        }

        /* floating bubbles */
        #bg { position: fixed; inset: 0; z-index: 0; overflow: hidden; }
        .bubble {
            position: absolute; border-radius: 50%; opacity: 0.12;
            animation: float-up linear infinite;
        }
        @keyframes float-up {
            0%   { transform: translateY(110vh); }
            100% { transform: translateY(-15vh); }
        }

        .star-bg {
            position: absolute;
            animation: twinkle ease-in-out infinite alternate;
        }
        @keyframes twinkle {
            from { opacity: 0.1; transform: scale(0.85); }
            to   { opacity: 0.3; transform: scale(1.1); }
        }

        /* card */
        #card {
            position: relative; z-index: 1;
            background: #2A0072;
            border: 4px solid #FFD60A;
            border-radius: 28px;
            padding: 0;
            max-width: 420px; width: 92%;
            box-shadow: 0 8px 0 #b8a000, 0 24px 48px rgba(0,0,0,0.6);
            text-align: center;
            overflow: hidden;
            animation: pop-in 0.45s cubic-bezier(.175,.885,.32,1.4) forwards;
        }
        @keyframes pop-in {
            from { transform: scale(0.7); opacity: 0; }
            to   { transform: scale(1);   opacity: 1; }
        }

        /* coloured stripe */
        #stripe { display: flex; height: 8px; }
        #stripe span { flex: 1; }

        #card-body { padding: 32px 32px 36px; }

        #icon {
            font-size: 4.5rem; line-height: 1;
            margin-bottom: 16px;
            display: block;
            animation: bob 2s ease-in-out infinite alternate;
        }
        @keyframes bob {
            from { transform: translateY(0); }
            to   { transform: translateY(-8px); }
        }

        #event-name {
            font-size: 0.72rem; font-weight: 800;
            color: rgba(255,255,255,0.4);
            letter-spacing: 0.12em; text-transform: uppercase;
            margin-bottom: 6px;
        }

        #headline {
            font-size: 1.6rem; font-weight: 900;
            color: #FFD60A;
            line-height: 1.2; margin-bottom: 10px;
        }

        #subtext {
            font-size: 0.9rem; font-weight: 700;
            color: rgba(255,255,255,0.45);
            line-height: 1.5;
        }

        #divider {
            margin: 20px auto;
            width: 48px; height: 4px;
            background: #FF9500;
            border-radius: 99px;
        }

        #hint {
            font-size: 0.78rem; font-weight: 700;
            color: rgba(255,255,255,0.25);
        }

        /* auto-refresh countdown */
        #countdown {
            display: inline-block;
            margin-top: 20px;
            background: #0A84FF;
            border-radius: 99px;
            padding: 6px 18px;
            font-size: 0.78rem; font-weight: 900;
            color: #fff; letter-spacing: 0.04em;
        }
    </style>
</head>
<body>

<div id="bg" aria-hidden="true"></div>

<div id="card">
    <div id="stripe">
        <span style="background:#FF2D55"></span>
        <span style="background:#FF9500"></span>
        <span style="background:#FFD60A"></span>
        <span style="background:#0A84FF"></span>
    </div>
    <div id="card-body">

        @if($reason === 'expired')
            <span id="icon">⏰</span>
            <div id="event-name">{{ $event->title }}</div>
            <div id="headline">Session Expired</div>
            <div id="subtext">The draw session has ended.<br>Ask the organizer to generate a new PIN.</div>

        @elseif($reason === 'invalid_pin')
            <span id="icon">🔐</span>
            <div id="event-name">{{ $event->title }}</div>
            <div id="headline">Invalid PIN</div>
            <div id="subtext">This link is not valid.<br>Make sure you have the correct link from the organizer.</div>

        @else
            <span id="icon">🎰</span>
            <div id="event-name">{{ $event->title }}</div>
            <div id="headline">No Raffle Yet</div>
            <div id="subtext">The lucky draw hasn't started for this event.<br>Stay tuned!</div>
        @endif

        <div id="divider"></div>
        <div id="hint">This page will check again automatically.</div>
        <div id="countdown">Checking in <span id="sec">30</span>s…</div>

    </div>
</div>

<script>
// background bubbles
(function() {
    const bg     = document.getElementById('bg');
    const colors = ['#FF2D55','#FF9500','#FFD60A','#0A84FF'];
    for (let i = 0; i < 14; i++) {
        const b  = document.createElement('div');
        b.className = 'bubble';
        const sz = 30 + Math.random() * 80;
        b.style.cssText = `width:${sz}px;height:${sz}px;left:${Math.random()*100}%;background:${colors[i%colors.length]};animation-duration:${9+Math.random()*12}s;animation-delay:-${Math.random()*12}s;`;
        bg.appendChild(b);
    }
    const emojis = ['⭐','🌟','✨','💫'];
    for (let i = 0; i < 16; i++) {
        const s = document.createElement('div');
        s.className = 'star-bg';
        s.textContent = emojis[i % emojis.length];
        s.style.cssText = `left:${Math.random()*100}%;top:${Math.random()*100}%;font-size:${0.8+Math.random()*1.2}rem;animation-duration:${1.5+Math.random()*2.5}s;animation-delay:-${Math.random()*2.5}s;`;
        bg.appendChild(s);
    }
})();

// countdown + auto-refresh
let sec = 30;
const el = document.getElementById('sec');
const t  = setInterval(() => {
    sec--;
    el.textContent = sec;
    if (sec <= 0) { clearInterval(t); location.reload(); }
}, 1000);
</script>

</body>
</html>
