<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee sign-in — MTI Attendance</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%230d6efd'%3E%3Cpath d='M0 .5A.5.5 0 0 1 .5 0h3a.5.5 0 0 1 0 1H1v2.5a.5.5 0 0 1-1 0v-3zm12 0a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-1 0V1h-2.5a.5.5 0 0 1-.5-.5zM.5 16a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 1 0v2.5h2.5a.5.5 0 0 1 0 1h-3zm12 0a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 1 0v2.5h2.5a.5.5 0 0 1 0 1h-3zM3 3h10v10H3V3z'/%3E%3Cpath d='M4 4h8v8H4z'/%3E%3C/svg%3E">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>
<body class="login-bg">

<div class="login-card card p-4 p-md-5 mx-auto">
    <div class="text-center mb-4">
        <div class="login-icon-wrap mx-auto mb-3">
            <i class="bi bi-person-badge"></i>
        </div>
        <h4 class="fw-bold mb-1">MTI Attendance</h4>
        <p class="text-muted small mb-0">Staff sign-in · use your <strong>username</strong> in the web app</p>
    </div>

    <a href="<?= base_url('employee') ?>" class="btn btn-primary w-100 py-2 fw-semibold mb-3">
        <i class="bi bi-box-arrow-in-right me-1"></i> Open employee web app
    </a>

    <p class="text-muted small text-center mb-0">
        Installable on your phone (PWA). Your administrator creates your account in the web panel—use the username and password they give you.
    </p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
