<?php
/**
 * Application configuration.
 * Edit the values below to match your PostgreSQL setup.
 * (Environment variables JP_* override them, which is handy for testing.)
 */

// URL prefix of the app. Use '' when the project folder is the web root
// (e.g. `php -S localhost:8000`), or '/jobportal' under XAMPP/Apache htdocs.
define('BASE_URL', rtrim((string)(getenv('JP_BASE_URL') ?: ''), '/'));

define('DB_HOST', getenv('JP_DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('JP_DB_PORT') ?: '5432');
define('DB_NAME', getenv('JP_DB_NAME') ?: 'jobportal');
define('DB_USER', getenv('JP_DB_USER') ?: 'postgres');
define('DB_PASS', getenv('JP_DB_PASS') ?: 'postgres');

define('APP_NAME', 'JobConnect');

// Resume uploads
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/resumes/');
define('MAX_RESUME_SIZE', 2 * 1024 * 1024); // 2 MB
define('ALLOWED_RESUME_EXT', ['pdf', 'doc', 'docx']);

define('PER_PAGE', 8);

date_default_timezone_set('Asia/Kolkata');
