<?php
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

if (is_post()) {
    verify_csrf();
    $action = post_str('action', 10);

    if ($action === 'add') {
        $name = post_str('name', 80);
        if ($name === '') {
            flash('danger', 'Category name is required.');
        } else {
            $n = q('INSERT INTO categories (name) VALUES (?) ON CONFLICT (name) DO NOTHING', [$name])->rowCount();
            flash($n ? 'success' : 'warning', $n ? 'Category added.' : 'That category already exists.');
        }
    } elseif ($action === 'rename') {
        $id   = post_int('category_id');
        $name = post_str('name', 80);
        if ($id && $name !== '') {
            try {
                q('UPDATE categories SET name = ? WHERE category_id = ?', [$name, $id]);
                flash('success', 'Category renamed.');
            } catch (PDOException $ex) {
                flash('danger', 'Another category already uses that name.');
            }
        }
    } elseif ($action === 'delete') {
        $id = post_int('category_id');
        if ($id) {
            q('DELETE FROM categories WHERE category_id = ?', [$id]);   // jobs keep existing, category becomes NULL
            flash('success', 'Category deleted.');
        }
    }
    redirect('admin/categories.php');
}

$cats = q('SELECT c.category_id, c.name, COUNT(j.job_id) AS jobs
           FROM categories c LEFT JOIN jobs j ON j.category_id = c.category_id
           GROUP BY c.category_id, c.name ORDER BY c.name')->fetchAll();

$pageTitle = 'Categories';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3">Job Categories</h1>

<form method="post" class="card card-body mb-4">
    <?= csrf_field() ?><input type="hidden" name="action" value="add">
    <div class="row g-2">
        <div class="col-md-9"><input class="form-control" name="name" placeholder="New category name" required></div>
        <div class="col-md-3 d-grid"><button class="btn btn-primary">Add category</button></div>
    </div>
</form>

<div class="table-responsive card"><table class="table align-middle mb-0">
    <thead><tr><th>Name</th><th>Jobs</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
    <?php foreach ($cats as $c): ?>
        <tr>
            <td>
                <form method="post" class="d-flex gap-2"><?= csrf_field() ?>
                    <input type="hidden" name="action" value="rename"><input type="hidden" name="category_id" value="<?= (int)$c['category_id'] ?>">
                    <input class="form-control form-control-sm" name="name" value="<?= e($c['name']) ?>" required>
                    <button class="btn btn-sm btn-outline-secondary">Rename</button>
                </form>
            </td>
            <td><?= (int)$c['jobs'] ?></td>
            <td class="text-end">
                <form method="post" onsubmit="return confirm('Delete this category? Its jobs will become uncategorised.');"><?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete"><input type="hidden" name="category_id" value="<?= (int)$c['category_id'] ?>">
                    <button class="btn btn-sm btn-outline-danger">Delete</button></form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
