<?php
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$s = q("SELECT
          (SELECT COUNT(*) FROM users WHERE role = 'seeker')                 AS seekers,
          (SELECT COUNT(*) FROM users WHERE role = 'employer')               AS employers,
          (SELECT COUNT(*) FROM employers WHERE is_approved = FALSE)         AS pending_employers,
          (SELECT COUNT(*) FROM jobs)                                        AS jobs,
          (SELECT COUNT(*) FROM jobs WHERE status = 'open')                  AS open_jobs,
          (SELECT COUNT(*) FROM applications)                                AS applications,
          (SELECT COUNT(*) FROM resumes)                                     AS resumes")->fetch();

$byCategory = q("SELECT COALESCE(c.name, 'Uncategorised') AS name, COUNT(*) AS n
                 FROM jobs j LEFT JOIN categories c ON c.category_id = j.category_id
                 GROUP BY c.name ORDER BY n DESC LIMIT 6")->fetchAll();
$byStatus = q('SELECT status, COUNT(*) AS n FROM applications GROUP BY status ORDER BY n DESC')->fetchAll();
$maxCat   = max(array_merge([1], array_map(fn($r) => (int)$r['n'], $byCategory)));
$totalApp = max(1, array_sum(array_map(fn($r) => (int)$r['n'], $byStatus)));

$pageTitle = 'Admin Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3">Admin Dashboard</h1>

<?php if ($s['pending_employers'] > 0): ?>
    <div class="alert alert-warning d-flex justify-content-between align-items-center">
        <span><strong><?= (int)$s['pending_employers'] ?></strong> employer(s) waiting for approval.</span>
        <a class="btn btn-sm btn-warning" href="<?= e(url('admin/employers.php')) ?>">Review</a>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4 text-center">
    <?php foreach ([['Job seekers', $s['seekers']], ['Employers', $s['employers']], ['Jobs', $s['jobs']], ['Open jobs', $s['open_jobs']],
                    ['Applications', $s['applications']], ['Resumes', $s['resumes']]] as [$label, $n]): ?>
        <div class="col-6 col-md-4 col-lg-2"><div class="card stat-card"><div class="card-body">
            <div class="display-6 text-primary"><?= (int)$n ?></div><div class="text-muted small"><?= e($label) ?></div>
        </div></div></div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <div class="col-lg-6"><div class="card"><div class="card-body">
        <h2 class="h5">Jobs by category</h2>
        <?php if (!$byCategory): ?><p class="text-muted mb-0">No jobs yet.</p><?php endif; ?>
        <?php foreach ($byCategory as $r): ?>
            <div class="mb-2">
                <div class="d-flex justify-content-between small"><span><?= e($r['name']) ?></span><span><?= (int)$r['n'] ?></span></div>
                <div class="progress" style="height:8px"><div class="progress-bar" style="width: <?= round($r['n'] / $maxCat * 100) ?>%"></div></div>
            </div>
        <?php endforeach; ?>
    </div></div></div>
    <div class="col-lg-6"><div class="card"><div class="card-body">
        <h2 class="h5">Applications by status</h2>
        <?php if (!$byStatus): ?><p class="text-muted mb-0">No applications yet.</p><?php endif; ?>
        <?php foreach ($byStatus as $r): ?>
            <div class="mb-2">
                <div class="d-flex justify-content-between small"><span><?= e(ucfirst($r['status'])) ?></span><span><?= (int)$r['n'] ?> (<?= round($r['n'] / $totalApp * 100) ?>%)</span></div>
                <div class="progress" style="height:8px"><div class="progress-bar bg-success" style="width: <?= round($r['n'] / $totalApp * 100) ?>%"></div></div>
            </div>
        <?php endforeach; ?>
    </div></div></div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
