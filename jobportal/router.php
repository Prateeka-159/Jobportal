<?php
/**
 * Router for PHP's built-in development server:
 *     php -S localhost:8000 router.php
 * Blocks direct access to private folders (the built-in server ignores .htaccess).
 * Not needed under Apache/XAMPP, where the .htaccess files do the same job.
 */
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

if (preg_match('#^/(uploads|config|includes|database)(/|$)#', $path)) {
    http_response_code(403);
    echo '403 Forbidden';
    return true;
}
return false; // serve the requested file / script normally
