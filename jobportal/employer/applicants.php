<?php
require_once __DIR__ . '/../includes/functions.php';

$user = require_role('employer');
$emp  = current_employer();
$eid  = (int)$emp['employer_id'];

$jobId = ctype_digit((string)($_GET['job_id'] ?? $_POST['job_id'] ?? '')) ? (int)($_GET['job_id'] ?? $_POST['job_id']) : 0;
$job   = q('SELECT * FROM jobs WHERE job_id = ? AND employer_id = ?', [$jobId, $eid])->fetch();
if (!$job) {
    http_response_code(404);
    exit('Job not found.');
}

if (is_post()) {
    verify_csrf();
    $aid    = post_int('application_id');
    $status = post_str('status', 12);
    if ($aid && in_array($status, ['applied', 'shortlisted', 'rejected', 'hired'], true)) {
        // the job_id condition makes sure employers can only touch their own applicants
        $n = q('UPDATE applications SET status = ? WHERE application_id = ? AND job_id = ?', [$status, $aid, $jobId])->rowCount();
        if ($n) { flash('success', 'Application marked as ' . $status . '.'); }
    }
    redirect('employer/applicants.php?job_id=' . $jobId . '&filter=' . urlencode((string)($_POST['filter'] ?? '')));
}

$filter  = (string)($_GET['filter'] ?? '');
$allowed = ['applied', 'shortlisted', 'rejected', 'hired'];
$sql = 'SELECT a.application_id, a.status, a.applied_at, a.cover_letter, a.resume_id,
               u.full_name, u.email,
               s.phone, s.location, s.headline, s.skills, s.education, s.experience_years, s.summary,
               r.title AS resume_title
        FROM applications a
        JOIN job_seekers s ON s.seeker_id = a.seeker_id
        JOIN users u       ON u.user_id = s.user_id
        LEFT JOIN resumes r ON r.resume_id = a.resume_id
        WHERE a.job_id = :job';
$params = [':job' => $jobId];
if (in_array($filter, $allowed, true)) {
    $sql .= ' AND a.status = :st';
    $params[':st'] = $filter;
}
$sql .= ' ORDER BY a.applied_at DESC';
$apps = q($sql, $params)->fetchAll();

foreach ($apps as &$a) {
    $a['score'] = match_score($job['skills_required'], $a['skills']);
}
unset($a);
usort($apps, fn($x, $y) => $y['score'] <=> $x['score']);   // best match first

$pageTitle = 'Applicants – ' . $job['title'];
require __DIR__ . '/../includes/header.php';
?>
<nav class="mb-2"><a href="<?= e(url('employer/my_jobs.php')) ?>">&larr; My jobs</a></nav>
<h1 class="h3 mb-1">Applicants for <?= e($job['title']) ?></h1>
<p class="text-muted">Sorted by skill match with: <?php foreach (split_skills($job['skills_required']) as $s): ?><span class="skill-chip"><?= e($s) ?></span><?php endforeach; ?></p>

<ul class="nav nav-pills mb-3">
    <?php foreach (['' => 'All'] + array_combine($allowed, array_map('ucfirst', $allowed)) as $k => $label): ?>
        <li class="nav-item"><a class="nav-link <?= $filter === (string)$k ? 'active' : '' ?>"
            href="?job_id=<?= $jobId ?>&filter=<?= e($k) ?>"><?= e($label) ?></a></li>
    <?php endforeach; ?>
</ul>

<?php if (!$apps): ?>
    <div class="alert alert-info">No applicants found.</div>
<?php endif; ?>

<div class="vstack gap-3">
<?php foreach ($apps as $a): ?>
    <div class="card"><div class="card-body">
        <div class="d-md-flex justify-content-between">
            <div>
                <h2 class="h5 mb-0"><?= e($a['full_name']) ?> <?= status_badge($a['status']) ?></h2>
                <div class="text-muted"><?= e($a['headline'] ?: '') ?></div>
                <div class="small mt-1">
                    <?= e($a['email']) ?><?= $a['phone'] ? ' &middot; ' . e($a['phone']) : '' ?>
                    <?= $a['location'] ? ' &middot; ' . e($a['location']) : '' ?>
                    &middot; <?= (int)$a['experience_years'] ?> yr exp
                </div>
            </div>
            <div class="text-md-end mt-2 mt-md-0">
                <span class="badge text-bg-<?= $a['score'] >= 60 ? 'success' : ($a['score'] >= 30 ? 'warning' : 'secondary') ?> fs-6"><?= (int)$a['score'] ?>% match</span>
                <div class="small text-muted mt-1">Applied <?= e(date('d M Y', strtotime($a['applied_at']))) ?></div>
            </div>
        </div>

        <div class="mt-2">
            <?php foreach (split_skills($a['skills']) as $s): ?>
                <span class="skill-chip"><?= e($s) ?></span>
            <?php endforeach; ?>
        </div>

        <?php if ($a['education'] || $a['summary'] || $a['cover_letter']): ?>
        <details class="mt-2"><summary class="text-primary" style="cursor:pointer">Education, summary &amp; cover letter</summary>
            <?php if ($a['education']): ?><p class="mb-1 mt-2"><strong>Education:</strong> <span class="pre-wrap"><?= e($a['education']) ?></span></p><?php endif; ?>
            <?php if ($a['summary']): ?><p class="mb-1"><strong>Summary:</strong> <span class="pre-wrap"><?= e($a['summary']) ?></span></p><?php endif; ?>
            <?php if ($a['cover_letter']): ?><p class="mb-0"><strong>Cover letter:</strong> <span class="pre-wrap"><?= e($a['cover_letter']) ?></span></p><?php endif; ?>
        </details>
        <?php endif; ?>

        <div class="mt-3 d-flex flex-wrap gap-2 align-items-center">
            <?php if ($a['resume_id']): ?>
                <a class="btn btn-sm btn-outline-primary" href="<?= e(url('download_resume.php?id=' . $a['resume_id'])) ?>">Download resume</a>
            <?php else: ?>
                <span class="text-muted small">Resume no longer available</span>
            <?php endif; ?>
            <?php foreach (['shortlisted' => 'primary', 'rejected' => 'danger', 'hired' => 'success'] as $st => $cls): ?>
                <?php if ($a['status'] !== $st): ?>
                <form method="post" class="d-inline"><?= csrf_field() ?>
                    <input type="hidden" name="job_id" value="<?= $jobId ?>">
                    <input type="hidden" name="application_id" value="<?= (int)$a['application_id'] ?>">
                    <input type="hidden" name="filter" value="<?= e($filter) ?>">
                    <input type="hidden" name="status" value="<?= e($st) ?>">
                    <button class="btn btn-sm btn-<?= $cls ?>"><?= $st === 'shortlisted' ? 'Shortlist' : ucfirst($st === 'hired' ? 'Hire' : 'Reject') ?></button></form>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div></div>
<?php endforeach; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
