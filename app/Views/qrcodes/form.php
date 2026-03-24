<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="card" style="max-width:680px;">
    <div class="card-header fw-semibold">
        <i class="bi bi-qr-code text-primary me-2"></i>
        <?= $qrcode ? 'Edit QR Code' : 'Generate New QR Code' ?>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= base_url($qrcode ? 'qr-codes/update/' . $qrcode['id'] : 'qr-codes/store') ?>">
            <?= csrf_field() ?>
            <div class="row g-3 mb-3">
                <?php if (empty($qrcode)) : ?>
                <div class="col-12">
                    <label class="form-label fw-medium d-block">QR type <span class="text-danger">*</span></label>
                    <div class="d-flex flex-wrap gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="qr_mode" id="qr-mode-static" value="static"
                                   <?= old('qr_mode', 'static') === 'static' ? 'checked' : '' ?> required>
                            <label class="form-check-label" for="qr-mode-static">
                                <strong>Static</strong> — print or sticker; token never changes.
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="qr_mode" id="qr-mode-rotating" value="rotating"
                                   <?= old('qr_mode') === 'rotating' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="qr-mode-rotating">
                                <strong>Linked (browser)</strong> — open a live page; scan token refreshes on a timer and after each successful scan.
                            </label>
                        </div>
                    </div>
                    <div class="form-text">Interval is set in Admin → Settings (default 15 seconds).</div>
                </div>
                <?php else : ?>
                <div class="col-12">
                    <label class="form-label fw-medium text-muted">QR type (fixed)</label>
                    <p class="mb-0 small">
                        <?php if (($qrcode['qr_mode'] ?? 'static') === 'rotating') : ?>
                            <span class="badge bg-info-subtle text-info border border-info-subtle">Linked / rotating</span>
                        <?php else : ?>
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Static</span>
                        <?php endif; ?>
                    </p>
                </div>
                <?php endif; ?>
                <div class="col-md-6">
                    <label class="form-label fw-medium">Location Name <span class="text-danger">*</span></label>
                    <input type="text" name="location_name" class="form-control" required
                           placeholder="e.g. Main Gate"
                           value="<?= esc(old('location_name', $qrcode['location_name'] ?? '')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-medium">Geofence Radius (m) <span class="text-danger">*</span></label>
                    <input type="number" name="geofence_radius" class="form-control" required min="10" max="5000"
                           value="<?= esc(old('geofence_radius', $qrcode['geofence_radius'] ?? 50)) ?>">
                    <div class="form-text">Default 50m. How far employee can be to scan in.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-medium">Latitude <span class="text-danger">*</span></label>
                    <input type="text" name="latitude" id="lat-input" class="form-control" required
                           placeholder="23.0225"
                           value="<?= esc(old('latitude', $qrcode['latitude'] ?? '')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-medium">Longitude <span class="text-danger">*</span></label>
                    <input type="text" name="longitude" id="lng-input" class="form-control" required
                           placeholder="72.5714"
                           value="<?= esc(old('longitude', $qrcode['longitude'] ?? '')) ?>">
                </div>
            </div>

            <!-- Address Search + Current Location -->
            <div class="mb-3 position-relative" id="address-search-wrapper">
                <label class="form-label fw-medium">
                    <i class="bi bi-search me-1 text-primary"></i>Search Address
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-geo-alt-fill text-primary"></i>
                    </span>
                    <input type="text" id="address-search-input" class="form-control border-start-0 border-end-0 ps-0"
                           placeholder="Type an address to search and auto-select location…"
                           autocomplete="off">
                    <button class="btn btn-outline-primary" type="button" id="address-search-btn" title="Search">
                        <i class="bi bi-search"></i>
                    </button>
                    <button class="btn btn-outline-success" type="button" id="current-location-btn" title="Use my current GPS location">
                        <span id="loc-spinner" class="spinner-border spinner-border-sm me-1 d-none" role="status"></span>
                        <i class="bi bi-crosshair2" id="loc-icon"></i>
                        <span class="d-none d-sm-inline ms-1">My Location</span>
                    </button>
                </div>
                <ul id="address-suggestions" class="list-group shadow-sm position-absolute w-100"
                    style="z-index:1050;top:100%;display:none;max-height:220px;overflow-y:auto;"></ul>
                <div id="address-search-status" class="form-text text-muted mt-1" style="min-height:1.2em;"></div>
            </div>

            <div class="alert alert-info py-2 small">
                <i class="bi bi-info-circle me-1"></i>
                Search an address above <strong>or</strong> click directly on the map to auto-fill latitude &amp; longitude.
            </div>
            <div id="picker-map" class="mb-3"></div>

            <div class="d-flex gap-2">
                <a href="<?= base_url('qr-codes') ?>" class="btn btn-secondary">
                    <i class="bi bi-x-lg me-1"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>
                    <?= $qrcode ? 'Update QR Code' : 'Generate QR Code' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ── Map Init ──────────────────────────────────────────────────────────────
    const initLat = parseFloat(document.getElementById('lat-input').value) || 23.0225;
    const initLng = parseFloat(document.getElementById('lng-input').value) || 72.5714;
    const map = L.map('picker-map').setView([initLat, initLng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    let marker = L.marker([initLat, initLng]).addTo(map);

    // Helper: move marker + fill inputs
    function placeMarker(lat, lng, zoom) {
        marker.setLatLng([lat, lng]);
        map.setView([lat, lng], zoom || map.getZoom());
        document.getElementById('lat-input').value = lat.toFixed(8);
        document.getElementById('lng-input').value = lng.toFixed(8);
    }

    // Click-on-map fallback
    map.on('click', function(e) {
        placeMarker(e.latlng.lat, e.latlng.lng);
    });

    // ── Address Search (Nominatim) ────────────────────────────────────────────
    const searchInput   = document.getElementById('address-search-input');
    const searchBtn     = document.getElementById('address-search-btn');
    const suggestionBox = document.getElementById('address-suggestions');
    const statusEl      = document.getElementById('address-search-status');

    let searchTimeout = null;

    function setStatus(msg, type) {
        statusEl.textContent = msg;
        statusEl.className = 'form-text mt-1 ' + (type === 'error' ? 'text-danger' : 'text-muted');
    }

    function hideSuggestions() {
        suggestionBox.innerHTML = '';
        suggestionBox.style.display = 'none';
    }

    function renderSuggestions(results) {
        suggestionBox.innerHTML = '';
        if (!results.length) {
            setStatus('No results found. Try a more specific address.', 'error');
            hideSuggestions();
            return;
        }
        results.forEach(function(item) {
            const li = document.createElement('li');
            li.className = 'list-group-item list-group-item-action d-flex align-items-start gap-2 py-2 px-3';
            li.style.cursor = 'pointer';
            li.innerHTML = `
                <i class="bi bi-geo-alt-fill text-primary mt-1 flex-shrink-0"></i>
                <span class="small">${item.display_name}</span>`;
            li.addEventListener('click', function() {
                const lat = parseFloat(item.lat);
                const lng = parseFloat(item.lon);
                searchInput.value = item.display_name;
                setStatus('✔ Location selected.', 'muted');
                hideSuggestions();
                placeMarker(lat, lng, 17);
            });
            suggestionBox.appendChild(li);
        });
        suggestionBox.style.display = 'block';
    }

    async function doSearch(query) {
        if (!query.trim()) { setStatus('', ''); hideSuggestions(); return; }
        setStatus('Searching…', 'muted');
        searchBtn.disabled = true;
        try {
            const url = 'https://nominatim.openstreetmap.org/search?format=json&limit=6&q='
                        + encodeURIComponent(query);
            const res  = await fetch(url, { headers: { 'Accept-Language': 'en' } });
            const data = await res.json();
            setStatus('', '');
            renderSuggestions(data);
        } catch (e) {
            setStatus('Search failed. Check your internet connection.', 'error');
            hideSuggestions();
        } finally {
            searchBtn.disabled = false;
        }
    }

    // Live autocomplete (debounced 500ms)
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const q = searchInput.value.trim();
        if (q.length < 3) { hideSuggestions(); setStatus('', ''); return; }
        searchTimeout = setTimeout(function() { doSearch(q); }, 500);
    });

    // Search button click
    searchBtn.addEventListener('click', function() {
        clearTimeout(searchTimeout);
        doSearch(searchInput.value);
    });

    // Enter key
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(searchTimeout);
            doSearch(searchInput.value);
        }
    });

    // ── Current Location Button ───────────────────────────────────────────────
    const locBtn     = document.getElementById('current-location-btn');
    const locSpinner = document.getElementById('loc-spinner');
    const locIcon    = document.getElementById('loc-icon');

    locBtn.addEventListener('click', function() {
        if (!navigator.geolocation) {
            setStatus('⚠️ Geolocation is not supported by your browser.', 'error');
            return;
        }

        // Show spinner
        locSpinner.classList.remove('d-none');
        locIcon.classList.add('d-none');
        locBtn.disabled = true;
        setStatus('📡 Detecting your location…', 'muted');

        navigator.geolocation.getCurrentPosition(
            async function(pos) {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;

                // Pin on map
                placeMarker(lat, lng, 18);

                // Reverse-geocode to show address
                try {
                    const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`;
                    const res  = await fetch(url, { headers: { 'Accept-Language': 'en' } });
                    const data = await res.json();
                    if (data && data.display_name) {
                        searchInput.value = data.display_name;
                        setStatus('✔ Location detected and pinned!', 'muted');
                    } else {
                        setStatus(`✔ Pinned at ${lat.toFixed(5)}, ${lng.toFixed(5)}`, 'muted');
                    }
                } catch (_) {
                    setStatus(`✔ Pinned at ${lat.toFixed(5)}, ${lng.toFixed(5)}`, 'muted');
                }

                // Reset button
                locSpinner.classList.add('d-none');
                locIcon.classList.remove('d-none');
                locBtn.disabled = false;
            },
            function(err) {
                locSpinner.classList.add('d-none');
                locIcon.classList.remove('d-none');
                locBtn.disabled = false;

                const msgs = {
                    1: '🔒 Location permission denied. Please allow location access in your browser.',
                    2: '📡 Location unavailable. Try again or search manually.',
                    3: '⏱️ Location request timed out. Please try again.',
                };
                setStatus(msgs[err.code] || '⚠️ Could not get location.', 'error');
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    });

    // Hide suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!document.getElementById('address-search-wrapper').contains(e.target)) {
            hideSuggestions();
        }
    });
});
</script>
<?= $this->endSection() ?>
