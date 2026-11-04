-- Online Job Portal with Resume Management System
-- PostgreSQL schema
-- Usage:  createdb jobportal && psql -d jobportal -f database/schema.sql

DROP TABLE IF EXISTS applications CASCADE;
DROP TABLE IF EXISTS resumes      CASCADE;
DROP TABLE IF EXISTS jobs         CASCADE;
DROP TABLE IF EXISTS categories   CASCADE;
DROP TABLE IF EXISTS employers    CASCADE;
DROP TABLE IF EXISTS job_seekers  CASCADE;
DROP TABLE IF EXISTS users        CASCADE;

CREATE TABLE users (
    user_id        SERIAL PRIMARY KEY,
    full_name      VARCHAR(100) NOT NULL,
    email          VARCHAR(150) NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,
    role           VARCHAR(10)  NOT NULL CHECK (role IN ('admin', 'employer', 'seeker')),
    is_active      BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at     TIMESTAMP    NOT NULL DEFAULT NOW()
);

CREATE TABLE job_seekers (
    seeker_id         SERIAL PRIMARY KEY,
    user_id           INT NOT NULL UNIQUE REFERENCES users(user_id) ON DELETE CASCADE,
    phone             VARCHAR(20),
    location          VARCHAR(100),
    headline          VARCHAR(150),
    skills            TEXT,
    education         TEXT,
    experience_years  INT NOT NULL DEFAULT 0 CHECK (experience_years >= 0),
    summary           TEXT
);

CREATE TABLE employers (
    employer_id   SERIAL PRIMARY KEY,
    user_id       INT NOT NULL UNIQUE REFERENCES users(user_id) ON DELETE CASCADE,
    company_name  VARCHAR(150) NOT NULL,
    industry      VARCHAR(100),
    website       VARCHAR(200),
    location      VARCHAR(100),
    description   TEXT,
    is_approved   BOOLEAN NOT NULL DEFAULT FALSE
);

CREATE TABLE categories (
    category_id  SERIAL PRIMARY KEY,
    name         VARCHAR(80) NOT NULL UNIQUE
);

CREATE TABLE jobs (
    job_id           SERIAL PRIMARY KEY,
    employer_id      INT NOT NULL REFERENCES employers(employer_id) ON DELETE CASCADE,
    category_id      INT REFERENCES categories(category_id) ON DELETE SET NULL,
    title            VARCHAR(150) NOT NULL,
    description      TEXT NOT NULL,
    location         VARCHAR(100) NOT NULL,
    job_type         VARCHAR(20)  NOT NULL DEFAULT 'Full-time'
                     CHECK (job_type IN ('Full-time', 'Part-time', 'Internship', 'Contract', 'Remote')),
    salary_min       INT CHECK (salary_min >= 0),
    salary_max       INT CHECK (salary_max >= 0),
    skills_required  TEXT,
    deadline         DATE,
    status           VARCHAR(10) NOT NULL DEFAULT 'open' CHECK (status IN ('open', 'closed')),
    created_at       TIMESTAMP   NOT NULL DEFAULT NOW(),
    CHECK (salary_max IS NULL OR salary_min IS NULL OR salary_max >= salary_min)
);

CREATE TABLE resumes (
    resume_id    SERIAL PRIMARY KEY,
    seeker_id    INT NOT NULL REFERENCES job_seekers(seeker_id) ON DELETE CASCADE,
    title        VARCHAR(100) NOT NULL,
    file_name    VARCHAR(255) NOT NULL,   -- original name shown to users
    stored_name  VARCHAR(255) NOT NULL,   -- random name on disk
    uploaded_at  TIMESTAMP NOT NULL DEFAULT NOW(),
    is_default   BOOLEAN   NOT NULL DEFAULT FALSE
);

CREATE TABLE applications (
    application_id  SERIAL PRIMARY KEY,
    job_id          INT NOT NULL REFERENCES jobs(job_id)             ON DELETE CASCADE,
    seeker_id       INT NOT NULL REFERENCES job_seekers(seeker_id)   ON DELETE CASCADE,
    resume_id       INT REFERENCES resumes(resume_id)                ON DELETE SET NULL,
    cover_letter    TEXT,
    status          VARCHAR(12) NOT NULL DEFAULT 'applied'
                    CHECK (status IN ('applied', 'shortlisted', 'rejected', 'hired')),
    applied_at      TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE (job_id, seeker_id)
);

CREATE INDEX idx_jobs_status_created  ON jobs (status, created_at DESC);
CREATE INDEX idx_jobs_category        ON jobs (category_id);
CREATE INDEX idx_jobs_employer        ON jobs (employer_id);
CREATE INDEX idx_applications_job     ON applications (job_id);
CREATE INDEX idx_applications_seeker  ON applications (seeker_id);
CREATE INDEX idx_resumes_seeker       ON resumes (seeker_id);

-- ---------------------------------------------------------------
-- Master data
-- ---------------------------------------------------------------
INSERT INTO categories (name) VALUES
 ('Information Technology'), ('Software Development'), ('Data Science & Analytics'),
 ('Marketing & Sales'), ('Finance & Accounting'), ('Human Resources'),
 ('Design & Creative'), ('Education & Training'), ('Healthcare'),
 ('Customer Support'), ('Engineering & Manufacturing');

-- Default administrator   (email: admin@jobportal.com   password: Admin@123)
INSERT INTO users (full_name, email, password_hash, role)
VALUES ('System Admin', 'admin@jobportal.com',
        '$2y$10$HigEFDHjppwvWJhrBmaYM.HKIPXBwkAoA.3Fi4BakRoVdDzvE2nHu', 'admin');
