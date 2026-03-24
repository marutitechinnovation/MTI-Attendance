<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="card p-0 overflow-hidden">
    <div id="live-map"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const locations = <?= $locations ?>;
    const liveData  = <?= $liveData ?>;
    const mapTileUrl = <?= json_encode($mapTileUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const mapAttribution = <?= json_encode($mapAttribution, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const mapSubdomains = <?= json_encode($mapSubdomains, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    const map = L.map('live-map').setView([23.0225, 72.5714], 14);
    L.tileLayer(mapTileUrl, {
        attribution: mapAttribution,
        subdomains: mapSubdomains || 'abc',
    }).addTo(map);

    locations.forEach(loc => {
        const icon = L.divIcon({ className: '', html: `<div style="background:#0d6efd;border-radius:50%;width:14px;height:14px;border:2px solid white;box-shadow:0 0 6px #0d6efd"></div>` });
        L.marker([loc.latitude, loc.longitude], { icon })
            .addTo(map)
            .bindPopup(`<b>${loc.location_name}</b><br>Radius: ${loc.geofence_radius}m`);
        L.circle([loc.latitude, loc.longitude], {
            radius: loc.geofence_radius, color: '#0d6efd', fillOpacity: 0.1, weight: 1
        }).addTo(map);
    });

    liveData.forEach(d => {
        const color = d.geofence_status === 'flagged' ? '#ffc107' : '#198754';
        const icon  = L.divIcon({ className: '', html: `<div style="background:${color};border-radius:50%;width:18px;height:18px;border:2px solid white;box-shadow:0 0 8px ${color}"></div>` });
        L.marker([d.latitude, d.longitude], { icon })
            .addTo(map)
            .bindPopup(`<b>${d.name}</b> (${d.employee_code})<br>${d.location_name}<br>Checked in: ${d.scanned_at}<br><span style="color:${color}">${d.geofence_status}</span>`);
    });

    setTimeout(() => location.reload(), 30000);
});
</script>
<?= $this->endSection() ?>
