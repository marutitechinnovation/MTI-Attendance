<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<!-- Filter + Export -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form class="d-flex align-items-center gap-2 flex-wrap" method="GET">
            <input type="month" name="month" class="form-control form-control-sm" style="width:160px;"
                   value="<?= esc($month) ?>">
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

<!-- Report Table -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-bar-chart-fill text-primary me-2"></i>
            Monthly Report — <?= date('F Y', strtotime($month . '-01')) ?>
        </span>
        <span class="badge bg-secondary-subtle text-secondary border">
            <?= $workingDaysInfo['total_working_days'] ?> working days 
            <small>(<?= $workingDaysInfo['weekends'] ?> weekends, <?= $workingDaysInfo['holidays'] ?> holidays)</small>
        </span>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="reports-table" width="100%">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Present</th>
                    <th>Absent</th>
                    <th>Late</th>
                    <th><i class="bi bi-clock text-success me-1"></i>Total Hours</th>
                    <th><i class="bi bi-cup-hot-fill text-warning me-1"></i>Break Hours</th>
                    <th><i class="bi bi-hourglass-split text-primary me-1"></i>Net Hours</th>
                    <th>Avg / Day</th>
                    <th>Attendance %</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($report as $row):
                $absent   = max(0, $workingDaysInfo['total_working_days'] - $row['days_present']);
                $pct      = $workingDaysInfo['total_working_days'] > 0 ? round(($row['days_present'] / $workingDaysInfo['total_working_days']) * 100) : 0;
                $barColor = $pct >= 80 ? 'bg-success' : ($pct >= 60 ? 'bg-warning' : 'bg-danger');

                // Total gross hours (check-in to check-out)
                $grossMins = (int)($row['total_gross_minutes'] ?? 0);
                $grossH    = floor($grossMins / 60);
                $grossM    = $grossMins % 60;
                $grossStr  = $grossMins > 0 ? $grossH . 'h' . ($grossM > 0 ? ' ' . $grossM . 'm' : '') : '—';

                // Total break hours
                $breakMins = (int)($row['total_break_minutes'] ?? 0);
                $breakH    = floor($breakMins / 60);
                $breakM    = $breakMins % 60;
                $breakStr  = $breakMins > 0 ? $breakH . 'h' . ($breakM > 0 ? ' ' . $breakM . 'm' : '') : '—';

                // Net working hours
                $netMins   = (int)($row['total_net_minutes'] ?? 0);
                $netH      = floor($netMins / 60);
                $netM      = $netMins % 60;
                $netStr    = $netMins > 0 ? $netH . 'h' . ($netM > 0 ? ' ' . $netM . 'm' : '') : '—';

                // Average hours per present day (based on net)
                $avgMins = $row['days_present'] > 0 ? round($netMins / $row['days_present']) : 0;
                $avgH    = floor($avgMins / 60);
                $avgM    = $avgMins % 60;
                $avgStr  = $avgMins > 0 ? $avgH . 'h' . ($avgM > 0 ? ' ' . $avgM . 'm' : '') : '—';

                // Avg badge color based on expected 8h work day
                $avgBadge = $avgMins >= 480 ? 'bg-success-subtle text-success border-success-subtle'
                          : ($avgMins >= 360 ? 'bg-warning-subtle text-warning border-warning-subtle'
                          : ($avgMins > 0   ? 'bg-danger-subtle text-danger border-danger-subtle' : 'bg-secondary-subtle text-secondary border-secondary-subtle'));
            ?>
            <tr>
                <td class="fw-medium">
                    <a href="<?= base_url('reports/employee-detail?employee_id=' . $row['id'] . '&month=' . urlencode($month)) ?>" 
                       class="text-decoration-none text-dark" title="View day-by-day detail">
                        <?= esc($row['name']) ?> <i class="bi bi-box-arrow-up-right text-muted" style="font-size:0.7rem;"></i>
                    </a>
                </td>
                <td><?= esc($row['department']) ?></td>
                <td><span class="text-success fw-semibold"><?= $row['days_present'] ?></span></td>
                <td><span class="text-danger fw-semibold"><?= $absent ?></span></td>
                <td><span class="text-warning fw-semibold"><?= $row['late_days'] ?></span></td>
                <td><span class="fw-semibold text-success"><?= $grossStr ?></span></td>
                <td><span class="fw-semibold text-warning"><?= $breakStr ?></span></td>
                <td><span class="fw-bold text-primary"><?= $netStr ?></span></td>
                <td><span class="badge border <?= $avgBadge ?>"><?= $avgStr ?></span></td>
                <td style="min-width:130px;">
                    <div class="d-flex align-items-center gap-2">
                        <div class="progress flex-grow-1" style="height:6px; border-radius:3px;">
                            <div class="progress-bar <?= $barColor ?>" style="width:<?= $pct ?>%"></div>
                        </div>
                        <small class="fw-semibold text-muted" style="width:36px;"><?= $pct ?>%</small>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
