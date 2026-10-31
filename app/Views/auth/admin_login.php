<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Administrator sign-in — Bitlence Attendance</title>
    <link rel="icon" type="image/svg+xml" href="<?= base_url('assets/icons/icon-192.svg') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>
<body class="login-bg">

<div class="login-card card p-4 p-md-5 mx-auto">
    <div class="text-center mb-4">
        <div class="login-icon-wrap mx-auto mb-3">
            <i class="bi bi-shield-lock"></i>
        </div>
        <h4 class="fw-bold mb-1">Administrator</h4>
        <p class="text-muted small mb-0">Web dashboard — email and password</p>
    </div>

    <?php
    $validationErrors = session()->getFlashdata('errors');
    if (is_array($validationErrors) && $validationErrors !== []):
    ?>
        <div class="alert alert-danger alert-sm py-2">
            <ul class="mb-0 ps-3 small">
                <?php foreach ($validationErrors as $err): ?>
                    <li><?= esc(is_array($err) ? implode(' ', $err) : (string) $err) ?></li>
                <?php endforeach ?>
            </ul>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-sm d-flex align-items-center gap-2 py-2">
            <i class="bi bi-exclamation-circle-fill"></i>
            <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success d-flex align-items-center gap-2 py-2">
            <i class="bi bi-check-circle-fill"></i>
            <?= session()->getFlashdata('success') ?>
        </div>
    <?php endif; ?>

    <?php if (filter_var(env('app.demoMode', false), FILTER_VALIDATE_BOOLEAN)): ?>
    <div class="alert alert-info py-2 small" style="text-align:left">
        <strong>Live demo.</strong> Sample data, read-only, refreshed nightly.<br>
        Email: <code>admin@demo.bitlence.com</code><br>Password: <code>demo1234</code><br><a href="<?= base_url('employee') ?>">Open the employee app</a>
    </div>
    <?php endif; ?>

    <form action="<?= base_url('admin/login') ?>" method="POST">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label fw-medium">Email address</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" class="form-control"
                       placeholder="admin@company.com" value="<?= old('email') ?>" required autofocus>
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label fw-medium">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
            <i class="bi bi-box-arrow-in-right me-1"></i> Sign in
        </button>
    </form>

    <p class="text-muted small text-center mt-4 mb-0">
        <a href="<?= base_url('login') ?>" class="text-decoration-none">Staff? Employee sign-in</a>
    </p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
