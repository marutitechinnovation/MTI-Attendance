<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<!-- Back + Month Nav -->
<div class="card mb-3">
    <div class="card-body py-2">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="<?= base_url('reports?month=' . urlencode($month)) ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Back to Reports
            </a>
            <div class="vr d-none d-sm-block"></div>
            <form class="d-flex align-items-center gap-2" method="GET">
                <input type="hidden" name="employee_id" value="<?= esc($employee['id']) ?>">
                <input type="month" name="month" class="form-control form-control-sm" style="width:160px;"
                       value="<?= esc($month) ?>">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-funnel me-1"></i>Go
                </button>
            </form>
            <?php
                $prevMonth = date('Y-m', strtotime($month . '-01 -1 month'));
                $nextMonth = date('Y-m', strtotime($month . '-01 +1 month'));
            ?>
            <div class="ms-auto d-flex gap-1">
                <a href="<?= base_url('reports/employee-detail?employee_id=' . $employee['id'] . '&month=' . $prevMonth) ?>"
                   class="btn btn-outline-primary btn-sm" title="Previous Month">
                    <i class="bi bi-chevron-left"></i>
                </a>
                <a href="<?= base_url('reports/employee-detail?employee_id=' . $employee['id'] . '&month=' . $nextMonth) ?>"
                   class="btn btn-outline-primary btn-sm" title="Next Month">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Employee Info + Summary Cards -->
<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                     style="width:56px; height:56px; font-size:1.4rem; font-weight:700; flex-shrink:0;">
                    <?= strtoupper(substr($employee['name'], 0, 1)) ?>
                </div>
                <div>
                    <h5 class="mb-0 fw-bold"><?= esc($employee['name']) ?></h5>
                    <div class="text-muted small"><?= esc($employee['employee_code']) ?> • <?= esc($employee['department'] ?? '—') ?></div>
                    <div class="text-muted small mt-1">
                        <i class="bi bi-calendar3 me-1"></i><?= date('F Y', strtotime($month . '-01')) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="row g-3">
            <?php
                $cards = [
                    ['label' => 'Present',      'value' => $totals['present'],  'icon' => 'bi-check-circle-fill',    'color' => 'success'],
                    ['label' => 'Absent',       'value' => $totals['absent'],   'icon' => 'bi-x-circle-fill',        'color' => 'danger'],
                    ['label' => 'Late',         'value' => $totals['late'],     'icon' => 'bi-clock-fill',           'color' => 'warning'],
                ];
                $netH = floor($totals['net_minutes'] / 60);
                $netM = $totals['net_minutes'] % 60;
                $cards[] = ['label' => 'Net Hours', 'value' => $netH . 'h' . ($netM > 0 ? ' ' . $netM . 'm' : ''), 'icon' => 'bi-hourglass-split', 'color' => 'primary'];
            ?>
            <?php foreach ($cards as $card): ?>
            <div class="col-6 col-md-3">
                <div class="card h-100 stat-card">
                    <div class="card-body d-flex align-items-center gap-2 py-2 px-3">
                        <div class="stat-icon-box bg-<?= $card['color'] ?> bg-opacity-10" style="width:38px; height:38px; min-width:38px;">
                            <i class="bi <?= $card['icon'] ?> text-<?= $card['color'] ?>" style="font-size:1rem;"></i>
                        </div>
                        <div>
                            <div class="fw-bold" style="font-size:1.15rem;"><?= $card['value'] ?></div>
                            <div class="text-muted" style="font-size:0.72rem;"><?= $card['label'] ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Day-by-Day Table -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span>
            <i class="bi bi-calendar2-week text-primary me-2"></i>
            Day-by-Day Attendance — <?= date('F Y', strtotime($month . '-01')) ?>
        </span>
        <span class="badge bg-secondary-subtle text-secondary border">
            <?= $workingDaysInfo['total_working_days'] ?> working days
        </span>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover datatable mb-0" id="employee-detail-table" width="100%"
               data-export-title="<?= esc($employee['name']) ?> (<?= esc($employee['employee_code']) ?>) — <?= date('F Y', strtotime($month . '-01')) ?>">
            <thead>
                <tr>
                    <th>Date</th>
                    <th><i class="bi bi-arrow-left-right text-primary me-1"></i>Shift In / Out</th>
                    <th><i class="bi bi-cup-hot text-warning me-1"></i>Break</th>
                    <th><i class="bi bi-clock text-success me-1"></i>Total / Break Hrs</th>
                    <th><i class="bi bi-hourglass-split text-primary me-1"></i>Net Hrs</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($days as $day):
                // Format times
                $in  = $day['check_in']  ? date('h:i A', strtotime($day['check_in']))  : '—';
                $out = $day['check_out'] ? date('h:i A', strtotime($day['check_out'])) : '—';

                // Break cell
                $breakCells = [];
                foreach ($day['breaks'] as $br) {
                    $bStart = $br['start'] ? date('h:i A', strtotime($br['start'])) : '';
                    $bEnd   = $br['end']   ? date('h:i A', strtotime($br['end']))   : '<span class="badge bg-warning-subtle text-warning border border-warning-subtle">On Break</span>';
                    if ($bStart) $breakCells[] = $bStart . ' &rarr; ' . $bEnd;
                }
                $breakCell = empty($breakCells) ? '—' : implode('<br>', $breakCells);

                // Format hours
                $fmtMins = function($mins) {
                    if ($mins <= 0) return '—';
                    $h = floor($mins / 60);
                    $m = $mins % 60;
                    return $h . 'h' . ($m > 0 ? ' ' . $m . 'm' : '');
                };
                $grossDur = $fmtMins($day['gross_minutes']);
                $breakDur = $fmtMins($day['break_minutes']);
                $netDur   = $fmtMins($day['net_minutes']);

                // Status badge
                $statusBadge = match($day['status']) {
                    'present' => '<span class="badge bg-success-subtle text-success border border-success-subtle">Present</span>',
                    'absent'  => '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">Absent</span>',
                    'flagged' => '<span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="bi bi-exclamation-triangle me-1"></i>Flagged</span>',
                    'weekend' => '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Weekend</span>',
                    'holiday' => '<span class="badge bg-info-subtle text-info border border-info-subtle"><i class="bi bi-star me-1"></i>' . esc($day['holiday_name'] ?? 'Holiday') . '</span>',
                    'future'  => '<span class="badge bg-light text-muted border">—</span>',
                    default   => '<span class="badge bg-secondary-subtle text-secondary">—</span>',
                };

                // Late indicator
                $lateTag = $day['is_late'] ? ' <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size:0.65rem;">LATE</span>' : '';

                // Row class
                $rowClass = '';
                if ($day['is_weekend'] || $day['is_holiday']) $rowClass = 'table-light';
                if ($day['status'] === 'future') $rowClass = 'table-light text-muted';

                // Combined shift cell
                $shiftCell = $in . ' → ' . $out;
                if ($in === '—' && $out === '—') $shiftCell = '—';

                // Combined hours cell
                if ($grossDur !== '—' || $breakDur !== '—') {
                    $hoursCell = '<span class="text-success fw-medium">' . $grossDur . '</span>';
                    $hoursCell .= ' <span class="text-muted mx-1">/</span> ';
                    $hoursCell .= '<span class="text-warning fw-medium">' . $breakDur . '</span>';
                } else {
                    $hoursCell = '—';
                }
            ?>
            <tr class="<?= $rowClass ?>">
                <td class="fw-medium text-nowrap">
                    <?= date('d M', strtotime($day['date'])) ?>
                    <span class="<?= $day['is_weekend'] ? 'text-danger fw-semibold' : 'text-muted' ?> ms-1">(<?= $day['day_short'] ?>)</span>
                </td>
                <td class="text-nowrap"><?= $shiftCell ?><?= $lateTag ?></td>
                <td class="small text-muted"><?= $breakCell ?></td>
                <td><?= $hoursCell ?></td>
                <td><span class="fw-bold text-primary"><?= $netDur ?></span></td>
                <td><?= $statusBadge ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>

            <!-- Totals Footer -->
            <tfoot class="table-dark">
                <tr>
                    <td colspan="3" class="fw-bold">
                        <i class="bi bi-calculator me-1"></i>Monthly Totals
                    </td>
                    <?php
                        $fmtTotals = function($mins) {
                            if ($mins <= 0) return '0h';
                            $h = floor($mins / 60);
                            $m = $mins % 60;
                            return $h . 'h' . ($m > 0 ? ' ' . $m . 'm' : '');
                        };
                    ?>
                    <td class="fw-bold"><?= $fmtTotals($totals['gross_minutes']) ?> / <?= $fmtTotals($totals['break_minutes']) ?></td>
                    <td class="fw-bold"><?= $fmtTotals($totals['net_minutes']) ?></td>
                    <td>
                        <span class="badge bg-success"><?= $totals['present'] ?>P</span>
                        <span class="badge bg-danger"><?= $totals['absent'] ?>A</span>
                        <span class="badge bg-warning text-dark"><?= $totals['late'] ?>L</span>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?= $this->endSection() ?>
