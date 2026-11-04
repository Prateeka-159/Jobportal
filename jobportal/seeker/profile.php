<?php
require_once __DIR__ . '/../includes/functions.php';

$user   = require_role('seeker');
$seeker = current_seeker();
$errors = [];

if (is_post()) {
    verify_csrf();
    $name  = post_str('full_name', 100);
    $phone = post_str('phone', 20);
    $exp   = post_int('experience_years');

    if ($name === '') { $errors[] = 'Full name is required.'; }
    if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) { $errors[] = 'Enter a valid phone number.'; }
    if ($exp === null || $exp < 0 || $exp > 60) { $errors[] = 'Experience must be between 0 and 60 years.'; }

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        q('UPDATE users SET full_name = ? WHERE user_id = ?', [$name, $user['id']]);
        q('UPDATE job_seekers
           SET phone = ?, location = ?, headline = ?, skills = ?, education = ?, experience_years = ?, summary = ?
           WHERE seeker_id = ?',
          [$phone, post_str('location', 100), post_str('headline', 150), post_str('skills', 1000),
           post_str('education', 1000), $exp, post_str('summary', 2000), $seeker['seeker_id']]);
        $pdo->commit();
        $_SESSION['user']['name'] = $name;
        flash('success', 'Profile updated.');
        redirect('seeker/profile.php');
    }
    // keep what the user typed
    $seeker = array_merge($seeker, [
        'phone' => $phone, 'location' => post_str('location', 100), 'headline' => post_str('headline', 150),
        'skills' => post_str('skills', 1000), 'education' => post_str('education', 1000),
        'experience_years' => $exp ?? 0, 'summary' => post_str('summary', 2000),
    ]);
    $user['name'] = $name;
}

$pageTitle = 'My Profile';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3">My Profile</h1>
<?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>

<form method="post" class="card card-body">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Full name</label>
            <input class="form-control" name="full_name" value="<?= e($user['name']) ?>" required></div>
        <div class="col-md-6"><label class="form-label">Email</label>
            <input class="form-control" value="<?= e($user['email']) ?>" disabled></div>
        <div class="col-md-4"><label class="form-label">Phone</label>
            <input class="form-control" name="phone" value="<?= e($seeker['phone']) ?>"></div>
        <div class="col-md-4"><label class="form-label">Location</label>
            <input class="form-control" name="location" value="<?= e($seeker['location']) ?>"></div>
        <div class="col-md-4"><label class="form-label">Years of experience</label>
            <input type="number" min="0" max="60" class="form-control" name="experience_years" value="<?= (int)$seeker['experience_years'] ?>"></div>
        <div class="col-12"><label class="form-label">Headline</label>
            <input class="form-control" name="headline" value="<?= e($seeker['headline']) ?>" placeholder="e.g. Fresher web developer"></div>
        <div class="col-12"><label class="form-label">Skills <span class="text-muted">(comma separated — used for job matching)</span></label>
            <input class="form-control" name="skills" value="<?= e($seeker['skills']) ?>" placeholder="PHP, PostgreSQL, JavaScript"></div>
        <div class="col-12"><label class="form-label">Education</label>
            <textarea class="form-control" name="education" rows="2"><?= e($seeker['education']) ?></textarea></div>
        <div class="col-12"><label class="form-label">Professional summary</label>
            <textarea class="form-control" name="summary" rows="4"><?= e($seeker['summary']) ?></textarea></div>
    </div>
    <div class="mt-3"><button class="btn btn-primary">Save profile</button></div>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
