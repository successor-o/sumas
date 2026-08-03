<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0,viewport-fit=cover"/>
  <meta name="theme-color" content="#6B3318"/>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Lecturer Dashboard — SUMAS SmartAttend</title>
  <link rel="stylesheet" href="{{ asset('assets/css/design-system.css') }}"/>
  <link rel="stylesheet" href="{{ asset('assets/css/pages.css') }}"/>
  <style>
    /* clip (not hidden) so the sticky sidebar keeps sticking while clipping horizontal overflow */
    #lecturer-dashboard { overflow-x: clip; }
    .dash-dropdown.open { display: block !important; }
    .dash-sidebar { position: sticky; top: 0; height: 100vh; overflow-y: auto; }
    @media(max-width:900px){ .dash-header{ position:sticky; top:52px!important; z-index:50; } }
    @media(max-width:640px){ .dash-header{ top:44px!important; } }

    /* ── Lecture modal button polish (create lecture + manual attendance) ── */
    #lecture-modal .modal-actions, #manual-att-modal .modal-actions { justify-content: flex-end; gap: var(--s3); }
    #lecture-modal .modal-actions .btn, #manual-att-modal .modal-actions .btn {
      min-width: 150px; height: 46px; padding: 0 26px; flex: 0 1 auto;
      border-radius: 12px; font-weight: 700; letter-spacing: .3px; font-size: .875rem;
    }
    #lecture-modal .btn-secondary, #manual-att-modal .btn-secondary {
      background: var(--white); color: var(--t-secondary); border: 1.5px solid var(--bdr);
    }
    #lecture-modal .btn-secondary:hover, #manual-att-modal .btn-secondary:hover {
      background: var(--surf-2); color: var(--t-primary); border-color: var(--brand);
      transform: translateY(-1px); box-shadow: var(--sh-sm);
    }
    #lecture-modal .btn-primary, #manual-att-modal .btn-primary {
      background: linear-gradient(135deg, var(--brand), var(--b700)); color: #fff;
      border-color: var(--brand); box-shadow: 0 8px 20px rgba(107,51,24,.3);
    }
    #lecture-modal .btn-primary:hover, #manual-att-modal .btn-primary:hover {
      background: linear-gradient(135deg, var(--b700), var(--b800)); border-color: var(--b700);
      transform: translateY(-2px); box-shadow: 0 10px 26px rgba(107,51,24,.4);
    }
    #lecture-modal .modal-actions .btn:disabled, #manual-att-modal .modal-actions .btn:disabled {
      opacity: .65; cursor: not-allowed; transform: none; box-shadow: none;
    }
    @media(max-width:640px){
      #lecture-modal .modal-actions, #manual-att-modal .modal-actions { flex-direction: column; }
      #lecture-modal .modal-actions .btn, #manual-att-modal .modal-actions .btn { width: 100%; flex: 1 1 100%; }
    }

    /* Toggle switch */
    .switch-row { display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: .84rem; font-weight: 600; color: var(--t-primary); }
    .switch-row input { display: none; }
    .switch { position: relative; width: 44px; height: 24px; border-radius: 999px; background: var(--bdr); transition: background .2s ease; flex-shrink: 0; }
    .switch::after { content: ''; position: absolute; top: 3px; left: 3px; width: 18px; height: 18px; border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.3); transition: transform .2s ease; }
    .switch-row input:checked + .switch { background: var(--success); }
    .switch-row input:checked + .switch::after { transform: translateX(20px); }
    .form-hint { font-size: .72rem; color: var(--t-muted); margin-top: 4px; }

    /* ── QR modal ── */
    .qr-modal-box { max-width: 430px; text-align: center; }
    .qr-lecture-title { font-size: 1rem; font-weight: 800; color: var(--t-primary); }
    .qr-lecture-sub { font-size: .78rem; color: var(--t-muted); margin-top: 2px; }
    .qr-code-wrap { margin: 18px auto 0; display: inline-block; padding: 14px; background: #fff; border: 1px solid var(--bdr-light); border-radius: var(--r-xl); box-shadow: var(--sh-md); }
    .qr-code-wrap img, .qr-code-wrap canvas { display: block; }
    .qr-scan-hint { margin-top: 12px; font-size: .72rem; color: var(--t-muted); line-height: 1.6; }
    .qr-count { margin-top: 14px; font-size: .84rem; font-weight: 800; color: var(--success); }
    .qr-link-row { display: flex; gap: var(--s2); margin-top: 14px; }
    .qr-link-row .input { font-size: .7rem; font-family: var(--f-mono); }
    .qr-code-meta { font-size: .72rem; color: var(--t-muted); margin-top: 6px; line-height: 1.6; }
    .qr-code-hint { font-size: .74rem; color: var(--t-secondary); margin-top: 10px; background: var(--surf-2); border: 1px dashed var(--bdr); border-radius: var(--r-md); padding: 10px 12px; }
  </style>
</head>
<body id="lecturer-dashboard">

<div id="page-loader"><div class="ldr-wrap">
  <div class="ldr-logo-row">
    <div class="ldr-crest"><img src="{{ asset('assets/images/sumas-logo.png') }}" alt="SUMAS"/></div>
    <div class="ldr-text-col">
      <div class="ldr-uni">SUMAS SmartAttend</div>
      <div class="ldr-name">Lecturer <em>Panel</em></div>
      <div class="ldr-tag">Loading dashboard…</div>
    </div>
  </div>
  <div class="ldr-divider"></div>
  <div class="ldr-bar-wrap"><div class="ldr-bar-track"><div class="ldr-bar-fill"></div></div></div>
  <div class="ldr-dots"><div class="ldr-dot"></div><div class="ldr-dot"></div><div class="ldr-dot"></div></div>
</div></div>
<div id="toast-container"></div>

<div class="dash-root lecturer-dash">

  <!-- ══ SIDEBAR ══ -->
  <aside class="dash-sidebar" role="navigation" aria-label="Dashboard navigation">
    <div class="dash-sb-brand">
      <img src="{{ asset('assets/images/sumas-logo.png') }}" alt="SUMAS" class="dash-sb-logo"/>
      <div><div class="dash-sb-name">Lecturer Panel</div><div class="dash-sb-sub">SmartAttend</div></div>
    </div>
    <nav class="dash-sb-nav">
      <div class="dash-sb-section">Main</div>
      <a class="dash-nav-link active" data-section="overview" href="#"><span class="dash-nav-icon">📊</span> Overview</a>
      <a class="dash-nav-link" data-section="students" href="#"><span class="dash-nav-icon">👨‍🎓</span> Students</a>
      <a class="dash-nav-link" data-section="courses" href="#"><span class="dash-nav-icon">📚</span> My Courses</a>
      <a class="dash-nav-link" data-section="lectures" href="#"><span class="dash-nav-icon">📝</span> Lectures</a>
      <a class="dash-nav-link" data-section="attendance" href="#"><span class="dash-nav-icon">✅</span> Attendance</a>
      <div class="dash-sb-section">Account</div>
      <a class="dash-nav-link" href="#" data-logout><span class="dash-nav-icon">🔓</span> Sign Out</a>
    </nav>
    <div class="dash-sb-footer">
      <div class="dash-sb-user">
        <div class="dash-sb-avatar" data-avatar>L</div>
        <div><div class="dash-sb-user-name" data-user-name>Lecturer</div><div class="dash-sb-user-role">Faculty Member</div></div>
      </div>
    </div>
  </aside>

  <!-- ══ MAIN ══ -->
  <div class="dash-main">

    <!-- Header -->
    <header class="dash-header">
      <div class="dash-header-logo-row">
        <a href="{{ route('lecturer.dashboard') }}" style="display:flex;align-items:center;gap:10px;text-decoration:none">
          <img src="{{ asset('assets/images/sumas-logo.png') }}" alt="SUMAS" class="dash-header-logo"/>
          <div>
            <div class="dash-header-page-name" id="dash-page-title-text">Overview</div>
            <div style="font-size:.6rem;color:var(--t-light);letter-spacing:.5px">SUMAS Faculty Console</div>
          </div>
        </a>
      </div>
      <div class="dash-header-right">
        <button class="dash-hdr-btn" id="theme-toggle" aria-label="Toggle theme">🌙</button>
        <div style="position:relative">
          <div class="dash-hdr-avatar" id="user-menu-btn" style="cursor:pointer" data-avatar role="button" tabindex="0" aria-haspopup="true">L</div>
          <div class="dash-dropdown" id="user-menu-dd" style="display:none">
            <div class="dd-head" data-user-name>Lecturer</div>
            <div class="dd-divider"></div>
            <div class="dd-item danger" data-logout><span>🔓</span> Sign Out</div>
          </div>
        </div>
      </div>
    </header>

    <!-- Content -->
    <div class="dash-content">

      <!-- ════ OVERVIEW ════ -->
      <section class="dash-section active" id="section-overview">
        <div class="wb">
          <div class="wb-bg"></div><div class="wb-glow"></div>
          <div class="wb-inner">
            <div class="wb-left">
              <div class="wb-eyebrow">SUMAS SmartAttend · Faculty Console</div>
              <h2 class="wb-title">Welcome back, <em data-user-name>Lecturer</em> 👋</h2>
              <p class="wb-sub">Manage your courses, students, and take attendance.</p>
            </div>
            <div class="wb-right">
              <div class="wb-stat"><div class="wb-stat-icon">📚</div><div class="wb-stat-val" id="stat-courses">0</div><div class="wb-stat-lbl">My Courses</div></div>
              <div class="wb-stat"><div class="wb-stat-icon">👨‍🎓</div><div class="wb-stat-val" id="stat-students">0</div><div class="wb-stat-lbl">Approved Students</div></div>
              <div class="wb-stat"><div class="wb-stat-icon">📝</div><div class="wb-stat-val" id="stat-lectures">0</div><div class="wb-stat-lbl">Recent Lectures</div></div>
            </div>
          </div>
        </div>

        <div class="metric-grid" style="margin-bottom:var(--s6)">
          <div class="metric-card mc-brand"><div class="metric-card-icon">📚</div><div class="metric-card-val" id="mc-courses">0</div><div class="metric-card-lbl">Assigned Courses</div></div>
          <div class="metric-card mc-green"><div class="metric-card-icon">👨‍🎓</div><div class="metric-card-val" id="mc-students">0</div><div class="metric-card-lbl">Approved Students</div></div>
          <div class="metric-card mc-gold"><div class="metric-card-icon">📝</div><div class="metric-card-val" id="mc-lectures">0</div><div class="metric-card-lbl">Total Lectures</div></div>
          <div class="metric-card mc-gray"><div class="metric-card-icon">✅</div><div class="metric-card-val" id="mc-active">0</div><div class="metric-card-lbl">Active Lectures</div></div>
        </div>

        <div class="dash-grid-2">
          <div class="widget">
            <div class="widget-head">
              <div class="widget-head-left"><div class="widget-head-icon">🕒</div><span class="widget-title">Recent Lectures</span></div>
              <span class="widget-action" onclick="showSection('lectures')" style="cursor:pointer">View all →</span>
            </div>
            <div style="overflow-x:auto">
              <table class="admin-table">
                <thead><tr><th>Lecture</th><th>Scheduled</th><th>Status</th></tr></thead>
                <tbody id="recent-lectures-tbody"><tr><td colspan="3" style="text-align:center;padding:24px;color:var(--t-muted);font-style:italic">Loading…</td></tr></tbody>
              </table>
            </div>
          </div>
          <div class="widget">
            <div class="widget-head">
              <div class="widget-head-left"><div class="widget-head-icon">📚</div><span class="widget-title">My Courses</span></div>
              <span class="widget-action" onclick="showSection('courses')" style="cursor:pointer">View all →</span>
            </div>
            <div style="overflow-x:auto">
              <table class="admin-table">
                <thead><tr><th>Code</th><th>Course</th><th>Level</th></tr></thead>
                <tbody id="overview-courses-tbody"><tr><td colspan="3" style="text-align:center;padding:24px;color:var(--t-muted);font-style:italic">Loading…</td></tr></tbody>
              </table>
            </div>
          </div>
        </div>
      </section>

      <!-- ════ STUDENTS ════ -->
      <section class="dash-section" id="section-students">
        <div class="dash-page-title">
          <div><div class="dash-page-title-text">Students</div><div class="dash-page-title-sub">Approved students in your department</div></div>
        </div>
        <div class="admin-table-wrap">
          <div class="admin-table-head">
            <div class="admin-table-search"><input type="text" id="student-search" placeholder="Search name, matric, email…"/></div>
            <span style="font-size:.78rem;color:var(--t-muted)" id="students-count">—</span>
          </div>
          <div style="overflow-x:auto">
            <table class="admin-table">
              <thead><tr><th>Student</th><th>Matric</th><th>Department</th><th>Level</th><th>Verified</th><th>Status</th></tr></thead>
              <tbody id="students-tbody"><tr><td colspan="6" style="text-align:center;padding:40px;color:var(--t-muted);font-style:italic">Loading…</td></tr></tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ════ MY COURSES ════ -->
      <section class="dash-section" id="section-courses">
        <div class="dash-page-title">
          <div><div class="dash-page-title-text">My Courses</div><div class="dash-page-title-sub">Courses assigned to you</div></div>
        </div>
        <div class="admin-table-wrap">
          <div class="admin-table-head">
            <span style="font-size:.78rem;color:var(--t-muted)" id="courses-count">—</span>
          </div>
          <div style="overflow-x:auto">
            <table class="admin-table">
              <thead><tr><th>Code</th><th>Course</th><th>Department</th><th>Level</th><th>Credits</th><th>Status</th></tr></thead>
              <tbody id="courses-tbody"><tr><td colspan="6" style="text-align:center;padding:40px;color:var(--t-muted);font-style:italic">Loading…</td></tr></tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ════ LECTURES ════ -->
      <section class="dash-section" id="section-lectures">
        <div class="dash-page-title">
          <div><div class="dash-page-title-text">Lectures &amp; Announcements</div><div class="dash-page-title-sub">Create lectures and end them to record attendance</div></div>
          <button class="btn btn-primary btn-md" id="create-lecture-btn">+ Create Lecture</button>
        </div>
        <div class="admin-table-wrap">
          <div class="admin-table-head">
            <span style="font-size:.78rem;color:var(--t-muted)" id="lectures-count">—</span>
          </div>
          <div style="overflow-x:auto">
            <table class="admin-table">
              <thead><tr><th>Lecture</th><th>Course</th><th>Scheduled</th><th>Status</th><th>Actions</th></tr></thead>
              <tbody id="lectures-tbody"><tr><td colspan="5" style="text-align:center;padding:40px;color:var(--t-muted);font-style:italic">Loading…</td></tr></tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ════ ATTENDANCE ════ -->
      <section class="dash-section" id="section-attendance">
        <div class="dash-page-title">
          <div><div class="dash-page-title-text">Attendance Management</div><div class="dash-page-title-sub">Review attendance records for a lecture</div></div>
          <button class="btn btn-primary btn-md" id="add-manual-att-btn">➕ Add Manually</button>
        </div>
        <div class="widget" style="margin-bottom:var(--s5)">
          <div class="widget-head"><div class="widget-head-left"><div class="widget-head-icon">🗓</div><span class="widget-title">Select a Lecture</span></div></div>
          <div class="widget-body">
            <select id="attendance-lecture-select" class="input">
              <option value="">-- Select a lecture --</option>
            </select>
          </div>
        </div>
        <div class="admin-table-wrap">
          <div style="overflow-x:auto">
            <table class="admin-table">
              <thead><tr><th>Student</th><th>Matric</th><th>Status</th><th>Notes</th><th>Marks</th></tr></thead>
              <tbody id="attendance-tbody"><tr><td colspan="5" style="text-align:center;padding:40px;color:var(--t-muted);font-style:italic">Select a lecture to view attendance…</td></tr></tbody>
            </table>
          </div>
        </div>
      </section>

    </div><!-- /dash-content -->
  </div><!-- /dash-main -->
</div><!-- /dash-root -->

<!-- CREATE LECTURE MODAL -->
<div class="student-modal" id="lecture-modal" style="display:none">
  <div class="modal-box">
    <div class="modal-head">
      <div class="modal-name">Create New Lecture</div>
      <button class="modal-close" id="close-lecture-modal">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Course</label>
        <select id="lecture-course" class="input" required>
          <option value="">-- Select course --</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Title</label>
        <input type="text" id="lecture-title" class="input" placeholder="Lecture title" required/>
      </div>
      <div class="form-group">
        <label class="form-label">Content</label>
        <textarea id="lecture-content" class="input" rows="5" placeholder="Lecture content or announcement" required></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Scheduled Date</label>
        <input type="datetime-local" id="lecture-date" class="input" required/>
      </div>
      <div class="form-group">
        <label class="form-label">Face Scan Check-In</label>
        <label class="switch-row">
          <input type="checkbox" id="lecture-attendance-enabled" checked/>
          <span class="switch"></span>
          <span>Enable face scan check-in for this lecture</span>
        </label>
        <p class="form-hint">When the lecture is active, students verify their identity with a face scan to mark attendance.</p>
      </div>
      <div class="form-group">
        <label class="form-label">Attendance Marks (optional)</label>
        <input type="number" id="lecture-attendance-score" class="input" min="0" max="999.99" step="0.5" placeholder="e.g. 2" inputmode="decimal"/>
        <p class="form-hint">Marks each student earns for attending this lecture. Leave blank for no marks (e.g. 0.5, 1, 2).</p>
      </div>
      <div class="form-group">
        <label class="form-label">GPS Check-in (optional)</label>
        <label class="switch-row">
          <input type="checkbox" id="lecture-gps-required"/>
          <span class="switch"></span>
          <span>Require students to be near this venue</span>
        </label>
        <p class="form-hint">When enabled, your current location is captured and students must be within the attendance radius. Requires HTTPS in production.</p>
      </div>
    </div>
    <div class="modal-actions">
      <button class="btn btn-secondary" id="cancel-lecture">Cancel</button>
      <button class="btn btn-primary" id="save-lecture">Create Lecture</button>
    </div>
  </div>
</div>

<!-- FACE CHECK-IN LINK MODAL -->
<div class="student-modal" id="qr-modal" style="display:none">
  <div class="modal-box qr-modal-box">
    <div class="modal-head">
      <div class="modal-name">🤖 Face Check-In Link</div>
      <button class="modal-close" id="close-qr-modal">✕</button>
    </div>
    <div class="modal-body">
      <div class="qr-lecture-title" id="qr-lecture-title">—</div>
      <div class="qr-lecture-sub" id="qr-lecture-sub">—</div>
      <div class="qr-code-wrap"><div id="qr-code"></div></div>
      <div class="qr-scan-hint">Students scan this code with their phone camera to open the <strong>face check-in</strong> page — their identity is verified with a live face scan.</div>
      <div class="qr-code-meta" id="qr-rotation-countdown"></div>
      <div class="qr-count" id="qr-count">—</div>
      <div class="qr-link-row">
        <input type="text" id="qr-link" class="input" readonly/>
        <button class="btn btn-secondary btn-md" id="qr-copy-btn">Copy</button>
        <button class="btn btn-outline btn-md" id="qr-refresh-btn">↻ Refresh</button>
      </div>
    </div>
  </div>
</div>

<!-- MANUAL ATTENDANCE MODAL -->
<div class="student-modal" id="manual-att-modal" style="display:none">
  <div class="modal-box">
    <div class="modal-head">
      <div class="modal-name">Add Attendance Manually</div>
      <button class="modal-close" id="close-manual-att">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-group" id="manual-att-target" style="margin-bottom:var(--s4)"></div>
      <div class="form-group">
        <label class="form-label">Student</label>
        <select id="manual-student" class="input" required></select>
      </div>
      <div class="form-group">
        <label class="form-label">Status</label>
        <select id="manual-status" class="input">
          <option value="present">Present</option>
          <option value="late">Late</option>
          <option value="absent">Absent</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Notes (optional)</label>
        <input type="text" id="manual-notes" class="input" placeholder="e.g. arrived 25 minutes late"/>
      </div>
    </div>
    <div class="modal-actions">
      <button class="btn btn-secondary" id="cancel-manual-att">Cancel</button>
      <button class="btn btn-primary" id="save-manual-att">Record Attendance</button>
    </div>
  </div>
</div>

<script src="{{ asset('assets/js/vendor/qrcode.min.js') }}"></script>
<script src="{{ asset('assets/js/api.js') }}"></script>
<script src="{{ asset('assets/js/app.js') }}"></script>
<script>
/* ── Lecturer Dashboard ── */
if (document.getElementById('lecturer-dashboard')) {
  SessionStore.requireLecturer('/lecturer/login');

  const setText = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v || '—'; };
  function escHtml(str) { return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
  function statusBadge(s) { return { Pending: 'badge-gold', Approved: 'badge-green', Rejected: 'badge-red' }[s] || 'badge-gray'; }

  let allStudents = [];
  let allCourses  = [];
  let allLectures = [];
  let courseMap   = {};

  function courseLabel(id) { return courseMap[id] ? `${courseMap[id].code} — ${courseMap[id].name}` : '—'; }

  /* ── Dashboard data ── */
  async function loadDashboard() {
    const res = await API.lecturer.dashboard();
    if (!res.ok) {
      if (res.status === 401 || res.status === 403) {
        SessionStore.clear();
        window.location.href = '/lecturer/login';
        return;
      }
      showToast('error', 'Load Error', res.data.message || 'Could not load dashboard data.');
      return;
    }
    const data = res.data;
    const first = (data.lecturer.name || 'Lecturer').split(' ')[0];
    const init  = first[0]?.toUpperCase() || 'L';
    document.querySelectorAll('[data-avatar]').forEach(el => el.textContent = init);
    document.querySelectorAll('[data-user-name]').forEach(el => el.textContent = data.lecturer.name || 'Lecturer');

    setText('stat-courses', data.courses_count);
    setText('stat-students', data.students_count);
    setText('stat-lectures', data.recent_lectures?.length || 0);
    setText('mc-courses', data.courses_count);
    setText('mc-students', data.students_count);
    setText('mc-lectures', data.recent_lectures?.length || 0);
    setText('mc-active', (data.recent_lectures || []).filter(l => l.is_active).length);

    renderRecentLectures(data.recent_lectures || []);
  }

  function renderRecentLectures(lectures) {
    const tbody = document.getElementById('recent-lectures-tbody');
    if (!tbody) return;
    if (!lectures.length) {
      tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;padding:24px;color:var(--t-muted);font-style:italic">No lectures yet.</td></tr>';
      return;
    }
    tbody.innerHTML = lectures.map(l => `
      <tr>
        <td><div style="font-weight:600;font-size:.85rem">${escHtml(l.title)}</div></td>
        <td style="font-size:.8rem">${new Date(l.scheduled_date).toLocaleString()}</td>
        <td><span class="badge ${l.is_active ? 'badge-green' : 'badge-gray'}">${l.is_active ? 'Active' : 'Ended'}</span></td>
      </tr>`).join('');
  }

  function renderOverviewCourses(courses) {
    const tbody = document.getElementById('overview-courses-tbody');
    if (!tbody) return;
    if (!courses.length) {
      tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;padding:24px;color:var(--t-muted);font-style:italic">No courses assigned.</td></tr>';
      return;
    }
    tbody.innerHTML = courses.slice(0, 5).map(c => `
      <tr>
        <td><strong style="font-size:.82rem">${escHtml(c.code)}</strong></td>
        <td style="font-size:.83rem">${escHtml(c.name)}</td>
        <td style="font-size:.82rem">${escHtml(c.level || '—')}</td>
      </tr>`).join('');
  }

  /* ── Students ── */
  async function loadStudents() {
    const res = await API.lecturer.students();
    if (!res.ok) {
      if (res.status === 401 || res.status === 403) { SessionStore.clear(); window.location.href = '/lecturer/login'; return; }
      showToast('error', 'Load Error', 'Could not load students.');
      return;
    }
    allStudents = res.data.students || [];
    renderStudents();
  }

  function renderStudents() {
    const tbody = document.getElementById('students-tbody');
    if (!tbody) return;
    const q = (document.getElementById('student-search')?.value || '').toLowerCase();
    const filtered = q
      ? allStudents.filter(s => (s.name + ' ' + s.matric + ' ' + (s.email || '')).toLowerCase().includes(q))
      : allStudents;
    const count = document.getElementById('students-count');
    if (count) count.textContent = `${filtered.length} student${filtered.length !== 1 ? 's' : ''}`;
    if (!filtered.length) {
      tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:40px;color:var(--t-muted);font-style:italic">${q ? `No results for "${escHtml(q)}".` : 'No students found.'}</td></tr>`;
      return;
    }
    tbody.innerHTML = filtered.map(s => `
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:10px">
            <div style="width:32px;height:32px;border-radius:50%;background:var(--brand);color:white;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.85rem;flex-shrink:0">${escHtml((s.name || 'S')[0])}</div>
            <div>
              <div style="font-weight:700;font-size:.875rem;color:var(--t-primary)">${escHtml(s.name)}</div>
              <div style="font-size:.72rem;color:var(--t-muted)">${escHtml(s.email || '')}</div>
            </div>
          </div>
        </td>
        <td style="font-size:.83rem;font-weight:600">${escHtml(s.matric)}</td>
        <td style="font-size:.83rem">${escHtml(s.dept || '—')}</td>
        <td style="font-size:.83rem">${escHtml(s.level || '—')}</td>
        <td><span class="badge ${s.verified ? 'badge-green' : 'badge-gold'}">${s.verified ? '✓ Verified' : '⏳ No'}</span></td>
        <td><span class="badge ${statusBadge(s.status)}">${escHtml(s.status)}</span></td>
      </tr>`).join('');
  }

  document.getElementById('student-search')?.addEventListener('input', renderStudents);

  /* ── Courses ── */
  async function loadCourses() {
    const res = await API.lecturer.courses();
    if (!res.ok) {
      if (res.status === 401 || res.status === 403) { SessionStore.clear(); window.location.href = '/lecturer/login'; return; }
      showToast('error', 'Load Error', 'Could not load courses.');
      return;
    }
    allCourses = res.data.courses || [];
    allCourses.forEach(c => { courseMap[c.id] = c; });
    renderCourses();
    populateCourseSelect();
    renderOverviewCourses(allCourses);
  }

  function renderCourses() {
    const tbody = document.getElementById('courses-tbody');
    if (!tbody) return;
    const count = document.getElementById('courses-count');
    if (count) count.textContent = `${allCourses.length} course${allCourses.length !== 1 ? 's' : ''}`;
    if (!allCourses.length) {
      tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:40px;color:var(--t-muted);font-style:italic">No courses assigned.</td></tr>';
      return;
    }
    tbody.innerHTML = allCourses.map(c => `
      <tr>
        <td><strong style="font-size:.82rem">${escHtml(c.code)}</strong></td>
        <td style="font-size:.85rem">${escHtml(c.name)}</td>
        <td style="font-size:.83rem">${escHtml(c.department || '—')}</td>
        <td style="font-size:.83rem">${escHtml(c.level || '—')}</td>
        <td style="font-size:.83rem">${c.credit_units}</td>
        <td><span class="badge ${c.is_active ? 'badge-green' : 'badge-gray'}">${c.is_active ? 'Active' : 'Inactive'}</span></td>
      </tr>`).join('');
  }

  function populateCourseSelect() {
    const select = document.getElementById('lecture-course');
    if (!select) return;
    select.innerHTML = '<option value="">-- Select course --</option>' +
      allCourses.map(c => `<option value="${c.id}">${escHtml(c.code)} - ${escHtml(c.name)}</option>`).join('');
  }

  /* ── Lectures ── */
  async function loadLectures() {
    const res = await API.lecturer.lectures();
    if (!res.ok) {
      if (res.status === 401 || res.status === 403) { SessionStore.clear(); window.location.href = '/lecturer/login'; return; }
      showToast('error', 'Load Error', 'Could not load lectures.');
      return;
    }
    allLectures = res.data.lectures || [];
    renderLectures();
    populateLectureSelect();
    updateDashboardCounts();
  }

  function renderLectures() {
    const tbody = document.getElementById('lectures-tbody');
    if (!tbody) return;
    const count = document.getElementById('lectures-count');
    if (count) count.textContent = `${allLectures.length} lecture${allLectures.length !== 1 ? 's' : ''}`;
    if (!allLectures.length) {
      tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:40px;color:var(--t-muted);font-style:italic">No lectures yet.</td></tr>';
      return;
    }
    tbody.innerHTML = allLectures.map(l => `
      <tr>
        <td><div style="font-weight:600;font-size:.85rem">${escHtml(l.title)}</div><div style="font-size:.72rem;color:var(--t-muted)">${escHtml(String(l.content || '').slice(0, 60))}${(l.content || '').length > 60 ? '…' : ''}</div></td>
        <td style="font-size:.83rem">${escHtml(courseLabel(l.course_id))}</td>
        <td style="font-size:.8rem">${new Date(l.scheduled_date).toLocaleString()}</td>
        <td><span class="badge ${l.is_active ? 'badge-green' : 'badge-gray'}">${l.is_active ? 'Active' : 'Ended'}</span></td>
        <td>
          ${l.is_active
            ? `<div style="display:flex;gap:var(--s2);flex-wrap:wrap">${l.attendance_enabled ? `<button class="btn btn-sm btn-outline" data-show-qr="${l.id}">📱 QR</button>` : ''}<button class="btn btn-sm btn-secondary" data-end-lecture="${l.id}">⏹ End</button></div>`
            : '<span style="font-size:.75rem;color:var(--t-light)">—</span>'}
        </td>
      </tr>`).join('');

    tbody.querySelectorAll('[data-show-qr]').forEach(btn => {
      btn.addEventListener('click', () => {
        const lecture = allLectures.find(l => l.id === parseInt(btn.dataset.showQr));
        if (lecture) openQrModal(lecture);
      });
    });

    tbody.querySelectorAll('[data-end-lecture]').forEach(btn => {
      btn.addEventListener('click', async () => {
        if (!confirm('End this lecture? Attendance will be finalised and absent students notified.')) return;
        const r = await API.lecturer.endLecture(btn.dataset.endLecture);
        if (r.ok) {
          showToast('success', 'Lecture Ended', r.data.message || 'Lecture ended successfully.');
          loadLectures(); loadDashboard();
        } else {
          showToast('error', 'Error', r.data.message || 'Could not end lecture.');
        }
      });
    });
  }

  function populateLectureSelect() {
    const select = document.getElementById('attendance-lecture-select');
    if (!select) return;
    select.innerHTML = '<option value="">-- Select a lecture --</option>' +
      allLectures.map(l => `<option value="${l.id}">${escHtml(l.title)} - ${new Date(l.scheduled_date).toLocaleDateString()}</option>`).join('');
  }

  function updateDashboardCounts() {
    setText('mc-lectures', allLectures.length);
    setText('mc-active', allLectures.filter(l => l.is_active).length);
  }

  /* ── Attendance ── */
  document.getElementById('attendance-lecture-select')?.addEventListener('change', async function () {
    const tbody = document.getElementById('attendance-tbody');
    if (!this.value) {
      tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:40px;color:var(--t-muted);font-style:italic">Select a lecture to view attendance…</td></tr>';
      return;
    }
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:40px;color:var(--t-muted);font-style:italic">Loading attendance…</td></tr>';
    const res = await API.lecturer.getAttendance(this.value);
    if (!res.ok) {
      if (res.status === 401 || res.status === 403) { SessionStore.clear(); window.location.href = '/lecturer/login'; return; }
      showToast('error', 'Load Error', 'Could not load attendance.');
      return;
    }
    renderAttendance(res.data.attendances || []);
  });

  function renderAttendance(attendances) {
    const tbody = document.getElementById('attendance-tbody');
    if (!tbody) return;
    if (!attendances.length) {
      tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:40px;color:var(--t-muted);font-style:italic">No attendance records yet for this lecture.</td></tr>';
      return;
    }
    tbody.innerHTML = attendances.map(a => `
      <tr>
        <td style="font-weight:600;font-size:.85rem">${escHtml(a.student_name)}</td>
        <td style="font-size:.83rem">${escHtml(a.student_matric)}</td>
        <td><span class="badge ${a.status === 'present' ? 'badge-green' : a.status === 'late' ? 'badge-gold' : 'badge-red'}">${escHtml(a.status)}</span></td>
        <td style="font-size:.8rem;color:var(--t-muted)">${escHtml(a.notes || '—')}</td>
        <td>${a.attendance_score != null && a.status !== 'absent' ? `<span class="badge badge-brand">🎯 +${a.attendance_score}</span>` : '<span style="font-size:.75rem;color:var(--t-light)">—</span>'}</td>
      </tr>`).join('');
  }

  /* ── Section navigation ── */
  window.showSection = function (sec) {
    document.querySelectorAll('.dash-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.dash-nav-link').forEach(l => l.classList.remove('active'));
    document.getElementById('section-' + sec)?.classList.add('active');
    document.querySelector(`.dash-nav-link[data-section="${sec}"]`)?.classList.add('active');
    setText('dash-page-title-text', {
      overview: 'Overview', students: 'Students', courses: 'My Courses',
      lectures: 'Lectures', attendance: 'Attendance',
    }[sec] || sec);

    if (sec === 'students') loadStudents();
    if (sec === 'courses') loadCourses();
    if (sec === 'lectures') loadLectures();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  document.querySelectorAll('.dash-nav-link[data-section]').forEach(l => {
    l.addEventListener('click', e => { e.preventDefault(); showSection(l.dataset.section); });
  });

  /* ── Create lecture modal ── */
  const lectureModal = document.getElementById('lecture-modal');
  function openLectureModal() { if (lectureModal) { lectureModal.style.display = 'flex'; void lectureModal.offsetWidth; lectureModal.classList.add('open'); } }
  function closeLectureModal() { if (lectureModal) { lectureModal.classList.remove('open'); setTimeout(() => { lectureModal.style.display = 'none'; }, 300); } }

  document.getElementById('create-lecture-btn')?.addEventListener('click', () => {
    if (!allCourses.length) { showToast('warning', 'No Courses', 'No courses are assigned to you yet.'); return; }
    populateCourseSelect();
    const attToggle = document.getElementById('lecture-attendance-enabled');
    if (attToggle) attToggle.checked = true;
    const gpsToggle = document.getElementById('lecture-gps-required');
    if (gpsToggle) gpsToggle.checked = false;
    openLectureModal();
  });
  document.getElementById('close-lecture-modal')?.addEventListener('click', closeLectureModal);
  document.getElementById('cancel-lecture')?.addEventListener('click', closeLectureModal);
  lectureModal?.addEventListener('click', e => { if (e.target === lectureModal) closeLectureModal(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLectureModal(); });

  document.getElementById('save-lecture')?.addEventListener('click', async () => {
    const courseId = document.getElementById('lecture-course')?.value;
    const title    = document.getElementById('lecture-title')?.value.trim();
    const content  = document.getElementById('lecture-content')?.value.trim();
    const date     = document.getElementById('lecture-date')?.value;

    if (!courseId || !title || !content || !date) {
      showToast('error', 'Required Fields', 'Please fill in all fields.');
      return;
    }

    // Optional attendance marks — validate client-side for a friendlier error.
    const scoreEl = document.getElementById('lecture-attendance-score');
    let attendanceScore = null;
    if (scoreEl && scoreEl.value !== '') {
      attendanceScore = parseFloat(scoreEl.value);
      if (isNaN(attendanceScore) || attendanceScore < 0 || attendanceScore > 999.99) {
        showToast('error', 'Invalid Marks', 'Attendance marks must be between 0 and 999.99.');
        return;
      }
    }

    const btn = document.getElementById('save-lecture');
    btn.disabled = true; btn.textContent = 'Creating…';

    const payload = {
      course_id: parseInt(courseId), title, content, scheduled_date: date,
      attendance_enabled: document.getElementById('lecture-attendance-enabled')?.checked ?? true,
      gps_required: document.getElementById('lecture-gps-required')?.checked ?? false,
    };
    if (attendanceScore !== null) payload.attendance_score = attendanceScore;
    if (payload.gps_required) {
      btn.textContent = 'Getting location…';
      const pos = await getPosition(10000);
      if (!pos) {
        btn.disabled = false; btn.textContent = 'Create Lecture';
        showToast('error', 'Location Needed', 'Allow location access to enable GPS check-in for this lecture.');
        return;
      }
      payload.latitude = pos.lat;
      payload.longitude = pos.lng;
    }

    const res = await API.lecturer.createLecture(payload);
    btn.disabled = false; btn.textContent = 'Create Lecture';

    if (res.ok) {
      showToast('success', 'Lecture Created', 'Your lecture has been created.');
      closeLectureModal();
      document.getElementById('lecture-title').value = '';
      document.getElementById('lecture-content').value = '';
      document.getElementById('lecture-date').value = '';
      const scoreReset = document.getElementById('lecture-attendance-score');
      if (scoreReset) scoreReset.value = '';
      loadLectures();
      loadDashboard();
    } else {
      showToast('error', 'Error', res.data.message || 'Could not create lecture.');
    }
  });

  /* ── Face check-in link modal (live: rotating QR link) ── */
  const qrModal = document.getElementById('qr-modal');
  let qrLectureId = null;
  let qrCurrentToken = null;
  let qrPollTimer = null;
  let qrTickTimer = null;
  let qrData = null;

  function stopQrPolling() {
    if (qrPollTimer) { clearInterval(qrPollTimer); qrPollTimer = null; }
    if (qrTickTimer) { clearInterval(qrTickTimer); qrTickTimer = null; }
  }

  function openQrModal(lecture) {
    if (!lecture || !lecture.token) return;
    qrLectureId = lecture.id;
    qrCurrentToken = null;
    qrData = null;
    document.getElementById('qr-lecture-title').textContent = lecture.title;
    document.getElementById('qr-lecture-sub').textContent = courseLabel(lecture.course_id);
    document.getElementById('qr-code').innerHTML = '';
    document.getElementById('qr-rotation-countdown').textContent = '';
    document.getElementById('qr-link').value = '';
    document.getElementById('qr-count').textContent = '⏳ Loading…';
    qrModal.style.display = 'flex'; void qrModal.offsetWidth; qrModal.classList.add('open');
    pollQrOnce(lecture.id);
    qrPollTimer = setInterval(() => pollQrOnce(lecture.id), 4000);
    qrTickTimer = setInterval(renderQrTicks, 1000);
  }

  function closeQrModal() {
    if (!qrModal) return;
    stopQrPolling();
    qrModal.classList.remove('open');
    setTimeout(() => { qrModal.style.display = 'none'; }, 300);
  }

  async function pollQrOnce(lectureId) {
    const res = await API.lecturer.liveCode(lectureId);
    if (!res.ok) {
      if (res.status === 401 || res.status === 403) { SessionStore.clear(); window.location.href = '/lecturer/login'; return; }
      return;
    }
    qrData = res.data;
    if (res.data.token && res.data.token !== qrCurrentToken) {
      qrCurrentToken = res.data.token;
      const url = res.data.qr_url || (window.location.origin + '/attend/' + res.data.token);
      document.getElementById('qr-link').value = url;
      const host = document.getElementById('qr-code');
      host.innerHTML = '';
      if (typeof QRCode !== 'undefined') {
        new QRCode(host, { text: url, width: 210, height: 210, colorDark: '#1c1917', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.M });
      } else {
        host.innerHTML = '<p style="font-size:.8rem;color:var(--t-muted)">QR library unavailable.</p>';
      }
    }
    const countEl = document.getElementById('qr-count');
    if (countEl && res.data.attendance_count != null) {
      countEl.textContent = `${res.data.attendance_count} student${res.data.attendance_count !== 1 ? 's' : ''} marked attendance`;
    }
    renderQrTicks();
  }

  function renderQrTicks() {
    if (!qrData) return;
    const now = Date.now();
    const rEl = document.getElementById('qr-rotation-countdown');
    if (rEl && qrData.rotation_expires_at) {
      const s = Math.max(0, Math.round((new Date(qrData.rotation_expires_at).getTime() - now) / 1000));
      rEl.textContent = `QR link refreshes in ${s}s`;
    }
  }

  document.getElementById('close-qr-modal')?.addEventListener('click', closeQrModal);
  qrModal?.addEventListener('click', e => { if (e.target === qrModal) closeQrModal(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeQrModal(); closeManualModal(); } });
  document.getElementById('qr-copy-btn')?.addEventListener('click', () => {
    const input = document.getElementById('qr-link');
    if (!input) return;
    input.select(); input.setSelectionRange(0, 99999);
    try { navigator.clipboard.writeText(input.value); } catch {}
    showToast('success', 'Copied', 'Attendance link copied to clipboard.');
  });
  document.getElementById('qr-refresh-btn')?.addEventListener('click', () => {
    if (qrLectureId) pollQrOnce(qrLectureId);
  });

  /* ── Browser geolocation helper (used by GPS lecture creation) ── */
  function getPosition(timeoutMs) {
    return new Promise(resolve => {
      if (!navigator.geolocation) { resolve(null); return; }
      navigator.geolocation.getCurrentPosition(
        pos => resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
        () => resolve(null),
        { enableHighAccuracy: true, timeout: timeoutMs || 8000, maximumAge: 30000 }
      );
    });
  }

  /* ── Manual attendance modal ── */
  const manualModal = document.getElementById('manual-att-modal');
  let currentLectureId = null;
  function openManualModal(lecture) {
    if (!lecture) return;
    currentLectureId = lecture.id;
    document.getElementById('manual-att-target').innerHTML =
      '<span style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--t-muted)">Lecture</span>' +
      '<div style="font-size:.9rem;font-weight:700;color:var(--t-primary)">' + escHtml(lecture.title) + '</div>';
    const select = document.getElementById('manual-student');
    select.innerHTML = '<option value="">-- Select student --</option>' +
      (allStudents.length
        ? allStudents.map(s => `<option value="${s.id}">${escHtml(s.name)} — ${escHtml(s.matric)}</option>`).join('')
        : '<option value="">No students available</option>');
    document.getElementById('manual-notes').value = '';
    manualModal.style.display = 'flex'; void manualModal.offsetWidth; manualModal.classList.add('open');
  }
  function closeManualModal() { if (!manualModal) return; manualModal.classList.remove('open'); setTimeout(() => { manualModal.style.display = 'none'; }, 300); }
  document.getElementById('add-manual-att-btn')?.addEventListener('click', () => {
    const sel = document.getElementById('attendance-lecture-select');
    if (!sel || !sel.value) { showToast('warning', 'Select Lecture', 'Choose a lecture from the list first.'); return; }
    const lecture = allLectures.find(l => l.id === parseInt(sel.value));
    if (!lecture) return;
    if (!allStudents.length) loadStudents();
    openManualModal(lecture);
  });
  document.getElementById('close-manual-att')?.addEventListener('click', closeManualModal);
  document.getElementById('cancel-manual-att')?.addEventListener('click', closeManualModal);
  manualModal?.addEventListener('click', e => { if (e.target === manualModal) closeManualModal(); });
  document.getElementById('save-manual-att')?.addEventListener('click', async () => {
    const studentId = document.getElementById('manual-student').value;
    const status = document.getElementById('manual-status').value;
    const notes = document.getElementById('manual-notes').value.trim();
    if (!currentLectureId || !studentId) { showToast('error', 'Required', 'Select a student.'); return; }
    const btn = document.getElementById('save-manual-att');
    btn.disabled = true; btn.textContent = 'Saving…';
    const res = await API.lecturer.recordAttendance({
      lecture_id: currentLectureId, student_id: parseInt(studentId), status, notes,
    });
    btn.disabled = false; btn.textContent = 'Record Attendance';
    if (res.ok) {
      showToast('success', 'Attendance Recorded', `${status} recorded for the student.`);
      closeManualModal();
      const sel = document.getElementById('attendance-lecture-select');
      if (sel && String(sel.value) === String(currentLectureId)) {
        const tbody = document.getElementById('attendance-tbody');
        if (tbody) tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:40px;color:var(--t-muted);font-style:italic">Loading attendance…</td></tr>';
        const r = await API.lecturer.getAttendance(currentLectureId);
        if (r.ok) renderAttendance(r.data.attendances || []);
      }
    } else {
      showToast('error', 'Error', res.data.message || 'Could not record attendance.');
    }
  });

  /* ── Dropdown + logout ── */
  setupDropdown('user-menu-btn', 'user-menu-dd');
  document.querySelectorAll('[data-logout]').forEach(el => el.addEventListener('click', async () => {
    await API.auth.lecturerLogout();
    SessionStore.clear();
    setTimeout(() => window.location.href = '/lecturer/login', 500);
  }));

  /* ── Init — verify the session on reload, then load the dashboard ── */
  (async function init() {
    if (!(await SessionStore.verify('lecturer', '/lecturer/login'))) return;
    await loadDashboard();
    await loadCourses();
    await loadLectures();
    await loadStudents();
    const name = SessionStore.getUser()?.name?.split(' ')[0] || 'Lecturer';
    setTimeout(() => showToast('success', `Welcome back, ${name}!`, ''), 500);
  })();
}
</script>
</body>
</html>
