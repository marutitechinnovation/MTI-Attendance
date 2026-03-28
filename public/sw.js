const CACHE_NAME = "mti-employee-v5";
const APP_SHELL = [
  "/employee",
  "/employee/dashboard",
  "/employee/attendance",
  "/employee/calendar",
  "/employee/profile",
  "/manifest.webmanifest",
  "/assets/css/employee-pwa.css",
  "/assets/js/employee-pwa.js",
  "/assets/js/html5-qrcode.min.js",
  "/assets/icons/icon-192.svg",
  "/assets/icons/icon-512.svg"
];

self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(APP_SHELL))
  );
  self.skipWaiting();
});

self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(
        keys
          .filter((key) => key !== CACHE_NAME)
          .map((key) => caches.delete(key))
      )
    )
  );
  self.clients.claim();
});

self.addEventListener("fetch", (event) => {
  const req = event.request;
  const url = new URL(req.url);

  if (req.method !== "GET") return;

  if (req.mode === "navigate" && url.pathname.startsWith("/employee")) {
    event.respondWith(
      fetch(req).catch(() => caches.match("/employee"))
    );
    return;
  }

  // API read endpoints: network-first with cache fallback
  if (url.pathname.startsWith("/api/")) {
    event.respondWith(
      fetch(req)
        .then((res) => {
          if (res.ok) {
            const clone = res.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(req, clone));
          }
          return res;
        })
        .catch(async () => {
          const cached = await caches.match(req);
          if (cached) return cached;
          return new Response(
            JSON.stringify({ status: "error", message: "Offline and no cached data." }),
            { status: 503, headers: { "Content-Type": "application/json" } }
          );
        })
    );
    return;
  }

  // App shell / static assets: cache-first with network update fallback
  event.respondWith(
    caches.match(req).then((cached) => {
      if (cached) return cached;
      return fetch(req)
        .then((res) => {
          if (res.ok) {
            const clone = res.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(req, clone));
          }
          return res;
        })
        .catch(() => caches.match("/employee"));
    })
  );
});

// ─── Offline Scan Background Sync ────────────────────────────────────────────

self.addEventListener("sync", (event) => {
  if (event.tag === "sync-attendance-scans") {
    event.waitUntil(replayScanQueue());
  }
});

function swOpenDB() {
  return new Promise((resolve, reject) => {
    const req = indexedDB.open("mti_offline", 1);
    req.onupgradeneeded = (e) => {
      const db = e.target.result;
      if (!db.objectStoreNames.contains("scan_queue")) {
        db.createObjectStore("scan_queue", { keyPath: "id", autoIncrement: true });
      }
    };
    req.onsuccess = (e) => resolve(e.target.result);
    req.onerror   = () => reject(req.error);
  });
}

async function replayScanQueue() {
  let db;
  try { db = await swOpenDB(); } catch (_) { return; }

  const items = await new Promise((resolve, reject) => {
    const tx  = db.transaction("scan_queue", "readonly");
    const req = tx.objectStore("scan_queue").getAll();
    req.onsuccess = () => resolve(req.result);
    req.onerror   = () => reject(req.error);
  });

  if (!items.length) return;

  for (const item of items) {
    try {
      const headers = { "Content-Type": "application/json", "Accept": "application/json" };
      if (item.token) headers["Authorization"] = `Bearer ${item.token}`;

      const res = await fetch("/api/attendance/scan", {
        method: "POST",
        headers,
        body: JSON.stringify(item.payload),
      });

      if (res.ok) {
        await new Promise((resolve, reject) => {
          const tx = db.transaction("scan_queue", "readwrite");
          tx.objectStore("scan_queue").delete(item.id);
          tx.oncomplete = resolve;
          tx.onerror    = () => reject(tx.error);
        });
      } else if (res.status === 401) {
        break; // Token expired — stop; user must log in again
      }
    } catch (_) {
      throw new Error("Still offline"); // Causes sync to retry later
    }
  }
}
