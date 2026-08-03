<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0,viewport-fit=cover"/>
  <meta name="theme-color" content="#6B3318"/>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Student Dashboard — SUMAS SmartAttend</title>
  <link rel="stylesheet" href="{{ asset('assets/css/design-system.css') }}"/>
  <link rel="stylesheet" href="{{ asset('assets/css/pages.css') }}"/>


<style>
#student-dashboard{overflow-x:clip} /* clip keeps position:sticky working on the sidebar */
.dash-dropdown.open{display:block!important}
.dash-sidebar{position:sticky;top:0;height:100vh;overflow-y:auto}
@media(max-width:900px){.dash-header{position:sticky;top:52px!important;z-index:50}}
@media(max-width:640px){.dash-header{top:44px!important}}
@media(max-width:480px){.dash-header{top:42px!important}}

/* ── Lecture cards ── */
.lecture-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:var(--s4)}
.lecture-card{background:var(--white);border:1px solid var(--bdr-light);border-radius:var(--r-xl);padding:var(--s5);box-shadow:var(--sh-xs);transition:all var(--dur-mid) var(--ease);position:relative;overflow:hidden}
.lecture-card:hover{box-shadow:var(--sh-md);transform:translateY(-3px)}
.lecture-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--brand),var(--g400))}
.lecture-card.attended::before{background:var(--success)}
.lecture-card-head{display:flex;justify-content:space-between;align-items:center;gap:var(--s2)}
.lecture-card-course{font-family:var(--f-mono);font-size:.68rem;font-weight:700;letter-spacing:.5px;color:var(--brand);text-transform:uppercase}
.lecture-card.attended .lecture-card-course{color:var(--success)}
.lc-dot{opacity:.5}
.lecture-card-title{font-size:1rem;font-weight:800;color:var(--t-primary);margin:var(--s2) 0 var(--s1);line-height:1.35}
.lecture-card-meta{font-size:.74rem;color:var(--t-muted);line-height:1.6}
.lecture-card-content{font-size:.83rem;color:var(--t-secondary);line-height:1.6;margin-top:var(--s3);display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}
.lecture-card-foot{margin-top:var(--s4);padding-top:var(--s3);border-top:1px dashed var(--bdr-light);font-size:.76rem;font-weight:600}
.lecture-card-foot.attended{color:var(--success)}
.lecture-card-foot.live{color:var(--brand)}

/* ── Live lecture cards: clickable to scan ── */
.lecture-card.scannable{cursor:pointer}
.lecture-card.scannable:hover{box-shadow:0 10px 30px rgba(107,51,24,.16);transform:translateY(-3px)}
.lecture-card.scannable .lecture-card-title{color:var(--brand)}
.lc-scan-btn{display:inline-flex;align-items:center;gap:6px;margin-top:var(--s3);width:100%;justify-content:center;
  height:38px;border-radius:10px;border:1.5px solid var(--brand);background:var(--brand-pale);
  color:var(--brand);font-family:var(--f-ui);font-size:.8rem;font-weight:700;cursor:pointer;
  transition:all var(--dur-fast) var(--ease)}
.lc-scan-btn:hover{background:var(--brand);color:#fff;box-shadow:var(--sh-brand)}
.att-score{margin-top:2px}

/* ── QR scan modal ── */
.scan-modal-box{max-width:460px}
.scan-lecture{background:var(--surf-2);border:1px solid var(--bdr-light);border-radius:var(--r-lg);padding:12px 14px;margin-bottom:14px}
.scan-lecture-course{font-family:var(--f-mono);font-size:.66rem;font-weight:700;letter-spacing:.6px;color:var(--brand);text-transform:uppercase}
.scan-lecture-title{font-size:.95rem;font-weight:800;color:var(--t-primary);margin-top:3px}
.scan-lecture-meta{font-size:.74rem;color:var(--t-muted);margin-top:3px;line-height:1.6}
.scan-camera-wrap{position:relative;border-radius:var(--r-lg);overflow:hidden;background:#0f0f10;aspect-ratio:1/1}
.scan-camera-wrap video{width:100%;height:100%;object-fit:cover;display:block}
.scan-camera-wrap canvas{display:none}
.scan-camera-overlay{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;
  color:rgba(255,255,255,.8);font-size:.84rem;text-align:center;padding:20px;line-height:1.5}
.scan-frame{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:64%;height:64%;pointer-events:none}
.scan-frame::before,.scan-frame::after{content:'';position:absolute;width:26px;height:26px;border:3px solid var(--g400);border-radius:4px}
.scan-frame::before{top:-3px;left:-3px;border-right:none;border-bottom:none}
.scan-frame::after{bottom:-3px;right:-3px;border-left:none;border-top:none}
.scan-scanline{position:absolute;left:6%;right:6%;height:2px;top:18%;background:linear-gradient(90deg,transparent,var(--g400),transparent);
  box-shadow:0 0 12px rgba(212,160,32,.8);animation:scanlineMove 2.2s ease-in-out infinite}
@keyframes scanlineMove{0%,100%{top:16%}50%{top:80%}}
.scan-status{margin-top:14px}
.scan-actions{margin-top:14px;display:flex;gap:var(--s3);flex-wrap:wrap}
.scan-actions .btn{flex:1;min-width:150px}
.scan-hint{font-size:.72rem;color:var(--t-muted);margin-top:12px;line-height:1.6}
.scan-err{border-radius:var(--r-lg);padding:12px 14px;font-size:.84rem;font-weight:600;line-height:1.5;margin-top:12px}
.scan-err.error{background:var(--error-bg);color:var(--error)}
.scan-err.success{background:var(--success-bg);color:var(--success)}
.scan-err.warn{background:var(--g100);color:var(--g700)}

/* ── Attendance cards ── */
.att-card{display:flex;align-items:flex-start;gap:var(--s3);background:var(--white);border:1px solid var(--bdr-light);border-radius:var(--r-lg);padding:var(--s4);margin-bottom:var(--s3);transition:all var(--dur-fast) var(--ease)}
.att-card:hover{border-color:var(--b300);box-shadow:var(--sh-sm)}
.att-card-icon{width:42px;height:42px;border-radius:12px;background:var(--surf-2);display:flex;align-items:center;justify-content:center;font-size:1.15rem;flex-shrink:0}
.att-card-body{flex:1;min-width:0}
.att-card-course{font-weight:700;font-size:.88rem;color:var(--t-primary)}
.att-card-lecture{font-size:.8rem;color:var(--t-secondary);margin-top:2px}
.att-card-meta{font-size:.72rem;color:var(--t-muted);margin-top:4px}
.att-card-side{display:flex;flex-direction:column;align-items:flex-end;gap:6px;flex-shrink:0}
.att-source{margin-top:2px}
</style>
</head>
<body>

<div id="page-loader"><div class="ldr-wrap"><div class="ldr-logo-row">
  <div class="ldr-crest"><img src="{{ asset('assets/images/sumas-logo.png') }}" alt="SUMAS"/></div>
  <div class="ldr-text-col"><div class="ldr-uni">State University of Medical &amp; Applied Sciences</div>
    <div class="ldr-name">SUMAS <em>Smart</em>Attend</div><div class="ldr-tag">Loading your dashboard…</div></div>
</div><div class="ldr-divider"></div>
<div class="ldr-bar-wrap"><div class="ldr-bar-track"><div class="ldr-bar-fill"></div></div></div>
<div class="ldr-dots"><div class="ldr-dot"></div><div class="ldr-dot"></div><div class="ldr-dot"></div></div>
</div></div>
<div id="toast-container"></div>

<div id="student-dashboard" class="dash-root student-dash">

  <!-- ══════════════════════════════
       PREMIUM SIDEBAR
  ══════════════════════════════ -->
  <aside class="dash-sidebar" role="navigation" aria-label="Dashboard navigation">

    <!-- Brand with real logo -->
    <div class="dash-sb-brand">
      <img src="{{ asset('assets/images/sumas-logo.png') }}" alt="SUMAS Crest" class="dash-sb-logo"/>
      <div>
        <div class="dash-sb-name">SmartAttend</div>
        <div class="dash-sb-sub">SUMAS · Igbo Eno</div>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="dash-sb-nav">
      <div class="dash-sb-section">Main</div>
      <a class="dash-nav-link active" data-section="overview" href="#" role="menuitem">
        <span class="dash-nav-icon">📊</span> Overview
      </a>
      <a class="dash-nav-link" data-section="courses" href="#" role="menuitem">
        <span class="dash-nav-icon">📚</span> My Courses
      </a>
      <a class="dash-nav-link" data-section="attendance" href="#" role="menuitem">
        <span class="dash-nav-icon">✅</span> Attendance
      </a>
      <a class="dash-nav-link" data-section="lectures" href="#" role="menuitem">
        <span class="dash-nav-icon">📝</span> Lectures
      </a>
      <a class="dash-nav-link" data-section="registration" href="#" role="menuitem">
        <span class="dash-nav-icon">📝</span> My Registration
      </a>
      <a class="dash-nav-link" data-section="documents" href="#" role="menuitem">
        <span class="dash-nav-icon">📄</span> Documents
        <span class="dash-nav-badge" id="nav-docs-badge">0</span>
      </a>
      <a class="dash-nav-link" data-section="verification" href="#" role="menuitem">
        <span class="dash-nav-icon">🤖</span> AI Verification
      </a>
      <div class="dash-sb-section">Account</div>
      <a class="dash-nav-link" data-section="profile" href="#" role="menuitem">
        <span class="dash-nav-icon">👤</span> My Profile
      </a>
      <a class="dash-nav-link" href="#" data-logout role="menuitem">
        <span class="dash-nav-icon">🔓</span> Sign Out
      </a>
    </nav>

    <!-- Sidebar user footer -->
    <div class="dash-sb-footer">
      <div class="dash-sb-user">
        <div class="dash-sb-avatar" data-avatar>S</div>
        <div>
          <div class="dash-sb-user-name" data-user-name>Student</div>
          <div class="dash-sb-user-role">Registered Student</div>
        </div>
      </div>
    </div>

  </aside>

  <!-- ══════════════════════════════
       MAIN CONTENT AREA
  ══════════════════════════════ -->
  <div class="dash-main">

    <!-- ── Premium Header with Logo ── -->
    <header class="dash-header">
      <div class="dash-header-logo-row">
        <!-- Logo in header -->
        <a href="{{ route('home') }}" style="display:flex;align-items:center;gap:10px;text-decoration:none">
          <img src="{{ asset('assets/images/sumas-logo.png') }}" alt="SUMAS" class="dash-header-logo"/>
          <div>
            <div class="dash-header-page-name" id="dash-page-title-text">Overview</div>
            <div style="font-family:var(--f-ui);font-size:.6rem;color:var(--t-light);letter-spacing:.5px">SUMAS SmartAttend</div>
          </div>
        </a>
      </div>

      <div class="dash-header-mid">
        <div class="dash-search-wrap">
          <span class="icon-left">🔍</span>
          <input type="text" class="dash-search" placeholder="Search records, documents…" aria-label="Search"/>
        </div>
      </div>

      <div class="dash-header-right">
        <!-- Theme toggle -->
        <button class="dash-hdr-btn" id="theme-toggle" aria-label="Toggle theme" title="Toggle dark mode">🌙</button>

        <!-- Notifications -->
        <div style="position:relative">
          <button class="dash-hdr-btn" id="notif-btn" aria-label="Notifications" aria-haspopup="true">
            🔔<span class="hdr-badge">2</span>
          </button>
          <div class="dash-dropdown notif-panel" id="notif-dd">
            <div class="dd-head">Notifications</div>
            <div id="notif-items"></div>
          </div>
        </div>

        <!-- User avatar + dropdown -->
        <div style="position:relative">
          <div class="dash-hdr-avatar" id="user-menu-btn" role="button" tabindex="0" aria-haspopup="true" data-avatar aria-label="User menu">S</div>
          <div class="dash-dropdown" id="user-menu-dd">
            <div class="dd-head" data-user-name>Student</div>
            <div class="dd-item" onclick="showSection('profile')"><span>👤</span> My Profile</div>
            <div class="dd-item" onclick="showSection('registration')"><span>📝</span> My Registration</div>
            <div class="dd-divider"></div>
            <div class="dd-item danger" data-logout><span>🔓</span> Sign Out</div>
          </div>
        </div>
      </div>
    </header>

    <!-- ── Dashboard Content ── -->
    <div class="dash-content">

      <!-- ════════════════ OVERVIEW ════════════════ -->
      <section class="dash-section active" id="section-overview">

        <!-- Welcome Banner -->
        <div class="wb">
          <div class="wb-bg"></div>
          <div class="wb-glow"></div>
          <div class="wb-inner">
            <div class="wb-left">
              <div class="wb-eyebrow">SUMAS SmartAttend · Student Dashboard</div>
              <h2 class="wb-title">Welcome back, <em id="wb-name">Student</em> 👋</h2>
              <p class="wb-sub">Track your registration and AI identity verification status below.</p>
            </div>
            <div class="wb-right">
              <div class="wb-stat">
                <div class="wb-stat-icon" id="wb-status-icon">⏳</div>
                <div class="wb-stat-val" id="wb-status-val">Under Review</div>
                <div class="wb-stat-lbl">Status</div>
              </div>
              <div class="wb-stat">
                <div class="wb-stat-icon">📅</div>
                <div class="wb-stat-val" id="wb-submitted">—</div>
                <div class="wb-stat-lbl">Submitted</div>
              </div>
              <div class="wb-stat">
                <div class="wb-stat-icon">📄</div>
                <div class="wb-stat-val" id="wb-docs">—</div>
                <div class="wb-stat-lbl">Documents</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Metric Cards -->
        <div class="metric-grid">
          <div class="metric-card mc-brand">
            <div class="metric-card-icon">📝</div>
            <div class="metric-card-val" id="mc-reg-val">Submitted</div>
            <div class="metric-card-lbl">Registration</div>
            <div class="metric-card-change">✓ Active</div>
          </div>
          <div class="metric-card mc-gold">
            <div class="metric-card-icon">📄</div>
            <div class="metric-card-val" id="mc-docs-val">0 Files</div>
            <div class="metric-card-lbl">Documents Uploaded</div>
          </div>
          <div class="metric-card mc-green">
            <div class="metric-card-icon">🤖</div>
            <div class="metric-card-val" id="mc-verify-val">Pending</div>
            <div class="metric-card-lbl">AI Verification</div>
          </div>
          <div class="metric-card mc-gray">
            <div class="metric-card-icon">📅</div>
            <div class="metric-card-val" id="mc-date-val">—</div>
            <div class="metric-card-lbl">Date Submitted</div>
          </div>
        </div>

        <!-- Alerts -->
        <div class="dash-alerts" id="dash-alerts-wrap"></div>

        <!-- Grid: Status tracker + Info -->
        <div class="dash-grid-2">
          <div class="widget">
            <div class="widget-head">
              <div class="widget-head-left">
                <div class="widget-head-icon">📍</div>
                <span class="widget-title">Registration Progress</span>
              </div>
              <span class="widget-action" onclick="showSection('registration')">View details →</span>
            </div>
            <div class="widget-body">
              <div class="status-track">
                <div class="st-item done">
                  <div class="st-dot">✓</div>
                  <div class="st-body"><span class="st-title">Account Created</span><span class="st-time">Completed</span></div>
                </div>
                <div class="st-item done">
                  <div class="st-dot">✓</div>
                  <div class="st-body"><span class="st-title">Documents Uploaded</span><span class="st-time">Submitted successfully</span></div>
                </div>
                <div class="st-item" id="st-verify-item">
                  <div class="st-dot pending" id="st-dot-verify">3</div>
                  <div class="st-body"><span class="st-title">AI Face Verification</span><span class="st-time" id="st-verify-time">Pending Review</span></div>
                </div>
                <div class="st-item">
                  <div class="st-dot pending">4</div>
                  <div class="st-body"><span class="st-title">Admin Approval</span><span class="st-time">Awaiting verification</span></div>
                </div>
                <div class="st-item">
                  <div class="st-dot pending">5</div>
                  <div class="st-body"><span class="st-title">SmartAttend Active</span><span class="st-time">Pending approval</span></div>
                </div>
              </div>
            </div>
          </div>

          <div class="widget">
            <div class="widget-head">
              <div class="widget-head-left">
                <div class="widget-head-icon">👤</div>
                <span class="widget-title">Student Information</span>
              </div>
              <span class="widget-action" onclick="showSection('profile')">Edit profile →</span>
            </div>
            <div class="widget-body">
              <div class="info-rows">
                <div class="info-row"><span class="info-key">Full Name</span><span class="info-val" id="inf-name">—</span></div>
                <div class="info-row"><span class="info-key">Matric No.</span><span class="info-val" id="inf-matric">—</span></div>
                <div class="info-row"><span class="info-key">Department</span><span class="info-val" id="inf-dept">—</span></div>
                <div class="info-row"><span class="info-key">Level</span><span class="info-val" id="inf-level">—</span></div>
                <div class="info-row"><span class="info-key">Email</span><span class="info-val" id="inf-email">—</span></div>
                <div class="info-row"><span class="info-key">Status</span><span class="info-val" id="inf-status">—</span></div>
              </div>
              <div style="margin-top:var(--s4)">
                <a href="{{ route('register') }}" class="btn btn-secondary btn-sm">📝 Update Registration</a>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Activity Widget -->
        <div class="widget">
          <div class="widget-head">
            <div class="widget-head-left">
              <div class="widget-head-icon">⚡</div>
              <span class="widget-title">Recent Activity</span>
            </div>
          </div>
          <div class="widget-body">
            <div class="activity-feed" id="activity-feed">
              <div class="activity-item"><div class="activity-icon">⏳</div><div><div class="activity-title">Loading activity…</div><div class="activity-sub"></div></div></div>
            </div>
          </div>
        </div>

      </section>

      <!-- ════════════════ COURSES ════════════════ -->
      <section class="dash-section" id="section-courses">
        <div class="dash-page-title">
          <div><div class="dash-page-title-text">My Courses</div><div class="dash-page-title-sub">Courses available in your department</div></div>
        </div>

        <div class="widget">
          <div class="widget-head">
            <div class="widget-head-left"><div class="widget-head-icon">📚</div><span class="widget-title">Available Courses</span></div>
          </div>
          <div class="widget-body" id="courses-list">
            <p style="color:var(--t-muted)">Loading courses...</p>
          </div>
        </div>
      </section>

      <!-- ════════════════ ATTENDANCE ════════════════ -->
      <section class="dash-section" id="section-attendance">
        <div class="dash-page-title">
          <div><div class="dash-page-title-text">Attendance Records</div><div class="dash-page-title-sub">View your attendance history across courses</div></div>
        </div>
        <div class="dash-card">
          <div class="dash-card-body" id="attendance-list">
            <p style="color:var(--t-muted)">Loading attendance records...</p>
          </div>
        </div>
      </section>

      <!-- ════════════════ LECTURES ════════════════ -->
      <section class="dash-section" id="section-lectures">
        <div class="dash-page-title">
          <div><div class="dash-page-title-text">Lectures & Updates</div><div class="dash-page-title-sub">View lectures and course announcements from your lecturers</div></div>
        </div>
        <div class="dash-card">
          <div class="dash-card-body" id="lectures-list">
            <p style="color:var(--t-muted)">Loading lectures...</p>
          </div>
        </div>
      </section>

      <!-- ════════════════ REGISTRATION ════════════════ -->
      <section class="dash-section" id="section-registration">
        <div class="dash-page-title">
          <div><div class="dash-page-title-text">My Registration Details</div><div class="dash-page-title-sub">Complete registration summary and timeline</div></div>
          <a href="{{ route('register') }}" class="btn btn-secondary btn-sm">📝 Update Registration</a>
        </div>
        <div class="dash-grid-2">
          <div class="widget">
            <div class="widget-head">
              <div class="widget-head-left"><div class="widget-head-icon">📋</div><span class="widget-title">Registration Summary</span></div>
            </div>
            <div class="widget-body">
              <div class="rs-rows" id="rs-tbody"></div>
            </div>
          </div>
          <div class="widget">
            <div class="widget-head">
              <div class="widget-head-left"><div class="widget-head-icon">📅</div><span class="widget-title">Registration Timeline</span></div>
            </div>
            <div class="widget-body">
              <div class="timeline">
                <div class="tl-item"><div class="tl-dot"></div><div><div class="tl-title">Registration Started</div><div class="tl-sub">Department and level selected</div><div class="tl-date" id="tl-1-date">—</div></div></div>
                <div class="tl-item"><div class="tl-dot"></div><div><div class="tl-title">Documents Uploaded</div><div class="tl-sub">All required files submitted</div><div class="tl-date" id="tl-2-date">—</div></div></div>
                <div class="tl-item"><div class="tl-dot" id="tl-dot-3"></div><div><div class="tl-title">AI Verification</div><div class="tl-sub" id="tl-3-sub">Awaiting completion</div><div class="tl-date" id="tl-3-date">Pending</div></div></div>
                <div class="tl-item"><div class="tl-dot muted"></div><div><div class="tl-title">Admin Review</div><div class="tl-sub">Under SUMAS administration review</div><div class="tl-date">Pending</div></div></div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ════════════════ DOCUMENTS ════════════════ -->
      <section class="dash-section" id="section-documents">
        <div class="dash-page-title">
          <div><div class="dash-page-title-text">My Documents</div><div class="dash-page-title-sub">Upload, view and manage your registration documents</div></div>
        </div>

        <!-- Upload widget -->
        <div class="widget" style="margin-bottom:var(--s5)">
          <div class="widget-head"><div class="widget-head-left"><div class="widget-head-icon">📤</div><span class="widget-title">Upload a Document</span></div></div>
          <div class="widget-body">
            <div class="doc-upload-row">
              <select id="doc-upload-type" class="input" aria-label="Document type">
                <option value="school-id">School ID Card</option>
                <option value="admission">Admission Letter</option>
                <option value="clearance">Department Clearance</option>
                <option value="nat-id">National ID Card</option>
                <option value="pp-1">Passport Photo 1</option>
                <option value="pp-2">Passport Photo 2</option>
                <option value="pp-3">Passport Photo 3</option>
              </select>
              <label class="doc-file-btn btn btn-secondary btn-md">
                📎 Choose File
                <input type="file" id="doc-upload-file" accept="image/*,.pdf" style="display:none"/>
              </label>
              <span class="doc-file-name" id="doc-file-name">No file selected</span>
              <button class="btn btn-primary btn-md" id="doc-upload-btn">Upload →</button>
            </div>
            <div class="uz-bar" id="doc-upload-progress" style="margin-top:var(--s3)"><div class="uz-fill" id="doc-upload-fill"></div></div>
          </div>
        </div>

        <!-- Documents list (filled by JS) -->
        <div class="doc-grid" id="doc-list">
          <div class="doc-card"><div class="doc-icon">⏳</div><div class="doc-name">Loading documents…</div></div>
        </div>
      </section>

      <!-- ════════════════ AI VERIFICATION ════════════════ -->
      <section class="dash-section" id="section-verification">
        <div class="dash-page-title">
          <div><div class="dash-page-title-text">Face Verification</div><div class="dash-page-title-sub">Biometric identity verification status</div></div>
        </div>
        <div class="dash-grid-2">
          <div class="widget">
            <div class="widget-head"><div class="widget-head-left"><div class="widget-head-icon">🤖</div><span class="widget-title">Verification Status</span></div></div>
            <div class="widget-body">
              <div class="verify-status-display">
                <div class="vsd-icon" id="vsd-icon">🫥</div>
                <div><div class="vsd-title" id="vsd-title">⏳ Pending Enrollment</div><div class="vsd-sub" id="vsd-sub">Your face will be enrolled by the administration.</div></div>
              </div>
              <div class="vm-chips">
                <div class="vm-chip"><span class="vm-chip-val" id="vmc-status">—</span><span class="vm-chip-lbl">Enrollment</span></div>
                <div class="vm-chip"><span class="vm-chip-val" id="vmc-verified">—</span><span class="vm-chip-lbl">Verified</span></div>
                <div class="vm-chip"><span class="vm-chip-val" id="vmc-date">—</span><span class="vm-chip-lbl">Enrolled Date</span></div>
                <div class="vm-chip"><span class="vm-chip-val" id="vmc-student">—</span><span class="vm-chip-lbl">Status</span></div>
              </div>
              <div style="margin-top:var(--s4)">
                <div class="alert alert-info" style="margin:0">
                  <span>ℹ️</span>
                  <span>Webcam face enrollment is performed by the SUMAS administration at the ICT office after approval.</span>
                </div>
              </div>
            </div>
          </div>
          <div class="widget">
            <div class="widget-head"><div class="widget-head-left"><div class="widget-head-icon">🛡️</div><span class="widget-title">Security Checks</span></div></div>
            <div class="widget-body">
              <div class="sec-check-list">
                <div class="sc-row"><span class="sc-ico">🧬</span><div><span class="sc-name">Face Enrollment</span><span class="sc-stat sc-pend" id="sc-bio-stat">Pending</span></div></div>
                <div class="sc-row"><span class="sc-ico">📋</span><div><span class="sc-name">Document Authenticity</span><span class="sc-stat sc-pend" id="sc-doc-stat">Under review</span></div></div>
                <div class="sc-row"><span class="sc-ico">🏛</span><div><span class="sc-name">University Record Match</span><span class="sc-stat sc-pend" id="sc-reg-stat">Verifying with registry</span></div></div>
                <div class="sc-row"><span class="sc-ico">🔍</span><div><span class="sc-name">Duplicate Detection</span><span class="sc-stat sc-ok">No duplicates found</span></div></div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ════════════════ PROFILE ════════════════ -->
      <section class="dash-section" id="section-profile">
        <div class="dash-page-title">
          <div><div class="dash-page-title-text">My Profile</div><div class="dash-page-title-sub">Your personal information and account details</div></div>
        </div>
        <div class="profile-grid">
          <!-- Avatar card -->
          <div class="widget">
            <div class="widget-body profile-avatar-inner">
              <div class="profile-big-avatar" data-avatar>S</div>
              <div class="profile-name" id="profile-name">Student</div>
              <div class="profile-id" id="profile-id">—</div>
              <div style="margin:var(--s3) 0"><span class="badge badge-brand badge-lg" id="profile-status-badge">⏳ Under Review</span></div>
              <!-- SUMAS logo in profile card -->
              <div style="margin-top:var(--s5);padding-top:var(--s5);border-top:1px solid var(--bdr-light);display:flex;align-items:center;justify-content:center;gap:var(--s3)">
                <img src="{{ asset('assets/images/sumas-logo.png') }}" alt="SUMAS" style="width:36px;height:36px;object-fit:contain"/>
                <div style="text-align:left">
                  <div style="font-family:var(--f-display);font-size:.78rem;font-weight:700;color:var(--brand);line-height:1.2">SUMAS</div>
                  <div style="font-family:var(--f-ui);font-size:.58rem;color:var(--t-light);text-transform:uppercase;letter-spacing:1px">SmartAttend</div>
                </div>
              </div>
            </div>
          </div>
          <!-- Profile form -->
          <div class="widget">
            <div class="widget-head"><div class="widget-head-left"><div class="widget-head-icon">✏️</div><span class="widget-title">Profile Information</span></div></div>
            <div class="widget-body">
              <div class="form-grid-2">
                <div class="form-group"><label class="form-label">Full Name</label><input type="text" class="input" id="pf-name" readonly style="background:var(--surf-2)"/></div>
                <div class="form-group"><label class="form-label">Matric Number</label><input type="text" class="input" id="pf-matric" readonly style="background:var(--surf-2)"/></div>
                <div class="form-group"><label class="form-label">Department</label><input type="text" class="input" id="pf-dept" readonly style="background:var(--surf-2)"/></div>
                <div class="form-group"><label class="form-label">Academic Level</label><input type="text" class="input" id="pf-level" readonly style="background:var(--surf-2)"/></div>
                <div class="form-group"><label class="form-label">Email Address</label><input type="email" class="input" id="pf-email" readonly style="background:var(--surf-2)"/></div>
                <div class="form-group"><label class="form-label">Phone Number</label><input type="tel" class="input" id="pf-phone" placeholder="+234…"/></div>
                <div class="form-group"><label class="form-label">Gender</label>
                  <select class="input" id="pf-gender">
                    <option value="">Select Gender</option>
                    <option>Male</option>
                    <option>Female</option>
                    <option>Prefer not to say</option>
                  </select>
                </div>
                <div class="form-group"><label class="form-label">Date of Birth</label><input type="date" class="input" id="pf-dob"/></div>
                <div class="form-group"><label class="form-label">State of Origin</label><input type="text" class="input" id="pf-state" placeholder="e.g. Enugu"/></div>
                <div class="form-group"><label class="form-label">Faculty</label>
                  <select class="input" id="pf-faculty">
                    <option value="">Select Faculty</option>
                    <option>Faculty of Clinical Sciences</option>
                    <option>Faculty of Basic Medical Sciences</option>
                    <option>Faculty of Health Sciences &amp; Technology</option>
                    <option>Faculty of Computing &amp; Applied Sciences</option>
                    <option>Faculty of Pharmaceutical Sciences</option>
                  </select>
                </div>
              </div>
              <div style="display:flex;gap:var(--s3);margin-top:var(--s5);flex-wrap:wrap">
                <button class="btn btn-primary btn-md" id="profile-save-btn">💾 Save Profile</button>
                <a href="{{ route('register') }}" class="btn btn-secondary btn-md">📝 Update Registration</a>
                <button class="btn btn-secondary btn-md" data-logout>🔓 Sign Out</button>
              </div>
            </div>
          </div>
        </div>
      </section>

    </div><!-- /dash-content -->
  </div><!-- /dash-main -->
</div><!-- /dash-root -->



<!-- QR SCAN MODAL (camera check-in) -->
<div class="student-modal" id="scan-modal" style="display:none">
  <div class="modal-box scan-modal-box">
    <div class="modal-head">
      <div class="modal-name">📷 Scan Attendance QR</div>
      <button class="modal-close" id="close-scan-modal">✕</button>
    </div>
    <div class="modal-body">
      <div class="scan-lecture" id="scan-lecture-info"></div>
      <div class="scan-camera-wrap">
        <video id="scan-video" playsinline muted></video>
        <canvas id="scan-canvas" width="480" height="480"></canvas>
        <div class="scan-camera-overlay" id="scan-camera-overlay">⏳ Starting camera…</div>
        <div class="scan-frame" id="scan-frame" style="display:none"></div>
        <div class="scan-scanline" id="scan-scanline" style="display:none"></div>
      </div>
      <div class="scan-err" id="scan-status" style="display:none"></div>
      <div class="scan-actions">
        <button class="btn btn-primary btn-md" id="scan-open-attend">🔢 Enter code manually</button>
      </div>
      <p class="scan-hint">Point your camera at the QR code on your lecturer’s screen. Check-in happens automatically when the code is recognised.</p>
    </div>
  </div>
</div>

<script src="{{ asset('assets/js/vendor/jsqr.min.js') }}"></script>
<script src="{{ asset('assets/js/api.js') }}"></script>
<script src="{{ asset('assets/js/app.js') }}"></script>
</body>
</html>
