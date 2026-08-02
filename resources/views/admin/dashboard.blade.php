<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0,viewport-fit=cover"/>
  <meta name="theme-color" content="#6B3318"/>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Admin Dashboard — SUMAS SmartAttend</title>
  <link rel="stylesheet" href="{{ asset('assets/css/design-system.css') }}"/>
  <link rel="stylesheet" href="{{ asset('assets/css/pages.css') }}"/>
  <style>
    .admin-section { display: none; }
    .admin-section.active { display: block; animation: secIn .28s ease; }
    @keyframes secIn { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }
    .dash-dropdown.open { display: block !important; }
    /* clip (not hidden) so the sticky sidebar keeps sticking while still clipping horizontal overflow */
    #admin-dashboard { overflow-x: clip; }
    @media(max-width:900px){ .dash-header{ position:sticky; top:52px!important; z-index:50; } }
    @media(max-width:640px){ .dash-header{ top:44px!important; } }
  </style>
</head>
<body id="admin-dashboard">

<div id="page-loader">
  <div class="ldr-wrap">
    <div class="ldr-logo-row">
      <div class="ldr-crest"><img src="{{ asset('assets/images/sumas-logo.png') }}" alt="SUMAS"/></div>
      <div class="ldr-text-col">
        <div class="ldr-uni">SUMAS SmartAttend</div>
        <div class="ldr-name">Admin <em>Panel</em></div>
        <div class="ldr-tag">Loading dashboard…</div>
      </div>
    </div>
    <div class="ldr-divider"></div>
    <div class="ldr-bar-wrap"><div class="ldr-bar-track"><div class="ldr-bar-fill"></div></div></div>
    <div class="ldr-dots"><div class="ldr-dot"></div><div class="ldr-dot"></div><div class="ldr-dot"></div></div>
  </div>
</div>
<div id="toast-container"></div>

<div class="dash-root admin-root">

  <!-- ══ SIDEBAR ══ -->
  <aside class="dash-sidebar">
    <div class="dash-sb-brand">
      <img src="{{ asset('assets/images/sumas-logo.png') }}" alt="SUMAS" class="dash-sb-logo"/>
      <div><div class="dash-sb-name">Admin Panel</div><div class="dash-sb-sub">SmartAttend</div></div>
    </div>
    <nav class="dash-sb-nav">
      <div class="dash-sb-section">Dashboard</div>
      <a class="dash-nav-link" data-section="overview" href="#"><span class="dash-nav-icon">📊</span> Overview</a>
      <div class="dash-sb-section">Students</div>
      <a class="dash-nav-link" data-section="students" href="#"><span class="dash-nav-icon">👥</span> All Students</a>
      <a class="dash-nav-link" data-section="pending" href="#"><span class="dash-nav-icon">⏳</span> Pending <span class="dash-nav-badge" id="nav-pending-badge">0</span></a>
      <a class="dash-nav-link" data-section="approved" href="#"><span class="dash-nav-icon">✅</span> Approved</a>
      <a class="dash-nav-link" data-section="rejected" href="#"><span class="dash-nav-icon">❌</span> Rejected</a>
      <div class="dash-sb-section">Academic</div>
      <a class="dash-nav-link" data-section="courses" href="#"><span class="dash-nav-icon">📚</span> Courses</a>
      <a class="dash-nav-link" data-section="lecturers" href="#"><span class="dash-nav-icon">👨‍🏫</span> Lecturers</a>
      <a class="dash-nav-link" data-section="faculties" href="#"><span class="dash-nav-icon">🏛</span> Faculties</a>
      <a class="dash-nav-link" data-section="departments" href="#"><span class="dash-nav-icon">🏢</span> Departments</a>
      <a class="dash-nav-link" data-section="levels" href="#"><span class="dash-nav-icon">🎓</span> Levels</a>
      <div class="dash-sb-section">System</div>
      <a class="dash-nav-link" data-section="settings" href="#"><span class="dash-nav-icon">⚙️</span> Settings</a>
      <a class="dash-nav-link" href="{{ route('login') }}"><span class="dash-nav-icon">🔗</span> Student Portal</a>
      <a class="dash-nav-link" href="{{ route('lecturer.login') }}"><span class="dash-nav-icon">👨‍🏫</span> Lecturer Portal</a>
      <a class="dash-nav-link" href="#" data-admin-logout><span class="dash-nav-icon">🔓</span> Sign Out</a>
    </nav>
    <div class="dash-sb-footer">
      <div class="dash-sb-user">
        <div class="dash-sb-avatar" style="background:var(--b800);border:2px solid var(--g400)">A</div>
        <div><div class="dash-sb-user-name">Administrator</div><div class="dash-sb-user-role">SUMAS Admin</div></div>
      </div>
    </div>
  </aside>

  <!-- ══ MAIN ══ -->
  <div class="dash-main">

    <!-- Header -->
    <header class="dash-header">
      <div class="dash-header-logo-row">
        <a href="{{ route('admin.dashboard') }}" style="display:flex;align-items:center;gap:10px;text-decoration:none">
          <img src="{{ asset('assets/images/sumas-logo.png') }}" alt="SUMAS" class="dash-header-logo"/>
          <div>
            <div class="dash-header-page-name" id="admin-page-title">Overview</div>
            <div style="font-size:.6rem;color:var(--t-light);letter-spacing:.5px">SUMAS Admin</div>
          </div>
        </a>
      </div>
      <div class="dash-header-mid">
        <div class="dash-search-wrap">
          <span class="icon-left">🔍</span>
          <input type="text" class="dash-search" id="header-search" placeholder="Quick search students…"/>
        </div>
      </div>
      <div class="dash-header-right">
        <button class="dash-hdr-btn" id="theme-toggle" aria-label="Toggle theme">🌙</button>
        <div style="position:relative">
          <div class="dash-hdr-avatar" id="user-menu-btn" style="background:var(--b800);border:2px solid var(--g400);cursor:pointer">A</div>
          <div class="dash-dropdown" id="user-menu-dd" style="display:none">
            <div class="dd-head">Administrator</div>
            <div class="dd-item" onclick="adminShowSection('settings')"><span>⚙️</span> Settings</div>
            <div class="dd-divider"></div>
            <div class="dd-item danger" data-admin-logout><span>🔓</span> Sign Out</div>
          </div>
        </div>
      </div>
    </header>

    <!-- Content -->
    <div class="dash-content">

      <!-- ════ OVERVIEW ════ -->
      <section class="admin-section active" id="section-overview">
        <div class="dash-page-title">
          <div><div class="dash-page-title-text">Dashboard Overview</div><div class="dash-page-title-sub">Registration statistics and recent activity</div></div>
          <div style="display:flex;gap:var(--s3);flex-wrap:wrap">
            <button class="btn btn-secondary btn-sm" id="export-csv-btn">⬇ Export CSV</button>
            <button class="btn btn-gold btn-sm" id="bulk-approve-btn">✅ Approve All Pending</button>
          </div>
        </div>
        <div class="admin-stat-grid">
          <div class="metric-card mc-gray"><div class="metric-card-icon">👥</div><div class="metric-card-val" id="ov-total">—</div><div class="metric-card-lbl">Total Registered</div></div>
          <div class="metric-card mc-gold"><div class="metric-card-icon">⏳</div><div class="metric-card-val" id="ov-pending">—</div><div class="metric-card-lbl">Pending Review</div></div>
          <div class="metric-card mc-green"><div class="metric-card-icon">✅</div><div class="metric-card-val" id="ov-approved">—</div><div class="metric-card-lbl">Approved</div></div>
          <div class="metric-card mc-brand"><div class="metric-card-icon">❌</div><div class="metric-card-val" id="ov-rejected">—</div><div class="metric-card-lbl">Rejected</div></div>
          <div class="metric-card" style="border-top:3px solid var(--g400)"><div class="metric-card-icon">🤖</div><div class="metric-card-val" id="ov-verified">—</div><div class="metric-card-lbl">AI Verified</div></div>
        </div>
        <div class="dash-grid-2">
          <div class="widget">
            <div class="widget-head">
              <div class="widget-head-left"><div class="widget-head-icon">📋</div><span class="widget-title">Recent Registrations</span></div>
              <span class="widget-action" onclick="adminShowSection('students')" style="cursor:pointer">View all →</span>
            </div>
            <div style="overflow-x:auto">
              <table class="admin-table">
                <thead><tr><th>Student</th><th>Department</th><th>Level</th><th>Date</th><th>Status</th></tr></thead>
                <tbody id="recent-tbody"><tr><td colspan="5" style="text-align:center;padding:24px;color:var(--t-muted);font-style:italic">Loading…</td></tr></tbody>
              </table>
            </div>
          </div>
          <div class="widget">
            <div class="widget-head"><div class="widget-head-left"><div class="widget-head-icon">🏛</div><span class="widget-title">Students by Department</span></div></div>
            <div class="widget-body"><div id="dept-chart"><p style="color:var(--t-muted);font-size:.85rem;font-style:italic">Loading…</p></div></div>
          </div>
        </div>
      </section>

      <!-- ════ ALL STUDENTS ════ -->
      <section class="admin-section" id="section-students">
        <div class="dash-page-title">
          <div><div class="dash-page-title-text">All Students</div><div class="dash-page-title-sub">Manage all registered students — click a row to manage</div></div>
        </div>
        <div class="admin-table-wrap">
          <div class="admin-table-head">
            <div class="table-filters">
              <button class="filter-btn active" data-filter="all">All</button>
              <button class="filter-btn" data-filter="Pending">⏳ Pending</button>
              <button class="filter-btn" data-filter="Approved">✅ Approved</button>
              <button class="filter-btn" data-filter="Rejected">❌ Rejected</button>
            </div>
            <div style="display:flex;align-items:center;gap:var(--s3);flex-wrap:wrap">
              <div class="admin-table-search"><input type="text" id="student-search" placeholder="Search name, matric, email…"/></div>
              <span style="font-size:.78rem;color:var(--t-muted)" id="table-count">—</span>
            </div>
          </div>
          <div style="overflow-x:auto">
            <table class="admin-table">
              <thead>
                <tr>
                  <th data-sort="name">Student</th>
                  <th>Matric No.</th>
                  <th>Faculty</th>
                  <th>Department</th>
                  <th>Level</th>
                  <th>Verified</th>
                  <th data-sort="date">Submitted</th>
                  <th data-sort="status">Status</th>
                </tr>
              </thead>
              <tbody id="students-tbody">
                <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--t-muted);font-style:italic">Loading…</td></tr>
              </tbody>
            </table>
          </div>
          <div class="admin-table-footer">
            <span>Click any row to view full details and approve, reject, or delete.</span>
            <div style="display:flex;gap:var(--s3)">
              <button class="btn btn-primary btn-sm" id="create-student-btn">+ Create Student</button>
              <button class="btn btn-gold btn-sm" onclick="document.getElementById('bulk-approve-btn').click()">✅ Approve All Pending</button>
            </div>
          </div>
        </div>
      </section>

      <!-- ════ PENDING ════ -->
      <section class="admin-section" id="section-pending">
        <div class="dash-page-title">
          <div><div class="dash-page-title-text">Pending Review</div><div class="dash-page-title-sub">Students awaiting admin approval</div></div>
          <button class="btn btn-gold btn-md" onclick="document.getElementById('bulk-approve-btn').click()">✅ Approve All Pending</button>
        </div>
        <div class="admin-table-wrap">
          <div class="admin-table-head">
            <div class="admin-table-search"><input type="text" id="pending-search" placeholder="Search…"/></div>
            <span style="font-size:.78rem;color:var(--t-muted)" id="pending-count">—</span>
          </div>
          <div style="overflow-x:auto">
            <table class="admin-table">
              <thead><tr><th>Student</th><th>Matric</th><th>Faculty</th><th>Department</th><th>Level</th><th>Verified</th><th>Submitted</th><th>Status</th></tr></thead>
              <tbody id="pending-tbody"><tr><td colspan="8" style="text-align:center;padding:40px;color:var(--t-muted);font-style:italic">Loading…</td></tr></tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ════ APPROVED ════ -->
      <section class="admin-section" id="section-approved">
        <div class="dash-page-title"><div><div class="dash-page-title-text">Approved Students</div></div></div>
        <div class="admin-table-wrap">
          <div class="admin-table-head">
            <div class="admin-table-search"><input type="text" id="approved-search" placeholder="Search…"/></div>
            <span style="font-size:.78rem;color:var(--t-muted)" id="approved-count">—</span>
          </div>
          <div style="overflow-x:auto">
            <table class="admin-table">
              <thead><tr><th>Student</th><th>Matric</th><th>Faculty</th><th>Department</th><th>Level</th><th>Verified</th><th>Submitted</th><th>Status</th></tr></thead>
              <tbody id="approved-tbody"><tr><td colspan="8" style="text-align:center;padding:40px;color:var(--t-muted);font-style:italic">Loading…</td></tr></tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ════ REJECTED ════ -->
      <section class="admin-section" id="section-rejected">
        <div class="dash-page-title"><div><div class="dash-page-title-text">Rejected Registrations</div></div></div>
        <div class="admin-table-wrap">
          <div class="admin-table-head">
            <div class="admin-table-search"><input type="text" id="rejected-search" placeholder="Search…"/></div>
            <span style="font-size:.78rem;color:var(--t-muted)" id="rejected-count">—</span>
          </div>
          <div style="overflow-x:auto">
            <table class="admin-table">
              <thead><tr><th>Student</th><th>Matric</th><th>Faculty</th><th>Department</th><th>Level</th><th>Verified</th><th>Submitted</th><th>Status</th></tr></thead>
              <tbody id="rejected-tbody"><tr><td colspan="8" style="text-align:center;padding:40px;color:var(--t-muted);font-style:italic">Loading…</td></tr></tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ════ COURSES ════ -->
      <section class="admin-section" id="section-courses">
        <div class="dash-page-title"><div><div class="dash-page-title-text">Course Management</div><div class="dash-page-title-sub">Create and manage courses, assign lecturers</div></div></div>
        <div class="admin-table-wrap">
          <div class="admin-table-head">
            <button class="btn btn-primary btn-md" id="create-course-btn">+ Create Course</button>
            <span style="font-size:.78rem;color:var(--t-muted)" id="courses-count">—</span>
          </div>
          <div style="overflow-x:auto">
            <table class="admin-table">
              <thead><tr><th>Code</th><th>Name</th><th>Faculty</th><th>Department</th><th>Level</th><th>Credits</th><th>Lecturers</th><th>Status</th><th>Actions</th></tr></thead>
              <tbody id="courses-tbody"><tr><td colspan="9" style="text-align:center;padding:40px;color:var(--t-muted);font-style:italic">Loading…</td></tr></tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ════ LECTURERS ════ -->
      <section class="admin-section" id="section-lecturers">
        <div class="dash-page-title"><div><div class="dash-page-title-text">Lecturer Management</div><div class="dash-page-title-sub">Create and manage lecturer accounts</div></div></div>
        <div class="admin-table-wrap">
          <div class="admin-table-head">
            <button class="btn btn-primary btn-md" id="create-lecturer-btn">+ Create Lecturer</button>
            <span style="font-size:.78rem;color:var(--t-muted)" id="lecturers-count">—</span>
          </div>
          <div style="overflow-x:auto">
            <table class="admin-table">
              <thead><tr><th>Name</th><th>Email</th><th>Faculty</th><th>Department</th><th>Phone</th><th>Courses</th><th>Status</th><th>Actions</th></tr></thead>
              <tbody id="lecturers-tbody"><tr><td colspan="8" style="text-align:center;padding:40px;color:var(--t-muted);font-style:italic">Loading…</td></tr></tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ════ FACULTIES ════ -->
      <section class="admin-section" id="section-faculties">
        <div class="dash-page-title"><div><div class="dash-page-title-text">Faculty Management</div><div class="dash-page-title-sub">Create and manage faculties</div></div></div>
        <div class="admin-table-wrap">
          <div class="admin-table-head">
            <button class="btn btn-primary btn-md" id="create-faculty-btn">+ Create Faculty</button>
            <span style="font-size:.78rem;color:var(--t-muted)" id="faculties-count">—</span>
          </div>
          <div style="overflow-x:auto">
            <table class="admin-table">
              <thead><tr><th>Faculty</th><th>Code</th><th>Description</th><th>Departments</th><th>Status</th><th>Actions</th></tr></thead>
              <tbody id="faculties-tbody"><tr><td colspan="6" style="text-align:center;padding:40px;color:var(--t-muted);font-style:italic">Loading…</td></tr></tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ════ DEPARTMENTS ════ -->
      <section class="admin-section" id="section-departments">
        <div class="dash-page-title"><div><div class="dash-page-title-text">Department Management</div><div class="dash-page-title-sub">Create and manage departments under faculties</div></div></div>
        <div class="admin-table-wrap">
          <div class="admin-table-head">
            <button class="btn btn-primary btn-md" id="create-department-btn">+ Create Department</button>
            <span style="font-size:.78rem;color:var(--t-muted)" id="departments-count">—</span>
          </div>
          <div style="overflow-x:auto">
            <table class="admin-table">
              <thead><tr><th>Department</th><th>Code</th><th>Faculty</th><th>Courses</th><th>Lecturers</th><th>Status</th><th>Actions</th></tr></thead>
              <tbody id="departments-tbody"><tr><td colspan="7" style="text-align:center;padding:40px;color:var(--t-muted);font-style:italic">Loading…</td></tr></tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ════ LEVELS ════ -->
      <section class="admin-section" id="section-levels">
        <div class="dash-page-title"><div><div class="dash-page-title-text">Academic Levels</div><div class="dash-page-title-sub">Create and manage academic levels</div></div></div>
        <div class="admin-table-wrap">
          <div class="admin-table-head">
            <button class="btn btn-primary btn-md" id="create-level-btn">+ Create Level</button>
            <span style="font-size:.78rem;color:var(--t-muted)" id="levels-count">—</span>
          </div>
          <div style="overflow-x:auto">
            <table class="admin-table">
              <thead><tr><th>Level</th><th>Code</th><th>Sort Order</th><th>Description</th><th>Status</th><th>Actions</th></tr></thead>
              <tbody id="levels-tbody"><tr><td colspan="6" style="text-align:center;padding:40px;color:var(--t-muted);font-style:italic">Loading…</td></tr></tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ════ SETTINGS ════ -->
      <section class="admin-section" id="section-settings">
        <div class="dash-page-title"><div><div class="dash-page-title-text">Admin Settings</div><div class="dash-page-title-sub">System configuration — managed via Laravel</div></div></div>
        <div class="dash-grid-2">
          <div class="settings-card">
            <div class="settings-head">🔐 Change Admin Password</div>
            <div class="settings-body">
              <p style="font-size:.85rem;color:var(--t-muted);margin-bottom:var(--s5)">Change the password you use to sign in to this admin panel.</p>
              <div class="form-group">
                <label class="form-label">Current Password</label>
                <input type="password" id="settings-current-pw" class="input" placeholder="Your current password" autocomplete="current-password"/>
              </div>
              <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="password" id="settings-new-pw" class="input" placeholder="Min. 8 characters" autocomplete="new-password"/>
              </div>
              <div class="form-group">
                <label class="form-label">Confirm New Password</label>
                <input type="password" id="settings-confirm-pw" class="input" placeholder="Repeat new password" autocomplete="new-password"/>
              </div>
              <button class="btn btn-primary btn-md btn-block" id="settings-save-btn">Save Changes</button>
            </div>
          </div>
          <div class="settings-card">
            <div class="settings-head">📋 System Information</div>
            <div class="settings-body">
              <div class="info-rows">
                <div class="info-row"><span class="info-key">System</span><span class="info-val">SUMAS SmartAttend v5</span></div>
                <div class="info-row"><span class="info-key">Backend</span><span class="info-val">Laravel 12 + MySQL</span></div>
                <div class="info-row"><span class="info-key">Auth</span><span class="info-val">Laravel Sanctum (Bearer)</span></div>
                <div class="info-row"><span class="info-key">University</span><span class="info-val">SUMAS, Igbo Eno, Enugu State</span></div>
                <div class="info-row"><span class="info-key">API Base</span><span class="info-val" style="font-family:var(--f-mono);font-size:.78rem">http://localhost:8000/api</span></div>
              </div>
            </div>
          </div>
        </div>
      </section>

    </div><!-- /dash-content -->
  </div><!-- /dash-main -->
</div><!-- /dash-root -->

<!-- COURSE MODAL -->
<div class="student-modal" id="course-modal" style="display:none">
  <div class="modal-box">
    <div class="modal-head">
      <div class="modal-name" id="course-modal-title">Create Course</div>
      <button class="modal-close" id="course-modal-close">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="course-id"/>
      <div class="form-group">
        <label class="form-label">Course Code</label>
        <input type="text" id="course-code" class="input" placeholder="e.g., MED101"/>
      </div>
      <div class="form-group">
        <label class="form-label">Course Name</label>
        <input type="text" id="course-name" class="input" placeholder="e.g., Introduction to Medicine"/>
      </div>
      <div class="form-group">
        <label class="form-label">Faculty</label>
        <select id="course-faculty" class="input">
          <option value="">Select Faculty</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Department</label>
        <select id="course-dept" class="input">
          <option value="">Select Department</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea id="course-desc" class="input" rows="3" placeholder="Course description (optional)"></textarea>
      </div>
      <div class="dash-grid-2">
        <div class="form-group">
          <label class="form-label">Credit Units</label>
          <input type="number" id="course-credits" class="input" min="0" max="10" value="3"/>
        </div>
        <div class="form-group">
          <label class="form-label">Level</label>
          <select id="course-level" class="input">
            <option value="100">100</option>
            <option value="200">200</option>
            <option value="300">300</option>
            <option value="400">400</option>
            <option value="500">500</option>
            <option value="600">600</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Status</label>
        <select id="course-active" class="input">
          <option value="true">Active</option>
          <option value="false">Inactive</option>
        </select>
      </div>
    </div>
    <div class="modal-actions">
      <button class="btn btn-primary btn-lg" id="course-save-btn">Save Course</button>
    </div>
  </div>
</div>

<!-- LECTURER MODAL -->
<div class="student-modal" id="lecturer-modal" style="display:none">
  <div class="modal-box">
    <div class="modal-head">
      <div class="modal-name" id="lecturer-modal-title">Create Lecturer</div>
      <button class="modal-close" id="lecturer-modal-close">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="lecturer-id"/>
      <div class="form-group">
        <label class="form-label">Full Name</label>
        <input type="text" id="lecturer-name" class="input" placeholder="e.g., Dr. John Doe"/>
      </div>
      <div class="form-group">
        <label class="form-label">Email</label>
        <input type="email" id="lecturer-email" class="input" placeholder="e.g., john.doe@sumas.edu.ng"/>
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" id="lecturer-password" class="input" placeholder="Min. 8 characters"/>
      </div>
      <div class="form-group">
        <label class="form-label">Phone</label>
        <input type="text" id="lecturer-phone" class="input" placeholder="Phone number (optional)"/>
      </div>
      <div class="form-group">
        <label class="form-label">Faculty</label>
        <select id="lecturer-faculty" class="input">
          <option value="">Select Faculty</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Department</label>
        <select id="lecturer-dept" class="input">
          <option value="">Select Department</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Bio</label>
        <textarea id="lecturer-bio" class="input" rows="3" placeholder="Brief bio (optional)"></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Status</label>
        <select id="lecturer-active" class="input">
          <option value="true">Active</option>
          <option value="false">Inactive</option>
        </select>
      </div>
    </div>
    <div class="modal-actions">
      <button class="btn btn-primary btn-lg" id="lecturer-save-btn">Save Lecturer</button>
    </div>
  </div>
</div>

<!-- FACULTY MODAL -->
<div class="student-modal" id="faculty-modal" style="display:none">
  <div class="modal-box">
    <div class="modal-head">
      <div class="modal-name" id="faculty-modal-title">Create Faculty</div>
      <button class="modal-close" id="faculty-modal-close">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="faculty-id"/>
      <div class="form-group">
        <label class="form-label">Faculty Name</label>
        <input type="text" id="faculty-name" class="input" placeholder="e.g., Faculty of Clinical Sciences"/>
      </div>
      <div class="form-group">
        <label class="form-label">Code</label>
        <input type="text" id="faculty-code" class="input" placeholder="e.g., FCS"/>
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea id="faculty-desc" class="input" rows="3" placeholder="Faculty description (optional)"></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Status</label>
        <select id="faculty-active" class="input">
          <option value="true">Active</option>
          <option value="false">Inactive</option>
        </select>
      </div>
    </div>
    <div class="modal-actions">
      <button class="btn btn-primary btn-lg" id="faculty-save-btn">Save Faculty</button>
    </div>
  </div>
</div>

<!-- DEPARTMENT MODAL -->
<div class="student-modal" id="department-modal" style="display:none">
  <div class="modal-box">
    <div class="modal-head">
      <div class="modal-name" id="department-modal-title">Create Department</div>
      <button class="modal-close" id="department-modal-close">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="department-id"/>
      <div class="form-group">
        <label class="form-label">Department Name</label>
        <input type="text" id="department-name" class="input" placeholder="e.g., Department of Medicine"/>
      </div>
      <div class="form-group">
        <label class="form-label">Code</label>
        <input type="text" id="department-code" class="input" placeholder="e.g., MED"/>
      </div>
      <div class="form-group">
        <label class="form-label">Faculty</label>
        <select id="department-faculty" class="input">
          <option value="">Select Faculty</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea id="department-desc" class="input" rows="3" placeholder="Department description (optional)"></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Status</label>
        <select id="department-active" class="input">
          <option value="true">Active</option>
          <option value="false">Inactive</option>
        </select>
      </div>
    </div>
    <div class="modal-actions">
      <button class="btn btn-primary btn-lg" id="department-save-btn">Save Department</button>
    </div>
  </div>
</div>

<!-- ACADEMIC LEVEL MODAL -->
<div class="student-modal" id="level-modal" style="display:none">
  <div class="modal-box">
    <div class="modal-head">
      <div class="modal-name" id="level-modal-title">Create Level</div>
      <button class="modal-close" id="level-modal-close">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="level-id"/>
      <div class="form-group">
        <label class="form-label">Level Name</label>
        <input type="text" id="level-name" class="input" placeholder="e.g., 100 Level"/>
      </div>
      <div class="dash-grid-2">
        <div class="form-group">
          <label class="form-label">Code</label>
          <input type="text" id="level-code" class="input" placeholder="e.g., 100"/>
        </div>
        <div class="form-group">
          <label class="form-label">Sort Order</label>
          <input type="number" id="level-order" class="input" min="0" value="0"/>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea id="level-desc" class="input" rows="3" placeholder="Level description (optional)"></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Status</label>
        <select id="level-active" class="input">
          <option value="true">Active</option>
          <option value="false">Inactive</option>
        </select>
      </div>
    </div>
    <div class="modal-actions">
      <button class="btn btn-primary btn-lg" id="level-save-btn">Save Level</button>
    </div>
  </div>
</div>

<!-- ASSIGN LECTURER MODAL -->
<div class="student-modal" id="assign-lecturer-modal" style="display:none">
  <div class="modal-box">
    <div class="modal-head">
      <div class="modal-name">Assign Lecturer to Course</div>
      <button class="modal-close" id="assign-lecturer-close">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="assign-course-id"/>
      <div class="form-group">
        <label class="form-label">Select Lecturer</label>
        <select id="assign-lecturer-select" class="input">
          <option value="">-- Select lecturer --</option>
        </select>
      </div>
    </div>
    <div class="modal-actions">
      <button class="btn btn-primary btn-lg" id="assign-lecturer-btn">Assign Lecturer</button>
    </div>
  </div>
</div>

<!-- STUDENT CREATE/EDIT MODAL -->
<div class="student-modal" id="student-edit-modal" style="display:none">
  <div class="modal-box">
    <div class="modal-head">
      <div class="modal-name" id="student-edit-modal-title">Create Student</div>
      <button class="modal-close" id="student-edit-modal-close">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="student-edit-id"/>
      <div class="form-group">
        <label class="form-label">Full Name</label>
        <input type="text" id="student-edit-name" class="input" placeholder="e.g., John Doe"/>
      </div>
      <div class="form-group">
        <label class="form-label">Matric Number</label>
        <input type="text" id="student-edit-matric" class="input" placeholder="e.g., 2024/001"/>
      </div>
      <div class="form-group">
        <label class="form-label">Email</label>
        <input type="email" id="student-edit-email" class="input" placeholder="e.g., john.doe@sumas.edu.ng"/>
      </div>
      <div class="form-group">
        <label class="form-label">Phone</label>
        <input type="text" id="student-edit-phone" class="input" placeholder="Phone number"/>
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" id="student-edit-password" class="input" placeholder="Min. 6 characters (leave blank to keep current)"/>
      </div>
      <div class="form-group">
        <label class="form-label">Faculty</label>
        <select id="student-edit-faculty" class="input">
          <option value="">Select Faculty</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Department</label>
        <select id="student-edit-dept" class="input">
          <option value="">Select Department</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Level</label>
        <select id="student-edit-level" class="input">
          <option value="">Select Level</option>
          <option value="100 Level">100 Level</option>
          <option value="200 Level">200 Level</option>
          <option value="300 Level">300 Level</option>
          <option value="400 Level">400 Level</option>
          <option value="500 Level">500 Level</option>
          <option value="600 Level">600 Level</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Gender</label>
        <select id="student-edit-gender" class="input">
          <option value="">Select Gender</option>
          <option value="Male">Male</option>
          <option value="Female">Female</option>
          <option value="Prefer not to say">Prefer not to say</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Date of Birth</label>
        <input type="date" id="student-edit-dob" class="input"/>
      </div>
      <div class="form-group">
        <label class="form-label">State of Origin</label>
        <input type="text" id="student-edit-state" class="input" placeholder="e.g., Enugu"/>
      </div>
      <div class="form-group">
        <label class="form-label">Status</label>
        <select id="student-edit-status" class="input">
          <option value="Pending">Pending</option>
          <option value="Approved">Approved</option>
          <option value="Rejected">Rejected</option>
        </select>
      </div>
    </div>
    <div class="modal-actions">
      <button class="btn btn-primary btn-lg" id="student-edit-save-btn">Save Student</button>
    </div>
  </div>
</div>

<!-- STUDENT DETAIL MODAL -->
<div class="student-modal" id="student-modal" style="display:none">
  <div class="modal-box">
    <div class="modal-head">
      <div class="modal-head-left">
        <div class="modal-avatar" id="m-avatar">S</div>
        <div>
          <div class="modal-name" id="m-name">Student Name</div>
          <div class="modal-matric" id="m-matric">Matric Number</div>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:var(--s3)">
        <span class="badge badge-gold badge-lg" id="m-status-badge">Pending</span>
        <button class="modal-close" id="m-close-btn" aria-label="Close modal">✕</button>
      </div>
    </div>
    <div class="modal-body">
      <div class="modal-grid">
        <div class="modal-field"><label>Faculty</label><div class="val" id="m-faculty">—</div></div>
        <div class="modal-field"><label>Department</label><div class="val" id="m-dept">—</div></div>
        <div class="modal-field"><label>Level</label><div class="val" id="m-level">—</div></div>
        <div class="modal-field"><label>Email</label><div class="val" id="m-email">—</div></div>
        <div class="modal-field"><label>Phone</label><div class="val" id="m-phone">—</div></div>
        <div class="modal-field"><label>Gender</label><div class="val" id="m-gender">—</div></div>
        <div class="modal-field"><label>State of Origin</label><div class="val" id="m-state">—</div></div>
        <div class="modal-field"><label>Date Submitted</label><div class="val" id="m-submitted">—</div></div>
        <div class="modal-field"><label>Face Enrollment</label><div class="val" id="m-verified">—</div></div>
        <div class="modal-field"><label>Documents Uploaded</label><div class="val" id="m-docs">—</div></div>
        <div class="modal-field"><label>Student ID</label><div class="val" id="m-id" style="font-family:var(--f-mono);font-size:.75rem">—</div></div>
      </div>

      <!-- Face enrollment (admin webcam) -->
      <div class="face-enroll-panel">
        <div class="fep-head">🧑‍🏫 Webcam Face Enrollment</div>
        <div class="fep-status" id="fep-status"></div>
        <div class="fep-grid">
          <div class="fep-cam">
            <video id="fep-video" autoplay muted playsinline></video>
            <div class="fep-cam-ph" id="fep-cam-ph">📷 Camera preview</div>
          </div>
          <div class="fep-side">
            <div class="fep-preview-wrap">
              <img id="fep-photo" alt="Enrolled face" style="display:none"/>
              <canvas id="fep-canvas" style="display:none"></canvas>
            </div>
            <div class="fep-actions">
              <button class="btn btn-primary btn-md" id="fep-start-btn">🎥 Start Camera</button>
              <button class="btn btn-gold btn-md" id="fep-capture-btn" disabled>📸 Capture</button>
              <button class="btn btn-success btn-md" id="fep-enroll-btn" disabled>✅ Enroll Face</button>
            </div>
            <p class="fep-hint">Position the student's face in the frame, then capture and enroll.</p>
          </div>
        </div>
      </div>

      <!-- Uploaded documents -->
      <div class="modal-docs">
        <div class="md-head">📄 Uploaded Documents</div>
        <div id="m-doc-list" class="md-list"><p style="color:var(--t-muted);font-size:.82rem;font-style:italic">No documents uploaded.</p></div>
      </div>

      <div class="modal-actions">
        <button class="btn btn-gold btn-lg" id="m-approve-btn">✅ Approve Registration</button>
        <button class="btn btn-secondary btn-lg" id="m-reject-btn">❌ Reject Registration</button>
        <button class="btn btn-danger btn-sm" id="m-delete-btn" style="margin-left:auto">🗑 Delete</button>
      </div>
    </div>
  </div>
</div>

<script src="{{ asset('assets/js/api.js') }}"></script>
<script src="{{ asset('assets/js/admin.js') }}"></script>
</body>
</html>
