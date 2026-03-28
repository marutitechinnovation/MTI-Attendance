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

            <?php if (!empty($loginBannerMessage)): ?>
                <div id="login-flash-banner" class="login-flash-banner <?= !empty($loginBannerIsError) ? 'login-flash-banner--error' : '' ?>" role="status">
                    <?= esc($loginBannerMessage) ?>
                </div>
            <?php endif; ?>

            <form id="login-form" class="card">
                <label for="username">Username</label>
                <input id="username" name="username" type="text" maxlength="50" required autocomplete="username">

                <label for="password">Password</label>
                <input id="password" name="password" type="password" maxlength="64" required autocomplete="current-password">

                <button id="login-btn" type="submit">Sign In</button>
                <p id="login-error" class="error login-error-msg hidden" aria-live="polite"></p>
            </form>

            <p class="admin-login-hint">Administrator?
                <a href="<?= esc($adminLoginUrl ?? base_url('admin/login')) ?>">Open admin login</a>
            </p>
        </div>
    </section>

    <section id="screen-main" class="screen">
        <header class="topbar">
            <div class="topbar-left">
                <div class="topbar-logo">M</div>
                <div class="topbar-titles">
                    <span class="topbar-app-name"><?= esc($shortAppName ?? 'MTI Employee') ?></span>
                    <h2 id="top-title">Dashboard</h2>
                </div>
            </div>
            <div class="topbar-actions">
                <button id="install-btn" class="topbar-icon-btn hidden" type="button" title="Install App">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                </button>
            </div>
        </header>

        <div id="ptr-indicator" class="ptr-indicator" aria-hidden="true">
            <svg id="ptr-icon" class="ptr-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.5"/>
            </svg>
        </div>
        <main class="content">
            <section id="tab-dashboard" class="tab active">
                <div class="panel">
                    <div class="hello-row">
                        <div class="hello-text">
                            <h3 id="hello-name">Hello</h3>
                            <p id="hello-meta">Today summary</p>
                        </div>
                        <span id="sum-status-badge" class="status-badge not-in">
                            <span class="status-dot"></span>
                            <span id="sum-status"><?= esc($statStatusNotIn ?? 'Not Checked In') ?></span>
                        </span>
                    </div>
                    <div class="summary-grid">
                        <div class="summary-item"><span><?= esc($statLabels['check_in'] ?? 'Check In') ?></span><strong id="sum-in">--:--</strong></div>
                        <div class="summary-item"><span><?= esc($statLabels['break_start'] ?? 'Break Start') ?></span><strong id="sum-bs">--:--</strong></div>
                        <div class="summary-item"><span><?= esc($statLabels['break_end'] ?? 'Break End') ?></span><strong id="sum-be">--:--</strong></div>
                        <div class="summary-item"><span><?= esc($statLabels['check_out'] ?? 'Check Out') ?></span><strong id="sum-out">--:--</strong></div>
                    </div>
                    <div class="work-metrics">
                        <div class="metric"><span>Worked</span><strong id="sum-worked">--</strong></div>
                        <div class="metric"><span>Break</span><strong id="sum-break">--</strong></div>
                        <div class="metric"><span>Net Hours</span><strong id="sum-net">--</strong></div>
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
                    <div class="cal-header">
                        <button id="cal-prev-btn" type="button" class="cal-nav-btn">&#8249;</button>
                        <span id="cal-month-label">Loading…</span>
                        <button id="cal-next-btn" type="button" class="cal-nav-btn">&#8250;</button>
                    </div>
                    <div id="cal-grid"></div>
                    <div class="cal-legend">
                        <span class="cal-legend-item"><span class="cal-dot present"></span> Present</span>
                        <span class="cal-legend-item"><span class="cal-dot absent"></span> Absent</span>
                        <span class="cal-legend-item"><span class="cal-dot holiday"></span> Holiday</span>
                        <span class="cal-legend-item"><span class="cal-dot flagged"></span> Flagged</span>
                        <span class="cal-legend-item"><span class="cal-dot weekend"></span> Weekend</span>
                    </div>
                </div>
                <div class="panel">
                    <h4>Holidays</h4>
                    <ul id="holiday-list" class="list"></ul>
                </div>
            </section>

            <section id="tab-profile" class="tab">
                <div class="panel">
                    <div id="profile-avatar" class="profile-avatar">?</div>
                    <dl class="kv">
                        <dt>Name</dt><dd id="p-name">-</dd>
                        <dt>Employee Code</dt><dd id="p-code">-</dd>
                        <dt>Department</dt><dd id="p-dept">-</dd>
                        <dt>Designation</dt><dd id="p-desig">-</dd>
                        <dt>Email</dt><dd id="p-email">-</dd>
                    </dl>
                    <button id="logout-btn" class="danger" style="margin-top:16px" type="button">Log Out</button>
                </div>
            </section>
        </main>

        <nav class="bottom-nav">
            <button class="nav-btn active" data-tab="dashboard" type="button">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                </span>
                <span class="nav-label">Dashboard</span>
            </button>
            <button class="nav-btn" data-tab="attendance" type="button">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                </span>
                <span class="nav-label">Attendance</span>
            </button>
            <button class="nav-btn" data-tab="calendar" type="button">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </span>
                <span class="nav-label">Calendar</span>
            </button>
            <button class="nav-btn" data-tab="profile" type="button">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </span>
                <span class="nav-label">Profile</span>
            </button>
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

<script src="<?= base_url('assets/js/html5-qrcode.min.js') ?>"></script>
<script src="<?= base_url('assets/js/employee-pwa.js') ?>?v=<?= filemtime(FCPATH . 'assets/js/employee-pwa.js') ?>"></script>
</body>
</html>
