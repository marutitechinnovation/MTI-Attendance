<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<!-- Filter bar -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form class="d-flex align-items-center gap-2 flex-wrap" method="GET">
            <input type="date" name="date" class="form-control form-control-sm" style="width:160px;"
                   value="<?= esc($date) ?>">
            <select name="employee_id" class="form-select form-select-sm" style="width:160px;">
                <option value="">All Employees</option>
                <?php foreach ($employees as $emp): ?>
                    <option value="<?= $emp['id'] ?>" <?= (($selectedEmployee ?? '') == $emp['id']) ? 'selected' : '' ?>>
                        <?= esc($emp['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="department" class="form-select form-select-sm" style="width:150px;">
                <option value="">All Departments</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?= esc($d['department']) ?>"
                        <?= (($selectedDepartment ?? '') == $d['department']) ? 'selected' : '' ?>>
                        <?= esc($d['department']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-funnel me-1"></i> Filter
            </button>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover datatable mb-0" id="attendance-table" width="100%">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Department</th>
                    <th><i class="bi bi-box-arrow-in-right text-success me-1"></i>Shift In</th>
                    <th><i class="bi bi-cup-hot text-warning me-1"></i>Break</th>
                    <th><i class="bi bi-box-arrow-right text-danger me-1"></i>Shift Out</th>
                    <th><i class="bi bi-clock text-success me-1"></i>Total Hours</th>
                    <th><i class="bi bi-cup-hot-fill text-warning me-1"></i>Break Hours</th>
                    <th><i class="bi bi-hourglass-split text-primary me-1"></i>Net Hours</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>

            <tbody>
            <?php foreach ($logs as $row):
                $in  = $row['check_in']  ? date('h:i A', strtotime($row['check_in']))  : '—';
                $out = $row['check_out'] ? date('h:i A', strtotime($row['check_out'])) : '—';

                $breaks = $row['breaks'] ?? [];
                $breakCells = [];
                foreach ($breaks as $br) {
                    $bStart = $br['start'] ? date('h:i A', strtotime($br['start'])) : '';
                    $bEnd   = $br['end']   ? date('h:i A', strtotime($br['end']))   : '<span class="badge bg-warning-subtle text-warning border border-warning-subtle">On Break</span>';
                    if ($bStart) {
                        $breakCells[] = $bStart . ' &rarr; ' . $bEnd;
                    }
                }
                $breakCell = empty($breakCells) ? '—' : implode('<br>', $breakCells);

                $grossMins = isset($row['gross_minutes']) && $row['gross_minutes'] > 0 ? (int)$row['gross_minutes'] : null;
                $grossDur  = $grossMins !== null
                    ? floor($grossMins / 60) . 'h ' . ($grossMins % 60 > 0 ? ($grossMins % 60) . 'm' : '')
                    : '—';

                $breakMins = isset($row['total_break_minutes']) && $row['total_break_minutes'] > 0 ? (int)$row['total_break_minutes'] : null;
                $breakDur  = $breakMins !== null
                    ? floor($breakMins / 60) . 'h ' . ($breakMins % 60 > 0 ? ($breakMins % 60) . 'm' : '')
                    : '—';

                $netMins = isset($row['net_minutes']) && $row['net_minutes'] > 0 ? (int)$row['net_minutes'] : null;
                $netDur  = $netMins !== null
                    ? floor($netMins / 60) . 'h ' . ($netMins % 60 > 0 ? ($netMins % 60) . 'm' : '')
                    : '—';

                $status = !$row['check_in'] ? 'absent'
                        : ($row['geofence_status'] === 'flagged' ? 'flagged' : 'present');
                $badgeClass = match($status) {
                    'present' => 'bg-success-subtle text-success border border-success-subtle',
                    'absent'  => 'bg-danger-subtle text-danger border border-danger-subtle',
                    'flagged' => 'bg-warning-subtle text-warning border border-warning-subtle',
                    default   => 'bg-secondary-subtle text-secondary',
                };
            ?>
            <tr>
                <td class="fw-medium"><?= esc($row['name']) ?></td>
                <td><?= esc($row['department']) ?></td>
                <td>
                    <?= $in ?>
                    <?php if ($row['is_late']): ?>
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle ms-1" style="font-size: 0.65rem;">LATE</span>
                    <?php endif; ?>
                </td>
                <td class="small text-muted"><?= $breakCell ?></td>
                <td><?= $out ?></td>
                <td><span class="fw-medium text-success"><?= $grossDur ?></span></td>
                <td><span class="fw-medium text-warning"><?= $breakDur ?></span></td>
                <td><span class="fw-bold text-primary"><?= $netDur ?></span></td>
                <td><span class="badge <?= $badgeClass ?>"><?= ucfirst($status) ?></span></td>
                <td class="text-end text-nowrap">
                    <!-- View Button -->
                    <button type="button" class="btn btn-sm btn-outline-info me-1" title="View Details"
                            data-bs-toggle="modal" data-bs-target="#viewLogModal"
                            data-name="<?= esc($row['name']) ?>"
                            data-date="<?= date('d M Y', strtotime($date)) ?>"
                            data-in="<?= esc($in) ?>"
                            data-out="<?= esc($out) ?>"
                            data-breaks="<?= esc($breakCell) ?>"
                            data-gross="<?= esc($grossDur) ?>"
                            data-breakhours="<?= esc($breakDur) ?>"
                            data-net="<?= esc($netDur) ?>"
                            data-late="<?= $row['is_late'] ? 'Yes' : 'No' ?>"
                            data-flagdetails="<?= esc($row['flag_details']) ?>"
                            data-flagged-scans='<?= $row['flagged_scans_json'] ?>'>
                        <i class="bi bi-eye"></i>
                    </button>
                    <!-- Edit Button -->
                    <a href="<?= base_url('attendance/edit/' . $date . '/' . $row['id']) ?>" 
                       class="btn btn-sm btn-outline-primary no-ajax me-1" title="Edit Logs">
                        <i class="bi bi-pencil-square"></i>
                    </a>
                    <?php if ($status !== 'absent'): ?>
                        <form method="POST" action="<?= base_url('attendance/delete/' . $date . '/' . $row['id']) ?>" class="d-inline"
                              onsubmit="return confirm('Delete all attendance logs for <?= esc($row['name']) ?> on this date?')">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger" title="Delete Logs">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>

            </tbody>
        </table>
    </div>
</div>

<!-- View Details Modal -->
<div class="modal fade" id="viewLogModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-semibold text-primary"><i class="bi bi-info-circle me-2"></i>Attendance Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <h6 class="fw-bold mb-1" id="modal-emp-name">Employee Name</h6>
                    <p class="text-muted small mb-0" id="modal-emp-date">Date</p>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Shift In:</span>
                    <span class="fw-medium text-success" id="modal-emp-in">--</span>
                </div>
                <div class="d-flex justify-content-between mb-2 border-bottom pb-2">
                    <span class="text-muted">Shift Out:</span>
                    <span class="fw-medium text-danger" id="modal-emp-out">--</span>
                </div>
                <div class="d-flex justify-content-between my-2 py-2">
                    <span class="text-muted w-50">Break Times:</span>
                    <span class="fw-medium text-warning text-end w-50" id="modal-emp-breaks">--</span>
                </div>
                <!-- Late Section (Hidden by default) -->
                <div id="modal-late-row" class="alert alert-warning py-1 px-2 small mb-2 d-none">
                    <i class="bi bi-clock-history me-1"></i> Arrived late today
                </div>
                <!-- Flag Section (Hidden by default) -->
                <div id="modal-flag-row" class="alert alert-danger py-1 px-2 small mb-2 d-none">
                    <i class="bi bi-geo-fill me-1"></i> <strong>Location Flag:</strong>
                    <div id="modal-emp-flagdetails" class="mt-1">--</div>
                    <div id="flag-map" class="mt-2 rounded" style="height: 180px; width: 100%;"></div>
                </div>
                <hr class="my-2">
                <div class="row g-2 mt-1">
                    <div class="col-4">
                        <div class="text-center p-2 rounded bg-success bg-opacity-10">
                            <div class="text-muted small">Total Hours</div>
                            <div class="fw-bold text-success" id="modal-emp-gross">--</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="text-center p-2 rounded bg-warning bg-opacity-10">
                            <div class="text-muted small">Break Hours</div>
                            <div class="fw-bold text-warning" id="modal-emp-breakhours">--</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="text-center p-2 rounded bg-primary bg-opacity-10">
                            <div class="text-muted small">Net Hours</div>
                            <div class="fw-bold text-primary" id="modal-emp-net">--</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
