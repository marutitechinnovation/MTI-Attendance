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
    const scanLabels   = { ...DEFAULT_SCAN_LABELS,   ...(pwaConfigRaw.scanLabels   || {}) };
    const statusLabels = { ...DEFAULT_STATUS_LABELS, ...(pwaConfigRaw.statusLabels || {}) };

    const storageKey   = "mti_employee_session_v1";
    let deferredPrompt = null;
    let qrScanner      = null;
    let scannerRunning = false;
    let scanLocked     = false;
    let attendanceSubtab = "scan";
    let holidaysCache  = null;
    let calendarDate   = new Date();

    const state = { session: loadSession() };

    // ─── Offline Scan Queue (IndexedDB) ──────────────────────────────────────
    const OFFLINE_DB_NAME = "mti_offline";
    const OFFLINE_STORE   = "scan_queue";

    function openOfflineDB() {
        return new Promise((resolve, reject) => {
            const req = indexedDB.open(OFFLINE_DB_NAME, 1);
            req.onupgradeneeded = (e) => {
                e.target.result.createObjectStore(OFFLINE_STORE, { keyPath: "id", autoIncrement: true });
            };
            req.onsuccess = (e) => resolve(e.target.result);
            req.onerror   = () => reject(req.error);
        });
    }

    async function queueOfflineScan(payload) {
        const db = await openOfflineDB();
        await new Promise((resolve, reject) => {
            const tx = db.transaction(OFFLINE_STORE, "readwrite");
            tx.objectStore(OFFLINE_STORE).add({
                payload,
                token:     state.session?.token || null,
                queued_at: new Date().toISOString(),
            });
            tx.oncomplete = resolve;
            tx.onerror    = () => reject(tx.error);
        });
        if ("serviceWorker" in navigator && "SyncManager" in window) {
            try {
                const reg = await navigator.serviceWorker.ready;
                await reg.sync.register("sync-attendance-scans");
            } catch (_) {}
        }
    }

    async function getQueuedScans() {
        const db = await openOfflineDB();
        return new Promise((resolve, reject) => {
            const tx  = db.transaction(OFFLINE_STORE, "readonly");
            const req = tx.objectStore(OFFLINE_STORE).getAll();
            req.onsuccess = () => resolve(req.result);
            req.onerror   = () => reject(req.error);
        });
    }

    async function deleteQueuedScan(id) {
        const db = await openOfflineDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(OFFLINE_STORE, "readwrite");
            tx.objectStore(OFFLINE_STORE).delete(id);
            tx.oncomplete = resolve;
            tx.onerror    = () => reject(tx.error);
        });
    }

    async function processOfflineQueue() {
        if (!navigator.onLine || !state.session?.data?.id) return;
        let queued;
        try { queued = await getQueuedScans(); } catch (_) { return; }
        if (!queued.length) return;

        let processed = 0;
        for (const item of queued) {
            try {
                await api("/attendance/scan", {
                    method: "POST",
                    body: JSON.stringify(item.payload),
                });
                await deleteQueuedScan(item.id);
                processed++;
            } catch (err) {
                if (err.status === 401) break;
            }
        }
        if (processed > 0) {
            await Promise.allSettled([loadToday(), loadHistory()]);
            await showModal(
                "Scans Synced",
                `${processed} offline scan(s) submitted successfully.`,
                [{ label: "OK", value: true }],
                "success"
            );
        }
    }
    // ─────────────────────────────────────────────────────────────────────────

    const routeToTab = {
        "/employee":            "dashboard",
        "/employee/dashboard":  "dashboard",
        "/employee/attendance": "attendance",
        "/employee/calendar":   "calendar",
        "/employee/profile":    "profile",
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
        const token = state.session?.token;
        const headers = {
            "Content-Type": "application/json",
            Accept: "application/json",
        };
        if (token) headers["Authorization"] = `Bearer ${token}`;
        return fetch(`${baseUrl}/api${path}`, {
            headers,
            ...options,
        }).then(async (res) => {
            const data = await res.json().catch(() => ({}));
            if (res.status === 401) {
                clearSession();
                stopScanner();
                window.history.replaceState({}, "", "/employee");
                setScreen("login");
                const err = new Error("Session expired. Please log in again.");
                err.status = 401;
                throw err;
            }
            if (!res.ok) {
                const err = new Error(extractApiErrorMessage(data) || "Request failed");
                err.status  = res.status;
                err.payload = data;
                throw err;
            }
            return data;
        }).catch((error) => {
            if (!navigator.onLine) throw new Error("No internet connection.");
            throw error;
        });
    }

    function extractApiErrorMessage(payload) {
        if (!payload || typeof payload !== "object") return "";
        if (typeof payload.message === "string" && payload.message.trim()) return payload.message.trim();
        const errorMsg = payload.messages?.error;
        if (typeof errorMsg === "string" && errorMsg.trim()) return errorMsg.trim();
        const messages = payload.messages;
        if (messages && typeof messages === "object") {
            const parts = [];
            Object.values(messages).forEach((v) => {
                if (typeof v === "string" && v.trim()) parts.push(v.trim());
                else if (Array.isArray(v)) v.forEach((x) => (typeof x === "string" && x.trim() ? parts.push(x.trim()) : null));
            });
            if (parts.length) return parts[0];
        }
        return "";
    }

    function friendlyError(error, fallback = "Something went wrong. Please try again.") {
        if (!error) return fallback;
        if (!navigator.onLine) return "No internet connection.";
        const msg    = String(error.message || "").trim();
        const status = error.status;
        if (status === 401 || status === 403) return msg || "Authentication failed. Please log in again.";
        if (status === 404) return "Service not found. Please contact admin.";
        if (status === 422) return msg || "Please check the details and try again.";
        if (status >= 500) return "Server error. Please try again after some time.";
        if (msg) return msg;
        return fallback;
    }

    function setScreen(screen) {
        document.getElementById("screen-login").classList.toggle("active", screen === "login");
        document.getElementById("screen-main").classList.toggle("active", screen === "main");
    }

    function setTab(tabName) {
        const nextId = `tab-${tabName}`;
        document.querySelectorAll(".tab").forEach((tab) => {
            const isNext = tab.id === nextId;
            if (isNext) return;
            if (tab.classList.contains("active")) {
                tab.classList.add("is-leaving");
                tab.classList.remove("active");
                window.setTimeout(() => tab.classList.remove("is-leaving"), 180);
            } else {
                tab.classList.remove("is-leaving");
            }
        });
        const nextTab = document.getElementById(nextId);
        if (nextTab) {
            nextTab.classList.remove("is-leaving");
            nextTab.classList.add("active");
        }
        document.querySelectorAll(".nav-btn").forEach((btn) => {
            const isActive = btn.dataset.tab === tabName;
            btn.classList.toggle("active", isActive);
            btn.setAttribute("aria-current", isActive ? "page" : "false");
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
        const nextId = `attendance-subtab-${name}`;
        document.querySelectorAll(".attendance-subtab").forEach((el) => {
            const isNext = el.id === nextId;
            if (isNext) return;
            if (el.classList.contains("active")) {
                el.classList.add("is-leaving");
                el.classList.remove("active");
                window.setTimeout(() => el.classList.remove("is-leaving"), 180);
            } else {
                el.classList.remove("is-leaving");
            }
        });
        const next = document.getElementById(nextId);
        if (next) {
            next.classList.remove("is-leaving");
            next.classList.add("active");
        }
        document.querySelectorAll(".attendance-subtab-btn").forEach((btn) => {
            btn.classList.toggle("active", btn.dataset.attSubtab === name);
        });
        if (name !== "scan" && scannerRunning) stopScanner();
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
        return date.toLocaleString(undefined, {
            year: "numeric", month: "short", day: "2-digit",
            hour: "2-digit", minute: "2-digit", hour12: true,
        });
    }

    function fmtTime(raw) {
        const d = new Date(raw);
        if (Number.isNaN(d.getTime())) return "--:--";
        return d.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit", hour12: true });
    }

    function minsToText(totalMins) {
        if (totalMins == null || totalMins < 0) return "--";
        const h = Math.floor(totalMins / 60);
        const m = totalMins % 60;
        if (h <= 0) return `${m}m`;
        return `${h}h ${m}m`;
    }

    function apiDate(d) {
        const y   = d.getFullYear();
        const m   = String(d.getMonth() + 1).padStart(2, "0");
        const day = String(d.getDate()).padStart(2, "0");
        return `${y}-${m}-${day}`;
    }

    function dateLabel(raw) {
        const d = new Date(raw);
        if (Number.isNaN(d.getTime())) return raw;
        return d.toLocaleDateString(undefined, {
            weekday: "short", day: "2-digit", month: "short", year: "numeric",
        });
    }

    function getTodayRecords() {
        const list = document.getElementById("today-list");
        const recordsJson = list.dataset.records || "[]";
        try { return JSON.parse(recordsJson); } catch (_) { return []; }
    }

    function renderTodaySummary(records) {
        const checkIn    = records.find((r) => r.type === "check_in");
        const breakStart = records.find((r) => r.type === "break_start");
        const breakEnd   = records.find((r) => r.type === "break_end");
        const checkOut   = [...records].reverse().find((r) => r.type === "check_out");

        document.getElementById("sum-in").textContent  = checkIn    ? fmtTime(checkIn.scanned_at)    : "--:--";
        document.getElementById("sum-bs").textContent  = breakStart ? fmtTime(breakStart.scanned_at) : "--:--";
        document.getElementById("sum-be").textContent  = breakEnd   ? fmtTime(breakEnd.scanned_at)   : "--:--";
        document.getElementById("sum-out").textContent = checkOut   ? fmtTime(checkOut.scanned_at)   : "--:--";

        const inDate  = checkIn ? new Date(checkIn.scanned_at) : null;
        const endDate = checkOut ? new Date(checkOut.scanned_at) : new Date();
        let workedMins = null;
        if (inDate && !Number.isNaN(inDate.getTime())) {
            workedMins = Math.max(0, Math.round((endDate - inDate) / 60000));
        }

        let breakMins = 0, openBreakStart = null;
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
        if (openBreakStart) breakMins += Math.max(0, Math.round((new Date() - openBreakStart) / 60000));

        const netWorked = workedMins == null ? null : Math.max(0, workedMins - breakMins);
        document.getElementById("sum-worked").textContent = minsToText(workedMins);
        document.getElementById("sum-break").textContent  = minsToText(breakMins);
        const netEl = document.getElementById("sum-net");
        if (netEl) netEl.textContent = minsToText(netWorked);

        const last   = records.length ? records[records.length - 1] : null;
        let status   = statusLabels.not_in;
        let badgeCls = "not-in";
        if (last?.type === "check_in"   || last?.type === "break_end")  { status = statusLabels.working;  badgeCls = ""; }
        if (last?.type === "break_start")                                { status = statusLabels.on_break; badgeCls = "break"; }
        if (last?.type === "check_out")                                  { status = statusLabels.complete; badgeCls = "complete"; }
        document.getElementById("sum-status").textContent = status;
        const badge = document.getElementById("sum-status-badge");
        if (badge) { badge.className = `status-badge ${badgeCls}`; }
    }

    function scanLabel(type) { return scanLabels[type] || type; }

    function statusFromLastType(type) {
        if (type === "check_in" || type === "break_end") return { label: statusLabels.working,  cls: "working" };
        if (type === "break_start")                      return { label: statusLabels.on_break, cls: "break" };
        if (type === "check_out")                        return { label: statusLabels.complete, cls: "complete" };
        return { label: statusLabels.not_in, cls: "" };
    }

    function renderNextAction(records) {
        const strip = document.getElementById("next-action-strip");
        const text  = document.getElementById("next-action-text");
        if (!strip || !text) return;
        strip.classList.remove("warning", "info", "danger");
        const last = records.length ? records[records.length - 1] : null;
        const next = nextScanType(last?.type);
        text.textContent = `Next: ${scanLabel(next)}`;
        if (next === "break_start") strip.classList.add("warning");
        else if (next === "break_end")  strip.classList.add("info");
        else if (next === "check_out")  strip.classList.add("danger");
    }

    function fillProfile() {
        const d = state.session?.data || {};
        document.getElementById("p-name").textContent  = d.name          || "-";
        document.getElementById("p-code").textContent  = d.employee_code || "-";
        document.getElementById("p-dept").textContent  = d.department    || "-";
        document.getElementById("p-desig").textContent = d.designation   || "-";
        document.getElementById("p-email").textContent = d.email         || "-";
        document.getElementById("hello-name").textContent = `Hello, ${d.name?.split(" ")[0] || "Employee"}`;
        const avatar = document.getElementById("profile-avatar");
        if (avatar) avatar.textContent = (d.name || "?")[0].toUpperCase();
    }

    async function loadToday() {
        if (!state.session?.data?.id) return;
        const body = await api(`/attendance/today?employee_id=${state.session.data.id}`, { method: "GET" });
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
            const li      = document.createElement("li");
            const label   = scanLabel(rec.type);
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
        holidaysCache = rows;
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

    function calcDayStats(items) {
        const checkIn  = items.find(r => r.type === "check_in");
        const checkOut = [...items].reverse().find(r => r.type === "check_out");
        let workedMins = null, breakMins = 0, openBreak = null;
        if (checkIn) {
            const end = checkOut ? new Date(checkOut.scanned_at) : null;
            if (end) workedMins = Math.max(0, Math.round((end - new Date(checkIn.scanned_at)) / 60000));
        }
        items.forEach(r => {
            const d = new Date(r.scanned_at);
            if (r.type === "break_start") { openBreak = d; }
            else if (r.type === "break_end" && openBreak) {
                breakMins += Math.max(0, Math.round((d - openBreak) / 60000));
                openBreak = null;
            }
        });
        const netMins = workedMins != null ? Math.max(0, workedMins - breakMins) : null;
        return { workedMins, breakMins, netMins };
    }

    async function loadHistory() {
        if (!state.session?.data?.id) return;
        const now   = new Date();
        const first = new Date(now.getFullYear(), now.getMonth(), 1);
        const body  = await api(
            `/attendance/history?employee_id=${state.session.data.id}&from=${apiDate(first)}&to=${apiDate(now)}`,
            { method: "GET" }
        );
        const wrap = document.getElementById("history-list");
        wrap.innerHTML = "";
        const rows = body.data || [];
        if (!rows.length) {
            wrap.innerHTML = `<li class="hist-empty">No attendance records this month.</li>`;
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
                const items    = grouped[date];
                const lastType = items[items.length - 1]?.type;
                const flagged  = items.some(r => r.geofence_status === "flagged");
                const status   = statusFromLastType(lastType);
                const chipCls  = flagged ? "flagged" : status.cls;
                const chipLbl  = flagged ? "Flagged"  : status.label;
                const { workedMins, breakMins, netMins } = calcDayStats(items);

                const d    = new Date(date);
                const dow  = d.toLocaleDateString(undefined, { weekday: "short" });
                const dom  = d.toLocaleDateString(undefined, { day: "2-digit", month: "short" });

                const scanRows = items.map(r => {
                    const dotCls = r.type.replace(/_/g, "-");
                    const geo    = r.geofence_status === "flagged"
                        ? `<span class="hc-flag-icon" title="Outside geofence">⚑</span>` : "";
                    return `<div class="hc-scan-row hc-dot-${dotCls}">
                        <span class="hc-dot"></span>
                        <span class="hc-scan-label">${scanLabel(r.type)}</span>
                        <span class="hc-scan-time">${fmtTime(r.scanned_at)}${geo}</span>
                    </div>`;
                }).join("");

                const statsRow = (workedMins != null || breakMins > 0) ? `
                    <div class="hc-stats">
                        ${netMins != null ? `<span class="hc-stat"><span>Net</span><strong>${minsToText(netMins)}</strong></span>` : ""}
                        ${workedMins != null ? `<span class="hc-stat"><span>Gross</span><strong>${minsToText(workedMins)}</strong></span>` : ""}
                        ${breakMins > 0 ? `<span class="hc-stat"><span>Break</span><strong>${minsToText(breakMins)}</strong></span>` : ""}
                    </div>` : "";

                const li = document.createElement("li");
                li.className = "hc";
                li.innerHTML = `
                    <div class="hc-header">
                        <div class="hc-date">
                            <span class="hc-dow">${dow}</span>
                            <span class="hc-dom">${dom}</span>
                        </div>
                        <span class="status-chip ${chipCls}">${chipLbl}</span>
                    </div>
                    <div class="hc-scans">${scanRows}</div>
                    ${statsRow}
                `;
                wrap.appendChild(li);
            });
    }

    // ─── Calendar Grid ────────────────────────────────────────────────────────

    function renderCalendarGrid(year, month, attendanceByDate, holidays) {
        const grid = document.getElementById("cal-grid");
        if (!grid) return;

        const today      = new Date();
        const todayStr   = apiDate(today);
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const firstWeekDay = new Date(year, month, 1).getDay();
        const holidaySet  = new Set((holidays || []).map(h => h.date || h));

        const dayHeaders = ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"];
        let html = `<div class="cal-week-headers">${dayHeaders.map(d => `<div class="cal-wh">${d}</div>`).join("")}</div><div class="cal-days">`;

        for (let i = 0; i < firstWeekDay; i++) {
            html += `<div class="cal-day cal-empty"></div>`;
        }

        for (let d = 1; d <= daysInMonth; d++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, "0")}-${String(d).padStart(2, "0")}`;
            const date    = new Date(year, month, d);
            const dow     = date.getDay();
            const isWeekend = dow === 0 || dow === 6;
            const isHoliday = holidaySet.has(dateStr);
            const isToday   = dateStr === todayStr;
            const isPast    = date <= today;
            const records   = attendanceByDate[dateStr] || [];
            const hasCheckIn = records.some(r => r.type === "check_in");
            const isFlagged  = records.some(r => r.geofence_status === "flagged");

            let cls = "cal-day";
            if (isToday)   cls += " cal-today";
            if (isHoliday) cls += " cal-holiday";
            else if (isWeekend) cls += " cal-weekend";
            else if (hasCheckIn) cls += isFlagged ? " cal-flagged" : " cal-present";
            else if (isPast && !isHoliday && !isWeekend) cls += " cal-absent";

            html += `<div class="${cls}"><span class="cal-num">${d}</span></div>`;
        }

        html += "</div>";
        grid.innerHTML = html;
    }

    async function loadCalendar() {
        if (!state.session?.data?.id) return;

        const year  = calendarDate.getFullYear();
        const month = calendarDate.getMonth();
        const el    = document.getElementById("cal-month-label");
        if (el) el.textContent = calendarDate.toLocaleDateString(undefined, { month: "long", year: "numeric" });

        const firstDay = new Date(year, month, 1);
        const lastDay  = new Date(year, month + 1, 0);
        let attendanceByDate = {};

        try {
            const body = await api(
                `/attendance/history?employee_id=${state.session.data.id}&from=${apiDate(firstDay)}&to=${apiDate(lastDay)}`,
                { method: "GET" }
            );
            (body.data || []).forEach(r => {
                if (!attendanceByDate[r.date]) attendanceByDate[r.date] = [];
                attendanceByDate[r.date].push(r);
            });
        } catch (_) {}

        if (!holidaysCache) {
            try {
                const body = await api("/holidays", { method: "GET" });
                holidaysCache = body.data || [];
            } catch (_) { holidaysCache = []; }
        }

        renderCalendarGrid(year, month, attendanceByDate, holidaysCache);
    }
    // ─────────────────────────────────────────────────────────────────────────

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

    function showActionSheet(title, actions) {
        const backdrop  = document.getElementById("sheet-backdrop");
        const titleEl   = document.getElementById("sheet-title");
        const actionsEl = document.getElementById("sheet-actions");
        titleEl.textContent = title;
        actionsEl.innerHTML = "";
        backdrop.classList.remove("hidden");

        return new Promise((resolve) => {
            function close(value) {
                backdrop.classList.add("hidden");
                backdrop.removeEventListener("click", onBackdropClick);
                resolve(value);
            }

            function onBackdropClick(e) {
                if (e.target === backdrop) close(null);
            }

            actions.forEach((action) => {
                const btn = document.createElement("button");
                btn.type = "button";
                btn.className = "sheet-btn " + (action.className || "");

                if (action.icon !== undefined) {
                    const iconEl = document.createElement("span");
                    iconEl.className = "sheet-btn-icon";
                    iconEl.textContent = action.icon;
                    btn.appendChild(iconEl);
                }

                const labelEl = document.createElement("span");
                labelEl.textContent = action.label;
                btn.appendChild(labelEl);

                btn.addEventListener("click", () => close(action.value));
                actionsEl.appendChild(btn);
            });

            backdrop.addEventListener("click", onBackdropClick);
        });
    }

    function showModal(title, text, actions, variant = "") {
        const backdrop  = document.getElementById("modal-backdrop");
        const titleEl   = document.getElementById("modal-title");
        const textEl    = document.getElementById("modal-text");
        const cardEl    = backdrop.querySelector(".modal-card");
        const actionsEl = document.getElementById("modal-actions");
        titleEl.textContent = title;
        textEl.textContent  = text;
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
            return await showActionSheet("What do you want to record?", [
                { label: scanLabels.break_start, value: "break_start", className: "break",    icon: "☕" },
                { label: scanLabels.check_out,   value: "check_out",   className: "checkout", icon: "🚪" },
                { label: "Cancel",               value: null,           className: "cancel" },
            ]);
        }
        const ok = await showModal(
            "Confirm Scan",
            `Record ${scanLabel(next)} now?`,
            [
                { label: "Cancel",  value: false, className: "ghost" },
                { label: "Confirm", value: true },
            ]
        );
        return ok ? next : null;
    }

    async function onLoginSubmit(e) {
        e.preventDefault();
        const err = document.getElementById("login-error");
        err.textContent = "";
        err.classList.add("hidden");

        const username = document.getElementById("username").value.trim();
        const password = document.getElementById("password").value.trim();
        const btn      = document.getElementById("login-btn");
        btn.disabled   = true;
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
            err.textContent = friendlyError(error, "Could not sign in. Please try again.");
            err.classList.remove("hidden");
        } finally {
            btn.disabled    = false;
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
        if (!navigator.geolocation) {
            msg.textContent = "Geolocation not supported by this browser.";
            return false;
        }

        const records    = getTodayRecords();
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
                            qr_token:    token,
                            latitude:    position.coords.latitude,
                            longitude:   position.coords.longitude,
                            scan_type:   chosenType,
                        };

                        // Offline: queue for background sync
                        if (!navigator.onLine) {
                            await queueOfflineScan(payload);
                            msg.textContent = "Queued – will submit when online.";
                            await showModal(
                                "Scan Queued",
                                "You are offline. Attendance saved locally and will sync automatically when your device reconnects.",
                                [{ label: "OK", value: true }],
                                "warning"
                            );
                            setTimeout(() => { scanLocked = false; }, 800);
                            resolve(true);
                            return;
                        }

                        const body = await api("/attendance/scan", {
                            method: "POST",
                            body: JSON.stringify(payload),
                        });
                        const recordedAt = body.scanned_at ? fmtTime(body.scanned_at) : body.time ? fmtTime(body.time) : "";
                        msg.textContent  = `${body.label || body.type || "Recorded"}${recordedAt ? ` at ${recordedAt}` : ""}`;
                        await showModal(
                            body.status === "flagged" ? "Attendance Flagged" : "Attendance Recorded",
                            body.status === "flagged"
                                ? `${body.label || body.type} saved, but outside geofence.`
                                : `${body.label || body.type} saved successfully.`,
                            [{ label: "Done", value: true }],
                            body.status === "flagged" ? "warning" : "success"
                        );
                        await Promise.allSettled([loadToday(), loadHistory()]);
                        setTimeout(() => { scanLocked = false; }, 1200);
                        resolve(true);
                    } catch (error) {
                        msg.textContent = friendlyError(error, "Scan failed. Please try again.");
                        await showModal(
                            "Scan Failed",
                            msg.textContent,
                            [{ label: "Try Again", value: true }],
                            "warning"
                        );
                        setTimeout(() => { scanLocked = false; }, 800);
                        resolve(false);
                    }
                },
                (error) => {
                    const code = error?.code;
                    if (code === 1)      msg.textContent = "Location permission denied. Allow location access and try again.";
                    else if (code === 2) msg.textContent = "Location unavailable. Please enable GPS and try again.";
                    else if (code === 3) msg.textContent = "Location request timed out. Try again in an open area.";
                    else                 msg.textContent = `Location error: ${error?.message || "Unknown error"}`;
                    showModal("Location Error", msg.textContent, [{ label: "OK", value: true }], "warning");
                    setTimeout(() => { scanLocked = false; }, 800);
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
        const stopBtn  = document.getElementById("stop-scan-btn");
        const msg      = document.getElementById("scan-message");
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
        const stopBtn  = document.getElementById("stop-scan-btn");
        try { await qrScanner.stop(); } catch (_) {}
        finally {
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
                                "A newer version of the app is ready. Reload to update now?",
                                [
                                    { label: "Later",  value: false, className: "ghost" },
                                    { label: "Reload", value: true },
                                ],
                                "info"
                            ).then((ok) => { if (ok) window.location.reload(); });
                        }
                    });
                });
            }).catch(() => {});
        }
    }

    // ─── Pull-to-Refresh ─────────────────────────────────────────────────────
    function initPullToRefresh() {
        const content    = document.querySelector(".content");
        const indicator  = document.getElementById("ptr-indicator");
        const iconEl     = document.getElementById("ptr-icon");
        if (!content || !indicator) return;

        const THRESHOLD  = 72;   // px to pull before release triggers refresh
        const MAX_PULL   = 100;  // max visual pull distance
        let startY       = 0;
        let pulling      = false;
        let pullDist     = 0;
        let refreshing   = false;

        function setIndicator(dist) {
            const clamped  = Math.min(dist, MAX_PULL);
            const progress = Math.min(clamped / THRESHOLD, 1);
            indicator.style.transform = `translateY(${clamped - 44}px)`;
            indicator.style.opacity   = String(progress);
            if (iconEl) {
                iconEl.style.transform = `rotate(${progress * 200}deg)`;
                iconEl.classList.toggle("ptr-ready", dist >= THRESHOLD);
            }
        }

        content.addEventListener("touchstart", (e) => {
            if (content.scrollTop > 0 || refreshing) return;
            startY  = e.touches[0].clientY;
            pulling = true;
        }, { passive: true });

        content.addEventListener("touchmove", (e) => {
            if (!pulling || refreshing) return;
            pullDist = e.touches[0].clientY - startY;
            if (pullDist <= 0) { pulling = false; return; }
            // rubberband resistance
            const resistance = 1 - (pullDist / (pullDist + 180));
            setIndicator(pullDist * resistance * 2.2);
        }, { passive: true });

        content.addEventListener("touchend", async () => {
            if (!pulling) return;
            pulling = false;
            const triggered = pullDist >= THRESHOLD;
            pullDist = 0;

            if (!triggered) {
                indicator.style.transition = "transform 220ms ease, opacity 220ms ease";
                setIndicator(0);
                setTimeout(() => { indicator.style.transition = ""; }, 230);
                return;
            }

            // Trigger refresh
            refreshing = true;
            indicator.style.transition = "transform 180ms ease";
            indicator.style.transform  = "translateY(0px)";
            indicator.style.opacity    = "1";
            if (iconEl) iconEl.classList.add("ptr-spinning");

            try {
                const tab = document.querySelector(".tab.active")?.id?.replace("tab-", "");
                if (tab === "dashboard")  await Promise.allSettled([loadToday()]);
                else if (tab === "attendance") await Promise.allSettled([loadToday(), loadHistory()]);
                else if (tab === "calendar")  await loadCalendar();
                else if (tab === "profile")   fillProfile();
            } catch (_) {}

            await new Promise(r => setTimeout(r, 400));
            indicator.style.transition = "transform 220ms ease, opacity 220ms ease";
            setIndicator(0);
            if (iconEl) iconEl.classList.remove("ptr-spinning", "ptr-ready");
            setTimeout(() => {
                indicator.style.transition = "";
                refreshing = false;
            }, 230);
        });
    }
    // ─────────────────────────────────────────────────────────────────────────

    function bindEvents() {
        document.getElementById("login-form").addEventListener("submit", onLoginSubmit);
        document.getElementById("start-scan-btn").addEventListener("click", startScanner);
        document.getElementById("stop-scan-btn").addEventListener("click", stopScanner);

        document.getElementById("logout-btn").addEventListener("click", async () => {
            const ok = await showModal(
                "Log Out",
                "Are you sure you want to log out?",
                [
                    { label: "Cancel",   value: false, className: "ghost" },
                    { label: "Log Out",  value: true,  className: "danger" },
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
                if (btn.dataset.tab === "calendar") {
                    await loadCalendar();
                }
            });
        });

        document.querySelectorAll(".attendance-subtab-btn").forEach((btn) => {
            btn.addEventListener("click", async () => {
                const next = btn.dataset.attSubtab;
                setAttendanceSubtab(next);
                if (next === "history") await loadHistory();
            });
        });

        const calPrev = document.getElementById("cal-prev-btn");
        const calNext = document.getElementById("cal-next-btn");
        if (calPrev) {
            calPrev.addEventListener("click", async () => {
                calendarDate = new Date(calendarDate.getFullYear(), calendarDate.getMonth() - 1, 1);
                await loadCalendar();
            });
        }
        if (calNext) {
            calNext.addEventListener("click", async () => {
                calendarDate = new Date(calendarDate.getFullYear(), calendarDate.getMonth() + 1, 1);
                await loadCalendar();
            });
        }

        window.addEventListener("online",  async () => {
            setOfflineBanner(false);
            await processOfflineQueue();
        });
        window.addEventListener("offline", () => setOfflineBanner(true));

        document.getElementById("offline-retry-btn").addEventListener("click", async () => {
            setOfflineBanner(!navigator.onLine);
            if (!navigator.onLine || !state.session?.data?.id) return;
            await Promise.allSettled([loadToday(), loadHistory(), loadHolidays()]);
        });
    }

    bindEvents();
    initPwaInstall();
    initPullToRefresh();
    setOfflineBanner(!navigator.onLine);

    if (state.session?.status === "success" && state.session?.data?.id) {
        initAuthedView().then(() => {
            const initial = tabFromRoute();
            setTab(initial);
            if (initial === "calendar") loadCalendar();
        });
    } else {
        window.history.replaceState({}, "", "/employee");
        setScreen("login");
    }
})();
