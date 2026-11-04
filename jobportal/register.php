<?php
require_once __DIR__ . '/includes/functions.php';

if (current_user()) {
    redirect(dashboard_url(current_user()['role']));
}

$errors = [];
$old = ['role' => 'seeker', 'full_name' => '', 'email' => '', 'company_name' => ''];

if (is_post()) {
    verify_csrf();
    $role     = post_str('role', 10);
    $name     = post_str('full_name', 100);
    $email    = strtolower(post_str('email', 150));
    $password = (string)($_POST['password'] ?? '');
    $confirm  = (string)($_POST['confirm'] ?? '');
    $company  = post_str('company_name', 150);
    $old = ['role' => $role, 'full_name' => $name, 'email' => $email, 'company_name' => $company];

    if (!in_array($role, ['seeker', 'employer'], true)) { $errors[] = 'Choose a valid account type.'; }
    if ($name === '')                                   { $errors[] = 'Full name is required.'; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))     { $errors[] = 'Enter a valid email address.'; }
    if (!valid_password($password))                     { $errors[] = 'Password must be at least 8 characters and contain a letter and a number.'; }
    if ($password !== $confirm)                         { $errors[] = 'Passwords do not match.'; }
    if ($role === 'employer' && $company === '')        { $errors[] = 'Company name is required for employers.'; }

    if (!$errors && q('SELECT 1 FROM users WHERE email = ?', [$email])->fetch()) {
        $errors[] = 'An account with this email already exists.';
    }

    if (!$errors) {
        $pdo = db();
        try {
            $pdo->beginTransaction();
            $uid = (int)q('INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, ?) RETURNING user_id',
                          [$name, $email, password_hash($password, PASSWORD_DEFAULT), $role])->fetchColumn();
            if ($role === 'seeker') {
                q('INSERT INTO job_seekers (user_id) VALUES (?)', [$uid]);
            } else {
                q('INSERT INTO employers (user_id, company_name) VALUES (?, ?)', [$uid, $company]);
            }
            $pdo->commit();
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $errors[] = 'Could not create the account. Please try again.';
        }
        if (!$errors) {
            flash('success', $role === 'employer'
                ? 'Account created. You can post jobs once an administrator approves your company. Please log in.'
                : 'Account created. Please log in.');
            redirect('login.php');
        }
    }
}

$pageTitle = 'Register';
require __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center"><div class="col-md-7 col-lg-6">
<div class="card"><div class="card-body p-4">
    <h1 class="h4 mb-3">Create an account</h1>
    <?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label d-block">I am a</label>
            <div class="btn-group w-100" role="group">
                <input type="radio" class="btn-check" name="role" id="r1" value="seeker" <?= $old['role'] !== 'employer' ? 'checked' : '' ?>>
                <label class="btn btn-outline-primary" for="r1">Job seeker</label>
                <input type="radio" class="btn-check" name="role" id="r2" value="employer" <?= $old['role'] === 'employer' ? 'checked' : '' ?>>
                <label class="btn btn-outline-primary" for="r2">Employer</label>
            </div>
        </div>
        <div class="mb-3"><label class="form-label">Full name</label>
            <input class="form-control" name="full_name" value="<?= e($old['full_name']) ?>" required></div>
        <div class="mb-3" id="companyBox"><label class="form-label">Company name <span class="text-muted">(employers only)</span></label>
            <input class="form-control" name="company_name" value="<?= e($old['company_name']) ?>"></div>
        <div class="mb-3"><label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" value="<?= e($old['email']) ?>" required></div>
        <div class="row">
            <div class="col-md-6 mb-3"><label class="form-label">Password</label>
                <input type="password" class="form-control" name="password" required></div>
            <div class="col-md-6 mb-3"><label class="form-label">Confirm password</label>
                <input type="password" class="form-control" name="confirm" required></div>
        </div>
        <button class="btn btn-primary w-100">Register</button>
    </form>
    <p class="text-center mt-3 mb-0">Already registered? <a href="<?= e(url('login.php')) ?>">Log in</a></p>
</div></div>
</div></div>
<script>
(function () {
    const box = document.getElementById('companyBox');
    const radios = document.querySelectorAll('input[name=role]');
    function sync() { box.style.display = document.getElementById('r2').checked ? '' : 'none'; }
    radios.forEach(r => r.addEventListener('change', sync));
    sync();
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
