<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="mb-3">
    <a href="<?= base_url('qr-codes/create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Generate QR Code
    </a>
</div>



<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover datatable mb-0" id="qrcodes-table" width="100%">
            <thead>
                <tr>
                    <th>Location</th>
                    <th>Type</th>
                    <th>Token</th>
                    <th>Radius</th>
                    <th>Coordinates</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($qrcodes as $qr): ?>
                <tr>
                    <td><i class="bi bi-geo-alt text-primary me-1"></i><?= esc($qr['location_name']) ?></td>
                    <td>
                        <?php if (($qr['qr_mode'] ?? 'static') === 'rotating') : ?>
                            <span class="badge bg-info-subtle text-info border border-info-subtle">Linked</span>
                        <?php else : ?>
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Static</span>
                        <?php endif; ?>
                    </td>
                    <td><code class="small text-muted"><?= substr($qr['token'], 0, 12) ?>…</code></td>
                    <td><span class="badge bg-info-subtle text-info border border-info-subtle"><?= $qr['geofence_radius'] ?>m</span></td>
                    <td class="small"><?= $qr['latitude'] ? round($qr['latitude'], 4) . ', ' . round($qr['longitude'], 4) : '—' ?></td>
                    <td>
                        <?php if ($qr['is_active']): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-circle-fill me-1" style="font-size:7px;"></i>Active</span>
                        <?php else: ?>
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?= base_url('qr-codes/show/' . $qr['id']) ?>" class="btn btn-sm btn-primary" title="View QR">
                            <i class="bi bi-qr-code"></i>
                        </a>
                        <a href="<?= base_url('qr-codes/edit/' . $qr['id']) ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="<?= base_url('qr-codes/toggle/' . $qr['id']) ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm <?= $qr['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?>" title="Toggle">
                                <i class="bi bi-<?= $qr['is_active'] ? 'pause' : 'play' ?>"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
