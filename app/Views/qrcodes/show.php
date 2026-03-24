<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="row g-3" id="qr-normal-view">
    <!-- QR Image Card -->
    <div class="col-md-4">
        <div class="card text-center h-100">
            <div class="card-header fw-semibold d-flex align-items-center justify-content-center gap-2 flex-wrap">
                <span><i class="bi bi-qr-code text-primary me-2"></i><?= esc($qrcode['location_name']) ?></span>
                <?php if (!empty($liveUrl)) : ?>
                    <span class="badge bg-info-subtle text-info border border-info-subtle">Linked / rotating</span>
                <?php else : ?>
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Static</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (!empty($liveUrl)) : ?>
                    <div class="alert alert-info text-start small py-2 mb-3">
                        This QR opens a <strong>web page</strong>. On a tablet or PC, open the link below (or scan this QR with a normal camera). Employees must use the <strong>attendance app</strong> to scan the <strong>inner</strong> code on that page — not this outer QR with the app.
                    </div>
                    <p class="small text-muted mb-1">Live page URL</p>
                    <div class="input-group input-group-sm mb-3">
                        <input type="text" class="form-control font-monospace small" readonly id="live-url-input" value="<?= esc($liveUrl) ?>">
                        <button class="btn btn-outline-primary" type="button" id="copy-live-url"><i class="bi bi-clipboard"></i></button>
                    </div>
                    <p class="small text-muted mb-1">Outer QR (opens browser)</p>
                <?php else : ?>
                    <p class="small text-muted mb-1">Scan token (unchanging)</p>
                    <code class="small d-block mb-3 text-break"><?= esc($qrcode['token']) ?></code>
                <?php endif; ?>
                <img src="<?= base_url($qrImage) ?>" alt="QR Code" class="qr-img img-fluid mb-3 rounded">
                <div class="d-flex justify-content-center gap-3 mt-2 small text-muted">
                    <span><i class="bi bi-geo-alt me-1"></i><?= $qrcode['latitude'] ?>, <?= $qrcode['longitude'] ?></span>
                    <span><i class="bi bi-bullseye me-1"></i><?= $qrcode['geofence_radius'] ?>m radius</span>
                </div>
            </div>
            <div class="card-footer d-grid gap-2">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="bi bi-printer me-1"></i> Print QR Code
                </button>
                <a href="<?= base_url($qrImage) ?>" download class="btn btn-outline-secondary">
                    <i class="bi bi-download me-1"></i> Download PNG
                </a>
            </div>
        </div>
    </div>

    <!-- Map Card -->
    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-map text-primary me-2"></i>Location on Map</span>
                <a href="<?= base_url('qr-codes/edit/' . $qrcode['id']) ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-pencil me-1"></i> Edit Location / Radius
                </a>
            </div>
            <div class="card-body p-2">
                <div id="qr-map"></div>
            </div>
        </div>
    </div>
</div>

<!-- Print styles: full-page A4 poster on print only -->
<style>
@media print {
    /* Completely hide everything else */
    body { margin: 0 !important; padding: 0 !important; background: #fff !important; }
    #sidebar, .navbar, .topbar, #qr-normal-view { display: none !important; }

    /* Remove browser default print headers/footers */
    @page { size: A4 portrait; margin: 0; }

    /* Show and fill page with the print poster */
    #qr-print-poster {
        display: flex !important;
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        height: 100vh !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        border: none !important;
        flex-direction: column !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    .qrp-header { padding: 40px 48px 30px !important; flex-shrink: 0; }
    .qrp-brand-icon { width: 64px !important; height: 64px !important; font-size: 32px !important; }
    .qrp-brand-name { font-size: 28px !important; }
    .qrp-brand-sub  { font-size: 16px !important; }
    .qrp-location-badge { font-size: 24px !important; padding: 20px 48px !important; flex-shrink: 0; }
    .qrp-location-badge i { font-size: 26px !important; }
    .qrp-qr-wrapper {
        flex-grow: 1 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 40px !important;
    }
    .qrp-qr-img {
        width: 480px !important;
        height: 480px !important;
        border-radius: 20px !important;
        border-width: 12px !important;
    }
    .qrp-instruction { padding: 0 48px 40px !important; gap: 20px !important; flex-shrink: 0; }
    .qrp-step-num  { min-width: 40px !important; height: 40px !important; font-size: 20px !important; }
    .qrp-step-text { font-size: 22px !important; }
    .qrp-info-row  { padding: 24px 48px 40px !important; flex-shrink: 0; justify-content: flex-start !important; gap: 20px !important; }
    .qrp-info-chip { font-size: 18px !important; padding: 10px 24px !important; }
    .qrp-info-chip i { font-size: 20px !important; }
}
</style>

<!-- Hidden print poster (invisible on screen, fills page on print) -->
<div id="qr-print-poster" style="display:none; font-family:'Inter',sans-serif; background:#fff;">
    <div class="qrp-header" style="background:linear-gradient(135deg,#1e3a8a 0%,#2563eb 100%); padding:18px 20px 16px;">
        <div style="display:flex; align-items:center; gap:12px;">
            <div class="qrp-brand-icon" style="width:40px;height:40px;background:rgba(255,255,255,.2);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;color:#fff;">
                <i class="bi bi-qr-code-scan"></i>
            </div>
            <div>
                <div class="qrp-brand-name" style="color:#fff;font-size:15px;font-weight:700;">MTI Attendance</div>
                <div class="qrp-brand-sub" style="color:rgba(255,255,255,.75);font-size:10px;margin-top:2px;">Automated Attendance System</div>
            </div>
        </div>
    </div>
    <div class="qrp-location-badge" style="background:#f0f9ff;border-bottom:1px solid #e0f0ff;padding:12px 20px;display:flex;align-items:center;gap:8px;font-size:14px;font-weight:700;color:#1e40af;">
        <i class="bi bi-geo-alt-fill"></i>
        <span><?= esc($qrcode['location_name']) ?></span>
    </div>
    <div class="qrp-qr-wrapper" style="padding:20px;background:#fff;display:flex;justify-content:center;flex-grow:1;align-items:center;">
        <img class="qrp-qr-img" src="<?= base_url($qrImage) ?>" alt="QR Code" style="width:200px;height:200px;border-radius:12px;border:4px solid #f1f5f9;">
    </div>
    <div class="qrp-instruction" style="padding:0 18px 16px;display:flex;flex-direction:column;gap:8px;">
        <div style="display:flex;align-items:flex-start;gap:10px;">
            <div class="qrp-step-num" style="min-width:22px;height:22px;background:#2563eb;color:#fff;border-radius:50%;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;">1</div>
            <div class="qrp-step-text" style="font-size:12px;color:#374151;">Open the <strong>MTI Attendance</strong> app</div>
        </div>
        <div style="display:flex;align-items:flex-start;gap:10px;">
            <div class="qrp-step-num" style="min-width:22px;height:22px;background:#2563eb;color:#fff;border-radius:50%;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;">2</div>
            <div class="qrp-step-text" style="font-size:12px;color:#374151;">Tap <strong>Scan QR Code</strong></div>
        </div>
        <div style="display:flex;align-items:flex-start;gap:10px;">
            <div class="qrp-step-num" style="min-width:22px;height:22px;background:#2563eb;color:#fff;border-radius:50%;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;">3</div>
            <div class="qrp-step-text" style="font-size:12px;color:#374151;">Point camera at this code to mark attendance</div>
        </div>
    </div>
    <div class="qrp-info-row" style="display:flex;justify-content:center;gap:10px;padding:12px 18px;background:#f8fafc;border-top:1px solid #f1f5f9;">
        <div class="qrp-info-chip" style="display:flex;align-items:center;gap:5px;background:#fff;border:1px solid #e2e8f0;border-radius:20px;padding:4px 12px;font-size:11px;color:#475569;">
            <i class="bi bi-bullseye" style="font-size:12px;color:#2563eb;"></i>
            <span>Radius: <?= $qrcode['geofence_radius'] ?>m</span>
        </div>
        <div class="qrp-info-chip" style="display:flex;align-items:center;gap:5px;background:#fff;border:1px solid #e2e8f0;border-radius:20px;padding:4px 12px;font-size:11px;color:#475569;">
            <i class="bi bi-shield-check" style="font-size:12px;color:#2563eb;"></i>
            <span>GPS Verified</span>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const copyBtn = document.getElementById('copy-live-url');
    const liveInp = document.getElementById('live-url-input');
    if (copyBtn && liveInp) {
        copyBtn.addEventListener('click', function() {
            liveInp.select();
            navigator.clipboard.writeText(liveInp.value).then(function() {
                copyBtn.innerHTML = '<i class="bi bi-check2"></i>';
                setTimeout(function() { copyBtn.innerHTML = '<i class="bi bi-clipboard"></i>'; }, 1500);
            });
        });
    }

    const lat    = <?= $qrcode['latitude']  ?? 23.0225 ?>;
    const lng    = <?= $qrcode['longitude'] ?? 72.5714 ?>;
    const radius = <?= $qrcode['geofence_radius'] ?>;
    const map    = L.map('qr-map').setView([lat, lng], 17);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    L.marker([lat, lng]).addTo(map)
        .bindPopup('<strong><?= esc($qrcode['location_name']) ?></strong><br>Geofence: <?= $qrcode['geofence_radius'] ?>m radius')
        .openPopup();
    L.circle([lat, lng], {
        radius: radius,
        color: '#2563eb',
        fillColor: '#2563eb',
        fillOpacity: 0.12,
        weight: 2
    }).addTo(map);
});
</script>

<?= $this->endSection() ?>
