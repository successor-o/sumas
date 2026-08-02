<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0,viewport-fit=cover"/>
  <meta name="theme-color" content="#6B3318"/>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Student Login — SUMAS SmartAttend</title>
  <link rel="stylesheet" href="{{ asset('assets/css/design-system.css') }}"/>
  <link rel="stylesheet" href="{{ asset('assets/css/pages.css') }}"/>
  <style>
    /* Mobile brand bar */
    .auth-mobile-brand {
      display: none; align-items: center; gap: 12px;
      padding: 14px 20px; background: var(--b900);
      border-bottom: 2px solid var(--brand);
    }
    .auth-mobile-brand img { width: 36px; height: 36px; object-fit: contain; }
    .auth-mobile-brand-name { font-size: .88rem; font-weight: 800; color: var(--white); }
    .auth-mobile-brand-sub  { font-size: .6rem; color: var(--g300); text-transform: uppercase; letter-spacing: 1px; margin-top: 1px; }
    @media(max-width:900px) { .auth-mobile-brand { display: flex !important; } }
  </style>
</head>
<body id="login-page">

<!-- PAGE LOADER -->
<div id="page-loader">
  <div class="ldr-wrap">
    <div class="ldr-logo-row">
      <div class="ldr-crest"><img src="{{ asset('assets/images/sumas-logo.png') }}" alt="SUMAS"/></div>
      <div class="ldr-text-col">
        <div class="ldr-uni">State University of Medical &amp; Applied Sciences</div>
        <div class="ldr-name">SUMAS <em>Smart</em>Attend</div>
        <div class="ldr-tag">Student Identity Portal</div>
      </div>
    </div>
    <div class="ldr-divider"></div>
    <div class="ldr-bar-wrap"><div class="ldr-bar-track"><div class="ldr-bar-fill"></div></div></div>
    <div class="ldr-dots"><div class="ldr-dot"></div><div class="ldr-dot"></div><div class="ldr-dot"></div></div>
  </div>
</div>

<div id="toast-container"></div>

<!-- Mobile brand bar (visible when left panel is hidden on small screens) -->
<div class="auth-mobile-brand" aria-hidden="true">
  <img src="{{ asset('assets/images/sumas-logo.png') }}" alt="SUMAS Crest"/>
  <div>
    <div class="auth-mobile-brand-name">SUMAS SmartAttend</div>
    <div class="auth-mobile-brand-sub">State University of Medical &amp; Applied Sciences</div>
  </div>
</div>

<!-- AUTH LAYOUT -->
<div class="auth-page">

  <!-- ── LEFT PANEL ── -->
  <div class="auth-left">
    <div class="auth-left-bg"></div>
    <div class="auth-left-overlay"></div>
    <div class="auth-left-content">

      <div class="auth-logo-row">
        <img src="{{ asset('assets/images/sumas-logo.png') }}" alt="SUMAS Crest" class="auth-logo-img"/>
        <div>
          <div class="auth-logo-name">State University of Medical &amp; Applied Sciences</div>
          <div class="auth-logo-sub">Igbo Eno · Enugu State · Nigeria</div>
        </div>
      </div>

      <div class="auth-left-body">
        <div>
          <h2 class="auth-left-headline">Welcome to<br/><em>SmartAttend</em></h2>
          <p class="auth-left-desc">AI-powered student identity verification and smart classroom attendance — built for academic excellence at SUMAS.</p>
        </div>
        <div class="auth-features">
          <div class="auth-feat"><div class="auth-feat-ic">🤖</div>Deep AI Facial Recognition Technology</div>
          <div class="auth-feat"><div class="auth-feat-ic">🛡️</div>Anti-Impersonation &amp; Fraud Prevention</div>
          <div class="auth-feat"><div class="auth-feat-ic">📊</div>Real-Time Attendance Dashboard</div>
          <div class="auth-feat"><div class="auth-feat-ic">🔒</div>End-to-End Encrypted Biometrics</div>
          <div class="auth-feat"><div class="auth-feat-ic">📱</div>Mobile-First Responsive Design</div>
        </div>
      </div>

      <div class="auth-vc-card">
        <img src="{{ asset('assets/images/vice-chancellor.png') }}" alt="Vice Chancellor" class="auth-vc-img"/>
        <div>
          <div class="auth-vc-name">Prof. James Chukwuma Ogbonna</div>
          <div class="auth-vc-title">Vice Chancellor, SUMAS</div>
        </div>
      </div>

    </div>
  </div>

  <!-- ── RIGHT PANEL ── -->
  <div class="auth-right">
    <div class="auth-form-wrap">

      <div class="auth-form-head">
        <div class="auth-form-crest">
          <img src="{{ asset('assets/images/sumas-logo.png') }}" alt="SUMAS Crest"/>
        </div>
        <h1 class="auth-form-title">Student Login</h1>
        <p class="auth-form-sub">Access your SmartAttend registration dashboard</p>
      </div>

      <!-- Matric field -->
      <div class="form-group">
        <label class="form-label" for="login-matric">Matric Number <span class="req">*</span></label>
        <div class="input-icon-wrap">
          <span class="icon-left">🎓</span>
          <input type="text" id="login-matric" class="input"
            placeholder="e.g. SUMAS/CS/2023/001"
            autocomplete="username" required
            aria-describedby="matric-hint matric-err"/>
        </div>
        <div class="form-hint" id="matric-hint">Enter your university matriculation number</div>
        <div class="form-error" id="matric-err" style="display:none">Matric number is required.</div>
      </div>

      <!-- Password field -->
      <div class="form-group">
        <label class="form-label" for="login-password">Password <span class="req">*</span></label>
        <div class="input-icon-wrap">
          <span class="icon-left">🔐</span>
          <input type="password" id="login-password" class="input"
            placeholder="Enter your password"
            autocomplete="current-password" required
            aria-describedby="pass-err"/>
          <button type="button" id="toggle-pw" class="icon-right" aria-label="Toggle password visibility">👁️</button>
        </div>
        <div class="form-error" id="pass-err" style="display:none">Password is required.</div>
      </div>

      <!-- Remember me + Forgot password -->
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--s5);flex-wrap:wrap;gap:var(--s2)">
        <label style="display:flex;align-items:center;gap:var(--s2);font-size:.83rem;color:var(--t-muted);cursor:pointer">
          <input type="checkbox" id="remember-me" style="accent-color:var(--brand)"/>
          Remember me
        </label>
        <a href="#" style="font-size:.82rem;color:var(--brand);font-weight:600">Forgot password?</a>
      </div>

      <!-- Submit -->
      <button class="btn btn-primary btn-lg btn-block" id="login-btn" type="button">
        Sign In &nbsp;→
      </button>

      <!-- Links -->
      <p class="auth-footer-link" style="margin-top:var(--s5)">
        Don't have an account? <a href="{{ route('register') }}">Register Now →</a>
      </p>
      <p class="auth-footer-link" style="margin-top:var(--s3)">
        Registration pending approval? <a href="{{ route('register') }}#status-check">Check your status →</a>
      </p>

      <div class="auth-back">
        <a href="{{ route('home') }}">← Back to SmartAttend Home</a>
      </div>

    </div>
  </div>

</div><!-- /auth-page -->

<script src="{{ asset('assets/js/api.js') }}"></script>
<script src="{{ asset('assets/js/app.js') }}"></script>
</body>
</html>
