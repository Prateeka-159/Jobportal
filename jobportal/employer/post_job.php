<?php
/** Create a job, or edit one when ?id= is supplied. */
require_once __DIR__ . '/../includes/functions.php';

$user = require_role('employer');
$emp  = current_employer();
$eid  = (int)$emp['employer_id'];

$editId = ctype_digit((string)($_GET['id'] ?? '')) ? (int)$_GET['id'] : 0;
$job    = null;
if ($editId) {
    $job = q('SELECT * FROM jobs WHERE job_id = ? AND employer_id = ?', [$editId, $eid])->fetch();
    if (!$job) {
        http_response_code(404);
        exit('Job not found.');
    }
}

if (!$emp['is_approved']) {
    flash('warning', 'Your company must be approved by an administrator before you can post or edit jobs.');
    redirect('employer/dashboard.php');
}

$types      = ['Full-time', 'Part-time', 'Internship', 'Contract', 'Remote'];
$categories = q('SELECT category_id, name FROM categories ORDER BY name')->fetchAll();
$errors     = [];

$form = $job ?? ['title' => '', 'description' => '', 'location' => '', 'job_type' => 'Full-time', 'category_id' => '',
                 'salary_min' => '', 'salary_max' => '', 'skills_required' => '', 'deadline' => '', 'status' => 'open'];

if (is_post()) {
    verify_csrf();
    $form = [
        'title'           => post_str('title', 150),
        'description'     => post_str('description', 5000),
        'location'        => post_str('location', 100),
        'job_type'        => post_str('job_type', 20),
        'category_id'     => post_int('category_id'),
        'salary_min'      => post_int('salary_min'),
        'salary_max'      => post_int('salary_max'),
        'skills_required' => post_str('skills_required', 1000),
        'deadline'        => post_str('deadline', 10),
        'status'          => $job['status'] ?? 'open',
    ];

    if ($form['title'] === '')       { $errors[] = 'Job title is required.'; }
    if ($form['description'] === '') { $errors[] = 'Job description is required.'; }
    if ($form['location'] === '')    { $errors[] = 'Location is required.'; }
    if (!in_array($form['job_type'], $types, true)) { $errors[] = 'Choose a valid job type.'; }
    if ($form['category_id'] !== null && !q('SELECT 1 FROM categories WHERE category_id = ?', [$form['category_id']])->fetch()) {
        $errors[] = 'Choose a valid category.';
    }
    foreach (['salary_min', 'salary_max'] as $f) {
        if ($form[$f] !== null && $form[$f] < 0) { $errors[] = 'Salary cannot be negative.'; break; }
    }
    if ($form['salary_min'] !== null && $form['salary_max'] !== null && $form['salary_max'] < $form['salary_min']) {
        $errors[] = 'Maximum salary cannot be less than minimum salary.';
    }
    $deadline = null;
    if ($form['deadline'] !== '') {
        $d = DateTime::createFromFormat('Y-m-d', $form['deadline']);
        if (!$d || $d->format('Y-m-d') !== $form['deadline']) {
            $errors[] = 'Enter a valid deadline date.';
        } elseif (!$job && $form['deadline'] < date('Y-m-d')) {
            $errors[] = 'Deadline cannot be in the past.';
        } else {
            $deadline = $form['deadline'];
        }
    }

    if (!$errors) {
        $vals = [$form['category_id'], $form['title'], $form['description'], $form['location'], $form['job_type'],
                 $form['salary_min'], $form['salary_max'], $form['skills_required'], $deadline];
        if ($job) {
            q('UPDATE jobs SET category_id = ?, title = ?, description = ?, location = ?, job_type = ?,
                               salary_min = ?, salary_max = ?, skills_required = ?, deadline = ?
               WHERE job_id = ? AND employer_id = ?', [...$vals, $job['job_id'], $eid]);
            flash('success', 'Job updated.');
        } else {
            q('INSERT INTO jobs (category_id, title, description, location, job_type, salary_min, salary_max, skills_required, deadline, employer_id)
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [...$vals, $eid]);
            flash('success', 'Job posted.');
        }
        redirect('employer/my_jobs.php');
    }
}

$pageTitle = $job ? 'Edit Job' : 'Post a Job';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3"><?= e($pageTitle) ?></h1>
<?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>

<form method="post" class="card card-body">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-8"><label class="form-label">Job title</label>
            <input class="form-control" name="title" value="<?= e($form['title']) ?>" required></div>
        <div class="col-md-4"><label class="form-label">Category</label>
            <select class="form-select" name="category_id">
                <option value="">— none —</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int)$c['category_id'] ?>" <?= (string)$form['category_id'] === (string)$c['category_id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select></div>
        <div class="col-md-4"><label class="form-label">Location</label>
            <input class="form-control" name="location" value="<?= e($form['location']) ?>" required></div>
        <div class="col-md-4"><label class="form-label">Job type</label>
            <select class="form-select" name="job_type">
                <?php foreach ($types as $t): ?><option <?= $form['job_type'] === $t ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?>
            </select></div>
        <div class="col-md-4"><label class="form-label">Application deadline</label>
            <input type="date" class="form-control" name="deadline" value="<?= e($form['deadline']) ?>"></div>
        <div class="col-md-6"><label class="form-label">Minimum salary (₹ / year)</label>
            <input type="number" min="0" class="form-control" name="salary_min" value="<?= e($form['salary_min']) ?>"></div>
        <div class="col-md-6"><label class="form-label">Maximum salary (₹ / year)</label>
            <input type="number" min="0" class="form-control" name="salary_max" value="<?= e($form['salary_max']) ?>"></div>
        <div class="col-12"><label class="form-label">Skills required <span class="text-muted">(comma separated — used for candidate matching)</span></label>
            <input class="form-control" name="skills_required" value="<?= e($form['skills_required']) ?>" placeholder="PHP, PostgreSQL, JavaScript"></div>
        <div class="col-12"><label class="form-label">Description</label>
            <textarea class="form-control" name="description" rows="8" required><?= e($form['description']) ?></textarea></div>
    </div>
    <div class="mt-3 d-flex gap-2">
        <button class="btn btn-primary"><?= $job ? 'Save changes' : 'Publish job' ?></button>
        <a class="btn btn-outline-secondary" href="<?= e(url('employer/my_jobs.php')) ?>">Cancel</a>
    </div>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
