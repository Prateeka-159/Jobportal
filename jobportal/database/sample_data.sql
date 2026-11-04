-- Optional demo data. Run AFTER schema.sql:
--   psql -d jobportal -f database/sample_data.sql
-- Every account below uses the password:  Password@123

INSERT INTO users (full_name, email, password_hash, role) VALUES
 ('TechNova HR',   'hr@technova.com',     '$2y$10$dS9iozBfRb/YvqxaHRLrmeR81iGh1/dCh8zkh6RXEmeEhs/HimwvC', 'employer'),
 ('GreenLeaf HR',  'hr@greenleaf.com',    '$2y$10$dS9iozBfRb/YvqxaHRLrmeR81iGh1/dCh8zkh6RXEmeEhs/HimwvC', 'employer'),
 ('Arun Kumar',    'arun@example.com',    '$2y$10$dS9iozBfRb/YvqxaHRLrmeR81iGh1/dCh8zkh6RXEmeEhs/HimwvC', 'seeker'),
 ('Divya Lakshmi', 'divya@example.com',   '$2y$10$dS9iozBfRb/YvqxaHRLrmeR81iGh1/dCh8zkh6RXEmeEhs/HimwvC', 'seeker');

INSERT INTO employers (user_id, company_name, industry, website, location, description, is_approved)
SELECT user_id, 'TechNova Solutions', 'Software', 'https://technova.example.com', 'Coimbatore',
       'Product company building SaaS tools for small businesses.', TRUE
FROM users WHERE email = 'hr@technova.com';

INSERT INTO employers (user_id, company_name, industry, website, location, description, is_approved)
SELECT user_id, 'GreenLeaf Analytics', 'Data & Analytics', 'https://greenleaf.example.com', 'Chennai',
       'Analytics consultancy working with agriculture and retail clients.', FALSE
FROM users WHERE email = 'hr@greenleaf.com';

INSERT INTO job_seekers (user_id, phone, location, headline, skills, education, experience_years, summary)
SELECT user_id, '9876543210', 'Salem', 'Fresher web developer', 'PHP, PostgreSQL, HTML, CSS, JavaScript',
       'B.E. Computer Science', 0, 'Final-year student looking for a junior backend role.'
FROM users WHERE email = 'arun@example.com';

INSERT INTO job_seekers (user_id, phone, location, headline, skills, education, experience_years, summary)
SELECT user_id, '9123456780', 'Chennai', 'Data analyst', 'Python, SQL, Excel, Power BI',
       'B.Sc. Statistics', 2, 'Two years of experience building dashboards and reports.'
FROM users WHERE email = 'divya@example.com';

INSERT INTO jobs (employer_id, category_id, title, description, location, job_type,
                  salary_min, salary_max, skills_required, deadline)
SELECT e.employer_id, c.category_id,
       'PHP Developer', 'Build and maintain backend features for our SaaS platform using PHP and PostgreSQL.',
       'Coimbatore', 'Full-time', 300000, 500000, 'PHP, PostgreSQL, JavaScript, HTML, CSS',
       CURRENT_DATE + 30
FROM employers e, categories c
WHERE e.company_name = 'TechNova Solutions' AND c.name = 'Software Development';

INSERT INTO jobs (employer_id, category_id, title, description, location, job_type,
                  salary_min, salary_max, skills_required, deadline)
SELECT e.employer_id, c.category_id,
       'Junior Data Analyst', 'Prepare weekly reports and dashboards from SQL data sources.',
       'Remote', 'Remote', 250000, 400000, 'SQL, Excel, Python, Power BI',
       CURRENT_DATE + 20
FROM employers e, categories c
WHERE e.company_name = 'TechNova Solutions' AND c.name = 'Data Science & Analytics';

INSERT INTO jobs (employer_id, category_id, title, description, location, job_type,
                  salary_min, salary_max, skills_required, deadline)
SELECT e.employer_id, c.category_id,
       'Web Development Intern', 'Six-month internship working on our customer-facing web app.',
       'Coimbatore', 'Internship', 10000, 15000, 'HTML, CSS, JavaScript, PHP',
       CURRENT_DATE + 15
FROM employers e, categories c
WHERE e.company_name = 'TechNova Solutions' AND c.name = 'Information Technology';
