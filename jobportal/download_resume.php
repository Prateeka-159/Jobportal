<?php
/**
 * Streams a resume file after checking that the requester may see it:
 *  - the seeker who owns it,
 *  - the employer who received it through an application to one of their jobs,
 *  - an administrator.
 */
require_once __DIR__ . '/includes/functions.php';

$user = require_role('seeker', 'employer', 'admin');
$id   = ctype_digit((string)($_GET['id'] ?? '')) ? (int)$_GET['id'] : 0;

$resume = q('SELECT r.*, s.user_id AS owner_user_id
             FROM resumes r JOIN job_seekers s ON s.seeker_id = r.seeker_id
             WHERE r.resume_id = ?', [$id])->fetch();

$allowed = false;
if ($resume) {
    if ($user['role'] === 'admin') {
        $allowed = true;
    } elseif ($user['role'] === 'seeker') {
        $allowed = (int)$resume['owner_user_id'] === $user['id'];
    } else {
        $allowed = (bool)q('SELECT 1
                            FROM applications a
                            JOIN jobs j      ON j.job_id = a.job_id
                            JOIN employers e ON e.employer_id = j.employer_id
                            WHERE a.resume_id = ? AND e.user_id = ?
                            LIMIT 1', [$id, $user['id']])->fetch();
    }
}

$path = $resume ? UPLOAD_DIR . basename($resume['stored_name']) : '';
if (!$allowed || !is_file($path)) {
    http_response_code(404);
    exit('Resume not found.');
}

$ext   = strtolower(pathinfo($resume['stored_name'], PATHINFO_EXTENSION));
$types = [
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];
$download = $resume['file_name'] ?: ('resume.' . $ext);

header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
header('Content-Length: ' . filesize($path));
header('Content-Disposition: attachment; filename="' . str_replace(['"', "\r", "\n"], '', $download) . '"');
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
