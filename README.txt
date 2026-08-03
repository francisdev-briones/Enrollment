========================================================
  CCDI Student Enrollment System — XAMPP Setup Guide
========================================================

FOLDER STRUCTURE (place inside C:\xampp\htdocs\it2b\)
------------------------------------------------------
it2b/
├── index.php               ← Public enrollment form
├── install.php             ← Run ONCE to seed database
├── database.sql            ← Import this in phpMyAdmin
├── config/
│   ├── db.php              ← Database credentials
│   └── functions.php       ← Helpers & session
├── assets/
│   └── style.css           ← Admin panel styles
└── admin/
    ├── login.php
    ├── logout.php
    ├── dashboard.php
    ├── students.php
    ├── student_add.php
    ├── student_edit.php
    ├── student_view.php
    ├── student_delete.php
    ├── enroll.php
    ├── programs.php
    ├── users.php
    └── partials/
        ├── header.php
        └── footer.php

========================================================
  STEP-BY-STEP SETUP
========================================================

STEP 1 — Start XAMPP
  Open XAMPP Control Panel → Start Apache + MySQL

STEP 2 — Copy files
  Copy the entire it2b/ folder to:
  C:\xampp\htdocs\it2b\

STEP 3 — Import database
  Open browser → http://localhost/phpmyadmin
  Click "New" → create database named: ccdi_enrollment
  Click "Import" → choose database.sql → click Go

STEP 4 — Configure DB credentials
  Open config/db.php and set:
    define('DB_USER', 'root');   // your MySQL username
    define('DB_PASS', '');       // your MySQL password (blank by default in XAMPP)
    define('DB_NAME', 'ccdi_enrollment');

STEP 5 — Run installer (IMPORTANT — fixes admin password)
  Open browser → http://localhost/it2b/install.php
  You should see green checkmarks. This seeds:
    - All 5 programs (BSCS, BSIT, etc.)
    - Admin account with correct password hash

STEP 6 — Delete install.php
  After install succeeds, DELETE install.php for security!

STEP 7 — Access the system
  Enrollment form:  http://localhost/it2b/index.php
  Admin panel:      http://localhost/it2b/admin/login.php
    Username: admin
    Password: Admin@1234

========================================================
  COMMON ERRORS & FIXES
========================================================

ERROR: "Failed to open stream: No such file or directory"
  FIX: Make sure config/ folder is inside it2b/
       All require_once now use __DIR__ so paths are
       absolute — no more working-directory issues.

ERROR: "No such database" or MySQL errors
  FIX: Make sure you imported database.sql and
       ran install.php

ERROR: "Invalid credentials" on admin login
  FIX: Run install.php again — it resets the password.
       The issue was a wrong hash in the old SQL file.

ERROR: Form submits but nothing saves
  FIX: Check that program_id dropdown has options
       (means programs table is populated via install.php)
