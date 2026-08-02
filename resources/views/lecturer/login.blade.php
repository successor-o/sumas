<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0,viewport-fit=cover"/>
  <meta name="theme-color" content="#6B3318"/>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Lecturer Login — SUMAS SmartAttend</title>
  <link rel="stylesheet" href="{{ asset('assets/css/design-system.css') }}"/>
  <link rel="stylesheet" href="{{ asset('assets/css/pages.css') }}"/>
</head>
<body id="lecturer-login-page">

<div id="page-loader">
  <div class="ldr-wrap">
    <div class="ldr-logo-row">
      <div class="ldr-crest"><img src="{{ asset('assets/images/sumas-logo.png') }}" alt="SUMAS"/></div>
      <div class="ldr-text-col">
        <div class="ldr-uni">SUMAS SmartAttend</div>
        <div class="ldr-name">Lecturer <em>Portal</em></div>
        <div class="ldr-tag">Faculty Access</div>
      </div>
    </div>
    <div class="ldr-divider"></div>
    <div class="ldr-bar-wrap"><div class="ldr-bar-track"><div class="ldr-bar-fill"></div></div></div>
    <div class="ldr-dots"><div class="ldr-dot"></div><div class="ldr-dot"></div><div class="ldr-dot"></div></div>
  </div>
</div>

<div id="toast-container"></div>

<div class="lecturer-login-page">
  <div class="lecturer-login-card">

    <div class="lecturer-login-head">
      <div class="lecturer-login-crest">
        <img src="{{ asset('assets/images/sumas-logo.png') }}" alt="SUMAS Crest"/>
      </div>
      <div class="lecturer-badge">👨‍🏫 Faculty Access</div>
      <h1 class="lecturer-login-title">Lecturer Portal</h1>
      <p class="lecturer-login-sub">SUMAS SmartAttend · Faculty Console</p>
    </div>

    <div class="form-group">
      <label class="form-label" for="lecturer-email">Email Address <span class="req">*</span></label>
      <div class="input-icon-wrap">
        <span class="icon-left">📧</span>
        <input type="email" id="lecturer-email" class="input"
          placeholder="your.email@sumas.edu.ng"
          autocomplete="email" required/>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="lecturer-password">Password <span class="req">*</span></label>
      <div class="input-icon-wrap">
        <span class="icon-left">🔐</span>
        <input type="password" id="lecturer-password" class="input"
          placeholder="Enter your password"
          autocomplete="current-password" required/>
        <button type="button" id="toggle-pw-lecturer" class="icon-right" aria-label="Toggle password">👁️</button>
      </div>
    </div>

    <div class="lecturer-login-alert" id="lecturer-login-alert" role="alert"></div>

    <button class="btn btn-primary btn-lg btn-block" id="lecturer-login-btn" type="button">
      Sign In to Lecturer Portal
    </button>

    <div class="lecturer-login-footer">
      <a href="{{ route('home') }}" class="lecturer-back-link">← Back to Home</a>
    </div>

  </div>
</div>

<script src="{{ asset('assets/js/api.js') }}"></script>
<script src="{{ asset('assets/js/app.js') }}"></script>
<script>
/* ── Lecturer Login Page ── */
if (document.getElementById('lecturer-login-page')) {
  SessionStore.redirectIfLecturer('/lecturer/dashboard');

  document.getElementById('toggle-pw-lecturer')?.addEventListener('click', function () {
    const pw = document.getElementById('lecturer-password');
    pw.type = pw.type === 'password' ? 'text' : 'password';
    this.textContent = pw.type === 'text' ? '🙈' : '👁️';
  });

  const loginBtn = document.getElementById('lecturer-login-btn');
  const alertBox = document.getElementById('lecturer-login-alert');

  function showAlert(msg) {
    if (!alertBox) return;
    alertBox.textContent = '⚠️ ' + msg;
    alertBox.classList.add('show');
  }
  function clearAlert() {
    if (alertBox) { alertBox.textContent = ''; alertBox.classList.remove('show'); }
  }

  async function doLogin() {
    const email = document.getElementById('lecturer-email')?.value.trim();
    const password = document.getElementById('lecturer-password')?.value;
    const emailErr = document.getElementById('lecturer-email')?.classList;
    const passErr  = document.getElementById('lecturer-password')?.classList;

    clearAlert();
    emailErr?.remove('error');
    passErr?.remove('error');

    let valid = true;
    if (!email) { emailErr?.add('error'); valid = false; }
    if (!password) { passErr?.add('error'); valid = false; }
    if (!valid) {
      const msg = 'Please enter both email and password.';
      showAlert(msg);
      showToast('error', 'Required Fields', msg);
      return;
    }

    loginBtn.disabled = true;
    loginBtn.textContent = '⏳ Signing in…';

    try {
      const res = await API.lecturer.login(email, password);

      if (res.ok && res.data.token) {
        SessionStore.save(res.data.token, res.data.lecturer, 'lecturer');
        clearAlert();
        showToast('success', `Welcome, ${res.data.lecturer.name}!`, 'Loading your dashboard…');
        setTimeout(() => window.location.href = '/lecturer/dashboard', 800);
        return;
      }

      const msg = res.data.message || 'Invalid credentials. Please try again.';
      showAlert(msg);
      showToast('error', 'Login Failed', msg);
    } catch (err) {
      const msg = 'Something went wrong. Please try again.';
      showAlert(msg);
      showToast('error', 'Login Error', msg);
      console.error('Lecturer login error:', err);
    } finally {
      // Always restore the button — never leave it stuck on "Signing in…".
      loginBtn.disabled = false;
      loginBtn.textContent = 'Sign In to Lecturer Portal';
    }
  }

  loginBtn?.addEventListener('click', doLogin);
  document.getElementById('lecturer-email')?.addEventListener('keydown', e => e.key === 'Enter' && doLogin());
  document.getElementById('lecturer-password')?.addEventListener('keydown', e => e.key === 'Enter' && doLogin());
}
</script>
</body>
</html>
