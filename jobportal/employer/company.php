<?php
require_once __DIR__ . '/../includes/functions.php';

$user   = require_role('employer');
$emp    = current_employer();
$errors = [];

if (is_post()) {
    verify_csrf();
    $company = post_str('company_name', 150);
    $website = post_str('website', 200);

    if ($company === '') { $errors[] = 'Company name is required.'; }
    if ($website !== '' && !preg_match('#^https?://#i', $website)) { $errors[] = 'Website must start with http:// or https://'; }
    if ($website !== '' && !filter_var($website, FILTER_VALIDATE_URL)) { $errors[] = 'Enter a valid website URL.'; }

    if (!$errors) {
        q('UPDATE employers SET company_name = ?, industry = ?, website = ?, location = ?, description = ? WHERE employer_id = ?',
          [$company, post_str('industry', 100), $website, post_str('location', 100), post_str('description', 3000), $emp['employer_id']]);
        flash('success', 'Company profile saved.');
        redirect('employer/company.php');
    }
    $emp = array_merge($emp, [
        'company_name' => $company, 'industry' => post_str('industry', 100), 'website' => $website,
        'location' => post_str('location', 100), 'description' => post_str('description', 3000),
    ]);
}

$pageTitle = 'Company Profile';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3">Company Profile</h1>
<?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>
<form method="post" class="card card-body">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Company name</label>
            <input class="form-control" name="company_name" value="<?= e($emp['company_name']) ?>" required></div>
        <div class="col-md-6"><label class="form-label">Industry</label>
            <input class="form-control" name="industry" value="<?= e($emp['industry']) ?>"></div>
        <div class="col-md-6"><label class="form-label">Website</label>
            <input class="form-control" name="website" value="<?= e($emp['website']) ?>" placeholder="https://example.com"></div>
        <div class="col-md-6"><label class="form-label">Location</label>
            <input class="form-control" name="location" value="<?= e($emp['location']) ?>"></div>
        <div class="col-12"><label class="form-label">About the company</label>
            <textarea class="form-control" name="description" rows="5"><?= e($emp['description']) ?></textarea></div>
    </div>
    <div class="mt-3 d-flex align-items-center gap-3">
        <button class="btn btn-primary">Save</button>
        <span class="text-muted small">Approval status: <?= $emp['is_approved'] ? '<span class="badge text-bg-success">Approved</span>' : '<span class="badge text-bg-warning">Pending</span>' ?></span>
    </div>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
