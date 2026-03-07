<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle ?? 'MTI Attendance') ?> — MTI Attendance</title>
    <!-- QR Code Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%230d6efd'%3E%3Cpath d='M0 .5A.5.5 0 0 1 .5 0h3a.5.5 0 0 1 0 1H1v2.5a.5.5 0 0 1-1 0v-3zm12 0a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-1 0V1h-2.5a.5.5 0 0 1-.5-.5zM.5 16a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 1 0v2.5h2.5a.5.5 0 0 1 0 1h-3zm12 0a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 1 0v2.5h2.5a.5.5 0 0 1 0 1h-3zM3 3h10v10H3V3z'/%3E%3Cpath d='M4 4h8v8H4z'/%3E%3C/svg%3E">
    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- DataTables + Bootstrap 5 skin -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <?php if (strpos(current_url(), '/map') !== false || strpos(current_url(), '/qr-codes/show') !== false || strpos(current_url(), '/qr-codes/edit') !== false || strpos(current_url(), '/qr-codes/create') !== false): ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <?php endif; ?>
    <?php if (url_is('dashboard*')): ?>
    <!-- FullCalendar CSS -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js'></script>
    <?php endif; ?>
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="d-flex">
    <!-- ── Sidebar ────────────────────────────────── -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon"><i class="bi bi-qr-code-scan"></i></div>
            <div>
                <div class="brand-name">MTI</div>
                <div class="brand-sub">Attendance</div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <a href="<?= base_url('dashboard') ?>" class="nav-link <?= url_is('dashboard*') ? 'active' : '' ?>">
                <i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span>
            </a>
            <a href="<?= base_url('employees') ?>" class="nav-link <?= url_is('employees*') && !isset($_GET['status']) ? 'active' : '' ?>">
                <i class="bi bi-people-fill"></i><span>Employees</span>
            </a>
            <a href="<?= base_url('employees?status=inactive') ?>" class="nav-link <?= (url_is('employees*') && isset($_GET['status']) && $_GET['status'] === 'inactive') ? 'active' : '' ?>">
                <i class="bi bi-person-slash"></i><span>Inactive Employees</span>
            </a>
            <a href="<?= base_url('qr-codes') ?>" class="nav-link <?= url_is('qr-codes*') ? 'active' : '' ?>">
                <i class="bi bi-qr-code"></i><span>QR Codes</span>
            </a>
            <a href="<?= base_url('attendance') ?>" class="nav-link <?= url_is('attendance*') ? 'active' : '' ?>">
                <i class="bi bi-calendar-check-fill"></i><span>Attendance</span>
            </a>
            <a href="<?= base_url('holidays') ?>" class="nav-link <?= url_is('holidays*') ? 'active' : '' ?>">
                <i class="bi bi-calendar-event"></i><span>Holidays</span>
            </a>
            <a href="<?= base_url('reports') ?>" class="nav-link <?= url_is('reports*') ? 'active' : '' ?>">
                <i class="bi bi-bar-chart-fill"></i><span>Reports</span>
            </a>

            <hr class="sidebar-divider">
            <a href="<?= base_url('settings') ?>" class="nav-link <?= url_is('settings*') ? 'active' : '' ?>">
                <i class="bi bi-gear-fill"></i><span>Settings</span>
            </a>
            <a href="<?= base_url('logout') ?>" class="nav-link nav-logout">
                <i class="bi bi-box-arrow-right"></i><span>Logout</span>
            </a>
        </nav>
    </aside>

    <!-- ── Main Wrapper ───────────────────────────── -->
    <div class="main-wrapper flex-grow-1">
        <!-- Topbar -->
        <nav class="navbar topbar px-3 py-0">
            <button class="btn btn-sm btn-light me-2 sidebar-toggle-btn" id="sidebarToggle">
                <i class="bi bi-list fs-5"></i>
            </button>
            <span class="fw-semibold text-dark"><?= esc($pageTitle ?? '') ?></span>
            <div class="ms-auto d-flex align-items-center gap-2">
                <div class="dropdown">
                    <button class="btn btn-light rounded-pill border py-1 ps-1 pe-3 d-flex align-items-center gap-2 shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-weight: 600; font-size: 14px;">
                            <?php 
                                $name = session()->get('admin_name') ?: 'Admin';
                                echo strtoupper(substr($name, 0, 1));
                            ?>
                        </div>
                        <span class="fw-medium text-dark small"><?= esc($name) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 py-2">
                        <li><h6 class="dropdown-header text-uppercase text-muted fw-semibold mb-1" style="letter-spacing: 0.5px; font-size: 0.7rem;">Signed in as</h6></li>
                        <li><span class="dropdown-item-text fw-medium pb-2"><?= esc(session()->get('admin_email') ?: 'admin@mti.com') ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger pt-2" href="<?= base_url('logout') ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Flash messages -->
        <div class="px-4 pt-3">
            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                    <i class="bi bi-check-circle-fill"></i> <?= session()->getFlashdata('success') ?>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                    <i class="bi bi-exclamation-circle-fill"></i> <?= session()->getFlashdata('error') ?>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Page Content -->
        <main class="content px-4 pb-4">
            <?= $this->renderSection('content') ?>
        </main>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if (strpos(current_url(), '/map') !== false || strpos(current_url(), '/qr-codes/show') !== false || strpos(current_url(), '/qr-codes/edit') !== false || strpos(current_url(), '/qr-codes/create') !== false): ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<?php endif; ?>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script src="<?= base_url('assets/js/app.js') ?>?v=<?= filemtime(FCPATH . 'assets/js/app.js') ?>"></script>
</body>
</html>
