<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0,viewport-fit=cover"/>
  <meta name="theme-color" content="#6B3318"/>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Student Registration — SUMAS SmartAttend</title>
  <link rel="stylesheet" href="{{ asset('assets/css/design-system.css') }}"/>
  <link rel="stylesheet" href="{{ asset('assets/css/pages.css') }}"/>
  <style>
    .reg-page-wrap { overflow-x: clip; }
    .reg-container  { overflow-x: clip; }
    .upload-zone    { cursor: pointer; }
    .photo-box      { cursor: pointer; }
  </style>
</head>
<body id="register-page">

<div id="page-loader">
  <div class="ldr-wrap">
    <div class="ldr-logo-row">
      <div class="ldr-crest"><img src="{{ asset('assets/images/sumas-logo.png') }}" alt="SUMAS"/></div>
      <div class="ldr-text-col">
        <div class="ldr-uni">State University of Medical &amp; Applied Sciences</div>
        <div class="ldr-name">SUMAS <em>Smart</em>Attend</div>
        <div class="ldr-tag">Student Registration Portal</div>
      </div>
    </div>
    <div class="ldr-divider"></div>
    <div class="ldr-bar-wrap"><div class="ldr-bar-track"><div class="ldr-bar-fill"></div></div></div>
    <div class="ldr-dots"><div class="ldr-dot"></div><div class="ldr-dot"></div><div class="ldr-dot"></div></div>
  </div>
</div>

<div id="toast-container"></div>

<!-- TOPBAR -->
<div class="topbar">
  <div class="container"><div class="topbar-inner">
    <div class="topbar-left">
      <div class="topbar-item">📍 SUMAS, Igbo Eno, Enugu State</div>
      <div class="topbar-item">✉️ smartattend@sumas.edu.ng</div>
    </div>
    <div class="topbar-right">
      <a href="{{ route('login') }}" class="topbar-link">🔐 Already registered? Sign In</a>
    </div>
  </div></div>
</div>

<!-- NAVBAR -->
<nav class="navbar">
  <div class="container"><div class="navbar-inner">
    <a href="{{ route('home') }}" class="nav-brand">
      <img src="{{ asset('assets/images/sumas-logo.png') }}" alt="SUMAS" class="nav-logo"/>
      <div class="nav-brand-text">
        <span class="nav-brand-name">SUMAS SmartAttend</span>
        <span class="nav-brand-sub">Student Registration</span>
      </div>
    </a>
    <ul class="nav-menu">
      <li><a href="{{ route('home') }}" class="nav-link">Home</a></li>
      <li><a href="{{ route('register') }}" class="nav-link active">Register</a></li>
      <li><a href="{{ route('login') }}" class="nav-link">Sign In</a></li>
      <li><a href="#status-check" class="nav-link">Check Status</a></li>
    </ul>
    <div class="nav-actions">
      <button class="icon-btn theme-toggle" id="theme-toggle" aria-label="Toggle theme">🌙</button>
      <a href="{{ route('login') }}" class="btn btn-secondary btn-md">Sign In</a>
      <button class="hamburger" id="hamburger" aria-label="Menu" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div></div>
  <div class="mobile-nav" id="mobile-nav">
    <a href="{{ route('home') }}" class="mobile-link">🏠 Home</a>
    <a href="{{ route('register') }}" class="mobile-link active">📝 Register</a>
    <a href="{{ route('login') }}" class="mobile-link">🔐 Sign In</a>
    <a href="#status-check" class="mobile-link">🔍 Check Status</a>
  </div>
</nav>

<!-- PAGE BANNER -->
<div class="page-banner">
  <div class="page-banner-bg"></div>
  <div class="page-banner-grad"></div>
  <div class="container page-banner-content">
    <div class="section-eyebrow page-banner-label">SUMAS SmartAttend</div>
    <h1>Student Registration</h1>
    <p>Complete your AI identity verification to enroll in the smart attendance system.</p>
    <div class="breadcrumbs">
      <a href="{{ route('home') }}">Home</a>
      <span class="sep">›</span>
      <span class="crumb-current">Registration</span>
    </div>
  </div>
</div>

<!-- REGISTRATION FORM -->
<div class="reg-page-wrap">
<div class="reg-container">
<div class="reg-card" id="reg-main-card">

  <!-- Card header -->
  <div class="reg-card-head">
    <div class="reg-head-logo"><img src="{{ asset('assets/images/sumas-logo.png') }}" alt="SUMAS Crest"/></div>
    <div class="reg-head-text">
      <div class="reg-head-title">SmartAttend Registration Form</div>
      <div class="reg-head-sub">State University of Medical and Applied Sciences · Igbo Eno, Enugu State, Nigeria</div>
    </div>
  </div>

  <!-- FORM BODY -->
  <div class="reg-card-body" id="reg-form-body">

    <!-- ① FACULTY, DEPARTMENT & LEVEL -->
    <div class="form-sec">
      <div class="form-sec-title"><span class="sec-icon">🏛</span>Faculty, Department &amp; Academic Level</div>
      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label" for="faculty-select">Faculty <span class="req">*</span></label>
          <select id="faculty-select" class="input" required aria-describedby="faculty-err">
            <option value="">— Select your faculty —</option>
          </select>
          <div class="form-error" id="faculty-err" style="display:none">Please select your faculty.</div>
        </div>
        <div class="form-group">
          <label class="form-label" for="dept-select">Department <span class="req">*</span></label>
          <div class="dept-select-wrap">
            <select id="dept-select" class="input" required aria-describedby="dept-err" disabled>
              <option value="">— Select faculty first —</option>
            </select>
          </div>
          <div class="form-error" id="dept-err" style="display:none">Please select your department.</div>
        </div>

        <div class="form-group">
          <label class="form-label">Academic Level <span class="req">*</span></label>
          <div class="level-chip-row">
            <div class="level-chip"><input type="radio" name="level" id="l100" value="100 Level"/><label for="l100"><span class="lc-num">100</span><span class="lc-txt">Level</span></label></div>
            <div class="level-chip"><input type="radio" name="level" id="l200" value="200 Level"/><label for="l200"><span class="lc-num">200</span><span class="lc-txt">Level</span></label></div>
            <div class="level-chip"><input type="radio" name="level" id="l300" value="300 Level"/><label for="l300"><span class="lc-num">300</span><span class="lc-txt">Level</span></label></div>
            <div class="level-chip"><input type="radio" name="level" id="l400" value="400 Level"/><label for="l400"><span class="lc-num">400</span><span class="lc-txt">Level</span></label></div>
            <div class="level-chip"><input type="radio" name="level" id="l500" value="500 Level"/><label for="l500"><span class="lc-num">500</span><span class="lc-txt">Level</span></label></div>
            <div class="level-chip"><input type="radio" name="level" id="l600" value="600 Level"/><label for="l600"><span class="lc-num">600</span><span class="lc-txt">Level</span></label></div>
          </div>
          <div class="form-error" id="level-err" style="display:none">Please select your level.</div>
        </div>
      </div>
    </div>

    <!-- ② PERSONAL INFORMATION -->
    <div class="form-sec">
      <div class="form-sec-title"><span class="sec-icon">👤</span>Personal Information</div>
      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label" for="field-fullname">Full Name <span class="req">*</span></label>
          <input type="text" id="field-fullname" class="input" placeholder="e.g. Chukwuemeka Obi" required/>
          <div class="form-error" id="err-fullname" style="display:none">Full name is required.</div>
        </div>
        <div class="form-group">
          <label class="form-label" for="field-matric">Matric Number <span class="req">*</span></label>
          <input type="text" id="field-matric" class="input" placeholder="e.g. SUMAS/CS/2023/001" required/>
          <div class="form-error" id="err-matric" style="display:none">Matric number is required.</div>
        </div>
        <div class="form-group">
          <label class="form-label" for="field-email">Email Address <span class="req">*</span></label>
          <input type="email" id="field-email" class="input" placeholder="you@sumas.edu.ng" required/>
          <div class="form-error" id="err-email" style="display:none">Valid email is required.</div>
        </div>
        <div class="form-group">
          <label class="form-label" for="field-phone">Phone Number <span class="req">*</span></label>
          <input type="tel" id="field-phone" class="input" placeholder="+234 800 000 0000" required/>
          <div class="form-error" id="err-phone" style="display:none">Phone number is required.</div>
        </div>
        <div class="form-group">
          <label class="form-label" for="field-gender">Gender</label>
          <select id="field-gender" class="input">
            <option value="">Select Gender</option>
            <option>Male</option>
            <option>Female</option>
            <option>Prefer not to say</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="field-dob">Date of Birth</label>
          <input type="date" id="field-dob" class="input"/>
        </div>
        <div class="form-group">
          <label class="form-label" for="field-state">State of Origin</label>
          <input type="text" id="field-state" class="input" placeholder="e.g. Enugu"/>
        </div>
      </div>
    </div>

    <!-- ③ SET PASSWORD -->
    <div class="form-sec">
      <div class="form-sec-title"><span class="sec-icon">🔐</span>Create Password</div>
      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label" for="field-password">Password <span class="req">*</span></label>
          <div class="input-icon-wrap">
            <span class="icon-left">🔐</span>
            <input type="password" id="field-password" class="input" placeholder="Minimum 6 characters" required/>
            <button type="button" id="toggle-pw-reg" class="icon-right" aria-label="Toggle password">👁️</button>
          </div>
          <div class="form-hint">You'll use this to sign in after registration.</div>
          <div class="form-error" id="err-password" style="display:none">Password must be at least 6 characters.</div>
        </div>
        <div class="form-group">
          <label class="form-label" for="field-password2">Confirm Password <span class="req">*</span></label>
          <div class="input-icon-wrap">
            <span class="icon-left">🔐</span>
            <input type="password" id="field-password2" class="input" placeholder="Repeat password" required/>
            <button type="button" id="toggle-pw-reg2" class="icon-right" aria-label="Toggle password">👁️</button>
          </div>
          <div class="form-error" id="err-password2" style="display:none">Passwords do not match.</div>
        </div>
      </div>
    </div>

    <!-- ④ DOCUMENTS -->
    <div class="form-sec">
      <div class="form-sec-title"><span class="sec-icon">📄</span>Upload Documents</div>
      <div class="alert alert-info" style="margin-bottom:var(--s4)">
        <span>ℹ️</span>
        <span>Upload clear scans or photos. Max 5 MB each. Accepted: JPG, PNG, PDF.</span>
      </div>
      <label class="upload-zone" id="uz-school-id">
        <input type="file" accept="image/*,.pdf" data-doc="school-id"/>
        <span class="uz-icon">🪪</span>
        <div class="uz-info"><div class="uz-name">School ID Card</div><div class="uz-hint">JPG, PNG or PDF</div><div class="uz-bar"><div class="uz-fill"></div></div></div>
        <span class="badge badge-red" style="flex-shrink:0;margin-left:auto">Required</span>
      </label>
      <label class="upload-zone" id="uz-admission">
        <input type="file" accept="image/*,.pdf" data-doc="admission"/>
        <span class="uz-icon">📜</span>
        <div class="uz-info"><div class="uz-name">Admission Letter</div><div class="uz-hint">JPG, PNG or PDF</div><div class="uz-bar"><div class="uz-fill"></div></div></div>
        <span class="badge badge-red" style="flex-shrink:0;margin-left:auto">Required</span>
      </label>
      <label class="upload-zone" id="uz-clearance">
        <input type="file" accept="image/*,.pdf" data-doc="clearance"/>
        <span class="uz-icon">✅</span>
        <div class="uz-info"><div class="uz-name">Department Clearance Form</div><div class="uz-hint">JPG, PNG or PDF</div><div class="uz-bar"><div class="uz-fill"></div></div></div>
        <span class="badge badge-red" style="flex-shrink:0;margin-left:auto">Required</span>
      </label>
      <label class="upload-zone" id="uz-natid">
        <input type="file" accept="image/*,.pdf" data-doc="nat-id"/>
        <span class="uz-icon">🆔</span>
        <div class="uz-info"><div class="uz-name">National ID Card</div><div class="uz-hint">JPG, PNG or PDF</div><div class="uz-bar"><div class="uz-fill"></div></div></div>
        <span class="badge badge-gray" style="flex-shrink:0;margin-left:auto">Optional</span>
      </label>
    </div>

    <!-- ⑤ PASSPORT PHOTOS -->
    <div class="form-sec">
      <div class="form-sec-title"><span class="sec-icon">📸</span>Passport Photographs</div>
      <div class="alert alert-warning" style="margin-bottom:var(--s4)">
        <span>📌</span>
        <span><strong>Requirements:</strong> White background · Clear face · Good lighting · Taken within 6 months · No glasses or head coverings</span>
      </div>
      <div class="photo-row">
        <div class="photo-box" id="pb-1">
          <input type="file" accept="image/*" data-doc="pp-1"/>
          <div class="photo-inner"><span class="photo-icon">📸</span><span class="photo-lbl">Photo 1</span><span class="photo-req">Required</span></div>
        </div>
        <div class="photo-box" id="pb-2">
          <input type="file" accept="image/*" data-doc="pp-2"/>
          <div class="photo-inner"><span class="photo-icon">📸</span><span class="photo-lbl">Photo 2</span><span class="photo-req">Required</span></div>
        </div>
        <div class="photo-box" id="pb-3">
          <input type="file" accept="image/*" data-doc="pp-3"/>
          <div class="photo-inner"><span class="photo-icon">📸</span><span class="photo-lbl">Photo 3</span><span class="photo-req">Required</span></div>
        </div>
      </div>
    </div>

    <!-- ⑥ FACE ENROLLMENT (performed by administration) -->
    <div class="form-sec">
      <div class="form-sec-title"><span class="sec-icon">🤖</span>Face Enrollment</div>
      <div class="alert alert-info" style="margin-bottom:var(--s4)">
        <span>💡</span>
        <span><strong>Biometric security:</strong> your face is enrolled securely by the SmartAttend administration using a supervised webcam capture at the ICT office — after your registration is approved. You do not need a camera for this step.</span>
      </div>
      <div class="face-enroll-info">
        <div class="fei-step"><span class="fei-num">1</span><div><div class="fei-title">Submit your registration</div><div class="fei-sub">Your details and documents are reviewed by the registrar.</div></div></div>
        <div class="fei-step"><span class="fei-num">2</span><div><div class="fei-title">Visit the ICT office</div><div class="fei-sub">Bring your SUMAS ID card for the supervised webcam capture.</div></div></div>
        <div class="fei-step"><span class="fei-num">3</span><div><div class="fei-title">Face enrolled by admin</div><div class="fei-sub">An administrator captures and records your face — your account becomes fully verified.</div></div></div>
      </div>
      <div class="cam-warn" style="margin-top:var(--s4)">
        ⚠️ <strong>Academic Warning:</strong> Impersonation is a serious offence under the SUMAS Academic Code of Conduct.
      </div>
    </div>

    <!-- ⑦ SUBMIT -->
    <div class="reg-submit">
      <div class="alert alert-warning" style="margin-bottom:var(--s5)">
        <span>⚠️</span>
        <span>By submitting this form you confirm all information is accurate. False information is an academic offence.</span>
      </div>
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--s4)">
        <p style="font-size:.85rem;color:var(--t-muted)">
          Already registered? <a href="{{ route('login') }}" style="color:var(--brand);font-weight:700">Sign In →</a>
        </p>
        <button class="btn btn-gold btn-xl" id="final-submit-btn" type="button">
          🚀 &nbsp;Submit Registration
        </button>
      </div>
    </div>

  </div><!-- /reg-form-body -->

  <!-- SUCCESS STATE -->
  <div class="reg-success" id="reg-success-view">
    <span class="reg-success-icon">🎉</span>
    <h2 class="reg-success-title">Registration Submitted!</h2>
    <p class="reg-success-body">Your registration has been received and is now <strong>under review</strong>. You will be able to sign in once the administration approves your registration.</p>
    <p class="reg-success-body" id="reg-docs-count" style="margin-top:var(--s2);font-size:.9rem"></p>
    <div style="display:flex;gap:var(--s4);justify-content:center;flex-wrap:wrap">
      <a href="#status-check" class="btn btn-primary btn-lg">🔍 Check Registration Status</a>
      <a href="{{ route('login') }}" class="btn btn-secondary btn-lg">Sign In</a>
    </div>
  </div>

</div><!-- /reg-card -->

<!-- STATUS CHECK -->
<div class="status-card" id="status-check">
  <div class="status-card-head">
    <img src="{{ asset('assets/images/sumas-logo.png') }}" alt="SUMAS" class="status-card-head-logo"/>
    <h3>Check Registration Status</h3>
  </div>
  <div class="status-card-body">
    <p style="font-size:.875rem;color:var(--t-muted);margin-bottom:var(--s4)">Enter your matric number to track your registration status.</p>
    <div class="status-search-row">
      <input type="text" id="status-matric-input" class="input" placeholder="e.g. SUMAS/CS/2023/001"/>
      <button class="btn btn-primary btn-lg" id="status-check-btn" type="button">Check Status</button>
    </div>
    <div id="status-result" style="display:none" class="status-result"></div>
  </div>
</div>

</div><!-- /reg-container -->
</div><!-- /reg-page-wrap -->

<!-- FOOTER -->
<footer class="footer" style="padding-top:var(--s8)">
  <div class="container">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:var(--s5) 0;border-top:1px solid rgba(255,255,255,.07);flex-wrap:wrap;gap:var(--s3)">
      <div class="footer-logo-row" style="margin:0">
        <img src="{{ asset('assets/images/sumas-logo.png') }}" alt="SUMAS" class="footer-logo"/>
        <div><div class="footer-brand-name">SUMAS SmartAttend</div><div class="footer-brand-sub">AI Attendance System</div></div>
      </div>
      <div style="font-size:.74rem;color:rgba(255,255,255,.3)">
        © 2025 SUMAS. All rights reserved. &nbsp;·&nbsp;
        <a href="{{ route('home') }}" style="color:var(--g300)">Home</a>
      </div>
    </div>
  </div>
</footer>

<script src="{{ asset('assets/js/api.js') }}"></script>
<script src="{{ asset('assets/js/app.js') }}"></script>
</body>
</html>
