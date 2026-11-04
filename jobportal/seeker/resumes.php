<?php
require_once __DIR__ . '/../includes/functions.php';

$user   = require_role('seeker');
$seeker = current_seeker();
$sid    = (int)$seeker['seeker_id'];

if (is_post()) {
    verify_csrf();
    $action = post_str('action', 20);

    if ($action === 'upload') {
        $title = post_str('title', 100);
        $file  = $_FILES['resume'] ?? null;
        $err   = '';

        if ($title === '') {
            $err = 'Give the resume a title.';
        } elseif (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $err = ($file['error'] ?? 0) === UPLOAD_ERR_INI_SIZE || ($file['error'] ?? 0) === UPLOAD_ERR_FORM_SIZE
                ? 'File is too large (max 2 MB).' : 'Please choose a file to upload.';
        } elseif ($file['size'] > MAX_RESUME_SIZE) {
            $err = 'File is too large (max 2 MB).';
        } else {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ALLOWED_RESUME_EXT, true)) {
                $err = 'Only PDF, DOC and DOCX files are allowed.';
            } else {
                // Check the real content type too, not only the extension
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
                $okMime = [
                    'pdf'  => ['application/pdf'],
                    'doc'  => ['application/msword', 'application/x-ole-storage', 'application/octet-stream'],
                    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
                ];
                if (!in_array($mime, $okMime[$ext], true)) {
                    $err = 'The file content does not match its extension.';
                }
            }
        }

        if ($err === '') {
            if (!is_dir(UPLOAD_DIR)) { mkdir(UPLOAD_DIR, 0775, true); }
            $stored = bin2hex(random_bytes(16)) . '.' . $ext;
            if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $stored)) {
                $err = 'Could not save the file. Check folder permissions on uploads/resumes.';
            } else {
                $isFirst = (int)q('SELECT COUNT(*) FROM resumes WHERE seeker_id = ?', [$sid])->fetchColumn() === 0;
                $name = mb_substr(basename($file['name']), 0, 255);
                if ($isFirst) {
                    q('INSERT INTO resumes (seeker_id, title, file_name, stored_name, is_default) VALUES (?, ?, ?, ?, TRUE)',
                      [$sid, $title, $name, $stored]);
                } else {
                    q('INSERT INTO resumes (seeker_id, title, file_name, stored_name) VALUES (?, ?, ?, ?)',
                      [$sid, $title, $name, $stored]);
                }
                flash('success', 'Resume uploaded.');
            }
        }
        if ($err !== '') { flash('danger', $err); }

    } elseif ($action === 'default') {
        $rid = post_int('resume_id');
        if ($rid && q('SELECT 1 FROM resumes WHERE resume_id = ? AND seeker_id = ?', [$rid, $sid])->fetch()) {
            $pdo = db();
            $pdo->beginTransaction();
            q('UPDATE resumes SET is_default = FALSE WHERE seeker_id = ?', [$sid]);
            q('UPDATE resumes SET is_default = TRUE WHERE resume_id = ?', [$rid]);
            $pdo->commit();
            flash('success', 'Default resume updated.');
        }

    } elseif ($action === 'delete') {
        $rid = post_int('resume_id');
        $row = $rid ? q('SELECT * FROM resumes WHERE resume_id = ? AND seeker_id = ?', [$rid, $sid])->fetch() : false;
        if ($row) {
            $inUse = (int)q("SELECT COUNT(*) FROM applications WHERE resume_id = ? AND status = 'applied'", [$rid])->fetchColumn();
            if ($inUse > 0) {
                flash('warning', 'This resume is attached to pending applications, so it cannot be deleted yet.');
            } else {
                q('DELETE FROM resumes WHERE resume_id = ?', [$rid]);
                $path = UPLOAD_DIR . basename($row['stored_name']);
                if (is_file($path)) { unlink($path); }
                // keep one default if any resumes remain
                if ($row['is_default']) {
                    q('UPDATE resumes SET is_default = TRUE
                       WHERE resume_id = (SELECT resume_id FROM resumes WHERE seeker_id = ? ORDER BY uploaded_at DESC LIMIT 1)', [$sid]);
                }
                flash('success', 'Resume deleted.');
            }
        }
    }
    redirect('seeker/resumes.php');
}

$resumes = q('SELECT * FROM resumes WHERE seeker_id = ? ORDER BY is_default DESC, uploaded_at DESC', [$sid])->fetchAll();

$pageTitle = 'My Resumes';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3">My Resumes</h1>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card"><div class="card-body">
            <h2 class="h5">Upload a resume</h2>
            <form method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="upload">
                <div class="mb-3"><label class="form-label">Title</label>
                    <input class="form-control" name="title" placeholder="e.g. Web developer resume" required></div>
                <div class="mb-3"><label class="form-label">File <span class="text-muted">(PDF, DOC, DOCX — max 2 MB)</span></label>
                    <input type="file" class="form-control" name="resume" accept=".pdf,.doc,.docx" required></div>
                <button class="btn btn-primary">Upload</button>
            </form>
        </div></div>
    </div>
    <div class="col-lg-7">
        <?php if (!$resumes): ?>
            <div class="alert alert-info">No resumes uploaded yet.</div>
        <?php endif; ?>
        <div class="vstack gap-2">
        <?php foreach ($resumes as $r): ?>
            <div class="card"><div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <strong><?= e($r['title']) ?></strong>
                    <?php if ($r['is_default']): ?><span class="badge text-bg-success">Default</span><?php endif; ?><br>
                    <small class="text-muted"><?= e($r['file_name']) ?> &middot; <?= e(date('d M Y', strtotime($r['uploaded_at']))) ?></small>
                </div>
                <div class="d-flex gap-1">
                    <a class="btn btn-sm btn-outline-primary" href="<?= e(url('download_resume.php?id=' . $r['resume_id'])) ?>">Download</a>
                    <?php if (!$r['is_default']): ?>
                    <form method="post"><?= csrf_field() ?>
                        <input type="hidden" name="action" value="default"><input type="hidden" name="resume_id" value="<?= (int)$r['resume_id'] ?>">
                        <button class="btn btn-sm btn-outline-secondary">Make default</button></form>
                    <?php endif; ?>
                    <form method="post" onsubmit="return confirm('Delete this resume?');"><?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete"><input type="hidden" name="resume_id" value="<?= (int)$r['resume_id'] ?>">
                        <button class="btn btn-sm btn-outline-danger">Delete</button></form>
                </div>
            </div></div>
        <?php endforeach; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
