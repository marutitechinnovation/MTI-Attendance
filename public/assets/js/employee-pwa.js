(function () {
    const app = document.getElementById("app");
    if (!app) return;

    const baseUrl = app.dataset.baseUrl || "";

    const DEFAULT_SCAN_LABELS = {
        check_in: "Check In",
        break_start: "Break Start",
        break_end: "Break End",
        check_out: "Check Out",
    };
    const DEFAULT_STATUS_LABELS = {
        working: "Working",
        on_break: "On Break",
        complete: "Shift Complete",
        not_in: "Not Checked In",
    };
    let pwaConfigRaw = {};
    try {
        pwaConfigRaw = JSON.parse(app.dataset.pwaConfig || "{}");
    } catch (_) {
        pwaConfigRaw = {};
    }
    const scanLabels = { ...DEFAULT_SCAN_LABELS, ...(pwaConfigRaw.scanLabels || {}) };
    const statusLabels = { ...DEFAULT_STATUS_LABELS, ...(pwaConfigRaw.statusLabels || {}) };
    const storageKey = "mti_employee_session_v1";
    let deferredPrompt = null;
    let qrScanner = null;
    let scannerRunning = false;
    let scanLocked = false;
    let attendanceSubtab = "scan";

    const state = {
        session: loadSession(),
    };

    const routeToTab = {
        "/employee": "dashboard",
        "/employee/dashboard": "dashboard",
        "/employee/attendance": "attendance",
        "/employee/calendar": "calendar",
        "/employee/profile": "profile",
    };

    function loadSession() {
        try {
            return JSON.parse(localStorage.getItem(storageKey) || "null");
        } catch (_) {
            return null;
        }
    }

    function saveSession(payload) {
        localStorage.setItem(storageKey, JSON.stringify(payload));
        state.session = payload;
    }

    function clearSession() {
        localStorage.removeItem(storageKey);
        state.session = null;
    }

    function api(path, options) {
        return fetch(`${baseUrl}/api${path}`, {
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
            },
            ...options,
        }).then(async (res) => {
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                throw new Error(data.message || data.messages?.error || "Request failed");
            }
            return data;
        }).catch((error) => {
            if (!navigator.onLine) {
                throw new Error("No internet connection.");
            }
            throw error;
        });
    }

    function setScreen(screen) {
        document.getElementById("screen-login").classList.toggle("active", screen === "login");
        document.getElementById("screen-main").classList.toggle("active", screen === "main");
    }

    function setTab(tabName) {
        document.querySelectorAll(".tab").forEach((tab) => {
            tab.classList.toggle("active", tab.id === `tab-${tabName}`);
        });
        document.querySelectorAll(".nav-btn").forEach((btn) => {
            btn.classList.toggle("active", btn.dataset.tab === tabName);
        });
        const title = tabName[0].toUpperCase() + tabName.slice(1);
        document.getElementById("top-title").textContent = title;
        const targetPath = `/employee/${tabName}`;
        if (window.location.pathname !== targetPath) {
            window.history.replaceState({}, "", targetPath);
        }
        if (tabName !== "attendance" && scannerRunning) {
            stopScanner();
        }
    }

    function tabFromRoute() {
        const p = window.location.pathname;
        return routeToTab[p] || "dashboard";
    }

    function setAttendanceSubtab(name) {
        attendanceSubtab = name;
        document.querySelectorAll(".attendance-subtab").forEach((el) => {
            el.classList.toggle("active", el.id === `attendance-subtab-${name}`);
        });
        document.querySelectorAll(".attendance-subtab-btn").forEach((btn) => {
            btn.classList.toggle("active", btn.dataset.attSubtab === name);
        });

        if (name !== "scan" && scannerRunning) {
            stopScanner();
        }
    }

    function setOfflineBanner(offline) {
        const banner = document.getElementById("offline-banner");
        if (!banner) return;
        banner.classList.toggle("hidden", !offline);
    }

    function setCameraHelp(message) {
        const help = document.getElementById("camera-help");
        if (!help) return;
        help.textContent = message || "";
        help.classList.toggle("hidden", !message);
    }

    function fmtDateTime(raw) {
        const date = new Date(raw);
        if (Number.isNaN(date.getTime())) return raw || "-";
        return date.toLocaleString();
    }

    function fmtTime(raw) {
        const d = new Date(raw);
        if (Number.isNaN(d.getTime())) return "--:--";
        return d.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
    }

    function minsToText(totalMins) {
        if (totalMins == null || totalMins < 0) return "--";
        const h = Math.floor(totalMins / 60);
        const m = totalMins % 60;
        if (h <= 0) return `${m}m`;
        return `${h}h ${m}m`;
    }

    function apiDate(d) {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, "0");
        const day = String(d.getDate()).padStart(2, "0");
        return `${y}-${m}-${day}`;
    }

    function dateLabel(raw) {
        const d = new Date(raw);
        if (Number.isNaN(d.getTime())) return raw;
        return d.toLocaleDateString(undefined, {
            weekday: "short",
            day: "2-digit",
            month: "short",
            year: "numeric",
        });
    }

    function getTodayRecords() {
        const list = document.getElementById("today-list");
        const recordsJson = list.dataset.records || "[]";
        try {
            return JSON.parse(recordsJson);
        } catch (_) {
            return [];
        }
    }

    function renderTodaySummary(records) {
        const checkIn = records.find((r) => r.type === "check_in");
        const breakStart = records.find((r) => r.type === "break_start");
        const breakEnd = records.find((r) => r.type === "break_end");
        const checkOut = [...records].reverse().find((r) => r.type === "check_out");

        document.getElementById("sum-in").textContent = checkIn ? fmtTime(checkIn.scanned_at) : "--:--";
        document.getElementById("sum-bs").textContent = breakStart ? fmtTime(breakStart.scanned_at) : "--:--";
        document.getElementById("sum-be").textContent = breakEnd ? fmtTime(breakEnd.scanned_at) : "--:--";
        document.getElementById("sum-out").textContent = checkOut ? fmtTime(checkOut.scanned_at) : "--:--";

        const inDate = checkIn ? new Date(checkIn.scanned_at) : null;
        const endDate = checkOut ? new Date(checkOut.scanned_at) : new Date();
        let workedMins = null;
        if (inDate && !Number.isNaN(inDate.getTime())) {
            workedMins = Math.max(0, Math.round((endDate - inDate) / 60000));
        }

        let breakMins = 0;
        let openBreakStart = null;
        records.forEach((r) => {
            const d = new Date(r.scanned_at);
            if (Number.isNaN(d.getTime())) return;
            if (r.type === "break_start") {
                openBreakStart = d;
            } else if (r.type === "break_end" && openBreakStart) {
                breakMins += Math.max(0, Math.round((d - openBreakStart) / 60000));
                openBreakStart = null;
            }
        });
        if (openBreakStart) {
            breakMins += Math.max(0, Math.round((new Date() - openBreakStart) / 60000));
        }

        const netWorked = workedMins == null ? null : Math.max(0, workedMins - breakMins);
        document.getElementById("sum-worked").textContent = minsToText(netWorked);
        document.getElementById("sum-break").textContent = minsToText(breakMins);

        const last = records.length ? records[records.length - 1] : null;
        let status = statusLabels.not_in;
        if (last?.type === "check_in" || last?.type === "break_end") status = statusLabels.working;
        if (last?.type === "break_start") status = statusLabels.on_break;
        if (last?.type === "check_out") status = statusLabels.complete;
        document.getElementById("sum-status").textContent = status;
    }

    function scanLabel(type) {
        return scanLabels[type] || type;
    }

    function statusFromLastType(type) {
        if (type === "check_in" || type === "break_end") return { label: statusLabels.working, cls: "working" };
        if (type === "break_start") return { label: statusLabels.on_break, cls: "break" };
        if (type === "check_out") return { label: statusLabels.complete, cls: "complete" };
        return { label: statusLabels.not_in, cls: "" };
    }

    function renderNextAction(records) {
        const strip = document.getElementById("next-action-strip");
        const text = document.getElementById("next-action-text");
        if (!strip || !text) return;
        strip.classList.remove("warning", "info", "danger");

        const last = records.length ? records[records.length - 1] : null;
        const next = nextScanType(last?.type);
        text.textContent = `Next: ${scanLabel(next)}`;

        if (next === "break_start") strip.classList.add("warning");
        else if (next === "break_end") strip.classList.add("info");
        else if (next === "check_out") strip.classList.add("danger");
    }

    function fillProfile() {
        const d = state.session?.data || {};
        document.getElementById("p-name").textContent = d.name || "-";
        document.getElementById("p-code").textContent = d.employee_code || "-";
        document.getElementById("p-dept").textContent = d.department || "-";
        document.getElementById("p-desig").textContent = d.designation || "-";
        document.getElementById("p-email").textContent = d.email || "-";
        document.getElementById("hello-name").textContent = `Hello, ${d.name || "Employee"}`;
    }

    async function loadToday() {
        if (!state.session?.data?.id) return;
        const body = await api(`/attendance/today?employee_id=${state.session.data.id}`, {
            method: "GET",
        });
        const list = document.getElementById("today-list");
        list.innerHTML = "";

        const records = body.data || [];
        list.dataset.records = JSON.stringify(records);
        document.getElementById("hello-meta").textContent = `${records.length} scan(s) today`;
        renderTodaySummary(records);

        if (!records.length) {
            list.innerHTML = "<li>No scans found for today.</li>";
            renderNextAction(records);
            return;
        }

        records.forEach((rec) => {
            const li = document.createElement("li");
            const label = scanLabel(rec.type);
            const flagged = rec.geofence_status === "flagged";
            li.innerHTML = `
                <div class="timeline-row">
                    <span class="timeline-type ${rec.type}">
                        <span class="timeline-dot"></span>
                        ${label}
                    </span>
                    <span class="timeline-time">${fmtTime(rec.scanned_at)}</span>
                </div>
                ${flagged ? '<div class="timeline-flag">Outside geofence - flagged for review</div>' : ""}
            `;
            list.appendChild(li);
        });
        renderNextAction(records);
    }

    async function loadHolidays() {
        const body = await api("/holidays", { method: "GET" });
        const list = document.getElementById("holiday-list");
        list.innerHTML = "";
        const rows = body.data || [];
        if (!rows.length) {
            list.innerHTML = "<li>No holidays available.</li>";
            return;
        }
        rows.forEach((h) => {
            const li = document.createElement("li");
            li.textContent = `${h.date} - ${h.name}`;
            list.appendChild(li);
        });
    }

    async function loadHistory() {
        if (!state.session?.data?.id) return;
        const now = new Date();
        const first = new Date(now.getFullYear(), now.getMonth(), 1);
        const body = await api(
            `/attendance/history?employee_id=${state.session.data.id}&from=${apiDate(first)}&to=${apiDate(now)}`,
            { method: "GET" }
        );
        const list = document.getElementById("history-list");
        list.innerHTML = "";
        const rows = body.data || [];
        if (!rows.length) {
            list.innerHTML = "<li>No history this month.</li>";
            return;
        }

        const grouped = rows.reduce((acc, row) => {
            const key = row.date || "unknown";
            if (!acc[key]) acc[key] = [];
            acc[key].push(row);
            return acc;
        }, {});

        Object.keys(grouped)
            .sort((a, b) => (a < b ? 1 : -1))
            .forEach((date) => {
                const li = document.createElement("li");
                const items = grouped[date];
                const scans = items.map((r) => `${scanLabel(r.type)} ${fmtTime(r.scanned_at)}`).join(" • ");
                const lastType = items.length ? items[items.length - 1].type : null;
                const flagged = items.some((r) => r.geofence_status === "flagged");
                const status = statusFromLastType(lastType);
                const chipClass = flagged ? "flagged" : status.cls;
                const chipLabel = flagged ? "Flagged" : status.label;
                li.innerHTML = `
                    <div class="history-date">${dateLabel(date)}</div>
                    <div class="history-row">
                        <div class="history-head">
                            <span>${items.length} scans</span>
                            <span class="status-chip ${chipClass}">${chipLabel}</span>
                        </div>
                        <div class="history-meta">${scans}</div>
                    </div>
                `;
                list.appendChild(li);
            });
    }

    async function initAuthedView() {
        setScreen("main");
        fillProfile();
        setTab("dashboard");
        setAttendanceSubtab("scan");
        await Promise.allSettled([loadToday(), loadHolidays(), loadHistory()]);
        initScanner();
    }

    function nextScanType(lastType) {
        const cycle = ["check_in", "break_start", "break_end", "check_out"];
        if (!lastType) return "check_in";
        const i = cycle.indexOf(lastType);
        if (i < 0 || i === cycle.length - 1) return "check_in";
        return cycle[i + 1];
    }

    function showModal(title, text, actions, variant = "") {
        const backdrop = document.getElementById("modal-backdrop");
        const titleEl = document.getElementById("modal-title");
        const textEl = document.getElementById("modal-text");
        const cardEl = backdrop.querySelector(".modal-card");
        const actionsEl = document.getElementById("modal-actions");
        titleEl.textContent = title;
        textEl.textContent = text;
        cardEl.classList.remove("success", "warning");
        if (variant) cardEl.classList.add(variant);
        actionsEl.innerHTML = "";
        backdrop.classList.remove("hidden");

        return new Promise((resolve) => {
            actions.forEach((action) => {
                const btn = document.createElement("button");
                btn.type = "button";
                btn.textContent = action.label;
                if (action.className) btn.className = action.className;
                btn.addEventListener("click", () => {
                    backdrop.classList.add("hidden");
                    resolve(action.value);
                });
                actionsEl.appendChild(btn);
            });
        });
    }

    async function resolveScanType(records) {
        const last = records.length ? records[records.length - 1] : null;
        const next = nextScanType(last?.type);
        if (next === "break_start" || next === "check_out") {
            const pick = await showModal(
                "Choose Action",
                "What do you want to record now?",
                [
                    { label: scanLabels.break_start, value: "break_start", className: "ghost" },
                    { label: scanLabels.check_out, value: "check_out", className: "danger" },
                    { label: "Cancel", value: null, className: "ghost" },
                ]
            );
            return pick;
        }
        const ok = await showModal(
            "Confirm Scan",
            `Record ${scanLabel(next)} now?`,
            [
                { label: "Cancel", value: false, className: "ghost" },
                { label: "Confirm", value: true },
            ]
        );
        return ok ? next : null;
    }

    function isWideViewport() {
        return window.matchMedia("(min-width: 768px)").matches;
    }

    async function onLoginSubmit(e) {
        e.preventDefault();
        const err = document.getElementById("login-error");
        err.textContent = "";
        err.classList.add("hidden");

        const username = document.getElementById("username").value.trim();
        const password = document.getElementById("password").value.trim();
        const btn = document.getElementById("login-btn");
        btn.disabled = true;
        btn.textContent = "Signing in...";

        try {
            if (!navigator.onLine) throw new Error("No internet connection.");
            const body = await api("/auth/login", {
                method: "POST",
                body: JSON.stringify({ username, password }),
            });
            if (body.status !== "success" || !body.data) {
                throw new Error(body.message || "Login failed");
            }
            saveSession(body);
            await initAuthedView();
        } catch (error) {
            const raw = error.message || "Login failed";
            /* Desktop/tablet: avoid loud API-specific errors; phones get full detail. */
            if (isWideViewport()) {
                err.textContent =
                    "Could not sign in. Check your username and password, or use your phone for the best experience.";
                err.classList.remove("hidden");
            } else {
                err.textContent = raw;
                err.classList.remove("hidden");
            }
        } finally {
            btn.disabled = false;
            btn.textContent = "Sign In";
        }
    }

    async function submitScan(token) {
        const msg = document.getElementById("scan-message");
        msg.textContent = "Requesting location...";

        if (!token) {
            msg.textContent = "QR token is required.";
            return false;
        }
        if (!state.session?.data?.id) {
            msg.textContent = "Session expired, please login again.";
            clearSession();
            window.history.replaceState({}, "", "/employee");
            setScreen("login");
            return false;
        }
        if (!navigator.onLine) {
            msg.textContent = "No internet connection.";
            return false;
        }
        if (!navigator.geolocation) {
            msg.textContent = "Geolocation not supported by this browser.";
            return false;
        }

        const records = getTodayRecords();
        const chosenType = await resolveScanType(records);
        if (!chosenType) {
            msg.textContent = "Scan cancelled.";
            return false;
        }

        return new Promise((resolve) => {
            navigator.geolocation.getCurrentPosition(
                async (position) => {
                    try {
                        msg.textContent = "Submitting scan...";
                        const payload = {
                            employee_id: state.session.data.id,
                            qr_token: token,
                            latitude: position.coords.latitude,
                            longitude: position.coords.longitude,
                            scan_type: chosenType,
                        };

                        const body = await api("/attendance/scan", {
                            method: "POST",
                            body: JSON.stringify(payload),
                        });
                        msg.textContent = `${body.label || body.type || "Recorded"} at ${body.time || ""}`;
                        await showModal(
                            body.status === "flagged" ? "Attendance Flagged" : "Attendance Recorded",
                            body.status === "flagged"
                                ? `${body.label || body.type} saved, but outside geofence.`
                                : `${body.label || body.type} saved successfully.`,
                            [{ label: "Done", value: true }],
                            body.status === "flagged" ? "warning" : "success"
                        );
                        await Promise.allSettled([loadToday(), loadHistory()]);
                        setTimeout(() => {
                            scanLocked = false;
                        }, 1200);
                        resolve(true);
                    } catch (error) {
                        msg.textContent = error.message || "Scan failed";
                        await showModal(
                            "Scan Failed",
                            msg.textContent,
                            [{ label: "Try Again", value: true }],
                            "warning"
                        );
                        setTimeout(() => {
                            scanLocked = false;
                        }, 800);
                        resolve(false);
                    }
                },
                (error) => {
                    msg.textContent = `Location error: ${error.message}`;
                    showModal(
                        "Location Error",
                        msg.textContent,
                        [{ label: "OK", value: true }],
                        "warning"
                    );
                    setTimeout(() => {
                        scanLocked = false;
                    }, 800);
                    resolve(false);
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        });
    }

    function initScanner() {
        if (typeof window.Html5Qrcode === "undefined") return;
        if (qrScanner) return;
        qrScanner = new window.Html5Qrcode("qr-reader");
    }

    async function startScanner() {
        if (!qrScanner || scannerRunning) return;
        const startBtn = document.getElementById("start-scan-btn");
        const stopBtn = document.getElementById("stop-scan-btn");
        const msg = document.getElementById("scan-message");
        setCameraHelp("");
        scanLocked = false;
        try {
            await qrScanner.start(
                { facingMode: "environment" },
                { fps: 8, qrbox: { width: 220, height: 220 } },
                async (decodedText) => {
                    if (scanLocked) return;
                    scanLocked = true;
                    await stopScanner();
                    const ok = await submitScan(decodedText);
                    if (attendanceSubtab === "scan" && !scannerRunning) {
                        setTimeout(() => {
                            if (attendanceSubtab === "scan") startScanner();
                        }, ok ? 1200 : 2000);
                    }
                }
            );
            scannerRunning = true;
            msg.textContent = "Scanner running. Point camera at QR code.";
            startBtn.classList.add("hidden");
            stopBtn.classList.remove("hidden");
        } catch (error) {
            msg.textContent = `Unable to start camera: ${error.message || error}`;
            const raw = String(error?.message || error || "");
            if (raw.includes("NotAllowedError") || raw.toLowerCase().includes("permission")) {
                setCameraHelp("Camera permission denied. Allow camera access in browser settings and retry.");
            } else if (raw.includes("NotFoundError")) {
                setCameraHelp("No camera device found on this device.");
            } else if (!navigator.onLine) {
                setCameraHelp("You are offline. Camera may work, but scan submit requires internet.");
            } else {
                setCameraHelp("Camera unavailable. Close other camera apps/tabs and retry.");
            }
        }
    }

    async function stopScanner() {
        if (!qrScanner || !scannerRunning) return;
        const startBtn = document.getElementById("start-scan-btn");
        const stopBtn = document.getElementById("stop-scan-btn");
        try {
            await qrScanner.stop();
        } catch (_) {
        } finally {
            scannerRunning = false;
            startBtn.classList.remove("hidden");
            stopBtn.classList.add("hidden");
            setCameraHelp("");
        }
    }

    function initPwaInstall() {
        const installBtn = document.getElementById("install-btn");
        window.addEventListener("beforeinstallprompt", (e) => {
            e.preventDefault();
            deferredPrompt = e;
            installBtn.classList.remove("hidden");
        });

        installBtn.addEventListener("click", async () => {
            if (!deferredPrompt) return;
            deferredPrompt.prompt();
            await deferredPrompt.userChoice;
            deferredPrompt = null;
            installBtn.classList.add("hidden");
        });

        if ("serviceWorker" in navigator) {
            navigator.serviceWorker.register(`${baseUrl}/sw.js`).then((reg) => {
                reg.addEventListener("updatefound", () => {
                    const worker = reg.installing;
                    if (!worker) return;
                    worker.addEventListener("statechange", () => {
                        if (worker.state === "installed" && navigator.serviceWorker.controller) {
                            showModal(
                                "Update Available",
                                "A newer app version is available. Reload to update now?",
                                [
                                    { label: "Later", value: false, className: "ghost" },
                                    { label: "Reload", value: true },
                                ],
                                "info"
                            ).then((ok) => {
                                if (ok) window.location.reload();
                            });
                        }
                    });
                });
            }).catch(() => {});
        }
    }

    function bindEvents() {
        document.getElementById("login-form").addEventListener("submit", onLoginSubmit);
        document.getElementById("start-scan-btn").addEventListener("click", startScanner);
        document.getElementById("stop-scan-btn").addEventListener("click", stopScanner);
        document.getElementById("logout-btn").addEventListener("click", async () => {
            const ok = await showModal(
                "Log Out",
                "Are you sure you want to log out?",
                [
                    { label: "Cancel", value: false, className: "ghost" },
                    { label: "Log Out", value: true, className: "danger" },
                ],
                "warning"
            );
            if (!ok) return;
            stopScanner();
            clearSession();
            window.history.replaceState({}, "", "/employee");
            setScreen("login");
        });

        document.querySelectorAll(".nav-btn").forEach((btn) => {
            btn.addEventListener("click", async () => {
                setTab(btn.dataset.tab);
                if (btn.dataset.tab === "attendance") {
                    await Promise.allSettled([loadToday(), loadHistory()]);
                    if (!attendanceSubtab) setAttendanceSubtab("scan");
                }
            });
        });

        document.querySelectorAll(".attendance-subtab-btn").forEach((btn) => {
            btn.addEventListener("click", async () => {
                const next = btn.dataset.attSubtab;
                setAttendanceSubtab(next);
                if (next === "history") {
                    await loadHistory();
                }
            });
        });

        window.addEventListener("online", () => setOfflineBanner(false));
        window.addEventListener("offline", () => setOfflineBanner(true));

        document.getElementById("offline-retry-btn").addEventListener("click", async () => {
            setOfflineBanner(!navigator.onLine);
            if (!navigator.onLine || !state.session?.data?.id) return;
            await Promise.allSettled([loadToday(), loadHistory(), loadHolidays()]);
        });
    }

    bindEvents();
    initPwaInstall();
    setOfflineBanner(!navigator.onLine);
    if (state.session?.status === "success" && state.session?.data?.id) {
        initAuthedView().then(() => {
            const initial = tabFromRoute();
            setTab(initial);
        });
    } else {
        window.history.replaceState({}, "", "/employee");
        setScreen("login");
    }
})();
