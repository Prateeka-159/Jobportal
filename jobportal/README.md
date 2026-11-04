# Online Job Portal with Resume Management System

A web application built with **PHP 8 (PDO)** and **PostgreSQL**, with Bootstrap 5 for the interface.

## Features

| Role | What they can do |
|------|------------------|
| **Job seeker** | Register, build a profile with skills, upload several resumes (PDF/DOC/DOCX), choose a default, search and filter jobs, see a skill-match %, apply with a resume and cover letter, track and withdraw applications, get recommended jobs |
| **Employer** | Register a company (needs admin approval), post / edit / close / reopen / delete jobs, review applicants ranked by skill match, download resumes, shortlist / reject / hire |
| **Admin** | Dashboard with statistics, approve or revoke employers, activate / deactivate / delete users, remove jobs, manage categories |

## Requirements

- PHP 8.1 or newer with the `pdo_pgsql`, `fileinfo` and `mbstring` extensions
- PostgreSQL 12 or newer

## Setup

1. **Create the database and tables**

   ```bash
   createdb -U postgres jobportal
   psql -U postgres -d jobportal -f database/schema.sql
   psql -U postgres -d jobportal -f database/sample_data.sql   # optional demo data
   ```

   (pgAdmin users: create a database named `jobportal`, then run `schema.sql` and `sample_data.sql` in the Query Tool.)

2. **Configure the connection** in `config/config.php` (host, port, database name, user, password).

3. **Run the app**

   - Quick way (PHP built-in server), from the project folder:

     ```bash
     php -S localhost:8000 router.php
     ```

     Open <http://localhost:8000>.

   - XAMPP / Apache: copy the folder to `htdocs/jobportal`, enable `pdo_pgsql` and `pgsql` in `php.ini`,
     set `BASE_URL` to `'/jobportal'` in `config/config.php`, and open <http://localhost/jobportal>.

4. Make sure `uploads/resumes/` is writable by the web server.

## Demo accounts

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@jobportal.com | Admin@123 |
| Employer (approved) | hr@technova.com | Password@123 |
| Employer (pending approval) | hr@greenleaf.com | Password@123 |
| Job seeker | arun@example.com | Password@123 |
| Job seeker | divya@example.com | Password@123 |

Change the admin password after first use on any real deployment.

## Database design (7 tables)

```
users ──┬── job_seekers ──┬── resumes
        │                 └── applications ── jobs ── categories
        └── employers ───────────────────────────┘
```

- `users` – login accounts (role: admin / employer / seeker)
- `job_seekers`, `employers` – role-specific profile data (1:1 with `users`)
- `categories` – job categories
- `jobs` – postings (employer 1:N jobs, category 1:N jobs)
- `resumes` – uploaded files (seeker 1:N resumes)
- `applications` – links a seeker, a job and a resume; `UNIQUE (job_id, seeker_id)` prevents duplicate applications

## Security measures

- Passwords stored with `password_hash()` (bcrypt)
- All SQL uses PDO prepared statements
- Output escaped with `htmlspecialchars` (XSS)
- CSRF token on every POST form
- Role checks on every protected page; employers can only touch their own jobs and applicants
- Resume uploads: extension whitelist, MIME check, 2 MB limit, random file names, stored in a folder that is not served directly; downloads go through `download_resume.php`, which checks who is asking
- Session ID regenerated on login

## Project structure

```
config/        configuration
database/      schema.sql, sample_data.sql
includes/      shared helpers, header and footer
seeker/        job seeker pages
employer/      employer pages
admin/         admin pages
uploads/       stored resumes (private)
assets/        CSS
router.php     router for the PHP built-in server
```

## Ideas for future enhancements

Email notifications, password reset, resume parsing, saved jobs, interview scheduling, REST API / mobile app.
