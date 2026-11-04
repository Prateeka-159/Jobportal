<?php
require_once __DIR__ . '/../includes/functions.php';

$admin = require_role('admin');

if (is_post()) {
    verify_csrf();
    $id     = post_int('user_id');
    $action = post_str('action', 10);
    $target = $id ? q('SELECT user_id, role FROM users WHERE user_id = ?', [$id])->fetch() : false;

    if (!$target) {
        flash('danger', 'User not found.');
    } elseif ((int)$target['user_id'] === (int)$admin['id'] || $target['role'] === 'admin') {
        flash('warning', 'Administrator accounts cannot be changed here.');
    } elseif ($action === 'toggle') {
        q('UPDATE users SET is_active = NOT is_active WHERE user_id = ?', [$id]);
        flash('success', 'Account status updated.');
    } elseif ($action === 'delete') {
        // collect stored resume files first; DB rows are removed by ON DELETE CASCADE
        $files = q('SELECT r.stored_name FROM resumes r JOIN job_seekers s ON s.seeker_id = r.seeker_id WHERE s.user_id = ?', [$id])->fetchAll();
        q('DELETE FROM users WHERE user_id = ?', [$id]);
        foreach ($files as $f) {
            $p = UPLOAD_DIR . basename($f['stored_name']);
            if (is_file($p)) { unlink($p); }
        }
        flash('success', 'User and related data deleted.');
    }
    redirect('admin/users.php?' . http_build_query(['role' => (string)($_POST['role_filter'] ?? '')]));
}

$roleFilter = (string)($_GET['role'] ?? '');
$sql = 'SELECT user_id, full_name, email, role, is_active, created_at FROM users';
$params = [];
if (in_array($roleFilter, ['admin', 'employer', 'seeker'], true)) {
    $sql .= ' WHERE role = ?';
    $params[] = $roleFilter;
}
$users = q($sql . ' ORDER BY created_at DESC', $params)->fetchAll();

$pageTitle = 'Users';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3">Users</h1>
<ul class="nav nav-pills mb-3">
    <?php foreach (['' => 'All', 'seeker' => 'Job seekers', 'employer' => 'Employers', 'admin' => 'Admins'] as $k => $label): ?>
        <li class="nav-item"><a class="nav-link <?= $roleFilter === (string)$k ? 'active' : '' ?>" href="?role=<?= e($k) ?>"><?= e($label) ?></a></li>
    <?php endforeach; ?>
</ul>

<div class="table-responsive card"><table class="table align-middle mb-0">
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Joined</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
        <tr>
            <td><?= e($u['full_name']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td><?= e(ucfirst($u['role'])) ?></td>
            <td><?= e(date('d M Y', strtotime($u['created_at']))) ?></td>
            <td><?= $u['is_active'] ? '<span class="badge text-bg-success">Active</span>' : '<span class="badge text-bg-secondary">Disabled</span>' ?></td>
            <td class="text-end">
                <?php if ($u['role'] !== 'admin'): ?>
                <div class="d-inline-flex gap-1">
                    <form method="post"><?= csrf_field() ?>
                        <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>"><input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="role_filter" value="<?= e($roleFilter) ?>">
                        <button class="btn btn-sm btn-outline-secondary"><?= $u['is_active'] ? 'Deactivate' : 'Activate' ?></button></form>
                    <form method="post" onsubmit="return confirm('Permanently delete this user and all related data?');"><?= csrf_field() ?>
                        <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>"><input type="hidden" name="action" value="delete">
                        <input type="hidden" name="role_filter" value="<?= e($roleFilter) ?>">
                        <button class="btn btn-sm btn-outline-danger">Delete</button></form>
                </div>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
