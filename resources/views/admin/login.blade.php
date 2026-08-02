<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0,viewport-fit=cover"/>
  <meta name="theme-color" content="#6B3318"/>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Admin Login — SUMAS SmartAttend</title>
  <link rel="stylesheet" href="{{ asset('assets/css/design-system.css') }}"/>
  <link rel="stylesheet" href="{{ asset('assets/css/pages.css') }}"/>
</head>
<body id="admin-login-page">

<div id="page-loader">
  <div class="ldr-wrap">
    <div class="ldr-logo-row">
      <div class="ldr-crest"><img src="{{ asset('assets/images/sumas-logo.png') }}" alt="SUMAS"/></div>
      <div class="ldr-text-col">
        <div class="ldr-uni">SUMAS SmartAttend</div>
        <div class="ldr-name">Admin <em>Panel</em></div>
        <div class="ldr-tag">Restricted Access · Authorised Personnel Only</div>
      </div>
    </div>
    <div class="ldr-divider"></div>
    <div class="ldr-bar-wrap"><div class="ldr-bar-track"><div class="ldr-bar-fill"></div></div></div>
    <div class="ldr-dots"><div class="ldr-dot"></div><div class="ldr-dot"></div><div class="ldr-dot"></div></div>
  </div>
</div>

<div id="toast-container"></div>

<div class="admin-login-page">
  <div class="admin-login-card">

    <div class="admin-login-head">
      <div class="admin-login-crest">
        <img src="{{ asset('assets/images/sumas-logo.png') }}" alt="SUMAS Crest"/>
      </div>
      <div class="admin-badge">🔐 Admin Access Only</div>
      <h1 class="admin-login-title">Admin Panel</h1>
      <p class="admin-login-sub">SUMAS SmartAttend · Administration Console</p>
    </div>

    <div class="form-group">
      <label class="form-label" for="admin-username">Admin Username <span class="req">*</span></label>
      <div class="input-icon-wrap">
        <span class="icon-left">👤</span>
        <input type="text" id="admin-username" class="input"
          placeholder="Enter admin username"
          autocomplete="username" required/>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="admin-password">Password <span class="req">*</span></label>
      <div class="input-icon-wrap">
        <span class="icon-left">🔐</span>
        <input type="password" id="admin-password" class="input"
          placeholder="Enter admin password"
          autocomplete="current-password" required/>
        <button type="button" id="admin-toggle-pw" class="icon-right" aria-label="Toggle password">👁️</button>
      </div>
    </div>

    <button class="btn btn-primary btn-lg btn-block" id="admin-login-btn" type="button">
      Sign In to Admin Panel
    </button>

  </div>
</div>

<script src="{{ asset('assets/js/api.js') }}"></script>
<script src="{{ asset('assets/js/admin.js') }}"></script>
</body>
</html>
