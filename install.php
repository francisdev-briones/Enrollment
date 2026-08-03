<?php
/**
 * CCDI Enrollment System — One-Time Installer
 * ─────────────────────────────────────────────
 * Step 1: Import database.sql in phpMyAdmin
 * Step 2: Open this file in your browser: http://localhost/CCDI_Enrollment/install.php
 * Step 3: DELETE this file after setup is complete!
 */
require_once __DIR__ . '/config/db.php';

$messages = [];
$success  = false;

// ── Seed default programs ──────────────────────────────────────
$programs = [
    ['BSCS',  'BS Computer Science',             'Bachelor of Science in Computer Science.',        '4 years', 50],
    ['BSIT',  'BS Information Technology',       'Bachelor of Science in Information Technology.',  '4 years', 50],
    ['BSIS',  'BS Information System',           'Bachelor of Science in Information System.',      '4 years', 45],
    ['BSOAd', 'BS Office Administration',        'Bachelor of Science in Office Administration.',   '4 years', 40],
    ['ACT',   'Associate in Computer Technology','2-Year Associate in Computer Technology.',        '2 years', 60],
];
foreach ($programs as [$code, $title, $desc, $dur, $slots]) {
    $chk = db()->prepare("SELECT id FROM programs WHERE code=?");
    $chk->bind_param('s', $code); $chk->execute(); $chk->store_result();
    if ($chk->num_rows === 0) {
        $ins = db()->prepare("INSERT INTO programs (code,title,description,duration,max_slots) VALUES (?,?,?,?,?)");
        $ins->bind_param('ssssi', $code, $title, $desc, $dur, $slots);
        $ins->execute();
        $messages[] = "✅ Program <strong>$code</strong> created.";
    } else {
        $messages[] = "⏭️ Program <strong>$code</strong> already exists, skipped.";
    }
}

// ── Seed default admin ─────────────────────────────────────────
$adminUser = 'admin';
$adminPass = 'Admin@1234';
$adminEmail= 'admin@ccdi.edu.ph';
$adminName = 'System Administrator';
$adminRole = 'superadmin';

$chk = db()->prepare("SELECT id FROM admins WHERE username=? OR email=?");
$chk->bind_param('ss', $adminUser, $adminEmail);
$chk->execute(); $chk->store_result();

if ($chk->num_rows === 0) {
    $hash = password_hash($adminPass, PASSWORD_DEFAULT);
    $ins  = db()->prepare("INSERT INTO admins (username,email,password,full_name,role) VALUES (?,?,?,?,?)");
    $ins->bind_param('sssss', $adminUser, $adminEmail, $hash, $adminName, $adminRole);
    if ($ins->execute()) {
        $messages[] = "✅ Admin account <strong>$adminUser</strong> created with password <code>$adminPass</code>.";
        $success = true;
    } else {
        $messages[] = "❌ Failed to create admin: " . db()->error;
    }
} else {
    // Re-hash and update password in case it was wrong before
    $hash = password_hash($adminPass, PASSWORD_DEFAULT);
    $upd  = db()->prepare("UPDATE admins SET password=? WHERE username=?");
    $upd->bind_param('ss', $hash, $adminUser);
    $upd->execute();
    $messages[] = "🔄 Admin <strong>$adminUser</strong> already exists — password reset to <code>$adminPass</code>.";
    $success = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>CCDI Installer</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:Outfit,sans-serif;background:#f1f5f9;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem}
  .card{background:#fff;border-radius:14px;max-width:580px;width:100%;padding:2.5rem;box-shadow:0 8px 40px rgba(0,0,0,.12)}
  h1{font-size:1.6rem;color:#0c2461;margin-bottom:.4rem}
  p.sub{color:#6b7280;font-size:.9rem;margin-bottom:1.6rem;border-bottom:1px solid #e5e7eb;padding-bottom:1rem}
  .msg{padding:.7rem 1rem;border-radius:8px;font-size:.88rem;margin-bottom:.6rem;background:#f8fafc;border:1px solid #e5e7eb}
  .actions{margin-top:1.6rem;display:flex;gap:.75rem;flex-wrap:wrap}
  .btn{display:inline-flex;align-items:center;gap:.4rem;padding:.65rem 1.3rem;border-radius:8px;font-family:Outfit,sans-serif;font-size:.9rem;font-weight:600;text-decoration:none;transition:all .2s}
  .btn-blue{background:#0c2461;color:#fff}.btn-blue:hover{background:#1e3799}
  .btn-gold{background:#c9a84c;color:#060e24}.btn-gold:hover{background:#f0c040}
  .btn-red{background:#fee2e2;color:#b91c1c}.btn-red:hover{background:#fca5a5}
  .warning{background:#fef9c3;border:1px solid #fde68a;color:#854d0e;padding:1rem;border-radius:8px;margin-top:1.2rem;font-size:.85rem}
  code{background:#f3f4f6;padding:.15rem .4rem;border-radius:4px;font-size:.85rem}
</style>
</head>
<body>
<div class="card">
  <h1>🎓 CCDI Enrollment Installer</h1>
  <p class="sub">One-time setup script — <strong>delete this file after completing setup!</strong></p>

  <?php foreach ($messages as $m): ?>
  <div class="msg"><?= $m ?></div>
  <?php endforeach; ?>

  <?php if ($success): ?>
  <div class="warning">
    ⚠️ <strong>Security Notice:</strong> Delete <code>install.php</code> from your server now.
    Anyone who visits this URL can reset the admin password.
  </div>
  <div class="actions">
    <a href="admin/login.php" class="btn btn-blue">→ Go to Admin Login</a>
    <a href="index.php" class="btn btn-gold">→ View Enrollment Page</a>
  </div>
  <?php else: ?>
  <div class="actions">
    <a href="install.php" class="btn btn-blue">↻ Retry</a>
  </div>
  <?php endif; ?>
</div>
</body>
</html>