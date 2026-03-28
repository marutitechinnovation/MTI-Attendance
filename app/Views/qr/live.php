<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex,nofollow">
    <title><?= esc($pageTitle ?? 'Live QR') ?></title>
    <style>
        :root {
            --bg: #0f172a;
            --text: #f1f5f9;
            --muted: #94a3b8;
            --accent: #38bdf8;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100dvh;
            display: grid;
            place-items: center;
            padding: 24px 16px;
            background: var(--bg);
            color: var(--text);
            font-family: system-ui, -apple-system, sans-serif;
        }
        .wrap { width: min(420px, 100%); text-align: center; }
        h1 { font-size: 1.15rem; font-weight: 700; margin: 0 0 6px; line-height: 1.3; }
        .sub { font-size: 13px; color: var(--muted); margin: 0 0 20px; }
        #qr-wrap {
            background: #fff;
            border-radius: 20px;
            padding: 20px;
            display: inline-block;
            box-shadow: 0 24px 48px rgba(0,0,0,.35);
            min-width: 240px;
            min-height: 240px;
            position: relative;
        }
        /* qrcodejs creates a table or canvas inside the div — normalise it */
        #qr-wrap img, #qr-wrap canvas { display: block; max-width: 100%; height: auto; }
        #qr-wrap table { margin: auto; border-collapse: collapse; }
        #qr-wrap td { padding: 0; }
        .status { margin-top: 20px; font-size: 12px; color: var(--muted); }
        .status span { color: var(--accent); font-weight: 600; }
        .err { color: #fca5a5; font-size: 14px; margin-top: 16px; }
        .hidden { display: none !important; }
    </style>
</head>
<body>
<div class="wrap">
    <h1><?= esc($locationName) ?></h1>
    <p class="sub">Scan with the employee attendance app. Code refreshes automatically.</p>
    <div id="qr-wrap"></div>
    <p class="status" id="status-line">Updating in <span id="countdown">—</span>s</p>
    <p class="err hidden" id="err-line"></p>
</div>
<script src="<?= base_url('assets/js/qrcode.min.js') ?>"></script>
<script>
(function () {
    const slug    = <?= json_encode($slug, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const apiUrl  = <?= json_encode(rtrim(base_url(), '/') . '/api/qr/live-token/', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?> + encodeURIComponent(slug);
    const qrWrap      = document.getElementById('qr-wrap');
    const countdownEl = document.getElementById('countdown');
    const errLine     = document.getElementById('err-line');

    let pollMs  = 15000;
    let timer   = null;
    let cdTimer = null;
    let qrObj   = null;

    function showErr(msg) { errLine.textContent = msg; errLine.classList.remove('hidden'); }
    function hideErr()    { errLine.classList.add('hidden'); }

    function drawQr(token) {
        if (typeof QRCode === 'undefined' || !token) {
            showErr('QR library not loaded. Please refresh.');
            return;
        }
        if (qrObj) {
            qrObj.makeCode(token);
        } else {
            qrObj = new QRCode(qrWrap, {
                text:        token,
                width:       280,
                height:      280,
                colorDark:   '#0f172a',
                colorLight:  '#ffffff',
                correctLevel: QRCode.CorrectLevel.M,
            });
        }
    }

    function startCountdown(sec) {
        let rem = Math.max(1, sec);
        countdownEl.textContent = String(rem);
        if (cdTimer) clearInterval(cdTimer);
        cdTimer = setInterval(function () {
            rem -= 1;
            if (rem <= 0) { clearInterval(cdTimer); countdownEl.textContent = '…'; return; }
            countdownEl.textContent = String(rem);
        }, 1000);
    }

    async function fetchToken() {
        hideErr();
        try {
            const res  = await fetch(apiUrl, { cache: 'no-store' });
            const data = await res.json().catch(function () { return {}; });
            if (!res.ok || data.status !== 'success' || !data.token) {
                showErr(data.message || 'Unable to load QR. Check link or ask admin.');
                if (timer) clearTimeout(timer);
                timer = setTimeout(fetchToken, 10000);
                return;
            }
            pollMs = Math.max(5000, Math.min(600000, (data.interval_seconds || 15) * 1000));
            drawQr(data.token);
            startCountdown(Math.round(pollMs / 1000));
            if (timer) clearTimeout(timer);
            timer = setTimeout(fetchToken, pollMs);
        } catch (e) {
            showErr('Network error. Retrying…');
            if (timer) clearTimeout(timer);
            timer = setTimeout(fetchToken, 5000);
        }
    }

    fetchToken();
})();
</script>
</body>
</html>
