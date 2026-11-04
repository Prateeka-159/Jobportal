<?php
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

if (is_post()) {
    verify_csrf();
    $id     = post_int('employer_id');
    $action = post_str('action', 10);
    if ($id && in_array($action, ['approve', 'revoke'], true)) {
        if ($action === 'approve') {
            q('UPDATE employers SET is_approved = TRUE WHERE employer_id = ?', [$id]);
            flash('success', 'Employer approved.');
        } else {
            q('UPDATE employers SET is_approved = FALSE WHERE employer_id = ?', [$id]);
            flash('success', 'Approval revoked. Their jobs are hidden from the public.');
        }
    }
    redirect('admin/employers.php');
}

$rows = q('SELECT e.employer_id, e.company_name, e.industry, e.location, e.is_approved,
                  u.full_name, u.email, u.created_at,
                  (SELECT COUNT(*) FROM jobs j WHERE j.employer_id = e.employer_id) AS jobs
           FROM employers e JOIN users u ON u.user_id = e.user_id
           ORDER BY e.is_approved ASC, u.created_at DESC')->fetchAll();

$pageTitle = 'Employers';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3">Employers</h1>
<div class="table-responsive card"><table class="table align-middle mb-0">
    <thead><tr><th>Company</th><th>Contact</th><th>Jobs</th><th>Registered</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><strong><?= e($r['company_name']) ?></strong><br><small class="text-muted"><?= e(trim(($r['industry'] ?? '') . ' ' . ($r['location'] ?? ''))) ?></small></td>
            <td><?= e($r['full_name']) ?><br><small class="text-muted"><?= e($r['email']) ?></small></td>
            <td><?= (int)$r['jobs'] ?></td>
            <td><?= e(date('d M Y', strtotime($r['created_at']))) ?></td>
            <td><?= $r['is_approved'] ? '<span class="badge text-bg-success">Approved</span>' : '<span class="badge text-bg-warning">Pending</span>' ?></td>
            <td class="text-end">
                <form method="post"><?= csrf_field() ?>
                    <input type="hidden" name="employer_id" value="<?= (int)$r['employer_id'] ?>">
                    <?php if ($r['is_approved']): ?>
                        <input type="hidden" name="action" value="revoke"><button class="btn btn-sm btn-outline-warning">Revoke</button>
                    <?php else: ?>
                        <input type="hidden" name="action" value="approve"><button class="btn btn-sm btn-success">Approve</button>
                    <?php endif; ?>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
