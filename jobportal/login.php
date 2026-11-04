<?php
require_once __DIR__ . '/includes/functions.php';

if (current_user()) {
    redirect(dashboard_url(current_user()['role']));
}

$error = '';
$email = '';

if (is_post()) {
    verify_csrf();
    $email    = strtolower(post_str('email', 150));
    $password = (string)($_POST['password'] ?? '');

    $u = q('SELECT * FROM users WHERE email = ?', [$email])->fetch();

    if ($u && password_verify($password, $u['password_hash'])) {
        if (!$u['is_active']) {
            $error = 'This account has been deactivated. Contact the administrator.';
        } else {
            login_user($u);
            redirect(dashboard_url($u['role']));
        }
    } else {
        $error = 'Invalid email or password.';
    }
}

$pageTitle = 'Login';
require __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center"><div class="col-md-6 col-lg-5">
<div class="card"><div class="card-body p-4">
    <h1 class="h4 mb-3">Log in</h1>
    <?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="mb-3"><label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" value="<?= e($email) ?>" required autofocus></div>
        <div class="mb-3"><label class="form-label">Password</label>
            <input type="password" class="form-control" name="password" required></div>
        <button class="btn btn-primary w-100">Log in</button>
    </form>
    <p class="text-center mt-3 mb-0">New here? <a href="<?= e(url('register.php')) ?>">Create an account</a></p>
</div></div>
</div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
