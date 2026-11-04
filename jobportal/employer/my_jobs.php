<?php
require_once __DIR__ . '/../includes/functions.php';

$user = require_role('employer');
$emp  = current_employer();
$eid  = (int)$emp['employer_id'];

if (is_post()) {
    verify_csrf();
    $jid    = post_int('job_id');
    $action = post_str('action', 10);

    if ($jid && q('SELECT 1 FROM jobs WHERE job_id = ? AND employer_id = ?', [$jid, $eid])->fetch()) {
        if ($action === 'close') {
            q("UPDATE jobs SET status = 'closed' WHERE job_id = ?", [$jid]);
            flash('success', 'Job closed. It no longer accepts applications.');
        } elseif ($action === 'reopen') {
            q("UPDATE jobs SET status = 'open' WHERE job_id = ?", [$jid]);
            flash('success', 'Job reopened.');
        } elseif ($action === 'delete') {
            q('DELETE FROM jobs WHERE job_id = ?', [$jid]); // applications are removed by ON DELETE CASCADE
            flash('success', 'Job and its applications were deleted.');
        }
    }
    redirect('employer/my_jobs.php');
}

$jobs = q("SELECT j.job_id, j.title, j.location, j.job_type, j.status, j.deadline, j.created_at,
                  (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.job_id) AS applicants
           FROM jobs j WHERE j.employer_id = ?
           ORDER BY j.created_at DESC", [$eid])->fetchAll();

$pageTitle = 'My Jobs';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">My Jobs</h1>
    <?php if ($emp['is_approved']): ?><a class="btn btn-primary" href="<?= e(url('employer/post_job.php')) ?>">+ Post a job</a><?php endif; ?>
</div>

<?php if (!$jobs): ?>
    <div class="alert alert-info">You haven't posted any jobs yet.</div>
<?php else: ?>
<div class="table-responsive card"><table class="table align-middle mb-0">
    <thead><tr><th>Title</th><th>Type</th><th>Deadline</th><th>Applicants</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
    <?php foreach ($jobs as $j): ?>
        <tr>
            <td><a href="<?= e(url('job.php?id=' . $j['job_id'])) ?>"><?= e($j['title']) ?></a><br><small class="text-muted"><?= e($j['location']) ?></small></td>
            <td><?= e($j['job_type']) ?></td>
            <td><?= $j['deadline'] ? e(date('d M Y', strtotime($j['deadline']))) : '—' ?></td>
            <td><a href="<?= e(url('employer/applicants.php?job_id=' . $j['job_id'])) ?>"><?= (int)$j['applicants'] ?></a></td>
            <td><?= status_badge($j['status']) ?></td>
            <td class="text-end">
                <div class="d-inline-flex gap-1">
                    <a class="btn btn-sm btn-outline-primary" href="<?= e(url('employer/applicants.php?job_id=' . $j['job_id'])) ?>">Applicants</a>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('employer/post_job.php?id=' . $j['job_id'])) ?>">Edit</a>
                    <form method="post"><?= csrf_field() ?>
                        <input type="hidden" name="job_id" value="<?= (int)$j['job_id'] ?>">
                        <input type="hidden" name="action" value="<?= $j['status'] === 'open' ? 'close' : 'reopen' ?>">
                        <button class="btn btn-sm btn-outline-warning"><?= $j['status'] === 'open' ? 'Close' : 'Reopen' ?></button></form>
                    <form method="post" onsubmit="return confirm('Delete this job and all its applications?');"><?= csrf_field() ?>
                        <input type="hidden" name="job_id" value="<?= (int)$j['job_id'] ?>"><input type="hidden" name="action" value="delete">
                        <button class="btn btn-sm btn-outline-danger">Delete</button></form>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
