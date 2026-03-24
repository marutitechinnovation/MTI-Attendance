<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="card" style="max-width:920px;">
    <div class="card-header fw-semibold">
        <i class="bi bi-gear-fill text-primary me-2"></i>System Settings
    </div>
    <div class="card-body">
        <?php if (session()->getFlashdata('success')) : ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= esc(session()->getFlashdata('success')) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <form method="POST" action="<?= base_url('settings') ?>">
            <?= csrf_field() ?>
            
            <div class="d-flex flex-wrap gap-2 mb-4 pb-4 border-bottom">
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="copySignupLink(this)">
                    <i class="bi bi-link-45deg me-1"></i> Copy Public Signup Link
                </button>
            </div>

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-medium">Company Name</label>
                    <input type="text" name="company_name" class="form-control"
                           value="<?= esc($settings['company_name'] ?? 'MTI Company') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-medium">Work Start Time</label>
                    <input type="time" name="work_start_time" class="form-control"
                           value="<?= esc($settings['work_start_time'] ?? '09:00') ?>">
                    <div class="form-text">Used to detect late arrivals.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-medium">Work End Time</label>
                    <input type="time" name="work_end_time" class="form-control"
                           value="<?= esc($settings['work_end_time'] ?? '18:00') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-medium">Default Geofence Radius (meters)</label>
                    <input type="number" name="default_geofence_radius" class="form-control" min="10" max="5000"
                           value="<?= esc($settings['default_geofence_radius'] ?? 50) ?>">
                    <div class="form-text">Applied to new QR codes unless overridden per location.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-medium">Linked QR — token refresh (seconds)</label>
                    <input type="number" name="qr_rotating_interval_seconds" class="form-control" min="5" max="600"
                           value="<?= esc($settings['qr_rotating_interval_seconds'] ?? 15) ?>">
                    <div class="form-text">How often the scan code rotates on the live browser page (5–600). Also rotates immediately after each successful attendance scan.</div>
                </div>
                <div class="col-12 mt-4">
                    <label class="form-label fw-medium">Standard Weekend Days</label>
                    <div class="form-text mb-2">Select the days that are considered weekends (non-working days).</div>
                    <?php 
                        $weekends = isset($settings['weekend_days']) ? json_decode($settings['weekend_days'], true) : ['Saturday', 'Sunday']; 
                        if (!is_array($weekends)) $weekends = ['Saturday', 'Sunday'];
                        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                    ?>
                    <div class="d-flex flex-wrap gap-3">
                        <?php foreach($days as $day): ?>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" name="weekend_days[]" value="<?= $day ?>" id="chk_<?= $day ?>"
                                <?= in_array($day, $weekends) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="chk_<?= $day ?>">
                                <?= $day ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="col-12 mt-4 pt-4 border-top">
                    <h6 class="fw-semibold mb-3"><i class="bi bi-phone text-primary me-2"></i>Employee PWA — name &amp; colors</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">App name (login &amp; title)</label>
                            <input type="text" name="employee_app_name" class="form-control" required maxlength="120"
                                   value="<?= esc($settings['employee_app_name'] ?? 'MTI Attendance') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Short name (home screen / manifest)</label>
                            <input type="text" name="employee_app_short_name" class="form-control" maxlength="12"
                                   value="<?= esc($settings['employee_app_short_name'] ?? 'MTI Employee') ?>">
                            <div class="form-text">Keep short; iOS recommends ~12 characters.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Browser tab title (prefix)</label>
                            <input type="text" name="employee_app_page_title" class="form-control" maxlength="80"
                                   value="<?= esc($settings['employee_app_page_title'] ?? 'Employee App') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium">PWA description</label>
                            <input type="text" name="employee_app_description" class="form-control" maxlength="200"
                                   value="<?= esc($settings['employee_app_description'] ?? 'Employee attendance PWA.') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Theme color</label>
                            <input type="text" name="employee_app_theme_color" class="form-control" pattern="#?[0-9A-Fa-f]{6}"
                                   value="<?= esc($settings['employee_app_theme_color'] ?? '#1A237E') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Background</label>
                            <input type="text" name="employee_app_background_color" class="form-control" pattern="#?[0-9A-Fa-f]{6}"
                                   value="<?= esc($settings['employee_app_background_color'] ?? '#F5F7FB') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Accent</label>
                            <input type="text" name="employee_app_accent_color" class="form-control" pattern="#?[0-9A-Fa-f]{6}"
                                   value="<?= esc($settings['employee_app_accent_color'] ?? '#00BCD4') ?>">
                        </div>
                    </div>
                </div>

                <div class="col-12 mt-4 pt-4 border-top">
                    <h6 class="fw-semibold mb-3"><i class="bi bi-map text-primary me-2"></i>Live map — tiles &amp; key</h6>
                    <?php $mapProv = $settings['map_tile_provider'] ?? 'osm'; ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Tile provider</label>
                            <select name="map_tile_provider" class="form-select">
                                <option value="osm" <?= $mapProv === 'osm' ? 'selected' : '' ?>>OpenStreetMap (free, default)</option>
                                <option value="custom" <?= $mapProv === 'custom' ? 'selected' : '' ?>>Custom URL</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Map API key</label>
                            <input type="password" name="map_api_key" class="form-control" autocomplete="off"
                                   placeholder="<?= !empty($settings['map_api_key']) ? '•••••••• (enter new to replace)' : 'Optional — paste key' ?>">
                            <div class="form-text">Used when the tile URL contains <code>{apikey}</code>. Leave blank to keep the saved key.</div>
                            <?php if (!empty($settings['map_api_key'])) : ?>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="clear_map_api_key" value="1" id="clear_map_api_key">
                                <label class="form-check-label" for="clear_map_api_key">Remove saved map API key</label>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium">Custom tile URL template</label>
                            <input type="text" name="map_tile_url" class="form-control" dir="ltr"
                                   placeholder="https://api.mapbox.com/styles/v1/.../tiles/512/{z}/{x}/{y}@2x?access_token={apikey}"
                                   value="<?= esc($settings['map_tile_url'] ?? '') ?>">
                            <div class="form-text">Only if provider is Custom. Use Leaflet placeholders <code>{z}</code> <code>{x}</code> <code>{y}</code> and optional <code>{s}</code>. Put <code>{apikey}</code> where the token goes.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Tile subdomains (for <code>{s}</code>)</label>
                            <input type="text" name="map_tile_subdomains" class="form-control" maxlength="20"
                                   value="<?= esc($settings['map_tile_subdomains'] ?? 'abc') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Attribution (HTML allowed by Leaflet)</label>
                            <input type="text" name="map_attribution" class="form-control"
                                   value="<?= esc($settings['map_attribution'] ?? '© OpenStreetMap contributors') ?>">
                        </div>
                    </div>
                </div>

                <div class="col-12 mt-4 pt-4 border-top">
                    <h6 class="fw-semibold mb-3"><i class="bi bi-graph-up text-primary me-2"></i>Employee app — scan &amp; status labels</h6>
                    <div class="row g-3">
                        <?php
                        $lbl = [
                            'stat_label_check_in'    => 'Check In',
                            'stat_label_break_start' => 'Break Start',
                            'stat_label_break_end'   => 'Break End',
                            'stat_label_check_out'   => 'Check Out',
                        ];
                        foreach ($lbl as $k => $label) : ?>
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label fw-medium small"><?= esc($label) ?> (label)</label>
                            <input type="text" name="<?= esc($k) ?>" class="form-control form-control-sm" maxlength="40"
                                   value="<?= esc($settings[$k] ?? $label) ?>">
                        </div>
                        <?php endforeach; ?>
                        <?php
                        $st = [
                            'stat_status_working'  => ['Working', 'Status: Working / timeline'],
                            'stat_status_on_break' => ['On Break', 'Status: On break'],
                            'stat_status_complete' => ['Shift Complete', 'Status: Shift complete'],
                            'stat_status_not_in'   => ['Not Checked In', 'Status: Not checked in'],
                        ];
                        foreach ($st as $k => $meta) : ?>
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label fw-medium small"><?= esc($meta[1]) ?></label>
                            <input type="text" name="<?= esc($k) ?>" class="form-control form-control-sm" maxlength="40"
                                   value="<?= esc($settings[$k] ?? $meta[0]) ?>">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="mt-4 border-top pt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function copySignupLink(btn) {
    const url = '<?= base_url('signup') ?>';
    navigator.clipboard.writeText(url).then(() => {
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check2 me-1"></i> Copied!';
        btn.classList.remove('btn-outline-primary');
        btn.classList.add('btn-success', 'text-white');
        
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.classList.remove('btn-success', 'text-white');
            btn.classList.add('btn-outline-primary');
        }, 2000);
    }).catch(err => {
        console.error('Failed to copy: ', err);
        alert('Failed to copy link. The URL is: ' + url);
    });
}
</script>

<?= $this->endSection() ?>
