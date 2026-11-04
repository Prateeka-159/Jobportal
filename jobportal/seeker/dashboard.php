<?php
require_once __DIR__ . '/../includes/functions.php';

$user   = require_role('seeker');
$seeker = current_seeker();
$sid    = (int)$seeker['seeker_id'];

$counts = q("SELECT COUNT(*) AS total,
                    COUNT(*) FILTER (WHERE status = 'shortlisted') AS shortlisted,
                    COUNT(*) FILTER (WHERE status = 'hired')       AS hired,
                    COUNT(*) FILTER (WHERE status = 'rejected')    AS rejected
             FROM applications WHERE seeker_id = ?", [$sid])->fetch();
$resumeCount = (int)q('SELECT COUNT(*) FROM resumes WHERE seeker_id = ?', [$sid])->fetchColumn();

$recent = q('SELECT a.application_id, a.status, a.applied_at, j.job_id, j.title, e.company_name
             FROM applications a
             JOIN jobs j ON j.job_id = a.job_id
             JOIN employers e ON e.employer_id = j.employer_id
             WHERE a.seeker_id = ?
             ORDER BY a.applied_at DESC LIMIT 5', [$sid])->fetchAll();

// Profile completeness
$fields = ['phone', 'location', 'headline', 'skills', 'education', 'summary'];
$filled = count(array_filter($fields, fn($f) => trim((string)$seeker[$f]) !== ''));
$pct    = (int)round(($filled + ($resumeCount > 0 ? 1 : 0)) / (count($fields) + 1) * 100);

// Recommended jobs by skill match
$candidates = q("SELECT j.job_id, j.title, j.location, j.skills_required, e.company_name
                 FROM jobs j JOIN employers e ON e.employer_id = j.employer_id
                 WHERE j.status = 'open' AND e.is_approved = TRUE
                   AND (j.deadline IS NULL OR j.deadline >= CURRENT_DATE)
                   AND NOT EXISTS (SELECT 1 FROM applications a WHERE a.job_id = j.job_id AND a.seeker_id = ?)
                 ORDER BY j.created_at DESC LIMIT 50", [$sid])->fetchAll();
foreach ($candidates as &$c) {
    $c['score'] = match_score($c['skills_required'], $seeker['skills']);
}
unset($c);
usort($candidates, fn($a, $b) => $b['score'] <=> $a['score']);
$recommended = array_filter(array_slice($candidates, 0, 5), fn($c) => $c['score'] > 0);

$pageTitle = 'Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Welcome, <?= e($user['name']) ?></h1>

<div class="row g-3 mb-4 text-center">
    <?php foreach ([['Applications', $counts['total']], ['Shortlisted', $counts['shortlisted']], ['Hired', $counts['hired']], ['Resumes', $resumeCount]] as [$label, $n]): ?>
        <div class="col-6 col-md-3"><div class="card stat-card"><div class="card-body">
            <div class="display-6 text-primary"><?= (int)$n ?></div><div class="text-muted"><?= e($label) ?></div>
        </div></div></div>
    <?php endforeach; ?>
</div>

<?php if ($pct < 100): ?>
<div class="card mb-4"><div class="card-body">
    <div class="d-flex justify-content-between"><strong>Profile completeness</strong><span><?= $pct ?>%</span></div>
    <div class="progress my-2"><div class="progress-bar" style="width: <?= $pct ?>%"></div></div>
    <a href="<?= e(url('seeker/profile.php')) ?>">Complete your profile</a> and
    <a href="<?= e(url('seeker/resumes.php')) ?>">upload a resume</a> to get better job matches.
</div></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-7">
        <h2 class="h5">Recent applications</h2>
        <?php if (!$recent): ?>
            <div class="alert alert-info">You haven't applied to any jobs yet. <a href="<?= e(url('jobs.php')) ?>">Browse jobs</a></div>
        <?php else: ?>
        <div class="list-group">
            <?php foreach ($recent as $a): ?>
                <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                   href="<?= e(url('job.php?id=' . $a['job_id'])) ?>">
                    <span><strong><?= e($a['title']) ?></strong><br><small class="text-muted"><?= e($a['company_name']) ?> &middot; <?= e(date('d M Y', strtotime($a['applied_at']))) ?></small></span>
                    <?= status_badge($a['status']) ?>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <div class="col-lg-5">
        <h2 class="h5">Recommended for your skills</h2>
        <?php if (!$recommended): ?>
            <p class="text-muted">Add skills to your profile to see matching jobs here.</p>
        <?php else: ?>
        <div class="list-group">
            <?php foreach ($recommended as $r): ?>
                <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                   href="<?= e(url('job.php?id=' . $r['job_id'])) ?>">
                    <span><?= e($r['title']) ?><br><small class="text-muted"><?= e($r['company_name']) ?> &middot; <?= e($r['location']) ?></small></span>
                    <span class="badge text-bg-success"><?= (int)$r['score'] ?>%</span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
