<?php
require_once __DIR__ . '/includes/functions.php';

$id = ctype_digit((string)($_GET['id'] ?? '')) ? (int)$_GET['id'] : 0;

$job = q("SELECT j.*, e.company_name, e.industry, e.website, e.description AS company_desc,
                 e.location AS company_location, e.is_approved, e.user_id AS employer_user_id,
                 c.name AS category
          FROM jobs j
          JOIN employers e ON e.employer_id = j.employer_id
          LEFT JOIN categories c ON c.category_id = j.category_id
          WHERE j.job_id = ?", [$id])->fetch();

$user = current_user();
$isOwnerOrAdmin = $job && $user && ($user['role'] === 'admin' || (int)$job['employer_user_id'] === $user['id']);

if (!$job || (!$job['is_approved'] && !$isOwnerOrAdmin)) {
    http_response_code(404);
    $pageTitle = 'Job not found';
    require __DIR__ . '/includes/header.php';
    echo '<div class="alert alert-warning">This job does not exist or is no longer available.</div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$expired = $job['deadline'] !== null && $job['deadline'] < date('Y-m-d');
$canApply = $job['status'] === 'open' && !$expired;

$seeker = current_seeker();
$resumes = [];
$existing = null;
if ($seeker) {
    $resumes = q('SELECT resume_id, title, file_name, is_default FROM resumes WHERE seeker_id = ? ORDER BY is_default DESC, uploaded_at DESC', [$seeker['seeker_id']])->fetchAll();
    $existing = q('SELECT status, applied_at FROM applications WHERE job_id = ? AND seeker_id = ?', [$id, $seeker['seeker_id']])->fetch();
}

$pageTitle = $job['title'];
require __DIR__ . '/includes/header.php';
?>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card"><div class="card-body">
            <h1 class="h3"><?= e($job['title']) ?></h1>
            <div class="text-muted mb-3"><?= e($job['company_name']) ?> &middot; <?= e($job['location']) ?></div>
            <div class="mb-3">
                <span class="badge text-bg-light border"><?= e($job['job_type']) ?></span>
                <?php if ($job['category']): ?><span class="badge text-bg-light border"><?= e($job['category']) ?></span><?php endif; ?>
                <?= status_badge($expired ? 'closed' : $job['status']) ?>
            </div>
            <h2 class="h6 text-uppercase text-muted">Description</h2>
            <p class="pre-wrap"><?= e($job['description']) ?></p>

            <?php if (split_skills($job['skills_required'])): ?>
                <h2 class="h6 text-uppercase text-muted mt-4">Skills required</h2>
                <?php foreach (split_skills($job['skills_required']) as $s): ?><span class="skill-chip"><?= e($s) ?></span><?php endforeach; ?>
            <?php endif; ?>
        </div></div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3"><div class="card-body">
            <dl class="mb-0">
                <dt>Salary</dt><dd><?= e(money_range($job['salary_min'], $job['salary_max'])) ?></dd>
                <dt>Apply before</dt><dd><?= $job['deadline'] ? e(date('d M Y', strtotime($job['deadline']))) : 'Open until filled' ?></dd>
                <dt>Posted on</dt><dd class="mb-0"><?= e(date('d M Y', strtotime($job['created_at']))) ?></dd>
            </dl>
        </div></div>

        <div class="card mb-3"><div class="card-body">
            <h2 class="h6">About <?= e($job['company_name']) ?></h2>
            <p class="small text-muted mb-1"><?= e($job['industry'] ?: '') ?></p>
            <p class="small pre-wrap"><?= e($job['company_desc'] ?: 'No company description provided.') ?></p>
            <?php if ($job['website']): ?><a class="small" href="<?= e($job['website']) ?>" target="_blank" rel="noopener noreferrer">Company website</a><?php endif; ?>
        </div></div>

        <div class="card"><div class="card-body">
            <?php if (!$user): ?>
                <p>Log in as a job seeker to apply.</p>
                <a class="btn btn-primary w-100" href="<?= e(url('login.php')) ?>">Login to apply</a>
            <?php elseif (!$seeker): ?>
                <p class="mb-0 text-muted">Only job seeker accounts can apply for jobs.</p>
            <?php elseif ($existing): ?>
                <p class="mb-1">You applied on <?= e(date('d M Y', strtotime($existing['applied_at']))) ?></p>
                <p class="mb-0">Status: <?= status_badge($existing['status']) ?></p>
            <?php elseif (!$canApply): ?>
                <p class="mb-0 text-muted">Applications for this job are closed.</p>
            <?php elseif (!$resumes): ?>
                <p>You need to upload a resume before applying.</p>
                <a class="btn btn-primary w-100" href="<?= e(url('seeker/resumes.php')) ?>">Upload resume</a>
            <?php else: ?>
                <form method="post" action="<?= e(url('seeker/apply.php')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="job_id" value="<?= (int)$job['job_id'] ?>">
                    <div class="mb-3">
                        <label class="form-label">Resume</label>
                        <select class="form-select" name="resume_id" required>
                            <?php foreach ($resumes as $r): ?>
                                <option value="<?= (int)$r['resume_id'] ?>" <?= $r['is_default'] ? 'selected' : '' ?>><?= e($r['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cover letter <span class="text-muted">(optional)</span></label>
                        <textarea class="form-control" name="cover_letter" rows="4" maxlength="2000"></textarea>
                    </div>
                    <button class="btn btn-success w-100">Apply now</button>
                </form>
            <?php endif; ?>
        </div></div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
