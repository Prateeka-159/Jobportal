<?php
require_once __DIR__ . '/../includes/functions.php';

$user   = require_role('seeker');
$seeker = current_seeker();

if (!is_post()) {
    redirect('jobs.php');
}
verify_csrf();

$jobId    = post_int('job_id');
$resumeId = post_int('resume_id');
$cover    = post_str('cover_letter', 2000);
$back     = 'job.php?id=' . (int)$jobId;

$job = $jobId ? q("SELECT j.job_id, j.status, j.deadline
                   FROM jobs j JOIN employers e ON e.employer_id = j.employer_id
                   WHERE j.job_id = ? AND e.is_approved = TRUE", [$jobId])->fetch() : false;

if (!$job) {
    flash('danger', 'That job is not available.');
    redirect('jobs.php');
}
if ($job['status'] !== 'open' || ($job['deadline'] !== null && $job['deadline'] < date('Y-m-d'))) {
    flash('warning', 'Applications for this job are closed.');
    redirect($back);
}
if (!$resumeId || !q('SELECT 1 FROM resumes WHERE resume_id = ? AND seeker_id = ?', [$resumeId, $seeker['seeker_id']])->fetch()) {
    flash('danger', 'Please select one of your resumes.');
    redirect($back);
}

// UNIQUE (job_id, seeker_id) blocks duplicates even if two requests race
$inserted = q('INSERT INTO applications (job_id, seeker_id, resume_id, cover_letter)
               VALUES (?, ?, ?, ?)
               ON CONFLICT (job_id, seeker_id) DO NOTHING
               RETURNING application_id',
              [$jobId, $seeker['seeker_id'], $resumeId, $cover !== '' ? $cover : null])->fetchColumn();

if ($inserted) {
    flash('success', 'Application submitted. Good luck!');
    redirect('seeker/applications.php');
}
flash('info', 'You have already applied for this job.');
redirect($back);
