<?php
require_once __DIR__ . '/functions.php';
$user      = current_user();
$pageTitle = $pageTitle ?? APP_NAME;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <link href="<?= e(url('assets/vendor/bootstrap.min.css')) ?>" rel="stylesheet">
    <link href="<?= e(url('assets/style.css')) ?>" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= e(url('index.php')) ?>"><?= e(APP_NAME) ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div id="nav" class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="<?= e(url('jobs.php')) ?>">Browse Jobs</a></li>
                <?php if ($user && $user['role'] === 'seeker'): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('seeker/dashboard.php')) ?>">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('seeker/resumes.php')) ?>">My Resumes</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('seeker/applications.php')) ?>">Applications</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('seeker/profile.php')) ?>">Profile</a></li>
                <?php elseif ($user && $user['role'] === 'employer'): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('employer/dashboard.php')) ?>">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('employer/post_job.php')) ?>">Post a Job</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('employer/my_jobs.php')) ?>">My Jobs</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('employer/company.php')) ?>">Company</a></li>
                <?php elseif ($user && $user['role'] === 'admin'): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('admin/dashboard.php')) ?>">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('admin/employers.php')) ?>">Employers</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('admin/users.php')) ?>">Users</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('admin/jobs.php')) ?>">Jobs</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('admin/categories.php')) ?>">Categories</a></li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <?php if ($user): ?>
                    <li class="nav-item"><span class="navbar-text me-3">
                        <?= e($user['name']) ?> <span class="badge bg-light text-primary"><?= e(ucfirst($user['role'])) ?></span>
                    </span></li>
                    <li class="nav-item"><a class="btn btn-outline-light btn-sm" href="<?= e(url('logout.php')) ?>">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('login.php')) ?>">Login</a></li>
                    <li class="nav-item"><a class="btn btn-light btn-sm ms-lg-2" href="<?= e(url('register.php')) ?>">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4 flex-grow-1">
<?php foreach (take_flashes() as $f): ?>
    <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show" role="alert">
        <?= e($f['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endforeach; ?>
