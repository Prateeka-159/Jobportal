<?php
require_once __DIR__ . '/../includes/functions.php';

$user   = require_role('seeker');
$seeker = current_seeker();
$sid    = (int)$seeker['seeker_id'];

if (is_post()) {
    verify_csrf();
    // A seeker may withdraw an application only while it is still unreviewed
    $aid = post_int('application_id');
    if ($aid) {
        $n = q("DELETE FROM applications WHERE application_id = ? AND seeker_id = ? AND status = 'applied'", [$aid, $sid])->rowCount();
        flash($n ? 'success' : 'warning', $n ? 'Application withdrawn.' : 'Only unreviewed applications can be withdrawn.');
    }
    redirect('seeker/applications.php');
}

$apps = q('SELECT a.application_id, a.status, a.applied_at, j.job_id, j.title, j.location,
                  e.company_name, r.title AS resume_title
           FROM applications a
           JOIN jobs j      ON j.job_id = a.job_id
           JOIN employers e ON e.employer_id = j.employer_id
           LEFT JOIN resumes r ON r.resume_id = a.resume_id
           WHERE a.seeker_id = ?
           ORDER BY a.applied_at DESC', [$sid])->fetchAll();

$pageTitle = 'My Applications';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3">My Applications</h1>

<?php if (!$apps): ?>
    <div class="alert alert-info">You haven't applied to any jobs yet. <a href="<?= e(url('jobs.php')) ?>">Browse jobs</a></div>
<?php else: ?>
<div class="table-responsive card"><table class="table table-hover align-middle mb-0">
    <thead><tr><th>Job</th><th>Company</th><th>Resume used</th><th>Applied on</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($apps as $a): ?>
        <tr>
            <td><a href="<?= e(url('job.php?id=' . $a['job_id'])) ?>"><?= e($a['title']) ?></a><br><small class="text-muted"><?= e($a['location']) ?></small></td>
            <td><?= e($a['company_name']) ?></td>
            <td><?= e($a['resume_title'] ?? '(deleted)') ?></td>
            <td><?= e(date('d M Y', strtotime($a['applied_at']))) ?></td>
            <td><?= status_badge($a['status']) ?></td>
            <td class="text-end">
                <?php if ($a['status'] === 'applied'): ?>
                <form method="post" onsubmit="return confirm('Withdraw this application?');"><?= csrf_field() ?>
                    <input type="hidden" name="application_id" value="<?= (int)$a['application_id'] ?>">
                    <button class="btn btn-sm btn-outline-danger">Withdraw</button></form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
