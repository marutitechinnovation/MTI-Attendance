const CACHE_NAME = "mti-employee-v3";
const APP_SHELL = [
  "/employee",
  "/employee/login",
  "/employee/dashboard",
  "/employee/attendance",
  "/employee/calendar",
  "/employee/profile",
  "/manifest.webmanifest",
  "/assets/css/employee-pwa.css",
  "/assets/js/employee-pwa.js",
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

  // API strategy:
  // - Attendance scan/write endpoints: always network-only
  // - Read endpoints: network-first with cache fallback
  if (url.pathname.includes("/api/attendance/scan")) {
    event.respondWith(fetch(req));
    return;
  }

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

  // App shell/static assets: cache-first with network update fallback
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
