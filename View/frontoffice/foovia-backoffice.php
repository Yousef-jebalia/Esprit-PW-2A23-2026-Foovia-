<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>FOOVIA - Backoffice Access</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Boldonse&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="foovia-signin.css">
<link rel="stylesheet" href="foovia-backoffice.css">
</head>
<body>

<div class="left-panel">
  <div class="blob blob-1"></div>
  <div class="blob blob-2"></div>
  <div class="blob blob-3"></div>

  <a href="foovia.php" class="left-logo">FOOVIA</a>

  <div class="left-body">
    <h1>Backoffice<br>control<br><span>center.</span></h1>
    <p>Manage users, follow platform activity, and operate your admin tools from one secure space.</p>
  </div>

  <div class="left-pills">
    <div class="pill"><div class="pill-dot pill-dot-yellow"></div>User and account supervision</div>
    <div class="pill"><div class="pill-dot pill-dot-green"></div>Dashboard and analytics pages</div>
    <div class="pill"><div class="pill-dot pill-dot-orange"></div>Backoffice authentication flow</div>
  </div>
</div>

<div class="right-panel">
  <p class="form-eyebrow">Admin area</p>
  <h1 class="form-title">Sign in to<br>Backoffice</h1>
  <p class="form-sub">Need an admin account? <a href="../backoffice/auth-sign-up.php">Create one now</a></p>

  <form method="POST" action="../backoffice/auth-normal-sign-in.php" id="backofficeForm">
    <div class="field-group">
      <div class="field">
        <label for="email">Email address</label>
        <div class="field-wrap">
          <input type="email" id="email" name="email" placeholder="admin@example.com" autocomplete="email" required/>
          <span class="field-icon">@</span>
        </div>
        <span class="field-error" id="err-email">Please enter a valid email address.</span>
      </div>

      <div class="field">
        <label for="password">Password</label>
        <div class="field-wrap">
          <input type="password" id="password" name="password" placeholder="Your password" autocomplete="current-password" required/>
          <button class="toggle-pw" type="button" onclick="togglePw('password', this)">Show</button>
        </div>
        <span class="field-error" id="err-password">Password cannot be empty.</span>
      </div>
    </div>

    <div class="forgot-row"><a href="../backoffice/auth-reset-password.html">Forgot your password?</a></div>

    <button type="submit" name="signin_submit" class="btn-submit">Continue to Backoffice</button>
  </form>

  <div class="divider">
    <div class="divider-line"></div>
    <span class="divider-text">quick links</span>
    <div class="divider-line"></div>
  </div>

  <div class="social-btns">
    <a class="social-btn" href="../backoffice/auth-normal-sign-in.php">Sign in page</a>
    <a class="social-btn" href="../backoffice/auth-sign-up.php">Sign up page</a>
  </div>
</div>

<script>
function togglePw(id, btn) {
  const input = document.getElementById(id);
  const hidden = input.type === 'password';
  input.type = hidden ? 'text' : 'password';
  btn.textContent = hidden ? 'Hide' : 'Show';
}

function validate(id, check, errId) {
  const input = document.getElementById(id);
  const err = document.getElementById(errId);
  const ok = check(input.value);
  input.classList.toggle('error', !ok);
  err.classList.toggle('visible', !ok);
  return ok;
}

document.getElementById('backofficeForm').addEventListener('submit', function(e) {
  const validEmail = validate('email', v => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v), 'err-email');
  const validPassword = validate('password', v => v.length > 0, 'err-password');
  if (!validEmail || !validPassword) {
    e.preventDefault();
  }
});
</script>
</body>
</html>
