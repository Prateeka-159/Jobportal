<?php
require_once __DIR__ . '/includes/functions.php';

$kw       = trim((string)($_GET['q'] ?? ''));
$loc      = trim((string)($_GET['location'] ?? ''));
$cat      = ctype_digit((string)($_GET['category'] ?? '')) ? (int)$_GET['category'] : 0;
$type     = (string)($_GET['type'] ?? '');
$types    = ['Full-time', 'Part-time', 'Internship', 'Contract', 'Remote'];
$page     = max(1, (int)($_GET['page'] ?? 1));

$where  = ["j.status = 'open'", 'e.is_approved = TRUE', '(j.deadline IS NULL OR j.deadline >= CURRENT_DATE)'];
$params = [];

if ($kw !== '') {
    $where[] = '(j.title ILIKE :k1 OR j.skills_required ILIKE :k2 OR e.company_name ILIKE :k3)';
    $like = '%' . addcslashes($kw, '%_\\') . '%';
    $params[':k1'] = $params[':k2'] = $params[':k3'] = $like;
}
if ($loc !== '') {
    $where[] = 'j.location ILIKE :loc';
    $params[':loc'] = '%' . addcslashes($loc, '%_\\') . '%';
}
if ($cat > 0) {
    $where[] = 'j.category_id = :cat';
    $params[':cat'] = $cat;
}
if (in_array($type, $types, true)) {
    $where[] = 'j.job_type = :type';
    $params[':type'] = $type;
}
$whereSql = implode(' AND ', $where);

$total = (int)q("SELECT COUNT(*) FROM jobs j JOIN employers e ON e.employer_id = j.employer_id WHERE $whereSql", $params)->fetchColumn();
$pages = max(1, (int)ceil($total / PER_PAGE));
$page  = min($page, $pages);

$st = db()->prepare("SELECT j.job_id, j.title, j.location, j.job_type, j.salary_min, j.salary_max,
                            j.skills_required, j.created_at, e.company_name, c.name AS category
                     FROM jobs j
                     JOIN employers e ON e.employer_id = j.employer_id
                     LEFT JOIN categories c ON c.category_id = j.category_id
                     WHERE $whereSql
                     ORDER BY j.created_at DESC
                     LIMIT :lim OFFSET :off");
foreach ($params as $k => $v) {
    $st->bindValue($k, $v);
}
$st->bindValue(':lim', PER_PAGE, PDO::PARAM_INT);
$st->bindValue(':off', ($page - 1) * PER_PAGE, PDO::PARAM_INT);
$st->execute();
$jobs = $st->fetchAll();

$categories = q('SELECT category_id, name FROM categories ORDER BY name')->fetchAll();

// Skills of the logged-in seeker, used to show a match score
$seeker = current_seeker();

$qs = fn(int $p) => http_build_query(array_filter(
    ['q' => $kw, 'location' => $loc, 'category' => $cat ?: null, 'type' => $type, 'page' => $p],
    fn($v) => $v !== '' && $v !== null
));

$pageTitle = 'Browse Jobs';
require __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-3">Browse Jobs</h1>

<form class="card card-body mb-4" method="get">
    <div class="row g-2">
        <div class="col-md-3"><input class="form-control" name="q" value="<?= e($kw) ?>" placeholder="Title, skill or company"></div>
        <div class="col-md-2"><input class="form-control" name="location" value="<?= e($loc) ?>" placeholder="Location"></div>
        <div class="col-md-3">
            <select class="form-select" name="category">
                <option value="">All categories</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int)$c['category_id'] ?>" <?= $cat === (int)$c['category_id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="type">
                <option value="">Any type</option>
                <?php foreach ($types as $t): ?>
                    <option <?= $type === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-grid"><button class="btn btn-primary">Filter</button></div>
    </div>
</form>

<p class="text-muted"><?= $total ?> job<?= $total === 1 ? '' : 's' ?> found</p>

<?php if (!$jobs): ?>
    <div class="alert alert-info">No jobs match your search. Try different keywords or clear the filters.</div>
<?php endif; ?>

<div class="vstack gap-3">
<?php foreach ($jobs as $j): ?>
    <div class="card job-card"><div class="card-body d-md-flex justify-content-between">
        <div>
            <h2 class="h5 mb-1"><a class="text-decoration-none" href="<?= e(url('job.php?id=' . $j['job_id'])) ?>"><?= e($j['title']) ?></a></h2>
            <div class="text-muted mb-2"><?= e($j['company_name']) ?> &middot; <?= e($j['location']) ?>
                <?php if ($j['category']): ?>&middot; <?= e($j['category']) ?><?php endif; ?></div>
            <div>
                <?php foreach (array_slice(split_skills($j['skills_required']), 0, 6) as $s): ?>
                    <span class="skill-chip"><?= e($s) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="text-md-end mt-2 mt-md-0">
            <span class="badge text-bg-light border"><?= e($j['job_type']) ?></span>
            <div class="small text-muted mt-1"><?= e(money_range($j['salary_min'], $j['salary_max'])) ?></div>
            <?php if ($seeker): $m = match_score($j['skills_required'], $seeker['skills']); ?>
                <div class="small mt-1"><span class="badge text-bg-<?= $m >= 60 ? 'success' : ($m >= 30 ? 'warning' : 'secondary') ?>"><?= $m ?>% skill match</span></div>
            <?php endif; ?>
            <div class="small text-muted">Posted <?= e(date('d M Y', strtotime($j['created_at']))) ?></div>
        </div>
    </div></div>
<?php endforeach; ?>
</div>

<?php if ($pages > 1): ?>
<nav class="mt-4"><ul class="pagination justify-content-center">
    <?php for ($p = 1; $p <= $pages; $p++): ?>
        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
            <a class="page-link" href="?<?= e($qs($p)) ?>"><?= $p ?></a>
        </li>
    <?php endfor; ?>
</ul></nav>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
