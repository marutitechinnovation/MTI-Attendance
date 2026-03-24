<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="<?= esc($themeColor ?? '#1A237E') ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="<?= esc($shortAppName ?? 'MTI Employee') ?>">
    <title><?= esc($pageTitle ?? 'Employee App') ?> - <?= esc($brandName ?? 'MTI Attendance') ?></title>

    <link rel="manifest" href="<?= base_url('manifest.webmanifest') ?>">
    <link rel="icon" href="<?= base_url('assets/icons/icon-192.svg') ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= base_url('assets/css/employee-pwa.css') ?>?v=<?= filemtime(FCPATH . 'assets/css/employee-pwa.css') ?>">
    <style>
        :root {
            --primary: <?= esc($themeColor ?? '#1A237E') ?>;
            --accent: <?= esc($accentColor ?? '#00BCD4') ?>;
            --bg: <?= esc($backgroundColor ?? '#F5F7FB') ?>;
        }
        .login-shell {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary) 50%, var(--accent) 160%);
        }
    </style>
</head>
<body>
<div id="app"
     data-base-url="<?= esc(rtrim(base_url(), '/')) ?>"
     data-pwa-config="<?= esc($pwaConfigJson ?? '{}', 'attr') ?>">
    <div id="offline-banner" class="offline-banner hidden">
        <span>You are offline. Some actions may not work.</span>
        <button id="offline-retry-btn" type="button">Retry</button>
    </div>

    <section id="screen-login" class="screen active">
        <div class="login-shell">
            <div class="login-brand">
                <div class="logo-mark">M</div>
                <h1><?= esc($brandName ?? 'MTI Attendance') ?></h1>
                <p>Sign in with your employee account.</p>
            </div>

            <form id="login-form" class="card">
                <label for="username">Username</label>
                <input id="username" name="username" type="text" maxlength="50" required>

                <label for="password">Password</label>
                <input id="password" name="password" type="password" maxlength="64" required>

                <button id="login-btn" type="submit">Sign In</button>
                <p id="login-error" class="error hidden"></p>
            </form>
        </div>
    </section>

    <section id="screen-main" class="screen">
        <header class="topbar">
            <h2 id="top-title">Dashboard</h2>
            <button id="install-btn" class="ghost hidden" type="button">Install App</button>
        </header>

        <main class="content">
            <section id="tab-dashboard" class="tab active">
                <div class="panel">
                    <h3 id="hello-name">Hello</h3>
                    <p id="hello-meta">Today summary</p>
                    <div class="summary-grid">
                        <div class="summary-item"><span><?= esc($statLabels['check_in'] ?? 'Check In') ?></span><strong id="sum-in">--:--</strong></div>
                        <div class="summary-item"><span><?= esc($statLabels['break_start'] ?? 'Break Start') ?></span><strong id="sum-bs">--:--</strong></div>
                        <div class="summary-item"><span><?= esc($statLabels['break_end'] ?? 'Break End') ?></span><strong id="sum-be">--:--</strong></div>
                        <div class="summary-item"><span><?= esc($statLabels['check_out'] ?? 'Check Out') ?></span><strong id="sum-out">--:--</strong></div>
                    </div>
                    <div class="work-metrics">
                        <div class="metric"><span>Worked</span><strong id="sum-worked">--</strong></div>
                        <div class="metric"><span>Break</span><strong id="sum-break">--</strong></div>
                        <div class="metric"><span>Status</span><strong id="sum-status"><?= esc($statStatusNotIn) ?></strong></div>
                    </div>
                </div>
                <div class="panel">
                    <h4>Today Timeline</h4>
                    <ul id="today-list" class="list"></ul>
                </div>
            </section>

            <section id="tab-attendance" class="tab">
                <div class="attendance-subtabs">
                    <button class="attendance-subtab-btn active" data-att-subtab="scan" type="button">Scan QR</button>
                    <button class="attendance-subtab-btn" data-att-subtab="history" type="button">History</button>
                </div>

                <div id="attendance-subtab-scan" class="attendance-subtab active">
                    <div class="panel">
                        <h4>Mark Attendance</h4>
                        <p class="sub">Scan office QR with camera, then confirm action.</p>
                        <div id="next-action-strip" class="next-action-strip">
                            <span id="next-action-text">Next: Check In</span>
                        </div>
                        <div id="qr-reader" class="qr-reader"></div>
                    <p id="camera-help" class="sub hidden"></p>
                        <div class="scan-actions">
                            <button id="start-scan-btn" class="ghost" type="button">Start Camera Scan</button>
                            <button id="stop-scan-btn" class="ghost hidden" type="button">Stop Scan</button>
                        </div>
                    <p id="scan-message" class="sub">Tap "Start Camera Scan" and scan office QR.</p>
                    </div>
                </div>

                <div id="attendance-subtab-history" class="attendance-subtab">
                    <div class="panel">
                        <h4>This Month History</h4>
                        <ul id="history-list" class="list"></ul>
                    </div>
                </div>
            </section>

            <section id="tab-calendar" class="tab">
                <div class="panel">
                    <h4>Holidays</h4>
                    <ul id="holiday-list" class="list"></ul>
                </div>
            </section>

            <section id="tab-profile" class="tab">
                <div class="panel">
                    <h4>My Profile</h4>
                    <dl class="kv">
                        <dt>Name</dt><dd id="p-name">-</dd>
                        <dt>Employee Code</dt><dd id="p-code">-</dd>
                        <dt>Department</dt><dd id="p-dept">-</dd>
                        <dt>Designation</dt><dd id="p-desig">-</dd>
                        <dt>Email</dt><dd id="p-email">-</dd>
                    </dl>
                    <button id="logout-btn" class="danger" type="button">Log Out</button>
                </div>
            </section>
        </main>

        <nav class="bottom-nav">
            <button class="nav-btn active" data-tab="dashboard" type="button">Dashboard</button>
            <button class="nav-btn" data-tab="attendance" type="button">Attendance</button>
            <button class="nav-btn" data-tab="calendar" type="button">Calendar</button>
            <button class="nav-btn" data-tab="profile" type="button">Profile</button>
        </nav>
    </section>
</div>

<div id="modal-backdrop" class="modal-backdrop hidden">
    <div class="modal-card">
        <h4 id="modal-title">Confirm</h4>
        <p id="modal-text" class="sub">Continue?</p>
        <div id="modal-actions" class="modal-actions"></div>
    </div>
</div>

<script src="<?= base_url('assets/js/employee-pwa.js') ?>?v=<?= filemtime(FCPATH . 'assets/js/employee-pwa.js') ?>"></script>
<script src="https://unpkg.com/html5-qrcode"></script>
</body>
</html>
