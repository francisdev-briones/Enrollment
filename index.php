<?php
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/db.php';

$success = $error = '';
$formData = [];

$programs = db()->query("SELECT id, code, title FROM programs WHERE status='Open' ORDER BY code")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll_submit'])) {
    $required = ['last_name','first_name','date_of_birth','gender','program_id','school_year','semester','student_type'];
    $formData = $_POST;
    $missing  = [];
    foreach ($required as $f) {
        if (empty(trim($_POST[$f] ?? ''))) $missing[] = $f;
    }
    if ($missing) {
        $error = 'Please fill in all required fields.';
    } else {
        $d = db();
        do {
            $sno = generateStudentNo();
            $ck  = $d->prepare("SELECT id FROM students WHERE student_no=?");
            $ck->bind_param('s', $sno); $ck->execute(); $ck->store_result();
        } while ($ck->num_rows > 0);

        $stmt = $d->prepare("
            INSERT INTO students
            (student_no, last_name, first_name, middle_name, suffix,
             date_of_birth, place_of_birth, gender, civil_status, nationality, religion,
             email, mobile, telephone, present_address, permanent_address,
             guardian_name, guardian_relation, guardian_mobile, guardian_email, guardian_address,
             program_id, year_level, semester, school_year, student_type,
             prev_school, prev_school_addr, prev_program, last_year_attended, honors_received,
             status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'Pending')
        ");
        $p = [
            $sno,
            post('last_name'), post('first_name'), post('middle_name'), post('suffix'),
            post('date_of_birth'), post('place_of_birth'), post('gender'), post('civil_status'),
            post('nationality') ?: 'Filipino', post('religion'),
            post('email'), post('mobile'), post('telephone'),
            post('present_address'), post('permanent_address'),
            post('guardian_name'), post('guardian_relation'), post('guardian_mobile'),
            post('guardian_email'), post('guardian_address'),
            (int)$_POST['program_id'], post('year_level'), post('semester'),
            post('school_year'), post('student_type'),
            post('prev_school'), post('prev_school_addr'), post('prev_program'),
            post('last_year_attended'), post('honors_received'),
        ];
        $types = 'ssssssssssssssssssssssissssssss';
        $stmt->bind_param($types, ...$p);
        if ($stmt->execute()) {
            $success  = $sno;
            $formData = [];
        } else {
            $error = 'Submission failed: ' . $d->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Enrollment — CCDI Legazpi</title>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800;900&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
/* ══════════════════════════════════════════════════════════
   CCDI LEGAZPI — Official Brand Stylesheet
   Colors: Royal Blue #1a3a8f | Red #c0392b | White #ffffff
   ══════════════════════════════════════════════════════════ */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --blue:      #1a3a8f;
  --blue-dark: #0f2460;
  --blue-mid:  #2451b3;
  --blue-light:#3d6fd4;
  --red:       #c0392b;
  --red-dark:  #96281b;
  --red-light: #e74c3c;
  --white:     #ffffff;
  --offwhite:  #f4f6fb;
  --gray-lt:   #e8ecf4;
  --gray:      #6b7280;
  --gray-dk:   #374151;
  --border:    #d1d9eb;
  --text:      #1e293b;
  --shadow:    0 4px 24px rgba(26,58,143,.13);
  --shadow-lg: 0 12px 48px rgba(26,58,143,.18);
}

html { scroll-behavior: smooth; }
body { font-family: 'Open Sans', sans-serif; background: var(--offwhite); color: var(--text); }

/* ── NAVBAR ────────────────────────────────────────────────── */
.navbar {
  background: var(--blue);
  padding: 0 3rem;
  display: flex; align-items: stretch; justify-content: space-between;
  position: sticky; top: 0; z-index: 100;
  box-shadow: 0 2px 16px rgba(15,36,96,.35);
  height: 65px;
  margin-bottom:-3rem;
}
.navbar-brand {
  display: flex; align-items: center; gap: 1rem;
  padding: .8rem 0; text-decoration: none;
}
/* SVG logo mark replaces the image */
.ccdi-logo-svg { width: 52px; height: 52px; flex-shrink: 0; }
.brand-text { line-height: 1.15; }
.brand-text strong {
  display: block; color: var(--white);
  font-family: 'Montserrat', sans-serif;
  font-size: .82rem; font-weight: 800; letter-spacing: .02em;
  text-transform: uppercase;
}
.brand-text span { font-size: .65rem; color: rgba(255,255,255,.55); letter-spacing: .04em; }
.navbar-links { display: flex; align-items: center; gap: 0; }
.navbar-links a {
  color: rgba(255,255,255,.78); font-size: .84rem; font-weight: 600;
  padding: 0 1.1rem; height: 100%;
  display: flex; align-items: center;
  text-decoration: none; border-bottom: 3px solid transparent;
  transition: all .2s; letter-spacing: .02em;
}
.navbar-links a:hover { color: #fff; border-bottom-color: var(--red); background: rgba(255,255,255,.06); }
.navbar-links .btn-enroll {
  background: var(--red); color: #fff; margin-left: .5rem;
  padding: 0 1.4rem; border-bottom: none; border-radius: 0;
  font-weight: 700; letter-spacing: .04em;
}
.navbar-links .btn-enroll:hover { background: var(--red-dark); }

/* ── HERO ──────────────────────────────────────────────────── */
.hero {
  background: linear-gradient(135deg, var(--blue-dark) 0%, var(--blue) 55%, var(--blue-mid) 100%);
  position: relative; overflow: hidden;
  min-height: 88vh;
  display: flex; align-items: center;
}
/* Decorative circles echoing the CCDI logo rings */
.hero::before {
  content: '';
  position: absolute; top: -120px; right: -120px;
  width: 600px; height: 600px; border-radius: 50%;
  border: 80px solid rgba(255,255,255,.04);
  pointer-events: none;
}
.hero::after {
  content: '';
  position: absolute; top: -40px; right: -40px;
  width: 420px; height: 420px; border-radius: 50%;
  border: 50px solid rgba(255,255,255,.06);
  pointer-events: none;
}
.hero-grid {
  position: absolute; inset: 0; opacity: .04;
  background-image:
    linear-gradient(rgba(255,255,255,.8) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,.8) 1px, transparent 1px);
  background-size: 48px 48px;
}

.hero-inner {
  position: relative; z-index: 2;
  max-width: 1160px; margin: 0 auto;
  padding: 5rem 2rem 4rem;
  display: grid; grid-template-columns: 1fr auto;
  gap: 3rem; align-items: center;
}
.hero-content {}
.hero-badge {
  display: inline-flex; align-items: center; gap: .5rem;
  background: rgba(192,57,43,.2); border: 1px solid rgba(192,57,43,.45);
  color: #ff8a7a; border-radius: 20px;
  padding: .35rem 1rem; font-size: .72rem; font-weight: 700;
  letter-spacing: .08em; text-transform: uppercase;
  margin-bottom: 1.4rem;
  margin-left:-5rem;
  animation: fadeUp .5s ease both;
}
.hero-badge .dot { width: 6px; height: 6px; border-radius: 50%; background: var(--red-light); animation: blink 1.4s infinite; }
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:.2} }
.hero-title {
  font-family: 'Montserrat', sans-serif;
  font-size: clamp(2rem, 4.5vw, 3.6rem);
  font-weight: 900; color: var(--white);
  line-height: 1.08; letter-spacing: -.01em;
  margin-bottom: 1rem;
  margin-left:-5rem;
  animation: fadeUp .5s .1s ease both;
}
.hero-title .accent { color: #ff8a7a; }
.hero-subtitle {
  color: rgba(255,255,255,.62); font-size: 1rem; line-height: 1.7;
  max-width: 520px; margin-bottom: 1.8rem;
  animation: fadeUp .5s .2s ease both;
  margin-left:-5rem;
}
.hero-meta {
  display: flex; flex-wrap: wrap; gap: .75rem; margin-bottom: 2rem;
  animation: fadeUp .5s .3s ease both;
  margin-left:-5rem;
}
.meta-chip {
  display: flex; align-items: center; gap: .4rem;
  background: rgba(255,255,255,.09);
  border: 1px solid rgba(255,255,255,.15);
  color: rgba(255,255,255,.8);
  padding: .38rem .9rem; border-radius: 6px; font-size: .8rem; font-weight: 500;
}
.hero-cta {
  display: flex; gap: 1rem; flex-wrap: wrap;
  animation: fadeUp .5s .4s ease both;
  margin-left:-5rem;

}
.btn-cta-primary {
  display: inline-flex; align-items: center; gap: .5rem;
  background: var(--red); color: var(--white);
  font-family: 'Montserrat', sans-serif;
  font-weight: 800; font-size: .95rem; letter-spacing: .04em;
  padding: .9rem 2rem; border-radius: 8px; text-decoration: none;
  transition: all .25s; box-shadow: 0 4px 18px rgba(192,57,43,.4);
  text-transform: uppercase;
}
.btn-cta-primary:hover { background: var(--red-dark); transform: translateY(-2px); box-shadow: 0 8px 28px rgba(192,57,43,.5); }
.btn-cta-secondary {
  display: inline-flex; align-items: center; gap: .5rem;
  border: 2px solid rgba(255,255,255,.3); color: rgba(255,255,255,.88);
  font-weight: 700; font-size: .95rem;
  padding: .9rem 1.8rem; border-radius: 8px; text-decoration: none;
  transition: all .25s;
}
.btn-cta-secondary:hover { border-color: #fff; color: #fff; background: rgba(255,255,255,.08); }

/* Hero logo mark */
.hero-logo-wrap {
  display: flex; flex-direction: column; align-items: center; gap: 1rem;
  animation: fadeUp .5s .25s ease both;
}
.hero-logo-ring {
  width: 200px; height: 200px; position: relative;
  display: flex; align-items: center; justify-content: center;
}
.hero-logo-ring .ring-svg { width: 100%; height: 100%; }
.hero-tagline {
  text-align: center;
  font-family: 'Montserrat', sans-serif;
  font-size: .78rem; font-weight: 700; letter-spacing: .06em;
  color: rgba(255,255,255,.5); text-transform: uppercase;
  max-width: 180px; line-height: 1.5;
}
@keyframes fadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }

/* ── STATS BAR ─────────────────────────────────────────────── */
.stats-bar {
  background: var(--red);
  padding: 1.1rem 2rem;
  display: flex; align-items: center; justify-content: center;
  gap: 3rem; flex-wrap: wrap;
}
.stat-item { text-align: center; }
.stat-item strong { display: block; font-family: 'Montserrat',sans-serif; font-size: 1.5rem; font-weight: 900; color: #fff; }
.stat-item span { font-size: .72rem; color: rgba(255,255,255,.8); letter-spacing: .05em; text-transform: uppercase; }
.stat-sep { width: 1px; height: 36px; background: rgba(255,255,255,.25); }

/* ── PROGRAMS SECTION ──────────────────────────────────────── */
.section { padding: 4.5rem 2rem; }
.section-inner { max-width: 1160px; margin: 0 auto; }
.section-label {
  display: inline-flex; align-items: center; gap: .5rem;
  background: rgba(26,58,143,.08); border: 1px solid rgba(26,58,143,.18);
  color: var(--blue); border-radius: 20px;
  padding: .3rem .9rem; font-size: .72rem; font-weight: 700;
  letter-spacing: .08em; text-transform: uppercase; margin-bottom: .9rem;
}
.section-title {
  font-family: 'Montserrat', sans-serif;
  font-size: clamp(1.5rem,3vw,2.2rem); font-weight: 800;
  color: var(--blue-dark); margin-bottom: .5rem; line-height: 1.15;
}
.section-title span { color: var(--red); }
.section-sub { color: var(--gray); font-size: .92rem; max-width: 520px; line-height: 1.6; margin-bottom: 2.5rem; }

.programs-bg { background: var(--blue-dark); }
.programs-bg .section-title { color: #fff; }
.programs-bg .section-sub { color: rgba(255,255,255,.55); }
.programs-bg .section-label { background: rgba(192,57,43,.2); border-color: rgba(192,57,43,.4); color: #ff8a7a; }

.prog-group { margin-bottom: 2.5rem; }
.prog-group-title {
  font-family: 'Montserrat', sans-serif;
  font-size: .72rem; font-weight: 800; text-transform: uppercase;
  letter-spacing: .1em; color: rgba(255,255,255,.45);
  margin-bottom: 1rem; padding-bottom: .5rem;
  border-bottom: 1px solid rgba(255,255,255,.1);
}
.prog-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px,1fr)); gap: 1rem; }
.prog-card {
  background: rgba(255,255,255,.06);
  border: 1px solid rgba(255,255,255,.1);
  border-radius: 10px; padding: 1.4rem 1.2rem;
  transition: all .25s; cursor: default;
  position: relative; overflow: hidden;
}
.prog-card::before {
  content: ''; position: absolute; top: 0; left: 0; right: 0;
  height: 3px; background: var(--red); transform: scaleX(0);
  transform-origin: left; transition: transform .3s;
}
.prog-card:hover { background: rgba(255,255,255,.1); border-color: rgba(255,255,255,.22); transform: translateY(-3px); }
.prog-card:hover::before { transform: scaleX(1); }
.prog-years {
  font-size: .65rem; font-weight: 700; color: var(--red-light);
  letter-spacing: .06em; text-transform: uppercase; margin-bottom: .5rem;
}
.prog-code {
  font-family: 'Montserrat', sans-serif;
  font-size: 1.1rem; font-weight: 900; color: #fff; margin-bottom: .35rem;
}
.prog-name { font-size: .82rem; color: rgba(255,255,255,.65); line-height: 1.4; }

.act-majors { display: grid; grid-template-columns: repeat(auto-fit,minmax(160px,1fr)); gap: .75rem; margin-top: .75rem; }
.act-major {
  background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.1);
  border-radius: 8px; padding: .8rem 1rem;
  font-size: .82rem; color: rgba(255,255,255,.7);
  display: flex; align-items: center; gap: .5rem;
}
.act-major::before { content: '✓'; color: var(--red-light); font-weight: 700; flex-shrink: 0; }

/* ── SCHOLARSHIPS ──────────────────────────────────────────── */
.schol-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px,1fr)); gap: 1.25rem; }
.schol-card {
  background: var(--white); border: 1px solid var(--border);
  border-radius: 10px; padding: 1.4rem;
  box-shadow: var(--shadow);
}
.schol-card-title {
  font-family: 'Montserrat',sans-serif; font-weight: 800;
  font-size: .88rem; color: var(--blue); margin-bottom: .9rem;
  display: flex; align-items: center; gap: .5rem;
}
.schol-card-title .icon { font-size: 1.1rem; }
.schol-list { list-style: none; }
.schol-list li {
  font-size: .83rem; color: var(--gray-dk); padding: .28rem 0;
  display: flex; align-items: flex-start; gap: .4rem; line-height: 1.4;
}
.schol-list li::before { content: '✦'; color: var(--red); font-size: .65rem; margin-top: .2rem; flex-shrink: 0; }
.schol-list li.sub { padding-left: 1.2rem; color: var(--gray); }
.schol-list li.sub::before { content: '✓'; color: var(--blue-light); }

/* ── CONTACT STRIP ─────────────────────────────────────────── */
.contact-strip {
  background: var(--blue);
  padding: 2.5rem 2rem;
}
.contact-strip-inner {
  max-width: 1160px; margin: 0 auto;
  display: grid; grid-template-columns: 1fr 1fr 1fr;
  gap: 2rem; align-items: center;
}
.contact-item { display: flex; align-items: flex-start; gap: .75rem; }
.contact-icon {
  width: 40px; height: 40px; border-radius: 8px;
  background: rgba(255,255,255,.1);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem; flex-shrink: 0;
}
.contact-info strong { display: block; font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: rgba(255,255,255,.45); margin-bottom: .2rem; }
.contact-info span { font-size: .88rem; color: rgba(255,255,255,.85); line-height: 1.5; }
.contact-info a { color: rgba(255,255,255,.85); text-decoration: none; }
.contact-info a:hover { color: #fff; }

/* ── ENROLLMENT FORM ───────────────────────────────────────── */
.form-wrap{
  position: relative;
  background: 
    linear-gradient(160deg, rgba(10,20,50,.92) 0%, rgba(10,20,50,.85) 45%, rgba(20,15,45,.93) 100%),
    url('469960960_122172018776055934_2241213344340004621_n.jpg') center center / cover no-repeat fixed;
}
.form-section-inner { max-width: 980px; margin: 0 auto; }
.form-section-head { text-align: center; margin-bottom: 2.5rem; }
.form-section-head h2 {
  font-family: 'Montserrat', sans-serif;
  font-size: clamp(1.6rem,3vw,2.2rem); font-weight: 900;
  color: #fff; margin-bottom: .4rem;
}
.form-section-head p { color: #fff; font-size: .9rem; }
.red-rule { width: 70px; height: 4px; background: var(--red); border-radius: 2px; margin: .9rem auto 1rem; }

.enroll-card {
  background: #fffffffb; border-radius: 14px;
  box-shadow: var(--shadow-lg); overflow: hidden;
  border: 1px solid var(--border);
}
.form-hdr {
  background: linear-gradient(135deg, var(--blue-dark), var(--blue));
  padding: 1.4rem 2rem;
  display: flex; align-items: center; gap: 1rem;
  border-bottom: 4px solid var(--red);
}
.form-hdr-icon {
  width: 48px; height: 48px; border-radius: 10px;
  background: rgba(192,57,43,.25);
  display: flex; align-items: center; justify-content: center; font-size: 1.3rem;
  flex-shrink: 0;
}
.form-hdr h3 {
  font-family: 'Montserrat', sans-serif; font-weight: 800;
  font-size: 1.2rem; color: var(--white);
}
.form-hdr p { font-size: .78rem; color: rgba(255,255,255,.55); margin-top: .15rem; }

.form-body { padding: 2rem; }
.f-section { margin-bottom: 2rem; }
.f-section-title {
  font-size: .68rem; font-weight: 800; letter-spacing: .1em;
  text-transform: uppercase; color: var(--blue);
  border-bottom: 2px solid var(--gray-lt);
  padding-bottom: .5rem; margin-bottom: 1.1rem;
  display: flex; align-items: center; gap: .5rem;
}
.f-section-title::before {
  content: ''; width: 3px; height: 14px;
  background: var(--red); border-radius: 2px; display: inline-block;
}
.f-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: .85rem; }
.f-field { display: flex; flex-direction: column; gap: .28rem; }
.f-field.w2 { grid-column: span 2; }
.f-field.w3 { grid-column: 1 / -1; }
.f-field label {
  font-size: .74rem; font-weight: 700; color: var(--gray-dk);
  letter-spacing: .02em;
}
.f-field label .r { color: var(--red); margin-left: .15rem; }
.f-field input, .f-field select, .f-field textarea {
  padding: .65rem .9rem;
  border: 1.5px solid var(--border);
  border-radius: 7px;
  font-family: 'Open Sans', sans-serif; font-size: .875rem;
  color: var(--text); width: 100%; outline: none;
  transition: border-color .2s, box-shadow .2s;
  background: var(--white);
}
.f-field input:focus, .f-field select:focus, .f-field textarea:focus {
  border-color: var(--blue);
  box-shadow: 0 0 0 3px rgba(26,58,143,.12);
}
textarea { min-height: 70px; resize: vertical; }

.submit-row {
  display: flex; align-items: center; justify-content: space-between;
  flex-wrap: wrap; gap: 1rem;
  padding: 1.4rem 2rem;
  background: var(--offwhite);
  border-top: 1px solid var(--border);
}
.submit-note { font-size: .78rem; color: var(--gray); max-width: 420px; line-height: 1.5; }
.btn-submit {
  display: inline-flex; align-items: center; gap: .5rem;
  background: var(--red); color: var(--white);
  font-family: 'Montserrat', sans-serif;
  font-size: .88rem; font-weight: 800; letter-spacing: .05em;
  text-transform: uppercase;
  padding: .85rem 2rem; border: none; border-radius: 8px;
  cursor: pointer; transition: all .25s;
  box-shadow: 0 4px 16px rgba(192,57,43,.35);
}
.btn-submit:hover { background: var(--red-dark); transform: translateY(-2px); box-shadow: 0 8px 24px rgba(192,57,43,.45); }

/* Alerts */
.alert { padding: 1rem 1.2rem; border-radius: 10px; font-size: .88rem; font-weight: 500; margin-bottom: 1.4rem; display: flex; align-items: flex-start; gap: .7rem; }
.alert-success { background: #f0fdf4; border: 2px solid #86efac; color: #15803d; }
.alert-error   { background: #fef2f2; border: 2px solid #fca5a5; color: var(--red); }
.success-sno { font-family: monospace; font-size: 1.05rem; font-weight: 700; background: #dcfce7; padding: .2rem .6rem; border-radius: 5px; color: #15803d; }

/* ── FOOTER ────────────────────────────────────────────────── */
footer {
  background: var(--blue-dark);
  border-top: 4px solid var(--red);
}
.footer-inner {
  max-width: 1160px; margin: 0 auto;
  padding: 2.5rem 2rem 1.5rem;
  display: grid; grid-template-columns: 1.4fr 1fr 1fr;
  gap: 2rem;
}
.footer-brand { display: flex; align-items: flex-start; gap: .9rem; }
.footer-brand-text strong {
  display: block; font-family: 'Montserrat',sans-serif; font-size: .82rem;
  font-weight: 800; color: #fff; text-transform: uppercase; letter-spacing: .02em;
}
.footer-brand-text span { font-size: .72rem; color: rgba(255,255,255,.45); }
.footer-brand-text p { font-size: .78rem; color: rgba(255,255,255,.55); margin-top: .6rem; line-height: 1.6; }
.footer-col h4 {
  font-family: 'Montserrat',sans-serif; font-weight: 800;
  font-size: .72rem; text-transform: uppercase; letter-spacing: .08em;
  color: var(--red-light); margin-bottom: .9rem;
}
.footer-col ul { list-style: none; }
.footer-col ul li { font-size: .8rem; color: rgba(255,255,255,.6); padding: .22rem 0; }
.footer-bottom {
  border-top: 1px solid rgba(255,255,255,.08);
  padding: 1rem 2rem; text-align: center;
  font-size: .74rem; color: rgba(255,255,255,.35);
}
.footer-bottom a { color: rgba(255,255,255,.5); text-decoration: none; }

/* ── RESPONSIVE ────────────────────────────────────────────── */
@media (max-width: 900px) {
  .hero-inner { grid-template-columns: 1fr; }
  .hero-logo-wrap { display: none; }
  .contact-strip-inner { grid-template-columns: 1fr 1fr; }
  .footer-inner { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 640px) {
  .topbar { display: none; }
  .navbar { padding: 0 1rem; }
  .navbar-links a:not(.btn-enroll) { display: none; }
  .hero-inner { padding: 3rem 1rem 2.5rem; }
  .section { padding: 3rem 1rem; }
  .stats-bar { gap: 1.5rem; }
  .stat-sep { display: none; }
  .contact-strip-inner { grid-template-columns: 1fr; }
  .footer-inner { grid-template-columns: 1fr; }
  .form-body { padding: 1.2rem; }
  .submit-row { flex-direction: column; }
  .btn-submit { width: 100%; justify-content: center; }
  .f-field.w2 { grid-column: 1 / -1; }
}
.ccdi-logo{
  width: 52px;
  height: 52px;
  object-fit: contain;
  flex-shrink: 0;
}
/* ───── SUCCESS MODAL ───── */

.success-modal-overlay{
  position: fixed;
  inset: 0;
  background: rgba(15,36,96,.75);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 1rem;
  animation: fadeModal .3s ease;
}

.success-modal{
  width: 100%;
  max-width: 500px;
  background: #fff;
  border-radius: 24px;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0,0,0,.35);
  animation: popUp .35s ease;
  position: relative;
}

.success-top{
  background: linear-gradient(135deg, var(--blue-dark), var(--blue));
  padding: 2.5rem 2rem 1.5rem;
  text-align: center;
  position: relative;
}

.success-check{
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: #22c55e;
  color: #fff;
  font-size: 2.5rem;
  font-weight: 900;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1rem;
  border: 6px solid rgba(255,255,255,.2);
  box-shadow: 0 10px 30px rgba(34,197,94,.4);
}

.success-logo{
  width: 70px;
  margin-bottom: 1rem;
}

.success-top h2{
  color: #fff;
  font-family: 'Montserrat', sans-serif;
  font-size: 1.8rem;
  font-weight: 800;
  margin-bottom: .5rem;
}

.success-message{
  color: rgba(255,255,255,.75);
  line-height: 1.6;
  font-size: .92rem;
}

.student-number-box{
  margin: 2rem;
  padding: 1.3rem;
  border-radius: 16px;
  background: var(--offwhite);
  border: 2px dashed var(--blue);
  text-align: center;
}

.student-number-box span{
  display: block;
  font-size: .75rem;
  font-weight: 700;
  color: var(--gray);
  text-transform: uppercase;
  letter-spacing: .08em;
  margin-bottom: .4rem;
}

.student-number-box strong{
  font-size: 1.8rem;
  font-family: monospace;
  color: var(--blue-dark);
  letter-spacing: 2px;
}

.success-info{
  padding: 0 2rem;
  text-align: center;
  color: var(--gray);
  line-height: 1.7;
  font-size: .9rem;
}

.success-actions{
  display: flex;
  gap: 1rem;
  padding: 2rem;
}

.modal-btn-primary,
.modal-btn-secondary{
  flex: 1;
  padding: .95rem 1rem;
  border-radius: 12px;
  font-weight: 700;
  text-decoration: none;
  text-align: center;
  transition: .25s;
  cursor: pointer;
  border: none;
  font-family: 'Montserrat', sans-serif;
}

.modal-btn-primary{
  background: var(--red);
  color: #fff;
}

.modal-btn-primary:hover{
  background: var(--red-dark);
  transform: translateY(-2px);
}

.modal-btn-secondary{
  background: var(--gray-lt);
  color: var(--gray-dk);
}

.modal-btn-secondary:hover{
  background: #dbe3f0;
}

@keyframes popUp{
  from{
    opacity:0;
    transform: scale(.85) translateY(20px);
  }
  to{
    opacity:1;
    transform: scale(1) translateY(0);
  }
}

@keyframes fadeModal{
  from{opacity:0;}
  to{opacity:1;}
}

@media(max-width:640px){
  .success-actions{
    flex-direction: column;
  }

  .success-modal{
    border-radius: 18px;
  }

  .student-number-box strong{
    font-size: 1.3rem;
  }
}
.logo-wrap {
  width: 130px; height: 130px;
  margin: -3rem 0rem;
  position: relative;
  display: flex; align-items: center; justify-content: center;
  margin-top: -6rem;
}

.logo-wrap::before {
  content: '';
  position: absolute; inset: -14px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.5) 0%, transparent 70%);
  animation: halo 3s ease-in-out infinite;
}
@keyframes halo {
  0%,100% { transform: scale(1); opacity: .7; }
  50%      { transform: scale(1.1); opacity: 1; }
}

.logo-wrap img {
  width: 260px; height: 260px;
  object-fit: contain;
  position: relative; z-index: 1;
  filter: drop-shadow(0 4px 24px rgba(255, 255, 255, 0.4));
  animation: float 4s ease-in-out infinite;
}
@keyframes float {
  0%,100% { transform: translateY(0); }
  50%      { transform: translateY(-6px); }
}
</style>
</head>
<body>

<!-- ── NAVBAR ─────────────────────────────────────────────────── -->
<nav class="navbar">
  <a href="#top" class="navbar-brand">
    <!-- CCDI logo mark as inline SVG -->
    <img src="ccdi-removebg-preview.png" alt="CCDI Logo" class="ccdi-logo">
      <!-- Outer ring -->
      <circle cx="48" cy="50" r="40" fill="none" stroke="#1a3a8f" stroke-width="9" opacity=".9"/>
      <!-- Middle ring -->
      <circle cx="48" cy="50" r="28" fill="none" stroke="#2451b3" stroke-width="7" opacity=".75"/>
      <!-- Inner circle fill -->
      <circle cx="48" cy="50" r="17" fill="#1a3a8f" opacity=".7"/>
      <!-- C letterform -->
      <path d="M38 38 A16 16 0 1 0 38 62" fill="none" stroke="#ffffff" stroke-width="5" stroke-linecap="round"/>
      <!-- D letterform -->
      <path d="M52 38 L52 62 Q70 62 70 50 Q70 38 52 38Z" fill="none" stroke="#ffffff" stroke-width="4.5" stroke-linejoin="round"/>
      <!-- Red i dot -->
      <circle cx="78" cy="38" r="6" fill="#c0392b"/>
      <!-- Red i stem -->
      <rect x="75" y="47" width="6" height="18" rx="3" fill="#c0392b"/>
    </svg>
    <div class="brand-text">
      <strong>Computer Communication<br>Development Institute</strong>
      <span>CCDI Legazpi</span>
    </div>
  </a>
  <div class="navbar-links">
    <a href="#programs">Programs</a>
    <a href="#scholarships">Scholarships</a>
    <a href="#contact">Contact</a>
    <a href="#enroll-form" class="btn-enroll">✦ Enroll Now</a>
    <a href="admin/login.php" style="color:rgba(255,255,255,.5);font-size:.78rem;padding:0 .9rem">Admin</a>
  </div>
</nav>

<!-- ── HERO ───────────────────────────────────────────────────── -->
<section class="hero" id="top">
  <div class="hero-grid"></div>
  <div class="hero-inner">
    <div class="hero-content">
      <div class="hero-badge">
        <span class="dot"></span>
        Enrollment Open — A.Y. <?= date('Y') . '–' . (date('Y')+1) ?>
      </div>
      <h1 class="hero-title">
        Aim High with<br>
        <span class="accent">CCDI</span> Legazpi
      </h1>
      <p class="hero-subtitle">
        <em>"Young Man think big, aspire and succeed!"</em><br><br>
        Build your career in technology and communication at
        Computer Communication Development Institute — the premier
        tech-focused institution in Legazpi City.
      </p>
      <div class="hero-meta">
        <span class="meta-chip">🏛️ CHED Recognized</span>
        <span class="meta-chip">📍 Legazpi City, Albay</span>
        <span class="meta-chip">🎓 Quality Programs</span>
        <span class="meta-chip">💡 Scholarship Grants</span>
      </div>
      <div class="hero-cta">
        <a href="#enroll-form" class="btn-cta-primary">📋 Enroll Now</a>
        <a href="#programs" class="btn-cta-secondary">View Programs →</a>
      </div>
    </div>

    <!-- Decorative logo ring -->
    <div class="hero-logo-wrap">
      <div class="hero-logo-ring">
        <div class="logo-wrap">
        <img src="ccdi-removebg-preview.png"
           alt="CCDI Logo">
        </div>
          <defs>
            <radialGradient id="glow" cx="50%" cy="50%" r="50%">
              <stop offset="0%" stop-color="#2451b3" stop-opacity=".6"/>
              <stop offset="100%" stop-color="#0f2460" stop-opacity="0"/>
            </radialGradient>
            <filter id="blur"><feGaussianBlur stdDeviation="8"/></filter>
          </defs>
          <circle cx="100" cy="100" r="95" fill="url(#glow)" filter="url(#blur)"/>
          <!-- Rings -->
          <circle cx="96" cy="100" r="82" fill="none" stroke="rgba(255,255,255,.12)" stroke-width="18"/>
          <circle cx="96" cy="100" r="57" fill="none" stroke="rgba(255,255,255,.1)" stroke-width="13"/>
          <circle cx="96" cy="100" r="35" fill="rgba(26,58,143,.5)" stroke="rgba(255,255,255,.08)" stroke-width="2"/>
          <!-- C -->
          <path d="M78 76 A28 28 0 1 0 78 124" fill="none" stroke="#ffffff" stroke-width="10" stroke-linecap="round"/>
          <!-- D -->
          <path d="M104 76 L104 124 Q138 124 138 100 Q138 76 104 76Z" fill="none" stroke="#ffffff" stroke-width="9" stroke-linejoin="round"/>
          <!-- i -->
          <circle cx="158" cy="74" r="10" fill="#c0392b"/>
          <rect x="153" y="88" width="10" height="30" rx="5" fill="#c0392b"/>
        </svg>
      </div>
      <div class="hero-tagline">Sikatuna St., Old Albay<br>Legazpi City</div>
    </div>
  </div>
</section>

<!-- ── STATS BAR ──────────────────────────────────────────────── -->
<div class="stats-bar">
  <div class="stat-item"><strong>4+</strong><span>Degree Programs</span></div>
  <div class="stat-sep"></div>
  <div class="stat-item"><strong>2-YR</strong><span>Associate Program</span></div>
  <div class="stat-sep"></div>
  <div class="stat-item"><strong>10+</strong><span>Scholarship Types</span></div>
  <div class="stat-sep"></div>
  <div class="stat-item"><strong>CHED</strong><span>Accredited</span></div>
</div>

<!-- ── PROGRAMS ───────────────────────────────────────────────── -->
<section class="section programs-bg" id="programs">
  <div class="section-inner">
    <div class="section-label">📚 CHED Programs</div>
    <h2 class="section-title" style="color:#fff">Our <span>Academic Programs</span></h2>
    <p class="section-sub">Choose from CHED-recognized degree programs and associate courses designed for the technology-driven world.</p>

    <div class="prog-group">
      <div class="prog-group-title">4-Year Degree Programs</div>
      <div class="prog-cards">
        <div class="prog-card">
          <div class="prog-years">4-Year Degree</div>
          <div class="prog-code">BSCS</div>
          <div class="prog-name">BS Computer Science</div>
        </div>
        <div class="prog-card">
          <div class="prog-years">4-Year Degree</div>
          <div class="prog-code">BSIT</div>
          <div class="prog-name">BS Information Technology</div>
        </div>
        <div class="prog-card">
          <div class="prog-years">4-Year Degree</div>
          <div class="prog-code">BSIS</div>
          <div class="prog-name">BS Information System</div>
        </div>
        <div class="prog-card">
          <div class="prog-years">4-Year Degree</div>
          <div class="prog-code">BSOAd</div>
          <div class="prog-name">BS Office Administration</div>
        </div>
      </div>
    </div>

    <div class="prog-group">
      <div class="prog-group-title">2-Year Associate Program</div>
      <div class="prog-cards" style="grid-template-columns:1fr">
        <div class="prog-card">
          <div class="prog-years">2-Year Associate</div>
          <div class="prog-code">ACT</div>
          <div class="prog-name">Associate in Computer Technology</div>
          <div class="act-majors">
            <div class="act-major">Major in Programming</div>
            <div class="act-major">Major in Networking</div>
            <div class="act-major">Major in Multi-Media</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ── SCHOLARSHIPS ────────────────────────────────────────────── -->
<section class="section" id="scholarships">
  <div class="section-inner">
    <div class="section-label">🎓 Financial Aid</div>
    <h2 class="section-title">College <span>Scholarship Grants</span></h2>
    <p class="section-sub">CCDI offers a wide range of scholarship opportunities to support deserving students in pursuing quality education.</p>

    <div class="schol-grid">
      <div class="schol-card">
        <div class="schol-card-title"><span class="icon">🏛️</span> CHED Grantees</div>
        <ul class="schol-list">
          <li>Tulong Dunong Program (TDP)</li>
          <li>Tertiary Education Subsidy / UNIFAST</li>
        </ul>
      </div>
      <div class="schol-card">
        <div class="schol-card-title"><span class="icon">🏆</span> Academic Scholarships</div>
        <ul class="schol-list">
          <li>With Highest Honors</li>
          <li>With High Honors</li>
          <li>With Honors</li>
          <li>Special Awards</li>
          <li>Student Assistant</li>
        </ul>
      </div>
      <div class="schol-card">
        <div class="schol-card-title"><span class="icon">🤝</span> Government & Special</div>
        <ul class="schol-list">
          <li>Athletics</li>
          <li>Gabay Aral</li>
          <li>4P's Beneficiaries</li>
          <li>DSWD</li>
          <li>OWWA</li>
          <li>Ako Bicol (AKB)</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<!-- ── CONTACT STRIP ──────────────────────────────────────────── -->
<div class="contact-strip" id="contact">
  <div class="contact-strip-inner">
    <div class="contact-item">
      <div class="contact-icon">📍</div>
      <div class="contact-info">
        <strong>Address</strong>
        <span>Sikatuna St., Old Albay<br>Legazpi City, Albay<br><em style="font-size:.74rem;color:rgba(255,255,255,.4)">(Located at the back of Jollibee Albay)</em></span>
      </div>
    </div>
    <div class="contact-item">
      <div class="contact-icon">📞</div>
      <div class="contact-info">
        <strong>Contact Numbers</strong>
        <span>
          <a href="tel:052-742-5462">(052) 742-5462</a><br>
          <a href="tel:09618581212">0961-858-1212</a><br>
          <a href="tel:09120221329">0912-022-1329</a>
        </span>
      </div>
    </div>
    <div class="contact-item">
      <div class="contact-icon">🌐</div>
      <div class="contact-info">
        <strong>Online</strong>
        <span>
          <a href="https://www.ccdi-legazpi.com" target="_blank">www.ccdi-legazpi.com</a><br>
          <a href="https://facebook.com/CcdiLegazpi" target="_blank">fb.com/CcdiLegazpi</a>
        </span>
      </div>
    </div>
  </div>
</div>

<!-- ── ENROLLMENT FORM ─────────────────────────────────────────── -->
<section class="section form-wrap" id="enroll-form">
  <div class="form-section-inner">
    <div class="form-section-head">
      <div class="section-label" style="color: #fff; margin:0 auto .9rem">📋 Online Enrollment</div>
      <h2>Student Enrollment Form</h2>
      <div class="red-rule"></div>
      <p>Fill out all fields marked <span style="color:var(--red);font-weight:700">*</span>. Our Registrar will review and confirm your application.</p>
    </div>

    <?php if ($success): ?>

<div class="success-modal-overlay" id="successModal">
  <div class="success-modal">

    <div class="success-top">
      <div class="success-check">✓</div>

      <h2>Enrollment Submitted!</h2>

      <p class="success-message">
        Your application has been successfully submitted to
        <strong>CCDI Legazpi</strong>.
      </p>
    </div>

    <div class="student-number-box">
      <span>Student Number</span>
      <strong><?= htmlspecialchars($success) ?></strong>
    </div>

    <div class="success-info">
      Please save your student number for future transactions and wait for confirmation from the Registrar's Office.
    </div>

    <div class="success-actions">
      <a href="index.php#enroll-form" class="modal-btn-primary">
        Submit Another Application
      </a>

      <a href="index.php" class="modal-btn-secondary">
        Close
      </a>
    </div>
  </div>
</div>

<?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <div class="enroll-card">
      <div class="form-hdr">
        <div class="form-hdr-icon">📋</div>
        <div>
          <h3>Enrollment Application Form</h3>
          <p>Computer Communication Development Institute, Inc. — A.Y. <?= date('Y').'–'.(date('Y')+1) ?></p>
        </div>
      </div>

      <div class="form-reminder" style="background:#fff7e6;border:1px solid #f0c36d;color:#8a6100;padding:10px 14px;border-radius:6px;margin-bottom:16px;font-size:14px;">
        ⚠️ <strong>Reminder:</strong> All fields on this form are required. Please fill out every field completely before submitting your application.
      </div>

      <form method="POST" class="form-body" id="enrollForm" novalidate>

        <div class="f-section">
          <div class="f-section-title">Personal Information</div>
          <div class="f-grid">
            <div class="f-field"><label>Last Name<span class="r">*</span></label>
              <input type="text" name="last_name" value="<?= htmlspecialchars($formData['last_name']??'') ?>" placeholder="Dela Cruz" required></div>
            <div class="f-field"><label>First Name<span class="r">*</span></label>
              <input type="text" name="first_name" value="<?= htmlspecialchars($formData['first_name']??'') ?>" placeholder="Juan" required></div>
            <div class="f-field"><label>Middle Name<span class="r">*</span></label>
              <input type="text" name="middle_name" value="<?= htmlspecialchars($formData['middle_name']??'') ?>" placeholder="Santos" required></div>
            <div class="f-field"><label>Suffix</label>
              <select name="suffix">
                <?php foreach(['','Jr.','Sr.','II','III','IV'] as $s): ?>
                <option value="<?= $s ?>" <?= ($formData['suffix']??'')===$s?'selected':'' ?>><?= $s?:'None' ?></option>
                <?php endforeach; ?>
              </select></div>
            <div class="f-field"><label>Date of Birth<span class="r">*</span></label>
              <input type="date" name="date_of_birth" value="<?= htmlspecialchars($formData['date_of_birth']??'') ?>" required></div>
            <div class="f-field"><label>Place of Birth<span class="r">*</span></label>
              <input type="text" name="place_of_birth" value="<?= htmlspecialchars($formData['place_of_birth']??'') ?>" placeholder="City/Province" required></div>
            <div class="f-field"><label>Gender<span class="r">*</span></label>
              <select name="gender" required>
                <option value="">Select…</option>
                <?php foreach(['Male','Female','Other'] as $g): ?>
                <option value="<?= $g ?>" <?= ($formData['gender']??'')===$g?'selected':'' ?>><?= $g ?></option>
                <?php endforeach; ?>
              </select></div>
            <div class="f-field"><label>Civil Status<span class="r">*</span></label>
              <select name="civil_status" required>
                <?php foreach(['Single','Married','Widowed','Separated'] as $cs): ?>
                <option value="<?= $cs ?>" <?= ($formData['civil_status']??'Single')===$cs?'selected':'' ?>><?= $cs ?></option>
                <?php endforeach; ?>
              </select></div>
            <div class="f-field"><label>Nationality<span class="r">*</span></label>
              <input type="text" name="nationality" value="<?= htmlspecialchars($formData['nationality']??'Filipino') ?>" required></div>
            <div class="f-field"><label>Religion</label>
              <input type="text" name="religion" value="<?= htmlspecialchars($formData['religion']??'') ?>" placeholder="Optional"></div>
          </div>
        </div>

        <div class="f-section">
          <div class="f-section-title">Contact Details</div>
          <div class="f-grid">
            <div class="f-field"><label>Email Address<span class="r">*</span></label>
              <input type="email" name="email" value="<?= htmlspecialchars($formData['email']??'') ?>" placeholder="juan@email.com" required></div>
            <div class="f-field"><label>Mobile Number<span class="r">*</span></label>
              <input type="tel" name="mobile" value="<?= htmlspecialchars($formData['mobile']??'') ?>" placeholder="09XX XXX XXXX" required></div>
            <div class="f-field"><label>Telephone</label>
              <input type="tel" name="telephone" value="<?= htmlspecialchars($formData['telephone']??'') ?>" placeholder="(052) XXXX XXXX"></div>
            <div class="f-field w3"><label>Present Address<span class="r">*</span></label>
              <textarea name="present_address" placeholder="Street, Barangay, City/Municipality, Province" required><?= htmlspecialchars($formData['present_address']??'') ?></textarea></div>
            <div class="f-field w3"><label>Permanent Address<span class="r">*</span></label>
              <textarea name="permanent_address" placeholder="Street, Barangay, City/Municipality, Province" required><?= htmlspecialchars($formData['permanent_address']??'') ?></textarea></div>
          </div>
        </div>

        <div class="f-section">
          <div class="f-section-title">Parent / Guardian Information</div>
          <div class="f-grid">
            <div class="f-field"><label>Guardian Name<span class="r">*</span></label>
              <input type="text" name="guardian_name" value="<?= htmlspecialchars($formData['guardian_name']??'') ?>" placeholder="Full Name" required></div>
            <div class="f-field"><label>Relationship<span class="r">*</span></label>
              <input type="text" name="guardian_relation" value="<?= htmlspecialchars($formData['guardian_relation']??'') ?>" placeholder="Mother / Father / etc." required></div>
            <div class="f-field"><label>Guardian Mobile<span class="r">*</span></label>
              <input type="tel" name="guardian_mobile" value="<?= htmlspecialchars($formData['guardian_mobile']??'') ?>" placeholder="09XX XXX XXXX" required></div>
            <div class="f-field"><label>Guardian Email<span class="r">*</span></label>
              <input type="email" name="guardian_email" value="<?= htmlspecialchars($formData['guardian_email']??'') ?>" required></div>
            <div class="f-field w3"><label>Guardian Address<span class="r">*</span></label>
              <textarea name="guardian_address" placeholder="Guardian's address" required><?= htmlspecialchars($formData['guardian_address']??'') ?></textarea></div>
          </div>
        </div>

        <div class="f-section">
          <div class="f-section-title">Academic Information</div>
          <div class="f-grid">
            <div class="f-field"><label>Program<span class="r">*</span></label>
              <select name="program_id" required>
                <option value="">Select Program…</option>
                <?php foreach($programs as $prog): ?>
                <option value="<?= $prog['id'] ?>" <?= ($formData['program_id']??'')==$prog['id']?'selected':'' ?>>
                  <?= htmlspecialchars($prog['code'].' — '.$prog['title']) ?>
                </option>
                <?php endforeach; ?>
              </select></div>
            <div class="f-field"><label>Year Level<span class="r">*</span></label>
              <select name="year_level" required>
                <?php foreach(['1st Year','2nd Year','3rd Year','4th Year','5th Year'] as $yl): ?>
                <option value="<?= $yl ?>" <?= ($formData['year_level']??'1st Year')===$yl?'selected':'' ?>><?= $yl ?></option>
                <?php endforeach; ?>
              </select></div>
            <div class="f-field"><label>Semester<span class="r">*</span></label>
              <select name="semester" required>
                <?php foreach(['1st Semester','2nd Semester','Summer'] as $sem): ?>
                <option value="<?= $sem ?>" <?= ($formData['semester']??'1st Semester')===$sem?'selected':'' ?>><?= $sem ?></option>
                <?php endforeach; ?>
              </select></div>
            <div class="f-field"><label>School Year<span class="r">*</span></label>
              <input type="text" name="school_year" value="<?= htmlspecialchars($formData['school_year']??date('Y').'–'.(date('Y')+1)) ?>" placeholder="2025–2026" required></div>
            <div class="f-field"><label>Student Type<span class="r">*</span></label>
              <select name="student_type" required>
                <?php foreach(['New','Old','Transferee','Returnee'] as $st): ?>
                <option value="<?= $st ?>" <?= ($formData['student_type']??'New')===$st?'selected':'' ?>><?= $st ?></option>
                <?php endforeach; ?>
              </select></div>
          </div>
        </div>

        <div class="f-section">
          <div class="f-section-title">Previous School Information</div>
          <div class="f-grid">
            <div class="f-field w2"><label>Previous School Name<span class="r">*</span></label>
              <input type="text" name="prev_school" value="<?= htmlspecialchars($formData['prev_school']??'') ?>" placeholder="Name of last school attended" required></div>
            <div class="f-field w2"><label>School Address<span class="r">*</span></label>
              <input type="text" name="prev_school_addr" value="<?= htmlspecialchars($formData['prev_school_addr']??'') ?>" placeholder="City/Province" required></div>
            <div class="f-field"><label>Program / Strand<span class="r">*</span></label>
              <input type="text" name="prev_program" value="<?= htmlspecialchars($formData['prev_program']??'') ?>" placeholder="e.g. STEM, BSIT" required></div>
            <div class="f-field"><label>Last Year Attended<span class="r">*</span></label>
              <input type="text" name="last_year_attended" value="<?= htmlspecialchars($formData['last_year_attended']??'') ?>" placeholder="e.g. 2024–2025" required></div>
            <div class="f-field"><label>Honors / Awards Received<span class="r">*</span></label>
              <input type="text" name="honors_received" value="<?= htmlspecialchars($formData['honors_received']??'') ?>" placeholder="With Honors, Dean's List, etc." required></div>
          </div>
        </div>

        <div class="submit-row">
          <p class="submit-note">
            By submitting this form, you certify that all information provided is accurate and complete.
            False information may result in cancellation of enrollment.
          </p>
          <div id="formAlert" style="display:none;background:#fde8e8;border:1px solid #f5a3a3;color:#9c1c1c;padding:10px 14px;border-radius:6px;margin-bottom:12px;font-size:14px;">
            ❌ Please fill out all required fields before submitting.
          </div>
          <button type="submit" name="enroll_submit" value="1" class="btn-submit">
            📤 Submit Application
          </button>
        </div>

      </form>
    </div><!-- .enroll-card -->
    <?php endif; ?>
  </div>
</section>

<script>
document.getElementById('enrollForm').addEventListener('submit', function(e) {
  const form = e.target;
  const alertBox = document.getElementById('formAlert');
  let allFilled = true;
  let firstInvalid = null;

  form.querySelectorAll('input[required], select[required], textarea[required]').forEach(field => {
    field.classList.remove('field-error');
    if (!field.value || field.value.trim() === '') {
      allFilled = false;
      field.classList.add('field-error');
      if (!firstInvalid) firstInvalid = field;
    }
  });

  if (!allFilled) {
    e.preventDefault();
    alertBox.style.display = 'block';
    if (firstInvalid) {
      firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
      firstInvalid.focus();
    }
  } else {
    alertBox.style.display = 'none';
  }
});
</script>

<style>
.field-error {
  border-color: #e02424 !important;
  background-color: #fff5f5 !important;
}
</style>

<!-- ── FOOTER ──────────────────────────────────────────────────── -->
<footer>
  <div class="footer-inner">
    <div class="footer-brand">
      <img src="ccdi-removebg-preview.png" alt="CCDI Logo" class="ccdi-logo" style="width: 85px; height: 85px;object-fit: contain;flex-shrink: 0;">
      <div class="footer-brand-text">
        <strong>Computer Communication<br>Development Institute, Inc.</strong>
        <span>CCDI Legazpi</span>
        <p>Sikatuna St., Old Albay, Legazpi City<br>
           Located at the back of Jollibee Albay</p>
      </div>
    </div>
    <div class="footer-col">
      <h4>Programs</h4>
      <ul>
        <li>BSCS — Computer Science</li>
        <li>BSIT — Information Technology</li>
        <li>BSIS — Information System</li>
        <li>BSOAd — Office Administration</li>
        <li>ACT — Associate in Comp. Tech.</li>
      </ul>
    </div>
    <div class="footer-col">
      <h4>Contact</h4>
      <ul>
        <li>(052) 742-5462</li>
        <li>0961-858-1212</li>
        <li>0912-022-1329</li>
        <li>www.ccdi-legazpi.com</li>
        <li>fb: Ccdi Legazpi</li>
      </ul>
    </div>
  </div>
  <div class="footer-bottom">
    © <?= date('Y') ?> Computer Communication Development Institute, Inc. — All rights reserved. &nbsp;|&nbsp;
    <a href="admin/login.php">Admin Portal</a>
  </div>
</footer>

</body>
</html>