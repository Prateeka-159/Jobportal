<?php
require_once __DIR__ . '/includes/functions.php';

$categories = q('SELECT category_id, name FROM categories ORDER BY name')->fetchAll();

$latest = q("SELECT j.job_id, j.title, j.location, j.job_type, j.salary_min, j.salary_max, j.created_at,
                    e.company_name
             FROM jobs j JOIN employers e ON e.employer_id = j.employer_id
             WHERE j.status = 'open' AND e.is_approved = TRUE
               AND (j.deadline IS NULL OR j.deadline >= CURRENT_DATE)
             ORDER BY j.created_at DESC
             LIMIT 6")->fetchAll();

$stats = q("SELECT
              (SELECT COUNT(*) FROM jobs j JOIN employers e ON e.employer_id = j.employer_id
                WHERE j.status = 'open' AND e.is_approved = TRUE) AS jobs,
              (SELECT COUNT(*) FROM employers WHERE is_approved = TRUE) AS companies,
              (SELECT COUNT(*) FROM job_seekers) AS seekers")->fetch();

$pageTitle = 'Find your next job';
require __DIR__ . '/includes/header.php';
?>
<section class="hero p-4 p-md-5 mb-4">
    <h1 class="display-6 fw-bold">Find the job that fits your resume</h1>
    <p class="lead mb-4">Upload your resume once, apply to openings in a click and track every application.</p>
    <form class="row g-2" action="<?= e(url('jobs.php')) ?>" method="get">
        <div class="col-md-4"><input class="form-control" name="q" placeholder="Job title, skill or company"></div>
        <div class="col-md-3"><input class="form-control" name="location" placeholder="Location"></div>
        <div class="col-md-3">
            <select class="form-select" name="category">
                <option value="">All categories</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int)$c['category_id'] ?>"><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-grid"><button class="btn btn-light fw-semibold">Search</button></div>
    </form>
</section>

<div class="row g-3 mb-4 text-center">
    <div class="col-4"><div class="card stat-card"><div class="card-body">
        <div class="display-6 text-primary"><?= (int)$stats['jobs'] ?></div><div class="text-muted">Open jobs</div></div></div></div>
    <div class="col-4"><div class="card stat-card"><div class="card-body">
        <div class="display-6 text-primary"><?= (int)$stats['companies'] ?></div><div class="text-muted">Companies</div></div></div></div>
    <div class="col-4"><div class="card stat-card"><div class="card-body">
        <div class="display-6 text-primary"><?= (int)$stats['seekers'] ?></div><div class="text-muted">Job seekers</div></div></div></div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0">Latest openings</h2>
    <a href="<?= e(url('jobs.php')) ?>">View all jobs &rarr;</a>
</div>

<?php if (!$latest): ?>
    <div class="alert alert-info">No jobs have been posted yet. Check back soon!</div>
<?php endif; ?>

<div class="row g-3">
<?php foreach ($latest as $j): ?>
    <div class="col-md-6">
        <a class="text-decoration-none text-reset" href="<?= e(url('job.php?id=' . $j['job_id'])) ?>">
            <div class="card job-card h-100"><div class="card-body">
                <h3 class="h5 mb-1"><?= e($j['title']) ?></h3>
                <div class="text-muted mb-2"><?= e($j['company_name']) ?> &middot; <?= e($j['location']) ?></div>
                <span class="badge text-bg-light border"><?= e($j['job_type']) ?></span>
                <span class="badge text-bg-light border"><?= e(money_range($j['salary_min'], $j['salary_max'])) ?></span>
            </div></div>
        </a>
    </div>
<?php endforeach; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
