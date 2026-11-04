<?php
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

if (is_post()) {
    verify_csrf();
    $id = post_int('job_id');
    if ($id) {
        $n = q('DELETE FROM jobs WHERE job_id = ?', [$id])->rowCount();
        if ($n) { flash('success', 'Job removed.'); }
    }
    redirect('admin/jobs.php');
}

$jobs = q('SELECT j.job_id, j.title, j.location, j.status, j.created_at, e.company_name,
                  (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.job_id) AS applicants
           FROM jobs j JOIN employers e ON e.employer_id = j.employer_id
           ORDER BY j.created_at DESC')->fetchAll();

$pageTitle = 'All Jobs';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3">All Jobs</h1>
<?php if (!$jobs): ?>
    <div class="alert alert-info">No jobs have been posted yet.</div>
<?php else: ?>
<div class="table-responsive card"><table class="table align-middle mb-0">
    <thead><tr><th>Title</th><th>Company</th><th>Posted</th><th>Applicants</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($jobs as $j): ?>
        <tr>
            <td><a href="<?= e(url('job.php?id=' . $j['job_id'])) ?>"><?= e($j['title']) ?></a><br><small class="text-muted"><?= e($j['location']) ?></small></td>
            <td><?= e($j['company_name']) ?></td>
            <td><?= e(date('d M Y', strtotime($j['created_at']))) ?></td>
            <td><?= (int)$j['applicants'] ?></td>
            <td><?= status_badge($j['status']) ?></td>
            <td class="text-end">
                <form method="post" onsubmit="return confirm('Remove this job and its applications?');"><?= csrf_field() ?>
                    <input type="hidden" name="job_id" value="<?= (int)$j['job_id'] ?>">
                    <button class="btn btn-sm btn-outline-danger">Remove</button></form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
