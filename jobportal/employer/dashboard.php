<?php
require_once __DIR__ . '/../includes/functions.php';

$user = require_role('employer');
$emp  = current_employer();
$eid  = (int)$emp['employer_id'];

$c = q("SELECT
          (SELECT COUNT(*) FROM jobs WHERE employer_id = :e1)                                 AS jobs,
          (SELECT COUNT(*) FROM jobs WHERE employer_id = :e2 AND status = 'open')             AS open_jobs,
          (SELECT COUNT(*) FROM applications a JOIN jobs j ON j.job_id = a.job_id
             WHERE j.employer_id = :e3)                                                       AS applicants,
          (SELECT COUNT(*) FROM applications a JOIN jobs j ON j.job_id = a.job_id
             WHERE j.employer_id = :e4 AND a.status = 'applied')                              AS pending",
        [':e1' => $eid, ':e2' => $eid, ':e3' => $eid, ':e4' => $eid])->fetch();

$recent = q('SELECT a.application_id, a.status, a.applied_at, j.job_id, j.title, u.full_name
             FROM applications a
             JOIN jobs j ON j.job_id = a.job_id
             JOIN job_seekers s ON s.seeker_id = a.seeker_id
             JOIN users u ON u.user_id = s.user_id
             WHERE j.employer_id = ?
             ORDER BY a.applied_at DESC LIMIT 6', [$eid])->fetchAll();

$pageTitle = 'Employer Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3"><?= e($emp['company_name']) ?></h1>

<?php if (!$emp['is_approved']): ?>
    <div class="alert alert-warning">
        Your company is <strong>awaiting administrator approval</strong>. You can prepare your company profile now,
        but jobs can be posted only after approval.
    </div>
<?php endif; ?>

<div class="row g-3 mb-4 text-center">
    <?php foreach ([['Jobs posted', $c['jobs']], ['Open jobs', $c['open_jobs']], ['Total applicants', $c['applicants']], ['Awaiting review', $c['pending']]] as [$label, $n]): ?>
        <div class="col-6 col-md-3"><div class="card stat-card"><div class="card-body">
            <div class="display-6 text-primary"><?= (int)$n ?></div><div class="text-muted"><?= e($label) ?></div>
        </div></div></div>
    <?php endforeach; ?>
</div>

<div class="d-flex justify-content-between align-items-center mb-2">
    <h2 class="h5 mb-0">Latest applications</h2>
    <?php if ($emp['is_approved']): ?><a class="btn btn-primary btn-sm" href="<?= e(url('employer/post_job.php')) ?>">+ Post a job</a><?php endif; ?>
</div>

<?php if (!$recent): ?>
    <div class="alert alert-info">No applications received yet.</div>
<?php else: ?>
<div class="table-responsive card"><table class="table align-middle mb-0">
    <thead><tr><th>Candidate</th><th>Job</th><th>Applied</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($recent as $a): ?>
        <tr>
            <td><?= e($a['full_name']) ?></td>
            <td><?= e($a['title']) ?></td>
            <td><?= e(date('d M Y', strtotime($a['applied_at']))) ?></td>
            <td><?= status_badge($a['status']) ?></td>
            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= e(url('employer/applicants.php?job_id=' . $a['job_id'])) ?>">Review</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
