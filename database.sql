-- ============================================================
--  CCDI Student Enrollment System — Database Schema
--  Import this file via phpMyAdmin or run: mysql -u root -p < database.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS ccdi_enrollment
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE ccdi_enrollment;

-- ── Administrators ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS admins (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    username     VARCHAR(60)  NOT NULL UNIQUE,
    email        VARCHAR(120) NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL,
    full_name    VARCHAR(120) NOT NULL,
    role         ENUM('superadmin','registrar','staff') DEFAULT 'staff',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default admin account is seeded by install.php (see instructions below).
-- Run install.php ONCE in your browser after importing this SQL file.

-- ── Programs / Courses ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS programs (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    code         VARCHAR(20)  NOT NULL UNIQUE,
    title        VARCHAR(150) NOT NULL,
    description  TEXT,
    duration     VARCHAR(40)  DEFAULT '4 years',
    max_slots    INT          DEFAULT 50,
    status       ENUM('Open','Closed','Full') DEFAULT 'Open',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO programs (code, title, description, duration, max_slots) VALUES
('BSCS',  'BS Computer Science',           'Core computing theory, algorithms and software engineering.',   '4 years', 50),
('BSIT',  'BS Information Technology',     'Practical IT skills, networking and systems administration.',   '4 years', 50),
('BSIS',  'BS Information Systems',        'Business-aligned information systems and data management.',    '4 years', 45),
('BSECE', 'BS Electronics Engineering',    'Electronics, communications and embedded systems.',             '5 years', 40),
('DICT',  'Diploma in ICT',                'Two-year diploma in information and communications technology.','2 years', 60)
ON DUPLICATE KEY UPDATE id=id;

-- ── Students ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS students (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    student_no       VARCHAR(20)  NOT NULL UNIQUE,
    -- Personal
    last_name        VARCHAR(60)  NOT NULL,
    first_name       VARCHAR(60)  NOT NULL,
    middle_name      VARCHAR(60),
    suffix           VARCHAR(10),
    date_of_birth    DATE         NOT NULL,
    place_of_birth   VARCHAR(120),
    gender           ENUM('Male','Female','Other') NOT NULL,
    civil_status     ENUM('Single','Married','Widowed','Separated') DEFAULT 'Single',
    nationality      VARCHAR(60)  DEFAULT 'Filipino',
    religion         VARCHAR(60),
    -- Contact
    email            VARCHAR(120),
    mobile           VARCHAR(20),
    telephone        VARCHAR(20),
    present_address  TEXT,
    permanent_address TEXT,
    -- Guardian
    guardian_name    VARCHAR(120),
    guardian_relation VARCHAR(60),
    guardian_mobile  VARCHAR(20),
    guardian_email   VARCHAR(120),
    guardian_address TEXT,
    -- Academic
    program_id       INT,
    year_level       ENUM('1st Year','2nd Year','3rd Year','4th Year','5th Year') DEFAULT '1st Year',
    semester         ENUM('1st Semester','2nd Semester','Summer') DEFAULT '1st Semester',
    school_year      VARCHAR(20)  NOT NULL,
    student_type     ENUM('New','Old','Transferee','Returnee') DEFAULT 'New',
    -- Previous School
    prev_school      VARCHAR(150),
    prev_school_addr VARCHAR(200),
    prev_program     VARCHAR(100),
    last_year_attended VARCHAR(20),
    honors_received  VARCHAR(120),
    -- Status
    status           ENUM('Enrolled','Pending','Dropped','Graduated','LOA') DEFAULT 'Pending',
    photo            VARCHAR(255),
    remarks          TEXT,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE SET NULL
);

-- ── Enrollment Records ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS enrollments (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    student_id   INT NOT NULL,
    program_id   INT NOT NULL,
    year_level   VARCHAR(20),
    semester     VARCHAR(30),
    school_year  VARCHAR(20),
    status       ENUM('Enrolled','Dropped','Completed','LOA') DEFAULT 'Enrolled',
    enrolled_by  INT,
    enrolled_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id)  REFERENCES students(id)  ON DELETE CASCADE,
    FOREIGN KEY (program_id)  REFERENCES programs(id)  ON DELETE CASCADE,
    FOREIGN KEY (enrolled_by) REFERENCES admins(id)    ON DELETE SET NULL
);

-- ── Update programs to match official CCDI flyer ─────────────
-- Run this after importing the main schema if programs are already seeded
INSERT INTO programs (code, title, description, duration, max_slots, status) VALUES
('BSCS',  'BS Computer Science',            'Bachelor of Science in Computer Science',         '4 years', 50, 'Open'),
('BSIT',  'BS Information Technology',      'Bachelor of Science in Information Technology',   '4 years', 50, 'Open'),
('BSIS',  'BS Information System',          'Bachelor of Science in Information System',       '4 years', 45, 'Open'),
('BSOAd', 'BS Office Administration',       'Bachelor of Science in Office Administration',    '4 years', 40, 'Open'),
('ACT',   'Associate in Computer Technology','2-Year Associate in Computer Technology',         '2 years', 60, 'Open')
ON DUPLICATE KEY UPDATE
  title=VALUES(title), description=VALUES(description),
  duration=VALUES(duration), status=VALUES(status);